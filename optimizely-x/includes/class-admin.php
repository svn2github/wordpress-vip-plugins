<?php
/**
 * Optimizely X: Admin class
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

namespace Optimizely_X;

/**
 * Handles wp-admin functionality for the Optimizely X plugin.
 *
 * @since 1.0.0
 */
class Admin {

	/**
	 * The default conditional template JavaScript.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const DEFAULT_CONDITIONAL_TEMPLATE = <<<JAVASCRIPT
function pollingFn() {
    return document.querySelectorAll( '.post-\$POST_ID' ).length > 0;
}
JAVASCRIPT;

	/**
	 * The default variation template JavaScript.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const DEFAULT_VARIATION_TEMPLATE = <<<JAVASCRIPT
var utils = window['optimizely'].get('utils');
utils.waitForElement( '.post-\$POST_ID h1' ).then( function () {
    var element = document.querySelector( '.post-\$POST_ID h1' );
    element.innerHTML = '\$NEW_TITLE';
} );
utils.waitForElement( '.post-\$POST_ID h3 a' ).then( function () {
    var element = document.querySelector( '.post-\$POST_ID h3 a' );
    element.innerHTML = '\$NEW_TITLE';
} );
JAVASCRIPT;

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var Admin
	 */
	private static $instance;

	/**
	 * Determine whether Optimizely settings are fully initialized.
	 *
	 * If settings are fully initialized, then experiments can be created.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return bool True if all settings are set, false if not.
	 */
	public static function is_initialized() {

		// Try to get the token and the project ID.
		$token = get_option( 'optimizely_token' );
		$project_id = absint( get_option( 'optimizely_project_id' ) );

		return ( ! empty( $token ) && ! empty( $project_id ) );
	}

	/**
	 * Gets the singleton instance.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return Admin
	 */
	public static function instance() {

		// Initialize the instance, if necessary.
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Admin;
			self::$instance->setup();
		}

		return self::$instance;
	}

	/**
	 * Returns an array of supported post type objects, keyed by name.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array
	 */
	public static function supported_post_types() {

		// Get a list of public post types minus pages and attachments.
		$post_types = get_post_types(
			array(
				'show_ui' => true,
			),
			'objects'
		);
		unset( $post_types['page'] );
		unset( $post_types['attachment'] );

		return $post_types;
	}

	/**
	 * Add Optimizely to the admin menu.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function add_menu_page() {
		add_menu_page(
			esc_html__( 'Optimizely', 'optimizely-x' ),
			esc_html__( 'Optimizely', 'optimizely-x' ),
			Filters::admin_capability(),
			'optimizely-config',
			array( $this, 'render_page_config' ),
			OPTIMIZELY_X_BASE_URL . '/admin/images/optimizely-icon.png'
		);
	}

	/**
	 * Add the meta box for title variations.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function add_meta_boxes() {

		// Ensure Optimizely is enabled for this post type.
		if ( ! $this->is_post_type_enabled( get_post_type() ) ) {
			return;
		}

		// Add the meta box.
		add_meta_box(
			'optimizely-headlines',
			esc_html__( 'A/B Test Headlines', 'optimizely-x' ),
			array( $this, 'metabox_headlines_render' ),
			get_post_type(),
			'side',
			'high'
		);
	}

	/**
	 * Registers settings sections and settings fields for use on the options page.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function add_settings() {

		// Define fields to register.
		$fields = array(
			'optimizely_token' => array(
				'label' => esc_html__( 'Personal Token', 'optimizely-x' ),
				'sanitize' => array( $this, 'field_sanitize_token' ),
			),
			'optimizely_project_id' => array(
				'label' => esc_html__( 'Choose a Project', 'optimizely-x' ),
				'sanitize' => 'absint',
			),
			'optimizely_project_name' => array(
				'label' => esc_html__( 'Project Name', 'optimizely-x' ),
				'sanitize' => 'sanitize_text_field',
			),
			'optimizely_post_types' => array(
				'label' => esc_html__( 'Post Types', 'optimizely-x' ),
				'sanitize' => array( $this, 'field_sanitize_post_types' ),
			),
			'optimizely_url_targeting' => array(
				'label' => esc_html__( 'Default URL Targeting', 'optimizely-x' ),
				'sanitize' => array( $this, 'field_sanitize_url_targeting' ),
			),
			'optimizely_url_targeting_type' => array(
				'label' => esc_html__( 'URL Targeting Type', 'optimizely-x' ),
				'sanitize' => array( $this, 'field_sanitize_url_targeting_type' ),
			),
			'optimizely_variation_template' => array(
				'label' => esc_html__( 'Variation Code', 'optimizely-x' ),
				'sanitize' => array( $this, 'field_sanitize_variation_template' ),
			),
			'optimizely_activation_mode' => array(
				'label' => esc_html__( 'Activation Mode', 'optimizely-x' ),
				'sanitize' => array( $this, 'field_sanitize_activation_mode' ),
			),
			'optimizely_conditional_activation_code' => array(
				'label' => esc_html__( 'Conditional Activation Code', 'optimizely-x' ),
				'sanitize' => array( $this, 'field_sanitize_conditional_activation_code' ),
			),
			'optimizely_num_variations' => array(
				'label' => esc_html__( 'Number of Variations', 'optimizely-x' ),
				'sanitize' => array( $this, 'field_sanitize_num_variations' ),
			),
		);

		// Register the config section.
		add_settings_section(
			'optimizely_config_section',
			esc_html__( 'Optimizely Configuration', 'optimizely-x' ),
			null,
			'optimizely_config_options'
		);

		// Loop over field definitions and register each.
		foreach ( $fields as $field_key => $field_properties ) {

			// Add the definition for the field.
			add_settings_field(
				$field_key,
				$field_properties['label'],
				array( $this, 'render_field' ),
				'optimizely_config_options',
				'optimizely_config_section',
				array(
					'field_name' => $field_key,
					'label_for' => str_replace( '_', '-', $field_key ),
				)
			);

			// Register the fields.
			register_setting(
				'optimizely_config_section',
				$field_key,
				$field_properties['sanitize']
			);
		}
	}

	/**
	 * Display admin notices for the plugin.
	 *
	 * @access public
	 */
	public function admin_notices() {

		// Display a message if no token is set or no project is selected.
		if ( ! get_option( 'optimizely_token' ) ) {
			Partials::load( 'admin', 'notices/no-token' );
		} elseif ( 0 === absint( get_option( 'optimizely_project_id' ) ) ) {
			Partials::load( 'admin', 'notices/no-project-id' );
		}
	}

	/**
	 * Enqueues scripts and styles on Optimizely admin pages.
	 *
	 * @param string $hook The admin page the hook was called from.
	 *
	 * @access public
	 */
	public function enqueue_scripts( $hook ) {

		// Enqueue admin stylesheet.
		wp_enqueue_style(
			'optimizely_admin_style',
			OPTIMIZELY_X_BASE_URL . '/admin/css/style.css',
			array(),
			Core::VERSION
		);

		// Enqueue scripts for the configuration page.
		if ( 'toplevel_page_optimizely-config' === $hook ) {

			// Enqueue beautify.js.
			wp_enqueue_script(
				'optimizely_beautify_js',
				OPTIMIZELY_X_BASE_URL . '/admin/js/beautify.min.js',
				array(),
				Core::VERSION,
				true
			);

			// Enqueue main admin configuration script.
			wp_enqueue_script(
				'optimizely_admin_config_script',
				OPTIMIZELY_X_BASE_URL . '/admin/js/config.js',
				array( 'jquery', 'optimizely_beautify_js' ),
				Core::VERSION,
				true
			);
		}

		// Enqueue scripts for the post edit screen.
		if ( 'post.php' === $hook ) {

			// Enqueue the meta box script.
			wp_enqueue_script(
				'optimizely_admin_metabox_script',
				OPTIMIZELY_X_BASE_URL . '/admin/js/metabox.js',
				array( 'jquery' ),
				Core::VERSION,
				true
			);

			// Localize the metabox script.
			wp_localize_script(
				'optimizely_admin_metabox_script',
				'optimizely_metabox_strings',
				array(
					'experiment_error' => __(
						'An error occurred during the creation of the Optimizely experiment.',
						'optimizely-x'
					),
					/* translators: the variation number */
					'no_title' => __(
						'Variation #%d does not have a title set.',
						'optimizely-x'
					),
					'status_error' => __(
						'An error occurred while trying to change the experiment status.',
						'optimizely-x'
					),
				)
			);

			// Create a nonce for use in AJAX requests.
			wp_localize_script(
				'optimizely_admin_metabox_script',
				'optimizely_metabox_nonce',
				array(
					'nonce' => wp_create_nonce( 'optimizely-metabox' ),
				)
			);
		}
	}

	/**
	 * A callback function to sanitize the value of optimizely_activation_mode.
	 *
	 * @param string $option_value The option value passed from the Settings API.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string The sanitized option value.
	 */
	public function field_sanitize_activation_mode( $option_value ) {

		// Default to 'immediate'.
		if ( empty( $option_value ) ) {
			$option_value = 'immediate';
		}

		return sanitize_text_field( $option_value );
	}

	/**
	 * A callback function to sanitize the value of optimizely_conditional_activation_code.
	 *
	 * @param string $option_value The option value passed from the Settings API.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string The sanitized option value.
	 */
	public function field_sanitize_conditional_activation_code( $option_value ) {

		// If not specified, load the default template.
		if ( empty( $option_value ) ) {
			$option_value = self::DEFAULT_CONDITIONAL_TEMPLATE;
		}

		return sanitize_text_field( $option_value );
	}

	/**
	 * A callback function to sanitize the value of optimizely_num_variations.
	 *
	 * @param string $option_value The option value passed from the Settings API.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string The sanitized option value.
	 */
	public function field_sanitize_num_variations( $option_value ) {

		// If not specified, use the default value.
		if ( empty( $option_value ) ) {
			$option_value = 2;
		}

		return sanitize_text_field( $option_value );
	}

	/**
	 * A callback function to sanitize the value of optimizely_post_types.
	 *
	 * @param string $option_value The option value passed from the Settings API.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array The sanitized option value.
	 */
	public function field_sanitize_post_types( $option_value ) {

		// We are expecting an array. If we aren't given an array, reset to empty.
		if ( ! is_array( $option_value ) ) {
			return array();
		}

		// Sanitize against the list of supported post types.
		$option_value = array_intersect(
			array_keys( self::supported_post_types() ),
			$option_value
		);

		return $option_value;
	}

	/**
	 * A callback function to sanitize the value of optimizely_token before saving.
	 *
	 * @param string $option_value The option value passed from the Settings API.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string The sanitized option value.
	 */
	public function field_sanitize_token( $option_value ) {

		// Load the current value and determine if the option value is equivalent
		// to hashing the value saved in the database. If so, use the current value
		// instead of replacing it with the hashed version used for form display.
		$current_value = get_option( 'optimizely_token' );
		$hashed_value = hash( 'ripemd160', $current_value );
		if ( $option_value === $hashed_value ) {
			return $current_value;
		}

		return sanitize_text_field( $option_value );
	}

	/**
	 * A callback function to sanitize the value of optimizely_url_targeting.
	 *
	 * @param string $option_value The option value passed from the Settings API.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string The sanitized option value.
	 */
	public function field_sanitize_url_targeting( $option_value ) {

		// Default to the site url.
		if ( empty( $option_value ) ) {
			$option_value = get_site_url();
		}

		return sanitize_text_field( $option_value );
	}

	/**
	 * A callback function to sanitize the value of optimizely_url_targeting_type.
	 *
	 * @param string $option_value The option value passed from the Settings API.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string The sanitized option value.
	 */
	public function field_sanitize_url_targeting_type( $option_value ) {

		// Default to 'substring'.
		if ( empty( $option_value ) ) {
			$option_value = 'substring';
		}

		return sanitize_text_field( $option_value );
	}

	/**
	 * A callback function to sanitize the value of optimizely_variation_template.
	 *
	 * @param string $option_value The option value passed from the Settings API.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string The sanitized option value.
	 */
	public function field_sanitize_variation_template( $option_value ) {

		// If not specified, load the default template.
		if ( empty( $option_value ) ) {
			$option_value = self::DEFAULT_VARIATION_TEMPLATE;
		}

		return sanitize_text_field( $option_value );
	}

	/**
	 * Display the contents of the meta box.
	 *
	 * @param \WP_Post $post The post object for which to render the metabox.
	 *
	 * @access public
	 */
	public function metabox_headlines_render( $post ) {

		// Handle unauthenticated state.
		if ( false === self::is_initialized() ) {
			Partials::load( 'admin', 'metabox/unauthenticated' );

			return;
		}

		// Handle unpublished state.
		if ( 'publish' !== $post->post_status ) {
			Partials::load( 'admin', 'metabox/unpublished' );

			return;
		}

		// Load primary metabox patial.
		Partials::load( 'admin', 'metabox' );
	}

	/**
	 * Callback function to render the optimizely-config admin menu page.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function render_page_config() {
		Partials::load( 'admin', 'config' );
	}

	/**
	 * A callback function to render a configuration field using the Settings API.
	 *
	 * @param array $args {
	 *      An array of arguments passed to the callback during field registration.
	 *
	 *      @type string $field_name The name of the field that was registered.
	 *                               Required. Used to load the field render partial.
	 * }
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function render_field( $args ) {

		// Ensure a field name was specified for loading the partial.
		if ( empty( $args['field_name'] ) ) {
			return;
		}

		// Load the partial for the field.
		Partials::load( 'admin', 'fields/' . str_replace( '_', '-', $args['field_name'] ) );
	}

	/**
	 * Migrates legacy settings to new formats.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function upgrade_check() {

		// Determine if the database version and code version are the same.
		$current_version = get_option( 'optimizely_version' );
		if ( version_compare( $current_version, Core::VERSION, '>=' ) ) {
			return;
		}

		// Handle upgrade to version 1.0.0.
		if ( version_compare( $current_version, '1.0.0', '<' ) ) {
			$this->upgrade_to_1_0_0();
		}

		// Set the database version to the current version in code.
		update_option( 'optimizely_version', Core::VERSION );
	}

	/**
	 * Empty clone method, forcing the use of the instance() method.
	 *
	 * @see self::instance()
	 *
	 * @access private
	 */
	private function __clone() {
	}

	/**
	 * Empty constructor, forcing the use of the instance() method.
	 *
	 * @see self::instance()
	 *
	 * @access private
	 */
	private function __construct() {
	}

	/**
	 * Empty wakeup method, forcing the use of the instance() method.
	 *
	 * @see self::instance()
	 *
	 * @access private
	 */
	private function __wakeup() {
	}

	/**
	 * Check if this is a post type that uses Optimizely.
	 *
	 * @param string $post_type The post type to check.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return bool
	 */
	private function is_post_type_enabled( $post_type ) {

		// Convert selected post types to an array.
		$selected_post_types = get_option( 'optimizely_post_types', array() );

		return ( is_array( $selected_post_types )
			&& in_array( $post_type, $selected_post_types, true )
		);
	}

	/**
	 * Registers action and filter hooks.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function setup() {

		// Register action hooks.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'add_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'plugins_loaded', array( $this, 'upgrade_check' ) );
	}

	/**
	 * Upgrades the database version of the plugin to version 1.0.0.
	 *
	 * @access private
	 */
	private function upgrade_to_1_0_0() {

		// Attempt to convert legacy project ID to new format.
		$project_id = absint( get_option( 'optimizely_project_id' ) );
		if ( empty( $project_id ) ) {

			// Determine whether to migrate settings from legacy project code.
			$project_code = get_option( 'optimizely_project_code' );
			if ( ! empty( $project_code )
				&& false !== strpos( $project_code, 'js' )
			) {

				// Extract the project ID from the project code.
				$project_id = substr( $project_code, strpos( $project_code, 'js' ) + 3 );
				$project_id = substr( $project_id, 0, strpos( $project_id, 'js' ) - 1 );
				$project_id = absint( $project_id );

				// Determine whether the extraction was successful.
				if ( ! empty( $project_id ) ) {

					// Remove the old project code and save the ID to the new option.
					delete_option( 'optimizely_project_code' );
					update_option( 'optimizely_project_id', $project_id );
				}
			}
		}

		// Attempt to convert legacy post types value to array.
		$post_types = get_option( 'optimizely_post_types' );
		if ( ! is_array( $post_types ) ) {
			$post_types = array_filter( explode( ',', $post_types ) );
		}

		// If no post types selected, set to default.
		if ( empty( $post_types ) ) {
			$post_types = array( 'post' );
		}

		// Update the option.
		update_option( 'optimizely_post_types', $post_types );
	}
}

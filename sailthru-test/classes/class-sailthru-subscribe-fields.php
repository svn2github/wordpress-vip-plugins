<?php


class Sailthru_Subscribe_Fields {


	protected $admin_views = array();

	// Represents the nonce value used to save the post media
	private $nonce = 'wp_sailthru_nonce';


	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/

	/**
	 * Initializes the plugin by setting localization, filters, and administration functions.
	 */
	function __construct() {

		// Load plugin text domain
		add_action( 'init', array( $this, 'sailthru_init' ) );

		// Register admin styles and scripts
		// Documentation says: admin_print_styles should not be used to enqueue styles or scripts on the admin pages. Use admin_enqueue_scripts instead.
		add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );

		// Register the menu
		add_action( 'admin_menu', array( $this, 'sailthru_menu' ) );

		// Register the Horizon meta tags
		add_action( 'wp_head', array( $this, 'sailthru_horizon_meta_tags' ) );

		// Setup the meta box hooks
		add_action( 'add_meta_boxes', array( $this, 'sailthru_post_metabox' ) );
		add_action( 'save_post', array( $this, 'save_custom_meta_data' ) );

	} // end constructor

	/**
	 * Fired when the plugin is activated.
	 *
	 * @param boolean $network_wide True if WPMU superadmin
	 *          uses "Network Activate" action, false if WPMU is
	 *          disabled or plugin is activated on an individual blog
	 */
	public static function activate( $network_wide ) {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// signal that it's ok to override WordPress's built-in email functions
		if ( false === get_option( 'sailthru_override_wp_mail' ) ) {
			add_option( 'sailthru_override_wp_mail', 1 );
		} // end if

	} // end activate

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param boolean $network_wide True if WPMU superadmin
	 *          uses "Network Activate" action, false if WPMU is
	 *          disabled or plugin is activated on an individual blog
	 */
	public static function deactivate( $network_wide ) {

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// stop overriding WordPress's built in email functions
		if ( false !== get_option( 'sailthru_override_wp_mail' ) ) {
			delete_option( 'sailthru_override_wp_mail' );
		}

		// we don't know if the API keys, etc, will still be
		// good, so kill the flag that said we knew.
		if ( false !== get_option( 'sailthru_setup_complete' ) ) {
			delete_option( 'sailthru_setup_complete' );
		}

		// remove all setup information including API key info
		if ( false !== get_option( 'sailthru_setup_options' ) ) {
			delete_option( 'sailthru_setup_options' );
		}

		// remove concierge settings
		if ( false !== get_option( 'sailthru_concierge_options' ) ) {
			delete_option( 'sailthru_concierge_options' );
		}

		// remove scout options
		if ( false !== get_option( 'sailthru_scout_options' ) ) {
			delete_option( 'sailthru_scout_options' );
		}

		// remove custom fields options
		if ( false !== get_option( 'sailthru_forms_options' ) ) {
			delete_option( 'sailthru_forms_options' );
		}

		// remove integrations options
		if ( false !== get_option( 'sailthru_integrations_options' ) ) {
			delete_option( 'sailthru_integrations_options' );
		}

	} // end deactivate

	/**
	 * Fired when the plugin is uninstalled.
	 *
	 * @param boolean $network_wide True if WPMU superadmin
	 *          uses "Network Activate" action, false if WPMU is
	 *          disabled or plugin is activated on an individual blog
	 */
	public static function uninstall( $network_wide ) {
		// nothing to see here.
	} // end uninstall


	public function sailthru_init() {

		/**
		 * Loads the plugin text domain for translation
		 */
		$domain = 'sailthru-for-wordpress-locale';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		load_textdomain( $domain, SAILTHRU_PLUGIN_PATH . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		// Add a thumbnail size for concierge
		add_theme_support( 'post-thumbnails' );
		add_image_size( 'concierge-thumb', 50, 50 );

	} // end plugin_textdomain


	/*--------------------------------------------*
	 * Core Functions
	 *---------------------------------------------*/
	/**
	 * Add a top-level Sailthru menu and its submenus.
	 */
	function sailthru_menu() {

		$sailthru_menu                       = add_menu_page(
			'Sailthru',                                         // The value used to populate the browser's title bar when the menu page is active
			__( 'Sailthru', 'sailthru-for-wordpress' ),         // The text of the menu in the administrator's sidebar
			'manage_options',                                   // What roles are able to access the menu
			'sailthru_configuration_page',                      // The ID used to bind submenu items to this menu
			array( $this, 'load_sailthru_admin_display' ),      // The callback function used to render this menu
			SAILTHRU_PLUGIN_URL . 'img/sailthru-menu-icon.png'  // The icon to represent the menu item
		);
		$this->admin_views[ $sailthru_menu ] = 'sailthru_configuration_page';

		$redundant_menu                       = add_submenu_page(
			'sailthru_configuration_page',
			__( 'Welcome', 'sailthru-for-wordpress' ),
			__( 'Welcome', 'sailthru-for-wordpress' ),
			'manage_options',
			'sailthru_configuration_page',
			array( $this, 'load_sailthru_admin_display' )
		);
		$this->admin_views[ $redundant_menu ] = 'sailthru_configuration_page';

		$settings_menu                       = add_submenu_page(
			'sailthru_configuration_page',
			__( 'Settings', 'sailthru-for-wordpress' ),
			__( 'Settings', 'sailthru-for-wordpress' ),
			'manage_options',
			'settings_configuration_page',
			array( $this, 'load_sailthru_admin_display' )
		);
		$this->admin_views[ $settings_menu ] = 'settings_configuration_page';

		$concierge_menu                       = add_submenu_page(
			'sailthru_configuration_page',                          // The ID of the top-level menu page to which this submenu item belongs
			__( 'Concierge Options', 'sailthru-for-wordpress' ),    // The value used to populate the browser's title bar when the menu page is active
			__( 'Concierge Options', 'sailthru-for-wordpress' ),    // The label of this submenu item displayed in the menu
			'manage_options',                                       // What roles are able to access this submenu item
			'concierge_configuration_page',                         // The ID used to represent this submenu item
			array( $this, 'load_sailthru_admin_display' )           // The callback function used to render the options for this submenu item
		);
		$this->admin_views[ $concierge_menu ] = 'concierge_configuration_page';

		$scout_menu                       = add_submenu_page(
			'sailthru_configuration_page',
			__( 'Scout Options', 'sailthru-for-wordpress' ),
			__( 'Scout Options', 'sailthru-for-wordpress' ),
			'manage_options',
			'scout_configuration_page',
			array( $this, 'load_sailthru_admin_display' )
		);
		$this->admin_views[ $scout_menu ] = 'scout_configuration_page';

		$scout_menu                       = add_submenu_page(
			'sailthru_configuration_page',
			__( 'Subscribe Widget Fields', 'sailthru-for-wordpress' ),
			__( 'Subscribe Widget Fields', 'sailthru-for-wordpress' ),
			'manage_options',
			'custom_fields_configuration_page',
			array( $this, 'load_sailthru_admin_display' )
		);
		$this->admin_views[ $scout_menu ] = 'customforms_configuration_page';

		$forms_menu                       = add_submenu_page(
			'customforms_configuration_page',
			__( 'Custom Forms', 'sailthru-for-wordpress' ),
			__( 'Custom Forms', 'sailthru-for-wordpress' ),
			'manage_options',
			'customforms_configuration_page',
			array( $this, 'load_sailthru_admin_display' )
		);
		$this->admin_views[ $forms_menu ] = 'customforms_configuration_page';

		$integrations_menu                       = add_submenu_page(
			'sailthru_configuration_page',
			__( 'Integrations', 'sailthru-for-wordpress' ),
			__( 'Integrations', 'sailthru-for-wordpress' ),
			'manage_options',
			'integrations_configuration_page',
			array( $this, 'load_sailthru_admin_display' )
		);
		$this->admin_views[ $integrations_menu ] = 'integrations_configuration_page';

	} // end sailthru_menu

	/**
	 * Renders a simple page to display for the theme menu defined above.
	 */
	function load_sailthru_admin_display() {

		$active_tab = empty( $this->views[ current_filter() ] ) ? '' : $this->views[ current_filter() ];
		// display html
		include SAILTHRU_PLUGIN_PATH . 'views/admin.php';

	} // end sailthru_admin_display

	/**
	 * Renders Horizon specific meta tags in the <head></head>
	 */
	function sailthru_horizon_meta_tags() {

		// only do this on pages and posts
		if ( ! is_single() ) {
			return;
		}

		// filter to disable all output
		if ( false === apply_filters( 'sailthru_horizon_meta_tags_enable', true ) ) {
			return;
		}

		global $post;

		$post_object = get_post();

		$horizon_tags = array();

		// date
		$post_date                     = get_the_date( 'Y-m-d H:i:s' );
		$horizon_tags['sailthru.date'] = esc_attr( $post_date );

		// title
		$post_title                     = get_the_title();
		$horizon_tags['sailthru.title'] = esc_attr( $post_title );

		// tags in the order of priority
		// first sailthru tags
		$post_tags = get_post_meta( $post_object->ID, 'sailthru_meta_tags', true );

		// WordPress tags
		if ( empty( $post_tags ) ) {
			$post_tags = get_the_tags();
			if ( $post_tags ) {
				$post_tags = esc_attr( implode( ', ', wp_list_pluck( $post_tags, 'name' ) ) );
			}
		}

		// WordPress categories
		if ( empty( $post_tags ) ) {
			$post_categories = get_the_category( $post_object->ID );
			foreach ( $post_categories as $post_category ) {
				$post_tags .= $post_category->name . ', ';
			}
			$post_tags = substr( $post_tags, 0, -2 );
		}

		if ( ! empty( $post_tags ) ) {
			$horizon_tags['sailthru.tags'] = $post_tags;
		}

		// author << works on display name. best option?
		$post_author = get_the_author();
		if ( ! empty( $post_author ) ) {
			$horizon_tags['sailthru.author'] = $post_author;
		}

		// description
		$post_description = get_the_excerpt();
		if ( empty( $post_description ) ) {
			$excerpt_length = 250;
			// get the entire post and then strip it down to just sentences.
			$text             = $post_object->post_content;
			$text             = apply_filters( 'the_content', $text );
			$text             = str_replace( ']]>', ']]>', $text );
			$text             = strip_shortcodes( $text );
			$text             = wp_strip_all_tags( $text );
			$text             = substr( $text, 0, $excerpt_length );
			$post_description = $this->reverse_strrchr( $text, '.', 1 );
		}
		$horizon_tags['sailthru.description'] = esc_html( $post_description );

		// image & thumbnail
		if ( has_post_thumbnail( $post_object->ID ) ) {
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );
			$thumb = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'concierge-thumb' );

			$post_image                           = $image[0];
			$horizon_tags['sailthru.image.full']  = esc_attr( $post_image );
			$post_thumbnail                       = $thumb[0];
			$horizon_tags['sailthru.image.thumb'] = $post_thumbnail;
		}

		// expiration date
		$post_expiration = get_post_meta( $post_object->ID, 'sailthru_post_expiration', true );

		if ( ! empty( $post_expiration ) ) {
			$horizon_tags['sailthru.expire_date'] = esc_attr( $post_expiration );
		}

		$horizon_tags = apply_filters( 'sailthru_horizon_meta_tags', $horizon_tags, $post_object );

		$tag_output = "\n\n<!-- BEGIN Sailthru Horizon Meta Information -->\n";
		foreach ( (array) $horizon_tags as $tag_name => $tag_content ) {
			if ( empty( $tag_content ) ) {
				continue; // Don't ever output empty tags
			}
			$meta_tag    = sprintf( '<meta name="%s" content="%s" />', esc_attr( $tag_name ), esc_attr( $tag_content ) );
			$tag_output .= apply_filters( 'sailthru_horizon_meta_tags_output', $meta_tag );
			$tag_output .= "\n";
		}
		$tag_output .= "<!-- END Sailthru Horizon Meta Information -->\n\n";

		echo esc_html( $tag_output );

	} // sailthru_horizon_meta_tags


	/*-------------------------------------------
	 * Utility Functions
	 *------------------------------------------*/

	/*
	 * Returns the portion of haystack which goes until the last occurrence of needle
	 * Credit: http://www.wprecipes.com/wordpress-improved-the_excerpt-function
	 */
	function reverse_strrchr( $haystack, $needle, $trail ) {
		return strrpos( $haystack, $needle ) ? substr( $haystack, 0, strrpos( $haystack, $needle ) + $trail ) : false;
	}


	/*--------------------------------------------*
	 * Hooks
	 *--------------------------------------------*/

	/**
	 * Introduces the meta box for expiring content,
	 * and a meta box for Sailthru tags.
	 */
	public function sailthru_post_metabox() {

		add_meta_box(
			'sailthru-post-data',
			__( 'Sailthru Post Data', 'sailthru' ),
			array( $this, 'post_metabox_display' ),
			'post',
			'side',
			'high'
		);

	} // sailthru_post_metabox

	/**
	 * Adds the input box for the post meta data.
	 *
	 * @param object  $post The post to which this information is going to be saved.
	 */
	public function post_metabox_display( $post ) {

		$sailthru_post_expiration = get_post_meta( $post->ID, 'sailthru_post_expiration', true );
		$sailthru_meta_tags       = get_post_meta( $post->ID, 'sailthru_meta_tags', true );

		wp_nonce_field( plugin_basename( __FILE__ ), $this->nonce );

		// post expiration
		$html  = '<p><strong>Sailthru Post Expiration</strong></p>';
		$html .= '<input id="sailthru_post_expiration" type="text" placeholder="YYYY-MM-DD" name="sailthru_post_expiration" value="' . esc_attr( $sailthru_post_expiration ) . '" size="25" class="datepicker" />';
		$html .= '<p class="description">';
		$html .= 'Flash sales, events and some news stories should not be recommended after a certain date and time. Use this Sailthru-specific meta tag to prevent Horizon from suggesting the content at the given point in time. <a href="http://docs.sailthru.com/documentation/products/horizon-data-collection/horizon-meta-tags" target="_blank">More information can be found here</a>.';
		$html .= '</p><!-- /.description -->';

		// post meta tags
		$html .= '<p>&nbsp;</p>';
		$html .= '<p><strong>Sailthru Meta Tags</strong></p>';
		$html .= '<input id="sailthru_meta_tags" type="text" name="sailthru_meta_tags" value="' . esc_attr( $sailthru_meta_tags ) . '" size="25"  />';
		$html .= '<p class="description">';
		$html .= 'Tags are used to measure user interests and later to send them content customized to their tastes.';
		$html .= '</p><!-- /.description -->';
		$html .= '<p class="howto">Separate tags with commas</p>';

		echo esc_html( $html );

	} // end post_media

	/**
	 * Determines whether or not the current user has the ability to save meta data associated with this post.
	 *
	 * @param int     $post_id The ID of the post being save
	 * @param bool    Whether or not the user has the ability to save this post.
	 */
	public function save_custom_meta_data( $post_id ) {

		// First, make sure the user can save the post
		if ( $this->user_can_save( $post_id, $this->nonce ) ) {

			// Did the user set an expiry date, or are they clearing an old one?
			if ( ! empty( $_POST['sailthru_post_expiration'] ) && isset( $_POST['sailthru_post_expiration'] )
				|| get_post_meta( $post_id, 'sailthru_post_expiration', true ) ) {

				$expiry_time = strtotime( sanitize_text_field( $_POST['sailthru_post_expiration'] ) );
				if ( $expiry_time ) {
					$expiry_date = date( 'Y-m-d', $expiry_time );

					// Save the date. hehe.
					update_post_meta( $post_id, 'sailthru_post_expiration', $expiry_date );
				}
			} // end if

			// Did the user set some meta tags, or are they clearing out old tags?
			if ( ! empty( $_POST['sailthru_meta_tags'] ) && isset( $_POST['sailthru_meta_tags'] )
				|| get_post_meta( $post_id, 'sailthru_meta_tags', true ) ) {

				//remove trailing comma
				$meta_tags = rtrim( sanitize_text_field( $_POST['sailthru_meta_tags'] ), ',' );
				update_post_meta( $post_id, 'sailthru_meta_tags', $meta_tags );

			}
		} // end if

	} // end save_custom_meta_data

	/*--------------------------------------------*
	 * Helper Functions
	 *--------------------------------------------*/

	/**
	 * FROM: https://github.com/tommcfarlin/WordPress-Upload-Meta-Box
	 * Determines whether or not the current user has the ability to save meta data associated with this post.
	 *
	 * @param int     $post_id The ID of the post being save
	 * @param bool    Whether or not the user has the ability to save this post.
	 */
	function user_can_save( $post_id, $nonce ) {

		$is_autosave    = wp_is_post_autosave( $post_id );
		$is_revision    = wp_is_post_revision( $post_id );
		$is_valid_nonce = ( isset( $_POST[ $nonce ] ) && wp_verify_nonce( $_POST[ $nonce ], plugin_basename( __FILE__ ) ) );

		// Return true if the user is able to save; otherwise, false.
		return ! ( $is_autosave || $is_revision ) && $is_valid_nonce;

	} // end user_can_save
}

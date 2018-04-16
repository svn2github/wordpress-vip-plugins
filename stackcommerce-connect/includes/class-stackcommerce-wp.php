<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * The core plugin class that is used to define API endpoints and
 * admin-specific hooks.
 *
 * @since      1.0.0
 * @package    StackCommerce_WP
 * @subpackage StackCommerce_WP/includes
 */
class StackCommerce_WP {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @var      StackCommerce_WP_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * Define the core functionality class that is used to define API endpoints and
	 * admin-specific hooks.
	 *
	 * @since    1.0.0
	*/
	public function __construct() {
		$this->load_dependencies();

		// Add query vars
		$this->loader->add_filter( 'query_vars', $this, 'add_query_vars', 0 );

		// Register CSS and JS assets
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'scripts' );

		$this->register_pages();
		$this->register_endpoint();
		$this->add_sc_js_code();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - StackCommerce_WP_Loader. Orchestrates the hooks of the plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-stackcommerce-wp-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-stackcommerce-wp-module.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-stackcommerce-wp-endpoint.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-stackcommerce-wp-article.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-stackcommerce-wp-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-stackcommerce-wp-maintenance.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-stackcommerce-wp-media.php';

		$this->loader = new StackCommerce_WP_Loader();
	}

	/**
	 * Action to register settings pages
	 *
	 * @since    1.3.0
	 */
	public function register_pages() {
		$stackcommerce_wp_settings = new StackCommerce_WP_Settings();

		$this->loader->add_action( 'admin_init', $stackcommerce_wp_settings, 'register_api' );
		$this->loader->add_action( 'admin_menu', $stackcommerce_wp_settings, 'register_menu' );
	}

	/**
	* Query vars
	*
	* @since    1.3.0
	*/
	public function add_query_vars( $vars ) {
		$vars[] = 'sc-api-version';
		$vars[] = 'sc-api-route';

		return $vars;
	}

	/**
	 * Register hook that allow the plugin to receive articles
	 *
	 * @since    1.3.0
	 */
	public function register_endpoint() {
		$stackcommerce_wp_endpoint = new StackCommerce_WP_Endpoint();

		$this->loader->add_action( 'parse_request', $stackcommerce_wp_endpoint, 'receive' );
	}

	/**
	 * Register stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function styles() {
		wp_register_style( 'stackcommerce_wp_admin_style_select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/3.5.4/select2.min.css', array(), '', 'all' );
		wp_register_style( 'stackcommerce_wp_admin_style', plugin_dir_url( dirname( __FILE__ ) ) . 'dist/styles/stackcommerce-wp.css', array(), '1.3.0', 'all' );

		wp_enqueue_style( 'stackcommerce_wp_admin_style_select2' );
		wp_enqueue_style( 'stackcommerce_wp_admin_style' );
	}

	/**
	* Register JS scripts for the admin area.
	*
	* @since    1.0.0
	*/
	public function scripts() {
		wp_register_script( 'stackcommerce_wp_admin_script_select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/3.5.4/select2.min.js', array(), '', 'all' );
		wp_register_script( 'stackcommerce_wp_admin_script', plugin_dir_url( dirname( __FILE__ ) ) . 'dist/scripts/stackcommerce-wp.min.js', array( 'jquery' ), '1.3.0', 'all' );

		wp_enqueue_script( 'stackcommerce_wp_admin_script_select2' );
		wp_enqueue_script( 'stackcommerce_wp_admin_script' );
	}

	/**
	 * Enqueue admin scripts and styles
	 *
	 * @since    1.0.0
	 */
	public function enqueue_assets() {
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'scripts' );
	}

	/**
	 * Add settings action link
	 *
	 * @since    1.0.4
	 */
	public function add_settings_action_link( $links ) {
		$settings = array( '<a href="' . admin_url( 'admin.php?page=stackcommerce_wp_page_general_settings' ) . '" aria-label="' . SCWP_NAME . ' Settings">Settings</a>' );
		$links    = array_merge( $settings, $links );

		return $links;
	}

	/**
	 * Load StackCommerce JS Code
	 *
	 * @since    1.1.0
	 */
	public function load_sc_js_code() {
		require_once( dirname( dirname( __FILE__ ) ) . '/views/stackcommerce-wp-js.php' );
	}

	/**
	 * Add StackCommerce JS Code before ending body tag
	 *
	 * @since    1.1.0
	*/
	public function add_sc_js_code() {
		$this->loader->add_action( 'wp_head', $this, 'load_sc_js_code', 10000 );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    StackCommerce_WP_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}
}

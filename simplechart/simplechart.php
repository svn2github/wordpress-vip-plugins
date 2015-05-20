<?php
/*
Plugin Name: Simplechart
Plugin URI: https://github.com/alleyinteractive/wordpress-simplechart
Description: Create and render interactive charts in WordPress using Simplechart
Author: Drew Machat, Josh Kadis, Alley Interactive
Version: 0.0.1
Author URI: http://www.alleyinteractive.com/
*/

class Simplechart {

	// both will include trailing slash
	private $_plugin_dir_path = null;
	private $_plugin_dir_url = null;
	private $_admin_notices = array( 'updated' => array(), 'error' => array() );
	private $_plugin_id = 'wordpress-simplechart/simplechart.php';

	// config vars that will eventually come from settings page
	private $_config = array(
		'clear_mexp_default_svcs' => true, // override default Media Explorer services
		'loader_js_url' => null,
		'web_app_iframe_src' => null,
		'web_app_url' => null,
		'loader_js_path' => '/assets/widget/loader.js',
		'version' => '0.0.1',
	);

	// startup
	function __construct(){
		if ( ! $this->_check_dependencies() ){
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'admin_init', array( $this, 'deactivate' ) );
			return;
		}
		$this->_plugin_dir_path = plugin_dir_path( __FILE__ );
		$this->_plugin_dir_url = $this->_set_plugin_dir_url();
		$this->_init_modules();
		add_action( 'init', array( $this, 'action_init' ) );
	}

	/**
	 * test for plugin dependencies and add error messages to admin notices as needed
	 */
	private function _check_dependencies() {
		$deps_found = true;

		// require Media Explorer
		if ( ! class_exists( 'Media_Explorer' ) ){
			$this->_admin_notices['error'][] = __( 'Media Explorer is a required plugin for Simplechart', 'simplechart' );
			$deps_found = false;
		}

		return $deps_found;
	}

	/**
	 * deactivate plugin if it is active
	 */
	public function deactivate() {
		if ( is_plugin_active( $this->_plugin_id ) ) {
			deactivate_plugins( $this->_plugin_id );
		}
	}

	/**
	 * print admin notices, either 'updated' (green) or 'error' (red)
	 */
	public function admin_notices() {
		foreach ( $this->_admin_notices as $class => $notices ) {
			foreach ( $notices as $notice ) {
				printf( '<div class="%s"><p>%s</p></div>', esc_attr( $class ), esc_html( $notice ) );
			}
		}
	}

	/**
	 * get root url and path of plugin, whether loaded from plugins directory or in theme
	 */
	private function _set_plugin_dir_url(){

		// if running as regular plugin (i.e. inside wp-content/plugins/)
		if ( 0 === strpos( $this->_plugin_dir_path, WP_PLUGIN_DIR ) ){
			return plugin_dir_url( __FILE__ );
		}
		// if running as VIP plugin
		elseif ( function_exists( 'wpcom_vip_get_loaded_plugins' ) && in_array( 'plugins/simplechart', wpcom_vip_get_loaded_plugins() ) ) {
			return plugins_url( '', __FILE__ );
		}
		// assume running inside theme
		else {
			$path_relative_to_theme = str_replace( get_template_directory(), '', $this->_plugin_dir_path );
			return get_template_directory_uri() . $path_relative_to_theme;
		}
	}

	/**
	 * config getter
	 */
	public function get_config( $key ){
		return $this->_config[ $key ];
	}

	/**
	 * LOAD MODULES
	 */
	private function _init_modules() {
		// setup simplechart post type
		require_once( $this->_plugin_dir_path . 'modules/class-simplechart-post-type.php' );
		$this->post_type = new Simplechart_Post_Type;

		// setup save post stuff
		require_once( $this->_plugin_dir_path . 'modules/class-simplechart-save.php' );
		$this->save = new Simplechart_Save;

		// load Media Explorer extension and initialize
		require_once( $this->_plugin_dir_path . 'modules/class-simplechart-mexp.php' );
		add_filter( 'mexp_services', 'simplechart_mexp_init' );

		// template rendering module
		require_once( $this->_plugin_dir_path . 'modules/class-simplechart-template.php' );
		$this->template = new Simplechart_Template;

	}

	/**
	 * on the 'init' action, do frontend or backend startup
	 */
	public function action_init(){
		$this->_config['web_app_url'] = $this->_plugin_dir_url . 'app';
		$this->_config['web_app_url'] = apply_filters( 'simplechart_web_app_url', $this->_config['web_app_url'] );

		// get URL of loader.js for front-end chart display
		$this->_config['loader_js_url'] = $this->_config['web_app_url'] . $this->_config['loader_js_path'];
		$this->_config['loader_js_url'] = apply_filters( 'simplechart_loader_js_url', $this->_config['loader_js_url'] );

		// default to root-relative path to simplechart web app
		$this->_config['web_app_iframe_src'] = parse_url( $this->_config['web_app_url'], PHP_URL_PATH ) . '/#/simplechart';
		$this->_config['web_app_iframe_src'] = apply_filters( 'simplechart_web_app_iframe_src', $this->_config['web_app_iframe_src'] );

		if ( is_admin() ){
			$this->_admin_setup();
		}
	}

	/*
	 * setup /wp-admin functionality
	 */
	private function _admin_setup(){
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		load_plugin_textdomain( 'simplechart', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
	}

	public function enqueue_admin_scripts(){
		// check for site specific config file which can be included in theme
		// this is used to preload stuff like color palette for charts, etc
		if ( file_exists( get_template_directory() . '/inc/plugins/simplechart-site-options.js' ) ) {
			wp_register_script( 'simplechart-site-options', get_template_directory_uri() . '/inc/plugins/simplechart-site-options.js' );
			wp_register_script( 'simplechart-post-edit', $this->_plugin_dir_url . 'js/post-edit.js', array( 'jquery', 'underscore', 'simplechart-site-options' ) );
		} else {
			wp_register_script( 'simplechart-post-edit', $this->_plugin_dir_url . 'js/post-edit.js', array( 'jquery', 'underscore' ) );
		}
		wp_register_style( 'simplechart-style', $this->_plugin_dir_url . 'css/style.css' );
		wp_enqueue_script( 'simplechart-post-edit' );
		wp_enqueue_style( 'simplechart-style' );
	}

	public function add_meta_box(){
		global $post;
		$json_data = $data = get_post_meta( $post->ID, 'simplechart-data', true );
		add_meta_box( 'simplechart-preview',
			__( 'Simplechart', 'simplechart' ),
			array( $this->post_type, 'render_meta_box' ),
			'simplechart',
			'normal',
			'default',
			array( $this->_plugin_dir_path, $json_data )
		);
	}

	// used by modules that need this info
	public function get_plugin_url(){
		return $this->_plugin_dir_url;
	}

	// used by modules that need this info
	public function get_plugin_dir(){
		return $this->_plugin_dir_path;
	}

}
global $simplechart;
$simplechart = new Simplechart;


/**
 * Helper Functions
 */
function simplechart_render_chart( $id ){
	global $simplechart;
	return $simplechart->template->render( $id );
}

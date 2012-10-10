<?php
/*
Plugin Name: PMC Post Savior
Plugin URI: https://github.com/Penske-Media-Corp/pmc-post-savior
Description: PMC Post Savior checks every 15 seconds to see if you're still logged in.  If you are not, it presents a pop-up window so that you can log back in without losing your work.
Version: 1.0
Author: PMC
Author URI: http://www.pmc.com/
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/*
@todo "You will be logged out in X minutes" warning; "Click here to stay logged in" to re-auth. Like a bank.
@todo Maybe save unauthenticated post data, and some way to restore that data once you log back in.
*/

class PMC_Post_Savior {
	/**
	 * String to use for the plugin name.  Used for generating class names, etc.
	 */
	const plugin_id = 'pmc-post-savior';

	/**
	 * String to use for the plugin version.  Primarily used for wp_enqueue_*()
	 */
	const plugin_version = '1.0';

	/**
	 * Holds the singleton instance of this object
	 */
	private static $_instance = null;

	/**
	 * Private constructor because we're a singleton
	 */
	private function __construct() {}

	/**
	 * Initialize the singleton
	 */
	public static function get_instance() {
		$this_class = __CLASS__;
		if ( ! ( self::$_instance instanceof $this_class ) ) {
			self::$_instance = new $this_class;
			self::$_instance->_init();
		}

		return self::$_instance;
	}

	/**
	 * Object init, sets up hooks.
	 */
	protected function _init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );

		add_action( 'login_enqueue_scripts', array( $this, 'login_enqueue_scripts' ) );

		add_action( 'login_enqueue_scripts', array( $this, 'interim_login_enqueue' ) );

		add_action( 'wp_ajax_pmc_post_savior_check', array( $this, 'ajax_check' ) );

		add_action( 'wp_ajax_nopriv_pmc_post_savior_check', array( $this, 'ajax_check' ) );

		add_action( 'admin_footer', array( $this, 'admin_footer' ) );
	}

	/**
	 * Enqueue styles, scripts and script data on the "Edit Post" page
	 */
	public function admin_enqueue( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		wp_enqueue_style( 'pmc-post-savior', plugins_url( 'css/pmc-post-savior.css', __FILE__ ), array(), self::plugin_version );

		wp_enqueue_script( 'pmc-post-savior', plugins_url( 'js/pmc-post-savior.js', __FILE__ ), array( 'jquery' ), self::plugin_version, true );


		$login_url = add_query_arg( array(
			'pmc_post_savior_login' => 'true',
			'TB_iframe' => 'true',
			'width' => 600,
			'height' => 550,
		), wp_login_url( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) );

		wp_localize_script( 'pmc-post-savior', 'pmc_post_savior_opts', array(
			'nonce' => wp_create_nonce( 'pmc-post-savior-ajax-check' ),
			'login_url' => $login_url,
			'interim_login' => 'false',
		) );

		wp_localize_script( 'pmc-post-savior', 'pmc_post_savior_text', array(
			'not_logged_in'    => __( 'Oops! Looks like you are not logged in.', 'pmc-post-savior' ),
			'log_in'         => __( 'Please log in again. <br />You will not leave this screen.', 'pmc-post-savior' ),
			'maybe_offline'  => __( 'Looks like you are not connected to the server.', 'pmc-post-savior' ),
		) );
	}

	/**
	 * Set up wp-login.php's "interim login" functionality, and
	 * enqueue CSS overrides
	 */
	public function login_enqueue_scripts() {
		if ( ! isset($_GET['pmc_post_savior_login']) || 'true' !== $_GET['pmc_post_savior_login'] ) {
			return;
		}

		wp_enqueue_style( 'pmc-post-savior', plugins_url( 'css/pmc-post-savior.css', __FILE__ ), array(), self::plugin_version );

		global $interim_login;
		$interim_login = true;
	}

	/**
	 * Enqueue javascript for the interim login screen
	 */
	public function interim_login_enqueue() {
		global $interim_login;

		if ( true !== $interim_login ) {
			return;
		}

		wp_enqueue_script( 'pmc-post-savior', plugins_url( 'js/pmc-post-savior.js', __FILE__ ), array( 'jquery' ), self::plugin_version, true );

		wp_localize_script( 'pmc-post-savior', 'pmc_post_savior_opts', array(
			'interim_login' => 'true',
		) );
	}

	/**
	 * Adds a div for displaying the login notice
	 */
	public function admin_footer() {
		echo '<div id="pmc-post-savior-notice"></div>';
	}

	/**
	 * AJAX action for testing
	 */
	public function ajax_check() {
		check_ajax_referer( 'pmc-post-savior-ajax-check', 'nonce' );

		// There are 2 known instances of being logged out:
		// 1) User's login has expired
		// 2) User's /wp-admin/ auth cookie is missing
		// Checking for those two scenarios in case we want to take different
		// action for them in the future.
		if ( ! isset( $_COOKIE[LOGGED_IN_COOKIE] ) ) {
			echo 'not_logged_in';
		} elseif ( ! wp_validate_auth_cookie() ) {
			echo 'no_admin_auth';
		} else {
			echo 'logged_in'; // This is what the AJAX poll looks for
		}

		die();
	}

}

PMC_Post_Savior::get_instance();

// EOF
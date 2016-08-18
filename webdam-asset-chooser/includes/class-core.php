<?php

namespace Webdam;

/**
 * Plugin Core
 */
class Core {

	/**
	 * @var Used to store an internal reference for the class
	 */
	private static $_instance;

	/**
	 * Fetch THE singleton instance of this class
	 *
	 * @param null
	 *
	 * @return Asset_Chooser object instance
	 */
	static function get_instance() {

		if ( empty( static::$_instance ) ) {

			self::$_instance = new self();
		}

		// Return the single/cached instance of the class
		return self::$_instance;
	}

	/**
	 * Core constructor.
	 */
	public function __construct() {
		add_action( 'activate_webdam/webdam_asset_chooser.php', array( $this, 'activate' ), 10, 0 );
		add_action( 'deactivate_webdam/webdam_asset_chooser.php', array( $this, 'deactivate' ), 10, 0 );
		add_action( 'plugins_loaded', array( $this, 'action_plugins_loaded' ), 10, 0 );
	}

	/**
	 * Plugin activation routine
	 */
	public function activate() {

		// Do something?
	}

	/**
	 * Plugin deactivation routine
	 */
	public function deactivate() {

		// Do something?

	}

	/**
	 * Run code on the wp core plugins_loaded action
	 */
	public function action_plugins_loaded() {

		// Load the plugin's textdomain for translations
		load_plugin_textdomain( 'webdam', false, WEBDAM_PLUGIN_DIR );
	}
}

Core::get_instance();

// EOF
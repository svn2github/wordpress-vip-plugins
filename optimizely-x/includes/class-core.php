<?php
/**
 * Optimizely X: Core class
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

namespace Optimizely_X;

/**
 * The core plugin class. Keeps track of the plugin version and slug, as well as
 * registering action and filter hooks used by the plugin.
 *
 * @since 1.0.0
 */
class Core {

	/**
	 * The current version of the plugin.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var Core
	 */
	private static $instance;

	/**
	 * Gets the singleton instance.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return Core
	 */
	public static function instance() {

		// Initialize the instance, if necessary.
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Core;
			self::$instance->setup();
		}

		return self::$instance;
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
	 * Initialize the objects that control the plugin's functionality.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function setup() {
		Admin::instance();
		AJAX::instance();
		Frontend::instance();
		I18N::instance();
	}
}

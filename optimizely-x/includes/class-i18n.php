<?php
/**
 * Optimizely X: I18N class
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

namespace Optimizely_X;

/**
 * Defines the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin so that it is
 * ready for translation.
 *
 * @since 1.0.0
 */
class I18N {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var I18N
	 */
	private static $instance;

	/**
	 * Gets the singleton instance.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return I18N
	 */
	public static function instance() {

		// Initialize the instance, if necessary.
		if ( ! isset( self::$instance ) ) {
			self::$instance = new I18N;
			self::$instance->setup();
		}

		return self::$instance;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'optimizely-x',
			false,
			OPTIMIZELY_X_BASE_DIR . '/languages'
		);
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
	 * Registers action and filter hooks.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function setup() {

		// Register action hooks.
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
	}
}

<?php
/**
 * Optimizely X: Frontend class
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

namespace Optimizely_X;

/**
 * The public-facing (non-wp-admin) functionality of the plugin.
 *
 * @since 1.0.0
 */
class Frontend {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var Frontend
	 */
	private static $instance;

	/**
	 * Gets the singleton instance.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return Frontend
	 */
	public static function instance() {

		// Initialize the instance, if necessary.
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Frontend;
			self::$instance->setup();
		}

		return self::$instance;
	}

	/**
	 * Injects the Optimizely script into the <head> of the theme.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function inject_script() {
		Partials::load( 'public', 'head-js' );
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
		add_action( 'wp_head', array( $this, 'inject_script' ), - 1000 );
	}
}

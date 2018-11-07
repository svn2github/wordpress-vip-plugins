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

	use Singleton;

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

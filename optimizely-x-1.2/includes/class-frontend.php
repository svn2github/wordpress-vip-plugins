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

	use Singleton;

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

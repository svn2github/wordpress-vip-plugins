<?php
/**
 * Optimizely X: Filters class
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

namespace Optimizely_X;

/**
 * Centrally defines filtered values for use throughout the plugin.
 *
 * @since 1.0.0
 */
class Filters {

	/**
	 * Returns the capability name required for administering this plugin.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string The capability name required for administering this plugin.
	 */
	public static function admin_capability() {

		/**
		 * The capability required for administering this plugin.
		 *
		 * @since 1.0.0
		 *
		 * @param string $capability The capability name to be filtered.
		 */
		return apply_filters( 'optimizely_admin_capability', 'manage_options' );
	}
}

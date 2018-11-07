<?php
/**
 * Optimizely X: Partials class
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

namespace Optimizely_X;

/**
 * A helper class for loading partials.
 *
 * @since 1.0.0
 */
class Partials {

	/**
	 * A helper function for loading partials.
	 *
	 * @param string $scope Where to load the partial from (admin or public).
	 * @param string $slug The partial filepath to the partial template.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public static function load( $scope, $slug ) {

		// Ensure requested partial exists.
		$filepath = OPTIMIZELY_X_BASE_DIR . '/' . $scope . '/partials/' . $slug . '.php';
		if ( ! file_exists( $filepath ) ) {
			return;
		}

		require $filepath;
	}
}

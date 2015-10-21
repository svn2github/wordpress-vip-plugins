<?php

/**
 * Autoload the class from tinypass plugin directory
 *
 * @param $className
 */
function wp_tinypass_autoloader( $className ) {
	if ( ( preg_match( '/^WPTinypass/', $className ) || preg_match( '/^Tinypass/', $className ) ) && file_exists( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'include/' . $className . '.php' ) ) {
		require_once( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'include/' . sanitize_file_name( $className ) . '.php' );
	} elseif ( preg_match( '/^TP/', $className ) && file_exists( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'include/token/' . $className . '.php' ) ) {
		require_once( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'include/token/' . sanitize_file_name( $className ) . '.php' );
	} elseif ( preg_match( '/^TP/', $className ) && file_exists( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'include/util/' . $className . '.php' ) ) {
		require_once( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'include/util/' . sanitize_file_name( $className ) . '.php' );
	}
}

// Register the autoloader
spl_autoload_register( 'wp_tinypass_autoloader' );
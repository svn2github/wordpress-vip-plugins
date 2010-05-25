<?php

/*
Plugin Name: VIP Local Development Helper
Description: Helps you test your <a href="http://vip.wordpress.com/hosting/">WordPress.com VIP</a> theme in your local development environment by replicating some VIP-specific functionality.
Plugin URI:  http://viphostingtech.wordpress.com/getting-started/development-environment/
Version:     31276
Author:      Automattic
Author URI:  http://vip.wordpress.com/

For help with this plugin, please see http://wp.me/PPtWC-2T or contact VIP support at vip-support@wordpress.com

Functions in this plugin are not neccessarily identical code wise to those on WordPress.com,
but the idea is that they function the same as their WordPress.com counterparts.
*/


/*
 * Loads a plugin out of our shared plugins directory.
 *
 * See the following URL for details:
 * @link http://viphostingtech.wordpress.com/plugins/
 *
 * @param string $plugin Plugin folder name (and filename) of the plugin
 * @param string $folder Optional. Folder to include from. Useful for when you have multiple themes and your own shared plugins folder.
 * @return boolean True if the include was successful, false if it failed.
*/
function vip_load_plugin( $plugin, $folder = 'plugins' ) {

	// Sanitize $plugin and $folder
	$plugin = _vip_load_plugin_sanitizer( $plugin );
	if ( 'plugins' !== $folder )
		$folder = _vip_load_plugin_sanitizer( $folder );

	// This is the location that matches our environment
	// i.e. /wp-content/themes/vip/plugins/example-plugin/example-plugin.php
	$fullpath = WP_CONTENT_DIR . "/themes/vip/$folder/$plugin/$plugin.php";
	if ( file_exists( $fullpath ) ) {
		include_once( $fullpath );
		return true;
	}

	// However for your development environment, you can also opt to have it in a more standard location
	// i.e. /wp-content/plugins/example-plugin/example-plugin.php
	// This is not a part of the WordPress.com version of the function
	$fullpath = WP_CONTENT_DIR . "/plugins/$plugin/$plugin.php";
	if ( file_exists( $fullpath ) ) {
		include_once( $fullpath );
		return true;
	}

	// This would normally return false, but we'll die() since we're in a development environment
	die( "Unable to load $plugin using vip_load_plugin()!" );
}

/*
 * Helper function for vip_load_plugin()
 * You shouldn't use this function.
 */
function _vip_load_plugin_sanitizer( $folder ) {
	$folder = preg_replace( '#([^a-zA-Z0-9-_.]+)#', '', $folder );

	return $folder;
}

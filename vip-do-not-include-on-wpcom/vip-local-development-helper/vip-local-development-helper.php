<?php

/*
Plugin Name: VIP Local Development Helper
Description: Helps you test your <a href="http://vip.wordpress.com/hosting/">WordPress.com VIP</a> theme in your local development environment by defining some functions that are always loaded on WordPress.com
Plugin URI:  http://viphostingtech.wordpress.com/getting-started/development-environment/
Author:      Automattic
Author URI:  http://vip.wordpress.com/

For help with this plugin, please see http://wp.me/PPtWC-2T or contact VIP support at vip-support@wordpress.com

This plugin is enabled automatically on WordPress.com for VIPs.
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
function wpcom_vip_load_plugin( $plugin = false, $folder = 'plugins' ) {

	if ( empty($plugin) ) {
		// On WordPress.com, message Alex M. about the bad call to this function
		if ( function_exists('xmpp_message') ) {
			xmpp_message( 'viper007bond@im.wordpress.com', 'wpcom_vip_load_plugin() was called without a $plugin parameter on ' . get_bloginfo('url') );
			return false;
		}
		// die() in non-WordPress.com environments so you know you made a mistake
		else {
			die( 'wpcom_vip_load_plugin() was called without a first parameter!' );
		}
	}

	// Make sure $plugin is valid
	$plugin = _wpcom_vip_load_plugin_sanitizer( $plugin );
	if ( 'plugins' !== $folder )
		$folder = _wpcom_vip_load_plugin_sanitizer( $folder );

	// On WordPress.com, shared plugins are located at /wp-content/themes/vip/plugins/example-plugin/example-plugin.php
	$includepath = WP_CONTENT_DIR . "/themes/vip/$folder/$plugin/$plugin.php";
	if ( file_exists( $includepath ) ) {
		include_once( $includepath );
		return true;
	} elseif ( function_exists('xmpp_message') ) {
		xmpp_message( 'viper007bond@im.wordpress.com', "wpcom_vip_load_plugin() tried to load a non-existent file ( $fullpath ) on " . get_bloginfo('url') );
		return false;
	}

	// However if you wish, you can store your plugins in the normal location in your development environment
	// i.e. /wp-content/plugins/example-plugin/example-plugin.php
	if ( ! function_exists('wpcom_is_vip') ) {
		$fullpath = WP_CONTENT_DIR . "/plugins/$plugin/$plugin.php";
		if ( file_exists( $fullpath ) ) {
			include_once( $fullpath );
			return true;
		} else {
			die( "Unable to load $plugin using wpcom_vip_load_plugin()!" );
		}
	}

	// The function should never get to this point
	return false;
}

/*
 * Helper function for wpcom_vip_load_plugin()
 * You shouldn't use this function.
 */
function _wpcom_vip_load_plugin_sanitizer( $folder ) {
	$folder = preg_replace( '#([^a-zA-Z0-9-_.]+)#', '', $folder );
	$folder = str_replace( '..', '', $folder ); // To prevent going up directories

	return $folder;
}

?>
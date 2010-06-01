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

	// Make sure there's a plugin to load
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

	// Make sure $plugin and $folder are valid
	$plugin = _wpcom_vip_load_plugin_sanitizer( $plugin );
	if ( 'plugins' !== $folder )
		$folder = _wpcom_vip_load_plugin_sanitizer( $folder );

	// Shared plugins are located at /wp-content/themes/vip/plugins/example-plugin/
	// You should keep your local copies of the plugins in the same location
	$includepath = WP_CONTENT_DIR . "/themes/vip/$folder/$plugin/$plugin.php";
	if ( file_exists( $includepath ) ) {

		// Since we're going to be include()'ing inside of a function,
		// we need to do some hackery to get the variable scope we want.
		// See http://www.php.net/manual/en/language.variables.scope.php#91982

		// Start by marking down the currently defined variables (so we can exclude them later)
		$pre_include_variables = get_defined_vars();

		// Now include
		include_once( $includepath );

		// Blacklist out some variables
		$blacklist = array( 'blacklist' => 0, 'pre_include_variables' => 0, 'new_variables' => 0 );

		// Let's find out what's new by comparing the current variables to the previous ones
		$new_variables = array_diff_key( get_defined_vars(), $GLOBALS, $blacklist, $pre_include_variables );

		// global each new variable
		foreach ( $new_variables as $new_variable => $devnull )
			global $$new_variable;

		// Set the values again on those new globals
		extract( $new_variables );

		return true;
	} else {
		// On WordPress.com, message Alex M. about the bad call to this function
		if ( function_exists('xmpp_message') ) {
			xmpp_message( 'viper007bond@im.wordpress.com', "wpcom_vip_load_plugin() tried to load a non-existent file ( $fullpath ) on " . get_bloginfo('url') );
			return false;
		}
		// die() in non-WordPress.com environments so you know you made a mistake
		else {
			die( "Unable to load $plugin using wpcom_vip_load_plugin()!" );
		}
	}
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
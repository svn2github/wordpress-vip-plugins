<?php

/*
Plugin Name: VIP Local Development Helper
Description: Helps you test your <a href="http://vip.wordpress.com/hosting/">WordPress.com VIP</a> theme in your local development environment by defining some functions that are always loaded on WordPress.com
Plugin URI:  http://lobby.vip.wordpress.com/getting-started/development-environment/
Author:      Automattic
Author URI:  http://vip.wordpress.com/

For help with this plugin, please see http://wp.me/PPtWC-2T or contact VIP support at vip-support@wordpress.com

This plugin is enabled automatically on WordPress.com for VIPs.
*/


/*
 * Loads a plugin out of our shared plugins directory.
 *
 * See the following URL for details:
 * @link http://lobby.vip.wordpress.com/plugins/
 *
 * @param string $plugin Plugin folder name (and filename) of the plugin
 * @param string $folder Optional. Folder to include from. Useful for when you have multiple themes and your own shared plugins folder.
 * @return boolean True if the include was successful, false if it failed.
*/
function wpcom_vip_load_plugin( $plugin = false, $folder = 'plugins' ) {

	// Make sure there's a plugin to load
	if ( empty($plugin) ) {
		// On WordPress.com, use an internal function to message VIP about a bad call to this function
		if ( function_exists( 'wpcom_is_vip' ) ) {
			if ( function_exists( 'send_vip_team_debug_message' ) ) {
				// Use an expiring cache value to avoid spamming messages
				if ( ! wp_cache_get( 'noplugin', 'wpcom_vip_load_plugin' ) ) {
					send_vip_team_debug_message( 'WARNING: wpcom_vip_load_plugin() is being called without a $plugin parameter' );
					wp_cache_set( 'noplugin', 1, 'wpcom_vip_load_plugin', 3600 );
				}
			}
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

		wpcom_vip_add_loaded_plugin( "$folder/$plugin" );

		// Since we're going to be include()'ing inside of a function,
		// we need to do some hackery to get the variable scope we want.
		// See http://www.php.net/manual/en/language.variables.scope.php#91982

		// Start by marking down the currently defined variables (so we can exclude them later)
		$pre_include_variables = get_defined_vars();

		// Now include
		include_once( $includepath );

		// If there's a wpcom-helper file for the plugin, load that too
		$helper_path = WP_CONTENT_DIR . "/themes/vip/$folder/$plugin/wpcom-helper.php";
		if ( file_exists( $helper_path ) )
			require_once( $helper_path );

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
		// On WordPress.com, use an internal function to message VIP about the bad call to this function
		if ( function_exists( 'wpcom_is_vip' ) ) {
			if ( function_exists( 'send_vip_team_debug_message' ) ) {
				// Use an expiring cache value to avoid spamming messages
				$cachekey = md5( $folder . '|' . $plugin );
				if ( ! wp_cache_get( "notfound_$cachekey", 'wpcom_vip_load_plugin' ) ) {
					send_vip_team_debug_message( "WARNING: wpcom_vip_load_plugin() is trying to load a non-existent file ( /$folder/$plugin/$plugin.php )" );
					wp_cache_set( "notfound_$cachekey", 1, 'wpcom_vip_load_plugin', 3600 );
				}
			}
			return false;
		}
		// die() in non-WordPress.com environments so you know you made a mistake
		else {
			die( "Unable to load $plugin ({$helper_path}) using wpcom_vip_load_plugin()!" );
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


/*
 * Loads the shared VIP helper file which defines some helpful functions.
 *
*/
function wpcom_vip_load_helper() {
	$includepath = WP_CONTENT_DIR . '/themes/vip/plugins/vip-helper.php';

	if ( file_exists( $includepath ) ) {
		require_once( $includepath );
	} else {
		die( "Unable to load vip-helper.php using wpcom_vip_load_helper(). The file doesn't exist!" );
	}
}


/*
 * Loads the WordPress.com-only VIP helper file which defines some helpful functions.
 *
*/
function wpcom_vip_load_helper_wpcom() {
	$includepath = WP_CONTENT_DIR . '/themes/vip/plugins/vip-helper-wpcom.php';

	if ( function_exists( 'wpcom_is_vip' ) ) {
		require_once( $includepath );
	} else {
		die( "wpcom_vip_load_helper_wpcom() should only be called on WordPress.com as it contains WordPress.com-specific code." );
	}
}

/*
 * Loads the WordPress.com-only VIP helper file for stats which defines some helpful stats-related functions.
 *
*/
function wpcom_vip_load_helper_stats() {
        $includepath = WP_CONTENT_DIR . '/themes/vip/plugins/vip-helper-stats-wpcom.php';

        if ( function_exists( 'wpcom_is_vip' ) ) {
                require_once( $includepath );
        } else {
                die( "wpcom_vip_load_helper_stats() should only be called on WordPress.com as it contains WordPress.com-specific code." );
        }
}

function wpcom_vip_add_loaded_plugin( $plugin ) {
	global $vip_loaded_plugins;
	
	if( ! isset( $vip_loaded_plugins ) )
		$vip_loaded_plugins = array();
	
	array_push( $vip_loaded_plugins, $plugin );
}

function wpcom_vip_get_loaded_plugins() {
	global $vip_loaded_plugins;
	
	if( ! isset( $vip_loaded_plugins ) )
		$vip_loaded_plugins = array();
	
	return $vip_loaded_plugins;
	
}

/**
 * Filter plugins_url() so that it works for plugins inside the shared VIP plugins directory or a theme directory.
 * Props to the GigaOm dev team for coming up with this method.
 */
function wpcom_vip_plugins_url( $url = '', $path = '', $plugin = '' ) {

	// Be gentle on Windows, borrowed from core, see plugin_basename
	$content_dir = str_replace( '\\','/', WP_CONTENT_DIR ); // sanitize for Win32 installs
	$content_dir = preg_replace( '|/+|','/', $content_dir ); // remove any duplicate slash

	$vip_dir = $content_dir . '/themes/vip';
	$vip_url = content_url( '/themes/vip' );

	if( 0 === strpos( $plugin, $vip_dir ) )
		$url_override = str_replace( $vip_dir, $vip_url, dirname( $plugin ) );
	elseif  ( 0 === strpos( $plugin, get_stylesheet_directory() ) )
		$url_override = str_replace(get_stylesheet_directory(), get_stylesheet_directory_uri(), dirname( $plugin ) );

	if ( isset( $url_override ) )
		$url = trailingslashit( $url_override ) . $path;

	return $url;
}

add_filter( 'plugins_url', 'wpcom_vip_plugins_url', 10, 3 );

/**
 * Return a URL for given VIP theme and path. Does not work with VIP shared plugins.
 * 
 * @param $path string Path to suffix to the theme URL
 * @param $theme string Name of the theme folder
 *
 * @return string URL for the specified theme and path
 */
function wpcom_vip_theme_url( $path = '', $theme = '' ) {
	if ( empty( $theme ) )
		$theme = str_replace( 'vip/', '', get_stylesheet() );

	// We need to reference a file in the specified theme; style.css will almost always be there.
	$theme_folder = sprintf( '%s/themes/vip/%s', WP_CONTENT_DIR, $theme );
	$theme_file = $theme_folder . '/style.css';

	// For local environments where the theme isn't under /themes/vip/themename/
	$theme_folder_alt = sprintf( '%s/themes/%s', WP_CONTENT_DIR, $theme );
	$theme_file_alt = $theme_folder_alt . '/style.css';

	$path = ltrim( $path, '/' );

	// We pass in a dummy file to plugins_url even if it doesn't exist, otherwise we get a URL relative to the parent of the theme folder (i.e. /themes/vip/)
	if ( is_dir( $theme_folder ) )
		return plugins_url( $path, $theme_file );
	elseif( is_dir( $theme_folder_alt ) )
		return plugins_url( $path, $theme_file_alt );

	return false;
}

/**
 * Return the directory path for a given VIP theme
 *
 * @param $theme string Name of the theme folder
 *
 * @return string path for the specified theme
 */
function wpcom_vip_theme_dir( $theme = '' ) {
	if ( empty( $theme ) )
		$theme = get_stylesheet();

	// Simple sanity check, in case we get passed a lame path
	$theme = ltrim( $theme, '/' );
	$theme = str_replace( 'vip/', '', $theme );

	return trailingslashit( sprintf( '%s/themes/vip/%s', WP_CONTENT_DIR, $theme ) );
}

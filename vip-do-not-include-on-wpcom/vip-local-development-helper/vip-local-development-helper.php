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
		// On WordPress.com, use an internal function to message VIP about the bad call to this function
		if ( function_exists( 'wpcom_is_vip' ) ) {
			if ( function_exists( 'send_vip_team_debug_message' ) ) {
				// Use an expiring cache value to avoid spamming messages
				if ( ! wp_cache_get( 'noplugin', 'wpcom_vip_load_plugin' ) ) {
					send_vip_team_debug_message( 'WARNING: wpcom_vip_load_plugin() is being called without a $plugin parameter on ' . get_bloginfo('url') );
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
					send_vip_team_debug_message( "WARNING: wpcom_vip_load_plugin() is trying to load a non-existent file ( /$folder/$plugin/$plugin.php ) on " . get_bloginfo('url') );
					wp_cache_set( "notfound_$cachekey", 1, 'wpcom_vip_load_plugin', 3600 );
				}
			}
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

/** Wrappers for user attribute functions which are available on WP.com **/

if ( !function_exists('update_user_attribute') ) :
	/**
	 * Update a user's attribute
	 *
	 * There is no need to serialize values, they will be serialized if it is
	 * needed. The metadata key can only be a string with underscores. All else will
	 * be removed.
	 *
	 * Will remove the attribute, if the meta value is empty.
	 *
	 * @param int $user_id User ID
	 * @param string $meta_key Metadata key.
	 * @param mixed $meta_value Metadata value.
	 * @return bool True on successful update, false on failure.
	 */
	function update_user_attribute( $user_id, $meta_key, $meta_value ) 
	{
		if ( update_user_meta( $user_id, $meta_key, $meta_value ) )
			return true;
		else
			return false;
	}
endif;

if ( !function_exists('get_user_attribute') ) :
	/**
	 * Retrieve user attribute data.
	 *
	 * If $user_id is not a number, then the function will fail over with a 'false'
	 * boolean return value. Other returned values depend on whether there is only
	 * one item to be returned, which be that single item type. If there is more
	 * than one metadata value, then it will be list of metadata values.
	 *
	 * @param int $user_id User ID
	 * @param string $meta_key Optional. Metadata key.
	 * @return mixed
	 */
	function get_user_attribute( $user_id, $meta_key ) 
	{
		if ( !$usermeta = get_user_meta( $user_id, $meta_key ) )
			return false;
			
		if ( count($usermeta) == 1 )
			return reset($usermeta);
			
		return $usermeta;
	}
endif;

if ( !function_exists('delete_user_attribute') ) :
	/**
	 * Remove user attribute data.
	 *
	 * @uses $wpdb WordPress database object for queries.
	 *
	 * @param int $user_id User ID.
	 * @param string $meta_key Metadata key.
	 * @param mixed $meta_value Metadata value.
	 * @return bool True deletion completed and false if user_id is not a number.
	 */
	function delete_user_attribute( $user_id, $meta_key, $meta_value = '' )
	{
		return delete_user_meta( $user_id, $meta_key, $meta_value );	
	}
endif;

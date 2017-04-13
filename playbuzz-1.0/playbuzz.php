<?php
/*
Plugin Name: Playbuzz
Plugin URI:  https://www.playbuzz.com/
Description: Embed customized playful content from Playbuzz.com into your WordPress site
Version:     1.0.3
Author:      Playbuzz
Author URI:  https://www.playbuzz.com/
Text Domain: playbuzz
Domain Path: /lang
*/



/*
 * Exit if file accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



/*
 * Include plugin files
 */
include_once( plugin_dir_path( __FILE__ ) . 'class-pbconstants.php' );      // Add Constants
include_once( plugin_dir_path( __FILE__ ) . 'class-playbuzzembedcodes.php' );    // Create embed code.
include_once( plugin_dir_path( __FILE__ ) . 'class-playbuzzoptions.php' );      // Add Activation hook
include_once( plugin_dir_path( __FILE__ ) . 'class-playbuzzi18n.php' );           // Add Internationalization support
include_once( plugin_dir_path( __FILE__ ) . 'class-playbuzzsettings.php' );       // Add Setting Page
include_once( plugin_dir_path( __FILE__ ) . 'class-playbuzzstorycreator.php' );  // Add Story post type
include_once( plugin_dir_path( __FILE__ ) . 'class-playbuzzscriptsstyles.php' ); // Load Scripts and Styles
include_once( plugin_dir_path( __FILE__ ) . 'oembed.php' );         // Add oEmbed support
include_once( plugin_dir_path( __FILE__ ) . 'shortcodes.php' );     // Add WordPress Shortcodes
include_once( plugin_dir_path( __FILE__ ) . 'class-playbuzztinymce.php' );        // Add TinyMCE plugin

/*
 * Add settings link on plugin page
 */
function playbuzz_settings_link( $links ) {
	$links[] = '<a href="options-general.php?page=playbuzz">' . __( 'Settings' ) . '</a>';
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'playbuzz_settings_link' );



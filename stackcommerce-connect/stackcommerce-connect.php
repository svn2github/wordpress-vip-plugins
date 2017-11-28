<?php

/**
 * Plugin Name: StackCommerce Connect
 * Plugin URI: https://wordpress.org/plugins/stackcommerce-connect/
 * Description: The Connect plugin by StackCommerce connects your WordPress CMS to the StackCommerce Articles repository.
 * Version: 1.6.4
 * Author: StackCommerce, Inc
 * Author URI: https://www.stackcommerce.com
 */


if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

define( 'SCWP_NAME',                 'StackCommerce Connect' );
define( 'SCWP_REQUIRED_PHP_VERSION', '5.3.5' );
define( 'SCWP_REQUIRED_WP_VERSION',  '4.4' );
define( 'SCWP_API_VERSION', '1' );
define( 'SCWP_PLUGIN_VERSION', '1.6.4' );
define( 'SCWP_CMS_API_ENDPOINT',  'https://hive.stackcommerce.net' );

/**
 * Checks if the system requirements are met
 *
 * @return bool True if system requirements are met, false if not
 *
 * @since    1.0.0
 */
function scwp_requirements_met() {
	global $wp_version;

	if ( version_compare( PHP_VERSION, SCWP_REQUIRED_PHP_VERSION, '<' ) ) {
		return false;
	}

	if ( version_compare( $wp_version, SCWP_REQUIRED_WP_VERSION, '<' ) ) {
		return false;
	}

	return true;
}

/**
 * Prints an error that the system requirements weren't met.
 *
 * @since    1.0.0
 */
function scwp_requirements_error() {
	global $wp_version;

	require_once( dirname( __FILE__ ) . '/views/stackcommerce-wp-requirements-error.php' );
}

/**
 * Check requirements and load main class
 *
 * @since    1.0.0
 */
if ( scwp_requirements_met() ) {

	/**
	* The core plugin class that is used to define API endpoints and
	* admin-specific hooks.
	*/
	require plugin_dir_path( __FILE__ ) . 'includes/class-stackcommerce-wp.php';

	/**
	* Begins execution of the plugin
	*
	* @since    1.0.0
	*/
	function run_stackcommerce_wp() {
		$plugin = new StackCommerce_WP();
		$plugin->run();
	}
	run_stackcommerce_wp();

	//
	// Won't work on WP VIP installations but we're keeping it here
	// to maintain a single codebase for WP.org, WP.com and WP VIP.
	//

	/**
	* Add activation, deactivation and uninstall hooks
	*
	* @since    1.0.0
	*/
	function scwp_register_maintenance_hooks() {
		$stackcommerce_wp_maintenance = new StackCommerce_WP_Maintenance();

		$stackcommerce_wp_maintenance->activation();
		register_deactivation_hook( __FILE__, array( $stackcommerce_wp_maintenance, 'deactivate' ) );
	}
	scwp_register_maintenance_hooks();

	/**
	 * Register filter to add action link
	 *
	 * @since    1.0.4
	 */
	function scwp_register_action_links() {
		$plugin = new StackCommerce_WP();

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $plugin, 'add_settings_action_link' ) );
	}
	scwp_register_action_links();

} else {
	add_action( 'admin_notices', 'scwp_requirements_error' );
}

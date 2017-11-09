<?php
/**
 * Optimizely X
 *
 * @link https://www.optimizely.com
 *
 * @author Optimizely
 * @copyright 2017 Optimizely
 * @license GPL-2.0+
 * @package Optimizely_X
 * @since 1.1.0
 *
 * @wordpress-plugin
 * Plugin Name: Optimizely X
 * Plugin URI: https://wordpress.org/plugins/optimizely-x/
 * Description: Simple, fast, and powerful. <a href="https://www.optimizely.com">Optimizely</a> is a dramatically easier way for you to improve your website through A/B testing. Create an experiment in minutes with our easy-to-use visual interface with absolutely no coding or engineering required. Convert your website visitors into customers and earn more revenue today! To get started: 1) Click the "Activate" link to the left of this description, 2) Sign up for an <a href="https://www.optimizely.com">Optimizely account</a>, and 3) Create an API Token here: <a href="https://www.optimizely.com/tokens">API Tokens</a>, and enter your API token in the Configuration Tab of the Plugin, then select a project to start testing!
 * Version: 1.1.0
 * Author: Optimizely
 * Author URI: https://www.optimizely.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: optimizely-x
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The base directory for all Optimizely X plugin files.
 *
 * @since 1.0.0
 * @var string
 */
define( 'OPTIMIZELY_X_BASE_DIR', __DIR__ );

/**
 * The base URL for all Optimizely X plugin files.
 *
 * @since 1.0.0
 * @var string
 */
define( 'OPTIMIZELY_X_BASE_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

/**
 * An autoloader callback for loading classes in the Optimizely_X namespace.
 *
 * @param string $class The class name that was referenced.
 *
 * @private
 */
function optimizely_load_class( $class ) {

	// Set project-specific namespace prefix.
	$prefix = 'Optimizely_X\\';

	// Determine if the requested class is in this project's namespace.
	$class = ltrim( $class, '\\' );
	if ( strpos( $class, $prefix ) !== 0 ) {
		return;
	}

	// Convert class name to WordPress standard conventions.
	$class = strtolower(
		str_replace( array( $prefix, '_' ), array( '', '-' ), $class )
	);

	// Treat the class name as a path and split into parts.
	$dirs = explode( '\\', $class );

	// Remove the name of the class from the directory path.
	$class = array_pop( $dirs );

	// Include the class file.
	require_once OPTIMIZELY_X_BASE_DIR
		. rtrim( '/includes/' . implode( '/', $dirs ), '/' )
		. '/class-' . $class . '.php';
}

// Initialize the autoloader.
spl_autoload_register( 'optimizely_load_class' );

// Bootstrap the plugin.
Optimizely_X\Core::instance();

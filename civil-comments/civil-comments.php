<?php
/**
 * Plugin Name:     Civil Comments
 * Plugin URI:      https://www.civilcomments.com/
 * Description:     Replace your comments with Civil Comments
 * Author:          Civil Comments
 * Author URI:      https://www.civilcomments.com/
 * Text Domain:     civil-comments
 * Domain Path:     /languages
 * Version:         0.2.1
 * License:         GPLv2 or later
 *
 * @package         Civil_Comments
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'CIVIL_PLUGIN_DIR' ) ) {
	define( 'CIVIL_PLUGIN_DIR', dirname( __FILE__ ) );
}

if ( ! defined( 'CIVIL_PLUGIN_URL' ) ) {
	define( 'CIVIL_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
}

if ( ! defined( 'CIVIL_VERSION' ) ) {
	define( 'CIVIL_VERSION', '0.2.1' );
}

require_once CIVIL_PLUGIN_DIR . '/includes/requirements.php';
$requirements = new Civil_Requirements_Check(
	__FILE__,
	'Civil Comments',
	'5.3',
	'4.2'
);
if ( ! $requirements->check() ) {
	return;
}

require_once CIVIL_PLUGIN_DIR . '/includes/functions.php';
require_once CIVIL_PLUGIN_DIR . '/includes/template-tags.php';

if ( is_admin() ) {
	require_once CIVIL_PLUGIN_DIR . '/includes/admin.php';
}

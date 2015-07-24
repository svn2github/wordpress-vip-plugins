<?php
/**
 * Plugin Name: Roost Web Push
 * Plugin URI: https://goroost.com/
 * Description: Drive traffic to your website with Roost Notifications -- which includes Chrome and Safari.
 * Version: 1.0.0
 * Author: Roost
 * Author URI: https://goroost.com
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'ROOST_URL' ) ) {
    define( 'ROOST_URL', plugins_url( '', __FILE__ ) );
}

require_once( plugin_dir_path( __FILE__ ) . 'includes/class-roost-core.php' );
require_once( plugin_dir_path( __FILE__ ) . 'includes/class-roost-api.php' );

Roost::init();

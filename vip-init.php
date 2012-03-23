<?php

define( 'WPCOM_IS_VIP_ENV', function_exists( 'wpcom_is_vip' ) );

// Load our development and environment helpers
require_once( WP_CONTENT_DIR . '/themes/vip/plugins/vip-do-not-include-on-wpcom/vip-local-development-helper/vip-local-development-helper.php' );

// Load the WordPress.com dependent helper files, only on WordPress.com (for now)
if ( function_exists( 'wpcom_is_vip' ) ) {
	wpcom_vip_load_helper_wpcom(); // vip-helper-wpcom.php
	wpcom_vip_load_helper_stats(); // vip-helper-wpcom.php
}

// Load the "works everywhere" helper file
wpcom_vip_load_helper(); // vip-helper.php
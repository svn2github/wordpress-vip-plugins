<?php

define( 'WPCOM_IS_VIP_ENV', function_exists( 'wpcom_is_vip' ) );

// Load our development and environment helpers
require_once( __DIR__ . '/vip-do-not-include-on-wpcom/vip-local-development-helper/vip-local-development-helper.php' );
require_once( __DIR__ . '/vip-do-not-include-on-wpcom/vip-powered-wpcom/vip-powered-wpcom.php' );
require_once( __DIR__ . '/vip-do-not-include-on-wpcom/vip-roles.php' );
require_once( __DIR__ . '/vip-do-not-include-on-wpcom/vip-permastructs.php' );

// These are helper functions specific to WP.com-related functionality
wpcom_vip_load_helper_wpcom(); // vip-helper-wpcom.php


// Load the WordPress.com dependent helper files, only on WordPress.com (for now)
if ( WPCOM_IS_VIP_ENV ) {
	wpcom_vip_load_helper_stats(); // vip-helper-stats-wpcom.php
} else {
	// Local helpers that add WP.com functionality
	if ( ! function_exists( 'jetpack_is_mobile' ) )
		require_once( __DIR__ . '/vip-do-not-include-on-wpcom/is-mobile.php' );
	require_once( __DIR__ . '/vip-do-not-include-on-wpcom/wpcom-functions.php' );
}

// Load the "works everywhere" helper file
wpcom_vip_load_helper(); // vip-helper.php

// Shared plugins we want to load for everyone
wpcom_vip_load_plugin( 'pmc-post-savior' );
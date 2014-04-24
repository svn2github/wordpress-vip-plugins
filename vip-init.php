<?php

define( 'WPCOM_IS_VIP_ENV', function_exists( 'wpcom_is_vip' ) );

// Load our development and environment helpers
require_once( __DIR__ . '/vip-do-not-include-on-wpcom/vip-utils.php' );
require_once( __DIR__ . '/vip-do-not-include-on-wpcom/vip-local-development-helper/vip-local-development-helper.php' );
require_once( __DIR__ . '/vip-do-not-include-on-wpcom/vip-powered-wpcom/vip-powered-wpcom.php' );
require_once( __DIR__ . '/vip-do-not-include-on-wpcom/vip-roles.php' );
require_once( __DIR__ . '/vip-do-not-include-on-wpcom/vip-permastructs.php' );
require_once( __DIR__ . '/vip-do-not-include-on-wpcom/vip-mods.php' );
require_once( __DIR__ . '/vip-do-not-include-on-wpcom/vip-media.php' );

// Load WP_CLI helpers
if ( defined( 'WP_CLI' ) && WP_CLI )
    require_once( __DIR__ . '/vip-do-not-include-on-wpcom/vip-wp-cli.php' );

// These are helper functions specific to WP.com-related functionality
wpcom_vip_load_helper_wpcom(); // vip-helper-wpcom.php
wpcom_vip_load_helper_stats(); // vip-helper-stats-wpcom.php

// Load the WordPress.com dependent helper files, only on WordPress.com (for now)
if ( false === WPCOM_IS_VIP_ENV ) {
	// Local helpers that add WP.com functionality
	if ( ! function_exists( 'jetpack_is_mobile' ) )
		require_once( __DIR__ . '/vip-do-not-include-on-wpcom/is-mobile.php' );
	require_once( __DIR__ . '/vip-do-not-include-on-wpcom/wpcom-functions.php' );

	if ( function_exists( 'wpcom_print_sitemap' ) ) {
		trigger_error( 'You are loading a copy of the wpcom-sitemap plugin. This is no longer necessary and will be automattically loaded for you. Please remove your local copy to remove this error.', E_USER_WARNING );
	} else {
		require_once( __DIR__ . '/vip-do-not-include-on-wpcom/wpcom-plugins/wpcom-sitemap.php' );
	}
}

// Load the "works everywhere" helper file
wpcom_vip_load_helper(); // vip-helper.php

do_action( 'vip_loaded' );

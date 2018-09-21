<?php

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

// Autoloading for prophecy
require_once dirname( dirname( __FILE__ ) ) . '/vendor/autoload.php';

/**
 * Manually load the plugin for tests.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/apple-news.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

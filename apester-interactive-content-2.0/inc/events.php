<?php
/*
 * Security check:
 * Exit if file accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_enqueue_scripts', 'apester_events_enqueue' );
function apester_events_enqueue($hook) {
	$apesterTokensSent = get_option( 'apester-tokens-sent' );
	$tokensPublishOptionUpdated = get_option( 'tokens-publish-option-updated' );

	// if the option is true - no need to continue with this logic
	if ($apesterTokensSent && $tokensPublishOptionUpdated) {
		return;
	}

	wp_enqueue_script( 'apester-events-ajax', plugins_url( '/public/js/apester_events.dist.js', QMERCE__PLUGIN_FILE ), array('jquery') );

	// in the js code, object properties are accessed as ajax_object.ajaxUrl etc.
	wp_localize_script( 'apester-events-ajax', 'ape_ajax_object',
		array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		       'isApesterTokensSent' => $apesterTokensSent,
		       'isTokensPublishOptionUpdated' => $tokensPublishOptionUpdated ) );
}

add_action( 'wp_ajax_apester_events', 'apester_events_callback' );
function apester_events_callback() {
	$isApesterTokenSent = boolval( $_POST['apesterTokenSent'] );

	// we update the flag to true anyway
	update_option( 'apester-tokens-sent', true );

	echo get_option( 'apester-tokens-sent' );
	wp_die();
}

add_action( 'wp_ajax_apester_tokens_publish_option', 'apester_tokens_publish_option_callback' );
function apester_tokens_publish_option_callback() {
	$isPublishOptionUpdated = boolval( $_POST['isPublishOptionUpdated'] );

	// we update the flag to true anyway
	update_option( 'tokens-publish-option-updated', true );

	echo get_option( 'tokens-publish-option-updated' );
	wp_die();
}



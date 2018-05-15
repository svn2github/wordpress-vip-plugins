<?php
/*
 * Security check:
 * Exit if file accessed directly.
 */
if ( ! defined('ABSPATH') ) {
	exit;
}

add_action( 'admin_enqueue_scripts', 'apester_events_enqueue' );
function apester_events_enqueue($hook) {
	global $wp_version;

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
		       'wp_version' => $wp_version,
		       'php_version' => phpversion(),
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

/**
 * Create a new array of tokens based on the 'auth_token' sub-property of plugin options
 * The new array is a dictionary, and it replaces the use of the old 'auth-token' property.
 * This functionality should only run once after the plugin has been updated
 */
function createAutoPlaylistFromTokens() {
	$apester_options = get_option( 'qmerce-settings-admin' );
	$apester_tokens = $apester_options['apester_tokens'];

	// if $apester_tokens exists, it means it is has been created before with the logic below, therefor there's no need to convert anything, so we abort
	if ( isset($apester_tokens) ) {
		return;
	}
	
	$tokens = $apester_options['auth_token'];
	
	// make sure we convert old plugin tokens that containd only one string into array of strings first
	$tokens = is_array( $tokens ) ? $tokens : array( $tokens );

	// init the new tokens associative array from the current tokens
	$autoPlaylistTokens = array();
	foreach($tokens as $key => &$value) {
		$autoPlaylistTokens[$value] = array(
			'isPlaylistEnabled' => ( $key == 0 ? '1' : '0' )
		);
	}
	
	// save the new list into a new sub-property of apester plugin settings (found in -> get_option( 'qmerce-settings-admin' ))
	$apester_options['apester_tokens'] = $autoPlaylistTokens;

	update_option( 'qmerce-settings-admin', $apester_options );

}

createAutoPlaylistFromTokens();

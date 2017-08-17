<?php

function jwplayer_log( $msg, $print_r = false ) {
	// Due to Wordpress VIP requirements this function is just a stub.
	// If you want to make it work, please visit:
	// https://github.com/jwplayer/wordpress-plugin/wiki/Enable-logging-function
	return;
}

// Function to return the jwplayer_content_mask
function jwplayer_get_content_mask() {
	$content_mask = sanitize_text_field( get_option( 'jwplayer_content_mask' ) );
	if ( 'content.bitsontherun.com' === $content_mask ) {
		$content_mask = 'content.jwplatform.com';
	}
	return $content_mask;
}

// Function to fetch json formatted options and turn the older serialized
// values into json.
function jwplayer_get_json_option( $option_name ) {
	$raw_json = get_option( $option_name );
	if ( !$raw_json ) {
		return null;
	}
	$option_value = json_decode( $raw_json, true );
	if ( null === $option_value ) {
		$option_value = unserialize( $raw_json );
		if ( false === $option_value ) {
			// There should be no cases of a serialized false, so of this is the
			// return value, we can assume it was impossible to unserialize the
			// string.
			return null;
		}
		update_option( $option_name, wp_json_encode( $option_value ) );
	}
	return $option_value;
}

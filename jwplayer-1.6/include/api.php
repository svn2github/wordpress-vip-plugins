<?php

function jwplayer_api_call( $method, $params = array() ) {
	$api = jwplayer_api_get_instance();
	foreach ( $params as $key => $value ) {
		if ( null === $value ) {
			unset( $params[ $key ] );
		}
	}
	if ( $api ) {
		$response = $api->call( $method, $params );
		if ( $response ) {
			return $response;
		} else {
			jwplayer_log( 'API: invalid response.' );
		}
	}
	return null;
}


function jwplayer_api_response_ok( $response ) {
	if ( isset( $response ) && isset( $response['status'] ) ) {
		if ( 'ok' === $response['status'] ) {
			return true;
		}
		return false;
	}
	return null;
}


// Get the API object
function jwplayer_api_get_instance() {
	$api_key = get_option( 'jwplayer_api_key' );
	$api_secret = get_option( 'jwplayer_api_secret' );

	if ( 8 === strlen( $api_key ) && 24 === strlen( $api_secret ) ) {
		return new JWPlayer_api( $api_key, $api_secret );
	} else {
		jwplayer_log( 'API: Could not instantiate.' );
		return null;
	}
}

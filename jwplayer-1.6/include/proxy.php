<?php

function jwplayer_json_error( $message ) {
	$error = array(
		'status' => 'error',
		'message' => $message,
	);

	header( 'Content-Type: application/json' );
	echo json_encode( $error );
}

function jwplayer_ajax_jwp_api_proxy() {

	$JWPLAYER_PROXY_METHODS = array(
		'/videos/list',
		'/channels/list',
		'/videos/create',
		'/videos/thumbnails/show',
		'/players/list',
	);

	if ( ! isset( $_GET['token'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['token'] ) ), 'jwplayer-widget-nonce' ) ) { // Input var okay
		return;
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		jwplayer_json_error( 'Access denied' );
		return;
	}

	$method = ! empty( $_GET['method'] ) ? sanitize_text_field( wp_unslash( $_GET['method'] ) ) : null; // Input var okay

	if ( null === $method ) {
		jwplayer_json_error( 'Method was not specified' );
		return;
	}

	if ( ! in_array( $method, $JWPLAYER_PROXY_METHODS, true ) ) {
		jwplayer_json_error( 'Access denied' );
		return;
	}

	$jwplayer_api = jwplayer_api_get_instance();

	if ( null === $jwplayer_api ) {
		jwplayer_json_error( 'Enter your API key and secret first' );
		return;
	}

	$params = array();

	foreach ( $_GET as $name => $value ) { // Input var okay
		$name = sanitize_text_field( $name );
		if ( 'method' !== $name ) {
			$params[ $name ] = sanitize_text_field( wp_unslash( $value ) ); // Input var okay
		}
	}

	$params['api_format'] = 'json';
	$response = $jwplayer_api->call( $method, $params );

	header( 'Content-Type: application/json' );
	echo json_encode( $response );
}

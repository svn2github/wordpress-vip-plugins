<?php

/*-----------------------------------------------------------------------------
 * PHP client library for JW Platform System API
 *
 * Version:     1.4
 * Updated:     Wed Feb  8 11:59:56 CET 2012
 *
 * For the System API documentation see:
 * http://apidocs.jwplayer.com/
 *-----------------------------------------------------------------------------
 */

class JWPlayer_api {
	private $_url = 'https://api.jwplatform.com/v1';
	private $_library;

	private $_key, $_secret, $_version;

	public function __construct( $key, $secret ) {

		$this->_version = 'jwp-wp-plugin-' . JWPLAYER_PLUGIN_VERSION;
		$this->_key = $key;
		$this->_secret = $secret;

		// Determine which HTTP library to use:
		if ( defined( 'WPCOM_IS_VIP_ENV' ) && true === WPCOM_IS_VIP_ENV ) {
			$this->_library = 'wpvip';
		} else {
			$this->_library = 'wp';
		}
	}

	public function version() {
		return $this->_version;
	}

	// Sign API call arguments
	private function _sign( $args ) {
		ksort( $args );
		// We will use the same function as we use for generating the query
		$sbs = http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
		// Add shared secret to the Signature Base String and generate the signature
		$signature = sha1( $sbs . $this->_secret );

		return $signature;
	}

	// Add required api_* arguments
	private function _args( $args, $sign = true ) {
		$args['api_nonce'] = str_pad( mt_rand( 0, 99999999 ), 8, STR_PAD_LEFT );
		$args['api_timestamp'] = time();

		if ( $sign ) {
			$args['api_key'] = $this->_key;
		}

		if ( ! array_key_exists( 'api_format', $args ) ) {
			// Use the serialised PHP format,
			// otherwise use format specified in the call() args.
			$args['api_format'] = 'json';
		}

		// Add API kit version
		$args['api_kit'] = 'php-' . $this->_version;

		// Sign the array of arguments
		if ( $sign ) {
			$args['api_signature'] = $this->_sign( $args );
		}

		return $args;
	}

	// Construct call URL
	public function call_url( $call, $args = array() ) {
		$sign = '/accounts/credentials/show' !== $call;
		$url = $this->_url . $call . '?' . http_build_query( $this->_args( $args, $sign ), '', '&', PHP_QUERY_RFC3986 );
		return $url;
	}

	// Make an API call
	public function call( $call, $args = array() ) {
		$url = $this->call_url( $call, $args );

		$response = null;
		if ( 'wpvip' == $this->_library ) {
			$response = vip_safe_wp_remote_get( $url, '', 3, 3 );
		} else {
			$response = wp_remote_get( $url, array(
				'timeout' => 15
			) );
		}

		if ( is_wp_error( $response ) ) {
			return 'Error: call to JW Player API failed';
		}

		$response = wp_remote_retrieve_body( $response );
		$decoded_response = json_decode( $response, $assoc = true );
		return $decoded_response;
	}

	// Upload a file
	public function upload( $upload_link = array(), $file_path, $api_format = 'json' ) {
		if ( ! is_array( $upload_link ) ) {
			return 'Invalid Upload link array.';
		}
		$url = $upload_link['protocol'] . '://' . $upload_link['address'] . $upload_link['path'] .
			'?key=' . $upload_link['query']['key'] . '&token=' . $upload_link['query']['token'] .
			'&api_format=' . $api_format;

		$post_data = array( 'file' => '@' . $file_path );
		$response = wp_remote_post( $url, array(
			'method' => 'post',
			'timeout' => 30,
			'blocking' => true,
			'body' => $post_data,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		} else {
			return json_decode( $response, $assoc = true );
		}
	}
}

<?php

/**
 * Description.
 *
 * An extension of the Sailthru_Client class to use WordPress' HTTP API instead of cURL.
 * Provides a drop in replacement for the PHP5 Sailthru library with improved WordPress integration
 * by replacing all cURL calls with WordPress HTTP API calls.
 */
class WP_Sailthru_Client extends Sailthru_Client {

	protected $api_uri = 'https://api.sailthru.com';


	private function cleanDebugParams( $debug_params ) {

		if ( isset( $debug_params['payload']['api_key'] ) ) {
			unset( $debug_params['payload']['api_key'] );
		}

		if ( isset( $debug_params['payload']['sig'] ) ) {
			unset( $debug_params['payload']['sig'] );
		}

		if ( isset( $debug_params['api'] ) ) {
			$url = $debug_params['api'];

			if ( $url = wp_parse_url( $url ) ) {
				$debug_params['api'] = $url['scheme'] . '://' . $url['host'] . $url['path'];
			}
		}

		if ( isset( $debug_params['request'] ) ) {
			$url = $debug_params['request'];
			if ( $url = wp_parse_url( $url ) ) {
				$query = $url['query'];
				parse_str( $url['query'], $params );
				unset( $params['api_key'] );
				unset( $params['sig'] );
				http_build_query( $params );
				$debug_params['request'] = $url['scheme'] . '://' . $url['host'] . $url['path'] . '?' . http_build_query( $params );
			}
		}
		return $debug_params;
	}

	/**
	 * Prepare JSON payload
	 */
	protected function prepareJsonPayload( array $data, array $binary_data = array() ) {

		// Get the plugin and version and add to API calls
		if ( function_exists( 'get_plugin_data' ) ) {
			$plugin_info = get_plugin_data( __DIR__ . '/../plugin.php' );
			$version     = ! empty( $plugin_info['Version'] ) ? $plugin_info['Version'] : '';
		} else {
			$version = '';
		}

		$integration         = 'WordPress Integration - ' . $version;
		$data['integration'] = $integration;

		$payload        = array(
			'api_key' => $this->api_key,
			'format'  => 'json',
			'json'    => wp_json_encode( $data ),
		);
		$payload['sig'] = Sailthru_Util::getSignatureHash( $payload, $this->secret );
		if ( ! empty( $binary_data ) ) {
			$payload = array_merge( $payload, $binary_data );
		}
		return $payload;
	}


	/**
	 * Overload method to transparently intercept calls.
	 * Perform an HTTP request using the WordPress HTTP API.
	 *
	 * @param string  $url
	 * @param array   $data
	 * @param string  $method
	 * @return string
	 */
	function httpRequestCurl( $action, array $data, $method = 'POST', $options = [] ) {

		$url = $this->api_uri . '/' . $action;

		if ( 'GET' === $method ) {
			$url_with_params = $url;
			if ( count( $data ) > 0 ) {
				$url_with_params .= '?' . http_build_query( $data );
			}
			$url = $url_with_params;
		} else {
			// Build a WP approved array.
			$data = array(
				'method'      => 'POST',
				'timeout'     => 3,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => array(),
				'body'        => $data, // Data passed to us by the user.
				'cookies'     => array(),
			);
		}

		// start debugging of call
		$debug_params = array(
			'api'     => $url,
			'payload' => $data,
			'method'  => $method,
		);

		if ( 'GET' === $method ) {

			$debug_params['request'] = $url_with_params;

			if ( defined( 'WPCOM_IS_VIP_ENV' ) && true === WPCOM_IS_VIP_ENV ) {
				$reply = vip_safe_wp_remote_get( $url, $data, 3, 3 );
			} else {
				$reply = wp_remote_get( $url, $data );
			}
		} else {
			$reply = wp_remote_post( $url, $data );
		}

		write_log( $this->cleanDebugParams( $debug_params ) );
		// end debugging call

		if ( isset( $reply ) ) {
			//debug response
			write_log( $reply );

			if ( is_wp_error( $reply ) ) {
				throw new Sailthru_Client_Exception( "Bad response received from $url: " . $reply->get_error_message() );
			} else {

				if ( wp_remote_retrieve_response_code( $reply ) === 200 ) {
					return $reply['body'];
				} else {

					$resp = json_decode( $reply['body'] );
					if ( ! empty( $resp->errormsg ) ) {
						return  $resp->errormsg;
					} else {
						return false;
					}
				}
			}
		} else {
			throw new Sailthru_Client_Exception( 'A reply was never generated.' );
		}

	} // End httpRequestCurl().

} // End of WP_Sailthru_Client.

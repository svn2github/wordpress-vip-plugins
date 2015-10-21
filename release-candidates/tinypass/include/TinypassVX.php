<?php

/**
 * Implementation of integration with Tinypass VX REST API
 * Class TinypassVX
 */
class TinypassVX extends TPSecurityUtils {

	protected $APIToken, $baseURL;
	protected $requestTimeout = 200;

	const ERROR_RESOURCE_ALREADY_EXISTS = 820;
	const ERROR_NOT_FOUND = 404;
	const ERROR_TERM_ALREADY_EXISTS = 1000;

	/**
	 * Set custom base url for API requests
	 *
	 * @param string $url
	 */
	public function setBaseURL( $url ) {
		$this->baseURL = $url;
	}

	public function __construct( $baseURL, $APIToken ) {
		$this->baseURL  = $baseURL;
		$this->APIToken = $APIToken;
	}

	/**
	 * Method performs request to API and returns response, throws an exception in case of failure
	 *
	 * @param string $path API method path
	 * @param array $params Array of parameters
	 * @param bool $noTimeout Do request without timeout?
	 *
	 * @throws Exception
	 * @return stdClass
	 */
	public function callAPI( $path, $params, $noTimeout = true ) {
		$url                 = $this->baseURL . $path;
		$params['api_token'] = $this->APIToken;

		$request = wp_remote_post( $url, array(
			'body' => $params,
		) );

		$response = wp_remote_retrieve_body( $request );

		if ( 200 != wp_remote_retrieve_response_code( $request ) ) {
			throw new Exception( __( 'API connection failed', 'tinypass' ) );
		}

		if ( $response === false ) {
			throw new Exception( __( 'API connection failed', 'tinypass' ) );
		}

		if ( empty( $response ) ) {
			throw new Exception( __( 'API returned empty result', 'tinypass' ) );
		}

		$jsonResponse = json_decode( $response );

		if ( null === $jsonResponse ) {
			throw new Exception( __( 'API returned ambiguous response', 'tinypass' ) );
		}

		return $jsonResponse;
	}

}
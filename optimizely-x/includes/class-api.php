<?php
/**
 * Optimizely X: API class
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

namespace Optimizely_X;

/**
 * A class to handle communication with the Optimizely REST API.
 *
 * @since 1.0.0
 */
class API {

	/**
	 * The base URL for all API requests.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const BASE_URL = 'https://api.optimizely.com/v2';

	/**
	 * The number of results to return per "page" of data.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	const PER_PAGE = 100;

	/**
	 * Handles an HTTP DELETE operation using the Optimizely X API.
	 *
	 * @param string $operation The operation URL endpoint.
	 * @param array $data Optional data to include with the request.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array The API response data.
	 */
	public function delete( $operation, $data = array() ) {
		return $this->request( 'DELETE', $operation, $data );
	}

	/**
	 * Handles an HTTP GET operation using the Optimizely X API.
	 *
	 * @param string $operation The operation URL endpoint.
	 * @param array $data Optional data to include with the request.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array The API response data.
	 */
	public function get( $operation, $data = array() ) {

		// Add the per page option, if not set.
		if ( empty( $data['per_page'] ) ) {
			$data['per_page'] = self::PER_PAGE;
		}

		// Scaffold the response.
		$response = array(
			'json' => array(),
		);

		// Request data, one page at a time, until all data is delivered.
		$next_link = '';
		do {

			// Handle next link.
			if ( ! empty( $next_link ) ) {
				$operation = $next_link;
				$data = array();
			}

			// Get the API response for the operation.
			$raw_response = $this->request( 'GET', $operation, $data );

			// Add the response code.
			if ( ! empty( $raw_response['code'] ) ) {
				$response['code'] = absint( $raw_response['code'] );
			}

			// Add the response headers.
			if ( ! empty( $raw_response['headers'] ) ) {
				$response['headers'] = $raw_response['headers']->getAll();
			}

			// Add the response status.
			if ( ! empty( $raw_response['status'] ) ) {
				$response['status'] = sanitize_text_field( $raw_response['status'] );
			}

			// Combine the data from the raw response with the compiled data.
			if ( ! empty( $raw_response['json'] )
				&& is_array( $raw_response['json'] )
			) {
				$response['json'] = array_merge(
					$response['json'],
					$raw_response['json']
				);
			}

			// Negotiate next link.
			$next_link = ( ! empty( $response['headers']['link'] ) )
				? $this->get_next_link( $response['headers']['link'] )
				: '';
		} while ( ! empty( $next_link ) );

		return $response;
	}

	/**
	 * Handles an HTTP PATCH operation using the Optimizely X API.
	 *
	 * @param string $operation The operation URL endpoint.
	 * @param array $data Optional data to include with the request.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array The API response data.
	 */
	public function patch( $operation, $data = array() ) {
		return $this->request( 'PATCH', $operation, $data );
	}

	/**
	 * Handles an HTTP POST operation using the Optimizely X API.
	 *
	 * @param string $operation The operation URL endpoint.
	 * @param array $data Optional data to include with the request.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array The API response data.
	 */
	public function post( $operation, $data = array() ) {
		return $this->request( 'POST', $operation, $data );
	}

	/**
	 * Handles an HTTP PUT operation using the Optimizely X API.
	 *
	 * @param string $operation The operation URL endpoint.
	 * @param array $data Optional data to include with the request.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array The API response data.
	 */
	public function put( $operation, $data = array() ) {
		return $this->request( 'PUT', $operation, $data );
	}

	/**
	 * Extracts the link for the next page of data from response headers.
	 *
	 * @param string $header The raw header text to parse.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return string The URL, parsed from the header.
	 */
	private function get_next_link( $header ) {

		// Try to match a next link.
		$regex = '/<' . preg_quote( self::BASE_URL, '/' ) . '([^>]+)>; rel=next/';
		if ( ! preg_match( $regex, $header, $matches ) ) {
			return '';
		}

		return $matches[1];
	}

	/**
	 * Executes a request against the Optimizely X API.
	 *
	 * @param string $method One of DELETE, GET, PATCH, POST, or PUT.
	 * @param string $operation The API endpoint to execute against.
	 * @param array $data An optional array of data to include with the request.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return array
	 */
	private function request( $method, $operation, $data = array() ) {

		// If the provided operation is a partial path, convert to a full URL.
		if ( 0 !== strpos( $operation, self::BASE_URL ) ) {
			$url = self::BASE_URL . $operation;
		} else {
			$url = $operation;
		}

		// Ensure URL is valid before attempting to make the request.
		$url = esc_url_raw( $url );
		if ( empty( $url ) ) {
			return array(
				'code' => 404,
				'error' => array(
					__( 'Invalid API URL.', 'optimizely-x' ),
				),
				'status' => 'ERROR',
			);
		}

		// Ensure we have a token before attempting to make the request.
		$token = get_option( 'optimizely_token' );
		if ( empty( $token ) ) {
			return array(
				'code' => 401,
				'error' => array(
					esc_html__( 'You have not filled in a token.', 'optimizely-x' ),
				),
				'status' => 'NOTOKEN',
			);
		}

		// Add authentication header to the request object.
		$request = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . sanitize_text_field( $token ),
			),
		);

		// Encode data in body or query depending on request method.
		if ( in_array( $method, array( 'POST', 'PATCH' ), true ) ) {
			$request['body'] = ( ! empty( $data ) ) ? wp_json_encode( $data ) : '{}';
		} elseif ( ! empty( $data ) ) {
			$url .= '?' . http_build_query( $data );
		}

		// Fork for request method.
		switch ( $method ) {
			case 'DELETE':
				$request['method'] = 'DELETE';
				$response = wp_safe_remote_request( $url, $request );
				break;
			case 'GET':
				if ( defined( 'WPCOM_IS_VIP_ENV' ) && true === WPCOM_IS_VIP_ENV ) {
					$response = vip_safe_wp_remote_get(
						$url,
						array(),
						3,
						3,
						20,
						$request
					);
				} else {
					$response = wp_safe_remote_get( $url, $request );
				}
				break;
			case 'PATCH':
				$request['method'] = 'PATCH';
				$response = wp_safe_remote_request( $url, $request );
				break;
			case 'POST':
				$response = wp_safe_remote_post( $url, $request );
				break;
			case 'PUT':
				$request['method'] = 'PUT';
				$response = wp_safe_remote_request( $url, $request );
				break;
			default:
				return array(
					'code' => 403,
					'error' => array(
						esc_html__( 'Invalid request method.', 'optimizely-x' ),
					),
					'status' => 'ERROR',
				);
		} // End switch().

		// Build result object.
		$result = array(
			'body' => wp_remote_retrieve_body( $response ),
			'code' => absint( wp_remote_retrieve_response_code( $response ) ),
			'error' => array(),
			'headers' => wp_remote_retrieve_headers( $response ),
			'json' => json_decode( wp_remote_retrieve_body( $response ), true ),
		);

		// Handle rate limiting.
		if ( 429 === $result['code'] ) {
			$wait_time = wp_remote_retrieve_header( $response, 'X-RATELIMIT-RESET' );
			usleep( $wait_time );

			return $this->request( $method, $url, $data );
		}

		// Check for errors.
		if ( empty( $response )
			|| ! is_array( $response )
			|| is_wp_error( $response )
			|| $result['code'] < 200
			|| $result['code'] > 204
		) {
			$result['status'] = 'ERROR';
		}

		// Add success status to response.
		$result['status'] = 'SUCCESS';

		return $result;
	}
}

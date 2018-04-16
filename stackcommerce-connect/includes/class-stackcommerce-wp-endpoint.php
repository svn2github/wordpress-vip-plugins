<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Register the endpoint to receive API calls
 *
 * @since      1.0.0
 * @package    StackCommerce_WP
 * @subpackage StackCommerce_WP/includes
 */
class StackCommerce_WP_Endpoint {

	const DISCONNECTED_STATUS = 'disconnected';

	/**
	 * Receive API requests
	 *
	 * @since    1.3.0
	 */
	public function receive() {
		global $wp;

		if ( isset( $wp->query_vars['sc-api-version'] ) && isset( $wp->query_vars['sc-api-route'] ) ) {
			$sc_api_version = $wp->query_vars['sc-api-version'];
			$sc_api_route   = $wp->query_vars['sc-api-route'];

			// @codingStandardsIgnoreLine
			$sc_fields = json_decode( file_get_contents( 'php://input' ), true );
			$sc_hash   = isset( $_SERVER['HTTP_X_HASH'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_HASH'] ) ) : ''; // WPCS: input var okay.
		}

		if ( isset( $sc_api_version ) && isset( $sc_api_route ) && isset( $sc_fields ) ) {
			$secret            = get_option( 'stackcommerce_wp_secret' );
			$connection_status = get_option( 'stackcommerce_wp_connection_status' );

			if ( self::DISCONNECTED_STATUS === $connection_status ||
				( ! $secret && ! empty( $secret ) )
			) {
				return $this->response( 'This installation needs to be connected in order to receive content.',
					array(
						'code'        => 'stackcommerce_wp_disconnected_status',
						'status_code' => 400,
					)
				);
			}

			switch ( $sc_api_route ) {
				// @codingStandardsIgnoreStart
				case 'posts':
					$this->authentication( $sc_hash, $sc_fields );
				break;
				// @codingStandardsIgnoreEnd
			}

			exit;
		}
	}

	/**
	 * Performs authentication and generate a hash based on post content
	 *
	 * @since    1.4.0
	 */
	protected function authentication( $hash, $request ) {
		if ( empty( $request ) || empty( $request['post_content'] ) ) {
			return $this->response( 'Request is empty or post content is missing',
				array(
					'code'        => 'stackcommerce_wp_empty_request',
					'status_code' => 400,
				)
			);
		}

		$secret = '';

		if ( isset( $request['encode'] ) && ( true === $request['encode'] ) ) {
			$decoded_content = base64_decode( $request['post_content'] );

			if ( ! $decoded_content ) {
				return $this->response( 'Post content decode failed',
					array(
						'code'        => 'stackcommerce_wp_invalid_base64',
						'status_code' => 400,
					)
				);
			}

			$request['post_content'] = $decoded_content;
			$secret                  = hash_hmac( 'sha256', $decoded_content, get_option( 'stackcommerce_wp_secret' ) );
		} else {
			$secret = hash_hmac( 'sha256', $request['post_content'], get_option( 'stackcommerce_wp_secret' ) );
		}

		if ( $this->is_hash_valid( $hash, $secret ) ) {
			$stackcommerce_wp_article = new StackCommerce_WP_Article();
			$stackcommerce_wp_article->validate( $request );
		} else {
			return $this->response( 'Hash missing or invalid',
				array(
					'code'        => 'stackcommerce_wp_invalid_hash',
					'status_code' => 400,
				)
			);
		}
	}

	/**
	 * Makes hash comparison
	 *
	 * @since    1.1.0
	 */
	protected function is_hash_valid( $hash = '', $secret ) {
		if ( function_exists( 'hash_equals' ) ) {
			if ( ! empty( $hash ) && hash_equals( $hash, $secret ) ) {
				return true;
			} else {
				return false;
			}
		} else {
			if ( ! empty( $hash ) && $this->custom_hash_equals( $hash, $secret ) ) {
				return true;
			} else {
				return false;
			}
		}
	}

	/**
	 * Custom hash_equals() function for older PHP versions
	 * http://php.net/manual/en/function.hash-equals.php#115635
	 *
	 * @since    1.0.0
	 */
	protected function custom_hash_equals( $hash1, $hash2 ) {
		if ( strlen( $hash1 ) !== strlen( $hash2 ) ) {
			return false;
		} else {
			$res = $hash1 ^ $hash2;
			$ret = 0;

			for ( $i = strlen( $res ) - 1; $i >= 0; $i-- ) {
				$ret |= ord( $res[ $i ] );
			}

			return ! $ret;
		}
	}

	/**
	 * Send API responses
	 *
	 * @since    1.0.0
	 */
	public function response( $data, $args = array() ) {
		if ( is_array( $data ) ) {
			$response = $data;
		} else {
			$response = array(
				'message' => $data,
			);

			if ( $args['code'] ) {
				$code = array(
					'code' => $args['code'],
				);

				$response = $code + $response;
			}
		}

		if ( 200 === $args['status_code'] ) {
			wp_send_json_success( $response );
		} else {
			status_header( $args['status_code'] );
			wp_send_json_error( $response );
		}
	}
}

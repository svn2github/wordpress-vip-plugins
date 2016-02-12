<?php
namespace Apple_Push_API\Request;

use \Apple_Push_API\MIME_Builder as MIME_Builder;

require_once __DIR__ . '/../class-mime-builder.php';

/**
 * An object capable of sending signed HTTP requests to the Push API.
 *
 * @since 0.2.0
 */
class Request {

	/**
	 * Helper class used to build the MIME parts of the request.
	 *
	 * @var MIME_Builder
	 * @access private
	 * @since 0.2.0
	 */
	private $mime_builder;

	/**
	 * Whether or not we are debugging using a reverse proxy, like Charles.
	 *
	 * @var boolean
	 * @access private
	 * @since 0.2.0
	 */
	private $debug;

	/**
	 * The credentials that will be used to sign sent requests.
	 *
	 * @var Credentials
	 * @access private
	 * @since 0.2.0
	 */
	private $credentials;

	/**
	 * Default arguments passed to the WordPress HTTP API functions.
	 *
	 * @var array
	 * @access private
	 * @since 0.9.0
	 */
	private $default_args;

	/**
	 * Constructor.
	 *
	 * @param Credentials $credentials
	 * @param boolean $debug
	 * @param Mime_Builder $mime_builder
	 */
	function __construct( $credentials, $debug = false, $mime_builder = null ) {
		$this->credentials  = $credentials;
		$this->debug        = $debug;
		$this->mime_builder = $mime_builder ?: new MIME_Builder();

		// Set the default WordPress HTTP API args
		$this->default_args = apply_filters( 'apple_news_request_args', array(
			'timeout' => 30, // required because we need to package all images
			'reject_unsafe_urls' => true,
		) );
	}

	/**
	 * Sends a POST request with the given article and bundles.
	 *
	 * @param string $url
	 * @param string $article
	 * @param array $bundles
	 * @param array $meta
	 * @return mixed
	 * @since 0.2.0
	 */
	public function post( $url, $article, $bundles = array(), $meta = null ) {
		// Assemble the content to send
		$content = $this->build_content( $article, $bundles, $meta );

		// Build the post request args
		$args = array(
			'headers' => array(
				'Authorization' => $this->sign( $url, 'POST', $content ),
				'Content-Length' => strlen( $content ),
				'Content-Type' => 'multipart/form-data; boundary=' . $this->mime_builder->boundary(),
			),
			'body' => $content,
		);

		// Allow filtering and merge with the default args
		$args = apply_filters( 'apple_news_post_args', wp_parse_args( $args, $this->default_args ) );

		// Perform the request
		$response = wp_safe_remote_post( esc_url_raw( $url ), $args );

		// Parse and return the response
		return $this->parse_response( $response, true, 'post' );
	}

	/**
	 * Sends a DELETE request for the given article and bundles.
	 *
	 * @param string $url
	 * @return mixed
	 * @since 0.2.0
	 */
	public function delete( $url ) {
		// Build the delete request args
		$args = array(
			'headers' => array(
				'Authorization' => $this->sign( $url, 'DELETE' ),
			),
			'method' => 'DELETE',
		);

		// Allow filtering and merge with the default args
		$args = apply_filters( 'apple_news_delete_args', wp_parse_args( $args, $this->default_args ) );

		// Perform the delete
		$response = wp_safe_remote_request( esc_url_raw( $url ), $args );

		// NULL is a valid response for DELETE
		if ( is_null( $response ) ) {
			return null;
		}

		// Parse and return the response
		return $this->parse_response( $response, true, 'delete' );
	}

	/**
	 * Sends a GET request for the given article and bundles.
	 *
	 * @param string $url
	 * @return mixed
	 * @since 0.2.0
	 */
	public function get( $url ) {
		// Build the get request args
		$args = array(
			'headers' => array(
				'Authorization' => $this->sign( $url, 'GET' ),
			),
		);

		// Allow filtering and merge with the default args
		$args = apply_filters( 'apple_news_get_args', wp_parse_args( $args, $this->default_args ) );

		// Perform the get
		$response = wp_safe_remote_get( esc_url_raw( $url ), $args );

		// Parse and return the response
		return $this->parse_response( $response, true, 'get' );
	}

	/**
	 * Parses the API response and checks for errors.
	 *
	 * @param array $response
	 * @param boolean $json
	 * @param string $type
	 * @return mixed
	 * @since 0.2.0
	 */
	private function parse_response( $response, $json = true, $type = 'post' ) {
		// Ensure we have an expected response type
		if ( ( ! is_array( $response ) || ! isset( $response['body'] ) ) && ! is_wp_error( $response ) ) {
			throw new Request_Exception( __( 'Invalid response:', 'apple-news' ) . $response );
		}

		// If debugging mode is enabled, send an email
		$debugging = get_option( 'apple_news_enable_debugging' );
		if ( 'yes' === $debugging && 'get' != $type ) {
			$admin_email = filter_var( get_option( 'apple_news_admin_email' ), FILTER_VALIDATE_EMAIL );
			if ( ! empty( $admin_email ) ) {
				wp_mail(
					$admin_email,
					esc_html__( 'Apple News Notification', 'apple-news' ),
					print_r( $response, true )
				);
			}
		}

		// Check for errors with the request itself
		if ( is_wp_error( $response ) ) {
			$string_errors = '';
			foreach ( $response->get_error_messages() as $error ) {
				$string_errors .= $error . "\n";
			}
			throw new Request_Exception( __( 'There has been an error with your request:', 'apple-news' ) . "\n$string_errors" );
		}

		// Check for errors from the API
		$response_decoded = json_decode( $response['body'] );
		if ( ! empty( $response_decoded->errors ) ) {
			$messages = implode( ', ', wp_list_pluck( $response_decoded->errors, 'code' ) );
			throw new Request_Exception( $messages );
		}

		// Return the response in the desired format
		return $json ? $response_decoded : $response['body'];
	}

	/**
	 * Parses the API response and checks for errors.
	 * TODO The exporter has an abstracted article class. Should we have
	 * something similar here? That way this method could live there.
	 *
	 * @param string $article
	 * @param array $bundles
	 * @param array $meta
	 * @return string
	 * @since 0.2.0
	 */
	private function build_content( $article, $bundles = array(), $meta = null ) {
		$bundles = array_unique( $bundles );
		$content = '';

		if ( $meta ) {
			$content .= $this->mime_builder->add_metadata( $meta );
		}

		$content .= $this->mime_builder->add_json_string( 'my_article', 'article.json', $article );
		foreach ( $bundles as $bundle ) {
			$content .= $this->mime_builder->add_content_from_file( $bundle );
		}
		$content .= $this->mime_builder->close();

		return $content;
	}

	/**
	 * Signs the API request.
	 *
	 * @param string $url
	 * @param string $verb
	 * @param string $content
	 * @return array
	 * @since 0.2.0
	 */
	private function sign( $url, $verb, $content = null ) {
		$current_date = date( 'c' );

		$request_info = $verb . $url . $current_date;
		if ( 'POST' == $verb ) {
			$content_type = 'multipart/form-data; boundary=' . $this->mime_builder->boundary();
			$request_info .= $content_type . $content;
		}

		$secret_key = base64_decode( $this->credentials->secret() );
		$hash       = hash_hmac( 'sha256', $request_info, $secret_key, true );
		$signature  = base64_encode( $hash );

		return 'HHMAC; key=' . $this->credentials->key() . '; signature=' . $signature . '; date=' . $current_date;
	}
}

class Request_Exception extends \Exception {}

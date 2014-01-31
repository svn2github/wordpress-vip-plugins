<?php

/**
 * An extension of the Codebird class to use Wordpress' HTTP API instead of
 * cURL.
 *
 * @version 1.1.2
 */
class WP_Codebird extends \Codebird\Codebird {
	/**
	 * The current singleton instance
	 */
	private static $_instance = null;

	/**
	 * The file formats extensions twitter supports
	 * @var array
	 */
	protected $_supported_media_files_extensions = array( 'gif', 'jpg', 'jpeg', 'png' );

	/**
	 * Returns singleton class instance
	 * Always use this method unless you're working with multiple authenticated
	 * users at once.
	 *
	 * This method had to be overloaded because self was used
	 * in the references instead of get_called_class. So the method was always
	 * returning instances of Codebird instead of WP_Codebird.
	 *
	 * @return Codebird The instance
	 */
	public static function getInstance() {
		if ( self::$_instance == null ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	 * Overload magic __call() to transparently intercept Exceptions
	 *
	 * Most exceptions encountered in production are API timeouts - this will
	 * transparently handle these Exceptions to prevent fatal errors
	 */
	public function __call( $function, $arguments ) {
		try {
			return parent::__call( $function, $arguments );
		} catch ( Exception $e ) {
			return array();
		}
	}

	/**
	 * Calls the API using Wordpress' HTTP API.
	 *
	 * @since 0.1.0
	 * @see   Codebird::_callApi
	 *
	 * @param string $httpmethod      The HTTP method to use for making the request
	 * @param string $method          The API method to call
	 * @param string $method_template The templated API method to call
	 * @param        array            optional $params          The parameters to send along
	 * @param        bool             optional $multipart       Whether to use multipart/form-data
	 *
	 * @return mixed The API reply, encoded in the set return_format.
	 */
	protected function _callApi( $httpmethod, $method, $method_template, $params = array(), $multipart = false, $app_only_auth = false ) {
		$url             = $this->_getEndpoint( $method, $method_template );
		$url_with_params = null;
		$authorization   = null;

		$remote_params = array(
			'method'      => $httpmethod,
			'timeout'     => 5,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => null,
			'cookies'     => array(),
			'sslverify'   => false
		);

		if ( 'GET' == $httpmethod ) {
			$url_with_params = $url;
			if ( count( $params ) > 0 ) {
				$url_with_params .= '?' . http_build_query( $params );
			}

			$authorization = $this->_sign( $httpmethod, $url, $params );

			$url = $url_with_params;
		}
		else {
			if ( $multipart ) {
				$authorization = $this->_sign( $httpmethod, $url, array() );
				$params        = $this->_buildMultipart( $method_template, $params );

				// Add the boundaries
				$first_newline                              = strpos( $params, "\r\n" );
				$multipart_boundary                         = substr( $params, 2, $first_newline - 2 );
				$remote_params['headers']['Content-Length'] = strlen( $params );
				$remote_params['headers']['Content-Type']   = 'multipart/form-data; boundary=' . $multipart_boundary;

			}
			else {
				$authorization = $this->_sign( $httpmethod, $url, $params );
				$params        = http_build_query( $params );
			}
			$remote_params['body'] = $params;
		}

		if ( $app_only_auth ) {
			if ( null == self::$_oauth_consumer_key ) {
				throw new Exception( 'To make an app-only auth API request, the consumer key must be set' );
			}

			// automatically fetch bearer token, if necessary
			if ( null == self::$_oauth_bearer_token ) {
				$this->oauth2_token();
			}

			$authorization = 'Authorization: Bearer ' . self::$_oauth_bearer_token;
		}

		// Codebird::_sign() adds Authorization: to $authorization, but the WP HTTP API needs it separate
		$authorization = trim( str_replace( 'Authorization:', '', $authorization ) );

		if ( $authorization ) {
			$remote_params['headers']['Authorization'] = $authorization;
			$remote_params['headers']['Expect']        = '';
		}

		if ( 'GET' == $httpmethod ) {
			$reply = wp_remote_get( $url, $remote_params );
		}
		else {
			$reply = wp_remote_post( $url, $remote_params );
		}

		if ( isset( $reply ) ) {
			if ( is_wp_error( $reply ) ) {
				throw new Exception( $reply->get_error_message() );
			}
			else {
				$httpstatus = $reply['response']['code'];
				$reply      = $this->_parseApiReply( $method_template, $reply );

				if ( $this->_return_format == CODEBIRD_RETURNFORMAT_OBJECT ) {
					$reply->httpstatus = $httpstatus;
				}
				else {
					$reply['httpstatus'] = $httpstatus;
				}
			}
		}
		else {
			throw new Exception( 'A reply was never generated. Some has gone horribly awry.' );
		}

		return $reply;
	}

	/**
	 * Gets the OAuth bearer token
	 *
	 * Overridden to use the WordPress HTTP API
	 *
	 * @return string The OAuth bearer token
	 */

	public function oauth2_token() {
		if ( null == self::$_oauth_consumer_key ) {
			throw new Exception( 'To obtain a bearer token, the consumer key must be set.' );
		}

		$post_fields = array(
			'grant_type' => 'client_credentials'
		);

		$url = self::$_endpoint_oauth . 'oauth2/token';

		$headers = array(
			'Authorization' => 'Basic ' . base64_encode( self::$_oauth_consumer_key . ':' . self::$_oauth_consumer_secret ),
			'Expect'        => ''
		);

		$remote_params = array(
			'method'      => 'POST',
			'timeout'     => 5,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
			'body'        => $post_fields,
			'cookies'     => array(),
			'sslverify'   => false
		);

		$reply = wp_remote_post( $url, $remote_params );

		$httpstatus = wp_remote_retrieve_response_code( $reply );

		$reply = $this->_parseApiReply( 'oauth2/token', $reply );

		if ( CODEBIRD_RETURNFORMAT_OBJECT == $this->_return_format ) {
			$reply->httpstatus = $httpstatus;

			if ( 200 == $httpstatus ) {
				self::setBearerToken( $reply->access_token );
			}
		}
		else {
			$reply['httpstatus'] = $httpstatus;

			if ( 200 == $httpstatus ) {
				self::setBearerToken( $reply['access_token'] );
			}
		}

		return $reply;
	}

	/**
	 * Parses the API reply to encode it in the set return_format.
	 *
	 * @since 0.1.0
	 * @see   Codebird::_parseApiReply
	 *
	 * @param string $method The method that has been called
	 * @param string $reply  The actual reply, JSON-encoded or URL-encoded
	 *
	 * @return array|object The parsed reply
	 */
	protected function _parseApiReply( $method, $reply ) {
		// split headers and body
		$http_response = $reply;
		$headers       = $http_response['headers'];

		$reply = '';
		if ( isset( $http_response['body'] ) ) {
			$reply = $http_response['body'];
		}

		$need_array = $this->_return_format == CODEBIRD_RETURNFORMAT_ARRAY;
		if ( $reply == '[]' ) {
			return $need_array ? array() : new stdClass;
		}

		$parsed = array();
		if ( $method == 'users/profile_image/:screen_name' ) {
			// this method returns a 302 redirect, we need to extract the URL
			if ( isset( $headers['Location'] ) ) {
				$parsed = array( 'profile_image_url_https' => $headers['Location'] );
			}
		}
		elseif ( ! $parsed = json_decode( $reply, $need_array ) ) {
			if ( $reply ) {
				$reply = explode( '&', $reply );
				foreach ( $reply as $element ) {
					if ( stristr( $element, '=' ) ) {
						list( $key, $value ) = explode( '=', $element );
						$parsed[$key] = $value;
					}
					else {
						$parsed['message'] = $element;
					}
				}
			}
		}
		if ( ! $need_array ) {
			$parsed = ( object ) $parsed;
		}

		return $parsed;
	}

	/**
	 * Detect filenames in upload parameters,
	 * build multipart request from upload params
	 *
	 * @param string $method The API method to call
	 * @param array  $params The parameters to send along
	 *
	 * @return void
	 */
	protected function _buildMultipart( $method, $params ) {
		// well, files will only work in multipart methods
		if ( ! $this->_detectMultipart( $method ) ) {
			return;
		}

		// only check specific parameters
		$possible_files = array(
			// Tweets
			'statuses/update_with_media'              => 'media[]',
			// Accounts
			'account/update_profile_background_image' => 'image',
			'account/update_profile_image'            => 'image',
			'account/update_profile_banner'           => 'banner'
		);
		// method might have files?
		if ( ! in_array( $method, array_keys( $possible_files ) ) ) {
			return;
		}

		$possible_files = explode( ' ', $possible_files[$method] );

		$multipart_border  = '--------------------' . $this->_nonce();
		$multipart_request = '';

		foreach ( $params as $key => $value ) {
			// is it an array?
			if ( is_array( $value ) ) {
				_doing_it_wrong(
					'_buildMultiPart()',
					'Using URL-encoded parameters is not supported for uploading media.',
					'3.7.1'
				);
				continue;
			}
			$multipart_request .=
				'--' . $multipart_border . "\r\n"
				. 'Content-Disposition: form-data; name="' . $key . '"';

			// check for filenames
			if ( in_array( $key, $possible_files ) ) {
				$data = false;

				if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
					$filename = basename( parse_url( $value, PHP_URL_PATH ) );
					if ( $this->is_supported_media_file_extension( $filename ) ) {
						$data = getimagesize( $value );
					}
				} elseif (
					file_exists( $value )
					&& is_readable( $value )
					&& $this->is_supported_media_file_extension( basename( $value ) )
				) {
					$data = getimagesize( $value );
				} 

				// is it a supported image format?
				if ( $data && in_array( $data[2], $this->_supported_media_files ) ) {
					// try to read the file
					$data = file_get_contents( $value );
					if ( strlen( $data ) == 0 ) {
						continue;
					}
					$value = $data;
				} else {
					continue;
				}
			}

			$multipart_request .=
				"\r\n\r\n" . $value . "\r\n";
		}
		$multipart_request .= '--' . $multipart_border . '--';

		return $multipart_request;
	}

	private function is_supported_media_file_extension( $filename ) {
		$extension = pathinfo( $filename, PATHINFO_EXTENSION );
		return in_array( strtolower( $extension ), $this->_supported_media_files_extensions );
	}

}

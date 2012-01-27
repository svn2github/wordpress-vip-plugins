<?php
/**
 * WP_SocialFlow - WordPress SocialFlow Library
 *
 * @author Pete Mall
 */

// Load the OAuth library.
if ( ! class_exists( 'OAuthConsumer' ) )
	require( 'OAuth.php' );

class WP_SocialFlow {
	/* Set up the API root URL. */
	public $host = 'https://api.socialflow.com/';

	const request_token_url = 'https://www.socialflow.com/oauth/request_token';
	const access_token_url = 'https://www.socialflow.com/oauth/access_token';
	const authorize_url = 'https://www.socialflow.com/oauth/authorize';

	function __construct( $consumer_key, $consumer_secret, $oauth_token = NULL, $oauth_token_secret = NULL ) {
		$this->signature_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer = new OAuthConsumer( $consumer_key, $consumer_secret );

		if ( !empty( $oauth_token ) && !empty( $oauth_token_secret ) )
			$this->token = new OAuthConsumer( $oauth_token, $oauth_token_secret );
		else
			$this->token = NULL;
	}

	function get_request_token( $oauth_callback = NULL ) {
		$parameters = array();
		if ( !empty( $oauth_callback ) )
			$parameters['oauth_callback'] = $oauth_callback;

		$request = $this->oauth_request( self::request_token_url, 'GET', $parameters );

		if ( 200 != wp_remote_retrieve_response_code( $request ) )
			return false;

		$token = OAuthUtil::parse_parameters( wp_remote_retrieve_body( $request ) );
		$this->token = new OAuthConsumer( $token['oauth_token'], $token['oauth_token_secret'] );
		return $token;
	}

	/**
	 * Format and sign an OAuth / API request
	 */
	private function oauth_request( $url, $method, $parameters ) {
		$request = OAuthRequest::from_consumer_and_token( $this->consumer, $this->token, $method, $url, $parameters );
		$request->sign_request( $this->signature_method, $this->consumer, $this->token );

		$args = array( 'sslverify' => false, 'headers' => array( 'Authorization' => 'Basic ' . base64_encode( 'sf_partner' . ':' . 'our partners' ) ) );
		$parameters = array_merge( $request->get_parameters(), $args );

		if ( 'GET' == $method )
			return wp_remote_get( $request->to_url(), $parameters );
		else
			return wp_remote_post( $request->to_url(), $parameters );
	  }

	function get_authorize_url( $token ) {
		if ( is_array( $token ) )
			$token = $token['oauth_token'];

		return self::authorize_url . "?oauth_token={$token}";
	}

	/**
	 * Exchange request token and secret for an access token and
	 * secret, to sign API calls.
	 *
	 * @returns array( 'oauth_token' => 'the-access-token',
	 *                 'oauth_token_secret' => 'the-access-secret' )
	 */
	function get_access_token( $oauth_verifier = '' ) {
		$parameters = array();
		if ( !empty( $oauth_verifier ) )
			$parameters['oauth_verifier'] = $oauth_verifier;

		$request = $this->oauth_request( self::access_token_url, 'GET', $parameters );
		$token = OAuthUtil::parse_parameters( wp_remote_retrieve_body( $request ) );

		$this->token = new OAuthConsumer( $token['oauth_token'], $token['oauth_token_secret'] );
		return $token;
	}

	public function add_message( $message = '', $service_user_id, $account_type = '', $publish_option = '', $shorten_links = 0, $args = array() ) {
		if ( ! ( $message && $service_user_id && $account_type ) )
			return false;

		$parameters = array(
			'message'         => stripslashes( $message ),
			'service_user_id' => $service_user_id,
			'account_type'    => $account_type,
			'publish_option'  => $publish_option,
			'shorten_links'   => $shorten_links
		);
		$paramters = array_merge( $parameters, $args );

		$response = $this->post( 'message/add', $parameters );
		if ( 200 == wp_remote_retrieve_response_code( $response ) )
			return true;

		return false;
	}

	public function add_multiple( $message = '', $service_user_ids, $account_types = '', $publish_option = 'publish now', $shorten_links = 0, $args = array() ) {
		if ( ! ( $message && $service_user_ids && $account_types ) )
			return false;

		$parameters = array(
			'message'          => stripslashes( urldecode( $message ) ),
			'service_user_ids' => $service_user_ids,
			'account_types'    => $account_types,
			'publish_option'   => $publish_option,
			'shorten_links'    => $shorten_links
		);
		$paramters = array_merge( $parameters, $args );

		$response = $this->post( 'message/add_multiple', $parameters );
		if ( 200 == wp_remote_retrieve_response_code( $response ) )
			return true;

		return false;
	}

	public function get_account_list() {
		$response = $this->get( 'account/list' );

		if ( 200 != wp_remote_retrieve_response_code( $response ) )
			return false;

		$response = json_decode( wp_remote_retrieve_body( $response ), true );
		
		$accounts = array();
		foreach ( $response['data']['client_services'] as $account )
			$accounts[ $account['client_service_id'] ] = $account;

		return $accounts;
	}

	public function shorten_links( $message, $service_user_id, $account_type ) {
		if ( !$message || !$service_user_id || !$account_type )
			return false;

		$response = $this->get( 'link/shorten_message', array( 'service_user_id' => $service_user_id, 'account_type' => $account_type, 'message' => stripslashes( $message ) ) );

		if ( 200 == wp_remote_retrieve_response_code( $response ) )
			return json_decode( wp_remote_retrieve_body( $response ) )->new_message;
	}

	public function get_account_links( $consumer_key = '' ) {
		if ( !$consumer_key )
			return false;

		$response = wp_remote_get( "{$this->host}/account/links/?consumer_key={$consumer_key}", array( 'headers' => array( 'Authorization' => 'Basic ' . base64_encode( 'sf_partner' . ':' . 'our partners' ) ) ) );

		if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
			$response = json_decode( wp_remote_retrieve_body( $response ) );
			if ( 200 == $response->status )
				return $response->data;
		}

		return false;
	}

	/**
 	 * GET wrapper for oAuthRequest.
 	 */
	public function get( $url, $parameters = array() ) {
		$url = $this->host . $url;
		return $this->oauth_request( $url, 'GET', $parameters );
	}

	/**
 	 * POST wrapper for oAuthRequest.
 	 */
	public function post( $url, $parameters = array() ) {
		$url = $this->host . $url;
		return $this->oauth_request( $url, 'POST', $parameters );
	}
}
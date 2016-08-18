<?php

namespace Webdam;

/**
 * WebDAM API Integration
 *
 * http://webdam.com/DAM-software/API/
 */
class API {

	/**
	 * @var Used to store an internal reference for the class
	 */
	private static $_instance;

	public $base_url = 'https://apiv2.webdamdb.com/';

	protected $grant_type = 'authorization_code';

	protected $has_settings = false;

	protected $client_id = null;
	protected $client_secret = null;

	protected $authorization_redirect_uri = '';
	protected $authorization_code = null;

	protected $access_token_type = null;
	protected $access_token = null;
	protected $refresh_token = null;
	protected $access_token_expires_in = 0;
	protected $access_expires = 0;

	/**
	 * Fetch THE persistent instance of this class
	 *
	 * This one object lives forever. Created once,
	 * it's soul eternal—and persistent.
	 *
	 * Bathed in the blood-fire—it's self-healing,
	 * self-aware, and always has a valid access token.
	 *
	 * @param bool $refresh_cache
	 *
	 * @return API object instance
	 */
	static function get_instance( $refresh_cache = false ) {

		if ( empty( static::$_instance ) || $refresh_cache ){

			// Attempt to fetch a cache of the class instance
			$instance = get_transient( 'Webdam\API' );

			if ( false === $instance || $refresh_cache ) {

				// No cache available—let's create one
				$instance = new self();

				// Only cache the instance when it contains valid settings
				if ( $instance->has_settings ) {

					// Cache the API instance
					set_transient( 'Webdam\API', $instance );
				}
			} else {

				// Cache is good
				// Call anything which MUST execute on each request, e.g. WordPress hooks
				// This is also called when __construct() runs during an initial instantiation
				$instance->init();
			}

			self::$_instance = $instance;
		}

		// Return the single/cached instance of the class
		return self::$_instance;
	}

	/**
	 * Object initialization
	 *
	 * This construct only occurs once when the object is first created.
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function __construct() {

		if ( $settings = webdam_get_settings() ) {

			// The settings page may display a link for the user to click
			// and be taken to WebDAM's website to say "yes, this website
			// is allowed to access my account", i.e. the user went through
			// the 'authentication' process to 'authenticate' our application.
			//
			// After authenticating with WebDM the user is redirected
			// back to our settings page where they initially began the
			// authentication process.
			//
			// Create an internal reference to the settings page URL
			// aka known as the authentication redirect URL.
			$this->authorization_redirect_uri = \webdam_get_admin_settings_page_url();

			// Only proceed if we have credentials to send
			if ( ! empty( $settings['api_client_id'] ) && ! empty( $settings['api_client_secret'] ) ) {

				// Store internal references to the webdam settings
				$this->client_id = $settings['api_client_id'];
				$this->client_secret = $settings['api_client_secret'];
			}

			// Only flag our instance as having settings if all 3 of the following
			// needed items is present and not empty.
			if ( ! empty( $this->authorization_redirect_uri ) && ! empty( $this->client_id ) && ! empty( $this->client_secret ) ) {
				$this->has_settings = true;
				$this->init();
			}
		}
	}

	/**
	 * Object initializations
	 *
	 * This function runs on every admin page request
	 */
	public function init() {

		// Hook into WordPress—this must occur on every page load,
		// unlike this object, hooks are not persistent and must be
		// specified on every run of PHP.
		$this->setup_hooks();
	}

	/**
	 *	Setup the WordPress hooks
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function setup_hooks() {

		// Capture the auth code when it's available
		// This occurs after someone has been directed to
		// WebDAM to authorize this app's usage and they're
		// returned to our site with the auth code in the url
		add_action( 'admin_init', array( $this, 'capture_authorization_code' ), 0, 10 );

		// Ensure we always have valid authentication
		add_action( 'admin_init', array( $this, 'ensure_were_authenticated' ), 0, 11 );
	}

	/**
	 * Get the WebDAM API Authorization URL
	 *
	 * This is the URL we send users to to authenticate their account
	 * for use with our API.
	 *
	 * E.g. https://apiv2.webdamdb.com/oauth2/authorize?response_type=code&client_id=XXXX&redirect_uri=XXXX&state=STATE
	 *
	 * @param null
	 *
	 * @return string The authorization URL.
	 */
	public function get_authorization_url() {

		$query_args = array(
			'response_type' => 'code',
			'client_id' => $this->client_id,
			'redirect_uri' => $this->authorization_redirect_uri,
			'state' => 'STATE',
		);

		$authorization_url = add_query_arg(
			$query_args,
			esc_url_raw( $this->base_url . 'oauth2/authorize' )
		);

		return $authorization_url;
	}

	/**
	 * Capture the WebDAM API authorization_code from GET
	 *
	 * After the user has been taken to WebDAM to allow access
	 * they're redirected back to the settings page with a new
	 * GET 'code' query string variable in the URL. This 'code'
	 * can then be used to obtain an access_token.
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function capture_authorization_code() {

		if ( ! empty( $_GET['page'] ) ) {
			if ( 'webdam-settings' === $_GET['page'] ) {
				if ( ! empty( $_GET['code'] ) ) {

					// We have an auth_code
					$this->authorization_code = sanitize_text_field( $_GET['code'] );
				}
			}
		}
	}

	/**
	 * Helper to determine if we're authenticated or not
	 *
	 * @param null
	 *
	 * @return bool Return true|false if we're authenticated
	 */
	public function is_authenticated() {

		// Do we have a token?
		if ( ! empty( $this->access_token ) ) {

			// Is that token valid?
			if ( ! $this->is_access_token_expired() ) {

				// Yep, it is—good to go
				return true;
			}
		}

		return false;
	}

	/**
	 * Ensure we always have a valid access_token
	 *
	 * Fetch an access token when we don't yet have have one,
	 * or if our current token is expired; refresh it.
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function ensure_were_authenticated() {

		if ( empty( $this->access_token ) ) {

			// Only send an authentication request if we have an authorization code
			if ( ! empty( $this->authorization_code ) ) {

				// Do the authentication/fetch an access token
				$token_request = $this->do_authentication( $this->grant_type );
			}

		} else {

			// Do we need to refresh our token?
			if ( $this->is_access_token_expired() ) {

				// Refresh token
				$this->do_authentication( 'refresh_token' );
			}

			// We're authenticated — nothing else needed here.
			// All api calls will work
		}
	}

	/**
	 * Fetch an access token from the WebDAM API
	 *
	 * An initial access token is fetched using authentication
	 * (the user is directed to authorize at WebDAM, and redirected back)
	 *
	 * That initial token, and all tokens after it—all expire in
	 * one hour. When this plugin detects that a token is about
	 * to expire, it does a refresh_token authentication to obtain
	 * a new access token.
	 *
	 * The initial authorization_code and refresh_token always remain
	 * the same. The token is what expires regularly.
	 *
	 * @param string $grant_type The /token grant_type. Acceptable values are 'authorization_code' or 'refresh_token'
	 *
	 * @return null|false False on failure
	 */
	public function do_authentication( $grant_type = '' ) {

		$data = array();

		// Build the data we'll send in the request body
		// this data will vary depending on the grant_type
		switch ( $grant_type ) {
			case 'authorization_code' :
				$data = array(
					'grant_type'    => $grant_type,
					'client_id'     => $this->client_id,
					'client_secret' => $this->client_secret,
					'code'          => $this->authorization_code,
					'redirect_uri'  => $this->authorization_redirect_uri,
				);
			break;
			case 'refresh_token' :
				$data = array(
					'grant_type'    => $grant_type,
					'client_id'     => $this->client_id,
					'client_secret' => $this->client_secret,
					'refresh_token' => $this->refresh_token,
				);
			break;
		}

		// Fetch a token
		$token_data = $this->post( 'oauth2/token', $data, false );

		if ( ! $token_data['success'] ) {
			// Request failed
			return $token_data;
		}

		// Only proceed if we did infact receive an access_token
		if ( ! empty( $token_data['data']->access_token ) ) {

			// Store the token details internally
			// The token comes lowercase, but we'll need the
			// first uppercased, e.g. 'bearer' to 'Bearer'
			if ( ! empty( $token_data['data']->token_type ) ) {
				$this->access_token_type = ucfirst( $token_data['data']->token_type );
			}

			if ( ! empty( $token_data['data']->access_token ) ) {
				$this->access_token = $token_data['data']->access_token;
			}

			// Only the authorization_code and password grant types
			// supply us a refresh token
			if ( ! empty( $token_data['data']->refresh_token ) ) {
				$this->refresh_token = $token_data['data']->refresh_token;
			}

			if ( ! empty( $token_data['data']->expires_in ) ) {
				$this->access_token_expires_in = $token_data['data']->expires_in;
			}

			// Tokens expire in 3600s (1 hour)
			// let's set our internal expiration to 55min
			// so that we buy ourselves a 5min window
			// for someone to trigger this code & refresh the token
			$this->access_expires = strtotime( '+' . ( $this->access_token_expires_in - 300 ) . ' seconds' );

			// Ensure were not giving out a stale instance
			// and/or cache. Refresh it once we have new tokens.
			$this::$_instance = $this;

			// Cache the API instance
			set_transient( 'Webdam\API', $this );

		} else {
			// Didn't get back what we expected
			return $token_data;
		}
	}

	/**
	 * Is the current access token still valid?
	 *
	 * @param null
	 *
	 * @return bool True if the token is valid, false if it is not.
	 */
	public function is_access_token_expired() {
		if ( $this->access_expires < time() ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get the current access token
	 *
	 * @param null
	 *
	 * @return string The access token
	 */
	public function get_current_access_token() {
		return $this->access_token;
	}

	/**
	 * Get the current refresh token
	 *
	 * @param null
	 *
	 * @return string The refresh token
	 */
	public function get_current_refresh_token() {
		return $this->refresh_token;
	}

	/**
	 * Make a generic & configurable POST request to WebDAM
	 *
	 * @param string $endpoint
	 * @param array  $data
	 * @param bool   $send_authorization
	 *
	 * @return array An array containing a bool success, and an object response or a string error message on failure.
	 */
	public function post( $endpoint = '', $data = array(), $send_authorization = true ) {

		// POST requests to the WebDAM API may or may not need to be authenticated
		// we only want to ensure we're authenticated (possibly fetch an access token)
		// if the request requires authentication. Some requests, like for a token
		// inherently are unable to be authenticated——infinite loops ensue, turmoil,
		// fire, brimstone, etc.
		if ( $send_authorization ) {
			$this->ensure_were_authenticated();
		}

		$args = array(
			'body' => $data,
		);

		if ( $send_authorization ) {
			$args['headers'] = array(
				'Authorization' => $this->access_token_type . ' ' . $this->access_token,
			);
		}

		$url = $this->base_url . $endpoint;

		// Allow the post URL to be filtered
		$url = apply_filters( 'webdam-pre-post-url', $url );

		// Allow the post args to be filtered
		$args = apply_filters( 'webdam-pre-post-args', $args );

		// POST the request to the given url
		$response = wp_safe_remote_post( $url, $args );

		// Broadcast the raw post response
		do_action( 'webdam-post-response', $response );

		// Handle the response and return
		if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {

			$response['body'] = json_decode( $response['body'] );

			return array( 'success' => true, 'data' => $response['body'] ) ;

		} else {
			return array( 'success' => false, 'msg' => $response['body'] ) ;
		}
	}

	/**
	 * Make a GET request to WebDAM
	 *
	 * All GET requests to webdam require authentication.
	 *
	 * @param string $endpoint
	 *
	 * @return bool
	 */
	public function get( $endpoint = '' ) {

		// All GET requests to webdam require authentication.
		$this->ensure_were_authenticated();

		$url = $this->base_url . $endpoint;

		// Allow the GET request URL to be filtered
		$url = apply_filters( 'webdam-pre-get-url', $url );

		$args = array(
			'headers' => array(
				'Authorization' => $this->access_token_type . ' ' . $this->access_token,
			),
		);

		// Allow the GET request args to be filtered
		$args = apply_filters( 'webdam-pre-get-args', $args );

		// GET a response for the given url
		$response = wp_safe_remote_get( $url, $args );

		// Broadcast the raw get response
		do_action( 'webdam-get-response', $response );

		$response['body'] = json_decode( $response['body'] );

		// Handle the response and return
		if ( ! is_wp_error( $response ) && 200 === $response['response']['code'] ) {

			return array( 'success' => true, 'data' => $response['body'] ) ;

		} else {
			return array( 'success' => false, 'msg' => $response['body']->error_description ) ;
		}
	}

	/**
	 * API ENDPOINTS
	 * @see https://www.damsuccess.com/hc/en-us/articles/202134055-REST-API
	 */

	/**
	 * GET Image Metadata
	 *
	 * Fetch XMP metadata for a given image ID
	 *
	 * @param int|array $asset_ids The asset ID(s) you're fetching data for
	 * e.g. $asset_ids = 23945510;
	 * $asset_ids = array( 23945510, 23945511, ... );
	 *
	 * @return Presto\Response $response Response object
	 */
	public function get_asset_metadata( $asset_ids = array() ) {

		if ( empty( $asset_ids ) ) {
			return false;
		}

		// Convert non-array asset id to an array so our code below
		// can confidently deal with an array
		$asset_ids = (array) $asset_ids;

		// Ensure we're dealing with integer ID's
		$asset_ids = array_map( 'intval', $asset_ids );

		// Convert our array of ID's into a comma-delimited string
		// this allows us to fetch metadata for up to 50 assets
		$asset_ids = implode( ',', $asset_ids );

		$endpoint = "assets/$asset_ids/metadatas/xmp";

		// Fetch a token
		$response = $this->get( $endpoint );

		if ( $response['success'] ) {
			return $response['data'];
		}

		return false;
	}
}

// Update the api cache when new settings have been saved
add_action( 'webdam-saved-new-settings', function() {

	// Fetch a new instance of the class
	// passing 'true' forces a cache refresh
	API::get_instance( true );

}, 10, 0 );

// The API is only used in the admin
if ( is_admin() ) {
	API::get_instance();
}

// EOF
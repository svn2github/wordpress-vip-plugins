<?php


/**
 * Class TinypassConfig
 * @method TinypassConfig|mixed appId() appId( $appId = null )
 * @method TinypassConfig|mixed APIToken() APIToken( $APIToken = null )
 * @method TinypassConfig|mixed environment() environment( $environment = null )
 * @method TinypassConfig|mixed businessModel() businessModel( $businessModel = null )
 * @method TinypassConfig|mixed privateKey() privateKey( $privateKey = null )
 * @method TinypassConfig|mixed userProvider() userProvider( $userProvider = null )
 * @method TinypassConfig|mixed features() features( $features = null )
 * @method TinypassConfig|mixed paywallId() paywallId( $paywallId = null )
 * @method TinypassConfig|mixed paywallOfferId() paywallOfferId( $paywallOfferId = null )
 * @method TinypassConfig|mixed paywallTemplateId() paywallTemplateId( $paywallTemplateId = null )
 * @method TinypassConfig|mixed offerId() offerId( $offerId = null )
 * @method TinypassConfig|mixed enablePayPerPost() enablePayPerPost( $enablePayPerPost = null )
 * @method TinypassConfig|mixed firstClickMode() firstClickMode( $firstClickMode = null )
 * @method TinypassConfig|mixed firstClickRef() firstClickRef( $firstClickRef = null )
 * @method TinypassConfig|mixed userId() userId( $userId = null )
 * @method TinypassConfig|mixed userEmail() userEmail( $userEmail = null )
 * @method TinypassConfig|mixed userCreated() userCreated( $userCreated = null )
 * @method TinypassConfig|mixed userFirstName() userFirstName( $userFirstName = null )
 * @method TinypassConfig|mixed userLastName() userLastName( $userLastName = null )
 * @method TinypassConfig|mixed jsURL() jsURL( $jsURL = null )
 * @method TinypassConfig|mixed baseURL() baseURL( $baseURL = null )
 * @method TinypassConfig|mixed displayMode() displayMode( $displayMode = null )
 */
class TinypassConfig extends TinypassBuildable {

	const BASE_URL = 'https://api.tinypass.com/api/v3';
	const SANDBOX_BASE_URL = 'https://sandbox.tinypass.com/api/v3';
	const JS_URL = '//cdn.tinypass.com/api/tinypass.min.js';
	const SANDBOX_JS_URL = '//sandbox.tinypass.com/api/tinypass.min.js';

	const USER_PROVIDER_TINYPASS_ACCOUNTS = 'tinypass_accounts';
	const USER_PROVIDER_USER_REF = 'publisher_user_ref';
	const USER_PROVIDER_JANRAIN = 'janrain';

	/**
	 * Business models
	 */
	const BUSINESS_MODEL_METERED = 'metered';
	const BUSINESS_MODEL_SUBSCRIPTION = 'subscription';

	/**
	 * Deployment modes
	 */
	const MODE_PRODUCTION = 'prod';
	const MODE_SANDBOX = 'sandbox';
	const MODE_CUSTOM = 'custom';

	/**
	 * Possible first click free options
	 * [FIRST CLICK FREE OPTIONS]
	 */
	const FIRST_CLICK_OPTION_NONE = 'no';
	const FIRST_CLICK_OPTION_ALL = 'al';
	const FIRST_CLICK_OPTION_INCLUDE = 'in';
	const FIRST_CLICK_OPTION_EXCLUDE = 'ex';

	/**
	 * [/FIRST CLICK FREE OPTIONS]
	 */

	// States of metered paywall
	const PAYWALL_STATE_METER_ACTIVE = 'ok';
	const PAYWALL_STATE_ACCESS_GRANTED = 'ap';
	const PAYWALL_STATE_EXPIRED = 'ex';

	const DISPLAY_MODE_INLINE = 'inline';
	const DISPLAY_MODE_MODAL = 'modal';

	const FEATURE_NAME_MY_ACCOUNT = 'my_account';
	const FEATURE_NAME_ALGORITHM = 'content_algorithm';
	const FEATURE_NAME_JANRAIN_APP_NAME = 'janrain_app_name';
	const FEATURE_NAME_JANRAIN_APP_ID = 'janrain_app_id';
	const FEATURE_NAME_JANRAIN_CLIENT_ID = 'janrain_client_id';

	// API response error code for already existing resource
	const ERROR_RESOURCE_ALREADY_EXISTS = 820;

	// Webhook error for when no processor was found
	const ERROR_WEBHOOK_NO_PROCESSOR_FOUND = 4044;

	// Prefix for resource ids for 'pay-per-post' posts
	const PAY_PER_POST_RESOURCE_PREFIX = 'post-';

	// Prefix for metered paywall resource
	const PAYWALL_RESOURCE_PREFIX = 'PW_';

	protected $appId;
	protected $APIToken;
	protected $environment;

	protected $businessModel;

	protected $privateKey;
	protected $userProvider;
	protected $features;

	protected $paywallId;
	protected $paywallOfferId;
	protected $paywallTemplateId;

	protected $offerId;

	protected $enablePayPerPost;
	protected $firstClickMode;
	protected $firstClickRef;

	protected $userId;
	protected $userEmail;
	protected $userCreated;
	protected $userFirstName;
	protected $userLastName;

	protected $jsURL;

	protected $baseURL;
	protected $displayMode;

	/**
	 * @var TinypassVX
	 */
	protected $api;

	protected $app;

	/**
	 * Use already provided credentials to initialize API caller
	 * @return $this
	 */
	public function initAPI() {
		$this->api = new TinypassVX( $this->baseURL(), $this->APIToken() );

		return $this;
	}

	/**
	 * Method performs request to API and returns response, throws an exception in case of failure
	 *
	 * @param string $path API method path
	 * @param array $params Array of parameters
	 * @param bool $noTimeout Do request without timeout?
	 *
	 * @return stdClass
	 * @throws Exception
	 */
	protected function callApi( $path, $params, $noTimeout = true ) {
		if ( ! $this->api ) {
			throw new Exception( __( 'API is not initialized', 'tinypass' ) );
		}

		return $this->api->callAPI( $path, $params, $noTimeout );
	}

	/**
	 * Do additional checks for configuration completion
	 *
	 * @param bool $isConfigured Represents the result of the other configuration checks
	 *
	 * @return bool
	 */
	public function isConfigured() {
		if ( $this->businessModel() == self::BUSINESS_MODEL_METERED ) {
			// Metered paywall business model requires offer id, paywall id and template id
			return ( $this->paywallOfferId() && $this->paywallTemplateId() && $this->paywallId() );
		} elseif ( $this->businessModel() == self::BUSINESS_MODEL_SUBSCRIPTION ) {
			// Hard / keyed paywall business model requires offer id
			return (bool) $this->offerId();
		}
	}

	/**
	 * Get additional data for application
	 *
	 * @return stdClass
	 * @throws Exception
	 */
	public function getApp() {
		$res = $this->callAPI( '/publisher/app/get', array(
			'aid' => $this->appId()
		) );
		$this->checkApiResponse( $res );

		$this->app = $res->app;

		return $this->app;
	}

	public function getAppFeatures() {
		$features = new stdClass();
		$res      = $this->callAPI( '/publisher/algorithm/content/config/get', array(
			'aid' => $this->appId(),
		) );

		$algorithmEnabled = isset( $res->contentLockingConfig->content_locking_config_id ) && $res->contentLockingConfig->content_locking_config_id;

		$featureAlgorithm = new stdClass();

		$featureAlgorithm->enabled = $algorithmEnabled;

		$featureMyAccount = new stdClass();

		$featureMyAccount->enabled = in_array( $this->userProvider(), array(
			self::USER_PROVIDER_USER_REF,
		) );

		$features->{ self::FEATURE_NAME_ALGORITHM }  = $featureAlgorithm;
		$features->{ self::FEATURE_NAME_MY_ACCOUNT } = $featureMyAccount;

		if ( $this->userProvider() == self::USER_PROVIDER_JANRAIN ){
			$res = $this->callApi( '/publisher/provider/user/janrain/get', array(
				'aid' => $this->appId()
			) );
			$this->checkApiResponse($res);
			$features->{ self::FEATURE_NAME_JANRAIN_APP_ID } = $res->userProviderConfiguration->app_id;
			$features->{ self::FEATURE_NAME_JANRAIN_APP_NAME } = $res->userProviderConfiguration->app_name;
			$features->{ self::FEATURE_NAME_JANRAIN_CLIENT_ID } = $res->userProviderConfiguration->client_id;
		}

		$this->features( $features );

		return $features;
	}


	public function setWebhookEndpoint( $url, $setEnabled = true ) {
		$res = $this->callAPI( '/publisher/webhook/settings/update', array(
			'aid'     => $this->appId(),
			'url'     => $url,
			'enabled' => $setEnabled
		) );
		$this->checkApiResponse( $res );
	}

	/**
	 * Get additional data for metered paywall
	 *
	 * @return stdClass
	 * @throws Exception
	 */
	public function getPaywallData() {
		$res = $this->callAPI( '/anon/meter/load', array(
			'aid'        => $this->appId(),
			'paywall_id' => $this->paywallId()
		) );

		$this->checkApiResponse( $res );

		return $res;
	}

	/**
	 * Get api endpoint for dedicated environment
	 *
	 * @param $rawUrl
	 *
	 * @return bool|string
	 */
	public static function parseCustomUrl( $rawUrl ) {
		$parse = parse_url( $rawUrl );

		if ( false === $parse || ! isset( $parse['host'] ) ) {
			return false;
		}

		return ( isset( $parse['scheme'] ) ? "{$parse['scheme']}://" : '' ) . "{$parse['host']}" . ( isset( $parse['port'] ) ? ":{$parse['port']}" : '' ) . '/api/v3';
	}

	/**
	 * Get javascript url for dedicated environment
	 *
	 * @param $rawUrl
	 *
	 * @return string
	 */
	public static function parseCustomJsUrl( $rawUrl ) {
		$parse = parse_url( $rawUrl );

		if ( false === $parse ) {
			return self::JS_URL;
		}

		return ( isset( $parse['scheme'] ) ? "{$parse['scheme']}://" : '' ) . "{$parse['host']}" . ( isset( $parse['port'] ) ? ":{$parse['port']}" : '' ) . '/api/tinypass.min.js';
	}

	/**
	 * Check if provided API response has errors, throw exception if it has
	 *
	 * @param $data
	 *
	 * @throws Exception
	 */
	protected function checkApiResponse( $data ) {
		// It's considered that response has errors if "code" attribute is not = 0
		if ( isset( $data->code ) && ( $data->code != 0 ) ) {
			$message = $data->message;
			if ( isset( $data->validation_errors ) ) {
				$message = __( 'Validation errors', 'tinypass' ) . ": \n";
				foreach ( $data->validation_errors as $error ) {
					$message .= $error . " \n";
				}
			}
			throw new Exception( $message, $data->code );
		}
	}

	/**
	 * Get base url for images
	 * @return string
	 */
	public function baseImgURL() {

		$parse = parse_url( $this->baseURL() );

		if ( false === $parse ) {
			return '';
		}

		return ( isset( $parse['scheme'] ) ? "{$parse['scheme']}://" : '' ) . "{$parse['host']}" . ( isset( $parse['port'] ) ? ":{$parse['port']}" : '' );
	}

	/**
	 * Is my account available for publisher's application
	 *
	 * @return bool
	 */
	public function myAccountAvailable() {
		return (bool) isset( $this->features()->{ self::FEATURE_NAME_MY_ACCOUNT } ) ? $this->features()->{ self::FEATURE_NAME_MY_ACCOUNT }->enabled : false;
	}

	/**
	 * Is algorithmic keying available for publisher's application
	 *
	 * @return bool
	 */
	public function algorithmicKeyAvailable() {
		return (bool) isset( $this->features()->{ self::FEATURE_NAME_ALGORITHM } ) ? $this->features()->{ self::FEATURE_NAME_ALGORITHM }->enabled : false;
	}

	/**
	 * Can publisher use their' own users with their own user management system
	 *
	 * @return bool
	 */
	public function nativeUsersAvailable() {
		return in_array( $this->userProvider(), array(
			self::USER_PROVIDER_USER_REF
		) );
	}
}
<?php

/**
 * Class WPTinypass
 */
class WPTinypass {
	/**
	 * @var TinypassInternal object used for communication with VX API. If plugin is not configured - is null.
	 */
	public static $tinypass;

	/**
	 * Prefix for option names stored in WP database
	 */
	const OPTION_NAME_PREFIX = 'tinypass_';

	/**
	 * All option names. These names MUST also be defined as properties of this class
	 * [OPTION_NAMES]
	 */
	const OPTION_NAME_PLUGIN_VERSION = 'version';
	protected static $version;
	const OPTION_NAME_MODE = 'mode';
	protected static $mode;
	const OPTION_NAME_DEDICATED_ENVIRONMENT = 'dedicated';
	protected static $dedicated;

	const OPTION_NAME_BUSINESS_MODEL = 'business_model';
	protected static $business_model;
	const OPTION_NAME_ENABLE_PPP = 'enable_ppp';
	protected static $enable_ppp;

	const OPTION_NAME_USER_PROVIDER = 'user_provider';
	protected static $user_provider;
	const OPTION_NAME_FEATURES = 'features';
	protected static $features;
	const OPTION_NAME_PAYWALL_ID = 'paywall_id';
	protected static $paywall_id;
	const OPTION_NAME_APP_ID = 'app_id';
	protected static $app_id;
	const OPTION_NAME_API_TOKEN = 'api_token';
	protected static $api_token;
	const OPTION_NAME_PRIVATE_KEY = 'private_key';
	protected static $private_key;
	const OPTION_NAME_OFFER_ID = 'offer_id';
	protected static $offer_id;
	const OPTION_NAME_OFFER_SET = 'offer_set';
	protected static $offer_set;
	const OPTION_NAME_PAYWALL_OFFER_ID = 'paywall_offer_id';
	protected static $paywall_offer_id;
	const OPTION_NAME_PAYWALL_TEMPLATE_ID = 'paywall_template_id';
	protected static $paywall_template_id;
	const OPTION_NAME_RESOURCES = 'resources';
	protected static $resources;
	const OPTION_NAME_DISABLED_FOR_PRIVILEGED = 'disabled_for_privileged';
	protected static $disabled_for_privileged;
	const OPTION_NAME_METER_HOME_PAGE = 'track_home_page';
	protected static $track_home_page;
	const OPTION_NAME_DEFAULT_ACCESS_SETTINGS = 'default_access_settings';
	protected static $default_access_settings;
	const OPTION_NAME_TRUNCATION_MODE = 'truncation_mode';
	protected static $truncation_mode;
	const OPTION_NAME_PARAGRAPHS_COUNT = 'paragraphs_count';
	protected static $paragraphs_count;
	const OPTION_NAME_FIRST_CLICK_MODE = 'first_click_mode';
	protected static $first_click_mode;
	const OPTION_NAME_FIRST_CLICK_REFERRERS = 'first_click_ref';
	protected static $first_click_ref;
	const  OPTION_NAME_DEBUG = 'debug';
	protected static $debug;
	const  OPTION_NAME_ENABLE_PREMIUM_TAG = 'enable_premium_tag';
	protected static $enable_premium_tag;
	const  OPTION_NAME_DISPLAY_MODE = 'display_mode';
	protected static $display_mode;
	const OPTION_NAME_NULL = 'null';
	protected static $null;
	/**
	 * [/OPTION_NAMES]
	 */

	const META_NAME = 'tinypass_meta';

	/**
	 * Possible ways to hide the content on no access
	 * [TRUNCATION MODES]
	 */
	const TRUNCATION_MODE_ALL = 'all'; // Hide everything
	const TRUNCATION_MODE_PARAGRAPHED = 'par'; // Show only specified number of paragraphs
	const TRUNCATION_MODE_TPMORE = 'tpmore'; // Show until a custom tpmore tag
	const TRUNCATION_MODE_FILTER = 'filter'; // Publisher will provide their own filter for truncation
	/**
	 * [/TRUNCATION MODES]
	 */

	/**
	 * [ACTIONS AND FILTERS]
	 */
	// Filter for premium tag for the title
	const WP_FILTER_PREMIUM_TAG = 'tinypass_premium_tag';
	// Filter which allows custom truncation of content on no access
	const WP_FILTER_NO_ACCESS = 'tinypass_no_access';
	// Filter which clears any content on any access
	const WP_FILTER_TINYPASS = 'tinypass';
	// Filter which allows modification of array of data used by algorithmic keying
	const WP_FILTER_META = 'tinypass_meta';
	// Shortcode for my accout page
	const WP_SHORTCODE_MY_ACCOUNT = 'tp_my_account';
	/**
	 * [/ACTIONS AND FILTERS]
	 */

	// Prefix for resource ids for "pay-per-post" posts
	const PAY_PER_POST_RESOURCE_PREFIX = 'post-';

	const MORE_BUTTON = 'tinypass';

	const PLUGIN_NAME = 'tinypass';

	const VARY_CACHE_COOKIE_NAME = 'tp_cache';

	protected static $_debugData = array();

	/**
	 * Initialization of Wordpress hooks / filters / shortcodes
	 */
	public function init() {
		if ( $this->isConfigured( false ) ) { // Check if basic configuration is ok to init tinypass object
			// If plugin is successfully configured - initiate API caller object and be ready to be used
			$this->initTinypass();
		}
		if ( is_admin() ) {
			// Init admin pages
			$this->initBackend();
		} elseif ( $this->isConfigured() ) {
			if ( self::$truncation_mode != self::TRUNCATION_MODE_FILTER ) {
				// Add filter for no access only if it was not set to be implemented by publisher
				add_filter( self::WP_FILTER_NO_ACCESS, array(
					$this,
					'noAccess'
				), 10, 2 );
			}
			// Init functionality for the frontend
			$this->initFrontend();

			if ( self::canDebug() ) {
				// Start the debugger
				$this->initDebugger();
			}
		}
	}

	/**
	 * Initialization of admin pages
	 */
	private function initBackend() {
		new WPTinypassAdmin;
	}


	/**
	 * Initialization of filters / hooks for frontend
	 */
	private function initFrontend() {
		new WPTinypassFrontend;
	}

	/**
	 * Start the debugger
	 */
	private function initDebugger() {
		self::debugData( WPTinypassDebugger::FIELD_BUSINESS_MODEL, self::$business_model );
		new WPTinypassDebugger;
	}

	/**
	 * Can the debugger be displayed?
	 * @return bool
	 */
	public static function canDebug() {
		return ( self::$debug && current_user_can( 'manage_options' ) );
	}

	/**
	 * Update option in database as well as in object's property
	 *
	 * @param string $option Option name, one of the OPTION_NAME_* constants
	 * @param mixed $data Option's value
	 */
	protected static function setOption( $option, $data ) {
		$WPOptionName = self::getOptionName( $option );
		update_option( $WPOptionName, $data ) or
		add_option( $WPOptionName, $data );
		self::$$option = $data;
	}

	/**
	 * Fetch option from the database and also update it in object's property
	 *
	 * @param string $option Option name, one of the OPTION_NAME_* constants
	 * @param mixed $default Optional. Default value to return if the option does not exist.
	 *
	 * @return mixed
	 */
	protected static function getOption( $option, $default = false ) {
		$WPOptionName  = self::getOptionName( $option );
		$value         = get_option( $WPOptionName, $default );
		self::$$option = $value;

		return $value;
	}

	/**
	 * Get actual option name in Wordpress database
	 *
	 * @param string $option Option name, one of the OPTION_NAME_* constants
	 *
	 * @return string Option's name in Wordpress database
	 */
	protected static function getOptionName( $option ) {
		return self::OPTION_NAME_PREFIX . $option;
	}


	/**
	 * Get array with all options names
	 * @return array Set of all options names
	 */
	protected function optionNames() {
		return array(
			self::OPTION_NAME_PLUGIN_VERSION,
			self::OPTION_NAME_MODE,
			self::OPTION_NAME_DEDICATED_ENVIRONMENT,
			self::OPTION_NAME_BUSINESS_MODEL,
			self::OPTION_NAME_ENABLE_PPP,
			self::OPTION_NAME_USER_PROVIDER,
			self::OPTION_NAME_FEATURES,
			self::OPTION_NAME_PAYWALL_ID,
			self::OPTION_NAME_APP_ID,
			self::OPTION_NAME_API_TOKEN,
			self::OPTION_NAME_PRIVATE_KEY,
			self::OPTION_NAME_OFFER_ID,
			self::OPTION_NAME_OFFER_SET,
			self::OPTION_NAME_PAYWALL_OFFER_ID,
			self::OPTION_NAME_PAYWALL_TEMPLATE_ID,
			self::OPTION_NAME_RESOURCES,
			self::OPTION_NAME_DISABLED_FOR_PRIVILEGED,
			self::OPTION_NAME_METER_HOME_PAGE,
			self::OPTION_NAME_DEFAULT_ACCESS_SETTINGS,
			self::OPTION_NAME_TRUNCATION_MODE,
			self::OPTION_NAME_PARAGRAPHS_COUNT,
			self::OPTION_NAME_FIRST_CLICK_MODE,
			self::OPTION_NAME_FIRST_CLICK_REFERRERS,
			self::OPTION_NAME_DEBUG,
			self::OPTION_NAME_ENABLE_PREMIUM_TAG,
			self::OPTION_NAME_DISPLAY_MODE,
			self::OPTION_NAME_NULL
		);
	}

	/**
	 * Get all plugin's meta names
	 * @return array
	 */
	protected function metaNames() {
		return array(
			self::META_NAME
		);
	}

	/**
	 * Determine if plugin is configured properly and can be operated
	 * @var bool $versionSpecific
	 * @return bool
	 */
	public function isConfigured( $versionSpecific = true ) {
		$isConfigured = false;
		// For metered business model:
		if (
			! empty( self::$app_id ) &&
			! empty( self::$api_token ) &&
			( self::$business_model == TinypassConfig::BUSINESS_MODEL_METERED ) &&
			! empty( self::$paywall_id ) &&
			! empty( self::$mode )
		) {
			$isConfigured = true;
		}
		// For subscription-based business model
		if (
			! empty( self::$app_id ) &&
			! empty( self::$api_token ) &&
			( self::$business_model == TinypassConfig::BUSINESS_MODEL_SUBSCRIPTION ) &&
			! empty( self::$offer_id ) &&
			! empty( self::$mode )
		) {
			$isConfigured = true;
		}

		if ( $versionSpecific && $isConfigured ) {
			// Check if configured properly for the API
			$isConfigured = $this::$tinypass->isConfigured();
		}

		return $isConfigured;
	}

	/**
	 * Provide connector object with parameters, fetch additional parameters from API if requested
	 *
	 * @param bool $fetchAPI fetch additional parameters from API
	 */
	protected function initTinypass( $fetchAPI = false ) {
		global $current_user;
		get_currentuserinfo();

		self::$tinypass = new TinypassInternal();
		// Init all known data
		self::$tinypass
			->appId( self::$app_id )
			->paywallId( self::$paywall_id )
			->APIToken( self::$api_token )
			->businessModel( self::$business_model )
			->enablePayPerPost( self::$enable_ppp )
			->userId( $current_user->ID )
			->userEmail( $current_user->user_email )
			->userCreated( strtotime( $current_user->user_registered ) )
			->userFirstName( $current_user->user_firstname )
			->userLastName( $current_user->user_lastname )
			->paywallTemplateId( self::$paywall_template_id )
			->paywallOfferId( self::$paywall_offer_id )
			->offerId( self::$offer_id )
			->privateKey( self::$private_key )
			->userProvider( self::$user_provider )
			->features( self::$features )
			->firstClickMode( self::$first_click_mode )
			->firstClickRef( self::$first_click_ref )
			->displayMode( self::$display_mode );

		// Determine environment URLs
		$jsURL  = ( self::$mode == TinypassConfig::MODE_PRODUCTION ) ? TinypassConfig::JS_URL : TinypassConfig::SANDBOX_JS_URL;
		$APIUrl = '';
		switch ( self::$mode ) {
			case TinypassConfig::MODE_PRODUCTION:
				$APIUrl = TinypassConfig::BASE_URL;
				break;
			case TinypassConfig::MODE_CUSTOM:
				// Check if provided url can be parsed
				$APIUrl = TinypassConfig::parseCustomUrl( self::$dedicated );
				if ( $APIUrl === false ) {
					$this->setOption( self::OPTION_NAME_MODE, TinypassConfig::MODE_SANDBOX );
					$this->setOption( self::OPTION_NAME_DEDICATED_ENVIRONMENT, '' );
				} else {
					$jsURL = TinypassConfig::parseCustomJsUrl( self::$dedicated );
				}
				break;
			case TinypassConfig::MODE_SANDBOX:
				$APIUrl = TinypassConfig::SANDBOX_BASE_URL;

		}

		self::$tinypass
			->environment( self::$mode )
			->baseURL( $APIUrl )
			->jsURL( $jsURL );

		self::$tinypass->initAPI();

		if ( $fetchAPI ) {
			// We can throw exceptions here, they should be handled nicely

			// Call for API to get remaining settings
			$appData = self::$tinypass->getApp();

			if ( isset( $appData->user_provider ) ) {
				$this->setOption( self::OPTION_NAME_USER_PROVIDER, $appData->user_provider );
			} else {
				$this->setOption( self::OPTION_NAME_USER_PROVIDER, TinypassConfig::USER_PROVIDER_USER_REF );
			}
			$this->setOption( self::OPTION_NAME_PRIVATE_KEY, $appData->private_key );

			self::$tinypass->userProvider( self::$user_provider );

			$appFeatures = self::$tinypass->getAppFeatures();
			$this->setOption( self::OPTION_NAME_FEATURES, $appFeatures );

			if ( self::$tinypass->algorithmicKeyAvailable() ) {
				self::$tinypass->setWebhookEndpoint( get_site_url() . '/tinypass/callback' );
			}

			if ( self::$business_model ) {
				// Fetch some configuration-specific parameters
				if ( self::$business_model == TinypassConfig::BUSINESS_MODEL_METERED ) {
					$paywallData = self::$tinypass->getPaywallData();
					$this->setOption( self::OPTION_NAME_PAYWALL_OFFER_ID, $paywallData->user_meter->offer_id );
					$this->setOption( self::OPTION_NAME_PAYWALL_TEMPLATE_ID, $paywallData->user_meter->curtain_template_id );
				} elseif ( self::$business_model == TinypassConfig::BUSINESS_MODEL_SUBSCRIPTION ) {
					$offer                          = self::$tinypass->getWebsiteOffer( get_bloginfo( 'name' ) );
					self::$offer_set[ self::$mode ] = self::$offer_id = $offer->offer_id;
					self::$tinypass->offerId( self::$offer_id );
					$this->setOption( self::OPTION_NAME_OFFER_ID, self::$offer_id );
					$this->setOption( self::OPTION_NAME_OFFER_SET, self::$offer_set );
				}

			}
			// Get resources and terms
			self::$resources = self::$tinypass->updateResourceList( $this->getResources( false ) );

			self::setOption( self::OPTION_NAME_RESOURCES, self::$resources );

		}
	}

	public function __construct() {
		// Init function for batCache
		$this->varyCache();

		// Get all wordpress options related to the plugin
		$this->initOptions();

		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Fetch all required options from database
	 */
	protected function initOptions() {

		$isAdmin = is_admin();

		if ( $isAdmin ) {
			$this->getOption( self::OPTION_NAME_PLUGIN_VERSION );
		}

		// These options are always available
		$coreOptions = array(
			self::OPTION_NAME_MODE,
			self::OPTION_NAME_BUSINESS_MODEL,
			self::OPTION_NAME_USER_PROVIDER,
			self::OPTION_NAME_FEATURES,
			self::OPTION_NAME_APP_ID,
			self::OPTION_NAME_API_TOKEN,
			self::OPTION_NAME_PRIVATE_KEY,
			self::OPTION_NAME_DISABLED_FOR_PRIVILEGED,
			self::OPTION_NAME_TRUNCATION_MODE,
			self::OPTION_NAME_FIRST_CLICK_MODE,
			self::OPTION_NAME_DEBUG,
			self::OPTION_NAME_ENABLE_PREMIUM_TAG,
			self::OPTION_NAME_DISPLAY_MODE
		);

		// Load these options
		foreach ( $coreOptions as $option ) {
			$this->getOption( $option );
		}

		$businessModelOptions = array();
		if ( TinypassConfig::BUSINESS_MODEL_METERED === self::$business_model ) {
			// Load options for metered paywall
			$businessModelOptions = array(
				self::OPTION_NAME_PAYWALL_ID,
				self::OPTION_NAME_PAYWALL_OFFER_ID,
				self::OPTION_NAME_PAYWALL_TEMPLATE_ID,
				self::OPTION_NAME_METER_HOME_PAGE,
			);
		} elseif ( TinypassConfig::BUSINESS_MODEL_SUBSCRIPTION === self::$business_model ) {
			// Load options for hard paywall
			$businessModelOptions = array(
				self::OPTION_NAME_OFFER_ID,
				self::OPTION_NAME_OFFER_SET,
				self::OPTION_NAME_RESOURCES,
				self::OPTION_NAME_ENABLE_PPP,
			);
		}

		// Load options for the selected business model
		foreach ( $businessModelOptions as $option ) {
			$this->getOption( $option );
		}

		if ( $isAdmin ) {
			// Default access settings are only used in backend
			$this->getOption( self::OPTION_NAME_DEFAULT_ACCESS_SETTINGS );
		}

		if ( $isAdmin || ( TinypassConfig::MODE_CUSTOM === $this::$mode ) ) {
			// Dedicated environment url in frontend is only required when the mode is set to "dedicated"
			$this->getOption( self::OPTION_NAME_DEDICATED_ENVIRONMENT );
		}

		if ( $isAdmin || ( self::TRUNCATION_MODE_PARAGRAPHED === self::$truncation_mode ) ) {
			// Paragraphs count value in frontend is only required when truncation mode is set to "paragraphed"
			$this->getOption( self::OPTION_NAME_PARAGRAPHS_COUNT );
		}

		if ( $isAdmin || ( in_array( self::$first_click_mode, array( TinypassConfig::FIRST_CLICK_OPTION_EXCLUDE, TinypassConfig::FIRST_CLICK_OPTION_EXCLUDE ) ) ) ) {
			// Referrers for first click in frontend are only required when first click mode is set to "include" or "exclude
			$this->getOption( self::OPTION_NAME_FIRST_CLICK_REFERRERS );
		}

		if ( is_array( self::$offer_set ) ) {
			self::$offer_id = isset( self::$offer_set[ self::$mode ] ) ? self::$offer_set[ self::$mode ] : null;
		} else {
			self::$offer_set = array();
		}
	}

	/**
	 * Reset all plugin settings
	 */
	protected function resetAll() {
		// Get all option names related to the plugin
		$options = $this->optionNames();

		foreach ( $options as $option ) {
			// Delete the option
			delete_option( self::getOptionName( $option ) );
		}

		// Remove meta names
		$metaNames = $this->metaNames();

		foreach ( $metaNames as $metaName ) {
			delete_post_meta_by_key( $metaName );
		}
	}

	/**
	 * Get the resources, saved in the wordpress database and return them as array of TinypassResource
	 *
	 * @param bool $enabledOnly get only enabled resources
	 *
	 * @return TinypassResource[]
	 */
	public static function getResources( $enabledOnly = true ) {
		$resources = array();
		if ( self::$resources ) {
			foreach ( self::$resources as $id => $resourceData ) {
				// Initialize resource data as TinypassResource
				$resource = new TinypassResource( $resourceData, $enabledOnly );
				if ( ! $enabledOnly || $resource->isEnabled() ) {
					$resources[ $id ] = $resource;
				}
			}
		}

		return $resources;
	}

	/*
	 * Method for cleaning content on restricted access. It will not be called if truncation settings were set to be done by publisher's truncation algorithm
	 * @param string $content The content to truncate
	 * @param string $buttonHtml The html of a button (if applicable)
	 * @return string Truncated content
	 *
	 */
	public function noAccess( $content, $buttonHtml = '' ) {
		switch ( self::$truncation_mode ) {
			// If truncation is set to be dune by splitting content by "tpmore" tag
			case self::TRUNCATION_MODE_TPMORE:
				$content = self::getPostExcerpt();
				break;
			// If truncation is set to be done by number of paragraphs
			case self::TRUNCATION_MODE_PARAGRAPHED:
				$content = self::truncateParagraphs( $content, intval( self::$paragraphs_count ) );
				break;
			default: // Default is "hide all"
				$content = '';
		}

		// Add the button if needed
		$content .= $buttonHtml;

		return $content;
	}

	/**
	 * Return only specified amount of paragraphs
	 *
	 * @param string $content content to truncate
	 * @param int $count amount of paragraphs allowed to be shown
	 *
	 * @return string
	 */
	public static function truncateParagraphs( $content, $count ) {
		$return     = '';
		$paragraphs = explode( '</p>', $content );

		for ( $i = 0; ( $i < $count ) && ( $i < count( $paragraphs ) ); $i ++ ) {
			$return .= $paragraphs[ $i ] . '</p>';
			if ( ! trim( strip_tags( $paragraphs[ $i ] ) ) ) {
				$count ++;
			}
		}

		return $return;
	}

	/**
	 * Truncate content by "tpmore" tag
	 * @return string Truncated content
	 */
	public static function getPostExcerpt() {
		global $post;

		// Separate content with tpmore tag or more tag
		$extended = self::getExtended( $post->post_content );

		$content = $extended['main'];
		// If content had no tpmore or more tags - trim manually
		if ( ! $extended['extended'] ) {
			$content = '';
		}

		// Apply the content filters
		$content = apply_filters( 'the_content', $content );

		return $content;
	}

	/**
	 * Find the truncation point return the array with split content
	 *
	 * @param string $post The post content to split
	 *
	 * @return array Array with three elements: "main" - the content before tag, "extended" - the content after the tag, "more_text" - the text within the tag
	 */
	private static function getExtended( $post ) {
		// Available split tags sorted by execution priority
		$moreTags  = array( 'tpmore', 'more' );
		$main      = null;
		$extended  = null;
		$more_text = null;
		// Loop through available split tags
		foreach ( $moreTags as $moreTag ) {
			if ( preg_match( '/<!--' . $moreTag . '(.*?)?-->/', $post, $matches ) ) {
				// If the tag is present - split the content in place of this tag
				list( $main, $extended ) = explode( $matches[0], $post, 2 );
				$more_text = $matches[1];
				break;
			}
		}

		// If none of the tags were found
		if ( null === $main ) {
			$main      = $post;
			$extended  = '';
			$more_text = '';
		}

		//  leading and trailing whitespace.
		$main      = preg_replace( '/^[\s]*(.*)[\s]*$/', '\\1', $main );
		$extended  = preg_replace( '/^[\s]*(.*)[\s]*$/', '\\1', $extended );
		$more_text = preg_replace( '/^[\s]*(.*)[\s]*$/', '\\1', $more_text );

		return array(
			'main'      => $main,
			'extended'  => $extended,
			'more_text' => $more_text
		);
	}

	/**
	 * Get the resource id for pay-per-post
	 *
	 * @param $postId
	 *
	 * @return string
	 */
	public static function getPayPerPostResourceId( $postId ) {
		return self::PAY_PER_POST_RESOURCE_PREFIX . $postId;
	}

	/**
	 * Get resource/term name for the post
	 *
	 * @param $postId
	 *
	 * @return string
	 */
	protected function getOfferName( $postId ) {
		return get_the_title( $postId );
	}

	/**
	 * Send data to debugger
	 *
	 * @param string $name Slug name of data
	 * @param mixed $value The data itself
	 * @param bool $single is data singular or an array
	 */
	public static function debugData( $name, $value, $single = true ) {
		if ( ! self::$debug ) {
			return;
		}
		if ( $single ) {
			self::$_debugData[ $name ] = $value;

			return;
		} elseif ( ! array_key_exists( $name, self::$_debugData ) ) {
			self::$_debugData[ $name ] = array();
		}
		self::$_debugData[ $name ][] = $value;

		return;
	}

	public function uninstall() {
		$this->resetAll();
	}

	/**
	 * Generating function for batCache
	 */
	private function varyCache() {
		if ( function_exists( 'vary_cache_on_function' ) ) {
			$cacheFunction = '
				if ( preg_match( "/tinypass\/callback/i", $_SERVER["HTTP_HOST"] ) ) {
					return true;
				}
				$host = parse_url( $_SERVER["HTTP_HOST"] );
				$host = isset( $host["host"] ) ? $host["host"] : false;

				if ( $host ) {
					$ref = false;
					if ( ! empty( $_POST["_wp_http_referer"] ) ) {
						$ref = wp_unslash( $_POST["_wp_http_referer"] );
					} else if ( ! empty( $_GET["_wp_http_referer"] ) ) {
						$ref = wp_unslash( $_GET["_wp_http_referer"] );
					} else if ( ! empty( $_SERVER["HTTP_REFERER"] ) ) {
						$ref = wp_unslash( $_SERVER["HTTP_REFERER"] );
					}

					if ( $ref && $ref !== wp_unslash( $_SERVER["REQUEST_URI"] ) ) {
						$location = trim( $ref );
						// browsers will assume http is your protocol, and will obey a redirect to a URL starting with //
						if ( substr( $location, 0, 2 ) == "//" ) {
							$location = "http:" . $location;
						}

						// In php 5 parse_url may fail if the URL query part contains http://, bug #38143
						$test = ( $cut = strpos( $location, "?" ) ) ? substr( $location, 0, $cut ) : $location;

						$lp = parse_url( $test );

						// Give up if malformed URL
						if ( false !== $lp ) {
							// Allow only http and https schemes. No data:, etc.
							if ( isset( $lp["scheme"] ) && ( "http" == $lp["scheme"] || "https" == $lp["scheme"] ) ) {
								// Reject if scheme is set but host is not. This catches urls like https:host.com for which parse_url does not set the host field.
								if ( isset( $lp["scheme"] ) && isset( $lp["host"] ) ) {
									// if referred from outside
									if ( ! preg_match( "/{$host}/", $location ) ) {
										return true;
									}
								}
							}
						}
					}
				}
				if ( array_key_exists( "__tac", $_COOKIE ) ) {
					return $_COOKIE["__tac"];
				}

				return false;
            ';
			vary_cache_on_function( $cacheFunction );
		}
	}
}
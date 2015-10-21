<?php

/**
 * Contains methods for handling plugin functionality on front end
 * Class WPTinypassFrontend
 */
class WPTinypassFrontend extends WPTinypass {

	const OFFER_JS_URL_PREFIX = 'tinypassOfferJs';
	const METER_JS_URL_PREFIX = 'tinypassMeter';

	const PREMIUM_TITLE_HTML_NO_ACCESS = '<span class="tinypass-title-premium-no-access"></span>';
	const PREMIUM_TITLE_HTML_HAS_ACCESS = '<span class="tinypass-title-premium-has-access"></span>';

	public function __construct() {
		// Remove access token cookies on logout
		add_action( 'wp_logout', array( $this, 'logout' ) );
		// Main filter for the content which truncates content and displays offers
		add_filter( 'the_content', array( $this, 'filterContent' ) );
		if ( self::$enable_premium_tag ) {
			// Filter for the title which adds premium tag for premium content
			add_filter( 'the_title', array( $this, 'filterTitle' ), 10, 2 );
		}
		// Render javascript for tinypass initialisation
		add_action( 'wp_enqueue_scripts', array( $this, 'renderJS' ) );
		// Add css for premium tags
		add_action( 'wp_enqueue_scripts', array( $this, 'initCss' ) );
		// Add webhook endpoint
		add_action( 'template_redirect', array( $this, 'webHookCallback' ) );
		// Display javascript for offers
		add_action( 'template_redirect', array( $this, 'showOfferJs' ) );
		if ( self::$business_model == TinypassConfig::BUSINESS_MODEL_METERED ) {
			// Display javascript for metered paywall on home page
			add_action( 'template_redirect', array(
				$this,
				'showMeterTickJs'
			) );
		}
		// Add filter which will clear any passed content on no access
		add_filter( WPTinypass::WP_FILTER_TINYPASS, array(
			$this,
			'noAccessNoContent'
		), 10, 2 );
		// Shortcode for "My account" component
		add_shortcode( WPTinypass::WP_SHORTCODE_MY_ACCOUNT, array(
			$this,
			'myAccount'
		) );
	}

	/**
	 * Add premium tag to the title (if needed)
	 *
	 * @param $title
	 * @param null $id
	 *
	 * @return string
	 */
	public function filterTitle( $title, $id = null ) {
		$metaString      = get_post_meta( $id, self::META_NAME, true );
		$contentSettings = TinypassContentSettings::fromArray( $metaString );

		if ( $contentSettings->isPremium( self::$business_model, self::$enable_ppp, self::$tinypass->algorithmicKeyAvailable(), $this->getResources() ) ) {
			$premiumHtml     = self::PREMIUM_TITLE_HTML_HAS_ACCESS;
			try {
				$result = self::$tinypass->checkAccessSettings( $contentSettings, null, array(
					$this,
					'trimContent'
				), $this->getResources() );
				if ( $result->isKeyed() ) {
					$premiumHtml = self::PREMIUM_TITLE_HTML_NO_ACCESS;
				}
			} catch ( Exception $e ) {
				$premiumHtml = '';
			}

			// Apply filters to the tag in case if publisher has their own html for it (or they don't want it at all)
			$premiumHtml = apply_filters( self::WP_FILTER_PREMIUM_TAG, $premiumHtml );

			if ( $premiumHtml ) {
				$premiumHtml = '&nbsp;' . wp_kses_post( $premiumHtml );
			}

			$title .= $premiumHtml;

		}


		return $title;
	}

	public function logout() {
		$this::$tinypass->deleteResourcesCookie();
	}

	public function filterContent( $postContent ) {
		if ( ( self::$disabled_for_privileged && current_user_can( 'edit_posts' ) ) ) {
			// For post listings and excerpts content shouldn't be altered
			return $postContent;
		}

		if ( self::$business_model == TinypassConfig::BUSINESS_MODEL_METERED ) {
			if ( is_home() && self::$track_home_page ) {
				// If enabled metering home page and is home page - enqueue script for metering
				wp_enqueue_script( 'tinypass-meter', $this->getMeterJsUrl(), array( 'jquery' ) );
			}
		}
		if ( ! is_singular() ) {
			// Do not alter content if is not on content's page
			return $postContent;
		}

		// Get post id
		$id = get_the_ID();

		// Only process the content if it is the dedicated page of this content
		global $wp_query;
		if ( $wp_query->post->ID != $id ) {
			return $postContent;
		}

		// Check for first click free
		if ( self::$tinypass->isFirstClickFree( $this->getReferrer(), get_site_url() ) ) {
			return $postContent;
		}

		// Detach filter so it won't get into recursion
		remove_filter( 'the_content', array(
			$this,
			'filterContent'
		) );


		// Get post meta data
		$metaString = get_post_meta( $id, self::META_NAME, true );

		$contentSettings = TinypassContentSettings::fromArray( $metaString );

		if ( $contentSettings->isPremium( self::$business_model, self::$enable_ppp, self::$tinypass->algorithmicKeyAvailable(), $this->getResources() ) ) {
			try {
				// Check acccess to the content
				$result = self::$tinypass->checkAccessSettings( $contentSettings, $postContent, array(
					$this,
					'trimContent'
				), $this->getResources() );
				if ( $result->isKeyed() ) {
					$postContent = $result->content();
					wp_enqueue_script( 'tinypass-offer-' . $id, $this->getOfferJsUrl( $id ), array( 'jquery' ) );
				}
			} catch ( Exception $e ) {
				// Catch errors, so it won't break displaying the content
				self::debugData( WPTinypassDebugger::FIELD_TINYPASS_FATAL, $e->getMessage() );
			}
		}

		switch ( $contentSettings->chargeOption() ) {
			case $contentSettings::CHARGE_OPTION_ALGORITHMIC:
				$this->debugChargeOption( $id, __( 'Algorithmic keying', 'tinypass' ) );
				break;
			case $contentSettings::CHARGE_OPTION_ALWAYS:
				$this->debugChargeOption( $id, __( 'Constant paywall', 'tinypass' ) );
				break;
			case $contentSettings::CHARGE_OPTION_METERED:
				$this->debugChargeOption( $id, __( 'Metered paywall', 'tinypass' ) );
				break;
			case $contentSettings::CHARGE_OPTION_SUBSCRIPTION:
				$this->debugChargeOption( $id, __( 'Always keyed', 'tinypass' ) );
				break;
			default:
				$this->debugChargeOption( $id, __( 'Free', 'tinypass' ) );
		}

		// Re-attach filter, probably not necessary, but might be handy
		add_filter( 'the_content', array(
			$this,
			'filterContent'
		) );

		// Return updated content
		return $postContent;
	}

	public function noAccessNoContent( $content, $postId = null ) {
		if ( null === $postId ) {
			$postId = get_the_ID();
		}
		$metaString      = get_post_meta( $postId, self::META_NAME, true );
		$contentSettings = TinypassContentSettings::fromArray( $metaString );
		try {
			$result = self::$tinypass->checkAccessSettings( $contentSettings, null, null, $this->getResources() );
			if ( $result->isKeyed() ) {
				return '';
			}
		} catch ( Exception $e ) {

		}

		return $content;
	}

	/**
	 * Truncate content with the configured method
	 *
	 * @param string $content Original content
	 *
	 * @return string
	 */
	public function trimContent( $content ) {
		return $content = apply_filters( self::WP_FILTER_NO_ACCESS, $content );
	}

	/**
	 * Get referrer
	 * @return bool|string False if no referrer found, or string with referrer otherwise
	 */
	private function getReferrer() {
		$ref = false;
		// wp_get_referer() can't be used here because it filters through allowed referers
		if ( ! empty( $_POST["_wp_http_referer"] ) ) {
			$ref = wp_unslash( $_POST["_wp_http_referer"] );
		} else if ( ! empty( $_GET["_wp_http_referer"] ) ) {
			$ref = wp_unslash( $_GET["_wp_http_referer"] );
		} else if ( ! empty( $_SERVER["HTTP_REFERER"] ) ) {
			$ref = wp_unslash( $_SERVER["HTTP_REFERER"] );
		}

		if ( $ref && $ref !== wp_unslash( $_SERVER['REQUEST_URI'] ) ) {
			$location = trim( $ref );
			// browsers will assume 'http' is your protocol, and will obey a redirect to a URL starting with '//'
			if ( substr( $location, 0, 2 ) == '//' ) {
				$location = 'http:' . $location;
			}

			// In php 5 parse_url may fail if the URL query part contains http://, bug #38143
			$test = ( $cut = strpos( $location, '?' ) ) ? substr( $location, 0, $cut ) : $location;

			$lp = parse_url( $test );

			// Give up if malformed URL
			if ( false === $lp ) {
				return false;
			}

			// Allow only http and https schemes. No data:, etc.
			if ( isset( $lp['scheme'] ) && ! ( 'http' == $lp['scheme'] || 'https' == $lp['scheme'] ) ) {
				return false;
			}

			// Reject if scheme is set but host is not. This catches urls like https:host.com for which parse_url does not set the host field.
			if ( isset( $lp['scheme'] ) && ! isset( $lp['host'] ) ) {
				return false;
			}

			return $location;
		}

		return false;
	}

	/**
	 * Render any javascript necessary
	 */
	public function renderJS() {
		self::$tinypass->initJS();
	}

	/**
	 * Enqueues CSS for content keying indicator (the dot near the title)
	 */
	public function initCss() {
		wp_enqueue_style( 'tinypass_frontend_css', plugin_dir_url( TINYPASS_PLUGIN_FILE_PATH ) . 'css/frontend.css' );
	}

	/**
	 * Debug charge option
	 *
	 * @param int $id Content id
	 * @param string $option Human-readable charge option
	 */
	private function debugChargeOption( $id, $option ) {

		self::debugData( WPTinypassDebugger::FIELD_CHARGE_OPTIONS, '<strong>' . __( $option, 'tinypass' ) . '</strong>' );
	}


	/**
	 * Set the uri /tinypass/callback to read webhook data sent by tinypass
	 */
	public function webHookCallback() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) || empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		// Check if uri is /tinypass/callback
		if ( ! preg_match( '/^\/?tinypass\/callback/', $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		// Allow from external domains
		header( 'Access-Control-Allow-Origin: *' );
		try {
			// Get data from post or get
			$data = ( isset( $_POST['data'] ) ? $_POST['data'] : ( isset( $_GET['data'] ) ? $_GET['data'] : null ) );
			if ( ! $data ) {
				// Try to get data by parsing URI
				$params = array();
				parse_str( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY ), $params );
				$data = ( isset( $params['data'] ) ) ? str_replace( ' ', '+', $params['data'] ) : null;
			}
			// Process found data
			$result = self::$tinypass->processWebhookData( $data );
			$this->processWebhookResult( $result );
		} catch ( Exception $e ) {
			if ( $e->getCode() == TinypassConfig::ERROR_WEBHOOK_NO_PROCESSOR_FOUND ){
				// When there's no processor for event - it still should respond as OK, since otherwise it will block other webhooks
				header( 'HTTP/1.1 200 OK' );
			} else {
				// This endpoit should be only for tinypass interactions, so it's safe to display fatal errors
				header( $_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500 );
			}

			die( $e->getMessage() );
		}
	}

	/**
	 * Process the result of webhook procession
	 *
	 * @param TinypassWebhookResult $webhookResult
	 *
	 * @throws Exception
	 */
	private function processWebhookResult( TinypassWebhookResult $webhookResult ) {
		switch ( $webhookResult->action() ) {
			case TinypassWebhookResult::ACTION_UPDATE_CONTENT_KEY:
				$metaString = get_post_meta( $webhookResult->id(), self::META_NAME, true );
				// Get content settings from post meta
				$contentSettings = TinypassContentSettings::fromArray( $metaString );
				if ( $contentSettings->chargeOption() != TinypassContentSettings::CHARGE_OPTION_ALGORITHMIC ) {
					throw new Exception( __( 'Algorithmic keying is not enabled for this post', 'tinypass' ) );
				}
				// Set the key attribute to resulted from webhook processing
				$contentSettings->algorithmicKeyed( $webhookResult->key() );
				// Save post meta
				add_post_meta( $webhookResult->id(), self::META_NAME, $contentSettings->toArray(), true ) or
				update_post_meta( $webhookResult->id(), self::META_NAME, $contentSettings->toArray() );
				header( 'HTTP/1.1 200 OK' );
				exit( __( 'Keying rules updated', 'tinypass' ) );
				break;
		}
	}

	/**
	 * Get javascript URI for metered paywall on home page
	 * @return string
	 */
	public function getMeterJsUrl() {
		return '/' . self::METER_JS_URL_PREFIX;
	}

	/**
	 * Get javascript URI for offer
	 *
	 * @param $id
	 *
	 * @return string
	 */
	public function getOfferJsUrl( $id ) {
		return '/' . self::OFFER_JS_URL_PREFIX . $id;
	}

	/**
	 * Render javascript for metering without any content provided
	 */
	public function showMeterTickJs() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) || empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		if ( ! preg_match( '/^\/?' . self::METER_JS_URL_PREFIX . '(\?.+)?/', $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		try {
			$result = self::$tinypass->tickMeter();
			header( 'HTTP/1.1 200 OK' );
			header( 'Content-Type: application/javascript' );
			echo $result->javascript();
			die();
		} catch ( Exception $e ) {
			header( 'HTTP/1.1 200 OK' );
			if ( $this->canDebug() ) {
				die( $e->getMessage() );
			}
		}
	}

	/**
	 * Render offer javascript for content
	 */
	public function showOfferJs() {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) || empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}

		if ( ! preg_match( '/^\/?' . self::OFFER_JS_URL_PREFIX . '(\d+)(\?.+)?/', $_SERVER['REQUEST_URI'], $match ) ) {
			return;
		}

		$id   = $match[1];
		$post = get_post( $id );
		if ( ! $post ) {
			return;
		}
		$metaString      = get_post_meta( $post->ID, self::META_NAME, true );
		$contentSettings = TinypassContentSettings::fromArray( $metaString );
		$content         = apply_filters( 'the_content', $post->post_content );

		try {
			$result = self::$tinypass->checkAccessSettings( $contentSettings, $content, array(
				$this,
				'trimContent'
			), $this->getResources() );
			header( 'HTTP/1.1 200 OK' );
			header( 'Content-Type: application/javascript' );
			echo $result->javascript();
			die();
		} catch ( Exception $e ) {
			header( 'HTTP/1.1 200 OK' );
			if ( $this->canDebug() ) {
				die( $e->getMessage() );
			}
		}
	}

	public function myAccount( $atts ) {

		$atts = shortcode_atts( array(), $atts );

		$containerId = uniqid( 'tinypass_my_account_' );
		ob_start();
		require( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . '/views/frontend/my_account.php' );
		$content = ob_get_clean();

		return $content;
	}
}
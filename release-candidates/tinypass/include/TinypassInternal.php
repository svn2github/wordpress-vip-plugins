<?php

class TinypassInternal extends TinypassConfig {

	// Cookie name with access tokens
	const RESOURCES_COOKIE_NAME = '__tac';

	// Allowed lifetime of cookie with access tokens
	const RESOURCES_COOKIE_LIFETIME = 86400; // 1 day

	/**
	 * @var array set of resource ids which user has access to
	 */
	protected $usersResourcesIds = array();

	/**
	 * @var bool Do page metering server-side or with javascript
	 */
	protected $backendMeter = false;


	/**
	 * Get offer for the website, create one if failed to find
	 *
	 * @param string $websiteOfferName Website's name for the offer
	 *
	 * @return stdClass offer object
	 * @throws Exception
	 */
	public function getWebsiteOffer( $websiteOfferName ) {
		// Initialize returned variable
		$offer = null;

		// If offer_id was provided
		if ( $this->offerId() ) {
			// Get offer from API
			$res = $this->callAPI( '/publisher/offer/get', array(
				'aid'      => $this->appId(),
				'offer_id' => $this->offerId()
			) );

			try {
				// Check response expecting exception
				$this->checkApiResponse( $res );
				$offer = $res->offer;
			} catch ( Exception $e ) {
				// If response code is not "Invalid id" - rethrow the exception
				if ( $e->getCode() != 400 ) {
					throw $e;
				}
				$this->offerId( null );
			}
		}
		// If no valid offer_id was found - create offer
		if ( ! $this->offerId() ) {
			$res = $this->callAPI( '/publisher/offer/create', array(
				'aid'  => $this->appId(),
				'name' => $websiteOfferName
			) );
			try {
				// Check response expecting exception
				$this->checkApiResponse( $res );
				$offer = $res->offer;
				$this->offerId( $res->offer->offer_id );
			} catch ( Exception $e ) {
				$this->offerId( null );
			}
		}

		return $offer;
	}

	/**
	 * Updates content settings and does any necessary API requests
	 *
	 * @param TinypassContentSettings $contentSettings
	 * @param array $updatedData Request array with updated content settings values
	 *
	 * @return TinypassContentSettings
	 */
	public function saveContentSettings( TinypassContentSettings $contentSettings, $updatedData ) {
		// Get charge option before update
		$originalChargeOption = $contentSettings->chargeOption();

		$chargeOption = isset( $updatedData[ $contentSettings->chargeOptionPropertyName() ] ) ? $updatedData[ $contentSettings->chargeOptionPropertyName() ] : '' ;
		// Set possible values
		$contentSettings
			->chargeOption( $chargeOption )
			->resourceIds( isset( $updatedData[ $chargeOption ][ $contentSettings->resourceIdsPropertyName() ] ) ? $updatedData[ $chargeOption ][ $contentSettings->resourceIdsPropertyName() ] : null )
			->pppPrice( isset( $updatedData[ $chargeOption ][ $contentSettings->pppPricePropertyName() ] ) ? $updatedData[ $chargeOption ][ $contentSettings->pppPricePropertyName() ] : null )
			->pppCurrency( isset( $updatedData[ $chargeOption ][ $contentSettings->pppCurrencyPropertyName() ] ) ? $updatedData[ $chargeOption ][ $contentSettings->pppCurrencyPropertyName() ] : null );

		// Check for valid settings and set to proper settings (if required)
		$contentSettings->validate( $this->businessModel(), $this->enablePayPerPost(), $this->algorithmicKeyAvailable() );

		if ( $this->algorithmicKeyAvailable() ) {
			if ( ( $originalChargeOption == $contentSettings::CHARGE_OPTION_ALGORITHMIC ) && ( $originalChargeOption != $contentSettings->chargeOption() ) ) {
				// If algorithmic keying is enabled and if previously keying was set to "algorithmic" and now changed to something else - make request to unkey it
				$this->unwatchAlgorithmic( $contentSettings );
			} elseif ( $contentSettings->chargeOption() != $contentSettings::CHARGE_OPTION_ALGORITHMIC ) {
				// If algorithmic keying is enabled and current charge option is not algorithmic - make request to unkey content
				$this->unkeyAlgorithmic( $contentSettings );
			}
		}

		switch ( $contentSettings->chargeOption() ) {
			case $contentSettings::CHARGE_OPTION_ALGORITHMIC:
				// If algorithmic keying is enabled and it's the desired charge option - make request to save it
				$this->saveAlgorithmic( $contentSettings );
				break;
			case $contentSettings::CHARGE_OPTION_SUBSCRIPTION:
				if ( $contentSettings->hasPayPerPost() ) {
					// If pay-per-post option was selected - save it
					$contentSettings = $this->savePayPerPost( $contentSettings );
				}
		}

		return $contentSettings;
	}

	/**
	 * Check if has access to content
	 *
	 * @param TinypassContentSettings $contentSettings
	 * @param string $content
	 * @param callback $trimFunction Function for truncation
	 * @param TinypassResource[] $resources
	 *
	 * @return TinypassContentResult|null
	 * @throws Exception
	 */
	public function checkAccessSettings( TinypassContentSettings $contentSettings, $content = null, $trimFunction = null, $resources = array() ) {
		// Validate if content access setings are valid
		$contentSettings->validate( $this->businessModel(), $this->enablePayPerPost(), $this->algorithmicKeyAvailable(), $resources );

		$result = new TinypassContentResult();

		// If content is supposed to be free - return null
		if ( $contentSettings->isFree() ) {
			return $result;
		}

		if ( $this->businessModel() == self::BUSINESS_MODEL_METERED ) {
			// Process content for metered paywall business model
			if ( $contentSettings->chargeOption() == TinypassContentSettings::CHARGE_OPTION_METERED ) {
				$result = $this->initMeteredPaywall( $contentSettings, $content, $trimFunction );
				// For metered charge option the content needs to be always wrapped in proper html, since actual access checking is done via javascript
				$result->content( $this->trimMeter( $contentSettings, $content ) );
			} elseif ( $contentSettings->chargeOption() == TinypassContentSettings::CHARGE_OPTION_ALWAYS ) {
				$result = $this->initConstantPaywall( $contentSettings );
				if ( $result->isKeyed() ) {
					// For constant paywall, however, content should be wrapped and trimmed only if it's keyed (if user don't have access)
					$result->content( $this->trimOffer( $contentSettings, $content, $trimFunction ) );
				}
			}
		} elseif ( $this->businessModel() == self::BUSINESS_MODEL_SUBSCRIPTION ) {
			// Process content for hard / keyed business model
			if ( $contentSettings->chargeOption() == TinypassContentSettings::CHARGE_OPTION_SUBSCRIPTION ) {
				$result = $this->initHardKeyedPaywall( $contentSettings, $resources );
			} elseif ( $contentSettings->chargeOption() == TinypassContentSettings::CHARGE_OPTION_ALGORITHMIC ) {
				$result = $this->initAlgorithmicKeyedPaywall( $contentSettings, $resources );
			}

			if ( $result->isKeyed() ) {
				// Trim content if it's keyed
				$result->content( $this->trimOffer( $contentSettings, $content, $trimFunction ) );
			}
		}

		return $result;
	}

	/**
	 * @param TinypassContentSettings $contentSettings
	 * @param string $content
	 * @param callback $trimFunction
	 *
	 * @return TinypassContentResult
	 */
	public function initMeteredPaywall( TinypassContentSettings $contentSettings, $content, $trimFunction = null ) {
		$result = new TinypassContentResult();

		// Check access to paywall resource
		if ( ! $this->canAccessResources( array( self::PAYWALL_RESOURCE_PREFIX . $this->paywallId ) ) ) {
			// Set result as keyed
			$result->isKeyed( true );
		} else {
			$result->isKeyed( false );
		}

		$truncatedContent = '';
		if ( $trimFunction ) {
			// Truncate content using provided trim function
			$truncatedContent = call_user_func_array( $trimFunction, array( $content ) );
		}

		// Init javascript for metered paywall
		$result->javascript( $this->getMeterJs( $contentSettings, $truncatedContent ) );

		return $result;
	}

	/**
	 * @param TinypassContentSettings $contentSettings
	 * @param TinypassResource[] $resources
	 *
	 * @return TinypassContentResult
	 * @throws Exception
	 */
	public function initHardKeyedPaywall( TinypassContentSettings $contentSettings, $resources ) {
		// Get resource ids list with replaced pay-per-post slug to actual pay-per-post resource id
		$resourceIds = $contentSettings->getFilteredResourceIds();

		if ( ! $this->offerId() ) {
			// This should never be the case, but if happens - something is not right
			throw new Exception( __( 'Offer for this website is not set up', 'tinypass' ) );
		}

		// Create returning result object
		$result = new TinypassContentResult();
		// Check for access to resources
		if ( ! $this->canAccessResources( $resourceIds ) ) {
			$termIds = array();
			if ( $contentSettings->hasPayPerPost() ) {
				// If content has pay-per-post - get pay-per-post term id along the other terms
				$termIds[] = $contentSettings->payPerPostTermId();
			}
			// Go through required resources and get their terms
			foreach ( $resourceIds as $rid ) {
				// If required resource is within the known resources
				if ( array_key_exists( $rid, $resources ) ) {
					foreach ( $resources[ $rid ]->terms() as $term ) {
						// Popuplate terms array with term
						$termIds[] = $term->id();
					}
				}
			}
			// Set result as keyed
			$result->isKeyed( true );
			// Generate javascript
			$result->javascript( $this->getOfferJs( $contentSettings, $this->offerId(), null, null, $termIds ) );
		} else {
			// Set result as not keyed
			$result->isKeyed( false );
		}

		return $result;
	}

	/**
	 * @param TinypassContentSettings $contentSettings
	 * @param TinypassResource[] $resources
	 *
	 * @throws Exception
	 * @return mixed
	 */
	public function initAlgorithmicKeyedPaywall( TinypassContentSettings $contentSettings, $resources ) {
		$result = new TinypassContentResult();
		// Check if webhook was triggered to key this content
		if ( $contentSettings->algorithmicKeyed() ) {
			// If it was triggered - proceed as subscription paywall
			return $this->initHardKeyedPaywall( $contentSettings, $resources );
		} else {
			$result->isKeyed( false );
		}

		return $result;
	}

	/**
	 * @param TinypassContentSettings $contentSettings
	 *
	 * @return TinypassContentResult
	 */
	public function initConstantPaywall( TinypassContentSettings $contentSettings ) {
		// Create returning result object
		$result = new TinypassContentResult();

		// Check access for metered paywall resource
		if ( ! $this->canAccessResources( array( self::PAYWALL_RESOURCE_PREFIX . $this->paywallId ) ) ) {
			// Set result as keyed
			$result->isKeyed( true );
			// Generate javascript
			$result->javascript( $this->getOfferJs( $contentSettings, $this->paywallOfferId(), null, $this->paywallTemplateId(), null ) );
		} else {
			$result->isKeyed( false );
		}

		return $result;
	}


	/**
	 * Trim content and wrap it in required html for offer processing
	 *
	 * @param TinypassContentSettings $contentSettings
	 * @param $content
	 * @param callback $trimFunction
	 *
	 * @return string
	 */
	public function trimOffer( TinypassContentSettings $contentSettings, $content, $trimFunction = null ) {
		if ( $trimFunction ) {
			$content = call_user_func_array( $trimFunction, array( $content ) );
		} else {
			$content = '';
		}

		ob_start();
		require dirname( __FILE__ ) . '/tpl/offer.php';

		return ob_get_clean();

	}

	/**
	 * Wrap content in HTML for metered paywall procession
	 *
	 * @param TinypassContentSettings $contentSettings
	 * @param $content
	 *
	 * @return string
	 */
	public function trimMeter( TinypassContentSettings $contentSettings, $content ) {
		ob_start();
		require dirname( __FILE__ ) . '/tpl/meter.php';

		return ob_get_clean();

	}

	/**
	 * Get javascript to meter any page once
	 *
	 * @return TinypassContentResult
	 */
	public function tickMeter() {
		$result = new TinypassContentResult();

		// Check access to paywall resource
		if ( ! $this->canAccessResources( array( self::PAYWALL_RESOURCE_PREFIX . $this->paywallId ) ) ) {
			// Set result as keyed
			$result->isKeyed( true );
		} else {
			$result->isKeyed( false );
		}

		$result->javascript( $this->getMeterTickJs() );

		return $result;
	}

	/**
	 * Refresh resources from API
	 *
	 * @param TinypassResource[] $resources already set up resources
	 *
	 * @return array Updated resources list as simple array ready to be saved into db
	 * @throws Exception
	 */
	public function updateResourceList( $resources = array() ) {
		// Make API request to get terms. We don't make request for resources since there is no point of having resource in db without terms
		$res = $this->callAPI( '/publisher/term/list', array(
			'aid'    => $this->appId(),
			'limit'  => 1000,
			'offset' => 0
		) );

		// Check if api response is valid, it will throw exception if it's invalid
		$this->checkApiResponse( $res );
		// Initialize array of existing resource, so we could know if there are any to be removed
		$existingResources = array();
		$existingTerms     = array();
		// Loop through fetched terms
		foreach ( $res->terms as $term ) {
			if ( 'paywall' === $term->resource->type ) {
				// Paywall resource type cannot be used with this business model
				continue;
			}

			// Get resource id
			$rid = $term->resource->rid;
			// Initialize resource as null object
			$resource = null;
			if ( ! array_key_exists( $rid, $resources ) ) {
				// If this term's resource is not already known - init as new object
				$resource = new TinypassResource();
			} else {
				// If this term's resource is already already known - get it for an update
				$resource = $resources[ $rid ];
			}

			// Update term's resource with new data
			$resource
				->rid( $rid )
				->name( $term->resource->name )
				->description( $term->resource->description )
				->imageUrl( $term->resource->image_url );

			// Get terms array of this resource object
			$terms = $resource->terms();
			// Initialize term object as null
			$termObj = null;
			if ( ! array_key_exists( $term->term_id, $terms ) ) {
				// If term is not already inside the resource - initialize as new object
				$termObj = new TinypassTerm();
			} else {
				// If term is already inside the resource and is known - get it from array of resource's terms
				$termObj = $terms[ $term->term_id ];
			}

			// Update term object
			$termObj
				->id( $term->term_id )
				->name( $term->name )
				->description( $term->description )
				->billingPlanDescription( isset( $term->payment_billing_plan_description ) ? $term->payment_billing_plan_description : '' );

			// Update terms array with new term object
			$terms[ $termObj->id() ] = $termObj;

			// Update resource with updated terms array
			$resource->terms( $terms );

			// Insert updated resource with updated terms into resources array
			$resources[ $rid ] = $resource;

			$existingResources[] = $rid;
			$existingTerms[]     = $termObj->id();
		}

		// Initialize returning array
		$return = array();
		foreach ( $resources as $resource ) {
			// Fill the returning array with array data of the resource (with terms)
			if ( in_array( $resource->rid(), $existingResources ) ) {
				// Only fill if the resource still exists

				// Rebuild terms array to include only still existing
				$terms = array();
				foreach ( $resource->terms() as $term ) {
					if ( in_array( $term->id(), $existingTerms ) ) {
						$terms[ $term->id() ] = $term;
					}
				}
				$resource->terms( $terms );
				$return[ $resource->rid() ] = $resource->asArray();
			}
		}

		return $return;
	}


	/**
	 * Render js for initialization
	 */
	public function initJS() {
		// JavaScript generation
		require_once( dirname( __FILE__ ) . '/tpl/_js_header.php' );
	}

	/**
	 * Check if user can access to given resources ids
	 *
	 * @param array $resourceIds
	 *
	 * @return bool
	 */
	public function canAccessResources( $resourceIds ) {
		return $this->canAccessResourcesCookie( $resourceIds );
	}

	/**
	 * Check if user can access to given resources ids by checking cookies.
	 * This cookie and it's name are encrypted with the private key
	 * The format for cookie data is array ( {resourceId} => {timestamp} )
	 *
	 * @param $resourceIds
	 *
	 * @return bool
	 * @throws Exception
	 */
	private function canAccessResourcesCookie( $resourceIds ) {
		$usersResourcesIds = $this->getResourcesIdsFromCookie();
		foreach ( $usersResourcesIds as $resourceId ) {
			if ( in_array( $resourceId, $resourceIds ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Decrypt cookie with user's resources and get the array of resource ids
	 * @return array resource ids which user has access to
	 */
	private function getResourcesIdsFromCookie() {
		$resourceIds = array();

		$cookieName = $this->getResourcesCookieName();
		// This cookie value is working with vary_cache_on_function
		if ( isset( $_COOKIE[ $cookieName ] ) ) {
			if ( preg_match( '/^\{jax\}(.+)$/', rawurldecode( $_COOKIE[ $cookieName ] ), $match ) ) {
				$baseData = $match[1];
				$baseData = json_decode( TPSecurityUtils::decrypt( $this->privateKey(), $baseData ) );
				$data     = $this->checkResourcesCookieData( $baseData );
				if ( $baseData != $data ) {
					$this->deleteResourcesCookie();
				}
				foreach ( $data as $access ) {
					$resourceIds[] = $access->rid;
				}
			}
		}

		return $resourceIds;

	}

	private function getResourcesCookieName() {
		return self::RESOURCES_COOKIE_NAME;
	}


	/**
	 * Check if resources cookie data is up to date
	 *
	 * @param $data
	 *
	 * @return array
	 */
	private function checkResourcesCookieData( $data ) {
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		foreach ( $data as $access ) {
			if ( ! isset( $access->bt ) || ( ( time() - $access->bt ) > self::RESOURCES_COOKIE_LIFETIME ) ) {
				return array();
			}
		}

		return $data;
	}


	public function deleteResourcesCookie() {
		$cookieName = $this->getResourcesCookieName();
		// This cookie value is working with vary_cache_on_function so it should delete on logout
		setcookie( $cookieName, null, 0, '/' );
		setcookie( '__tae', null, 0, '/' );
	}

	/**
	 * Get billing plan string for posts with unlimited access
	 *
	 * @param $price
	 * @param $currency
	 *
	 * @return string
	 */
	private function oneOffFormat( $price, $currency ) {
		return "[ {$price} {$currency} ]";
	}

	/**
	 * Perform additional actions on saving post with subscriptions and pay-per-post
	 *
	 * @param TinypassContentSettings $contentSettings
	 *
	 * @return mixed
	 * @throws Exception
	 */
	protected function savePayPerPost( TinypassContentSettings $contentSettings ) {
		$rid = $this->payPerPostResourceId( $contentSettings );
		// Try to create resource
		$res = $this->callAPI( '/publisher/resource/create', array(
			'aid'  => $this->appId(),
			'rid'  => $rid,
			'name' => $contentSettings->name(),
			'type' => 'standard'
		) );
		try {
			$this->checkApiResponse( $res );
		} catch ( Exception $e ) {
			// Only allowed error - is if resource already exists
			if ( $e->getCode() != self::ERROR_RESOURCE_ALREADY_EXISTS ) {
				// If it's not - something went wrong
				throw $e;
			}
		}

		$contentSettings->payPerPostResourceId( $rid );

		if ( $contentSettings->payPerPostTermId() ) {
			// Term exists and we know it's ID
			// Update the term
			$res = $this->callAPI( '/publisher/term/payment/update', array(
				'name'                 => $contentSettings->name(),
				'term_id'              => $contentSettings->payPerPostTermId(),
				'payment_billing_plan' => $this->oneOffFormat( $contentSettings->pppPrice(), $contentSettings->pppCurrency() )
			) );

			try {
				$this->checkApiResponse( $res );
			} catch ( Exception $e ) {
				// Environment has been switched probably - therefore - create new term
				$contentSettings->payPerPostTermId( null );
			}
		}

		// If no term id is present - try to find it, and if no found - create term
		if ( ! $contentSettings->payPerPostTermId() ) {
			// Since this resource id is dedicated to this content - we can assume that any terms inside are pay-per-post terms
			$res = $this->callAPI( '/publisher/term/list', array(
				'aid'   => $this->appId(),
				'rid'   => $rid,
				'limit' => 1
			) );

			$this->checkApiResponse( $res );

			if ( $res->count ) {
				$termId = $res->terms[0]->term_id;
			} else {
				$res = $this->callAPI( '/publisher/term/payment/create', array(
					'aid'                        => $this->appId(),
					'rid'                        => $rid,
					'name'                       => $contentSettings->name(),
					'payment_billing_plan'       => $this->oneOffFormat( $contentSettings->pppPrice(), $contentSettings->pppCurrency() ),
					'payment_new_customers_only' => false
				) );

				$this->checkApiResponse( $res );

				$termId = $res->term->term_id;
			}

			$contentSettings->payPerPostTermId( $termId );
		}

		return $contentSettings;
	}

	/**
	 * Save article as algorithmically-keyed, this will enable sending web hooks from tinypass
	 *
	 * @param TinypassContentSettings $contentSettings
	 *
	 * @throws Exception
	 */
	public function saveAlgorithmic( TinypassContentSettings $contentSettings ) {


		$data = array(
			'aid'         => $this->appId(),
			'content_id'  => $contentSettings->id(),
			'content_url' => $contentSettings->url(),
			'metadata'    => json_encode( $contentSettings->meta() )
		);

		$res = $this->callAPI( '/publisher/algorithm/content/watch', $data );
		$this->checkApiResponse( $res );
	}

	/**
	 * Set article to stop being algorithmically-keyed
	 *
	 * @param TinypassContentSettings $contentSettings
	 *
	 * @throws Exception
	 */
	public function unwatchAlgorithmic( TinypassContentSettings $contentSettings ) {

		$data = array(
			'aid'        => $this->appId(),
			'content_id' => $contentSettings->id(),
		);

		$res = $this->callAPI( '/publisher/algorithm/content/unwatch', $data );
		$this->checkApiResponse( $res );
	}


	/**
	 * Set article to stop being algorithmically-keyed
	 *
	 * @param TinypassContentSettings $contentSettings
	 *
	 * @throws Exception
	 */
	public function unkeyAlgorithmic( TinypassContentSettings $contentSettings ) {

		$data = array(
			'aid'        => $this->appId(),
			'content_id' => $contentSettings->id(),
		);

		$res = $this->callAPI( '/publisher/algorithm/content/unlock', $data );
		$this->checkApiResponse( $res );
	}

	/**
	 * Parse given URL to the format of base url for API calls
	 *
	 * @param $rawUrl
	 *
	 * @return string Proper url for API
	 */
	public static function parseCustomUrl( $rawUrl ) {
		$parse = parse_url( $rawUrl );
		if ( false === $parse ) {
			return false;
		}

		return ( $parse['scheme'] ? "{$parse['scheme']}://" : '' ) . "{$parse['host']}" . ( $parse['port'] ? ":{$parse['port']}" : '' ) . '/api/v3';
	}

	/**
	 * Get javascript for offer
	 *
	 * @param TinypassContentSettings $contentSettings
	 * @param $offerId
	 * @param string $termId
	 * @param string $templateId
	 * @param array $termIds
	 *
	 * @return string
	 */
	private function getOfferJs( TinypassContentSettings $contentSettings, $offerId, $termId = null, $templateId = null, $termIds = null ) {
		ob_start();
		require dirname( __FILE__ ) . '/tpl/_js_offer.php';

		return ob_get_clean();
	}

	/**
	 * Get javascript for metered paywall
	 *
	 * @param TinypassContentSettings $contentSettings
	 * @param $truncatedContent
	 *
	 * @return string
	 */
	private function getMeterJs( TinypassContentSettings $contentSettings, $truncatedContent ) {
		ob_start();
		require dirname( __FILE__ ) . '/tpl/_js_meter.php';

		return ob_get_clean();
	}

	/**
	 * Get javascript for metering home page
	 *
	 * @return string
	 */
	private function getMeterTickJs() {
		ob_start();
		require dirname( __FILE__ ) . '/tpl/_js_meter_tick.php';

		return ob_get_clean();
	}

	/**
	 * Get array of available currencies
	 *
	 * @return array
	 * @throws Exception
	 */
	public function getCurrencies() {
		$res = $this->callAPI( '/publisher/app/currencies', array(
			'aid' => $this->appId()
		) );
		$this->checkApiResponse( $res );
		$currencies = array();
		foreach ( $res->data as $currency ) {
			$currencies[ $currency ] = $currency;
		}

		return $currencies;
	}

	/**
	 * Get user_ref token
	 * User ref token is used for identification of the user inside tinypass, it is base64-encoded string with user's data, i.e:
	 * tp.push(['setUserRef', 'aHR0cHM6Ly93d3cueW91dHViZS5jb20vd2F0Y2g/dj1kUXc0dzlXZ1hjUQ==']);
	 *
	 * @return string
	 */
	public function createUserRef() {
		return TPUserRefBuilder::create( $this->userId(), $this->userEmail() )
		                       ->setCreateDate( $this->userCreated() )
		                       ->setFirstName( $this->userFirstName() )
		                       ->setLastName( $this->userLastName() )
		                       ->build( $this->privateKey() );
	}

	/**
	 * Get resource id for pay-per-post
	 *
	 * @param TinypassContentSettings $contentSettings
	 *
	 * @return string
	 */
	protected function payPerPostResourceId( TinypassContentSettings $contentSettings ) {
		return self::PAY_PER_POST_RESOURCE_PREFIX . $contentSettings->id();
	}

	/**
	 * Process found webhook data
	 *
	 * @param string $data encrypted data
	 *
	 * @return TinypassWebhookResult
	 * @throws Exception
	 */
	public function processWebhookData( $data ) {
		// Decrypt data
		$data = TPSecurityUtils::decrypt( $this->privateKey(), $data );
		if ( false === $data ) {
			throw new Exception( __( 'Failed to decrypt data', 'tinypass' ) );
		}
		// Data expected to be in json
		$data = json_decode( $data, true );
		if ( null === $data ) {
			throw new Exception( __( 'Failed to parse data', 'tinypass' ) );
		}
		// Data should always have event_type and version attributes
		if ( ! isset( $data['type'] ) || ! isset( $data['version'] ) ) {
			throw new Exception( __( 'Invalid webhook data', 'tinypass' ) );
		}
		// Check if configured application id differs from provided by tinypass
		if ( self::appId() != ( isset( $data['aid'] ) ? $data['aid'] : '' ) ) {
			throw new Exception( __( 'Invalid application id', 'tinypass' ) );
		}
		switch ( $data['type'] ) {
			// Event to key / unkey content
			case 'content_algorithm': {
				if ( $data['version'] == 2 ) {
					return $this->webhookAlgorithmicKey( isset( $data['content_id'] ) ? $data['content_id'] : '' , ( isset( $data['event'] ) ? $data['event'] : '' )  );
				}
			}
		}
		// If processing didn't end at any point - that means no valid webhook processing was found
		throw new Exception( __( 'No valid webhook processor found', 'tinypass' ), self::ERROR_WEBHOOK_NO_PROCESSOR_FOUND );
	}

	/**
	 * Set the content to be keyed/unkeyed from algorithm
	 *
	 * @param int $id
	 * @param string $action
	 *
	 * @return TinypassWebhookResult
	 * @throws Exception
	 */
	private function webhookAlgorithmicKey( $id, $action ) {
		// If algorithmic keyng is disabled - exit
		if ( ! $this->algorithmicKeyAvailable() ) {
			throw new Exception( __( 'Algorithmic keying is not available for this site', 'tinypass' ) );
		}
		$key = null;
		// Check for valid keying action: allowed only "key" or "unkey"
		switch ( $action ) {
			case 'lock':
				$key = true;
				break;
			case 'unlock':
				$key = false;
				break;
			default:
				throw new Exception( __( 'Invalid action', 'tinypass' ) );
		}

		if ( ! $id ) {
			throw new Exception( __( 'No id provided', 'tinypass' ) );
		}

		$result = new TinypassWebhookResult();
		// Set result of operation - update keying settings
		$result
			->action( TinypassWebhookResult::ACTION_UPDATE_CONTENT_KEY )
			->id( $id )
			->key( $key );

		return $result;
	}


	/**
	 * Checks if user came from first-click-free available domains
	 *
	 * @param string $referrer
	 * @param string $homeUrl
	 *
	 * @return bool
	 */
	public function isFirstClickFree( $referrer, $homeUrl ) {
		if ( ! $referrer ) {
			return false;
		}
		$referrerDomain = parse_url( $referrer );
		$referrerDomain = isset( $referrerDomain['host'] ) ? $referrerDomain['host'] : false;

		if ( ! $referrerDomain ) {
			return false;
		}

		$homeDomain = parse_url( $homeUrl );
		$homeDomain = isset( $homeDomain['host'] ) ? $homeDomain['host'] : false;
		if ( ! $homeDomain || ( $homeDomain == $referrerDomain ) ) {
			return false;
		}

		// Get configured domains
		$domains = $this->getFirstClickDomains();

		switch ( $this->firstClickMode() ) {
			case self::FIRST_CLICK_OPTION_INCLUDE:
				// If domain must be one if the configured domains
				if ( in_array( $referrerDomain, $domains ) ) {
					return true;
				}

				return false;
			case self::FIRST_CLICK_OPTION_EXCLUDE:
				// If domain must be none if the configured domains
				if ( ! in_array( $referrerDomain, $domains ) ) {
					return true;
				}

				return false;
			case self::FIRST_CLICK_OPTION_ALL:
				// If all domains are first-click-free
				return true;
			default:
				// If first-click-free is disabled
				return false;
		}
	}

	/**
	 * Get domains configured to be enabled / disabled for first-click free
	 * @return array Array of domains
	 */
	private function getFirstClickDomains() {
		$rawDomains = explode( "\n", $this->firstClickRef() );
		$domains    = array();

		foreach ( $rawDomains as $rawDomain ) {
			$domain = parse_url( trim( $rawDomain ) );
			$domain = isset( $domain['host'] ) ? $domain['host'] : false;
			if ( ! $domain ) {
				$domain = $rawDomain;
			}
			if ( $domain ) {
				$domains[] = $domain;
			}
		}

		return $domains;
	}
}
<?php

/**
 * Class TinypassContentSettings
 *
 * Helper class which represents access settings to the content
 *
 * @method TinypassContentSettings|mixed chargeOption() chargeOption( $chargeOption = null )
 * @method string chargeOptionPropertyName() chargeOptionPropertyName()
 * @method TinypassContentSettings|mixed pppPrice() pppPrice( $pppPrice = null )
 * @method string pppPricePropertyName() pppPricePropertyName()
 * @method TinypassContentSettings|mixed pppCurrency() pppCurrency( $pppCurrency = null )
 * @method string pppCurrencyPropertyName() pppCurrencyPropertyName()
 * @method TinypassContentSettings|mixed resourceIds() resourceIds( $resourceIds = null )
 * @method string resourceIdsPropertyName() resourceIdsPropertyName()
 * @method TinypassContentSettings|mixed payPerPostTermId() payPerPostTermId( $payPerPostTermId = null )
 * @method string payPerPostTermIdPropertyName() payPerPostTermIdPropertyName()
 * @method TinypassContentSettings|mixed payPerPostResourceId() payPerPostResourceId( $payPerPostResourceId = null )
 * @method string payPerPostResourceIdPropertyName() payPerPostResourceIdPropertyName()
 * @method TinypassContentSettings|mixed id() id( $id = null )
 * @method string idPropertyName() idPropertyName()
 * @method TinypassContentSettings|mixed url() url( $url = null )
 * @method string urlPropertyName() urlPropertyName()
 * @method TinypassContentSettings|mixed name() name( $name = null )
 * @method string namePropertyName() namePropertyName()
 * @method TinypassContentSettings|mixed meta() meta( $meta = null )
 * @method string metaPropertyName() metaPropertyName()
 * @method TinypassContentSettings|mixed algorithmicKeyed() algorithmicKeyed( $algorithmicKeyed = null )
 * @method string algorithmicKeyedPropertyName() algorithmicKeyedPropertyName()
 */
class TinypassContentSettings extends TinypassBuildable {

	const CHARGE_OPTION_ALWAYS = 'always'; // Always display paywall
	const CHARGE_OPTION_METERED = 'metered'; // Display metered paywal
	const CHARGE_OPTION_SUBSCRIPTION = 'subscription'; // Consumer is required to have certain subscriptions
	const CHARGE_OPTION_ALGORITHMIC = 'algorithmic'; // Content is keyed based on algorithm

	// Slug name for resource of pay-per-post
	const PPP_RESOURCE_SLUG = 'ppp';

	const HTML_CONTAINER_ID_PREFIX = 'tinypass-container-';

	protected $chargeOption;
	protected $pppPrice;
	protected $pppCurrency;
	protected $resourceIds;
	protected $payPerPostTermId;
	protected $payPerPostResourceId;
	protected $id;
	protected $url;
	protected $name;
	protected $meta;
	protected $algorithmicKeyed;

	public function __call( $method, $arguments ) {
		if ( 'resourceIds' === $method ) {
			if ( ! count( $arguments ) && ! is_array( $this->resourceIds ) ) {
				return array();
			}
		}

		return parent::__call( $method, $arguments );
	}

	/**
	 * Get array representation of this object's data (for saving in database and future retrieval)
	 *
	 * @return array
	 */
	public function toArray() {
		return array(
			$this->idPropertyName()                   => $this->id(),
			$this->chargeOptionPropertyName()         => $this->chargeOption(),
			$this->pppPricePropertyName()             => $this->pppPrice(),
			$this->pppCurrencyPropertyName()          => $this->pppCurrency(),
			$this->resourceIdsPropertyName()          => $this->resourceIds(),
			$this->payPerPostTermIdPropertyName()     => $this->payPerPostTermId(),
			$this->payPerPostResourceIdPropertyName() => $this->payPerPostResourceId(),
			$this->urlPropertyName()                  => $this->url(),
			$this->namePropertyName()                 => $this->name(),
			$this->metaPropertyName()                 => $this->meta(),
			$this->algorithmicKeyedPropertyName()     => $this->algorithmicKeyed()
		);
	}


	/**
	 * Create object from array
	 *
	 * @param array $arraySettings
	 *
	 * @return TinypassContentSettings
	 */
	public static function fromArray( $arraySettings ) {
		$settings = new self();

		if ( $arraySettings && ( is_array( $arraySettings ) ) ) {
			foreach ( $arraySettings as $attribute => $value ) {
				if ( ! is_numeric( $attribute ) ) {
					$settings->$attribute( $value );
				}
			}
		}

		return $settings;
	}

	/**
	 * Is content set up to have pay-per-post
	 *
	 * @return bool
	 */
	public function hasPayPerPost() {
		return in_array( self::PPP_RESOURCE_SLUG, $this->resourceIds() );
	}

	/**
	 * Check if settings are valid. If they are not - update the object to be valid
	 *
	 * @param string $businessModel
	 * @param bool $payPerPostEnabled
	 * @param bool $algorithmicEnabled
	 * @param TinypassResource[] $resources
	 *
	 * @throws Exception
	 */
	public function validate( $businessModel, $payPerPostEnabled, $algorithmicEnabled, $resources = null ) {
		// Check for allowed business model values
		if ( ! in_array( $businessModel, array(
			TinypassConfig::BUSINESS_MODEL_SUBSCRIPTION,
			TinypassConfig::BUSINESS_MODEL_METERED
		) )
		) {
			throw new Exception( __( 'Invalid business model', 'tinypass' ) );
		}

		// In case of "metered paywall" business model - only valid charge option is required
		if ( $businessModel == TinypassConfig::BUSINESS_MODEL_METERED ) {
			// Check for allowed charge options - default to "Don't charge" on invalid or empty charge option
			switch ( $this->chargeOption() ) {
				case $this::CHARGE_OPTION_METERED:
				case $this::CHARGE_OPTION_ALWAYS:
					break;
				default:
					$this->chargeOption( '' );
			}
			// "Metered paywall" business model does not require resource ids or pay-per-post settings
			$this->resourceIds( null );
			$this->pppPrice( null );
			$this->pppCurrency( null );
		} else {
			// Check for allowed charge options - default to "Don't charge" on invalid or empty charge option
			switch ( $this->chargeOption() ) {
				case $this::CHARGE_OPTION_SUBSCRIPTION:
					$resourceIds = $this->resourceIds();

					if ( null !== $resources ) {
						$availableResourceIds = array();
						foreach ( $resourceIds as $resourceId ) {
							if ( ( $resourceId == self::PPP_RESOURCE_SLUG ) || array_key_exists( $resourceId, $resources ) ) {
								$availableResourceIds[] = $resourceId;
							}
						}
						$resourceIds = $availableResourceIds;
					}

					if ( in_array( self::PPP_RESOURCE_SLUG, $resourceIds ) && ! $payPerPostEnabled ) {
						// If one of the resources - slug for pay-per-post, and the pay-per-post is not enabled - remove it from the list of resources
						unset ( $resourceIds[ array_search( self::PPP_RESOURCE_SLUG, $resourceIds ) ] );
						$this->pppPrice( null );
						$this->resourceIds( array_values( $resourceIds ) );
					}
					if ( empty( $resourceIds ) ) {
						// Subscription charge option is available if there are resources exist to subscribe to
						$this->chargeOption( '' );
					}
					break;
				case $this::CHARGE_OPTION_ALGORITHMIC:
					if ( $algorithmicEnabled ) {
						// Algorithmic charge option is available only if algorithmic keying is enabled
						break;
					}
				default:
					$this->chargeOption( '' );
			}
		}

	}

	/**
	 * Get id for HTML container of the content
	 *
	 * @return string
	 */
	public function getHTMLContainerId() {
		return self::HTML_CONTAINER_ID_PREFIX . $this->id();
	}

	/**
	 * Get array of resources ids where pay-per-post resource is replaced with an actual resource id
	 *
	 * @return array
	 */
	public function getFilteredResourceIds() {
		$resourceIds = $this->resourceIds();
		if ( $this->hasPayPerPost() ) {
			unset( $resourceIds[ array_search( self::PPP_RESOURCE_SLUG, $resourceIds ) ] );
			$resourceIds[] = $this->payPerPostResourceId();
		}

		return array_values( $resourceIds );
	}

	/**
	 * Check if provided charge option is available with current configuration
	 *
	 * @param string $businessModel one of the TinypassConfig::BUSINESS_MODEL_SUBSCRIPTION or TinypassConfig::BUSINESS_MODEL_METERED
	 * @param bool $payPerPostEnabled Is pay-per-post enabled for this site
	 * @param bool $algorithmicEnabled Is algorithmic keying available for this site
	 * @param TinypassResource[] $resources Set of available resources
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function isPremium( $businessModel, $payPerPostEnabled, $algorithmicEnabled, $resources ) {
		$this->validate( $businessModel, $payPerPostEnabled, $algorithmicEnabled, $resources );

		return (bool) $this->chargeOption();
	}

	/**
	 * Is content free
	 *
	 * @return bool
	 */
	public function isFree() {
		return ! (bool) $this->chargeOption();
	}

}
<?php

/**
 * Class TinypassResource
 * @method TinypassResource|mixed rid() rid( $id = null )
 * @method string ridPropertyName() ridPropertyName()
 * @method TinypassResource|mixed name() name( $name = null )
 * @method string namePropertyName() namePropertyName()
 * @method TinypassResource|mixed description() description( $description = null )
 * @method string descriptionPropertyName() descriptionPropertyName()
 * @method TinypassResource|TinypassTerm[] terms() terms( $terms = null )
 * @method string termsPropertyName() termsPropertyName()
 * @method TinypassResource|mixed imageUrl() imageUrl( $imageUrl = null )
 * @method string imageUrlPropertyName() imageUrlPropertyName()
 */
class TinypassResource extends TinypassBuildable {
	protected $rid;
	protected $name;
	protected $description;
	protected $imageUrl;
	protected $terms;
	protected $isEnabled;

	/**
	 * Magic method which allows getting and setting protected attributes
	 *
	 * @param $method
	 * @param $arguments
	 *
	 * @return $this|array
	 */
	public function __call( $method, $arguments ) {
		if ( 'terms' === $method ) {
			// If terms are not set (or are not array) - default it to an empty array
			if ( ! count( $arguments ) && ! is_array( $this->terms ) ) {
				return array();
			}
		}

		return parent::__call( $method, $arguments );
	}

	/**
	 * Instantiates the object
	 *
	 * @param array $data Array data representing the resource with terms (from database)
	 * @param bool $enabledOnly Instantiate with only enabled terms
	 */
	public function __construct( $data = array(), $enabledOnly = false ) {
		foreach ( $data as $property => $value ) {
			if ( $property == $this->termsPropertyName() ) {
				foreach ( $value as $termId => $termData ) {
					$term = new TinypassTerm( $termData );
					if ( ! $enabledOnly || $term->isEnabled() ) {
						$this->terms[ $termId ] = $term;
					}
				}
			} elseif ( property_exists( $this, $property ) ) {
				$this->$property = $value;
			}
		}

		return $this;
	}

	/**
	 * Method accepts associative array with similar to TinypassResource data structure and updates values: isEnabled
	 *
	 * @param $data
	 * @return $this
	 */
	public function updateSettings( $data ) {
		foreach ( $this->terms() as $term ) {

			$term->isEnabled( (bool) ( isset( $data[ $this->termsPropertyName() ][ $term->id() ][ $term->isEnabledPropertyName() ] ) ? $data[ $this->termsPropertyName() ][ $term->id() ][ $term->isEnabledPropertyName() ] : false ) );
		}

		return $this;
	}

	/**
	 * Get object as array
	 *
	 * @return array
	 */
	public function asArray() {
		$terms = array();
		foreach ( $this->terms() as $term ) {
			$terms[ $term->id() ] = $term->asArray();
		}

		return array(
			$this->ridPropertyName()         => $this->rid(),
			$this->namePropertyName()        => $this->name(),
			$this->descriptionPropertyName() => $this->description(),
			$this->imageUrlPropertyName()    => $this->imageUrl(),
			$this->termsPropertyName()       => $terms
		);
	}

	/**
	 * Check if resource is enabled by website's settings
	 * @return bool
	 */
	public function isEnabled() {
		foreach ( $this->terms() as $term ) {
			if ( $term->isEnabled() ) {
				// Resource is considered enabled if it has at least one enabled term
				return true;
			}
		}

		return false;
	}
}
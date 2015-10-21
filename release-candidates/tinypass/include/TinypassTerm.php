<?php

/**
 * Class TinypassTerm
 * @method TinypassTerm|mixed id() id( $id = null )
 * @method string idPropertyName() idPropertyName()
 * @method TinypassTerm|mixed name() name( $name = null )
 * @method string namePropertyName() namePropertyName()
 * @method TinypassTerm|mixed description() description( $description = null )
 * @method string descriptionPropertyName() descriptionPropertyName()
 * @method TinypassTerm|mixed billingPlanDescription() billingPlanDescription( $billingPlanDescription = null )
 * @method string billingPlanDescriptionPropertyName() billingPlanDescriptionPropertyName()
 * @method TinypassTerm|mixed isEnabled() isEnabled( $isEnabled = null )
 * @method string isEnabledPropertyName() isEnabledPropertyName()
 */
class TinypassTerm extends TinypassBuildable {
	protected $id;
	protected $name;
	protected $description;
	protected $billingPlanDescription;
	protected $isEnabled;

	/**
	 * Instantiate object with data array from database
	 *
	 * @param array $data
	 */
	public function __construct( $data = array() ) {
		foreach ( $data as $property => $value ) {
			if ( property_exists( $this, $property ) ) {
				$this->$property = $value;
			}
		}

		return $this;
	}

	/**
	 * Get object as array
	 *
	 * @return array
	 */
	public function asArray() {
		return array(
			$this->idPropertyName()                     => $this->id(),
			$this->namePropertyName()                   => $this->name(),
			$this->descriptionPropertyName()            => $this->description(),
			$this->billingPlanDescriptionPropertyName() => $this->billingPlanDescription(),
			$this->isEnabledPropertyName()              => $this->isEnabled()
		);
	}

}
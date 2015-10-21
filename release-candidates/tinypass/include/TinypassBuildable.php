<?php

/**
 * Base class for buildable helper classes
 * Class TinypassBuildable
 */
abstract class TinypassBuildable {
	/**
	 * Magic method which allows getting and setting protected attributes
	 *
	 * @param $method
	 * @param $arguments
	 *
	 * @return $this
	 */
	public function __call( $method, $arguments ) {
		if ( preg_match( "/^(.+)PropertyName$/", $method, $match ) && property_exists( $this, $match[1] ) ) {
			return $match[1];
		}
		if ( property_exists( $this, $method ) ) {
			if ( ! count( $arguments ) ) {
				return $this->$method;
			} else {
				$this->$method = $arguments[0];

				return $this;
			}
		}
	}
}
<?php
namespace Speed_Bumps\Constraints\Elements;

class Factory {

	public static function build( $element_constraint ) {
		$element_constraint = "\Speed_Bumps\Constraints\Elements\\$element_constraint";
		if ( class_exists( $element_constraint ) ) {
			return new $element_constraint();
		} else {
			return new Dummy();
		}
	}
}

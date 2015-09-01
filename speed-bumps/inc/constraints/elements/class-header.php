<?php
namespace Speed_Bumps\Constraints\Elements;

class Header extends Constraint_Abstract {
	public function paragraph_not_contains_element( $paragraph ) {
		if ( 1 === preg_match( '/<h[1-6]|eader/', $paragraph, $matches, PREG_OFFSET_CAPTURE ) ) {
			return false;
		}
		return true;
	}
}

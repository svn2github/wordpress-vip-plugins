<?php
namespace Speed_Bumps\Constraints\Elements;

class Iframe extends Constraint_Abstract {
	public function paragraph_not_contains_element( $paragraph ) {
		if ( false !== stripos( $paragraph, '<iframe' ) ) {
			return false;
		}

		return true;
	}
}

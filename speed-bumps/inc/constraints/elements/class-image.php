<?php
namespace Speed_Bumps\Constraints\Elements;

class Image extends Constraint_Abstract {
	public function paragraph_not_contains_element( $paragraph ) {
		if ( false !== stripos( $paragraph, '<img' ) ) {
			return false;
		}

		return true;
	}
}

<?php
namespace Speed_Bumps\Constraints\Elements;

class Blockquote extends Constraint_Abstract {
	public function paragraph_not_contains_element( $paragraph ) {
		if ( false !== stripos( $paragraph, '<blockquote' ) ) {
			return false;
		}

		return true;
	}
}

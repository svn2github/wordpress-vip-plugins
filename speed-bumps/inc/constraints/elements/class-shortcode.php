<?php
namespace Speed_Bumps\Constraints\Elements;

class Shortcode extends Constraint_Abstract{
	public function paragraph_not_contains_element( $paragraph ) {
		if ( preg_match_all( '/' . get_shortcode_regex() . '/s', $paragraph, $matches, PREG_SET_ORDER ) ) {
			return false;
		}
		return true;
	}
}

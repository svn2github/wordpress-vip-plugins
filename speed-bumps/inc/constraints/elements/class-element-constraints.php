<?php
namespace Speed_Bumps\Constraints\Elements;

use Speed_Bumps\Utils\Text;

/**
 * Constraints for inserting speed bumps relating to surrounding elements.
 *
 * This class holds rules for determining speed bump insertion location based
 * on the contents of the surrounding paragraphs.
 *
 * As with all constraint rules, each rule receieves four arguments as parameters.
 *
 * @param bool $can_insert Logic as returned from other constraint rules.
 * @param array $context Context surround the current point in the document
 * @param array $args Arguments provided in definition of this speed bump
 * @param array $already_inserted array of speed bumps which have already been inserted
 *
 * @return bool True indicates that it is allowable (based on this rule) to insert here, false blocks insertion.
 */
class Element_Constraints {

	/**
	 * Is this insertion spot right before or after a disallowed element?
	 *
	 * Loops through each of the "from_element" defined at speed bump
	 * registration, and blocks insertion if either the previous or following
	 * paragraph contains any of them.
	 *
	 * To add additional element constraints, you must define your own element
	 * class which implements the method `paragraph_not_contains_element`.
	 * The class name will be the uppercased version of the string passed in
	 * from_element at speed bump registration.
	 *
	 * For example if you wanted to add an "hr" contraint, define a class at
	 * `\Speed_Bumps\Constraints\Elements\Hr` with has a method called
	 * "paragraph_not_contains_element" which checks that a string of text
	 * doesn't contain an `<hr>`.
	 */
	public static function meets_minimum_distance_from_elements( $can_insert, $context, $args, $already_inserted ) {

		// Support passing an integer here, which will be treated as a unit of "paragraphs"
		if ( is_int( $args['from_element'] ) ) {
			$args['from_element'] = array( 'paragraphs' => $args['from_element'] );
		}

		if ( ! is_array( $args['from_element'] ) ) {
			return $can_insert;
		}

		$defaults = array_flip( array( 'paragraphs', 'words', 'characters' ) );
		$base_distance_constraints = array_intersect_key( $args['from_element'], $defaults );

		$from_element = array_diff_key( $args['from_element'], $defaults );

		if ( ! empty( $from_element ) ) {

			foreach ( $from_element as $key => $val ) {

				$distance_constraints = $base_distance_constraints;

				if ( is_int( $key ) ) {
					$element_to_check = Factory::build( ucfirst( $val ) );
				} else {
					$element_to_check = Factory::build( ucfirst( $key ) );

					foreach ( array( 'paragraphs', 'words', 'characters' ) as $unit ) {
						if ( isset( $val[ $unit ] ) ) {
							$distance_constraints[ $unit ] = $val[ $unit ];
						}
					}
				}

				foreach ( array_filter( $distance_constraints ) as $unit => $measurement ) {

					$paragraphs_to_check = Text::content_within_distance_of(
						$context['parts'], $context['index'], $unit, $measurement
					);

					foreach ( $paragraphs_to_check as $paragraph ) {
						if ( ! $element_to_check->paragraph_not_contains_element( $paragraph ) ) {
							$can_insert = false;
						}
					}
				}
			}
		}

		return $can_insert;
	}

}

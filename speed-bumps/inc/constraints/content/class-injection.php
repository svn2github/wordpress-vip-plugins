<?php
namespace Speed_Bumps\Constraints\Content;

use Speed_Bumps\Utils\Comparison;
use Speed_Bumps\Utils\Text;

/**
 * Constraints for inserting speed bumps relating to other speed bumps.
 *
 * This class holds rules for determining speed bump insertion location based
 * on the number of other speed bumps which have already been inserted.
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

class Injection {

	/**
	 * Has this particular speedbump already been inserted?
	 *
	 * Blocks a speed bump from being inserted if it has already been inserted
	 * elsewhere in the document the maximum number of times allowable, as set
	 * by the 'maximum_inserts' argument on speed bump registration.
	 */
	public static function less_than_maximum_number_of_inserts( $can_insert, $context, $args, $already_inserted ) {

		$this_speed_bump_insertions = array_filter( $already_inserted,
			function( $insertion ) use ( $args ) { return $insertion['speed_bump_id'] === $args['id']; }
		);

		if ( count( $this_speed_bump_insertions ) >= $args['maximum_inserts'] ) {
			$can_insert = false;
		}

		return $can_insert;
	}

	/**
	 * Has another speed bump been inserted at this index?
	 *
	 * Blocks a speed bump from being inserted if another speed bump has
	 * already been inserted at the current index of the document.
	 */
	public static function no_speed_bump_inserted_here( $can_insert, $context, $args, $already_inserted ) {
		$current_index = $context['index'];

		foreach ( $already_inserted as $index => $element ) {
			if ( $element['index'] === $current_index ) {
				$can_insert = false;
			}
		}

		return $can_insert;
	}

	/**
	 * Is this speed bump far enough away from others to insert here?
	 *
	 * Blocks a speed bump from being inserted if it doesn't mean the
	 * distance defined in the speed bump's 'from_speedbump' registration
	 * arguments.
	 */
	public static function meets_minimum_distance_from_other_inserts( $can_insert, $context, $args, $already_inserted ) {

		// Support passing an integer here, which will be treated as a unit of "paragraphs"
		if ( is_int( $args['from_speedbump'] ) ) {
			$args['from_speedbump'] = array( 'paragraphs' => $args['from_speedbump'] );
		}
		if ( ! is_array( $args['from_speedbump'] ) ) {
			return $can_insert;
		}

		if ( is_int( $args['from_speedbump'] ) ) {
			$base_distance_constraints = array( 'paragraphs' => $args['from_speedbump'] );
			$from_speed_bump = array();
		} else {
			$defaults = array( 'paragraphs' => 1, 'words' => null, 'characters' => null );
			$base_distance_constraints = array_intersect_key( (array) $args['from_speedbump'], $defaults );
			$from_speedbump = array_diff_key( $args['from_speedbump'], $defaults );
		}

		$this_paragraph_index = $context['index'];

		if ( count( $already_inserted ) ) {

			foreach ( $already_inserted as $speed_bump ) {

				$distance_constraints = $base_distance_constraints;

				if ( isset( $from_speedbump[ $speed_bump['speed_bump_id'] ] ) &&
						is_array( $from_speedbump[ $speed_bump['speed_bump_id'] ] ) ) {

					foreach ( array( 'paragraphs', 'words', 'characters' ) as $unit ) {

						if ( isset( $from_speedbump[ $speed_bump['speed_bump_id'] ][ $unit ] ) ) {
							$distance_constraints[ $unit ] = $from_speedbump[ $speed_bump['speed_bump_id'] ][ $unit ];
						}
					}
				}

				$distance = Text::content_between_points( $context['parts'], $speed_bump['index'], $context['index'] );

				foreach ( $distance_constraints as $unit => $measurement ) {

					if ( isset( $args['from_speedbump'][ $speed_bump['speed_bump_id'] ] ) &&
							isset( $args['from_speedbump'][ $speed_bump['speed_bump_id'] ][ $unit ] ) ) {

						$measurement = $args['from_speedbump'][ $speed_bump['speed_bump_id'] ][ $unit ];
					}

					if ( $measurement && Comparison::content_less_than( $unit, $measurement, $distance ) ) {
						$can_insert = false;
					}
				}
			}
		}

		return $can_insert;
	}

}

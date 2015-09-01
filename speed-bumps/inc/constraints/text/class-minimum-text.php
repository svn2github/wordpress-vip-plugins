<?php
namespace Speed_Bumps\Constraints\Text;

use Speed_Bumps\Utils\Text;
use Speed_Bumps\Utils\Comparison;

/**
 * Constraints for inserting speed bumps relating to text length.
 *
 * This class holds rules for determining speed bump insertion location based
 * on the length of the body of text as a whole.
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
class Minimum_Text {

	/**
	 * Is the text long enough to insert this speed bump anywhere?
	 *
	 * Blocks insertion if the content being processed is shorter (in character
	 * length) than the "minimum_content_length" defined in speed bump
	 * registration arguments.
	 */
	public static function content_is_long_enough_to_insert( $can_insert, $context, $args, $already_inserted ) {
		if ( ! isset( $args['minimum_content_length'] ) ) {
			return $can_insert;
		}

		$content = $context['the_content'];

		if ( is_array( $args['minimum_content_length'] ) ) {
			foreach ( array( 'paragraphs', 'words', 'characters' ) as $unit ) {
				if ( isset( $args['minimum_content_length'][ $unit ] ) &&
					Comparison::content_less_than( $unit, $args['minimum_content_length'][ $unit ], $content ) ) {
					$can_insert = false;
				}
			}
		}

		if ( intval( $args['minimum_content_length'] ) ) {
			if ( Comparison::content_less_than( 'characters', intval( $args['minimum_content_length'] ), $content ) ) {
				$can_insert = false;
			}
		}

		return $can_insert;
	}

	/**
	 * Is this point far enough from the start to insert?
	 *
	 * Blocks insertion if the current insertion point is less than the minimum
	 * distance from the start of the content defined in the "from_start"
	 * option, as measured in the usual units of distance (paragraphs/words/characters).
	 *
	 * If an integer is passed, it's treated as a unit of "paragraphs".
	 *
	 */
	public static function meets_minimum_distance_from_start( $can_insert, $context, $args, $already_inserted ) {
		if ( ! isset( $args['from_start'] ) ) {
			return $can_insert;
		}

		if ( is_array( $args['from_start'] ) ) {
			$from_start = array_slice( $context['parts'], 0, $context['index'] + 1 );

			foreach ( array( 'paragraphs', 'words', 'characters' ) as $unit ) {
				if ( isset( $args['from_start'][ $unit ] ) &&
						Comparison::content_less_than( $unit, $args['from_start'][ $unit ], $from_start ) ) {
					$can_insert = false;
				}
			}
		}

		if ( is_int( $args['from_start'] ) ) {
			if ( $args['from_start'] > $context['index'] ) {
				$can_insert = false;
			}
		}

		return $can_insert;
	}

	/**
	 * Is this point far enough from the end to insert?
	 *
	 * Blocks insertion if the current insertion point is less than the minimum
	 * distance from the end of the content defined in the "from_end" option,
	 * as measured in the usual units of distance (paragraphs/words/characters).
	 *
	 * If an integer is passed, it's treated as a unit of "paragraphs".
	 *
	 */
	public static function meets_minimum_distance_from_end( $can_insert, $context, $args, $already_inserted ) {
		if ( ! isset( $args['from_end'] ) ) {
			return $can_insert;
		}

		if ( is_array( $args['from_end'] ) ) {
			$from_end = array_slice( $context['parts'], $context['index'] + 1 );

			foreach ( array( 'paragraphs', 'words', 'characters' ) as $unit ) {
				if ( isset( $args['from_end'][ $unit ] ) &&
						Comparison::content_less_than( $unit, $args['from_end'][ $unit ], $from_end ) ) {
					$can_insert = false;
				}
			}
		}

		if ( is_int( $args['from_end'] ) ) {
			if ( $args['from_end'] >= ( $context['total_paragraphs'] - $context['index'] ) ) {
				$can_insert = false;
			}
		}

		return $can_insert;
	}
}

<?php
namespace Speed_Bumps\Utils;

class Comparison {

	public static function content_less_than( $unit, $measurement, $content ) {
		switch ( $unit ) {
			case 'paragraphs':
				return self::less_than_paragraphs( $measurement, $content );
			case 'words':
				return self::less_than_words( $measurement, $content );
			case 'characters':
				return self::less_than_characters( $measurement, $content );
			default:
				return null;
		}
	}

	public static function less_than_paragraphs( $measurement, $content ) {
		$paragraphs = Text::split_paragraphs( $content );
		return count( $paragraphs ) < $measurement;
	}

	public static function less_than_words( $measurement, $content ) {
		$words = Text::split_words( $content );
		return count( $words ) < $measurement;
	}

	public static function less_than_characters( $measurement, $content ) {
		$characters = Text::split_characters( $content );
		return count( $characters ) < $measurement;
	}

}

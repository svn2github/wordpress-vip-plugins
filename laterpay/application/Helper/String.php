<?php

/**
 * LaterPay string helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Helper_String
{

    /**
     * Get the first given number of words from a string.
     *
     * @param   string  $string
     * @param   int     $word_limit
     *
     * @return  string
     */
    public static function limit_words( $string, $word_limit ) {
        $words = explode( ' ', $string );

        return implode( ' ', array_slice( $words, 0, $word_limit ) );
    }

    /**
     * Determine the number of words to be shown behind an overlay according to the settings supplied by the blog owner.
     *
     * @param string $content
     *
     * @return int $number_of_words
     */
    public static function determine_number_of_words( $content ) {
        $content        = preg_replace( '/\s+/', ' ', $content );
        $total_words    = count( explode( ' ', $content ) );

        $config = laterpay_get_plugin_config();

        $percent        = (int) $config->get( 'content.preview_percentage_of_content' );
        $percent        = max( min( $percent, 100 ), 1 );
        $min            = (int) $config->get( 'content.preview_word_count_min' );
        $max            = (int) $config->get( 'content.preview_word_count_max' );

        $number_of_words = $total_words * ( $percent / 100 );
        $number_of_words = max( min( $number_of_words, $max ), $min );

        return $number_of_words;
    }

    /**
     * Truncate text.
     *
     * Cuts a string to the length of $length and replaces the last characters
     * with an ellipsis, if the text is longer than length.
     *
     * ### Options:
     *
     * - `ellipsis` Will be used as ending and appended to the trimmed string (`ending` is deprecated)
     * - `exact` If false, $text will not be cut mid-word
     * - `html` If true, HTML tags are handled correctly
     *
     * @param string  $text    String to truncate.
     * @param integer $length  Length of returned string, including ellipsis.
     * @param array   $options An array of html attributes and options.
     *
     * @return string Trimmed string.
     *
     * @link http://book.cakephp.org/2.0/en/core-libraries/helpers/text.html#TextHelper::truncate
     */
    public static function truncate( $text, $length = 100, $options = array() ) {
        $default = array(
            'ellipsis'  => ' ...',
            'exact'     => true,
            'html'      => false,
            'words'     => false,
        );
        if ( isset( $options['ending'] ) ) {
            $default['ellipsis'] = $options['ending'];
        } elseif ( ! empty( $options['html'] ) ) {
            $default['ellipsis'] = "\xe2\x80\xa6";
        }
        $options = array_merge( $default, $options );

        $ellipsis = $options['ellipsis'];
        $html     = $options['html'];
        $exact    = $options['exact'];
        $words    = $options['words'];

        if ( ! function_exists( 'mb_strlen' ) ) {
            class_exists( 'Multibyte' );
        }

        if ( $html ) {
            $text = preg_replace( '/<! --(.*?)-->/i', '', $text );
            if ( $words ) {
                $length = mb_strlen( self::limit_words( preg_replace( '/<.*?>/', '', $text ), $length ) );
            }
            if ( mb_strlen( preg_replace( '/<.*?>/', '', $text ) ) <= $length ) {
                return $text;
            }
            $totalLength    = mb_strlen( wp_strip_all_tags( $ellipsis ) );
            $openTags       = array();
            $truncate       = '';

            preg_match_all( '/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER );
            foreach ( $tags as $tag ) {
                if ( ! preg_match( '/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2] ) ) {
                    if ( preg_match( '/<[\w]+[^>]*>/s', $tag[0] ) ) {
                        array_unshift( $openTags, $tag[2] );
                    } elseif ( preg_match( '/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag ) ) {
                        $pos = array_search( $closeTag[1], $openTags, true );
                        if ( $pos !== false ) {
                            array_splice( $openTags, $pos, 1 );
                        }
                    }
                }
                $truncate .= $tag[1];

                $contentLength = mb_strlen( preg_replace( '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3] ) );
                if ( $contentLength + $totalLength > $length ) {
                    $left = $length - $totalLength;
                    $entitiesLength = 0;
                    if ( preg_match_all( '/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE ) ) {
                        foreach ( $entities[0] as $entity ) {
                            if ( $entity[1] + 1 - $entitiesLength <= $left ) {
                                $left--;
                                $entitiesLength += mb_strlen( $entity[0] );
                            } else {
                                break;
                            }
                        }
                    }

                    $truncate .= mb_substr( $tag[3], 0, $left + $entitiesLength );
                    break;
                } else {
                    $truncate .= $tag[3];
                    $totalLength += $contentLength;
                }
                if ( $totalLength >= $length ) {
                    break;
                }
            }
        } else {
            if ( $words ) {
                $length = mb_strlen( self::limit_words( $text, $length ) );
            }
            if ( mb_strlen( $text ) <= $length ) {
                return $text;
            }
            $truncate = mb_substr( $text, 0, $length - mb_strlen( $ellipsis ) );
        }
        if ( ! $exact ) {
            $spacepos = mb_strrpos( $truncate, ' ' );
            if ( $html ) {
                $truncateCheck  = mb_substr( $truncate, 0, $spacepos );
                $lastOpenTag    = mb_strrpos( $truncateCheck, '<' );
                $lastCloseTag   = mb_strrpos( $truncateCheck, '>' );
                if ( $lastOpenTag > $lastCloseTag ) {
                    preg_match_all( '/<[\w]+[^>]*>/s', $truncate, $lastTagMatches );
                    $lastTag = array_pop( $lastTagMatches[0] );
                    $spacepos = mb_strrpos( $truncate, $lastTag ) + mb_strlen( $lastTag );
                }
                $bits = mb_substr( $truncate, $spacepos );
                preg_match_all( '/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER );
                if ( ! empty( $droppedTags ) ) {
                    if ( ! empty( $openTags ) ) {
                        foreach ( $droppedTags as $closingTag ) {
                            if ( ! in_array( $closingTag[1], $openTags, true ) ) {
                                array_unshift( $openTags, $closingTag[1] );
                            }
                        }
                    } else {
                        foreach ( $droppedTags as $closingTag ) {
                            $openTags[] = $closingTag[1];
                        }
                    }
                }
            }
            $truncate = mb_substr( $truncate, 0, $spacepos );
        }
        $truncate .= $ellipsis;

        if ( $html ) {
            foreach ( $openTags as $tag ) {
                $truncate .= '</' . $tag . '>';
            }
        }

        return $truncate;
    }

    /**
     * Encode a variable into JSON, with some sanity checks.
     *
     * @param mixed $data    Variable (usually an array or object) to encode as JSON.
     * @param int   $options Optional. Options to be passed to json_encode(). Default 0.
     * @param int   $depth   Optional. Maximum depth to walk through $data. Must be
     *                       greater than 0. Default 512.
     * @return bool|string The JSON encoded string, or false if it cannot be encoded.
     */
    public static function laterpay_json_encode( $data, $options = 0, $depth = 512 ) {
        return wp_json_encode( $data, $options, $depth );
    }

    /**
     * Get scrambled content.
     *
     * @param string $content Post Content.
     *
     * @return string
     */
    public static function get_scrambled_text( $content ) {
        $final_content = '';
        $tags          = [];

        $allowed_tags = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];

        // Split the content by tags and store all words.
        $words = preg_split( '/<(.*?)>/', $content );

        // Get All Tags from the content.
        preg_match_all( '/<(.*?)>/', $content, $matches, PREG_SET_ORDER, 0 );

        // Store all tags.
        foreach ( $matches as $match ) {
            if ( ! empty( $match[0] ) ) {
                $tags[] = $match[0];
            }
        }

        // Append the words and tags.
        foreach ( $words as $key => $value ) {
            $tag = ( ! empty( $tags[ $key ] ) ) ? $tags[ $key ] : '';

            if ( ! in_array( trim( $tag, '<>/' ), $allowed_tags, true ) ) {
                // Scramble the string before appending to final content.
                $final_content .= self::scramble_text( $value ) . $tag;
            } else {
                $final_content .= $value . $tag;
            }
        }

        return $final_content;
    }

    /**
     * Convert single word to scrambled text.
     *
     * @param string $content_string String with words.
     *
     * @return string
     */
    public static function scramble_text( $content_string ) {
        $scrambled_array = [];
        $words           = explode( ' ', $content_string );

        // Search for string in content and replaced with scrambled text.
        foreach ( $words as $word ) {
            $scrambled_array[] = self::str_rot( $word, wp_rand( 1, 20 ) );
        }
        return implode( ' ', $scrambled_array );
    }

    /**
     * Return random rotated string.
     *
     * Function reference : http://php.net/manual/en/function.str-rot13.php#107475
     *
     * @param string $s String to be rotated.
     * @param int    $n Random number to used for string rotation.
     *
     * @return string
     */
    public static function str_rot( $s, $n = 13 ) {

        static $letters = 'AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz';

        $n = (int) $n % 26;

        if ( ! $n ) {
            return $s;
        }

        if ( $n < 0 ) {
            $n += 26;
        }

        if ( $n === 13 ) {
            return str_rot13( $s );
        }

        $rep = substr( $letters, $n * 2 ) . substr( $letters, 0, $n * 2 );

        return strtr( $s, $letters, $rep );

    }
}

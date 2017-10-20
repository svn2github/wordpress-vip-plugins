<?php

// We use a global variable to keep track of all the players that are embedded
// on a page. If multiple videos with the same player are embedded on the
// same page the library only needs to be injected once.
$jwplayer_shortcode_embedded_players = array();

// Regular shortcode function.
function jwplayer_shortcode_handle( $atts ) {
	jwplayer_log( $atts, true );
	// Check for a api key
	$api_key = get_option( 'jwplayer_api_key' );
	if ( empty( $api_key ) ) {
		return '';
	}
	$keys = array_keys( $atts );
	$r = '/(?P<media>[0-9a-z]{8})(?:[-_])?(?P<player>[0-9a-z]{8})?/i';
	$m = array();
	if ( count( $keys ) > 0 && 0 === $keys[0] && preg_match( $r, $atts[0], $m ) ) {
		unset( $atts[0] );
		$player = ( isset( $m['player'] ) ) ? $m['player'] : null;
		return apply_filters( 'jwplayer_js_embed', jwplayer_shortcode_create_js_embed( $m['media'], $player, $atts ) );
	} else {
		// Legacy shortcode
		return jwplayer_shortcode_handle_legacy( $atts );
	}
}

function jwplayer_shortcode_content_filter( $content = '' ) {
	return jwplayer_shortcode_filter( 'content', $content );
}

function jwplayer_shortcode_excerpt_filter( $content = '' ) {
	return jwplayer_shortcode_filter( 'excerpt', $content );
}

function jwplayer_shortcode_filter( $filter_type = 'content', $content = '' ) {
	$option_name = false;
	if ( is_archive() ) {
		$option_name = 'jwplayer_shortcode_category_filter';
	} elseif ( is_search() ) {
		$option_name = 'jwplayer_shortcode_search_filter';
	} elseif ( is_tag() ) {
		$option_name = 'jwplayer_shortcode_tag_filter';
	} elseif ( is_home() ) {
		$option_name = 'jwplayer_shortcode_home_filter';
	}
	if ( $option_name ) {
		$action = get_option( $option_name );
	} elseif ( 'content' === $filter_type ) {
		$action = $filter_type;
	}
	$tag_regex = '/(.?)\[(jwplayer|jwplatform)\b(.*?)(?:(\/))?\](?:(.+?)\[\/\2\])?(.?)/s';
	if ( $action === $filter_type ) {
		$content = preg_replace_callback( $tag_regex, 'jwplayer_shortcode_parser', $content );
	} elseif ( 'strip' === $action ) {
		$content = preg_replace_callback( $tag_regex, 'jwplayer_shortcode_stripper', $content );
	}
	return $content;
}

function jwplayer_shortcode_widget_text_filter( $content = '' ) {
    $tag_regex = '/(.?)\[(jwplayer)\b(.*?)(?:(\/))?\](?:(.+?)\[\/\2\])?(.?)/s';
    $content = preg_replace_callback( $tag_regex, 'jwplayer_shortcode_parser', $content );
    return $content;
}

function jwplayer_shortcode_parser( $matches ) {
	if ( '[' === $matches[1] && ']' === $matches[6] ) {
		return substr( $matches[0], 1, -1 );
	}
	$param_regex = '/([\w.]+)\s*=\s*"([^"]*)"(?:\s|$)|([\w.]+)\s*=\s*\'([^\']*)\'(?:\s|$)|([\w.]+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
	$text = preg_replace( "/[\x{00a0}\x{200b}]+/u", ' ', $matches[3] );
	$text = preg_replace( '/&#8221;|&#8243;/', '"', preg_replace( '/&#8217;|&#8242;/', "'", $text ) );
	$atts = array();
	if ( preg_match_all( $param_regex, $text, $match, PREG_SET_ORDER ) ) {
		foreach ( $match as $p_match ) {
			if ( ! empty( $p_match[1] ) ) {
				$atts[ $p_match[1] ] = stripcslashes( $p_match[2] );
			} elseif ( ! empty( $p_match[3] ) ) {
				$atts[ $p_match[3] ] = stripcslashes( $p_match[4] );
			} elseif ( ! empty( $p_match[5] ) ) {
				$atts[ $p_match[5] ] = stripcslashes( $p_match[6] );
			} elseif ( isset( $p_match[7] ) && strlen( $p_match[7] ) ) {
				$atts[] = stripcslashes( $p_match[7] );
			} elseif ( isset( $p_match[8] ) ) {
				$atts[] = stripcslashes( $p_match[8] );
			}
		}
	} else {
		$atts = ltrim( $text );
	}
	return $matches[1] . jwplayer_shortcode_handle( $atts ) . $matches[6];
}

function jwplayer_shortcode_stripper( $matches ) {
	if ( '[' === $matches[1] && ']' === $matches[6] ) {
		return substr( $matches[0], 1, -1 );
	}
	return $matches[1] . $matches[6];
}

function jwplayer_shortcode_handle_legacy( $atts ) {
	$jw_sync = get_option( 'jwplayer_enable_sync', JWPLAYER_ENABLE_SYNC );
	// Try to get media
	if ( isset( $atts['mediaid'] ) ) {
		$hash = jwplayer_media_hash( intval( $atts['mediaid'] ), $jw_sync );
		if ( ! isset( $atts['image'] ) ) {
			$thumb = get_post_meta( $atts['mediaid'], 'jwplayermodule_thumbnail', true );
			if ( $thumb ) {
				$atts['image'] = $thumb;
			}
		}
		if ( ! $hash ) {
			$atts['file'] = wp_get_attachment_url( $atts['mediaid'] );
			$hash = 'local';
		}
		unset( $atts['mediaid'] );
	} elseif ( isset( $atts['file'] ) && $jw_sync ) {
		$title = ( isset( $atts['title'] ) ) ? $atts['title'] : null;
		$hash = jwplayer_media_legacy_external_source( $atts['file'], $title );
		unset( $atts['file'] );
	} elseif ( isset( $atts['file'] ) ) {
		$hash = 'local';
	} elseif ( isset( $atts['playlistid'] ) ) {
		$imported_playlists = get_option( 'jwplayer_imported_playlists' );
		if ( $imported_playlists && array_key_exists( $atts['playlistid'], $imported_playlists ) ) {
			$hash = $imported_playlists[ $atts['playlistid'] ];
		}
		unset( $atts['playlistid'] );
	}
	// Try to get player
	$player_hash = null;
	if ( isset( $atts['player'] ) ) {
		$imported_players = get_option( 'jwplayer_imported_players' );
		if ( $imported_players && array_key_exists( $atts['player'], $imported_players ) ) {
			$player_hash = $imported_players[ $atts['player'] ];
		}
	}
	// Return the old stuff
	if ( isset( $hash ) ) {
		return apply_filters( 'jwplayer_js_embed', jwplayer_shortcode_create_js_embed( $hash, $player_hash, $atts ) );
	}
	return '<!-- ERROR PARSING SHORTCODE -->';
}

function jwplayer_shortcode_filter_player_params( $atts ) {
	$params = array();
	$strip = array( 'mediaid', 'playlist', 'playlistid' );
	$translate = array(
		'true'  => true,
		'false' => false,
		'NULL'  => null,
		'null'  => null,
	);

	foreach ( $atts as $param => $value ) {
		if ( is_numeric( $param ) ) {
			continue;
		}
		if ( in_array( $param, $strip, true ) ) {
			continue;
		}
		$value = ( array_key_exists( strval( $value ), $translate ) ) ? $translate[ $value ] : $value;
		if ( strpos( $param, '__' ) ) {
			$parts = explode( '__', $param );
			$last_part = end( $parts );
			$a = &$params;
			foreach ( $parts as $part ) {
				if ( $part === $last_part ) {
					$a[ $part ] = $value;
				} else {
					if ( ! array_key_exists( $part, $a ) ) {
						$a[ $part ] = array();
					}
					$a = &$a[ $part ];
				}
			}
		} else {
			$params[ $param ] = $value;
		}
	}
	return $params;
}

// Create the JS embed code for the jwplayer player
function jwplayer_shortcode_create_js_embed( $media_hash, $player_hash = null, $params = array() ) {
	global $jwplayer_shortcode_embedded_players;
	$player_hash = ( null === $player_hash ) ? get_option( 'jwplayer_player' ) : $player_hash;
	$content_mask = jwplayer_get_content_mask();
	$protocol = ( is_ssl() && JWPLAYER_CONTENT_MASK === $content_mask ) ? 'https' : 'http';

	if ( in_array( $player_hash, $jwplayer_shortcode_embedded_players, true ) ) {
		$player_script = '';
	} else {
		// Injecting script tag because there's no way to properly enqueue a javascript
		// at this point in the process :'-(
		$player_script = true;
		$jwplayer_shortcode_embedded_players[] = $player_hash;
	}

	if ( 'local' == $media_hash ) {
		$element_id = "jwplayer_" . wp_generate_password( 12, false ) . "_div";
	} else {
		$element_id = "jwplayer_{$media_hash}_{$player_hash}_div";
	}

	$timeout = intval( get_option( 'jwplayer_timeout' ) );
	$js_lib = "$protocol://$content_mask/libraries/$player_hash.js";
	$json_feed = "$protocol://$content_mask/feeds/$media_hash.json";
	if ( $timeout > 0 ) {
		$api_secret = get_option( 'jwplayer_api_secret' );
		$expires = time() + 60 * $timeout;

		$js_lib_sig = md5( "libraries/$player_hash.js:" . $expires . ':' . $api_secret );
		$js_lib = "$js_lib?exp=$expires&sig=$js_lib_sig";

		$json_sig = md5( "feeds/$media_hash.json:" . $expires . ':' . $api_secret );
		$json_feed = "$json_feed?exp=$expires&sig=$json_sig";
	}

	$params = jwplayer_shortcode_filter_player_params( $params );
	if ( count( $params ) ) {
		// Support for player tracks.
		foreach ( array( 'sources', 'tracks' ) as $option ) {
			if ( isset( $params[ $option ] ) ) {
				$json = '[' . $params[ $option ] . ']';
				$obj = json_decode( preg_replace( '/[{, ]{1}(\w+):/i', '"\1":', $json ) );
				if ( null === $obj ) {
					$json = str_replace( array( '"',  "'" ), array( '\"', '"' ), $json );
					$obj = json_decode( preg_replace( '/[{, ]{1}(\w+):/i', '"\1":', $json ) );
				}
				$params[ $option ] = $obj;
			}
		}
	}
	if ( ! isset( $params['source'] ) && 'local' != $media_hash ) {
		$params['playlist'] = $json_feed;
	}

	// Set ph value for player hosting source
	$params['ph'] = 2;

	// Redeclare fitVids to stop it from breaking the JW Player embedding.
	if ( JWPLAYER_DISABLE_FITVIDS ) {
		if ( $player_script ) {
			return "
		<script type='text/javascript' src='" . esc_url( $js_lib ) . "'></script>
			<div id='" . esc_attr( $element_id ) . "'></div>
		<script type='text/javascript'>
			" . 'if(typeof(jQuery)=="function"){(function($){$.fn.fitVids=function(){}})(jQuery)};' . "
				jwplayer('" . esc_attr( $element_id ) . "').setup(
				" . wp_json_encode( $params ) . "
			);
		</script>
	";
		} else { // no player script
			return "
			<div id='" . esc_attr( $element_id ) . "'></div>
		<script type='text/javascript'>
			" . 'if(typeof(jQuery)=="function"){(function($){$.fn.fitVids=function(){}})(jQuery)};' . "
				jwplayer('" . esc_attr( $element_id ) . "').setup(
				" . wp_json_encode( $params ) . "
			);
		</script>
	";
		}
	} else {

		// no fitvids script here.
		if ( $player_script ) {
			return "
		<script type='text/javascript' src='" . esc_url( $js_lib ) . "'></script>
			<div id='" . esc_attr( $element_id ) . "'></div>
		<script type='text/javascript'>
				jwplayer('" . esc_attr( $element_id ) . "').setup(
				" . wp_json_encode( $params ) . "
			);
		</script>
	";
		} else { // no player script
			return "
			<div id='" . esc_attr( $element_id ) . "'></div>
		<script type='text/javascript'>
				jwplayer('" . esc_attr( $element_id ) . "').setup(
				" . wp_json_encode( $params ) . "
			);
		</script>
	";
		}
	}
}

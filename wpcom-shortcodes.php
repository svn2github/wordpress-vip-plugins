<?php

/*
Plugin Name: WPCOM Shortcodes
Author: Automattic, Inc.
Description: Adds Youtube and Brightcove shortcodes as found on WordPress.com
*/

/*
This is the code used on WordPress.com.  It needs to be updated though. It should be using WordPress' new Shortcode API: http://codex.wordpress.org/Shortcode_API We'll get that transitioned eventually.
*/

if ( !function_exists( 'youtube_embed_to_short_code' ) ) {
// these functions already exist on wp.com 

// around 2008-06-06 youtube changed their old embed code to this:
//<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/M1D30gS7Z8U&hl=en"></param><embed src="http://www.youtube.com/v/M1D30gS7Z8U&hl=en" type="application/x-shockwave-flash" width="425" height="344"></embed></object>
// old style was:
// <object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/dGY28Qbj76A&rel=0"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/dGY28Qbj76A&rel=0" type="application/x-shockwave-flash" wmode="transparent" width="425" height="344"></embed></object>

function youtube_embed_to_short_code($content) {
	if ( preg_match('!\<object width="\d+" height="\d+"\>\<param name="movie" value="http://www.youtube.com/v/(.+?)"\>\</param\>(?:\<param name="wmode" value="transparent"\>\</param\>)?\<embed src="http://www.youtube.com/v/(.+)" type="application/x-shockwave-flash"(?: wmode="transparent")? width="\d+" height="\d+"\>\</embed\>\</object\>!i', $content, $match) )  {
		$content = preg_replace("!{$match[0]}!", "[youtube=http://www.youtube.com/watch?v={$match[1]}]", $content);
	}

	if ( preg_match('!&lt;object width="\d+" height="\d+"&gt;&lt;param name="movie" value="http://www.youtube.com/v/(.+?)"&gt;&lt;/param&gt;(?:&lt;param name="wmode" value="transparent"&gt;&lt;/param&gt;)?&lt;embed src="http://www.youtube.com/v/(.+)" type="application/x-shockwave-flash"(?: wmode="transparent")? width="\d+" height="\d+"&gt;&lt;/embed&gt;&lt;/object&gt;!i', $content, $match) ) {
		$content = preg_replace("!{$match[0]}!", "[youtube=http://www.youtube.com/watch?v={$match[1]}]", $content);
	}

	return $content;
}
add_filter('pre_kses', 'youtube_embed_to_short_code');

function youtube_markup($content) {
	return preg_replace('|\[youtube[= ](.+?)]|ie', 'youtube_id("$1")', $content);
}

function youtube_id($url) {
	$url = trim($url, ' "');
	$url = str_replace( '/v/', '/?v=', $url ); // new format - http://www.youtube.com/v/jF-kELmmvgA
	$url = str_replace( '&amp;', '&', $url );
	$url = parse_url($url);

	if ( !isset($url['query']) )
		return '<!--YouTube Error: bad URL entered-->';

	parse_str($url['query'], $qargs);

	if ( !isset($qargs['v']) )
		return '<!--YouTube Error: bad URL entered-->';

	$id = preg_replace('|[^_a-z0-9-]|i', '', $qargs['v']);

	if ( is_feed() )
		return '<span style="text-align:center; display: block;"><a href="' . get_permalink() . '"><img src="http://img.youtube.com/vi/' . $id . '/2.jpg" alt="" /></a></span>';

	// default width should be 425 unless the theme's content width is smaller than that
	global $content_width;
	$default_width = ( !empty($content_width) ? min( $content_width, 425) : 425 );

	$w = (isset($qargs['w']) && intval($qargs['w'])) ? intval($qargs['w']) : $default_width;
	$h = ceil($w * 14 / 17);

	$video = "<span style='text-align:center; display: block;'><object width='$w' height='$h'><param name='movie' value='http://www.youtube.com/v/$id'></param><param name='wmode' value='transparent'></param><embed src='http://www.youtube.com/v/$id&rel=0' type='application/x-shockwave-flash' wmode='transparent' width='$w' height='$h'></embed></object></span>";

	return $video;
}

add_filter('the_content', 'youtube_markup');
add_filter('the_content_rss', 'youtube_markup');

} // end of if !function_exists youtube


if ( !function_exists( 'brightcove_markup' ) ) {
// these functions already exist on wp.com 

/*
 * [brightcove exp=627045696&vid=1415670151] for older player and backward compatibility
 * [brightcove exp=1463233149&vref=1601200825] for new player
 */
function brightcove_markup($content) {
	return preg_replace('|\[brightcove (.+?)]|ie', 'brightcove_src("$1")', $content);
}
function brightcove_src($params) {
	if ( faux_faux() )
		return '[brightcove]';

	$params = str_replace('&amp;', '&', $params);
		
	$params = apply_filters( 'brightcove_dimensions',  $params ); 
	
	parse_str($params, $arrrrrghs);

	if ( empty($arrrrrghs) )
		return "<!-- brightcove error: no arrrrrghs -->";

	extract($arrrrrghs);

	$fv = array(
		'viewerSecureGatewayURL' => 'https://services.brightcove.com/services/amfgateway',
		'servicesURL' => 'http://services.brightcove.com/services',
		'cdnURL' => 'http://admin.brightcove.com',
		'autoStart' => 'false'
	);
	if ( empty( $exp ) || !ctype_digit($exp) ) {
		if ( empty( $vid ) || !ctype_digit($vid) )
			return "<!-- brightcove error: id is missing or is not all digits -->";
		$src = 'http://admin.brightcove.com/destination/player/player.swf';
		$name = 'bcPlayer';
		$fv['initVideoId'] = $vid;
	} else {
		$src = "http://services.brightcove.com/services/viewer/federated_f8/$exp";
		$name = 'flashObj';
		if ( $vid )
			$fv['videoId'] = $vid;
		else if ( $vref )
			$fv['videoRef'] = $vref; 
			
		$fv['playerId'] = $exp;
		$fv['domain'] = 'embed';
	}

	if ( !empty($lbu) )
		$fv['linkBaseURL'] = $lbu;

	$flashvars = trim(add_query_arg($fv, ''), '?');

	if ( !empty($w) && !empty($h) ) {
		$w = abs((int) $w);
		$h = abs((int) $h);
		if ( $w && $h ) {
			$width = $w;
			$height = $h;
		}
	} elseif ( empty( $s ) || $s == 'l' ) {
		$width = '480';
		$height = '360';
	}

	if ( empty($width) || empty($height) ) {
		$width = '280';
		$height = '210';
	}

	return "<embed src='$src' bgcolor='#FFFFFF' flashvars='$flashvars' base='http://admin.brightcove.com' name='$name' width='$width' height='$height' allowFullScreen='true' allowScriptAccess='always' seamlesstabbing='false' type='application/x-shockwave-flash' swLiveConnect='true' pluginspage='http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash' />";
}
add_filter('the_content', 'brightcove_markup');

} // end if !function_exists brightcove

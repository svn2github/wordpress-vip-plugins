<?php
/*
 * is_mobile() checks the user agent to determine if the browser is a mobile device
 *
 * By default, is_mobile() visitors get a mobile theme: http://en.support.wordpress.com/themes/mobile-themes/
 * VIP sites can use their own mobile theme by contacting VIP Support.
 *
 * Many of the user agents came from http://www.russellbeattie.com/blog/mobile-browser-detection-in-php
 *
 * VIP version of this is manually synced from WPCOM code.
 */

function is_mobile( $kind = 'any', $return_matched_agent = false ) {
	static $kinds = array( 'smart' => false, 'dumb' => false, 'any' => false );
	static $first_run = true;
	static $matched_agent = '';

	if ( $return_matched_agent )
		return $matched_agent;

	if ( empty( $_SERVER['HTTP_USER_AGENT'] ) || strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ), 'ipad' ) )
		return false;

	if ( $first_run ) {
		$first_run = false;

		$agent = strtolower( $_SERVER['HTTP_USER_AGENT'] );

		$smart_agents = array( 'android', 'iphone', 'ipod', 'maemo' );
		$smart_webkit_agents = array( 'webos' );
		if ( false !== strpos( $agent, 'applewebkit' ) )
			$smart_agents = array_merge( $smart_agents, $smart_webkit_agents );

		foreach ( $smart_agents as $smart_agent ) {
			if ( false !== strpos( $agent, $smart_agent ) ) {
				$kinds['smart'] = true;
				$matched_agent = $smart_agent;
				break;
			}
		}

		if ( !$kinds['smart'] ) { // if smart, we are not dumb so no need to check
			if ( isset( $_SERVER["HTTP_X_WAP_PROFILE"]) ) {
				$kinds['dumb'] = true;
				$matched_agent = 'http_x_wap_profile';
			} elseif ( isset($_SERVER["HTTP_ACCEPT"]) && ( preg_match( "/wap\.|\.wap/i", $_SERVER["HTTP_ACCEPT"] ) || false !== strpos( strtolower($_SERVER['HTTP_ACCEPT'] ), 'application/vnd.wap.xhtml+xml' ) ) ) {
				$kinds['dumb'] = true;
				$matched_agent = 'vnd.wap.xhtml+xml';
			} else {
				$dumb_agents = array( 'alcatel', 'au-mic,', 'audiovox', 'avantgo', 'blackberry', 'blazer',
					'cldc-', 'danger', 'docomo', 'epoc',
					'ericsson,', 'ericy', 'i-mode', 'ipaq', 'j2me', 'midp-', 'mobile',
					'mot-', 'netfront', 'nitro', 'nokia', 'opera mini', 'palm',
					'palmsource', 'panasonic', 'philips', 'pocketpc', 'portalmmm',
					'rover', 'samsung', 'sanyo', 'series60', 'sie-',
					'smartphone', 'sony', 'symbian', 'up.browser', 'up.link',
					'up.link', 'vodafone/', 'wap1.', 'wap2.', 'webos', 'windows ce' );

				foreach ( $dumb_agents as $dumb_agent ) {
					if ( false !== strpos( $agent, $dumb_agent ) ) {
						$kinds['dumb'] = true;
						$matched_agent = $dumb_agent;
						break;
					}
				}
			}
		}

		if ( $kinds['dumb'] || $kinds['smart'] )
			$kinds['any'] = true;
	}

	return $kinds[$kind];
}

function is_bot() {
	static $is_bot = false;
	static $first_run = true;

	if ( $first_run ) {
		$first_run = false;

	/*
		$bot_ips = array( );

		foreach ( $bot_ips as $bot_ip ) {
			if ( $_SERVER['REMOTE_ADDR'] == $bot_ip )
				$is_bot = true;
		}
	*/

		$agent = strtolower( $_SERVER['HTTP_USER_AGENT'] );

		$bot_agents = array(
			'alexa', 'altavista', 'ask jeeves', 'attentio', 'baiduspider', 'chtml generic', 'crawler', 'fastmobilecrawl',
			'feedfetcher-google', 'firefly', 'froogle', 'gigabot', 'googlebot', 'heritrix', 'ia_archiver', 'irlbot',
			'infoseek', 'jumpbot', 'lycos', 'mediapartners', 'mediobot', 'motionbot', 'msnbot', 'mshots', 'openbot',
			'pythumbnail', 'scooter', 'slurp', 'snapbot', 'spider', 'surphace scout', 'taptubot', 'technoratisnoop',
			'teoma', 'twiceler', 'yahooseeker', 'yahooysmcm', 'yammybot' );

		foreach ( $bot_agents as $bot_agent ) {
			if ( false !== strpos( $agent, $bot_agent ) )
				$is_bot = true;
		}
	}

	return $is_bot;
}

/*
  is_ipad() can be used to check the User Agent for an iPad device

  They type can check for any iPad, an iPad using Safari, or an iPad using something other than Safari
*/

function is_ipad( $type = 'ipad-any' ) {
	$is_ipad = ( false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ), 'ipad') );
	$is_safari = ( false !== strpos( strtolower( $_SERVER['HTTP_USER_AGENT'] ), 'safari' ) );

	if ( 'ipad-safari' == $type )
		return $is_ipad && $is_safari;
	elseif ( 'ipad-not-safari' == $type )
		return $is_ipad && !$is_safari;
	else
		return $is_ipad;
}

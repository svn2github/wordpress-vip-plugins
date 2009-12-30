<?php
/*
 * is_mobile() checks the user agent to determine if the browser is a mobile device
 *
 * An is_mobile() visitor will have the page directly generated. This is different from regular (not logged in)
 * visitors, where the web page is served from cache (batcache) (php code is not rerun for each visitor).
 *
 * By default, is_mobile() visitors get a mobile theme: http://en.support.wordpress.com/themes/mobile-themes/
 * VIP sites can use their own mobile theme by contacting VIP Support.
 *
 * Many of the user agents came from http://www.russellbeattie.com/blog/mobile-browser-detection-in-php
 */

function is_mobile( $kind = 'any', $return_matched_agent = false ) {
	static $kinds = array('smart' => false, 'dumb' => false, 'any' => false);
	static $first_run = true;
	static $matched_agent = '';

	if ( $return_matched_agent )
		return $matched_agent;

	if ( empty( $_SERVER['HTTP_USER_AGENT'] ) )
		return false;

	if ( $first_run ) {
		$first_run = false;

		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);

		$bot_agents = array(
			'attentio', 'baiduspider', 'chtml generic', 'fast crawler', 'fastmobilecrawl', 'googlebot', 
			'heritrix', 'irlbot', 'jumpbot', 'mediapartners', 'mediobot', 'motionbot', 'msnbot', 
			'nokia6230i/. fast crawler', 'slurp', 'spider', 'teoma', 'twiceler', 
			'yahooseeker', 'yahooysmcm');

		// if UA is a bot, do not count as mobile
		foreach ( $bot_agents as $bot_agent ) {
			if ( false !== strpos( $agent, $bot_agent ) ) {
				return false;
			}
		}

		$smart_agents = array('android', 'iphone', 'ipod', 'maemo');
		$smart_webkit_agents = array('webos');
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
				$dumb_agents = array('alcatel', 'au-mic,', 'audiovox', 'avantgo', 'blackberry', 'blazer',
					'cldc-', 'danger', 'docomo', 'epoc',
					'ericsson,', 'ericy', 'i-mode', 'ipaq', 'j2me', 'midp-', 'mobile',
					'mot-', 'netfront', 'nitro', 'nokia', 'opera mini', 'palm',
					'palmsource', 'panasonic', 'philips', 'pocketpc', 'portalmmm',
					'rover', 'samsung', 'sanyo', 'series60', 'sie-',
					'smartphone', 'sony', 'symbian', 'up.browser', 'up.link',
					'up.link', 'vodafone/', 'wap1.', 'wap2.', 'webos', 'windows ce');

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

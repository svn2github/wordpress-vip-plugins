<?php

/*
  Plugin Name: TinyPass:Metered
  Plugin URI: http://www.tinypass.com
  Description: TinyPass:Metered allows for metered access to your WordPress site
  Author: Tinypass
  Version: 1.0.8
  Author URI: http://www.tinypass.com
 */

define('TINYPASSS_PLUGIN_PATH', plugins_url('', __FILE__));

register_activation_hook(__FILE__, 'tinypass_activate');
register_deactivation_hook(__FILE__, 'tinypass_deactivate');
register_uninstall_hook(__FILE__, 'tinypass_uninstall');

if (!class_exists('TPMeterState')) {

	class TPMeterState {

		public $embed_meter = null;
		public $track_page_view = false;
		public $paywall_id = 0;
		public $sandbox = 0;
		public $on_show_offer = null;

		public function reset() {
			$this->embed_meter = null;
			$this->track_page_view = false;
			$this->paywall_id = 0;
			$this->sandbox = 0;
			$this->on_show_offer = null;
		}

	}

}

$tpmeter = new TPMeterState();

//setup
if (is_admin()) {
	require_once dirname(__FILE__) . '/tinypass-admin.php';
}

add_action('init', 'tinypass_init');
add_action('wp_enqueue_scripts', 'tinypass_enqueue_scripts');
add_action('wp_footer', 'tinypass_footer');

function tinypass_init() {
	global $more;

	//process readon ajax requests and return the content without the teaser
	if (tinypass_is_readon_request()) {
		$id = (int) $_REQUEST['_p'];
		$query = new WP_Query(array('post_type' => 'any', 'p' => $id));

		if (!$query->have_posts()) {
			header("HTTP/1.0 404 Not Found");
			exit;
		}

		$post = $query->the_post();

		$more = true;
		$content = get_the_content("");

		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);

		$c = tinypass_split_excerpt_and_body($content, false);

		$content = $c['body'];

		echo $content;
		exit;
	}

	add_filter('the_content', 'tinypass_intercept_content', 5);
}

/**
 * Add the js-readon script
 */
function tinypass_enqueue_scripts() {
	wp_enqueue_script('tp-readon', TINYPASSS_PLUGIN_PATH . '/js/tp-readon.js', array('jquery'), true, true);
}

/**
 * This method determines if the tinypass-meter needs to be
 * embeded at the bottom of the page.
 * 
 * If the post is tagged and the request is for a page then we will embed
 * 
 * If the request is the home page it is embeded but not configured to track onLoad
 */
function tinypass_intercept_content($content) {

	global $tpmeter;
	global $post;


	if (tinypass_is_readon_request())
		return $content;

	tinypass_include();

	$ss = tinypass_load_settings();

	//break out if Tinypass is disabled
	if ($ss->isEnabled() == false)
		return $content;

	$storage = new TPStorage();

	//or non-subscribers metered should be ignored
	$tpmeter->embed_meter = true;

	$pwOptions = $storage->getPaywall("pw_config");

	if ($pwOptions->isDisabledForPriviledgesUsers() && is_user_logged_in() && current_user_can('edit_posts')) {
		$tpmeter->embed_meter = false;
	}

	//NOOP if pw is disabled or the wrong mode
	if ($pwOptions->isEnabled() == false || $pwOptions->isMode(TPPaySettings::MODE_METERED_LIGHT) == false)
		return $content;

	if (is_home()) {
		$tpmeter->track_page_view = $pwOptions->isTrackHomePage();
	} else {
		//check if current post is tagged for restriction
		$post_terms = get_the_tags($post->ID);
		if ($post_terms) {
			foreach ($post_terms as $term) {
				if ($pwOptions->tagMatches($term->name)) {
					$tpmeter->track_page_view = true;
					break;
				}
			}
		}
	}

	$tpmeter->paywall_id = $pwOptions->getPaywallID($ss->isProd());
	$tpmeter->sandbox = $ss->isSand();

	if (is_home() && ($pwOptions->isReadOnEnabled())) {
		$c = tinypass_split_excerpt_and_body($post->post_content, false);

		$content = $c['excerpt'];

		//we only want to show if there is a readmore or tpmore
		if ($c['body'] && $c['body'] != "") {
			$permalink = get_permalink();
			$url = $permalink . (preg_match("/\?/", $permalink) ? "&" : "?") . "tp-readon=fetch&_p=" . $post->ID;

			$id = hash('md5', $permalink);
			$content .= '<div id="slot-' . $id . '" class="extended" style="display:none"></div>';
			$content .= apply_filters('tinypass_readon', '<a href="' . get_permalink() . "\" readon_desc=\"Read On\" collapse_desc=\"Collapse Post\" id=\"$id\" url=\"$url\" class=\"readon-link\">Read On</a>", $id, $url);
		}
	} else if (is_singular()) {
		$tpmeter->on_show_offer = 'onPostPageShowOffer';
		$c = tinypass_split_excerpt_and_body($post->post_content);
		$content = $c['excerpt'] . "<br>" . $c['body'];
	}

	return $content;
}

/**
 * Trims a string based on WP settings
 */
function tinypass_trim_excerpt($text) {

	$excerpt_length = apply_filters('excerpt_length', 100);

	//$text = wp_strip_all_tags($text);

	$words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
	if (count($words) > $excerpt_length) {
		array_pop($words);
		$text = implode(' ', $words);
	} else {
		$text = implode(' ', $words);
	}
	return $text;
}

/**
 * Helper method to include tinypass related files
 */
function tinypass_include() {
	include_once dirname(__FILE__) . '/util/TPStorage.php';
	include_once dirname(__FILE__) . '/util/TPPaySettings.php';
	include_once dirname(__FILE__) . '/util/TPSiteSettings.php';
	include_once dirname(__FILE__) . '/util/TPValidate.php';
}

/**
 * Load and init global tinypass settings
 */
function tinypass_load_settings() {
	$storage = new TPStorage();
	$ss = $storage->getSiteSettings();
	return $ss;
}

/*
 * Check if the incoming request is a read on request
 */
function tinypass_is_readon_request() {
	$result = false;

	$header = '';

	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']))
		$header = $_SERVER['HTTP_X_REQUESTED_WITH'];

	if (strtolower($header) == 'xmlhttprequest') {
		if (isset($_REQUEST['tp-readon']) && $_REQUEST['tp-readon'] == 'fetch') {
			$result = true;
		}
	}
	return $result;
}

/**
 * Split the content by more or tp more
 */
function tinypass_split_excerpt_and_body($post, $surround = true) {

	$regex = '/(<p>)?\s*<!--more(.*?)?-->(<\/p>)?|(<p>)?\s*<span id="(.*)"><\/span>(<\/p>)?\s*/';
	$tpmore_regex = '/(<p>)?\s*<!--tpmore(.*?)?-->(<\/p>)?\s*/';

	if (preg_match($tpmore_regex, $post)) {
		$regex = $tpmore_regex;
	}

	//Match the new style more links
	if (preg_match($regex, $post, $matches)) {
		list($excerpt, $body) = explode($matches[0], $post, 2);
	} else {
		$excerpt = $post;
		$body = '';
	}

	// Strip leading and trailing whitespace
	$excerpt = preg_replace('/^[\s]*(.*)[\s]*$/', '\\1', $excerpt);
	if ($surround)
		$body = preg_replace('/^[\s]*(.*)[\s]*$/', '\\1', "<div id='tpmore'>" . $body . "</div>");
	else
		$body = preg_replace('/^[\s]*(.*)[\s]*$/', '\\1', $body);


	return array('excerpt' => $excerpt, 'body' => $body);
}

/**
 * Footer method to add scripts
 */
function tinypass_footer() {
	global $tpmeter;

	if ($tpmeter->embed_meter) {
		echo "
<script type=\"text/javascript\">
    window._tpm = window._tpm || [];
    window._tpm['paywallID'] = '" . esc_js($tpmeter->paywall_id) . "'; 
    window._tpm['sandbox'] = " . ($tpmeter->sandbox ? 'true' : 'false') . " 
    window._tpm['trackPageview'] = " . ($tpmeter->track_page_view ? 'true' : 'false') . "; 
    window._tpm['onShowOffer'] = '" . ($tpmeter->on_show_offer ? esc_js($tpmeter->on_show_offer) : '') . "'; 
		if(window._tpm['sandbox']) window._tpm['host'] = 'sandbox.tinypass.com';
	
		 (function () {
        var _tp = document.createElement('script');
        _tp.type = 'text/javascript';
        var _host = window._tpm['host'] ? window._tpm['host'] : 'code.tinypass.com';
        _tp.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + _host + '/tpl/d1/tpm.js';
        var s = document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(_tp, s);
    })();

</script>\n\n";
	}
}

?>

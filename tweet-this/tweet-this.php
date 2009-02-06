<?php
/*
Plugin Name: Tweet This
Plugin URI: http://richardxthripp.thripp.com/tweet-this
Description: Adds a "Tweet This Post" link to every post and page. Shortens URLs in advance through <a href="http://th8.us/">Th8.us</a>, eating up only 19 of 140 characters. Customize under Settings > Tweet This.
Author: Richard X. Thripp
Version: 1.2.3-WPCOM
Author URI: http://richardxthripp.thripp.com/
*/

/*
Tweet This is a plugin for WordPress 1.5 - 2.7. Also: WordPress MU.
Copyright 2008-2009  Richard X. Thripp  (email : richardxthripp@thripp.com)
Freely released under Version 2 of the GNU General Public License as published
by the Free Software Foundation, or, at your option, any later version.
*/

function get_tweet_this_short_url() {
	global $id;
	$purl = get_permalink();
	$cached_tt_url = get_post_meta($id, 'tweet_this_url', true);
	if($cached_tt_url && $cached_tt_url != 'getnew') {
		return $cached_tt_url;
	} else {
		if (get_option('tt_url_service') == 'tinyurl') {
			$url = file_get_contents('http://tinyurl.com/api-create.php?url=' . $purl);
		} elseif (get_option('tt_url_service') == 'bit.ly') {
			$url = file_get_contents('http://bit.ly/api?url=' . $purl);
		} elseif (get_option('tt_url_service') == 'is.gd') {
			$url = file_get_contents('http://is.gd/api.php?longurl=' . $purl);
		} elseif (get_option('tt_url_service') == 'metamark') {
			$url = file_get_contents('http://metamark.net/api/rest/simple?long_url=' . $purl);
		} elseif (get_option('tt_url_service') == 'snurl') {
			$url = file_get_contents('http://snurl.com/site/snip?r=simple&link=' . $purl);
		} elseif (get_option('tt_url_service') == 'urlb.at') {
			$url = file_get_contents('http://urlb.at/api/rest/?url=' . urlencode($purl));
		} elseif (get_option('tt_url_service') == 'tweetburner') {
			$ch = curl_init('http://tweetburner.com/links');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "link[url]=$purl");
			$url = curl_exec($ch); curl_close($ch);
		} elseif (get_option('tt_url_service') == 'zi.ma') {
			$url = file_get_contents('http://zi.ma/?module=ShortURL&file=Add&mode=API&url=' . $purl);
		} elseif (get_option('tt_url_service') == 'local') {
			global $id;
			$url = get_bloginfo('url') . '/?p=' . $id;
		} else {
			$url = 'http://' . file_get_contents('http://th8.us/api-tt.php?url=' . $purl);
		}
		if (get_option('tt_url_www') == 'true' && get_option('tt_url_service') != 'tweetburner')
			$url = str_replace('www.', 'http://', $url);
		if (get_option('tt_url_service') != 'local' && (strlen($url) > 30 || $url == 'Error' || $url == 'www.' || $url == 'http://'))
			$url = get_bloginfo('url') . '/?p=' . $id;
		if ($cached_tt_url == 'getnew')
			update_post_meta($id, 'tweet_this_url', $url, 'getnew');
		else
			add_post_meta($id, 'tweet_this_url', $url, true);
		return $url;
	}
}

function tweet_this_short_url() {
	echo get_tweet_this_short_url();
}

function get_tweet_this_trim_title() {
	$title = get_the_title();
	$special = array('&#34;', '&#034;', '&#38;', '&#038;', '&#39;',
	'&#039;', '&#60;', '&#060;', '&#62;', '&#062;', '&#160;', '&#161;',
	'&#162;', '&#163;', '&#164;', '&#165;', '&#166;', '&#167;', '&#168;',
	'&#169;', '&#170;', '&#171;', '&#172;', '&#173;', '&#174;', '&#175;',
	'&#176;', '&#177;', '&#178;', '&#179;', '&#180;', '&#181;', '&#182;',
	'&#183;', '&#184;', '&#185;', '&#186;', '&#187;', '&#188;', '&#189;',
	'&#190;', '&#191;', '&#192;', '&#193;', '&#194;', '&#195;', '&#196;',
	'&#197;', '&#198;', '&#199;', '&#200;', '&#201;', '&#202;', '&#203;',
	'&#204;', '&#205;', '&#206;', '&#207;', '&#208;', '&#209;', '&#210;',
	'&#211;', '&#212;', '&#213;', '&#214;', '&#215;', '&#216;', '&#217;',
	'&#218;', '&#219;', '&#220;', '&#221;', '&#222;', '&#223;', '&#224;',
	'&#225;', '&#226;', '&#227;', '&#228;', '&#229;', '&#230;', '&#231;',
	'&#232;', '&#233;', '&#234;', '&#235;', '&#236;', '&#237;', '&#238;',
	'&#239;', '&#240;', '&#241;', '&#242;', '&#243;', '&#244;', '&#245;',
	'&#246;', '&#247;', '&#248;', '&#249;', '&#250;', '&#251;', '&#252;',
	'&#253;', '&#254;', '&#255;', '&#8211;', '&#8212;', '&#8216',
	'&#8217;', '&#8220;', '&#8221;', '&#8230;', '&#8482;', '&#8243;',
	'&amp;', '&gt;', '&lt;', '&quot;', '’', '“', '”');
	$normal = array('"', '"', '&', '&', '\'', '\'', '<', '<', '>', '>',
	' ', '¡', '¢', '£', '¤', '¥', '¦', '§', '¨', '©', 'ª', '«', '¬', '',
	'®', '¯', '°', '±', '²', '³', '´', 'µ', '¶', '·', '¸', '¹', 'º', '»',
	'¼', '½', '¾', '¿', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É',
	'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', '×',
	'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'Þ', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å',
	'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ñ', 'ò', 'ó',
	'ô', 'õ', 'ö', '÷', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'þ', 'ÿ', '--', '--',
	'\'', '\'', '"', '\'\'', '...', '\'', '"', '&', '>', '<', '"', '\'',
	'"', '"');
	$title = str_replace($special, $normal, $title);
        $title = $title." ";
	if (get_option('tt_tweet_text') == '')
		$tt_len = '13';
	else
		$tt_len = strlen(get_option('tt_tweet_text'));
	if (get_option('tt_url_service') == 'tinyurl')
		$url_len = '26';
	elseif (get_option('tt_url_service') == 'is.gd')
		$url_len = '18';
	elseif (get_option('tt_url_service') == 'urlb.at')
		$url_len = '19';
	elseif (get_option('tt_url_service') == 'bit.ly')
		$url_len = '21';
	elseif (get_option('tt_url_service') == 'snurl')
		$url_len = '23';
	elseif (get_option('tt_url_service') == 'local')
		$url_len = strlen(get_bloginfo('url')) + 7;
	else
		$url_len = '20';
	if (get_option('tt_url_www') == 'true')
		$url_len = ($url_len - 3);
	$len1 = (135 - $tt_len - $url_len);
	$len2 = (130 - $tt_len - $url_len);
	$len3 = (140 - $tt_len - $url_len);
	$title = substr($title,0,$len1);
	$title = substr($title,0,strrpos($title,' '));
	if (strlen($title) > $len2 && strlen($title) < $len3)
		$title = $title . ' ...';
	$title = urlencode($title);
	return $title;
}

function tweet_this_trim_title() {
	echo get_tweet_this_trim_title();
}

// is_preview doesn't exist in WP 1.5.
function tt_is_preview() {
	if (function_exists('is_preview')) is_preview();
}

function tweet_this_link_text() {
	if (get_option('tt_link_text') == '')
		$link_text = 'Tweet This Post';
	else
		$link_text = get_option('tt_link_text');
	$link_s = array('[URL]', '[TITLE]');
	$link_r = array(get_tweet_this_short_url(), get_the_title());
	$link_text = str_replace($link_s, $link_r, $link_text);
	return $link_text;
}

function tweet_this_title_text() {
	if (get_option('tt_title_text') == '')
		$title_text = 'Post to Twitter ([URL])';
	else
		$title_text = get_option('tt_title_text');
	$title_s = array('[URL]', '[TITLE]');
	$title_r = array(get_tweet_this_short_url(), get_the_title());
	$title_text = str_replace($title_s, $title_r, $title_text);
	return $title_text;
}

function tweet_this_code($code) {
	if (!tt_is_preview()) {
		if (get_option('tt_limit_to_posts') == 'true') {
			if (get_option('tt_limit_to_single') == 'true') {
				if (is_single()) return $code;
			} elseif (!is_page()) {
				return $code;
			}
		}
		if (get_option('tt_limit_to_single') == 'true') {
			if (get_option('tt_limit_to_posts') == 'true') {
				if (is_single())
					return $code;
			} elseif (is_single() || is_page()) {
				return $code;
			}
		}
		if (get_option('tt_limit_to_posts') != 'true' && get_option('tt_limit_to_single') != 'true')
			return $code;
	}
}

function get_tweet_this_url() {
	if (get_option('tt_tweet_text') == '')
		$tweet_text = '[URL] [TITLE]';
	else
		$tweet_text = get_option('tt_tweet_text');
	$tweet_s = array('[URL]', '[TITLE]', ' ');
	$tweet_r = array(get_tweet_this_short_url(), get_tweet_this_trim_title(), '+');
	$tweet_text = str_replace($tweet_s, $tweet_r, $tweet_text);
	$url = 'http://twitter.com/home/?status=' . $tweet_text;
	return tweet_this_code($url);
}

function tweet_this_url() {
	echo get_tweet_this_url();
}

function tweet_this_text_link() {
	$link = '<a href="' . get_tweet_this_url() . '" title="' . tweet_this_title_text() . '">' . tweet_this_link_text() . '</a>';
	return tweet_this_code($link);
}

function tweet_this_big() {
	$icon = '<a class="tweet-this" href="' . get_tweet_this_url() .	'" title="' . tweet_this_title_text() . '"></a>';
	return tweet_this_code($icon);
}

function get_tweet_this() {
	if (get_option('tt_icon') == '')
		$icon_file = 'tt.png';
	else
		$icon_file = get_option('tt_icon');
	$icon = 'http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/' . $icon_file;
	$link = '<p><a class="tt-img" href="' . get_tweet_this_url() . '" title="' .
	tweet_this_title_text() . '"><img class="nothumb" src="' . $icon . '" alt="[Post to Twitter]" border="0" /></a>';
	if (get_option('tt_link_text') != '[BLANK]')
		$link .= ' <a class="tt-small" href="' . get_tweet_this_url() . '" title="' . tweet_this_title_text() . '">' . tweet_this_link_text() . '</a>';
	return tweet_this_code($link);
}

function get_tweet_this_small() {
	return get_tweet_this();
}

function tweet_this() {
	echo get_tweet_this();
}

function tweet_this_small() {
	echo get_tweet_this();
}

function get_tt_plurk_this() {
	if (get_option('tt_plurk_icon') == '')
		$icon_file = 'plurk.png';
	else
		$icon_file = get_option('tt_plurk_icon');
	$icon = 'http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/' . $icon_file;
	$plurk = str_replace('http://twitter.com/home', 'http://plurk.com', get_tweet_this_url());
	$plurk_o = array('Tweet', 'Twitter');
	$plurk_n = array('Plurk', 'Plurk');
	$plurk_title = str_replace($plurk_o, $plurk_n, tweet_this_title_text());
	$plurk_link = str_replace($plurk_o, $plurk_n, tweet_this_link_text());
	$link = '<a class="tt-img" href="' . $plurk . '" title="' .
	$plurk_title . '"><img class="nothumb" src="' . $icon . '" alt="[Post to Plurk]" border="0" /></a>';
	if (get_option('tt_link_text') != '[BLANK]')
		$link .= ' <a class="tt-small" href="' . $plurk . '" title="' . $plurk_title . '">' . $plurk_link . '</a>';
	return tweet_this_code($link);
}

function tt_plurk_this() {
	echo get_tt_plurk_this();
}

function get_tt_digg_this() {
	if (get_option('tt_digg_icon') == '')
		$icon_file = 'digg.png';
	else
		$icon_file = get_option('tt_digg_icon');
	$icon = 'http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/' . $icon_file;
	$digg = 'http://digg.com/submit?url=' . get_permalink() . '&title=' . get_tweet_this_trim_title();
	$digg_o = array('Tweet', 'Twitter');
	$digg_n = array('Digg', 'Digg');
	$digg_title = str_replace($digg_o, $digg_n, tweet_this_title_text());
	$digg_link = str_replace($digg_o, $digg_n, tweet_this_link_text());
	$link = '<a class="tt-img" href="' . $digg . '" title="' . $digg_title . '"><img class="nothumb" src="' . $icon . '" alt="[Post to Digg]" border="0" /></a>';
	if (get_option('tt_link_text') != '[BLANK]')
		$link .= ' <a class="tt-small" href="' . $digg . '" title="' . $digg_title . '">' . $digg_link . '</a>';
	return tweet_this_code($link);
}

function tt_digg_this() {
	echo get_tt_digg_this();
}

function get_tt_ping_this() {
	if (get_option('tt_ping_icon') == '')
		$icon_file = 'ping.png';
	else
		$icon_file = get_option('tt_ping_icon');
	$icon = 'http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/' . $icon_file;
	$ping = 'http://ping.fm/ref/?method=microblog&title=' . get_tweet_this_trim_title() . '&link=' . get_permalink();
	$ping_o = array('Tweet', 'Twitter');
	$ping_n = array('Ping', 'Ping');
	$ping_t = array('Ping.fm', 'Ping.fm');
	$ping_title = str_replace($ping_o, $ping_t, tweet_this_title_text());
	$ping_link = str_replace($ping_o, $ping_n, tweet_this_link_text());
	$link = '<a class="tt-img" href="' . $ping . '" title="' . $ping_title . '"><img class="nothumb" src="' . $icon . '" alt="[Post to ping.fm]" border="0" /></a>';
	if (get_option('tt_link_text') != '[BLANK]')
		$link .= ' <a class="tt-small" href="' . $ping . '" title="' . $ping_title . '">' . $ping_link . '</a>';
	return tweet_this_code($link);
}

function tt_ping_this() {
	echo get_tt_ping_this();
}

function insert_tweet_this($content) {
	global $id;
	$tweet_this_hide = get_post_meta($id, 'tweet_this_hide', true);
	if ($tweet_this_hide && $tweet_this_hide != 'false') {
		$content = $content;
	} else {
		if (get_option('tt_big_icon') == 'true' && (get_option('tt_plurk') == 'true' || get_option('tt_digg') == 'true' || get_option('tt_ping') == 'true')) 
			$content .= tweet_this_big() . '<p>';
		elseif (get_option('tt_big_icon') == 'true')
			$content .= tweet_this_big();
		else
			$content .= get_tweet_this();

		if (get_option('tt_plurk') == 'true')
			$content .= ' &nbsp; ' . get_tt_plurk_this();
		if (get_option('tt_digg') == 'true')
			$content .= ' &nbsp; ' . get_tt_digg_this();
		if (get_option('tt_ping') == 'true')
			$content .= ' &nbsp; ' . get_tt_ping_this();
	}
	return $content;
}

function tweet_this_css() {
	$height = '37';
	$width = '100';
	$tweet_image = 'http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-logo.png';
	if (get_option('tt_big_icon') == 'true')
		echo '<style type="text/css">a.tweet-this{background:url(' . $tweet_image . ') no-repeat 0px 0px;height:' . $height . 'px;width:' . $width . 'px;float:right;padding-right:3px;margin:5px 0px 0px 10px;} a.tweet-this:hover{background:url(' . $tweet_image . ') no-repeat 0px -37px;height:' . $height . 'px;width:' . $width . 'px;float:right;padding-right:3px;margin:5px 0px 0px 10px;}</style>';
	else
		echo '<style type="text/css">a.tt-img{text-decoration:none;}</style>';
}

function update_tt_options() {
	$ok = false;
	if ($_REQUEST['tt_auto_display'] == 'on') {
		update_option('tt_auto_display', 'true');
		$ok = true;
	} else {
		update_option('tt_auto_display', 'false');
		$ok = true;
	}

	if ($_REQUEST['tt_big_icon'] == 'on') {
		update_option('tt_big_icon', 'true');
		$ok = true;
	} else {
		update_option('tt_big_icon', 'false');
		$ok = true;
	}

	if ($_REQUEST['tt_limit_to_single'] == 'on') {
		update_option('tt_limit_to_single', 'true');
		$ok = true;
	} else {
		update_option('tt_limit_to_single', 'false');
		$ok = true;
	}

	if ($_REQUEST['tt_limit_to_posts'] == 'on') {
		update_option('tt_limit_to_posts', 'true');
		$ok = true;
	} else {
		update_option('tt_limit_to_posts', 'false');
		$ok = true;
	}

	if ($_REQUEST['tt_plurk'] == 'on') {
		update_option('tt_plurk', 'true');
		$ok = true;
	} else {
		update_option('tt_plurk', 'false');
		$ok = true;
	}
	
	if ($_REQUEST['tt_digg'] == 'on') {
		update_option('tt_digg', 'true');
		$ok = true;
	} else {
		update_option('tt_digg', 'false');
		$ok = true;
	}

	if ($_REQUEST['tt_ping'] == 'on') {
		update_option('tt_ping', 'true');
		$ok = true;
	} else {
		update_option('tt_ping', 'false');
		$ok = true;
	}

	if ($_REQUEST['tt_url_www'] == 'on') {
		if (get_option('tt_url_www') != 'true') {
			update_option('tt_url_www', 'true');
			global_flush_tt_cache(); $ok = true;
		}
	} elseif (get_option('tt_url_www') == 'true') {
			global_flush_tt_cache();
			update_option('tt_url_www', 'false');
			$ok = true;
	}

	if ($_REQUEST['tt_url_service']) {
		$tt_url_service = mysql_real_escape_string($_REQUEST['tt_url_service']);
		if (get_option('tt_url_service') != $tt_url_service) {
			update_option('tt_url_service', $tt_url_service);
			global_flush_tt_cache();
		}
		$ok = true;
	}
	if ($_REQUEST['tt_link_text']) {
		$tt_link_text = mysql_real_escape_string($_REQUEST['tt_link_text']);
		update_option('tt_link_text', $tt_link_text);
		$ok = true;
	}
	if ($_REQUEST['tt_tweet_text']) {
		$tt_tweet_text = mysql_real_escape_string($_REQUEST['tt_tweet_text']);
		update_option('tt_tweet_text', $tt_tweet_text);
		$ok = true;
	}
	if ($_REQUEST['tt_title_text']) {
		$tt_title_text = mysql_real_escape_string($_REQUEST['tt_title_text']);
		update_option('tt_title_text', $tt_title_text);
		$ok = true;
	}
	if ($_REQUEST['tt_icon']) {
		$tt_icon = mysql_real_escape_string($_REQUEST['tt_icon']);
		update_option('tt_icon', $tt_icon);
		$ok = true;
	}
	if ($_REQUEST['tt_plurk_icon']) {
		$tt_plurk_icon = mysql_real_escape_string($_REQUEST['tt_plurk_icon']);
		update_option('tt_plurk_icon', $tt_plurk_icon);
		$ok = true;
	}
	if ($_REQUEST['tt_digg_icon']) {
		$tt_digg_icon = mysql_real_escape_string($_REQUEST['tt_digg_icon']);
		update_option('tt_digg_icon', $tt_digg_icon);
		$ok = true;
	}
	if ($_REQUEST['tt_ping_icon']) {
		$tt_ping_icon = mysql_real_escape_string($_REQUEST['tt_ping_icon']);
		update_option('tt_ping_icon', $tt_ping_icon);
		$ok = true;
	}
	if ($ok)
		echo '<br /><div id="message" class="updated fade"><p>Tweet This options saved!</p></div>';
	else
		echo '<br /><div id="message" class="error fade"><p>Tweet This options could not be saved.</p></div>';
}

function print_tt_form() { ?>
	<style type="text/css">label.t{margin-top:5px;display:block;width:130px;padding:0;float:left;}</style>
	<p>You have <strong><?php
	global $wpdb; echo number_format($wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'publish'"));
	?></strong> published posts and pages. Tweet This has short URLs for <strong><?php
	echo number_format($wpdb->get_var("SELECT COUNT(*) FROM $wpdb->postmeta WHERE meta_key = 'tweet_this_url' AND meta_value != 'getnew'"));
	?></strong> of them.<br />URLs are cached as needed. Tokens for the 
	custom text fields: <strong>[TITLE]</strong>, <strong>[URL]</strong>.</p>
	<form id="tweet-this" name="tweet-this" method="post"><p>
	<label class="t" for="tt_url_service">URL Service:</label>
	<select name="tt_url_service"><option value="th8.us"<?php if (get_option('tt_url_service') == "th8.us") echo ' selected="selected"';
	?>>Th8.us (<?php if (get_option('tt_url_www') == 'true') echo '16';
	else echo '19'; ?> Characters)</option><option value="is.gd"<?php if (get_option('tt_url_service') == "is.gd") echo ' selected="selected"';
	?>>is.gd (<?php if (get_option('tt_url_www') == 'true') echo '14';
	else echo '17'; ?> Characters)</option><option value="urlb.at"<?php if (get_option('tt_url_service') == "urlb.at") echo ' selected="selected"';
	?>>urlb.at (<?php if (get_option('tt_url_www') == 'true') echo '15';
	else echo '18'; ?> Characters)</option><option value="zi.ma"<?php if (get_option('tt_url_service') == "zi.ma") echo ' selected="selected"';
	?>>Zi.ma (<?php if (get_option('tt_url_www') == 'true') echo '16';
	else echo '19'; ?> Characters)</option><option value="bit.ly"<?php if (get_option('tt_url_service') == "bit.ly") echo ' selected="selected"';
	?>>bit.ly (<?php if (get_option('tt_url_www') == 'true') echo '17';
	else echo '20'; ?> Characters)</option><option value="metamark"<?php if (get_option('tt_url_service') == "metamark") echo ' selected="selected"';
	?>>Metamark.net (<?php if (get_option('tt_url_www') == 'true') echo '17';
	else echo '20'; ?> Characters)</option><option value="snurl"<?php if (get_option('tt_url_service') == "snurl") echo ' selected="selected"';
	?>>Snurl.com (<?php if (get_option('tt_url_www') == 'true') echo '19';
	else echo '22'; ?> Characters)</option><option value="tweetburner"<?php if (get_option('tt_url_service') == "tweetburner") echo ' selected="selected"';
	?>>Tweetburner.com (21 Characters)</option><option value="tinyurl"<?php if (get_option('tt_url_service') == "tinyurl") echo ' selected="selected"';
	?>>TinyURL.com (<?php if (get_option('tt_url_www') == 'true') echo '22';
	else echo '25'; ?> Characters)</option><option value="local"<?php if (get_option('tt_url_service') == "local") echo ' selected="selected"';
	?>>Local, i.e. <?php $local = get_bloginfo('url');
	$local_www = str_replace('http://', 'www.', $local);
	if (get_option('tt_url_www') == 'true') echo $local_www; else echo $local;
	echo '/?p=123 ('; if (get_option('tt_url_www') == 'true') $chars =
	strlen(get_bloginfo('url')) + 4; else $chars = strlen(get_bloginfo('url'))
	+ 7; echo $chars; ?> Characters)</option></select></p>
	<p><label class="t" for="note">Note:<br /><br /><br /></label> For no link text, enter 
	<strong>[BLANK]</strong> in the box below.<br />For Plurk This, "Tweet" 
	and "Twitter" will be replaced by "Plurk."<br />For Digg This, "Tweet" 
	and "Twitter" will be replaced by "Digg."<br />For Ping This, "Tweet" 
	and "Twitter" will be replaced by "Ping."</p>
	<p><label class="t" for="tt_link_text">Link Text:</label>
	<input type="text" name="tt_link_text" id="tt_link_text" size="50" value="<?php
	if (get_option('tt_link_text') == '') echo 'Tweet This Post';
	else echo get_option('tt_link_text'); ?>" /></p>
	<p><label class="t" for="tt_tweet_text">Tweet Text:</label>
	<input type="text" name="tt_tweet_text" id="tt_tweet_text" size="50" value="<?php
	if (get_option('tt_tweet_text') == '') echo '[URL] [TITLE]';
	else echo get_option('tt_tweet_text'); ?>" /></p>
	<p><label class="t" for="tt_title_text">Popup Title Text:</label>
	<input type="text" name="tt_title_text" id="tt_title_text" size="50" value="<?php
	if (get_option('tt_title_text') == '') echo 'Post to Twitter ([URL])';
	else echo get_option('tt_title_text'); ?>" /></p>
	<p><label><input type="checkbox" name="tt_auto_display"<?php if (get_option('tt_auto_display') != 'false') echo ' checked="checked"';
	?> /> Automatically insert Tweet This links</label></p>
	<p><label><input type="checkbox" name="tt_big_icon"<?php if (get_option('tt_big_icon') == 'true') echo ' checked="checked"';
	?> /> Insert an alternate large icon at the bottom-right of posts</label></p>
	<p><label><input type="checkbox" name="tt_limit_to_single"<?php if (get_option('tt_limit_to_single') == 'true') echo ' checked="checked"';
	?> /> Only show Tweet This when viewing single posts or pages</label></p>
	<p><label><input type="checkbox" name="tt_limit_to_posts"<?php if (get_option('tt_limit_to_posts') == 'true') echo ' checked="checked"';
	?> /> Hide Tweet This on pages</label></p>
	<p><label><input type="checkbox" name="tt_url_www"<?php if (get_option('tt_url_www') == 'true') echo ' checked="checked"';
	?> /> Use "www." instead of "http://" in shortened URLs</label></p>
	<p><input type="radio" name="tt_icon" value="tt.png"<?php if (get_option('tt_icon') == 'tt.png' || get_option('tt_icon') == '')
	echo ' checked="checked"'; ?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt.png" alt="tt.png" /></input> 
	<input type="radio" name="tt_icon" value="tt-big1.png"<?php if (get_option('tt_icon') == 'tt-big1.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-big1.png" alt="tt-big1.png" /></input> 
	<input type="radio" name="tt_icon" value="tt-big2.png"<?php if (get_option('tt_icon') == 'tt-big2.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-big2.png" alt="tt-big2.png" /></input> 
	<input type="radio" name="tt_icon" value="tt-big3.png"<?php if (get_option('tt_icon') == 'tt-big3.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-big3.png" alt="tt-big3.png" /></input> 
	<input type="radio" name="tt_icon" value="tt-big4.png"<?php if (get_option('tt_icon') == 'tt-big4.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-big4.png" alt="tt-big4.png" /></input> 
	<input type="radio" name="tt_icon" value="tt-micro3.png"<?php if (get_option('tt_icon') == 'tt-micro3.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-micro3.png" alt="tt-micro3.png" /></input> 
	<input type="radio" name="tt_icon" value="tt-micro4.png"<?php if (get_option('tt_icon') == 'tt-micro4.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-micro4.png" alt="tt-micro4.png" /></input></p>
	<p><label><input type="checkbox" name="tt_plurk"<?php if (get_option('tt_plurk') == 'true') echo ' checked="checked"'; 
	?> /> Insert Plurk This links next to Tweet This links</label></p>
	<p><input type="radio" name="tt_plurk_icon" value="plurk.png"<?php if (get_option('tt_plurk_icon') == 'plurk.png' || get_option('tt_plurk_icon') == '')
	echo ' checked="checked"'; ?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/plurk.png" alt="plurk.png" /></input> 
	<input type="radio" name="tt_plurk_icon" value="tt-plurk-big1.png"<?php if (get_option('tt_plurk_icon') == 'tt-plurk-big1.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-plurk-big1.png" alt="tt-plurk-big1.png" /></input> 
	<input type="radio" name="tt_plurk_icon" value="tt-plurk-big2.png"<?php if (get_option('tt_plurk_icon') == 'tt-plurk-big2.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-plurk-big2.png" alt="tt-plurk-big2.png" /></input> 
	<input type="radio" name="tt_plurk_icon" value="tt-plurk-big3.png"<?php if (get_option('tt_plurk_icon') == 'tt-plurk-big3.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-plurk-big3.png" alt="tt-plurk-big3.png" /></input> 
	<input type="radio" name="tt_plurk_icon" value="tt-plurk-big4.png"<?php if (get_option('tt_plurk_icon') == 'tt-plurk-big4.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-plurk-big4.png" alt="tt-plurk-big4.png" /></input> 
	<input type="radio" name="tt_plurk_icon" value="tt-plurk-micro3.png"<?php if (get_option('tt_plurk_icon') == 'tt-plurk-micro3.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-plurk-micro3.png" alt="tt-plurk-micro3.png" /></input> 
	<input type="radio" name="tt_plurk_icon" value="tt-plurk-micro4.png"<?php if (get_option('tt_plurk_icon') == 'tt-plurk-micro4.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-plurk-micro4.png" alt="tt-plurk-micro4.png" /></input></p>
	<p><label><input type="checkbox" name="tt_digg"<?php if (get_option('tt_digg') == 'true') echo ' checked="checked"';
	?> /> Insert Digg This links next to Tweet This links</label></p>
	<p><input type="radio" name="tt_digg_icon" value="digg.png"<?php if (get_option('tt_digg_icon') == 'digg.png' || get_option('tt_digg_icon') == '')
	echo ' checked="checked"'; ?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/digg.png" alt="digg.png" /></input> 
	<input type="radio" name="tt_digg_icon" value="tt-digg-big1.png"<?php if (get_option('tt_digg_icon') == 'tt-digg-big1.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-digg-big1.png" alt="tt-digg-big1.png" /></input> 
	<input type="radio" name="tt_digg_icon" value="tt-digg-big2.png"<?php if (get_option('tt_digg_icon') == 'tt-digg-big2.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-digg-big2.png" alt="tt-digg-big2.png" /></input> 
	<input type="radio" name="tt_digg_icon" value="tt-digg-big3.png"<?php if (get_option('tt_digg_icon') == 'tt-digg-big3.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-digg-big3.png" alt="tt-digg-big3.png" /></input> 
	<input type="radio" name="tt_digg_icon" value="tt-digg-big4.png"<?php if (get_option('tt_digg_icon') == 'tt-digg-big4.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-digg-big4.png" alt="tt-digg-big4.png" /></input> 
	<input type="radio" name="tt_digg_icon" value="tt-digg-micro3.png"<?php if (get_option('tt_digg_icon') == 'tt-digg-micro3.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-digg-micro3.png" alt="tt-digg-micro3.png" /></input> 
	<input type="radio" name="tt_digg_icon" value="tt-digg-micro4.png"<?php if (get_option('tt_digg_icon') == 'tt-digg-micro4.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-digg-micro4.png" alt="tt-digg-micro4.png" /></input></p>
	<p><label><input type="checkbox" name="tt_ping"<?php if (get_option('tt_ping') == 'true') echo ' checked="checked"';
	?> /> Insert Ping This (ping.fm) links next to Tweet This links</label></p>
	<p><input type="radio" name="tt_ping_icon" value="ping.png"<?php if (get_option('tt_ping_icon') == 'ping.png' || get_option('tt_ping_icon') == '')
	echo ' checked="checked"'; ?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/ping.png" alt="ping.png" /></input> 
	<input type="radio" name="tt_ping_icon" value="tt-ping-big1.png"<?php if (get_option('tt_ping_icon') == 'tt-ping-big1.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-ping-big1.png" alt="tt-ping-big1.png" /></input> 
	<input type="radio" name="tt_ping_icon" value="tt-ping-big2.png"<?php if (get_option('tt_ping_icon') == 'tt-ping-big2.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-ping-big2.png" alt="tt-ping-big2.png" /></input> 
	<input type="radio" name="tt_ping_icon" value="tt-ping-big3.png"<?php if (get_option('tt_ping_icon') == 'tt-ping-big3.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-ping-big3.png" alt="tt-ping-big3.png" /></input> 
	<input type="radio" name="tt_ping_icon" value="tt-ping-big4.png"<?php if (get_option('tt_ping_icon') == 'tt-ping-big4.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-ping-big4.png" alt="tt-ping-big4.png" /></input> 
	<input type="radio" name="tt_ping_icon" value="tt-ping-micro3.png"<?php	if (get_option('tt_ping_icon') == 'tt-ping-micro3.png') echo ' checked="checked"';
	?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-ping-micro3.png" alt="tt-ping-micro3.png" /></input> 
	<input type="radio" name="tt_ping_icon" value="tt-ping-micro4.png"<?php	if (get_option('tt_ping_icon') == 'tt-ping-micro4.png') echo ' checked="checked"'; ?>> <img src="http://s3.wordpress.com/wp-content/themes/vip/plugins/tweet-this/icons/tt-ping-micro4.png" alt="tt-ping-micro4.png" /></input></p>
	<p class="submit"><input type="submit" name="submit" value="Save Options" /></p></form>
	<p>Thanks to <a href="http://blog.assbach.de/2009/01/11/freie-tweet-this-buttons/">Sascha</a> for the Tweet This buttons.</p>
	<p>If you like Tweet This, <a href="http://richardxthripp.thripp.com/donate">make a donation</a> to its development.</p>
<?php }

function tweet_this_add_options() {
	if (function_exists('add_options_page')) {
		add_options_page(__('Tweet This Options', 'tweet-this'), __('Tweet This', 'tweet-this'), 8, __FILE__, 'tweet_this_options');
	}
}

function tweet_this_options() {
	echo '<div class="wrap"><h2>Tweet This Options</h2>';
	if ($_POST['submit'])
		update_tt_options();
	print_tt_form();
	echo '</div>';
}

// Sets one cached URL to "getnew". get_tweet_this_short_url() respawns.
function flush_tt_cache($post_id) {
	$cached_tt_url = get_post_meta($post_id, 'tweet_this_url', true);
	if($cached_tt_url && $cached_tt_url != 'getnew') {
		update_post_meta($post_id, 'tweet_this_url', 'getnew');
	}
}

// Deletes the cached URL when you delete one post.
function delete_tt_cache() {
	global $id;
	delete_post_meta($id, 'tweet_this_url');
}

// Sets every cached URL to "getnew". For permalink / URL service changes.
function global_flush_tt_cache() {
	global $wpdb;
	$wpdb->query("UPDATE $wpdb->postmeta SET meta_value = 'getnew' WHERE meta_key = 'tweet_this_url'");
}

// Deletes every cached URL. Triggered upon deactivation.
function global_delete_tt_cache() {
	global $wpdb;
	// Careful here.
	$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = 'tweet_this_url'");
}

add_action('admin_menu', 'tweet_this_add_options');
add_action('publish_post', 'flush_tt_cache');
add_action('publish_future_post', 'flush_tt_cache');
add_action('save_post', 'flush_tt_cache');
add_action('edit_post', 'flush_tt_cache');
add_action('delete_post', 'delete_tt_cache');
add_action('generate_rewrite_rules', 'global_flush_tt_cache');

if (get_option('tt_auto_display') != 'false') {
	add_action('wp_head', 'tweet_this_css');
	add_filter('the_content', 'insert_tweet_this');
}

// register_deactivation_hook doesn't exist before WP 2.0.
if (function_exists('register_deactivation_hook'))
	register_deactivation_hook(__FILE__, 'global_delete_tt_cache');
?>

<?php

// ShareThis
//
// Copyright (c) 2007-2008 Nextumi, Inc.
// http://sharethis.com
//
// Based in part on code Copyright (c) 2006-2007 Alex King
// http://alexking.org/projects/wordpress
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// This is an add-on for WordPress
// http://wordpress.org/
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
// *****************************************************************

/*
Plugin Name: ShareThis
Plugin URI: http://sharethis.com
Description: Let your visitors share a post/page with others. Supports e-mail and posting to social bookmarking sites. <a href="options-general.php?page=sharethis.php">Configuration options are here</a>. Questions on configuration, etc.? Make sure to read the README.
Version: 2.3
Author: ShareThis and Crowd Favorite (crowdfavorite.com)
Author URI: http://sharethis.com
*/

load_plugin_textdomain('sharethis');

if (!function_exists('ak_uuid')) {
	function ak_uuid() {
		return sprintf( 
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x'
			, mt_rand( 0, 0xffff )
			, mt_rand( 0, 0xffff )
			, mt_rand( 0, 0xffff )
			, mt_rand( 0, 0x0fff ) | 0x4000
			, mt_rand( 0, 0x3fff ) | 0x8000
			, mt_rand( 0, 0xffff )
			, mt_rand( 0, 0xffff )
			, mt_rand( 0, 0xffff )
		);
	}
}

function st_install() {
	$publisher_id = get_option('st_pubid');
	$widget = get_option('st_widget');
	if ($publisher_id != "") {
		if ($widget != "") {
			$widget = preg_replace("/\&amp;/", "&", $widget);
			$pattern = "/([\&\?])publisher\=([^\&\"]*)/";
			preg_match($pattern, $widget, $matches);
			if ($matches[0] == "") {
				$widget = preg_replace("/\"\>\s*\<\/\s*script\s*\>/", "&publisher=".$publisher_id."\"></script>", $widget);
				$widget = preg_replace("/widget\/\&publisher\=/", "widget/?publisher=", $widget);
			} elseif ($matches[2] == "") {
				$widget = preg_replace("/([\&\?])publisher\=/", "$1publisher=".$publisher_id, $widget);
			} else {
				if ($publisher_id != $matches[2]) {
					$publisher_id = $matches[2];
				}
			}
		} else {
			$widget = st_default_widget();
			$widget = preg_replace("/\"\>\s*\<\/\s*script\s*\>/", "&publisher=".$publisher_id."\"></script>", $widget);
		}
	} else {
		if ($widget != "") {
			$widget = preg_replace("/\&amp;/", "&", $widget);
			$pattern = "/([\&\?])publisher\=([^\&\"]*)/";
			preg_match($pattern, $widget, $matches);
			if ($matches[0] == "") {
				$publisher_id = ak_uuid();
				$widget = preg_replace("/\"\>\s*\<\/\s*script\s*\>/", "&publisher=".$publisher_id."\"></script>", $widget);
				$widget = preg_replace("/widget\/\&publisher\=/", "widget/?publisher=", $widget);
			} elseif ($matches[2] == "") {
				$publisher_id = ak_uuid();
				$widget = preg_replace("/([\&\?])publisher\=/", "$1publisher=".$publisher_id, $widget);
			} else {
				$publisher_id = $matches[2];
			}
		} else {
			$publisher_id = ak_uuid();
			$widget = st_default_widget();
			$widget = preg_replace("/\"\>\s*\<\/\s*script\s*\>/", "&publisher=".$publisher_id."\"></script>", $widget);
		}
	}

	preg_match("/\<script\s[^\>]*charset\=\"utf\-8\"[^\>]*/", $widget, $matches);
	if ($matches[0] == "") {
		preg_match("/\<script\s[^\>]*charset\=\"[^\"]*\"[^\>]*/", $widget, $matches);
		if ($matches[0] == "") {
			$widget = preg_replace("/\<script\s/", "<script charset=\"utf-8\" ", $widget);
		}
		else {
			$widget = preg_replace("/\scharset\=\"[^\"]*\"/", " charset=\"utf-8\"", $widget);
		}
	}
	preg_match("/\<script\s[^\>]*type\=\"text\/javascript\"[^\>]*/", $widget, $matches);
	if ($matches[0] == "") {
		preg_match("/\<script\s[^\>]*type\=\"[^\"]*\"[^\>]*/", $widget, $matches);
		if ($matches[0] == "") {
			$widget = preg_replace("/\<script\s/", "<script type=\"text/javascript\" ", $widget);
		}
		else {
			$widget = preg_replace("/\stype\=\"[^\"]*\"/", " type=\"text/javascript\"", $widget);
		}
	}

// note: do not convert & to &amp; or append WP version here
	$widget = st_widget_fix_domain($widget);

	if (get_option('st_pubid') == '') {
		update_option('st_pubid', $publisher_id);
	}
	if (get_option('st_widget') == '') {
		update_option('st_widget', $widget);
	}
	if (get_option('st_add_to_content') == '') {
		update_option('st_add_to_content', 'yes');
	}
	if (get_option('st_add_to_page') == '') {
		update_option('st_add_to_page', 'yes');
	}
}
if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
	st_install();
}

function st_widget_head() {
	$widget = get_option('st_widget');
	if ($widget == '') {
		$widget = st_default_widget();
	}

	$widget = st_widget_add_wp_version($widget);
	$widget = st_widget_fix_domain($widget);
	$widget = preg_replace("/\&/", "&amp;", $widget);
	print($widget);
}
add_action('wp_head', 'st_widget_head');

function st_widget() {
	global $post;

	$sharethis = '<script type="text/javascript">SHARETHIS.addEntry({ title: "'.str_replace('"', '\"', strip_tags(get_the_title())).'", url: "'.get_permalink($post->ID).'" });</script>';

	return $sharethis;
}

function st_link() {
	global $post;

	$sharethis = '<p><a href="http://sharethis.com/item?&wp='
		.get_bloginfo('version').'&amp;publisher='
		.get_option('st_pubid').'&amp;title='
		.urlencode(get_the_title()).'&amp;url='
		.urlencode(get_permalink($post->ID)).'">ShareThis</a></p>';

	return $sharethis;
}

function sharethis_button() {
	echo st_widget();
}

function st_remove_st_add_link($content) {
	remove_action('the_content', 'st_add_link');
	remove_action('the_content', 'st_add_widget');
	return $content;
}

function st_add_widget($content) {
	if ((is_page() && get_option('st_add_to_page') != 'no') || (!is_page() && get_option('st_add_to_content') != 'no')) {
		if (!is_feed()) {
			return $content.'<p>'.st_widget().'</p>';
		}
	}		

	return $content;
}

// 2006-06-02 Renamed function from st_add_st_link() to st_add_feed_link()
function st_add_feed_link($content) {
	if (is_feed()) {
		$content .= st_link();
	}

	return $content;
}

// 2006-06-02 Filters to Add Sharethis widget on content and/or link on RSS
// 2006-06-02 Expected behavior is that the feed link will show up if an option is not 'no'
if (get_option('st_add_to_content') != 'no' || get_option('st_add_to_page') != 'no') {
	add_filter('the_content', 'st_add_widget');

	// 2008-08-15 Excerpts don't play nice due to strip_tags().
	add_filter('get_the_excerpt', 'st_remove_st_add_link',9);
	add_filter('the_excerpt', 'st_add_widget');
}

function st_widget_fix_domain($widget) {
	return preg_replace(
		"/\<script\s([^\>]*)src\=\"http\:\/\/sharethis/"
		, "<script $1src=\"http://w.sharethis"
		, $widget
	);
}

function st_widget_add_wp_version($widget) {
	preg_match("/([\&\?])wp\=([^\&\"]*)/", $widget, $matches);
	if ($matches[0] == "") {
		$widget = preg_replace("/\"\>\s*\<\/\s*script\s*\>/", "&wp=".get_bloginfo('version')."\"></script>", $widget);
		$widget = preg_replace("/widget\/\&wp\=/", "widget/?wp=", $widget);
	}
	else {
		$widget = preg_replace("/([\&\?])wp\=([^\&\"]*)/", "$1wp=".get_bloginfo('version'), $widget);
	}
	return $widget;
}

function st_default_widget() {
	return '<script type="text/javascript" charset="utf-8" src="http://w.sharethis.com/widget/?wp='.get_bloginfo('version').'"></script>';
}

if (!function_exists('ak_can_update_options')) {
	function ak_can_update_options() {
		if (function_exists('current_user_can')) {
			if (current_user_can('manage_options')) {
				return true;
			}
		}
		else {
			global $user_level;
			get_currentuserinfo();
			if ($user_level >= 8) {
				return true;
			}
		}
		return false;
	}
}

function st_request_handler() {
	if (!empty($_REQUEST['st_action'])) {
		switch ($_REQUEST['st_action']) {
			case 'st_update_settings':
				if (ak_can_update_options()) {
					if (!empty($_POST['st_widget'])) { // have widget
						$widget = stripslashes($_POST['st_widget']);
						$widget = preg_replace("/\&amp;/", "&", $widget);
						$pattern = "/([\&\?])publisher\=([^\&\"]*)/";
						preg_match($pattern, $widget, $matches);
						if ($matches[0] == "") { // widget does not have publisher parameter at all
							$publisher_id = get_option('st_pubid');
							if ($publisher_id != "") { 
								$widget = preg_replace("/\"\>\s*\<\/\s*script\s*\>/", "&publisher=".$publisher_id."\"></script>", $widget);
								$widget = preg_replace("/widget\/\&publisher\=/", "widget/?publisher=", $widget);
							} else {
								$publisher_id = ak_uuid();
								$widget = preg_replace("/\"\>\s*\<\/\s*script\s*\>/", "&publisher=".$publisher_id."\"></script>", $widget);
								$widget = preg_replace("/widget\/\&publisher\=/", "widget/?publisher=", $widget);
							}
						}
						elseif ($matches[2] == "") { // widget does not have pubid in publisher parameter
							$publisher_id = get_option('st_pubid');
							if ($publisher_id != "") {
								$widget = preg_replace("/([\&\?])publisher\=/", "$1publisher=".$publisher_id, $widget);
							} else {
								$publisher_id = ak_uuid(); 
								$widget = preg_replace("/([\&\?])publisher\=/", "$1publisher=".$publisher_id, $widget);
							}
						} else { // widget has pubid in publisher parameter
							$publisher_id = get_option('st_pubid');
							if ($publisher_id != "") {
								if ($publisher_id != $matches[2]) {
									$publisher_id = $matches[2];
								}
							}  else {
								$publisher_id = $matches[2];
							}
						}
					}
					else { // does not have widget
						$publisher_id = get_option('st_pubid');
						if ($publisher_id == "") {
							$publisher_id = ak_uuid();
						}
						$widget = st_default_widget();
						$widget = preg_replace("/\"\>\s*\<\/\s*script\s*\>/", "&publisher=".$publisher_id."\"></script>", $widget);
						$widget = preg_replace("/widget\/\&publisher\=/", "widget/?publisher=", $widget);
					}
	
					preg_match("/\<script\s[^\>]*charset\=\"utf\-8\"[^\>]*/", $widget, $matches);
					if ($matches[0] == "") {
						preg_match("/\<script\s[^\>]*charset\=\"[^\"]*\"[^\>]*/", $widget, $matches);
						if ($matches[0] == "") {
							$widget = preg_replace("/\<script\s/", "<script charset=\"utf-8\" ", $widget);
						}
						else {
							$widget = preg_replace("/\scharset\=\"[^\"]*\"/", " charset=\"utf-8\"", $widget);
						}
					}
					preg_match("/\<script\s[^\>]*type\=\"text\/javascript\"[^\>]*/", $widget, $matches);
					if ($matches[0] == "") {
						preg_match("/\<script\s[^\>]*type\=\"[^\"]*\"[^\>]*/", $widget, $matches);
						if ($matches[0] == "") {
							$widget = preg_replace("/\<script\s/", "<script type=\"text/javascript\" ", $widget);
						}
						else {
							$widget = preg_replace("/\stype\=\"[^\"]*\"/", " type=\"text/javascript\"", $widget);
						}
					}

// note: do not convert & to &amp; or append WP version here
					$widget = st_widget_fix_domain($widget);
					update_option('st_pubid', $publisher_id);
					update_option('st_widget', $widget);
					
					$options = array(
						'st_add_to_content'
						, 'st_add_to_page'
					);
					foreach ($options as $option) {
						if (isset($_POST[$option]) && in_array($_POST[$option], array('yes', 'no'))) {
							update_option($option, $_POST[$option]);
						}
					}
					
					header('Location: '.get_bloginfo('wpurl').'/wp-admin/options-general.php?page=sharethis.php&updated=true');
					die();
				}
				
				break;
		}
	}
}
add_action('init', 'st_request_handler', 9999);	

function st_options_form() {
	print('
			<div class="wrap">
				<h2>'.__('ShareThis Options', 'sharethis').'</h2>
				<form id="ak_sharethis" name="ak_sharethis" action="'.get_bloginfo('wpurl').'/wp-admin/index.php" method="post">
					<fieldset class="options">

						<script src="http://w.sharethis.com/widget/wordpress/config?publisher='.get_option('st_pubid').'" type="text/javascript"></script>

						<div id="st_widget">

							<p>Paste your widget code in here:</p>
	
							<p><textarea id="st_widget" name="st_widget" style="height: 80px; width: 500px;">'.htmlspecialchars(get_option('st_widget')).'</textarea></p>
						
						</div>
	');
	$options = array(
		'st_add_to_content' => __('Automatically add ShareThis to your posts?*', 'sharethis')
		, 'st_add_to_page' => __('Automatically add ShareThis to your pages?*', 'sharethis')
	);
	foreach ($options as $option => $description) {
		$$option = get_option($option);
		if (empty($$option) || $$option == 'yes') {
			$yes = ' selected="selected"';
			$no = '';
		}
		else {
			$yes = '';
			$no = ' selected="selected"';
		}
		print('
						<p>
							<label for="'.$option.'">'.$description.'</label>
							<select name="'.$option.'" id="'.$option.'">
								<option value="yes"'.$yes.'>'.__('Yes', 'sharethis').'</option>
								<option value="no"'.$no.'>'.__('No', 'sharethis').'</option>
							</select>
						</p>
		');
	}
	print('
						<p>'.__('* Note, if you turn this off, you will want to add the <a href="http://support.sharethis.com/publishers/publishers-faq/wordpress/66">ShareThis template tag</a> to your theme.', 'sharethis').'</p>

					</fieldset>
					<p class="submit">
						<input type="submit" name="submit_button" value="'.__('Update ShareThis Options', 'sharethis').'" />
					</p>
					<input type="hidden" name="st_action" value="st_update_settings" />
				</form>
			</div>
	');
}

function st_menu_items() {
	if (ak_can_update_options()) {
		add_options_page(
			__('ShareThis Options', 'sharethis')
			, __('ShareThis', 'sharethis')
			, 8 
			, basename(__FILE__)
			, 'st_options_form'
		);
	}
}
add_action('admin_menu', 'st_menu_items');

?>
<?php 
/*
Plugin Name: Paged Comments
Plugin URI: http://www.keyvan.net/code/paged-comments/
Description: Breaks down comments into a number of pages 
Author: Keyvan Minoukadeh
Contributors: Brian Dupuis, Jonathan Rawle, Mark Jaquith, kretzschmar, Lisa, WM, Mickey, Stephen R, Geremia T, Alejandro Martinez, Adam Michal Strzelecki
Version: 2.8 (2008-08-19)
Author URI: http://www.keyvan.net/
*/

/*
Copyright 2005-2008 Keyvan Minoukadeh

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/ 

// the config values below should be set in paged-comments-config.php.
// the values below will only be used if paged-comments-config.php does not exist.
$paged_comments->all_posts = true;
$paged_comments->all_pages = true;
$paged_comments->per_page = 10;
$paged_comments->ordering = 'DESC';
$paged_comments->page_range = 11;
$paged_comments->fancy_url = false;
$paged_comments->show_all_option = true;
$paged_comments->fill_last_page = false;
$paged_comments->show_all_ordering = 'ASC';
$paged_comments->default_page = 'auto';

if (file_exists(dirname(__FILE__).'/paged-comments-config.php')) {
	require_once(dirname(__FILE__).'/paged-comments-config.php');
}

if ($paged_comments->fancy_url) {
	add_action('init', 'paged_comments_fancy_url'); // 1.5 compatible
	// add_action('init', 'paged_comments_fancy_url', 10, 0); // not 1.5 compatible
}

// load template file and, if paging is enabled for the post, 
// replace comments_template() call with paged_comments_template()
add_action('template_redirect', 'paged_comments_alter_source', 15);

// load appropriate language text
add_action('init', 'paged_comments_load_language');

// redirect to the correct comment page when a user posts a new comment
add_filter('comment_post_redirect', 'paged_comments_post_redirect_location', 1, 2);

function paged_comments_load_language()
{
	load_plugin_textdomain('paged-comments','/wp-content/plugins/paged-comments/languages/');
}

function paged_comments_post_redirect_location($location, $comment)
{
	global $paged_comments;
	
	$page_count = paged_comments_get_page_count(@$comment->comment_post_ID);
	
	// if paging isn't enabled, or there's only 1 page of comments, or the comment form had a redirect URL, keep location unchanged
	if ($page_count === false || $page_count == 1 || !empty($_POST['redirect_to'])) {
		return $location;
	}
	
	if ($paged_comments->fancy_url && (get_settings('permalink_structure') != '')) {
		$new_location = rtrim(get_permalink($comment->comment_post_ID), '/')."/comment-page-$page_count/#comment-".$comment->comment_ID;  
	} else {
		$new_location = get_permalink($comment->comment_post_ID);
		if (strpos($new_location, '?') !== false) {
			$new_location .= '&';
		} else {
			$new_location .= '?';
		}
		$new_location .= "cp=$page_count#comment-".$comment->comment_ID;
	}
	
	return $new_location;
}

// replaces the is_single() and is_page() cases in template-loader.php
// when paged comments is enabled for the post/page. does essentially
// the same thing but reads the file contents to a variable, replaces
// comments_template() call to paged_comments_template().
function paged_comments_alter_source()
{
	global $wpdb, $post, $comment;
	if (paged_comments()) {
		$file_contents = '';
		$template = '';
		if (is_single()) {
			$template = get_single_template();
		} else if (is_page()) {
			$template = get_page_template();
		}
		if (($template == '') && file_exists(TEMPLATEPATH.'/index.php')) {
			$template = TEMPLATEPATH.'/index.php';
		}
		if ($template) {
			// WP 1.5 doesn't use is_attachment()
			if (function_exists('is_attachment') && is_attachment()) {
				add_filter('the_content', 'prepend_attachment');
			}
			$file_contents = file_get_contents($template);
			// simple check: if call to paged_comments_template() exists, assume all is fine.
			// if it doesn't exist, replace comments_template() with paged_comments_template().
			if (strpos($file_contents, 'paged_comments_template()') === false) {
				extract($GLOBALS, EXTR_SKIP | EXTR_REFS);
				$inc_path = get_include_path();
				set_include_path($inc_path . PATH_SEPARATOR . TEMPLATEPATH);
				$file_contents = str_replace('comments_template()', 'paged_comments_template()', $file_contents);
				//$file_contents = str_replace('<'.'?php','<'.'?',$file_contents);
				eval('?'.'>'.trim($file_contents));
				// I don't think I should be using restore_include_path(), what if other
				// plugins also use set_include_path()? according to php.net
				// restore_include_path restores "back to its original master value as set in php.ini"
				set_include_path($inc_path);
				exit;
			}
		}
	}
}

// return the page count (number of comment pages) of a post identified by $post_id
// returns false if the post is not paged
function paged_comments_get_page_count($post_id)
{
	global $wpdb, $paged_comments;
	
	$post_id = (int)$post_id;
	
	// get comment count
	$approved_condition = paged_comments_get_approved_condition();
	$comment_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = $post_id AND $approved_condition");
	
	// check if paging is enabled for this post (see paged_comments())
	if (!paged_comments($post_id)) {
		return false;
	}
	
	// if the post only contains 1 comment, then it's only got 1 comment page
	if ($comment_count == 1) {
		return 1;
	}
	
	// calculate and return the comment page count
	paged_comments_update_values($post_id);
	$pager =& new Pager($paged_comments->per_page, $comment_count);
	return $pager->num_pages();
}

// check for URL segment identifying the comment page number (or a request for all comments).
// note the page number and strip the segment from REQUEST_URI/PATH_INFO ready for WP to do its
// own parsing. url_to_postid() is used to prevent false positives (cases where a post/page
// happens to have a slug identical to one of the plugin's comment page identifiers).
function paged_comments_fancy_url($var='REQUEST_URI')
{
// WP passes an empty string as argument unless I tell it explicitly that 
// the function takes no parameters. But you can't specify number of args in WP1.5
// so the empty string is replaced below.
if (!in_array($var, array('REQUEST_URI', 'PATH_INFO'))) $var = 'REQUEST_URI';
$req = $_SERVER[$var];
if (preg_match('!^(.+/)comment-page-([0-9]+)/?(.*)?$!', $req, $match) && (url_to_postid($req) == 0)) {
	$_GET['cp'] = $match[2];
	if ($match[3] == '' && substr(get_settings('permalink_structure'), -1) != '/') {
		$match[1] = rtrim($match[1], '/');
	}
	$req = $match[1].$match[3];
	$_SERVER[$var] = $req;
} elseif (preg_match('!^(.+/)all-comments/?(.*)?$!', $req, $match) && (url_to_postid($req) == 0)) {
	$_GET['cp'] = 'all';
	if ($match[2] == '' && substr(get_settings('permalink_structure'), -1) != '/') {
		$match[1] = rtrim($match[1], '/');
	}
	$req = $match[1].$match[2];
	$_SERVER[$var] = $req;
}
// do the same for PATH_INFO
if (($var != 'PATH_INFO') && isset($_SERVER['PATH_INFO'])) {
	paged_comments_fancy_url('PATH_INFO');
}
} 

// override default values with custom post values
function paged_comments_update_values($post_id=null)
{
	global $paged_comments;
	// comments per page
	$val = paged_comments_get_custom('comments_per_page', $post_id);
	if (!empty($val)) $paged_comments->per_page = (int)$val;
	// comment ordering
	$val = strtoupper(paged_comments_get_custom('comment_ordering', $post_id));
	if (($val == 'ASC') || ($val == 'DESC')) $paged_comments->ordering = $val;
}

function paged_comments_get_approved_condition()
{
	global $user_ID;
	
	$commenter = wp_get_current_commenter();
	// extract $comment_author and $comment_author_email
	extract($commenter, EXTR_SKIP);

	if ($user_ID) {
		$approved_condition = "(comment_approved = '1' OR ( user_id = '$user_ID' AND comment_approved = '0' ))";
	} elseif (empty($comment_author)) {
		$approved_condition = "comment_approved = '1'";
	} else {
		$author_db = addslashes($comment_author);
		$email_db  = addslashes($comment_author_email);
		$approved_condition = "(comment_approved = '1' OR (comment_author = '$author_db' AND comment_author_email = '$email_db' AND comment_approved = '0'))";
	}
	return $approved_condition;
}

// Load paged comments template (function based on comments_template() in comment-template.php).
//
// I had initially intended to instruct users to modify template files and include '/comments-paged.php'
// as an argument to comments_template(). Unfortunately, for comment-laden posts, this would have been 
// inefficient (comments_template() issues a query which returns all comments for a post). A better 
// solution, for the purposes of this plugin at least, would be to have comments_template() made 
// "pluggable" (like those functions in pluggable-functions.php). This would allow me to override the 
// function if paged comments were enabled. (It would also mean users could load the plugin without 
// the need to edit any files at all.)
function paged_comments_template($file = '/comments-paged.php')
{
	global $paged_comments, $wp_query, $withcomments, $post, $wpdb, $id, $comment, $user_login, $user_ID, $user_identity;

	$include = apply_filters('comments_template', TEMPLATEPATH . $file);
	if (!file_exists($include)) {
		$include = ABSPATH.'wp-content/plugins/paged-comments/themes/'.get_template().'/comments-paged.php';
		if (!file_exists($include)) $include = ABSPATH.'wp-content/plugins/paged-comments/themes/default/comments-paged.php';
	}

	// revert to original comment template if:
	// + the current context is inappropriate (e.g. post listing)
	// + template for paged comments does not exist
	if (!paged_comments() || !file_exists($include)) {
		// load comments.php from current theme folder (or 'default' theme folder if it doesn't exist)
		comments_template();
		return;
	}

	$req = get_option('require_name_email');
	$approved_condition = paged_comments_get_approved_condition();

	// SQL for paged comments
	$comment_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = '$post->ID' AND $approved_condition");
	paged_comments_init_pager($comment_count);
	$limit_clause = paged_comments_show_all() ? '' : ' LIMIT '.implode(', ', paged_comments_sql_limit());
	$comments = $wpdb->get_results("SELECT * FROM $wpdb->comments WHERE comment_post_ID = '$post->ID' AND $approved_condition ORDER BY comment_date ".paged_comments_ordering().$limit_clause);
	// end SQL for paged comments

	// comment numbering
	$comment_number = ($paged_comments->pager->get_current_page() - 1) * $paged_comments->pager->get_items_per_page();
	$comment_mod = $comment_count % $paged_comments->pager->get_items_per_page();
	if (paged_comments_ordering() == 'DESC') {
		if ($paged_comments->fill_last_page && !paged_comments_show_all() && ($comment_mod != 0)) {
			$comment_number += $comment_mod;
		} else {
			$comment_number += count($comments);
		}
		$comment_delta = -1;
	} else { // ASC
		if ($paged_comments->fill_last_page && !paged_comments_show_all() && ($comment_mod != 0) && ($comment_number != 0)) {
			$comment_number -= $paged_comments->pager->get_items_per_page() - $comment_mod - 1;
		} else {
			$comment_number += 1;
		}
		$comment_delta = 1;
	}

	get_currentuserinfo();

	define('COMMENTS_TEMPLATE', true);
	require($include);
}

function paged_comments_get_custom($field, $post_id=null)
{
	global $post;
	if (!isset($post_id)) {
		$post_id = $post->ID;
	}
	$post_id = (int)$post_id;
	return @get_post_meta($post_id, $field, true);
}

// returns true if paged comments are enabled for this post (or for the post id supplied)
function paged_comments($post_id=null)
{
	global $paged_comments;
	
	if (isset($post_id)) {
		$post_id = (int)$post_id;
	}
	
	// ignore requests for feeds and trackbacks (fixes problem with comment feeds being 
	// treated as requests for HTML views of posts/pages -- thanks Joseph (comment#263))
	if (!isset($post_id)) {
		if (is_feed() || is_trackback()) return false;
	}
	
	// determine is_single and is_page values
	if (isset($post_id)) {
		$post = &get_post($post_id);
		if (empty($post->ID)) return false;
		$is_page = $post->post_type == 'page';
		$is_single = $post->post_type == 'post';
	} else {
		$is_page = is_page();
		$is_single = is_single();
	}
	
	// paged comments only when viewing a single post or a single page
	if (!$is_single && !$is_page) return false;

	// has user chosen to view all comments?
	// if so, disable paged comments for this post
	//if(!(strpos($_GET['cp'], 'all' )===false) && $paged_comments->show_all_option) return false;
	// has paging been explicitly enabled for this post
	$paging_enabled = strtolower(paged_comments_get_custom('paged_comments', $post_id));
	if ($is_single) {
		// is paging enabled for all posts? return true unless explicitly disabled for this post
		if ($paged_comments->all_posts) { // all_posts on
			return ($paging_enabled != 'off'); // returns false if explicitly disabled
		} else { // all_posts off
			return ($paging_enabled == 'on'); // returns true if explicitly enabled
		}
	} else { // is_page()
		// is paging enabled for all pages? return true unless explicitly disabled for this page
		if ($paged_comments->all_pages) { // all_pages on
			return ($paging_enabled != 'off'); // returns false if explicitly disabled
		} else { // all_posts off
			return ($paging_enabled == 'on'); // returns true if explicitly enabled
		}
	}
	// paging not enabled for this post
	return false;
}

// check for valid show_all request
function paged_comments_show_all()
{
	global $paged_comments;
	return (($_GET['cp'] == 'all') && $paged_comments->show_all_option);
}

// effective ordering
function paged_comments_ordering()
{
	global $paged_comments;
	if (paged_comments_show_all()) {
		return $paged_comments->show_all_ordering;
	} elseif (isset($paged_comments->pager) && $paged_comments->pager->num_pages() == 1) {
		return $paged_comments->show_all_ordering;
	} else {
		return $paged_comments->ordering;
	}
}

// initialise pager
function paged_comments_init_pager($total_comments)
{
	global $paged_comments;
	paged_comments_update_values();
	if (paged_comments_show_all()) {
		// ensure all comments go on one comment page
		$paged_comments->main_pager =& new Pager($total_comments, $total_comments);
	} else {
		$paged_comments->main_pager =& new Pager($paged_comments->per_page, $total_comments);
	}
	$paged_comments->pager =& $paged_comments->main_pager;
	if ((paged_comments_show_all() && $paged_comments->show_all_ordering == 'DESC') || $paged_comments->ordering == 'DESC') {
		$paged_comments->pager =& new InvertedPager($paged_comments->pager);
	}
	// set page number
	$page = (int)@$_GET['cp'];
	if ($page > 0) {
		$paged_comments->pager->set_current_page($page);
	} elseif ($paged_comments->default_page != 'auto') {
		if ($paged_comments->default_page == 'first') {
			$paged_comments->main_pager->set_current_page(1);
		} else {
			$paged_comments->main_pager->set_current_page($paged_comments->pager->num_pages());
		}
	}
}

// for mysql LIMIT clause (returns array with offset and limit)
function paged_comments_sql_limit()
{
	global $paged_comments;
	$remainder = $paged_comments->pager->get_total_items() % $paged_comments->per_page;
	$offset = ($paged_comments->main_pager->get_current_page() - 1) * $paged_comments->per_page;

	// if total-comments multiple of comments-per-page
	if ($remainder == 0) {
		return array($offset, $paged_comments->per_page);
	}

	if ($paged_comments->ordering == 'DESC') {
		// alternate descending mode where the last page always contains a fixed number of comments
		if ($paged_comments->fill_last_page) {
			return array($offset, $paged_comments->per_page);
		// limit clause for comments in descending order (if we're on the last page)
		} elseif ($paged_comments->pager->get_current_page() == $paged_comments->pager->num_pages()) {
			return array(0, $remainder);
		} else {
			return array($offset + $remainder - $paged_comments->per_page, $paged_comments->per_page);
		}
	} else { // ASC
		if ($paged_comments->fill_last_page && $paged_comments->pager->is_first_page()) {
			return array(0, $remainder);
		} elseif ($paged_comments->fill_last_page) {
			return array($offset - ($paged_comments->per_page - $remainder), $paged_comments->per_page);
		} else {
			// limit clause for comments in ascending order
			return array($offset, $paged_comments->per_page);
		}
	}
}

// Returns URL to comment page ($cpage) with the $fragment appended.
// If $cpage is null the current comment page is used or a relative 
// URL (consisting of only the fragment) is returned.
function paged_comments_url($fragment='comments', $cpage=null)
{
	global $paged_comments, $post, $multipage, $page;
	if (!isset($cpage) && paged_comments_show_all()) return "#$fragment";
	if (!isset($cpage) && isset($paged_comments->pager)) $cpage = $paged_comments->pager->get_current_page();
	$id = $post->ID;
	$qparam = is_page() ? 'page_id' : 'p';
	$qparam = is_attachment() ? 'attachment_id' : $qparam;
	$multipage_fancy = '';
	$multipage_classic = '';

	// Adding the use of multi-pages posts
	if ($multipage && $page) {
		$multipage_fancy = "/$page";
		$multipage_classic = "&amp;page=$page";
	}

	if ($paged_comments->fancy_url && (get_settings('permalink_structure') != '')) {
		$slash = (substr(get_settings('permalink_structure'), -1) != '/') ? '' : '/';
		if ($cpage == 'all') {
			return rtrim(get_permalink(), '/').$multipage_fancy."/all-comments$slash#$fragment";
		} else {
			return rtrim(get_permalink(), '/').$multipage_fancy."/comment-page-$cpage$slash#$fragment";
		}
	} else {
		if ($cpage == 'all') {
			return get_settings('home').'/'.get_settings('blogfilename')."?$qparam=$id$multipage_classic&amp;cp=all#$fragment";
		} else {
			return get_settings('home').'/'.get_settings('blogfilename')."?$qparam=$id$multipage_classic&amp;cp=$cpage#$fragment";
		}
	}
}

// output page numbers
function paged_comments_print_pages()
{
	global $paged_comments, $post;
	// URLs may contain the % symbol which will cause problems later when sprintf() is called
	// $url = paged_comments_url('comments', '%u');
	$url = paged_comments_url('comments', '=placeholder=');
	$url = str_replace('%', '%%', $url);
	$url = str_replace('=placeholder=', '%u', $url);
	$allurl = paged_comments_url('comments', 'all');
	$printer =& new PagePrinter($paged_comments->pager, $url, $paged_comments->page_range);
	$left = '&laquo;'; $right = '&raquo;'; $older = __('Older Comments','paged-comments'); $newer = __('Newer Comments','paged-comments'); $sep = ' ';
	$link_left = ($paged_comments->ordering == 'ASC') ? $printer->get_prev_link($left, $older) : $printer->get_next_link($left, $newer);
	// left arrow link
	if (!empty($link_left)) echo $link_left, $sep;
	// page number links
	echo $printer->get_links($sep);
	// right arrow link
	$link_right = ($paged_comments->ordering == 'ASC') ? $printer->get_next_link($right, $newer) : $printer->get_prev_link($right, $older);
	if (!empty($link_right)) echo $sep, $link_right;
	if ($paged_comments->show_all_option) echo $sep, '<a href="'.$allurl.'">'.__('Show All','paged-comments').'</a>';
}

// The classes below are used to calculate page numbers and print pages numbers

/*****************************************
* Class: Pager 
* Originally by: Tsigo <tsigo@tsiris.com>
* Modified: Keyvan
* Redistribute as you see fit. 
*****************************************/
class Pager 
{
	/**
	* Items per page.
	*
	* This is used, along with <var>$item_total</var>, to calculate how many
	* pages are needed.
	* @var int
	*/
	var $items_per_page;

	/**
	* Total number of items 
	*
	* This is used, along with <var>$items_per_page</var>, to calculate how many
	* pages are needed.
	* @var int
	*/
	var $item_total;

	/**
	* Current page
	* @var int
	*/
	var $current_page;

	/**
	* Number of pages needed
	* @var int
	*/
	var $num_pages;

	/**
	* Constructor
	*/
	function Pager($items_per_page, $item_total)
	{
		$this->items_per_page = $items_per_page;
		$this->item_total = $item_total;
		$this->num_pages = (int)ceil($this->item_total / $this->items_per_page);
		$this->set_current_page(1);
	}

	/**
	* Set current page number
	* @param int $page
	*/
	function set_current_page($page)
	{
		$this->current_page = min($page, $this->num_pages());
		$this->current_page = max($this->current_page, 1);
	}

	/**
	* Get current page
	* @return int
	*/
	function get_current_page()
	{
		return $this->current_page;
	}

	/**
	* Get items per page
	* @return int
	*/
	function get_items_per_page()
	{
		return $this->items_per_page;
	}

	/**
	* Get total items
	* @return int
	*/
	function get_total_items()
	{
		return $this->item_total;
	}
	
	/**
	* Number of pages needed
	* @return int
	*/
	function num_pages() 
	{
		return $this->num_pages;
	}

	/**
	* Is last page
	* @return boolean
	*/
	function is_last_page()
	{
		return ($this->get_current_page() == $this->num_pages());
	}

	/**
	* Is first page
	* @return boolean
	*/
	function is_first_page()
	{
		return ($this->get_current_page() == 1);
	}

	/**
	* Get page numbers within range
	* @param int $page_range number of pages to display at one time, default: all pages
	* @return array
	*/
	function get_page_numbers($page_range=null)
	{
		if (!isset($page_range)) {
			return range(1, $this->num_pages());
		} else {
			// set boundaries
			$pages = $this->num_pages();
			$range_halved = (int)floor($page_range / 2);
			$count_start = $this->current_page - $range_halved;
			$count_end = $this->current_page + $range_halved;

			// adjust boundaries
			while ($count_start < 1) {
				$count_start++;
				$count_end++;
			}
			while ($count_end > $pages) {
				$count_end--;
				$count_start--;
			}
			$count_start = max($count_start, 1);
			return range($count_start, $count_end);
		}
	}
}

// Implements the Pager interface but inverts numbers. (Decorator pattern)
class InvertedPager
{
	var $pager;

	function InvertedPager(&$pager)
	{
		$this->pager =& $pager;
	}

	function _invert_page($page)
	{
		return $this->pager->num_pages() + 1 - $page;
	}

	/**
	* Set current page number
	* @param int $page
	*/
	function set_current_page($page)
	{
		$this->pager->set_current_page($this->_invert_page($page));
	}

	/**
	* Get current page
	* @return int
	*/
	function get_current_page()
	{
		return $this->_invert_page($this->pager->get_current_page());
	}

	/**
	* Get page numbers within range
	* @param int $page_range number of pages to display at one time, default: all pages
	* @return array
	*/
	function get_page_numbers($page_range=null)
	{
		return array_map(array(&$this, '_invert_page'), $this->pager->get_page_numbers($page_range));
	}

	/**
	* Get items per page
	* @return int
	*/
	function get_items_per_page()
	{
		return $this->pager->get_items_per_page();
	}

	/**
	* Get total items
	* @return int
	*/
	function get_total_items()
	{
		return $this->pager->get_total_items();
	}

	/**
	* Number of pages needed
	* @return int
	*/
	function num_pages() 
	{
		return $this->pager->num_pages();
	}

	/**
	* Is last page
	* @return boolean
	*/
	function is_last_page()
	{
		return ($this->get_current_page() == $this->num_pages());
	}

	/**
	* Is first page
	* @return boolean
	*/
	function is_first_page()
	{
		return ($this->get_current_page() == 1);
	}
}

// Prints page number links using a Pager instance
class PagePrinter
{
	var $pager;

	/**
	* URL formatting string for building page links
	*
	* This should be a formatting string which will be passed to sprintf()
	* (see: <http://uk.php.net/sprintf>), it should include 1 conversion 
	* specification: %u (to hold the page number)
	* @string
	*/
	var $url;

	/**
	* Number of pages to show at one time
	* @var int
	*/
	var $page_range;

	function PagePrinter(&$pager, $url='', $page_range=null)
	{
		$this->pager =& $pager;
		$this->set_page_range($page_range);
		$this->set_url($url);
	}

	function get_prev_link($text='&laquo;', $title='Previous Page')
	{
		if ($this->pager->is_first_page()) return '';
		return '<a class="previous-comment-page" href="'.$this->get_url($this->pager->get_current_page() - 1).'" title="'.$title.'">'.$text.'</a>';
	}

	function get_next_link($text='&raquo;', $title='Next Page')
	{
		if ($this->pager->is_last_page()) return '';
		return '<a class="next-comment-page" href="'.$this->get_url($this->pager->get_current_page() + 1).'" title="'.$title.'">'.$text.'</a>';
	}

	/**
	* Get page links
	* @return string HTML
	*/
	function get_links($separator=' ', $pre_cur_page='<strong class="current-comment-page">[', $post_cur_page=']</strong>')
	{
		$pages = $this->pager->num_pages();
		$page_links  = ''; 
	
		// print page numbers
		$cur_page = $this->pager->get_current_page();
		$num_links = array();
		$page_numbers = $this->pager->get_page_numbers($this->page_range);
		$asc = ($page_numbers[0] < $page_numbers[1]);
		if( $asc ) {
			if( $page_numbers[0] != 1 ) {
				$num_links[] = '<a href="'.$this->get_url(1)."\">1</a> &#8230;"; 
			}
		} else {
			if( $page_numbers[0] != $this->pager->num_pages() ) {
				$num_links[] = '<a href="'.$this->get_url($this->pager->num_pages())."\">".$this->pager->num_pages()."</a> &#8230;"; 
			}
		}
		foreach ( $page_numbers as $i) { 
			if ($i == $cur_page) { 
				$num_links[] = $pre_cur_page.$i.$post_cur_page; 
			} else { 
				$num_links[] = '<a href="'.$this->get_url($i)."\">$i</a>"; 
			} 
		} 
		if( $asc ) {
			if( $page_numbers[count($page_numbers)-1] != $this->pager->num_pages() ) {
				$num_links[] = '&#8230; <a href="'.$this->get_url($this->pager->num_pages())."\">".$this->pager->num_pages()."</a>"; 
			}
		} else {
			if( $page_numbers[count($page_numbers)-1] != 1 ) {
				$num_links[] = '&#8230; <a href="'.$this->get_url(1)."\">1</a>"; 
			}
		}
		$page_links .= implode($separator, $num_links);
		
		return $page_links; 
	}

	/**
	* Set page range
	* @param int $max
	*/
	function set_page_range($max)
	{
		$this->page_range = $max;
	}

	/**
	* Set URL
	* @param string $url
	*/
	function set_url($url)
	{
		$this->url = $url;
	}

	/**
	* Get formatted URL (including page number)
	* @param int $page page number
	* @return string
	*/
	function get_url($page)
	{
		return sprintf($this->url, $page);
	}
}
?>
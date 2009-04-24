<?php
/*
Plugin Name: Breadcrumb NavXT - Core
Plugin URI: http://mtekk.weblogs.us/code/breadcrumb-navxt/
Description: Adds a breadcrumb navigation showing the visitor&#39;s path to their current location. This plug-in provides direct access to the bcn_breadcrumb_trail class without using the administrative interface. For details on how to use this plugin visit <a href="http://mtekk.weblogs.us/code/breadcrumb-navxt/">Breadcrumb NavXT</a>. 
Version: 3.1.0
Author: John Havlik
Author URI: http://mtekk.weblogs.us/
*/
/*  Copyright 2007-2009  John Havlik  (email : mtekkmonkey@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
//The breadcrumb class
class bcn_breadcrumb
{
	//Our member variables
	//The main text that will be shown
	var $title;
	//Boolean, is this element linked
	var $linked;
	//Linked anchor contents, null if $linked == false
	var $anchor;
	//Global prefix, outside of link tags
	var $prefix;
	//Global suffix, outside of link tags
	var $suffix;
	//Default constructor
	function breadcrumb()
	{
		//Default state of unlinked
		$this->linked = false;
		//Always NULL if unlinked
		$this->anchor = NULL;
	}
	/**
	 * title_trim
	 * 
	 * This function will intelligently trim the title to the value passed in through $max_length.
	 * 
	 * @param (int) max_length of the title.
	 */
	function title_trim($max_length)
	{
		//Make sure that we are not making it longer with that ellipse
		if((strlen($this->title) + 3) > $max_length)
		{
			//Trim the title
			$this->title = substr($this->title, 0, $max_length - 1);
			//Make sure we can split at a space, but we want to limmit to cutting at max an additional 25%
			if(strpos($this->title, " ", .75 * $max_length) > 0)
			{
				//Don't split mid word
				while(substr($this->title,-1) != " ")
				{
					$this->title = substr($this->title, 0, -1);
				}
			}
			//Remove the whitespace at the end and add the hellip
			$this->title = rtrim($this->title) . '&hellip;';
		}
	}
}

//The trail class
class bcn_breadcrumb_trail
{
	//Our member variables
	var $version;
	//An array of breadcrumbs
	var $trail;
	//The options
	var $opt;
	//Default constructor
	function bcn_breadcrumb_trail()
	{
		$this->version = "3.1.0";
		//Initilize the trail as an array
		$this->trail = array();
		//Load the translation domain as the next part needs it
		load_plugin_textdomain($domain = 'breadcrumb_navxt', $path = PLUGINDIR . '/breadcrumb-navxt');
		//Initilize with default option values
		$this->opt = array
		(
			//Should the home page be shown
			'home_display' => true,
			//Title displayed when is_home() returns true
			'home_title' => __('Blog','breadcrumb_navxt'),
			//The anchor template for the home page, this is global, two keywords are available %link% and %title%
			'home_anchor' => __('<a title="Go to %title%." href="%link%">','breadcrumb_navxt'),
			//The anchor template for the blog page only in static front page mode, this is global, two keywords are available %link% and %title%
			'blog_anchor' => __('<a title="Go to %title%." href="%link%">','breadcrumb_navxt'),
			//The prefix for page breadcrumbs, place on all page elements and inside of current_item prefix
			'home_prefix' => '',
			//The suffix for page breadcrumbs, place on all page elements and inside of current_item suffix
			'home_suffix' => '',
			//Separator that is placed between each item in the breadcrumb trial, but not placed before
			//the first and not after the last breadcrumb
			'separator' => '&nbsp;&gt;&nbsp;',
			//The maximum title lenght
			'max_title_length' => 0,
			//Current item options, really only applies to static pages and posts unless other current items are linked
			'current_item_linked' => false,
			//The anchor template for current items, this is global, two keywords are available %link% and %title%
			'current_item_anchor' => __('<a title="Reload the current page." href="%link%">','breadcrumb_navxt'),
			//The prefix for current items allows separate styling of the current location breadcrumb
			'current_item_prefix' => '',
			//The suffix for current items allows separate styling of the current location breadcrumb
			'current_item_suffix' => '',
			//Static page options
			//The prefix for page breadcrumbs, place on all page elements and inside of current_item prefix
			'page_prefix' => '',
			//The suffix for page breadcrumbs, place on all page elements and inside of current_item suffix
			'page_suffix' => '',
			//The anchor template for page breadcrumbs, two keywords are available %link% and %title%
			'page_anchor' => __('<a title="Go to %title%." href="%link%">','breadcrumb_navxt'),
			//Paged options
			//The prefix for paged breadcrumbs, place on all page elements and inside of current_item prefix
			'paged_prefix' => '',
			//The suffix for paged breadcrumbs, place on all page elements and inside of current_item suffix
			'paged_suffix' => '',
			//Should we try filling out paged information
			'paged_display' => false,
			//The post options previously singleblogpost
			//The prefix for post breadcrumbs, place on all page elements and inside of current_item prefix
			'post_prefix' => '',
			//The suffix for post breadcrumbs, place on all page elements and inside of current_item suffix
			'post_suffix' => '',
			//The anchor template for post breadcrumbs, two keywords are available %link% and %title%
			'post_anchor' => __('<a title="Go to %title%." href="%link%">','breadcrumb_navxt'),
			//Should the trail include the taxonomy of the post
			'post_taxonomy_display' => true,
			//What taxonomy should be shown leading to the post, tag or category
			'post_taxonomy_type' => 'category',
			//Attachment settings
			//The prefix for attachment breadcrumbs, place on all page elements and inside of current_item prefix
			'attachment_prefix' => '',
			//The suffix for attachment breadcrumbs, place on all page elements and inside of current_item suffix
			'attachment_suffix' => '',
			//404 page settings
			//The prefix for 404 breadcrumbs, place on all page elements and inside of current_item prefix
			'404_prefix' => '',
			//The suffix for 404 breadcrumbs, place on all page elements and inside of current_item suffix
			'404_suffix' => '',
			//The text to be shown in the breadcrumb for a 404 page
			'404_title' => __('404','breadcrumb_navxt'),
			//Search page options
			//The prefix for search breadcrumbs, place on all page elements and inside of current_item prefix
			'search_prefix' => __('Search results for &#39;','breadcrumb_navxt'),
			//The suffix for search breadcrumbs, place on all page elements and inside of current_item suffix
			'search_suffix' => '&#39;',
			//The anchor template for search breadcrumbs, two keywords are available %link% and %title%
			'search_anchor' => __('<a title="Go to the first page of search results for %title%." href="%link%">','breadcrumb_navxt'),
			//Tag related stuff
			//The prefix for tag breadcrumbs, place on all page elements and inside of current_item prefix
			'tag_prefix' => '',
			//The suffix for tag breadcrumbs, place on all page elements and inside of current_item suffix
			'tag_suffix' => '',
			//The anchor template for tag breadcrumbs, two keywords are available %link% and %title%
			'tag_anchor' => __('<a title="Go to the %title% tag archives." href="%link%">','breadcrumb_navxt'),
			//Author page stuff
			//The prefix for author breadcrumbs, place on all page elements and inside of current_item prefix
			'author_prefix' => __('Articles by: ','breadcrumb_navxt'),
			//The suffix for author breadcrumbs, place on all page elements and inside of current_item suffix
			'author_suffix' => '',
			//Which of the various WordPress display types should the author crumb display
			'author_display' => 'display_name',
			//Category stuff
			//The prefix for category breadcrumbs, place on all page elements and inside of current_item prefix
			'category_prefix' => '',
			//The suffix for category breadcrumbs, place on all page elements and inside of current_item suffix
			'category_suffix' => '',
			//The anchor template for category breadcrumbs, two keywords are available %link% and %title%
			'category_anchor' => __('<a title="Go to the %title% category archives." href="%link%">','breadcrumb_navxt'),
			//Archives related settings
			//Prefix for category archives, place inside of both the current_item prefix and the category_prefix
			'archive_category_prefix' => __('Archive by category &#39;','breadcrumb_navxt'),
			//Suffix for category archives, place inside of both the current_item suffix and the category_suffix
			'archive_category_suffix' => '&#39;',
			//Prefix for tag archives, place inside of the current_item prefix
			'archive_tag_prefix' => __('Archive by tag &#39;','breadcrumb_navxt'),
			//Suffix for tag archives, place inside of the current_item suffix
			'archive_tag_suffix' => '&#39;',
			'date_anchor' => __('<a title="Go to the %title% archives." href="%link%">','breadcrumb_navxt'),
			//Prefix for date archives, place inside of the current_item prefix
			'archive_date_prefix' => '',
			//Suffix for date archives, place inside of the current_item suffix
			'archive_date_suffix' => ''
		);
	}
	//The do filling functions
	/**
	 * do_search
	 * 
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for a search page.
	 */
	function do_search()
	{
		global $s;
		//Add new breadcrumb to the trail
		$this->trail[] = new bcn_breadcrumb();
		//Figure out where we placed the crumb, make a nice pointer to it
		$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
		//Assign the prefix
		$bcn_breadcrumb->prefix = $this->opt['current_item_prefix'] . $this->opt['search_prefix'];
		//Assign the suffix
		$bcn_breadcrumb->suffix = $this->opt['search_suffix'] . $this->opt['current_item_suffix'];
		//Assign the title
		$bcn_breadcrumb->title = wp_specialchars($s, 1);
		//If we're paged, let's link to the first page
		if(is_paged() && $this->opt['paged_display'])
		{
			//Figure out the hyperlink for the anchor
			$url = get_settings('home'). "?s=" . str_replace(" ", "+", wp_specialchars($s, 1));
			//Figure out the anchor for the search
			$bcn_breadcrumb->anchor = str_replace("%title%", $bcn_breadcrumb->title, str_replace("%link%", $url, $this->opt['search_anchor']));
			//We want this to be linked
			$bcn_breadcrumb->linked = true;
		}
	}
	/**
	 * do_attachment
	 * 
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for an attachment page.
	 */
	function do_attachment()
	{
		global $post;
		//Add new breadcrumb to the trail
		$this->trail[] = new bcn_breadcrumb();
		//Figure out where we placed the crumb, make a nice pointer to it
		$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
		//Assign the prefix
		$bcn_breadcrumb->prefix = $this->opt['current_item_prefix'] . $this->opt['attachment_prefix'];
		//Assign the suffix
		$bcn_breadcrumb->suffix = $this->opt['attachment_suffix'] . $this->opt['current_item_suffix'];
		//Addign the title, using a better method
		$bcn_breadcrumb->title = get_the_title();
		//Get the parent page/post of the attachment
		$bcn_parent_id = $post->post_parent;
		//Get the parent's information
		$bcn_parent = get_post($bcn_parent_id);
		//We need to treat post and page attachment hierachy differently
		if($bcn_parent->post_type == "page")
		{
			//Grab the page on front ID for page_parents
			$bcn_frontpage = get_option('page_on_front');
			//Place the rest of the page hierachy
			$this->page_parents($bcn_parent_id, $bcn_frontpage);
		}
		else
		{
			//Add new breadcrumb to the trail
			$this->trail[] = new bcn_breadcrumb();
			//Figure out where we placed the crumb, make a nice pointer to it
			$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
			//Assign the prefix
			$bcn_breadcrumb->prefix = $this->opt['post_prefix'];
			//Assign the suffix
			$bcn_breadcrumb->suffix = $this->opt['post_suffix'];
			//Get the parent's information
			$bcn_parent = get_post($bcn_parent_id);
			//Adding the title, throw it through the filters
			$bcn_breadcrumb->title = apply_filters("the_title", $bcn_parent->post_title);
			//Assign the anchor properties
			$bcn_breadcrumb->anchor = str_replace("%title%", $bcn_breadcrumb->title, str_replace("%link%", get_permalink($bcn_parent_id), $this->opt['post_anchor']));
			//We want this to be linked
			$bcn_breadcrumb->linked = true;
		}
	}
	/**
	 * do_author
	 * 
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for an author page.
	 */
	function do_author()
	{
		global $author;
		//Add new breadcrumb to the trail
		$this->trail[] = new bcn_breadcrumb();
		//Figure out where we placed the crumb, make a nice pointer to it
		$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
		//Assign the prefix
		$bcn_breadcrumb->prefix = $this->opt['current_item_prefix'] . $this->opt['author_prefix'];
		//Assign the suffix
		$bcn_breadcrumb->suffix = $this->opt['author_suffix'] . $this->opt['current_item_suffix'];
		//Get the Author name, note it is an object
		$bcn_curauth = (isset($_GET['author_name'])) ? get_userdatabylogin($author_name) : get_userdata(intval($author));
		//Get the Author display type
		$bcn_authdisp = $this->opt['author_display'];
		//Make sure user picks only safe values
		if($bcn_authdisp == "display_name" || $bcn_authdisp == "nickname" || $bcn_authdisp == "first_name" || $bcn_authdisp == "last_name")
		{
			//Assign the title
			$bcn_breadcrumb->title = apply_filters("the_author", $bcn_curauth->$bcn_authdisp);
		}
	}
	/**
	 * page_parents
	 * 
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This recursive functions fills the trail with breadcrumbs for parent pages.
	 * @param  (int)   $id The id of the parent page.
	 * @param  (int)   $frontpage The id of the front page.
	 */
	function page_parents($id, $frontpage)
	{
		//Add new breadcrumb to the trail
		$this->trail[] = new bcn_breadcrumb();
		//Figure out where we placed the crumb, make a nice pointer to it
		$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
		//Assign the prefix
		$bcn_breadcrumb->prefix = $this->opt['page_prefix'];
		//Assign the suffix
		$bcn_breadcrumb->suffix = $this->opt['page_suffix'];
		//Use WordPress API, though a bit heavier than the old method, this will ensure compatibility with other plug-ins
		$bcn_parent = get_post($id);
		//Assign the title
		$bcn_breadcrumb->title = apply_filters("the_title", $bcn_parent->post_title);
		//Assign the anchor properties
		$bcn_breadcrumb->anchor = str_replace("%title%", $bcn_breadcrumb->title, str_replace("%link%", get_permalink($id), $this->opt['page_anchor']));
		//We want this to be linked
		$bcn_breadcrumb->linked = true;
		//Make sure the id is valid, and that we won't end up spinning in a loop
		if($bcn_parent->post_parent && $id != $bcn_parent->post_parent && $frontpage != $bcn_parent->post_parent)
		{
			//If valid, recursivly call this function
			$this->page_parents($bcn_parent->post_parent, $frontpage);
		}
	}
	/**
	 * do_page
	 * 
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for a atatic page.
	 */
	function do_page()
	{
		global $post;
		//Add new breadcrumb to the trail
		$this->trail[] = new bcn_breadcrumb();
		//Figure out where we placed the crumb, make a nice pointer to it
		$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
		//Assign the prefix
		$bcn_breadcrumb->prefix = $this->opt['current_item_prefix'] . $this->opt['page_prefix'];
		//Assign the suffix
		$bcn_breadcrumb->suffix = $this->opt['page_suffix'] . $this->opt['current_item_suffix'];
		//Assign the title, using our older method to replace in the future
		$bcn_breadcrumb->title = get_the_title();
		//Done with the current item, now on to the parents
		$bcn_frontpage = get_option('page_on_front');
		//If there is a parent page let's find it
		if($post->post_parent && $post->ID != $post->post_parent && $bcn_frontpage != $post->post_parent)
		{
			$this->page_parents($post->post_parent, $bcn_frontpage);
		}
	}
	/**
	 * post_tag
	 * 
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for the tags of a post
	 * 
	 * @TODO	Need to implement this cleaner, possibly a recursive object
	 */
	function post_tags()
	{
		global $post;
		//Add new breadcrumb to the trail
		$this->trail[] = new bcn_breadcrumb();
		//Figure out where we placed the crumb, make a nice pointer to it
		$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
		//Fills a temporary object with the tags for the post
		$bcn_object = get_the_tags($post->ID);
		//Only process if we have tags
		if(is_array($bcn_object))
		{
			$i = true;
			foreach($bcn_object as $tag)
			{
				//Run through a filter for good measure
				$tag->name = apply_filters("get_tag", $tag->name);
				//On the first run we don't need a separator
				if($i)
				{
					$bcn_breadcrumb->title = $this->opt['tag_prefix'] . str_replace("%title%",  $tag->name, str_replace("%link%", get_tag_link($tag->term_id), $this->opt['tag_anchor'])) . $tag->name . "</a>" . $this->opt['tag_suffix'];
					$i = false;
				}
				else
				{
					$bcn_breadcrumb->title .= ', ' . $this->opt['tag_prefix'] . str_replace("%title%", $tag->name, str_replace("%link%", get_tag_link($tag->term_id), $this->opt['tag_anchor'])) . $tag->name . "</a>" . $this->opt['tag_suffix'];
				}
			}
		}
		else
		{
			$bcn_breadcrumb->title = "Untaged";	
		}
	}
	/**
	 * category_parents
	 * 
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This recursive functions fills the trail with breadcrumbs for parent categories.
	 * @param  (int)   $id The id of the parent category.
	 */
	function category_parents($id)
	{
		global $post;
		//Add new breadcrumb to the trail
		$this->trail[] = new bcn_breadcrumb();
		//Figure out where we placed the crumb, make a nice pointer to it
		$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
		//Assign the prefix
		$bcn_breadcrumb->prefix = $this->opt['category_prefix'];
		//Assign the suffix
		$bcn_breadcrumb->suffix = $this->opt['category_suffix'];
		//Get the current category object, filter applied within this call
		$bcn_category = get_category($id);
		//Setup the title
		$bcn_breadcrumb->title = $bcn_category->cat_name;
		//Figure out the anchor for the first category
		$bcn_breadcrumb->anchor = str_replace("%title%", $bcn_breadcrumb->title, str_replace("%link%", get_category_link($bcn_category->cat_ID), $this->opt['category_anchor']));
		//We want this to be linked
		$bcn_breadcrumb->linked = true;
		//Make sure the id is valid, and that we won't end up spinning in a loop
		if($bcn_category->category_parent && $bcn_category->category_parent != $id)
		{
			//Figure out the rest of the category hiearchy via recursion
			$this->category_parents($bcn_category->category_parent);
		}
	}
	/**
	 * do_archive_by_category
	 * 
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for a category archive.
	 */
	function do_archive_by_category()
	{
		global $wp_query;
		//Add new breadcrumb to the trail
		$this->trail[] = new bcn_breadcrumb();
		//Figure out where we placed the crumb, make a nice pointer to it
		$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
		//Assign the prefix
		$bcn_breadcrumb->prefix = $this->opt['current_item_prefix'] . $this->opt['category_prefix'] . $this->opt['archive_category_prefix'];
		//Assign the suffix
		$bcn_breadcrumb->suffix = $this->opt['archive_category_suffix'] . $this->opt['category_suffix'] . $this->opt['current_item_suffix'];
		//Assign the title
		$bcn_breadcrumb->title = single_cat_title("", false);	
		//Simmilar to using $post, but for things $post doesn't cover
		$bcn_category = $wp_query->get_queried_object();
		//If we're paged, let's link to the first page
		if(is_paged() && $this->opt['paged_display'])
		{
			//Figure out the anchor for current category
			$bcn_breadcrumb->anchor = str_replace("%title%", $bcn_breadcrumb->title, str_replace("%link%", get_category_link($bcn_category->cat_ID), $this->opt['category_anchor']));
			//We want this to be linked
			$bcn_breadcrumb->linked = true;
		}
		//Get parents of current category
		if($bcn_category->category_parent)
		{
			$this->category_parents($bcn_category->category_parent);
		}
	}
	/**
	 * do_archive_by_tag
	 * 
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for a tag archive.
	 */
	function do_archive_by_tag()
	{
		global $wp_query;
		//Add new breadcrumb to the trail
		$this->trail[] = new bcn_breadcrumb();
		//Figure out where we placed the crumb, make a nice pointer to it
		$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
		//Assign the prefix
		$bcn_breadcrumb->prefix = $this->opt['current_item_prefix'] . $this->opt['archive_tag_prefix'];
		//Assign the suffix
		$bcn_breadcrumb->suffix = $this->opt['archive_tag_suffix'] . $this->opt['current_item_suffix'];
		//Assign the title
		$bcn_breadcrumb->title = single_tag_title("", false);
		//If we're paged, let's link to the first page
		if(is_paged() && $this->opt['paged_display'])
		{
			//Simmilar to using $post, but for things $post doesn't cover
			$bcn_tag = $wp_query->get_queried_object();
			//Figure out the anchor for current category
			$bcn_breadcrumb->anchor = str_replace("%title%", $bcn_breadcrumb->title, str_replace("%link%", get_tag_link($bcn_tag->term_id), $this->opt['tag_anchor']));
			//We want this to be linked
			$bcn_breadcrumb->linked = true;
		}
	}
	/**
	 * do_archive_by_date
	 * 
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for a date archive.
	 */
	function do_archive_by_date()
	{
		global $wp_query;
		//Add new breadcrumb to the trail
		$this->trail[] = new bcn_breadcrumb();
		//Figure out where we placed the crumb, make a nice pointer to it
		$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
		if(is_day())
		{
			//First deal with the day breadcrumb
			//Assign the prefix
			$bcn_breadcrumb->prefix = $this->opt['current_item_prefix'] . $this->opt['archive_date_prefix'];
			//Assign the suffix
			$bcn_breadcrumb->suffix = $this->opt['archive_date_suffix'] . $this->opt['current_item_suffix'];
			//Assign the title
			$bcn_breadcrumb->title = get_the_time('d');
			//If we're paged, let's link to the first page
			if(is_paged() && $this->opt['paged_display'])
			{
				//Deal with the anchor
				$bcn_breadcrumb->anchor = str_replace("%title%", get_the_time('F') . " " . $bcn_breadcrumb->title . ", " . get_the_time('Y'), str_replace("%link%", get_day_link(get_the_time('Y'), get_the_time('m'), $bcn_breadcrumb->title), $this->opt['date_anchor']));
				//Yes we want this linked
				$bcn_breadcrumb->linked = true;
			}
			//Next add in the month breadcrumb
			//Add new breadcrumb to the trail
			$this->trail[] = new bcn_breadcrumb();
			//Figure out where we placed the crumb, make a nice pointer to it
			$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
			//Assign the prefix
			$bcn_breadcrumb->prefix = $this->opt['archive_date_prefix'];
			//Assign the suffix
			$bcn_breadcrumb->suffix = $this->opt['archive_date_suffix'];
			//Assign the title
			$bcn_breadcrumb->title = get_the_time('F');
			//Deal with the anchor
			$bcn_breadcrumb->anchor = str_replace("%title%", get_the_time('F') . " " . get_the_time('Y'), str_replace("%link%", get_month_link(get_the_time('Y'), get_the_time('m')), $this->opt['date_anchor']));
			//Yes we want this linked
			$bcn_breadcrumb->linked = true;
		}
		else if(is_month())
		{
			//Assign the prefix
			$bcn_breadcrumb->prefix = $this->opt['current_item_prefix'] . $this->opt['archive_date_prefix'];
			//Assign the suffix
			$bcn_breadcrumb->suffix = $this->opt['archive_date_suffix'] . $this->opt['current_item_suffix'];
			//Assign the title
			$bcn_breadcrumb->title = get_the_time('F');
			//If we're paged, let's link to the first page
			if(is_paged() && $this->opt['paged_display'])
			{
				//Deal with the anchor
				$bcn_breadcrumb->anchor = str_replace("%title%", get_the_time('F') . " " . get_the_time('Y'), str_replace("%link%", get_month_link(get_the_time('Y'), get_the_time('m')), $this->opt['date_anchor']));
				//Yes we want this linked
				$bcn_breadcrumb->linked = true;
			}
		}
		//Next add in the year breadcrumb
		if(is_year())
		{	
			//Assign the prefix
			$bcn_breadcrumb->prefix = $this->opt['current_item_prefix'] . $this->opt['archive_date_prefix'];
			//Assign the suffix
			$bcn_breadcrumb->suffix = $this->opt['archive_date_suffix'] . $this->opt['current_item_suffix'];
			//Assign the title
			$bcn_breadcrumb->title = get_the_time('Y');
			//If we're paged, let's link to the first page
			if(is_paged() && $this->opt['paged_display'])
			{
				//Deal with the anchor
				$bcn_breadcrumb->anchor = str_replace("%title%", get_the_time('Y'), str_replace("%link%", get_year_link(get_the_time('Y')), $this->opt['date_anchor']));
				//Yes we want this linked
				$bcn_breadcrumb->linked = true;
			}
		}
		else
		{
			//Add new breadcrumb to the trail
			$this->trail[] = new bcn_breadcrumb();
			//Figure out where we placed the crumb, make a nice pointer to it
			$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
			//Assign the prefix
			$bcn_breadcrumb->prefix = $this->opt['archive_date_prefix'];
			//Assign the suffix
			$bcn_breadcrumb->suffix = $this->opt['archive_date_suffix'];
			//Assign the title
			$bcn_breadcrumb->title = get_the_time('Y');
			//Deal with the anchor
			$bcn_breadcrumb->anchor = str_replace("%title%", get_the_time('Y'), str_replace("%link%", get_year_link(get_the_time('Y')), $this->opt['date_anchor']));
			//Yes we want this linked
			$bcn_breadcrumb->linked = true;
		}
	}
	/**
	 * do_post
	 * 
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for a post.
	 */
	function do_post()
	{
		global $post;
		//Add new breadcrumb to the trail
		$this->trail[] = new bcn_breadcrumb();
		//Figure out where we placed the crumb, make a nice pointer to it
		$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
		//Assign the prefix
		$bcn_breadcrumb->prefix = $this->opt['current_item_prefix'] . $this->opt['post_prefix'];
		//Assign the suffix
		$bcn_breadcrumb->suffix = $this->opt['post_suffix'] . $this->opt['current_item_suffix'];
		//Assign the title
		$bcn_breadcrumb->title = get_the_title();
		//Check to see if breadcrumbs for the taxonomy of the post needs to be generated
		if($this->opt['post_taxonomy_display'])
		{
			//Figure out which taxonomy is desired
			if($this->opt['post_taxonomy_type'] == "tag")
			{
				$this->post_tags();
			}
			else
			{
				//Fills the temp object to get the categories 
				$bcn_object = get_the_category();
				//Now find which one has a parent, pick the first one that does
				$i = 0;
				$bcn_use_category = 0;
				foreach($bcn_object as $object)
				{
					//We want the first category hiearchy
					if($object->category_parent > 0 && $bcn_use_category == 0)
					{
						$bcn_use_category = $i;
					}
					$i++;
				}
				//Fill out the category hiearchy
				$this->category_parents($bcn_object[$bcn_use_category]->term_id);
			}
		}
		//If our max title length is greater than 0 we should do something
		if($this->opt['max_title_length'] > 0)
		{
			$bcn_breadcrumb->title_trim($this->opt['max_title_length']);
		}
	}
	/**
	 * do_front_page
	 * 
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for the front page.
	 */
	function do_front_page()
	{
		global $post;
		//Add new breadcrumb to the trail
		$this->trail[] = new bcn_breadcrumb();
		//Figure out where we placed the crumb, make a nice pointer to it
		$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
		//Assign the title 
		$bcn_breadcrumb->title = $this->opt['home_title'];
		//Assign the prefix
		$bcn_breadcrumb->prefix = $this->opt['current_item_prefix'] . $this->opt['home_prefix'];
		//Assign the suffix
		$bcn_breadcrumb->suffix = $this->opt['home_suffix'] . $this->opt['current_item_suffix'];
		//If we're paged, let's link to the first page
		if(is_paged() && $this->opt['paged_display'])
		{
			//Figure out the anchor for home page
			$bcn_breadcrumb->anchor = str_replace("%title%", $this->opt['home_title'], str_replace("%link%", get_option('home'), $this->opt['home_anchor']));
			//We want this to be linked
			$bcn_breadcrumb->linked = true;
		}
	}
	/**
	 * do_home
	 * 
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for the home page.
	 */
	function do_home()
	{
		global $post;
		if(get_option('show_on_front') == "page")
		{
			//We'll have to check if this ID is valid, e.g. user has specified a posts page
			$posts_id = get_option("page_for_posts");
			//We only need the "blog" portion on members of the blog, not searches, pages or 404s
			if((is_single() || is_archive() || is_author() || is_home()) && $posts_id != NULL)
			{
				//Add new breadcrumb to the trail
				$this->trail[] = new bcn_breadcrumb();
				//Figure out where we placed the crumb, make a nice pointer to it
				$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
				//Get the blog page ID
				$post = get_post($posts_id);
				//Setup the title
				$bcn_breadcrumb->title = apply_filters("the_title", $post->post_title);
				if(is_home() && (!is_paged() || !$this->opt['paged_display']))
				{
					//Assign the prefix
					$bcn_breadcrumb->prefix = $this->opt['current_item_prefix'] . $this->opt['page_prefix'];
					//Assign the suffix
					$bcn_breadcrumb->suffix = $this->opt['page_suffix'] . $this->opt['current_item_suffix'];
				}
				else
				{
					//Assign the prefix
					$bcn_breadcrumb->prefix = $this->opt['page_prefix'];
					//Assign the suffix
					$bcn_breadcrumb->suffix = $this->opt['page_suffix'];
					//Deal with the anchor
					$bcn_breadcrumb->anchor = str_replace("%title%", $bcn_breadcrumb->title, str_replace("%link%", get_permalink($post->ID), $this->opt['blog_anchor']));
					//Yes link it
					$bcn_breadcrumb->linked = true;	
				}
				//Done with the current item, now on to the parents
				$bcn_frontpage = get_option('page_on_front');
				//If there is a parent page let's find it
				if($post->post_parent && $post->ID != $post->post_parent && $bcn_frontpage != $post->post_parent)
				{
					$this->page_parents($post->post_parent, $bcn_frontpage);
				}
			}
			//Sometimes we don't have a blog breadcrumb in the trail
			if($this->opt['home_display'])
			{
				//Add new breadcrumb to the trail
				$this->trail[] = new bcn_breadcrumb();
				//Figure out where we placed the crumb, make a nice pointer to it
				$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
				//Assign the prefix
				$bcn_breadcrumb->prefix = $this->opt['home_prefix'];
				//Assign the suffix
				$bcn_breadcrumb->suffix = $this->opt['home_suffix'];
				//Assign the title
				$bcn_breadcrumb->title = $this->opt['home_title'];
				//Deal with the anchor
				$bcn_breadcrumb->anchor = str_replace("%title%", $this->opt['home_title'], str_replace("%link%", get_option('home'), $this->opt['home_anchor']));
				//Yes link it
				$bcn_breadcrumb->linked = true;	
			}
		}
		//On everything else we need to link, but no current item (pre/suf)fixes
		else if($this->opt['home_display'])
		{
			//Add new breadcrumb to the trail
			$this->trail[] = new bcn_breadcrumb();
			//Figure out where we placed the crumb, make a nice pointer to it
			$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
			//Assign the prefix
			$bcn_breadcrumb->prefix = $this->opt['home_prefix'];
			//Assign the suffix
			$bcn_breadcrumb->suffix = $this->opt['home_suffix'];
			//Assign the title 
			$bcn_breadcrumb->title = $this->opt['home_title'];
			//Deal with the anchor
			$bcn_breadcrumb->anchor = str_replace("%title%", $this->opt['home_title'], str_replace("%link%", get_option('home'), $this->opt['home_anchor']));
			//Yes link it
			$bcn_breadcrumb->linked = true;	
		}
	}
	/**
	 * do_404
	 * 
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for 404 pages.
	 */
	function do_404()
	{
		global $post;
		$this->trail[] = new bcn_breadcrumb();
		//Figure out where we placed the crumb, make a nice pointer to it
		$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
		//Assign the prefix
		$bcn_breadcrumb->prefix = $this->opt['current_item_prefix'] . $this->opt['404_prefix'];
		//Assign the suffix
		$bcn_breadcrumb->suffix = $this->opt['404_suffix'] . $this->opt['current_item_suffix'];
		//Assign the title
		$bcn_breadcrumb->title = $this->opt['404_title'];
	}
	/**
	 * do_paged
	 * 
	 * A Breadcrumb Trail Filling Function
	 * 
	 * This functions fills a breadcrumb for paged pages.
	 */
	function do_paged()
	{
		global $paged;
		$this->trail[] = new bcn_breadcrumb();
		//Figure out where we placed the crumb, make a nice pointer to it
		$bcn_breadcrumb = &$this->trail[count($this->trail) - 1];
		//Assign the prefix
		$bcn_breadcrumb->prefix = $this->opt['paged_prefix'];
		//Assign the suffix
		$bcn_breadcrumb->suffix = $this->opt['paged_suffix'];
		//Assign the title
		$bcn_breadcrumb->title = $paged;
	}
	/**
	 * fill
	 * 
	 * Breadcrumb Trail Filling Function
	 * 
	 * This functions fills the breadcrumb trail.
	 */
	function fill()
	{
		global $wpdb, $post, $wp_query, $bcn_version, $paged;
		//Do specific opperations for the various page types
		//Check if this isn't the first of a multi paged item
		if(is_paged() && $this->opt['paged_display'])
		{
			$this->do_paged();
		}
		//For the front page, as it may also validate as a page
		if(is_front_page())
		{
			//Must have two seperate branches so that we don't evaluate it as a page
			if($this->opt['home_display'])
			{
				$this->do_front_page();
			}
		}
		//For searches
		else if(is_search())
		{
			$this->do_search();
		}
		//For pages
		else if(is_page())
		{
			$this->do_page();
		}
		//For post/page attachments
		else if(is_attachment())
		{
			$this->do_attachment();
		}
		//For blog posts
		else if(is_single())
		{
			$this->do_post();
		}
		//For author pages
		else if(is_author())
		{
			$this->do_author();
		}
		//For category based archives
		else if(is_archive() && is_category())
		{
			$this->do_archive_by_category();
		}
		//For date based archives
		else if(is_archive() && is_date())
		{
			$this->do_archive_by_date();
		}
		//For tag based archives
		else if(is_archive() && is_tag())
		{
			$this->do_archive_by_tag();
		}
		//For 404 pages
		else if(is_404())
		{
			$this->do_404();
		}
		//We always do the home link last, unless on the frontpage
		if(!is_front_page())
		{
			$this->do_home();
		}
		//We build the trail backwards the last thing to do is to get it back to normal order
		krsort($this->trail);
	}
	/**
	 * display
	 * 
	 * Breadcrumb Creation Function
	 * 
	 * This functions outputs or returns the breadcrumb trail.
	 *
	 * @param  (bool)   $bcn_return Whether to return data or to echo it.
	 * @param  (bool)   $bcn_linked Whether to allow hyperlinks in the trail or not.
	 * 
	 * @return (void)   Void if Option to print out breadcrumb trail was chosen.
	 * @return (string) String-Data of breadcrumb trail. 
	 */
	function display($bcn_return = false, $bcn_linked = true)
	{
		global $bcn_version;
		//Initilize the string which will hold the compiled trail
		$bcn_trail_str = "";
		//The main compiling loop
		foreach($this->trail as $key=>$breadcrumb)
		{
			//We only use a separator if there is more than one element
			if($key < count($this->trail) - 1)
			{
				$bcn_trail_str .= $this->opt['separator'];
			}
			//If we are on the current item, we better check if we need to link it
			if($key === 0 && $this->opt['current_item_linked'])
			{
				$breadcrumb->linked = true;
				$breadcrumb->anchor = str_replace("%title%", $breadcrumb->title, str_replace("%link%", "", $this->opt['current_item_anchor']));	
			}
			//Place in the breadcrumb's elements
			$bcn_trail_str .= $breadcrumb->prefix;
			//If we are linked we'll need to do up the link
			if($breadcrumb->linked && $bcn_linked)
			{
				$bcn_trail_str .= $breadcrumb->anchor . $breadcrumb->title . "</a>";
			}
			//Otherwise we just slip in the title
			else
			{
				$bcn_trail_str .= $breadcrumb->title;
			}
			$bcn_trail_str .= $breadcrumb->suffix;
		}
		//Should we return or echo the compiled trail?
		if($bcn_return)
		{
			return $bcn_trail_str;
		}
		else
		{
			//Giving credit where credit is due, please don't remove it
			$bcn_tag = "<!-- Breadcrumb NavXT " . $this->version . " -->\n";
			echo $bcn_tag . $bcn_trail_str;
		}
	}
}
<?php
/*
Plugin Name: Breadcrumb NavXT - Core
Plugin URI: http://mtekk.weblogs.us/code/breadcrumb-navxt/
Description: Adds a breadcrumb navigation showing the visitor&#39;s path to their current location. This plug-in provides direct access to the bcn_breadcrumb class without using the administrative interface. For details on how to use this plugin visit <a href="http://mtekk.weblogs.us/code/breadcrumb-navxt/">Breadcrumb NavXT</a>. 
Version: 2.1.4
Author: John Havlik
Author URI: http://mtekk.weblogs.us/
*/
/*  Copyright 2007-2008  John Havlik  (email : mtekkmonkey@gmail.com)

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
$bcn_version = "2.1.4";
//The main class
class bcn_breadcrumb
{
	var $opt;
	var $breadcrumb;
	//Class Constructor
	function bcn_breadcrumb()
	{
		//Setting array
		$this->opt = array(
				'static_frontpage' => 'false',
			//*** only used if 'static_frontpage' => true
			//Relative URL for your blog's address that is used for the Weblog link. 
			//Use it if your blog is available at http://www.site.com/myweblog/, 
			//and at http://www.site.com/ a Wordpress page is being displayed:
			//In this case apply '/myweblog/'.
				'url_blog' => '',
			//Display HOME? If set to false, HOME is not being displayed. 
				'home_display' => 'true',
			//URL for the home link
				'url_home' => get_option('home') . "/",
			//Apply a link to HOME? If set to false, only plain text is being displayed.
				'home_link' => 'true',
			//Text displayed for the home link, if you don't want to call it home then just change this.
			//Also, it is being checked if the current page title = this variable. If yes, only the Home link is being displayed,
			//but not a weird "Home / Home" breadcrumb.	
				'title_home' => 'Home',
			//Text displayed for the weblog. If "'static_frontpage' => false", you
			//might want to change this value to "Home" 
				'title_blog' => 'Blog',
			//Separator that is placed between each item in the breadcrumb navigation, but not placed before
			//the first and not after the last element. You also can use images here,
			//e.g. '<img src="separator.gif" title="separator" width="10" height="8" />'
				'separator' => ' &gt; ',
			//Prefix for a search page
				'search_prefix' => 'Search results for &#39;',
			//Suffix for a search page
				'search_suffix' => '&#39;',
			//Prefix for a author page
				'author_prefix' => 'Posts by ',
			//Suffix for a author page
				'author_suffix' => '',
			//Prefix for an attachment post
				'attachment_prefix' => 'Attachment: ',
			//Suffix for an attachment post
				'attachment_suffix' => '',
			//Name format to display for author (e.g., nickname, first_name, last_name, display_name)
				'author_display' => 'display_name',
			//Prefix for a single blog article.
				'singleblogpost_prefix' => 'Blog article: ',
			//Suffix for a single blog article.
				'singleblogpost_suffix' => '',
			//Prefix for a page.
				'page_prefix' => '',
			//Suffix for a page.
				'page_suffix' => '',
			//The prefix that is used for mouseover link (e.g.: "Browse to: Archive")
				'urltitle_prefix' => 'Browse to: ',
			//The suffix that is used for mouseover link
				'urltitle_suffix' => '',
			//Prefix for categories.
				'archive_category_prefix' => 'Archive by category &#39;',
			//Suffix for categories.
				'archive_category_suffix' => '&#39;',
			//Prefix for archive by year/month/day
				'archive_date_prefix' => 'Archive for ',
			//Suffix for archive by year/month/day
				'archive_date_suffix' => '',
			//Archive date format (e.g., ISO (yy/mm/dd), US (mm/dd/yy), EU (dd/mm/yy))
				'archive_date_format' => 'EU',
			//Prefix for tags.
				'archive_tag_prefix' => 'Archive by tag &#39;',
			//Suffix for tags.
				'archive_tag_suffix' => '&#39;',
			//Text displayed for a 404 error page, , only being used if 'use404' => true
				'title_404' => '404',
			//Display the paged information on pages that are paged
				'paged_display' => 'false',
			//Prefix to be displayed before the page number
				'paged_prefix' => ', Page ',
			//Suffix to be displayed after the page number
				'paged_suffix' => '',
			//Display current item as link?
				'link_current_item' => 'false',
			//URL title of current item, only being used if 'link_current_item' => true
				'current_item_urltitle' => 'Link of current page (click to refresh)', //
			//Style or prefix being applied as prefix to current item. E.g. <span class="bc_current">
				'current_item_style_prefix' => '',
			//Style or prefix being applied as suffix to current item. E.g. </span>
				'current_item_style_suffix' => '',
			//Maximum number of characters of post title to be displayed? 0 means no limit.
				'posttitle_maxlen' => 0,
			//Display category or tag when displaying single blog post (e.g., tag or category)
				'singleblogpost_taxonomy' => 'category',
			//Display category/tag when displaying single blog post
				'singleblogpost_taxonomy_display' => 'true',
			//Prefix for single blog post category, only being used if 'singleblogpost_taxonomy_display' => true
				'singleblogpost_category_prefix' => '',
			//Suffix for single blog post category, only being used if 'singleblogpost_taxonomy_display' => true
				'singleblogpost_category_suffix' => '',
			//Prefix for single blog post category, only being used if 'singleblogpost_taxonomy_display' => true
				'singleblogpost_tag_prefix' => '',
			//Suffix for single blog post tag, only being used if 'singleblogpost_taxonomy_display' => true
				'singleblogpost_tag_suffix' => '',
		);
		//Initilize breadcrumb stream
		$this->breadcrumb = array
		(
			//Used for the blog title
			'title' => NULL,
			//Used for the category/page hierarchy
			'middle' => NULL,
				//Used for the current tiem
			'last' => array
			(
				'prefix' => NULL,
				'item' => NULL,
				'suffix' => NULL
			)
		);
	}
	//Handle the home page or the first link part
	function do_home()
	{
		//Static front page
		if(get_option('show_on_front') == 'page')
		{
			//If we're displaying the home
			if($this->opt['home_display'] === 'true')
			{
				//Should we display the home link or not
				if($this->opt['home_link'] === 'true')
				{
					//If so, let's set it up
					$this->breadcrumb['title'] = '<a title="' . $this->opt['urltitle_prefix'] . $this->opt['title_home'] . $this->opt['urltitle_suffix'] . '" href="' . $this->opt['url_home'] . '">' . $this->opt['title_home'] . '</a>';
				}
				else
				{
					//Otherwise just the specified 'title_home' will do
					$this->breadcrumb['title'] = $this->opt['title_home'];
				}
			}
		}
		//If it's paged, we'll want to link it to the first page
		else if(is_paged() && $this->opt['paged_display'] === 'true')
		{
			$this->breadcrumb['title'] = '<a title="' . $this->opt['urltitle_prefix'] . $this->opt['title_blog'] . $this->opt['urltitle_suffix'] . '" href="' . get_option('home') . '">' . $this->opt['title_blog'] . '</a>';
		}
		//Non-static front page, if link current item is off
		else if($this->opt['link_current_item'] === 'false') 
		{
			$this->breadcrumb['title'] = $this->opt['title_blog'];
		}
		//Default to linking this is kinda hackish as we usually don't build links for the current item outside of the assembler
		else
		{
			$this->breadcrumb['title'] = '<a title="' . $this->opt['current_item_urltitle'] . '" href="' . get_option('home') . '">' . $this->opt['title_blog'] . '</a>';
		}
	}
	function do_title()
	{
		//If there are static front pages we need to make sure that link shows up as well as the blog title.	
		if(get_option('show_on_front') == 'page' && $this->opt['home_display'] === 'true')
		{
			//Single posts, archives of all types, and the author pages are descendents of "blog"
			if(is_page())
			{
				$this->breadcrumb['title'] = '<a title="' . $this->opt['urltitle_prefix'] . $this->opt['title_home'] . $this->opt['urltitle_suffix'] . '" href="' . $this->opt['url_home'] . '">' . $this->opt['title_home'] . '</a>';
			}
			else if(is_single() || is_archive() || is_author() || (is_home() && $this->opt['link_current_item'] === 'true'))
			{
				$this->breadcrumb['title'] = array();
				$this->breadcrumb['title'][] = '<a title="' . $this->opt['urltitle_prefix'] . $this->opt['title_home'] . $this->opt['urltitle_suffix'] . '" href="' . $this->opt['url_home'] . '">' . $this->opt['title_home'] . '</a>';
				$this->breadcrumb['title'][] = '<a title="' . $this->opt['urltitle_prefix'] . $this->opt['title_blog'] . $this->opt['urltitle_suffix'] . '" href="' . $this->opt['url_home'] . $this->opt['url_blog'] . '">' . $this->opt['title_blog'] . '</a>';
			}
			//If it's on the blog page but we don't link current
			else if(is_home())
			{
				$this->breadcrumb['title'] = array();
				$this->breadcrumb['title'][] = '<a title="' . $this->opt['urltitle_prefix'] . $this->opt['title_blog'] . $this->opt['urltitle_suffix'] . '" href="' . $this->opt['url_home'] . '">' . $this->opt['title_home'] . '</a>';
				$this->breadcrumb['title'][] = $this->opt['title_blog'];
			}
		}
		else
		{
			$this->breadcrumb['title'] = '<a title="' . $this->opt['urltitle_prefix'] . $this->opt['title_blog'] . $this->opt['urltitle_suffix'] . '" href="' . get_option('home') . '">' . $this->opt['title_blog'] . '</a>';
		}
	}
	//Handle search pages
	function do_search()
	{
		Global $s;
		//Get the search prefix
		$this->breadcrumb['last']['prefix'] = $this->opt['search_prefix'];
		//Get the searched text
		$this->breadcrumb['last']['item'] = wp_specialchars($s, 1);
		//Get the search suffix
		$this->breadcrumb['last']['suffix'] = $this->opt['search_suffix'];
	}
	//Handle "static" pages
	function do_page()
	{
		global $post;
		//Get the post title, this is a more robust method than using $post
		$bcn_page_title = trim(wp_title('', false));
		$bcn_parent_id = $post->post_parent;
		$bcn_middle = array();
		if($bcn_parent_id != 0)
		{
			//Fill the initial page
			//Use WordPress API, though a bit heavier than the old method, this will ensure compatibility with other plug-ins
			$bcn_parent = get_post($bcn_parent_id);
			$bcn_middle[] = '<a href="' . get_permalink($bcn_parent_id) . '" title="' . $this->opt['urltitle_prefix'] . $bcn_parent->post_title . $this->opt['urltitle_suffix'] . '">' . $bcn_parent->post_title . '</a>';
			$bcn_parent_id  = $bcn_parent->post_parent;
			while(is_numeric($bcn_parent_id) && $bcn_parent_id != 0)
			{
				$bcn_parent = get_post($bcn_parent_id);
				//Pushback a page into the array
				$bcn_middle[] = '<a href="' . get_permalink($bcn_parent_id) . '" title="' . $this->opt['urltitle_prefix'] . $bcn_parent->post_title . $this->opt['urltitle_suffix'] . '">' . $bcn_parent->post_title . '</a>';
				$bcn_parent_id = $bcn_parent->post_parent;
			}
			krsort($bcn_middle);
		}
		//Check to advoid Home > Home condition, has quick fallout for non-static conditions
		if(get_option('page_on_front') == 0 || !$this->opt['static_frontpage'] || (strtolower($bcn_page_title) != strtolower($this->opt['title_home'])))
		{
			$this->breadcrumb['middle'] = $bcn_middle;
			$this->breadcrumb['last']['prefix'] = $this->opt['page_prefix'];
			$this->breadcrumb['last']['item'] = $bcn_page_title;
			$this->breadcrumb['last']['suffix'] = $this->opt['page_suffix'];
		}		
	}
	//Handle attachment pages
	function do_attachment()
	{
		global $post;
		//Blog link and parent page
		$bcn_parent_id = $post->post_parent;
		//Get the parent information
		$bcn_parent = get_post($bcn_parent_id);
		//If the parent is a page we treat attachments like pages
		if($bcn_parent->post_type == "page")
		{
			$this->do_page();
		}
		//Otherwise we treat them like attachments
		else
		{
			//Setup the attachment's parent link
			$bcn_parents = '<a title="' . $this->opt['urltitle_prefix'] .
			$bcn_parent->post_title . $this->opt['urltitle_suffix'] . '" href="' . get_permalink($bcn_parent_id) . '">' . $bcn_parent->post_title . '</a>';
			$this->breadcrumb['middle'] = $bcn_parents;
			//Attachment prefix text
			$this->breadcrumb['last']['prefix'] = $this->opt['attachment_prefix'];
			//Get attachment name
			$this->breadcrumb['last']['item'] = trim(wp_title('', false));
			//Attachment suffix text
			$this->breadcrumb['last']['suffix'] = $this->opt['attachment_suffix'];
		}	
	}
	//Figure out the categories leading up to the post
	function single_categories()
	{
		global $post;
		$this->breadcrumb['middle'] = array();
		//Fills the object to get 
		$bcn_object = get_the_category();
		//Now find which one has a parrent, pick the first one that does
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
		//Get parents of current category
		$bcn_category = $bcn_object[$bcn_use_category];
		//Fill the initial category
		$this->breadcrumb['middle'][] = $this->opt['singleblogpost_category_prefix'] . '<a href="' . get_category_link($bcn_category->cat_ID) . '" title="' . $this->opt['urltitle_prefix'] . $bcn_category->cat_name . $this->opt['urltitle_suffix'] . '">' . $bcn_category->cat_name . '</a>'. $this->opt['singleblogpost_category_suffix'];
		$bcn_parent_id  = $bcn_category->category_parent;
		while($bcn_parent_id)
		{
			$bcn_category = get_category($bcn_parent_id);
			//Pushback a category into the array
			$this->breadcrumb['middle'][] = $this->opt['singleblogpost_category_prefix'] . '<a href="' . get_category_link($bcn_category->cat_ID) . '" title="' . $this->opt['urltitle_prefix'] . $bcn_category->cat_name . $this->opt['urltitle_suffix'] . '">' . $bcn_category->cat_name . '</a>' . $this->opt['singleblogpost_category_suffix'];
			$bcn_parent_id = $bcn_category->category_parent;
		}
		//We need to reverse the order (by key) to get the proper output
		krsort($this->breadcrumb['middle']);
	}
	//Figure out the tags leading up to the post
	function single_tags()
	{
		global $post;
		//Fills the object with the tags for the post
		$bcn_object = get_the_tags($post->ID);
		$i = 0;
		//Only process if we have tags
		if(is_array($bcn_object))
		{
			foreach($bcn_object as $tag)
			{
				//On the first run we don't need a separator
				if($i == 0)
				{
					$bcn_tags = $this->opt['singleblogpost_tag_prefix'] . '<a href="' . get_tag_link($tag->term_id) . '" title="' . $this->opt['urltitle_prefix'] . $tag->name . $this->opt['urltitle_suffix'] . '">' . $tag->name . '</a>'. $this->opt['singleblogpost_tag_suffix'];
					$i = 2;
				}
				else
				{
					$bcn_tags .= ', ' .$this->opt['singleblogpost_tag_prefix'] . '<a href="' . get_tag_link($tag->term_id) . '" title="' . $this->opt['urltitle_prefix'] . $tag->name . $this->opt['urltitle_suffix'] . '">' . $tag->name . '</a>'. $this->opt['singleblogpost_tag_suffix'];
	
				}
			}
		}
		else
		{
			$bcn_tags = "Untaged";	
		}
		$this->breadcrumb['middle'] = $bcn_tags;
	}
	//Handle single posts
	function do_post()
	{
		global $post;
		//Get the post title, this is a more robust method than using $post
		$bcn_post_title = trim(wp_title('', false));
		//Add categories if told to
		if($this->opt['singleblogpost_taxonomy_display'] === 'true')
		{		
			//If we're supposed to do tag hiearchy do that instead of category
			if($this->opt['singleblogpost_taxonomy'] == 'tag')
			{
				$this->single_tags();
			}
			else
			{
				$this->single_categories();
			}
		}
		//Trim post title if needed
		if($this->opt['posttitle_maxlen'] > 0 && (strlen($bcn_post_title) + 3) > $this->opt['posttitle_maxlen'])
		{
			$bcn_post_title = substr($bcn_post_title, 0, $this->opt['posttitle_maxlen'] - 1);
			$bcn_count = $this->opt['posttitle_maxlen'];
			//Make sure we can split at a space, but we want to limmit to cutting at max an additional 25%
			if(strpos($bcn_post_title, " ", 3 * $this->opt['posttitle_maxlen'] / 4) > 0)
			{
				//Don't split mid word
				while(substr($bcn_post_title,-1) != " ")
				{
					$bcn_post_title = substr($bcn_post_title, 0, -1);
				}
			}
			//remove the whitespace at the end and add the hellip
			$bcn_post_title = rtrim($bcn_post_title) . '&hellip;';
		}
		//Place it all in the array
		$this->breadcrumb['last']['prefix'] = $this->opt['singleblogpost_prefix'];
		$this->breadcrumb['last']['item'] = $bcn_post_title;
		$this->breadcrumb['last']['suffix'] = $this->opt['singleblogpost_suffix'];		
	}
	//Handle author pages
	function do_author()
	{
		//Author prefix text
		$this->breadcrumb['last']['prefix'] = $this->opt['author_prefix'];
		//Get the Author name, note it is an array
		$bcn_curauth = (get_query_var('author_name')) ? get_userdatabylogin(get_query_var('author_name')) : get_userdata(get_query_var('author'));
		//Get the Author display type
		$bcn_authdisp = $this->opt['author_display'];
		//Make sure user picks only safe values
		if($bcn_authdisp == 'nickname' || $bcn_authdisp == 'nickname' || $bcn_authdisp == 'first_name' || $bcn_authdisp == 'last_name' || $bcn_authdisp == 'display_name')
		{
			$this->breadcrumb['last']['item'] = $bcn_curauth->$bcn_authdisp;
		}
		$this->breadcrumb['last']['suffix'] = $this->opt['author_suffix'];
	}
	//Handle category based archives
	function do_archive_by_category()
	{
		global $wp_query;
		//Simmilar to using $post, but for things $post doesn't cover
		$bcn_object = $wp_query->get_queried_object();
		//Get parents of current category
		$bcn_parent_id  = $bcn_object->category_parent;
		$cat_breadcrumbs = '';
		while($bcn_parent_id)
		{
			$bcn_category = get_category($bcn_parent_id);
			$cat_breadcrumbs = '<a href="' . get_category_link($bcn_category->cat_ID) . '" title="' . $this->opt['urltitle_prefix'] . $bcn_category->cat_name . $this->opt['urltitle_suffix'] . '">' . $bcn_category->cat_name . '</a>' . $this->opt['separator'] . $cat_breadcrumbs;
			$bcn_parent_id = $bcn_category->category_parent;
		}
		//New hiearchy dictates that cateories look like parent pages, and thus
		$this->breadcrumb['last']['prefix'] = $cat_breadcrumbs;
		$this->breadcrumb['last']['prefix'] .= $this->opt['archive_category_prefix'];
		//Current Category, uses WP API to get the title of the page, hopefully itis more robust than the old method
		$this->breadcrumb['last']['item'] = trim(wp_title('', false));
		$this->breadcrumb['last']['suffix'] = $this->opt['archive_category_suffix'];		
	}
	//Handle date based archives
	function do_archive_by_date()
	{
		//If it's archives by day
		if(is_day())
		{
			//If the date format is US style
			if($this->opt['archive_date_format'] == 'US')
			{
				$this->breadcrumb['last']['prefix'] = $this->opt['archive_date_prefix'] . 
					'<a title="Browse to the ' . get_the_time('F') . ' ' . get_the_time('Y') . 
					' archive" href="' . get_year_link(get_the_time('Y')) . get_the_time('m') . 
					'">' . get_the_time('F') . '</a>' . ' ';
				$this->breadcrumb['last']['item'] = get_the_time('jS');
				$this->breadcrumb['last']['suffix'] = ', ' . ' <a title="Browse to the ' . 
					get_the_time('Y') . ' archive" href="' . get_year_link(get_the_time('Y')) . 
					'">' . get_the_time('Y') . '</a>' . $this->opt['archive_date_suffix'];
			}
			//If the date format is ISO style
			else if($this->opt['archive_date_format'] == 'ISO')
			{
				$this->breadcrumb['last']['prefix'] = $this->opt['archive_date_prefix'] .
					' <a title="Browse to the ' . get_the_time('Y') . ' archive" href="' . 
					get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . 
					'</a> <a title="Browse to the ' . get_the_time('F') . ' ' . get_the_time('Y') . 
					' archive" href="' . get_year_link(get_the_time('Y')) . get_the_time('m') . 
					'">' . get_the_time('F') . '</a>' . ' ';
				$this->breadcrumb['last']['item'] = get_the_time('d');
				$this->breadcrumb['last']['suffix'] = $this->opt['archive_date_suffix'];
			}
			//If the date format is European style
			else
			{
				$this->breadcrumb['last']['prefix'] = $this->opt['archive_date_prefix'];
				$this->breadcrumb['last']['item'] = get_the_time('d');
				$this->breadcrumb['last']['suffix'] = ' ' .'<a title="Browse to the ' . 
					get_the_time('F') . ' ' . get_the_time('Y') . ' archive" href="' . 
					get_year_link(get_the_time('Y')) . get_the_time('m') . '">' . 
					get_the_time('F') . '</a>' . ' <a title="Browse to the ' . get_the_time('Y') . 
					' archive" href="' . get_year_link(get_the_time('Y')) . '">' . 
					get_the_time('Y') . '</a>' . $this->opt['archive_date_suffix'];
			}
		}
		//If it's archives by month
		else if(is_month())
		{
			$this->breadcrumb['last']['prefix'] = $this->opt['archive_date_prefix'];
			$this->breadcrumb['last']['item'] = get_the_time('F');
			$this->breadcrumb['last']['suffix'] = ' ' . '<a title="Browse to the ' . 
				get_the_time('Y') . ' archive" href="' . get_year_link(get_the_time('Y')) . '">' . 
				get_the_time('Y') . '</a>' . $this->opt['archive_date_suffix'];
		}
		//If it's archives by year
		else if(is_year())
		{
			$this->breadcrumb['last']['prefix'] = $this->opt['archive_date_prefix'];
			$this->breadcrumb['last']['item'] = get_the_time('Y');
			$this->breadcrumb['last']['suffix'] = $this->opt['archive_date_suffix'];
		}
	}
	//Handle tag based archives
	function do_archive_by_tag()
	{
		$this->breadcrumb['last']['prefix'] = $this->opt['archive_tag_prefix'];
		//Use the WordPress API for the page title, should hook better than the other method
		$this->breadcrumb['last']['item'] = trim(wp_title('', false));
		$this->breadcrumb['last']['suffix'] = $this->opt['archive_tag_suffix'];
	}
	//Handled paged items
	function do_paged()
	{
		global $paged;
		//For home pages
		if(is_home())
		{
			$this->breadcrumb['title'] .= $this->opt['paged_prefix'] . $paged . $this->opt['paged_suffix'];
		}
		//For archive/search pages
		else
		{
			$this->breadcrumb['last']['suffix'] .= $this->opt['paged_prefix'] . $paged . $this->opt['paged_suffix'];
		}
	}
	//This function assembles the breadcrumb for the current page
	function assemble()
	{
		global $wpdb, $post, $wp_query, $bcn_version, $paged;
		////////////////////////////////////
		//Do specific opperations for the various page types
		////////////////////////////////////
		//For the home/front page
		if(is_front_page())
		{
			$this->do_home();
		}
		//Otherwise we dosomething slightly different
		else
		{
			$this->do_title();
			//For searches
			if(is_search())
			{
				$this->do_search();
			}
			////////////////////////////////////
			//For pages
			else if(is_page())
			{
				$this->do_page();
			}
			////////////////////////////////////
			//For post/page attachments
			else if(is_attachment())
			{
				$this->do_attachment();
			}
			////////////////////////////////////
			//For blog posts
			else if(is_single())
			{
				$this->do_post();
			}
			////////////////////////////////////
			//For author pages
			else if(is_author())
			{
				$this->do_author();
			}
			////////////////////////////////////
			//For category based archives
			else if(is_archive() && is_category())
			{
				$this->do_archive_by_category();
			}
			////////////////////////////////////
			//For date based archives
			else if(is_archive() && is_date())
			{
				$this->do_archive_by_date();
			}
			////////////////////////////////////
			//For tag based archives
			else if(is_archive() && is_tag())
			{
				$this->do_archive_by_tag();
			}
			////////////////////////////////////
			//For 404 pages
			else if(is_404())
			{
				$this->breadcrumb['last']['item'] = $this->opt['title_404'];
			}
			////////////////////////////////////
			//For paged items
			if(is_paged() && $this->opt['paged_display'] === 'true')
			{
				$this->do_paged();
			}
		}
	}
	/**
	 * display
	 * 
	 * Breadcrumb Creation Function
	 * 
	 * This functions outputs or returns the breadcrumb trail.
	 *
	 * @param  (bool)   $bcn_return Wether to return data or to echo it
	 *	 
	 * @return (void)   Void if Option to print out breadcrumb trail was chosen.
	 * @return (string) String-Data of breadcrumb trail. 
	 */
	function display($bcn_return = false)
	{
		global $bcn_version;
		////////////////////////////////////
		//Assemble the breadcrumb
		$bcn_output = '';
		if($this->breadcrumb['title'])
		{
			if(is_array($this->breadcrumb['title']))
			{
				//If the title is an array we only allow two entries, so manually unrolling the loop is ok here
				//Should end up containing 'home'
				$bcn_output .= $this->breadcrumb['title'][0];
				//Should end up displaying 'blog'
				$bcn_output .= $this->opt['separator'] . $this->breadcrumb['title'][1];
			}
			else
			{
				$bcn_output .= $this->breadcrumb['title'];
			}
			if(is_array($this->breadcrumb['middle']))
			{
				foreach($this->breadcrumb['middle'] as $bcn_mitem)
				{
					$bcn_output .= $this->opt['separator'] . $bcn_mitem;
				}
			}
			else if($this->breadcrumb['middle'])
			{
				$bcn_output .= $this->opt['separator'] . $this->breadcrumb['middle'];
			}
			if($this->breadcrumb['last']['item'] != NULL)
			{
				if($this->opt['link_current_item'] === 'true')
				{
					$this->breadcrumb['last']['item'] = '<a title="' . $this->opt['current_item_urltitle'] . 
					'" href="' . '">' . 
					$this->breadcrumb['last']['item'] . '</a>';
				}
				$bcn_output .= $this->opt['separator'] . $this->opt['current_item_style_prefix'] . 
				$this->breadcrumb['last']['prefix'] . $this->breadcrumb['last']['item'] . 
				$this->breadcrumb['last']['suffix'] . $this->opt['current_item_style_suffix'];
			}
		}
		//Polyglot compatibility filter
		if(function_exists('polyglot_filter'))
		{
			$bcn_output = polyglot_filter($bcn_output);
		}
		//qTranslate compatibility filter
		if(function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage'))
		{
			$bcn_output = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($bcn_output);	
		}
		//Return it or echo it?
		if($bcn_return)
		{
			return $bcn_output;
		}
		else
		{
			//Giving credit where credit is due, please don't remove it
			$bcn_tag = "<!-- \nBreadcrumb, generated by Breadcrumb NavXT " . $bcn_version . " - http://mtekk.weblogs.us/code \n-->";
			echo $bcn_tag . $bcn_output;
		}
	}
}
?>
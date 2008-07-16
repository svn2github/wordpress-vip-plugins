<?php
/*
Plugin Name: Author Complete Post List
Plugin URI: http://www.bombolom.com/weblog/wordpress/PluginAuthorCompletePostList2-2007-11-06-00-35.html
Description: Displays the complete post list (all posts) of one Author including his participations as co-author.
Version: 2.0
Author: José Lopes
Author URI: http://www.bombolom.com/
*/


/*

This plugin displays the complete post list of one Author
including his participations as co-author, with the total number.

*/


/*	Copyright 2007 Jose Lopes (email: jose.lopes@paxjulia.com)

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
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

if(function_exists('load_plugin_textdomain'))
        load_plugin_textdomain('author-complete-post-list','wp-content/plugins/author-complete-post-list');

function total_posts ($presentAuthor='', $loginAuthor='', $lang='') {
// This function gets the total of posts for a given Author.
	
	// Constants for the language
	$title_01 = __("The author posts are:", "author-complete-post-list");
	$title_02 = __("The author has", "author-complete-post-list");
	$title_03 = __("post(s):", "author-complete-post-list");
	// End constants

	// initialize var:
	$k = 0;

	// Get all the existing posts:
	query_posts('posts_per_page=-1');
	
	// Loop
	if ( have_posts() ) :
	while ( have_posts() ) : the_post();
		
		$mainAuthor= get_post($post->ID, ARRAY_A);
		
		if ($mainAuthor[post_author] == $presentAuthor) {$k++;}

		else {
			$other_authors = get_post_custom_values('other_author');
			if ( count($other_authors) < 1 ) continue;
			else {
				if (in_array($loginAuthor, $other_authors, true)){
					$k++;
				}
				else {continue;}
				}
			}
	endwhile;
	else: continue;
	endif;

	if ($k == 0) {
		echo '<p><b>', $title_01, '</b></p>';
	}
	else {
		echo '<p><b>', $title_02, ' ', $k, ' ', $title_03, '</b></p>';
	}
}

function full_post_list ($presentAuthor='', $loginAuthor='', $lang='') {

	// Constants for the language
	$noPost = __("The author has no post.", "author-complete-post-list");
	$stored = __("stored in", "author-complete-post-list");
	$time = __("on", "author-complete-post-list");

	// End constants

	// initialize var:
	$k = 0;

	// Get all the existing posts:
	query_posts('posts_per_page=-1');

	if ( have_posts() ) :
	while ( have_posts() ) : the_post();
		
		$mainAuthor= get_post($post->ID, ARRAY_A);
		
		if ($mainAuthor[post_author] == $presentAuthor){
			echo '<p><b>-</b> <a href="', the_permalink();
			echo '" rel="bookmark" title="Permanent Link: ';
			echo the_title();
			echo '">';
			echo the_title();
			echo '</a> ', $time, ' ';
			echo the_time('d/m/Y');
			echo ', ', $stored, ' ';
			echo the_category('&');
			echo '</p>';
			echo "\n";
			echo "\n";
			$k++;
		}

		else {
			$other_authors = get_post_custom_values('other_author');

			if ( count($other_authors) < 1 ) continue;
			else {
				if (in_array($loginAuthor, $other_authors, true)){
					echo '<p><b>-</b> <a href="', the_permalink();
					echo '" rel="bookmark" title="Permanent Link: ';
					echo the_title();
					echo '">';
					echo the_title();
					echo '</a>', $time;
					echo the_time('d/m/Y');
					echo $stored;
					echo the_category('&');
					echo '</p>';
					echo "\n";
					echo "\n";
					$k++;
				}
				else {continue;}
				}
			}
	endwhile;
	else: continue;
	endif;
	
	if ($k = 0) echo $noPost;
}

?>

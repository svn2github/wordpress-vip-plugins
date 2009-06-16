<?php
/*
Plugin Name: Most Commented
Plugin URI: http://mtdewvirus.com/code/wordpress-plugins/
Description: Retrieves a list of the posts with the most comments. Modified for Last X days -- by DJ Chuang www.djchuang.com 
Version: 1.6
Author: Nick Momrik
Author URI: http://mtdewvirus.com/
*/

function mdv_most_commented( $no_posts = 5, $before = '<li>', $after = '</li>', $show_pass_post = false, $duration = '', $echo = true ) {
	global $wpdb;

	if ( !($posts = wp_cache_get('mdv_most_commented')) ) {
		$request = "SELECT ID, post_title, comment_count FROM $wpdb->posts WHERE post_status = 'publish'";
		if ( !$show_pass_post ) $request .= " AND post_password =''";
		if ( is_int($duration) ) $request .= " AND DATE_SUB(CURDATE(),INTERVAL ".$duration." DAY) < post_date ";
		if ( !is_int($no_posts) ) $no_posts = 5;
		$request .= " ORDER BY comment_count DESC LIMIT $no_posts";

		$posts = $wpdb->get_results($request);

		wp_cache_set('mdv_most_commented', $posts, '', 1800);
	}

	if ( $echo ) {
		if ( !empty($posts) ) {
			foreach ($posts as $post) {
				$post_title = apply_filters('the_title', $post->post_title);
				$comment_count = $post->comment_count;
				$permalink = get_permalink($post->ID);
				$mdv_most_commented .= $before . '<a href="' . $permalink . '" title="' . $post_title.'">' . $post_title . '</a> (' . $comment_count.')' . $after;
			}
		} else {
			$mdv_most_commented .= $before . "None found" . $after;
		}

		echo $mdv_most_commented;
	} else {
		return $posts;
	}
}

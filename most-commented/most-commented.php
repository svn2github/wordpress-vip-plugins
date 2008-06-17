<?php
/*
Plugin Name: Most Commented
Plugin URI: http://mtdewvirus.com/code/wordpress-plugins/
Description: Retrieves a list of the posts with the most comments. Modified for Last X days -- by DJ Chuang www.djchuang.com 
Version: 1.5
Author: Nick Momrik
Author URI: http://mtdewvirus.com/
*/

function mdv_most_commented($no_posts = 5, $before = '<li>', $after = '</li>', $show_pass_post = false, $duration='') {
    global $wpdb;
	
	$mdv_most_commented = wp_cache_get('mdv_most_commented');
	if ($mdv_most_commented === false) {
		$request = "SELECT ID, post_title, comment_count FROM $wpdb->posts";
		$request .= " WHERE post_status = 'publish'";
		if (!$show_pass_post) $request .= " AND post_password =''";
	
		if ($duration !="") $request .= " AND DATE_SUB(CURDATE(),INTERVAL ".$duration." DAY) < post_date ";
	
		$request .= " ORDER BY comment_count DESC LIMIT $no_posts";
		$posts = $wpdb->get_results($request);

		if ($posts) {
			foreach ($posts as $post) {
				$post_title = stripslashes($post->post_title);
				$comment_count = $post->comment_count;
				$permalink = get_permalink($post->ID);
				$mdv_most_commented .= $before . '<a href="' . $permalink . '" title="' . $post_title.'">' . $post_title . '</a> (' . $comment_count.')' . $after;
			}
		} else {
			$mdv_most_commented .= $before . "None found" . $after;
		}
	
		wp_cache_set('mdv_most_commented', $mdv_most_commented);
	} 

    echo $mdv_most_commented;
}
?>
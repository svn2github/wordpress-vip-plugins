<?php
/*
Plugin Name: Recent Comments
Plugin URI: http://mtdewvirus.com/code/wordpress-plugins/
Description: Retrieves a list of the most recent comments.
Version: 1.2
Author: Nick Momrik
Author URI: http://mtdewvirus.com/
*/

function most_recent_comments($no_comments = 5, $comment_lenth = 5, $before = '<li>', $after = '</li>', $show_pass_post = false, $comment_style = 0, $hide_pingbacks_trackbacks = false) {
	global $wpdb;
	$most_recent_comments = wp_cache_get('most_recent_comments');
	
	if ( false === $most_recent_comments ) {
		$request = "SELECT ID, comment_ID, comment_content, comment_author, comment_author_url, post_title FROM $wpdb->comments LEFT JOIN $wpdb->posts ON $wpdb->posts.ID=$wpdb->comments.comment_post_ID WHERE post_status IN ('publish','static') ";
		if( !$show_pass_post ) $request .= "AND post_password ='' ";
		if ( $hide_pingbacks_trackbacks ) $request .= "AND comment_type='' ";
		$request .= "AND comment_approved = '1' ORDER BY comment_ID DESC LIMIT $no_comments";

		$comments = $wpdb->get_results($request);
	
		$output = '';

		if ( $comments ) {
			$idx = 0;
			foreach ($comments as $comment) {
				$comment_author = stripslashes($comment->comment_author);
				if ($comment_author == "")
					$comment_author = "anonymous"; 
				$comment_content = strip_tags($comment->comment_content);
				$comment_content = stripslashes($comment_content);
				$words = split(" ", $comment_content); 
				$comment_excerpt = join(" ", array_slice($words, 0, $comment_lenth));
				$permalink = get_permalink($comment->ID) . "#comment-" . $comment->comment_ID;
				
				if ( 1 == $comment_style ) {
					$post_title = stripslashes($comment->post_title);
					$post_id= stripslashes($comment->post_id);
					$url = $comment->comment_author_url;
					$idx++;
					if ( 1 == $idx % 2 )
						$before = "<li class='statsclass1'>";
					else
						$before = "<li class='statsclass2'>";
					$output .= $before . "<a href='$permalink'>$comment_author</a>" . ' on <a href="' . get_permalink($comment->ID) . '">' . $post_title . '</a>' . $after;
				} else {
					$idx++;
					if ( 1 == $idx % 2 )
						$before = "<li class='statsclass1'>";
					else
						$before = "<li class='statsclass2'>";
		
					$output .= $before . '<strong>' . $comment_author . ':</strong> <a href="' . $permalink;
					$output .= '" title="View the entire comment by ' . $comment_author.'">' . $comment_excerpt.'</a>' . $after;
				}
			}

			$output = convert_smilies($output);
		} else {
			$output .= $before . "None found" . $after;
		}

		$most_recent_comments = $output;
		wp_cache_set('most_recent_comments', $most_recent_comments);
	}

	echo $most_recent_comments;
}
?>
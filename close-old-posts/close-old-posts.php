<?php

/*
Plugin Name: Close Old Posts
Plugin URI: http://wordpress.org/#
Description: Closes comments on old posts on the fly, without any DB queries. By default it's 14 days, change that setting by editing the plugin file.
Author: Matt Mullenweg
Version: 1.2
Author URI: http://photomatt.net/
*/ 

if ( !defined( 'COP_DAYS_OLD' ) )
	define( 'COP_DAYS_OLD', 14 ); // close comments after this many days

function close_old_posts( $posts ) {
	if ( !is_single() || empty( $posts ) )
		return $posts;

	if ( time() - strtotime( $posts[0]->post_date_gmt ) > ( COP_DAYS_OLD * 24 * 60 * 60 ) ) {
		$posts[0]->comment_status = 'closed';
		$posts[0]->ping_status = 'closed';
	}

	return $posts;
}

add_filter( 'the_posts', 'close_old_posts' );

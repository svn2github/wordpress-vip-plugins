<?php
/*
Plugin Name: JSON feed
Plugin URI: http://wordpress.org/extend/plugins/json-feed/
Description: Provides feeds in JSON form
Version: 1.3-WPCOM
Author: Chris Northwood (modified by Dan Phiffer)
Author URI: http://www.pling.org.uk/
*/

add_filter( 'query_vars', 'json_feed_queryvars' );

function json_feed_queryvars( $qvars ) {
	$qvars[] = 'jsonp';
	$qvars[] = 'date_format';
	$qvars[] = 'remove_uncategorized';
	return $qvars;
}

function json_feed() {
	$output = array();
	while ( have_posts() ) {
		the_post();
		$output[] = array
		(
			'id' => (int) get_the_ID(),
			'permalink' => get_permalink(),
			'title' => get_the_title(),
			'content' => get_the_content(),
			'excerpt' => get_the_excerpt(),
			'date' => get_the_time( json_feed_date_format() ),
			'categories' => json_feed_categories(),
			'tags' => json_feed_tags()
		);
		// WPCOM Mod - custom filter
		$output = apply_filters( 'json_feed_output', $output);
		
	}
	if ( get_query_var( 'jsonp' ) == '' ) {
		header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );
		echo json_encode( $output );
	}
	else {
		header( 'Content-Type: application/javascript; charset=' . get_option( 'blog_charset' ), true );
		echo get_query_var( 'jsonp' ) . '(' . json_encode( $output ) . ')';
	}
}

function json_feed_date_format() {
	if ( get_query_var( 'date_format' ) ) {
		return get_query_var( 'date_format' );
	}
	else {
		return 'F j, Y H:i';
	}
}

function json_feed_categories() {
	$categories = get_the_category();
	if ( is_array( $categories ) ) {
		$categories = array_values( $categories );
		if ( get_query_var( 'remove_uncategorized' ) ) {
			$categories = array_filter( $categories, 'json_feed_remove_uncategorized' );
		}
		return array_map( 'json_feed_format_category', $categories );
	}
	else {
		return array();
	}
}

function json_feed_remove_uncategorized( $category ) {
	if ( $category->slug == 'uncategorized' ) {
		return false;
	}
	else {
		return true;
	}
}

function json_feed_format_category( $category ) {
	return array
	(
		'id' => (int) $category->cat_ID,
		'title' => $category->cat_name,
		'slug' => $category->slug
	);
}

function json_feed_tags() {
	$tags = get_the_tags();
	if ( is_array( $tags ) ) {
		$tags = array_values( $tags );
		return array_map( 'json_feed_format_tag', $tags );
	}
	else {
		return array();
	}
}

function json_feed_format_tag( $tag ) {
	return array
	(
		'id' => (int) $tag->term_id,
		'title' => $tag->name,
		'slug' => $tag->slug
	);
}

add_action( 'do_feed_json', 'json_feed' );


// WPCOM Mod  - rewrite rules 
function json_feed_rewrite_rules( $rules ) {
	global $default_rewrite_rules;
	$add_rules = array ( 
		'feed/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?&feed=$matches[1]&jsonp=$matches[3]&date_format=$matches[5]&remove_uncategorized=$matches[7]',
			
		'(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?&feed=$matches[1]&jsonp=$matches[3]&date_format=$matches[5]&remove_uncategorized=$matches[7]',
		
		'comments/feed/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?&feed=$matches[1]&withcomments=1&jsonp=$matches[3]&date_format=$matches[5]&remove_uncategorized=$matches[7]',
			
		'comments/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?&feed=$matches[1]&withcomments=1&jsonp=$matches[3]&date_format=$matches[5]&remove_uncategorized=$matches[7]',
		
		'search/(.+)/feed/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?s=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
		
		'search/(.+)/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?s=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
		
		'category/(.+?)/feed/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?category_name=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
		
		'category/(.+?)/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?category_name=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
		
		'tag/(.+?)/feed/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?tag=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
		
		'tag/(.+?)/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?tag=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
		
		'author/([^/]+)/feed/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?author_name=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
		
		'author/([^/]+)/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?author_name=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
		
		'([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/feed/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&jsonp=$matches[6]&date_format=$matches[8]&remove_uncategorized=$matches[10]',
		
		'([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&feed=$matches[4]&jsonp=$matches[6]&date_format=$matches[8]&remove_uncategorized=$matches[10]',
		
		'([0-9]{4})/([0-9]{1,2})/feed/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&jsonp=$matches[5]&date_format=$matches[7]&remove_uncategorized=$matches[9]',
		
		'([0-9]{4})/([0-9]{1,2})/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?year=$matches[1]&monthnum=$matches[2]&feed=$matches[3]&jsonp=$matches[5]&date_format=$matches[7]&remove_uncategorized=$matches[9]',
		
		'([0-9]{4})/feed/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?year=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
		
		'([0-9]{4})/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?year=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
		
		'[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/feed/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$'
			=> 'index.php?attachment=$matches[1]&feed=$matches[2]&jsonp=$matches[3]&date_format=$matches[5]&remove_uncategorized=$matches[7]',
		
		'[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/attachment/([^/]+)/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?attachment=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
		
		'([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/feed/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&feed=$matches[5]&jsonp=$matches[7]&date_format=$matches[9]&remove_uncategorized=$matches[11]',
		
		'([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/([^/]+)/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&name=$matches[4]&feed=$matches[5]&jsonp=$matches[7]&date_format=$matches[9]&remove_uncategorized=$matches[11]',
		
		'[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/feed/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?attachment=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
		
		'[0-9]{4}/[0-9]{1,2}/[0-9]{1,2}/[^/]+/([^/]+)/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?attachment=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
		
		'.+?/attachment/([^/]+)/feed/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?attachment=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
		
		'.+?/attachment/([^/]+)/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?attachment=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
		
		'(.+?)/feed/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?pagename=$matches[1]&feed=$matches[2&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]]',
		
		'(.+?)/(json)(/jsonp/([^/]+))?(/date_format/([^/]+))?(/remove_uncategorized/([^/]+))?/?$' 
			=> 'index.php?pagename=$matches[1]&feed=$matches[2]&jsonp=$matches[4]&date_format=$matches[6]&remove_uncategorized=$matches[8]',
	);
	return array_merge( $add_rules, (array) $default_rewrite_rules );
}
add_filter( 'pre_transient_rewrite_rules', 'json_feed_rewrite_rules' );
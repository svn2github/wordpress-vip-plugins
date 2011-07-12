<?php

/*
 * Plugin Name: Cache Nav Menus
 * Description: Allows Core Nave Menus to be cached using WP.com's Advanced Post Cache
 * Author: Automattic
 */

function cache_nav_menu_parse_query( &$query ) {
	if ( !isset( $query->query_vars['post_type'] ) || 'nav_menu_item' !== $query->query_vars['post_type'] ) {
		return;
	}

	$query->query_vars['suppress_filters'] = false;
	$query->query_vars['cache_results'] = true;
}

add_action( 'parse_query', 'cache_nav_menu_parse_query' );

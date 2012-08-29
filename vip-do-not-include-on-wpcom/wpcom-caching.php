<?php
/**
 * This file contains a bunch of helper functions that handle add caching to core WordPress functions.
 */

/**
 * Cached version of get_category_by_slug.
 */
function wpcom_vip_get_category_by_slug( $slug ) {
	return wpcom_vip_get_term_by( 'slug', $slug, 'category' );
}

/**
 * Cached version of get_term_by.
 *
 * Many calls to get_term_by (with name or slug lookup) across on a single pageload can easily add up the query count.
 * This function helps prevent that by adding a layer of caching.
 * 
 */
function wpcom_vip_get_term_by( $field, $value, $taxonomy, $output = OBJECT, $filter = 'raw' ) {
	// ID lookups are cached
	if ( 'id' == $field )
		return get_term_by( $field, $value, $taxonomy, $output, $filter );

	$cache_key = $field . '_' . md5( $value );
	$term_id = wp_cache_get( $cache_key, 'get_term_by' );

	if ( false === $term_id ) {
		$term = get_term_by( $field, $value, $taxonomy );
		if ( $term && ! is_wp_error( $term ) )
			wp_cache_set( $cache_key, $term->term_id, 'get_term_by' );
		else
			wp_cache_set( $cache_key, 0, 'get_term_by' ); // if we get an invalid value, let's cache it anyway
	} else {
		$term = get_term( $term_id, $taxonomy, $output, $filter );
	}

	if ( is_wp_error( $term ) )
		$term = false;

	return $term;
}

/**
 * Cached version of get_page_by_title so that we're not making unnecessary SQL all the time
 */
function wpcom_vip_get_page_by_title( $title, $output = OBJECT, $post_type = 'page' ) {
	$cache_key = $post_type . '_' . sanitize_key( $title );
	$page_id = wp_cache_get( $cache_key, 'get_page_by_title' );

	if ( $page_id === false ) {
		$page = get_page_by_title( $title, $output, $post_type );
		$page_id = $page ? $page->ID : 0;
		wp_cache_set( $cache_key, $page_id, 'get_page_by_title' ); // We only store the ID to keep our footprint small
	}

	if ( $page_id )
		return get_page( $page_id, $output );

	return null;
}

/**
 * Flush the cache for published pages so we don't end up with stale data
 */
add_action( 'transition_post_status', 'wpcom_vip_flush_get_page_by_title_cache', 10, 3 );
function wpcom_vip_flush_get_page_by_title_cache( $new_status, $old_status, $post ) {
	if ( 'publish' == $new_status || 'publish' == $old_status )
		wp_cache_delete( $post->post_type . '_' . sanitize_key( $post->post_title ), 'get_page_by_title' );
}

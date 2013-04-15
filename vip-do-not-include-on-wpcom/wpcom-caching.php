<?php
/**
 * This file contains a bunch of helper functions that handle add caching to core WordPress functions.
 */

/**
 * Cached version of get_category_by_slug.
 *
 * @param string $slug Category slug
 * @return object|null|bool Term Row from database. Will return null if $slug doesn't match a term. If taxonomy does not exist then false will be returned.
 * @link http://vip.wordpress.com/documentation/uncached-functions/ Uncached Functions
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
 * @param string $field Either 'slug', 'name', or 'id'
 * @param string|int $value Search for this term value
 * @param string $taxonomy Taxonomy Name
 * @param string $output Optional. Constant OBJECT*, ARRAY_A, or ARRAY_N
 * @param string $filter Optional. Default is 'raw' or no WordPress defined filter will applied.
 * @return mixed|null|bool Term Row from database in the type specified by $filter. Will return false if $taxonomy does not exist or $term was not found.
 * @link http://vip.wordpress.com/documentation/uncached-functions/ Uncached Functions
 */
function wpcom_vip_get_term_by( $field, $value, $taxonomy, $output = OBJECT, $filter = 'raw' ) {
	// ID lookups are cached
	if ( 'id' == $field )
		return get_term_by( $field, $value, $taxonomy, $output, $filter );

	$cache_key = $field . '|' . $taxonomy . '|' . md5( $value );
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
 *
 * @param string $page_title Page title
 * @param string $output Optional. Output type; OBJECT*, ARRAY_N, or ARRAY_A.
 * @param string $post_type Optional. Post type; default is 'page'.
 * @return WP_Post|null WP_Post on success or null on failure
 * @link http://vip.wordpress.com/documentation/uncached-functions/ Uncached Functions
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
 *
 * @param string $new_status The post's new status
 * @param string $old_status The post's previous status
 * @param WP_Post $post The post
 * @link http://vip.wordpress.com/documentation/uncached-functions/ Uncached Functions
 */
function wpcom_vip_flush_get_page_by_title_cache( $new_status, $old_status, $post ) {
	if ( 'publish' == $new_status || 'publish' == $old_status )
		wp_cache_delete( $post->post_type . '_' . sanitize_key( $post->post_title ), 'get_page_by_title' );
}
add_action( 'transition_post_status', 'wpcom_vip_flush_get_page_by_title_cache', 10, 3 );

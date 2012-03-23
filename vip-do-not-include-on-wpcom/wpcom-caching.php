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

	$cache_key = $field . '_' . $value;
	$term_id = wp_cache_get( $cache_key, 'get_term_by' );

	if ( false === $term_id ) {
		$term = get_term_by( $field, $value, $taxonomy );
		if ( $term && ! is_wp_error( $term ) )
			wp_cache_set( $cache_key, $term->term_id, 'get_term_by' );
		else
			wp_cache_set( $cache_key, 0, 'get_term_by' ); // if we get an invalid value, let's cache it
	} else {
		$term = get_term( $term_id, $taxonomy, $output, $filter );
	}

	if ( is_wp_error( $term ) )
		$term = false;

	return $term;
}
<?php /*

THIS PLUGIN IS NOT READY FOR USE YET, PLEASE DON'T USE IT :)

**************************************************************************

P lugin Name: WordPress.com elasticsearch
D escription: Replaces WordPress's core front-end search functionality with one powered by <a href="http://www.elasticsearch.org/">elasticsearch</a>.
A uthor:      Automattic
A uthor URI:  http://automattic.com/

**************************************************************************

Copyright (C) 2012 Automattic

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License version 2 or greater,
as published by the Free Software Foundation.

You may NOT assume that you can use any other version of the GPL.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

The license for this software can likely be found here:
http://www.gnu.org/licenses/gpl-2.0.html

**************************************************************************

TODO:

* PHPDoc
* Search refinement using parameters like category, tags, authors, etc.

**************************************************************************/

class WPCOM_elasticsearch {

	private $do_found_posts;
	private $found_posts = 0;

	private $search_result;

	private static $instance;

	private function __construct() {
		/* Don't do anything, needs to be initialized via instance() method */
	}

	public function __clone() { wp_die( "Please don't __clone WPCOM_elasticsearch" ); }

	public function __wakeup() { wp_die( "Please don't __wakeup WPCOM_elasticsearch" ); }

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WPCOM_elasticsearch;
			self::$instance->setup();
		}
		return self::$instance;
	}

	public function setup() {
		if ( is_admin() || ! function_exists( 'es_api_search_index' ) )
			return;

		// Checks to see if we need to worry about found_posts
		add_filter( 'post_limits_request', array( $this, 'filter__post_limits_request' ), 999, 2 );

		# Note: Advanced Post Cache hooks in at 10 so it's important to hook in before that

		// Replaces the standard search query with one that fetches the posts based on post IDs supplied by ES
		add_filter( 'posts_request', array( $this, 'filter__posts_request' ), 5, 2 );

		// Nukes the FOUND_ROWS() database query
		add_filter( 'found_posts_query', array( $this, 'filter__found_posts_query' ), 5, 2 );

		// Since the FOUND_ROWS() query was nuked, we need to supply the total number of found posts
		add_filter( 'found_posts', array( $this, 'filter__found_posts' ), 5, 2 );
	}

	public function filter__post_limits_request( $limits, $query ) {
		if ( ! $query->is_search() )
			return $limits;

		if ( empty( $limits ) || ( isset( $query->query_vars['no_found_rows'] ) && $query->query_vars['no_found_rows'] ) ) {
			$this->do_found_posts = false;
		} else {
			$this->do_found_posts = true;
		}

		return $limits;
	}

	public function filter__posts_request( $sql, $query ) {
		global $wpdb;

		if ( ! $query->is_main_query() || ! $query->is_search() )
			return $sql;

		$page = ( empty( $query->query_vars['paged'] ) ) ? 1 : absint( $query->query_vars['paged'] );

		$es_query_args = array(
			'multi_match' => array(
				'query'  => $query->query_vars['s'],
				'fields' => array( 'title', 'content' ),
			),
			'size' => $query->query_vars['posts_per_page'],
			'from' => ( $page - 1 ) * $query->query_vars['posts_per_page'], // Offset
			//'fields' => array( 'id' ), // Only need IDs, WP will supply the rest
		);

		// Use this filter to add whatever facets you want
		$es_query_args = apply_filters( 'wpcom_elasticsearch_query_args', $es_query_args, $query );

		$this->search_result = es_api_search_index( $es_query_args, 'blog-search' );

		if ( is_wp_error( $this->search_result ) || ! is_array( $this->search_result ) || empty( $this->search_result['results'] ) || empty( $this->search_result['results']['hits'] ) ) {
			$this->found_posts = 0;
			return "SELECT * FROM $wpdb->posts WHERE 1=0 /* ES search results */";
		}

		// Get the post IDs of the results
		$post_ids = array();
		foreach ( (array) $this->search_result['results']['hits'] as $result ) {
			// Fields arg
			if ( ! empty( $result['fields'] ) && ! empty( $result['fields']['id'] ) ) {
				$post_ids[] = $result['fields']['id'];
			}
			// Full source objects
			elseif ( ! empty( $result['_source'] ) && ! empty( $result['_source']['id'] ) ) {
				$post_ids[] = $result['_source']['id'];
			}
			// Unknown results format
			else {
				return $sql;
			}
		}

		// Total number of results for paging purposes
		$this->found_posts = $this->search_result['results']['total'];

		// Replace the search SQL with one that fetches the exact posts we want in the order we want
		$post_ids_string = implode( ',', array_map( 'absint', $post_ids ) );
		return "SELECT * FROM {$wpdb->posts} WHERE {$wpdb->posts}.ID IN( {$post_ids_string} ) ORDER BY FIELD( {$wpdb->posts}.ID, {$post_ids_string} ) /* ES search results */";
	}

	public function filter__found_posts_query( $sql, $query ) {
		if ( ! $query->is_main_query() || ! $query->is_search() )
			return $sql;

		return '';
	}

	public function filter__found_posts( $found_posts, $query ) {
		if ( ! $query->is_main_query() || ! $query->is_search() )
			return $found_posts;

		return $this->found_posts;
	}

	public function get_search_result() {
		return ( ! empty( $this->search_result ) && ! empty( $this->search_result['results'] ) ) ? $this->search_result['results'] : false;
	}

	public function get_search_facets() {
		$search_result = $this->get_search_result();
		return ( ! empty( $search_result ) && ! empty( $search_result['facets'] ) ) ? $search_result['facets'] : array();
	}
}

function WPCOM_elasticsearch() {
	return WPCOM_elasticsearch::instance();
}

add_action( 'after_setup_theme', 'WPCOM_elasticsearch' );

?>
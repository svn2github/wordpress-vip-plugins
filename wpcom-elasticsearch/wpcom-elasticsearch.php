<?php /*

THIS PLUGIN IS NOT READY FOR USE YET, PLEASE DON'T USE IT :)

**************************************************************************

P lugin Name: WordPress.com elasticsearch
D escription: Replaces WordPress's core front-end search functionality with one powered by <a href="http://www.elasticsearch.org/">elasticsearch</a>.
A uthor:      Automattic
A uthor URI:  http://automattic.com/

**************************************************************************

Copyright (C) 2012-2013 Automattic

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

**************************************************************************/

require_once( __DIR__ . '/widget-facets.php' );

class WPCOM_elasticsearch {

	public $facets = array();

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
		if ( ! function_exists( 'es_api_search_index' ) )
			return;

		add_action( 'widgets_init', array( $this, 'action__widgets_init' ) );

		if ( ! is_admin() ) {

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
	}

	public function action__widgets_init() {
		register_widget( 'WPCOM_elasticsearch_Widget_Facets' );
	}

	public function filter__post_limits_request( $limits, $query ) {
		if ( ! $query->is_search() )
			return $limits;

		if ( empty( $limits ) || $query->get( 'no_found_rows' ) ) {
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

		$page = ( $query->get( 'paged' ) ) ? absint( $query->get( 'paged' ) ) : 1;

		// Start building the WP-style search query args
		// They'll be translated to ES format args later
		$es_wp_query_args = array(
			'query'          => $query->get( 's' ),
			'posts_per_page' => $query->get( 'posts_per_page' ),
			'paged'          => $page,
		);

		// Look for query variables that match registered facets (taxonomies and post_type)
		foreach ( $this->facets as $label => $facet ) {
			switch ( $facet['type'] ) {
				case 'taxonomy':
					$taxonomy = get_taxonomy( $this->facets[ $label ]['taxonomy'] );

					if ( ! $taxonomy )
						continue 2;  // switch() is considered a looping structure

					if ( $query->get( $taxonomy->query_var ) )
						$es_wp_query_args['terms'][ $this->facets[ $label ]['taxonomy'] ] = $query->get( $taxonomy->query_var );

					break;

				case 'post_type':
					if ( $query->get( 'post_type' ) && 'any' != $query->get( 'post_type' ) ) {
						$es_wp_query_args['post_type'] = $query->get( 'post_type' );
					}
					elseif ( ! empty( $_GET['post_type'] ) && post_type_exists( $_GET['post_type'] ) ) {
						$es_wp_query_args['post_type'] = explode( ',', $_GET['post_type'] );
					}
					elseif ( ! empty( $_GET['post_type'] ) && 'any' == $_GET['post_type'] ) {
						$es_wp_query_args['post_type'] = false;
					}
					else {
						$es_wp_query_args['post_type'] = false;//'post';
					}

					break;
			}
		}

		// Facets
		if ( ! empty( $this->facets ) ) {
			$es_wp_query_args['facets'] = $this->facets;
		}

		// You can use this filter to modify the search query parameters, such as controlling the post_type.
		// These arguments are in the format for wpcom_search_api_wp_to_es_args(), i.e. WP-style.
		$es_wp_query_args = apply_filters( 'wpcom_elasticsearch_wp_query_args', $es_wp_query_args, $query );

		// Convert the WP-style args into ES args
		$es_query_args = wpcom_search_api_wp_to_es_args( $es_wp_query_args );

		$es_query_args['fields'] = array( 'post_id' );

		// This filter is harder to use if you're unfamiliar with ES but it allows complete control over the query
		$es_query_args = apply_filters( 'wpcom_elasticsearch_query_args', $es_query_args, $query );

		// Do the actual search query!
		$this->search_result = es_api_search_index( $es_query_args, 'blog-search' );

		if ( is_wp_error( $this->search_result ) || ! is_array( $this->search_result ) || empty( $this->search_result['results'] ) || empty( $this->search_result['results']['hits'] ) ) {
			$this->found_posts = 0;
			return "SELECT * FROM $wpdb->posts WHERE 1=0 /* ES search results */";
		}

		// Get the post IDs of the results
		$post_ids = array();
		foreach ( (array) $this->search_result['results']['hits'] as $result ) {
			// Fields arg
			if ( ! empty( $result['fields'] ) && ! empty( $result['fields']['post_id'] ) ) {
				$post_ids[] = $result['fields']['post_id'];
			}
			// Full source objects
			elseif ( ! empty( $result['_source'] ) && ! empty( $result['_source']['id'] ) ) {
				$post_ids[] = $result['_source']['id'];
			}
			// Unknown results format
			else {
				return '';//$sql;
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

	public function set_facets( $facets ) {
		$this->facets = $facets;
	}

	public function get_search_result( $raw = false ) {
		if ( $raw )
			return $this->search_result;

		return ( ! empty( $this->search_result ) && ! is_wp_error( $this->search_result ) && is_array( $this->search_result ) && ! empty( $this->search_result['results'] ) ) ? $this->search_result['results'] : false;
	}

	public function get_search_facets() {
		$search_result = $this->get_search_result();
		return ( ! empty( $search_result ) && ! empty( $search_result['facets'] ) ) ? $search_result['facets'] : array();
	}

	public function get_search_facet_data() {
		if ( empty( $this->facets ) )
			return false;

		$facets = $this->get_search_facets();

		if ( ! $facets )
			return false;

		$facet_data = array();

		foreach ( $facets as $label => $facet ) {
			if ( empty( $this->facets[ $label ] ) )
				continue;

			$facets_data[ $label ] = $this->facets[ $label ];
			$facets_data[ $label ]['items'] = array();

			// All taxonomy terms are going to have the same query_var
			if( 'taxonomy' == $this->facets[ $label ]['type'] ) {
				$taxonomy = get_taxonomy( $this->facets[ $label ]['taxonomy'] );

				if ( ! $taxonomy )
					continue;

				$tax_query_var = $taxonomy->query_var;
			}

			$items = array();
			if ( ! empty( $facet['terms'] ) ) {
				$items = (array) $facet['terms'];
			}
			elseif ( ! empty( $facet['entries'] ) ) {
				$items = (array) $facet['entries'];
			}

			foreach ( $items as $item ) {
				$query_vars = array();

				switch ( $this->facets[ $label ]['type'] ) {
					case 'taxonomy':
						$term = get_term_by( 'id', $item['term'], $this->facets[ $label ]['taxonomy'] );

						if ( ! $term )
							continue 2; // switch() is considered a looping structure

						$query_vars = array( $tax_query_var => $term->slug );
						$name       = $term->name;

						break;

					case 'post_type':
						$post_type = get_post_type_object( $item['term'] );

						if ( ! $post_type )
							continue 2;  // switch() is considered a looping structure

						$query_vars = array( 'post_type' => $item['term'] );
						$name       = $post_type->labels->singular_name;

						break;

					case 'date_histogram':
						$timestamp = $item['time'] / 1000;

						switch ( $this->facets[ $label ]['interval'] ) {
							case 'year':
								$query_vars = array(
									'year' => date( 'Y', $timestamp ),
								);
								$name = date( 'Y', $timestamp );
								break;

							case 'month':
								$query_vars = array(
									'year'     => date( 'Y', $timestamp ),
									'monthnum' => date( 'n', $timestamp ),
								);
								$name = date( 'F Y', $timestamp );
								break;

							case 'day':
								$query_vars = array(
									'year'     => date( 'Y', $timestamp ),
									'monthnum' => date( 'n', $timestamp ),
									'day'      => date( 'j', $timestamp ),
								);
								$name = date( 'F jS, Y', $timestamp );
								break;

							default:
								continue 3; // switch() is considered a looping structure
						}

						break;

					default:
						//continue 2; // switch() is considered a looping structure
				}

				$facets_data[ $label ]['items'][] = array(
					'url'        => add_query_arg( array_merge( $query_vars, array( 's' => get_query_var( 's' ) ) ) ),
					'query_vars' => $query_vars,
					'name'       => $name,
					'count'      => $item['count'],
				);
			}
		}

		return $facets_data;
	}
}

function WPCOM_elasticsearch() {
	return WPCOM_elasticsearch::instance();
}

add_action( 'after_setup_theme', 'WPCOM_elasticsearch' );

?>
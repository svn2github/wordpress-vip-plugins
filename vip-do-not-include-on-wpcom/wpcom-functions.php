<?php

/**
 * Plugin Name: WPCOM Functions
 * Description: Adds wrappers for functions which are available on WP.com to assist with local development
 */

if ( ! function_exists( 'wpcom_is_vip' ) ) : // Do not load these on WP.com

	/**
	 * Update a user's attribute
	 *
	 * There is no need to serialize values, they will be serialized if it is
	 * needed. The metadata key can only be a string with underscores. All else will
	 * be removed.
	 *
	 * Will remove the attribute, if the meta value is empty.
	 *
	 * @param int $user_id User ID
	 * @param string $meta_key Metadata key.
	 * @param mixed $meta_value Metadata value.
	 * @return bool True on successful update, false on failure.
	 */
	function update_user_attribute( $user_id, $meta_key, $meta_value ) {
		do_action( 'updating_user_attribute', $user_id, $meta_key, $meta_value );

		$result = update_user_meta( $user_id, $meta_key, $meta_value );

		if ( $result )
			do_action( 'updated_user_attribute', $user_id, $meta_key, $meta_value );

		return $result;
	}

	/**
	 * Retrieve user attribute data.
	 *
	 * If $user_id is not a number, then the function will fail over with a 'false'
	 * boolean return value. Other returned values depend on whether there is only
	 * one item to be returned, which be that single item type. If there is more
	 * than one metadata value, then it will be list of metadata values.
	 *
	 * @param int $user_id User ID
	 * @param string $meta_key Optional. Metadata key.
	 * @return mixed
	 */
	function get_user_attribute( $user_id, $meta_key ) {
		if ( !$usermeta = get_user_meta( $user_id, $meta_key ) )
			return false;

		if ( count($usermeta) == 1 )
			return reset($usermeta);

		return $usermeta;
	}

	/**
	 * Remove user attribute data.
	 *
	 * @uses $wpdb WordPress database object for queries.
	 *
	 * @param int $user_id User ID.
	 * @param string $meta_key Metadata key.
	 * @param mixed $meta_value Metadata value.
	 * @return bool True deletion completed and false if user_id is not a number.
	 */
	function delete_user_attribute( $user_id, $meta_key, $meta_value = '' ) {
		$result = delete_user_meta( $user_id, $meta_key, $meta_value );

		do_action( 'deleted_user_attribute', $user_id, $meta_key, $meta_value );

		return $result;
	}

	// These functions are defined on WordPress.com and can be a common source of frustration for VIP devs
	// Now they can be frustrated in their local environments as well :)
	if ( ! function_exists( 'widont' ) ) :
		function widont( $str = '' ) {
			return preg_replace( '|([^\s])\s+([^\s]+)\s*$|', '$1&nbsp;$2', $str );
		}
		add_filter( 'the_title', 'widont' );
	endif;

	if ( ! function_exists( 'widont' ) ) :
		function wido( $str = '' ) {
			return str_replace( '&#160;', ' ', $str );
		}
		add_filter( 'the_title_rss', 'wido' );
	endif;

	/**
	 * Initiate a job to flush rewrite rules
	 * There's a lot of magic behind the scenes on WordPress.com
	 * In the local environment, we can just flush rewrite rules
	 */
	if ( ! function_exists( 'wpcom_initiate_flush_rewrite_rules' ) ) :
		function wpcom_initiate_flush_rewrite_rules() {
			flush_rewrite_rules( false );
		}
	endif;

	/**
	 * Executes an elasticsearch query via our REST API.
	 * Requires setup on our end and a paid addon to your hosting account.
	 * You probably shouldn't be using this function.
	 *
	 * Valid arguments:
	 *
	 * * size: Number of query results to return.
	 *
	 * * from: Offset, the starting result to return.
	 *
	 * * multi_match: Will do a match search against the default fields (this is almost certainly what you want).
	 *                e.g. array( 'query' => 'this is a test', 'fields' => array( 'content' ) )
	 *                See: http://www.elasticsearch.org/guide/reference/query-dsl/multi-match-query.html
	 *
	 * * query_string: Will do a query_string search, interprets the string as Lucene query syntax.
	 *                 e.g. array( 'default_field' => 'content', 'query' => 'tag:(world OR nation) howdy' )
	 *                 This can fail if the user doesn't close parenthesis, or specifies a field that is not valid.
	 *                 See: http://www.elasticsearch.org/guide/reference/query-dsl/query-string-query.html
	 *
	 * * more_like_this: Will do a more_like_this search, which is best for related content.
	 *                   e.g. array( 'fields' => array( 'title', 'content' ), 'like_text' => 'this is a test', 'min_term_freq' => 1, 'max_query_terms' => 12 )
	 *                   See: http://www.elasticsearch.org/guide/reference/query-dsl/mlt-query.html
	 *
	 * * facets: Structured set of facets. DO NOT do a terms facet on the content of posts/comments. It will load ALL terms into memory,
	 *           probably taking minutes to complete and slowing down the entire cluster. With great power... etc.
	 *           See: http://www.elasticsearch.org/guide/reference/api/search/facets/index.html
	 *
	 * * filters: Structured set of filters (often FASTER, since cached from one query to the next).
	 *            See: http://www.elasticsearch.org/guide/reference/query-dsl/filtered-query.html
	 *
	 * * highlight: Structure defining how to highlight the results.
	 *              See: http://www.elasticsearch.org/guide/reference/api/search/highlighting.html
	 *
	 * * fields: Structure defining what fields to return with the results.
	 *           See: http://www.elasticsearch.org/guide/reference/api/search/fields.html
	 *
	 * * sort: Structure defining how to sort the results.
	 *         See: http://www.elasticsearch.org/guide/reference/api/search/sort.html
	 *
	 * Questions? Ask us.
	 */
	if ( ! function_exists( 'es_api_search_index' ) ) :
		function es_api_search_index( $args ) {
			$defaults = array(
				'blog_id' => get_current_blog_id(),
			);

			$args = wp_parse_args( $args, $defaults );

			$args['blog_id'] = absint( $args['blog_id'] );

			$service_url = 'http://public-api.wordpress.com/rest/v1/sites/' . $args['blog_id'] . '/search';

			unset( $args['blog_id'] );

			$request = wp_remote_post( $service_url, array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body' => json_encode( $args ),
			) );

			if ( is_wp_error( $request ) )
				return false;

			return json_decode( wp_remote_retrieve_body( $request ), true );
		}
	endif; // function_exists( 'es_api_search_index' )

	if ( ! function_exists( 'wpcom_search_api_query' ) ) :
		// A wrapper for es_api_search_index() that accepts WP-style args
		// See wpcom_search_api_wp_to_es_args() for details
		// This is a copy/paste, up to date as of WP.com r65003 (Jan 7th, 2013)
		function wpcom_search_api_query( $args, $stat_app_name = 'blog-search' ) {
			$es_query_args = wpcom_search_api_wp_to_es_args( $args );

			return es_api_search_index( $es_query_args, $stat_app_name );
		}
	endif; // function_exists( 'es_api_search_index' )

	if ( ! function_exists( 'wpcom_search_api_wp_to_es_args' ) ) :
		// Converts WP-style args to ES args
		function wpcom_search_api_wp_to_es_args( $args ) {
			$defaults = array(
				'blog_id'        => get_current_blog_id(),

				'query'          => null,    // Search phrase

				'post_type'      => 'post',
				'terms'          => array(), // ex: array( 'taxonomy-1' => array( 'slug' ), 'taxonomy-2' => array( 'slug-a', 'slug-b' ) )

				'author'         => null,
				'author_name'    => null,

				'orderby'        => null,    // Defaults to 'relevance' if query is set, otherwise 'date'. Pass an array for multiple orders.
				'order'          => 'DESC',

				'posts_per_page' => 10,
				'offset'         => null,
				'paged'          => null,

				'facets'         => null,    // array( 'Some Categories' => array( 'type' => 'terms', 'field' => 'category.raw', 'size' => 10, 'query_var' => 'category_name' ) )
			);

			$raw_args = $args; // Keep a copy

			$args = wp_parse_args( $args, $defaults );

			$es_query_args = array(
				'blog_id' => absint( $args['blog_id'] ),
				'size'    => absint( $args['posts_per_page'] ),
			);

			// ES "from" arg (offset)
			if ( $args['offset'] ) {
				$es_query_args['from'] = absint( $args['offset'] );
			} elseif ( $args['paged'] ) {
				$es_query_args['from'] = max( 0, ( absint( $args['paged'] ) - 1 ) * $es_query_args['size'] );
			}

			// ES stores usernames, not IDs, so transform
			if ( ! empty( $args['author'] ) ) {
				$user = get_user_by( 'id', $args['author'] );

				if ( $user && ! empty( $user->user_login ) ) {
					$args['author_name'] = $user->user_login;
				}
			}

			// Build the filters from the query elements.
			// Filters rock because they are cached from one query to the next
			// but they are cached as individual filters, rather than all combined together.
			// May get performance boost by also caching the top level boolean filter too.
			$filters = array();

			if ( $args['post_type'] ) {
				$filters[] = array( 'type' => array( 'value' => $args['post_type'] ) );
			}

			if ( $args['author_name'] ) {
				$filters[] = array( 'term' => array( 'author.raw' => $args['author_name'] ) );
			}

			if ( is_array( $args['terms'] ) ) {
				foreach ( $args['terms'] as $tax => $terms ) {
					$terms = (array) $terms;
					if ( count( $terms ) ) {
						switch ( $tax ) {
							case 'post_tag':
								$tax_fld = 'tag.raw';
								break;
							case 'category':
								$tax_fld = 'category.raw';
								break;
							default:
								$tax_fld = 'taxonomy_raw.' . $tax;
								break;
						}
						$filters[] = array( 'terms' => array( $tax_fld => (array) $terms ) );
					}
				}
			}

			if ( ! empty( $filters ) ) {
				$es_query_args['filters'] = array( 'and' => $filters );
			} else {
				$es_query_args['filters'] = array( 'match_all' => new stdClass() );
			}

			// Fill in the query
			//  todo: add auto phrase searching
			//  todo: add fuzzy searching to correct for spelling mistakes
			//  todo: boost title, tag, and category matches
			if ( $args['query'] ) {
				$es_query_args['multi_match'] = array(
					'query'  => $args['query'],
					'fields' => array( 'title', 'content', 'author', 'tag', 'category' ),
					'operator'  => 'and',
				);

				if ( ! $args['orderby'] ) {
					$args['orderby'] = array( 'relevance' );
				}
			} else {
				if ( ! $args['orderby'] ) {
					$args['orderby'] = array( 'date' );
				}
			}

			// Validate the "order" field
			switch ( strtolower( $args['order'] ) ) {
				case 'asc':
					$args['order'] = 'asc';
					break;
				case 'desc':
				default:
					$args['order'] = 'desc';
					break;
			}

			$es_query_args['sort'] = array();
			foreach ( (array) $args['orderby'] as $orderby ) {
				// Translate orderby from WP field to ES field
				// todo: add support for sorting by title, num likes, num comments, num views, etc
				switch ( $orderby ) {
					case 'relevance' :
						$es_query_args['sort'][] = array( '_score' => array( 'sort' => $args['order'] ) );
						break;
					case 'date' :
						$es_query_args['sort'][] = array( 'date' => array( 'sort' => $args['order'] ) );
						break;
					case 'ID' :
						$es_query_args['sort'][] = array( 'id' => array( 'sort' => $args['order'] ) );
						break;
					case 'author' :
						$es_query_args['sort'][] = array( 'author.raw' => array( 'sort' => $args['order'] ) );
						break;
				}
			}
			if ( empty( $es_query_args['sort'] ) )
				unset( $es_query_args['sort'] );

			// Facets
			if ( ! empty( $args['facets'] ) ) {
				foreach ( (array) $args['facets'] as $label => $facet ) {
					$es_query_args['facets'][$label] = array( $facet['type'] => array( 'field' => $facet['field'], 'size' => $facet['size'] ) );
				}
			}

			return $es_query_args;
		}
	endif; // function_exists( 'wpcom_search_api_wp_to_es_args' )

endif; // function_exists( 'wpcom_is_vip' )
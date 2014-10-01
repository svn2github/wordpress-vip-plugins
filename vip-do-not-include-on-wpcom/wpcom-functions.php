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
	 * @param mixed $meta_value Optional. Metadata value.
	 * @return bool True deletion completed and false if user_id is not a number.
	 */
	function delete_user_attribute( $user_id, $meta_key, $meta_value = '' ) {
		$result = delete_user_meta( $user_id, $meta_key, $meta_value );

		do_action( 'deleted_user_attribute', $user_id, $meta_key, $meta_value );

		return $result;
	}


	if ( ! function_exists( 'widont' ) ) :
		/**
		 * Eliminates widows in strings by replace the breaking space that appears before the last word with a non-breaking space.
		 *
		 * This function is defined on WordPress.com and can be a common source of frustration for VIP devs.
		 * Now they can be frustrated in their local environments as well :)
		 *
		 * @param string $str Optional. String to operate on.
		 * @return string
		 * @link http://www.shauninman.com/post/heap/2006/08/22/widont_wordpress_plugin Typesetting widows
		 */
		function widont( $str = '' ) {
			// Don't apply on non-tablet mobile devices so the browsers can fit to the viewport properly.
			if (
				function_exists( 'jetpack_is_mobile' ) && jetpack_is_mobile() &&
				class_exists( 'Jetpack_User_Agent_Info' ) && ! Jetpack_User_Agent_Info::is_tablet()
			) {
				return $str;
			}

			// We're dealing with whitespace from here out, let's not have any false positives. :)
			$str = trim( $str );

			// If string contains three or fewer words, don't join.
			if ( count( preg_split( '#\s+#', $str ) ) <= 3 ) {
				return $str;
			}

			// Don't join if words exceed a certain length: minimum 10 characters, default 15 characters, filterable via `widont_max_word_length`.
			$widont_max_word_length = max( 10, absint( apply_filters( 'widont_max_word_length', 15 ) ) );
			$regex = '#\s+([^\s]{1,' . $widont_max_word_length . '})\s+([^\s]{1,' . $widont_max_word_length . '})$#';

			return preg_replace( $regex, ' $1&nbsp;$2', $str );
		}
		add_filter( 'the_title', 'widont' );
	endif;

	if ( ! function_exists( 'wido' ) ) :
		/**
		 * Replace any non-breaking spaces in a string with a regular space.
		 *
		 * This functions is defined on WordPress.com and can be a common source of frustration for VIP devs.
		 * Now they can be frustrated in their local environments as well :)
		 *
		 * @param string $str Optional. String to operate on.
		 * @return string
		 * @link http://www.shauninman.com/post/heap/2006/08/22/widont_wordpress_plugin Typesetting widows
		 */
		function wido( $str = '' ) {
			return str_replace( '&#160;', ' ', $str );
		}
		add_filter( 'the_title_rss', 'wido' );
	endif;

	if ( ! function_exists( 'wpcom_initiate_flush_rewrite_rules' ) ) :
		/**
		 * Initiate a job to flush rewrite rules
		 *
		 * There's a lot of magic behind the scenes on WordPress.com. In the local environment, we can just flush rewrite rules.
		 */
		function wpcom_initiate_flush_rewrite_rules() {
			flush_rewrite_rules( false );
		}
	endif;

	if ( ! function_exists( 'es_api_search_index' ) ) :
		/**
		 * Executes an elasticsearch query via our REST API.
		 *
		 * Requires setup on our end and a paid addon to your hosting account.
		 * You probably shouldn't be using this function. Questions? Ask us.
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
		 * * filter: Structured set of filters (often FASTER, since cached from one query to the next).
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
		 * @param array $args
		 * @return bool|string False if WP_Error, otherwise JSON string
		 */
		function es_api_search_index( $args ) {
			if ( class_exists( 'Jetpack' ) ) {
				$jetpack_blog_id = Jetpack::get_option( 'id' );
				if ( ! $jetpack_blog_id ) {
					return array( 'error' => 'Failed to get Jetpack blog_id' );
				}

				$args['blog_id'] = $jetpack_blog_id;
			}

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
		/**
		 * A wrapper for es_api_search_index() that accepts WP-style args
		 *
		 * This is a copy/paste, up to date as of WP.com r65003 (Jan 7th, 2013)
		 *
		 * @param array $args
		 * @param string $stat_app_name Optional.
		 * @return bool|string False if WP_Error, otherwise JSON string
		 * @see wpcom_search_api_wp_to_es_args() for details
		 */
		function wpcom_search_api_query( $args, $stat_app_name = 'blog-search' ) {
			$es_query_args = wpcom_search_api_wp_to_es_args( $args );

			return es_api_search_index( $es_query_args, $stat_app_name );
		}
	endif; // function_exists( 'es_api_search_index' )

	if ( ! function_exists( 'wpcom_search_api_wp_to_es_args' ) ) :
		/**
		 * Converts WP-style args to ES args
		 *
		 * @param array $args
		 * @return array
		 */
		function wpcom_search_api_wp_to_es_args( $args ) {
			$defaults = array(
				'blog_id'        => get_current_blog_id(),

				'query'          => null,    // Search phrase
				'query_fields'   => array( 'title', 'content', 'author', 'tag', 'category' ),

				'post_type'      => 'post',  // string or an array
				'terms'          => array(), // ex: array( 'taxonomy-1' => array( 'slug' ), 'taxonomy-2' => array( 'slug-a', 'slug-b' ) )

				'author'         => null,    // id or an array of ids
				'author_name'    => array(), // string or an array

				'date_range'     => null,    // array( 'field' => 'date', 'gt' => 'YYYY-MM-dd', 'lte' => 'YYYY-MM-dd' ); date formats: 'YYYY-MM-dd' or 'YYYY-MM-dd HH:MM:SS'

				'orderby'        => null,    // Defaults to 'relevance' if query is set, otherwise 'date'. Pass an array for multiple orders.
				'order'          => 'DESC',

				'posts_per_page' => 10,
				'offset'         => null,
				'paged'          => null,

				/**
				 * Facets. Examples:
				 * array(
				 *     'Tag'       => array( 'type' => 'taxonomy', 'taxonomy' => 'post_tag', 'count' => 10 ) ),
				 *     'Post Type' => array( 'type' => 'post_type', 'count' => 10 ) ),
				 * );
				 */
				'facets'         => null,
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

			if ( !is_array( $args['author_name'] ) ) {
				$args['author_name'] = array( $args['author_name'] );
			}

			// ES stores usernames, not IDs, so transform
			if ( ! empty( $args['author'] ) ) {
				if ( !is_array( $args['author'] ) )
					$args['author'] = array( $args['author'] );
				foreach ( $args['author'] as $author ) {
					$user = get_user_by( 'id', $author );

					if ( $user && ! empty( $user->user_login ) ) {
						$args['author_name'][] = $user->user_login;
					}
				}
			}

			// Build the filters from the query elements.
			// Filters rock because they are cached from one query to the next
			// but they are cached as individual filters, rather than all combined together.
			// May get performance boost by also caching the top level boolean filter too.
			$filters = array();

			if ( $args['post_type'] ) {
				if ( !is_array( $args['post_type'] ) )
					$args['post_type'] = array( $args['post_type'] );
				$filters[] = array( 'terms' => array( 'post_type' => $args['post_type'] ) );
			}

			if ( $args['author_name'] ) {
				$filters[] = array( 'terms' => array( 'author_login' => $args['author_name'] ) );
			}

			if ( !empty( $args['date_range'] ) && isset( $args['date_range']['field'] ) ) {
				$field = $args['date_range']['field'];
				unset( $args['date_range']['field'] );
				$filters[] = array( 'range' => array( $field => $args['date_range'] ) );
			}

			if ( is_array( $args['terms'] ) ) {
				foreach ( $args['terms'] as $tax => $terms ) {
					$terms = (array) $terms;
					if ( count( $terms ) && mb_strlen( $tax ) ) {
						switch ( $tax ) {
							case 'post_tag':
								$tax_fld = 'tag.slug';
								break;
							case 'category':
								$tax_fld = 'category.slug';
								break;
							default:
								$tax_fld = 'taxonomy.' . $tax . '.slug';
								break;
						}
						foreach ( $terms as $term ) {
							$filters[] = array( 'term' => array( $tax_fld => $term ) );
						}
					}
				}
			}

			if ( ! empty( $filters ) ) {
				$es_query_args['filter'] = array( 'and' => $filters );
			} else {
				$es_query_args['filter'] = array( 'match_all' => new stdClass() );
			}

			// Fill in the query
			//  todo: add auto phrase searching
			//  todo: add fuzzy searching to correct for spelling mistakes
			//  todo: boost title, tag, and category matches
			if ( $args['query'] ) {
				$es_query_args['query'] = array( 'multi_match' => array(
					'query'  => $args['query'],
					'fields' => $args['query_fields'],
					'operator'  => 'and',
					'type'  => 'cross_fields',
				) );

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
						$es_query_args['sort'][] = array( '_score' => array( 'order' => $args['order'] ) );
						break;
					case 'date' :
						$es_query_args['sort'][] = array( 'date' => array( 'order' => $args['order'] ) );
						break;
					case 'ID' :
						$es_query_args['sort'][] = array( 'id' => array( 'order' => $args['order'] ) );
						break;
					case 'author' :
						$es_query_args['sort'][] = array( 'author.raw' => array( 'order' => $args['order'] ) );
						break;
				}
			}
			if ( empty( $es_query_args['sort'] ) )
				unset( $es_query_args['sort'] );

			// Facets
			if ( ! empty( $args['facets'] ) ) {
				foreach ( (array) $args['facets'] as $label => $facet ) {
					switch ( $facet['type'] ) {

						case 'taxonomy':
							switch ( $facet['taxonomy'] ) {

								case 'post_tag':
									$field = 'tag';
									break;

								case 'category':
									$field = 'category';
									break;

								default:
									$field = 'taxonomy.' . $facet['taxonomy'];
									break;
							} // switch $facet['taxonomy']

							$es_query_args['facets'][$label] = array(
								'terms' => array(
									'field' => $field . '.slug',
									'size' => $facet['count'],
								),
							);

							break;

						case 'post_type':
							$es_query_args['facets'][$label] = array(
								'terms' => array(
									'field' => 'post_type',
									'size' => $facet['count'],
								),
							);

							break;

						case 'date_histogram':
							$es_query_args['facets'][$label] = array(
								'date_histogram' => array(
									'interval' => $facet['interval'],
									'field'    => ( ! empty( $facet['field'] ) && 'post_date_gmt' == $facet['field'] ) ? 'date_gmt' : 'date',
									'size'     => $facet['count'],
								),
							);

							break;
					}
				}
			}

			return $es_query_args;
		}
	endif; // function_exists( 'wpcom_search_api_wp_to_es_args' )

	if ( ! function_exists( 'wp_startswith' ) ) :
		function wp_startswith( $haystack, $needle ) {
			return 0 === strpos( $haystack, $needle );
		}
	endif;

	if ( ! function_exists( 'wp_endswith' ) ) :
		function wp_endswith( $haystack, $needle ) {
			return $needle === substr( $haystack, -strlen( $needle ));
		}
	endif;

	if ( ! function_exists( 'wp_in' ) ) :
		function wp_in( $needle, $haystack ) {
			return false !== strpos( $haystack, $needle );
		}
	endif;

	if ( ! function_exists( 'wpcom_vip_debug' ) ) {
		function wpcom_vip_debug( $type, $data ) {
			// Just a stub
		}
	}

	// Mimic batcache's behavior when it's not available (for catching code that batcache will reject)
	// 
	// Has no affect on caching
	if ( ! function_exists( 'vary_cache_on_function' ) ) {
		function vary_cache_on_function( $function ) {
			if ( preg_match('/include|require|echo|print|dump|export|open|sock|unlink|`|eval/i', $function) )
				die('Illegal word in variant determiner.');

			if ( !preg_match('/\$_/', $function) )
				die('Variant determiner should refer to at least one $_ variable.');
		}
	}


endif; // function_exists( 'wpcom_is_vip' )

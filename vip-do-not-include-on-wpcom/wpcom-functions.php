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

			$curl = curl_init( $service_url );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json') ); 
			curl_setopt( $curl, CURLOPT_POST, true );
			$curl_args = json_encode( $args );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $curl_args );
			$curl_response = curl_exec( $curl );
			$code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );
			curl_close( $curl );

			return json_decode( $curl_response, true );
		}
	endif; // function_exists( 'es_api_search_index' )

endif; // function_exists( 'wpcom_is_vip' )
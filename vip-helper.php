<?php
/*
 * VIP Helper Functions
 * 
 * These functions can all be used in your local WordPress environment. Add 
require_once( WP_CONTENT_DIR . '/themes/vip/plugins/vip-helper.php' );
 * in the theme's functions.php to use this
 */

// Add our caching helper functions
require_once( dirname( __FILE__ ) . '/vip-do-not-include-on-wpcom/wpcom-caching.php' );

/**
 * Simple 301 redirects
 * array elements should be in the form of:
 * '/old' => 'http://wordpress.com/new/'
 *
 */
function vip_redirects( $vip_redirects_array = array(), $case_insensitive = false ) {
	if ( empty( $vip_redirects_array ) )
		return;

	$redirect_url = '';

	// Sanitize the redirects array
	$vip_redirects_array = array_map( 'untrailingslashit', $vip_redirects_array );

	$uri_unslashed = untrailingslashit( $_SERVER['REQUEST_URI'] );

	if ( $case_insensitive ) {
		$vip_redirects_array = array_change_key_case( $vip_redirects_array );
		$uri_unslashed = strtolower( $uri_unslashed );
	}

	// Get the current URL minus query string
	$parsed_uri_path = parse_url( $uri_unslashed, PHP_URL_PATH );
	$parsed_uri_path = $parsed_uri_path ? $parsed_uri_path : '';
	$parsed_uri_path_slashed = trailingslashit( $parsed_uri_path );

	if ( $parsed_uri_path && array_key_exists( $parsed_uri_path, $vip_redirects_array ) )
		$redirect_url = $vip_redirects_array[ $parsed_uri_path ];
	elseif ( $parsed_uri_path_slashed && array_key_exists( $parsed_uri_path_slashed, $vip_redirects_array ) )
		$redirect_url = $vip_redirects_array[ $parsed_uri_path_slashed ];

	if ( $redirect_url ) {
		wp_redirect( $redirect_url, 301 );
		exit;
	}
}

/**
 * Wildcard redirects based on the beginning of the request path.
 *
 * This is basically an alternative to vip_regex_redirects() for when you
 * only need to redirect /foo/bar/* to somewhere else. Using regex
 * to do this simple check would add lots of overhead.
 *
 * array elements should be in the form of:
 * '/some-path/' => 'http://wordpress.com/new/'
 *
 * With $append_old_uri set to 'true', the full path past the match will be added to the new URL
 */
function vip_substr_redirects( $vip_redirects_array = array(), $append_old_uri = false ) {
	if ( empty( $vip_redirects_array ) )
		return;

	// Don't do anything for the homepage
	if ( '/' == $_SERVER['REQUEST_URI'] )
		return;

	foreach ( $vip_redirects_array as $old_path => $new_url ) {
		if ( substr( $_SERVER['REQUEST_URI'], 0, strlen( $old_path ) ) == $old_path ) {
			if ( $append_old_uri )
				$new_url .= str_replace( $old_path, '', $_SERVER['REQUEST_URI'] );
			wp_redirect( $new_url, 301 );
			exit();
		}
	}
}

/**
 * Advanced 301 redirects using regex to match and redirect URLs.
 *
 * Warning: Since regex is expensive and this will be run on every uncached pageload, you'll want to keep this small, lean, and mean.
 *
 * Some examples:
 *
 * Redirecting from /2011/12/dont-miss-it-make-live-holiday-giveaway.html (extra .html at the end)
 * '|/([0-9]{4})/([0-9]{2})/([0-9]{2})/([^/]+)\.html|' => '|/$1/$2/$3/$4/|'
 *
 * Redirecting from /archive/2011/12/dont-miss-it-make-live-holiday-giveaway
 * '|/archive/([0-9]{4})/([0-9]{2})/([^/]+)/?|' => '|/$3/|' // since we don't have the day, we should just send to /%postname% then WordPress can redirect from there
 *
 * Redirecting from /tax-tips/how-to-get-a-tax-break-for-summer-child-care/04152011-6163 (/%category%/%postname%/%month%%day%%year%-%post_id%)
 * '|/([^/]+)\/([^/]+)/([0-9]{1,2})([0-9]{1,2})([0-9]{4})-([0-9]{1,})/?|' => '|/$5/$3/$4/$2/|'
 *
 * @param array Elements should be in the form of: '/old/permalink/regex' => '/new/permalink/regex'
 * @param bool Whether the querystring should be included in the check
 *
 */
function vip_regex_redirects( $vip_redirects_array = array(), $with_querystring = false ) {

	if ( empty( $vip_redirects_array ) )
		return;

	$uri = $_SERVER['REQUEST_URI'];

	if ( ! $with_querystring )
		$uri = parse_url( $uri, PHP_URL_PATH );

	if( $uri && '/' != $uri ) { // don't process for homepage
		
		foreach ( $vip_redirects_array as $old_url => $new_url ) {
			if ( preg_match( $old_url, $uri, $matches ) ) {
				$redirect_uri = preg_replace( $old_url, $new_url, $uri );
				wp_redirect( $redirect_uri, 301 );
				exit;
			}
		}
	}
}

/*
 * Fetch a remote URL and cache the result for a certain period of time
 * See http://lobby.vip.wordpress.com/best-practices/fetching-remote-data/ for more details
 *
 * This function originally used file_get_contents(), hence the function name.
 * While it no longer does, it still operates the same as the basic PHP function.
 *
 * We strongly recommend not using a $timeout value of more than 3 seconds as this
 * function makes blocking requests (stops page generation and waits for the response).
 */
function wpcom_vip_file_get_contents( $url, $timeout = 3, $cache_time = 900, $extra_args = array() ) {
	global $blog_id;

	$extra_args_defaults = array(
		'obey_cache_control_header' => true, // Uses the "cache-control" "max-age" value if greater than $cache_time
		'http_api_args' => array(), // See http://codex.wordpress.org/Function_API/wp_remote_get
	);

	$extra_args = wp_parse_args( $extra_args, $extra_args_defaults );


	// $url = esc_url_raw( $url );

	$cache_key = md5( $url );
	$backup_key = 'backup:' . $cache_key;
	$cache_group = 'wpcom_vip_file_get_contents';
	$disable_get_key = 'disable:' . $cache_key;

	// Let's see if we have an existing cache already
	// Empty strings are okay, false means no cache
	if ( false !== $cache = wp_cache_get( $cache_key, $cache_group) )
		return $cache;

	// The timeout can be 1 to 10 seconds, we strongly recommend no more than 3 seconds
	$timeout = min( 10, max( 1, (int) $timeout ) );

	if ( $timeout > 3 )
		_doing_it_wrong( __FUNCTION__, 'Using a timeout value of over 3 seconds is strongly discouraged because users have to wait for the remote request to finish before the rest of their page loads.', null );

	$server_up = true;
	$response = false;
	$content = false;

	// Check to see if previous attempts have failed
	if ( false !== wp_cache_get( $disable_get_key, $cache_group ) ) {
		$server_up = false;
	}
	// Otherwise make the remote request
	else {
		$http_api_args = (array) $extra_args['http_api_args'];
		$http_api_args['timeout'] = $timeout;
		$response = wp_remote_get( $url, $http_api_args );
	}
	
	// Was the request successful?
	if ( $server_up && ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
		$content = wp_remote_retrieve_body( $response );

		$cache_header = wp_remote_retrieve_header( $response, 'cache-control' );
		if ( is_array( $cache_header ) )
			$cache_header = array_shift( $cache_header );

		// Obey the cache time header unless an arg is passed saying not to
		if ( $extra_args['obey_cache_control_header'] && $cache_header ) {
			$cache_header = trim( $cache_header );
			// When multiple cache-control directives are returned, they are comma separated
			foreach ( explode( ',', $cache_header ) as $cache_control ) {
				// In this scenario, only look for the max-age directive 
				if( 'max-age' == substr( trim( $cache_control ), 0, 7 ) )
					list( $cache_header_type, $cache_header_time ) = explode( '=', trim( $cache_control ) );
			}
			// If the max-age directive was found and had a value set that is greater than our cache time
			if ( isset( $cache_header_type ) && isset( $cache_header_time ) && $cache_header_time > $cache_time )
				$cache_time = (int) $cache_header_time; // Casting to an int will strip "must-revalidate", etc.
		}

		// The cache time shouldn't be less than a minute
		// Please try and keep this as high as possible though
		// It'll make your site faster if you do
		$cache_time = (int) $cache_time;
		if ( $cache_time < 60 )
			$cache_time = 60;

		// Cache the result
		wp_cache_set( $cache_key, $content, $cache_group, $cache_time );

		// Additionally cache the result with no expiry as a backup content source
		wp_cache_set( $backup_key, $content, $cache_group );

		// So we can hook in other places and do stuff
		do_action( 'wpcom_vip_remote_request_success', $url, $response );
	}
	// Okay, it wasn't successful. Perhaps we have a backup result from earlier.
	elseif ( $content = wp_cache_get( $backup_key, $cache_group ) ) {
		// If a remote request failed, log why it did
		if ( $response && ! is_wp_error( $response ) ) {
			error_log( "wpcom_vip_file_get_contents: Blog ID {$blog_id}: Failure for $url and the result was: " . maybe_serialize( $response['headers'] ) . ' ' . maybe_serialize( $response['response'] ) );
		} elseif ( $response ) { // is WP_Error object
			error_log( "wpcom_vip_file_get_contents: Blog ID {$blog_id}: Failure for $url and the result was: " . maybe_serialize( $response ) );
		}
	}
	// We were unable to fetch any content, so don't try again for another 60 seconds
	elseif ( $response ) {
		wp_cache_set( $disable_get_key, 1, $cache_group, 60 );

		// If a remote request failed, log why it did
		if ( $response && ! is_wp_error( $response ) ) {
			error_log( "wpcom_vip_file_get_contents: Blog ID {$blog_id}: Failure for $url and the result was: " . maybe_serialize( $response['headers'] ) . ' ' . maybe_serialize( $response['response'] ) );
		} elseif ( $response ) { // is WP_Error object
			error_log( "wpcom_vip_file_get_contents: Blog ID {$blog_id}: Failure for $url and the result was: " . maybe_serialize( $response ) );
		}
		// So we can hook in other places and do stuff
		do_action( 'wpcom_vip_remote_request_error', $url, $response );
	}

	return $content;
}

/*
 * This is the old deprecated version of wpcom_vip_file_get_contents()
 * Please don't use this function in any new code
 */
function vip_wp_file_get_content( $url, $echo_content = true, $timeout = 3 ) {
	$output = wpcom_vip_file_get_contents( $url, $timeout );

	if ( $echo_content )
		echo $output;
	else
		return $output;
}

/*
 * Disable tag suggest on post screen
 * @author mdawaffe
 */
function vip_disable_tag_suggest() {
	add_action( 'admin_init', '_vip_disable_tag_suggest' );
}
function _vip_disable_tag_suggest() {
	if ( !@constant( 'DOING_AJAX' ) || empty( $_GET['action'] ) || 'ajax-tag-search' != $_GET['action'] )
		return;
	exit();
}

/*
 * Disable autosave
 * @author mdawaffe
 */
function disable_autosave() {
	add_action( 'init', '_disable_autosave' );
}
function _disable_autosave() {
	wp_deregister_script( 'autosave' );
}

/*
 * Redirect http://blog.wordpress.com/feed/ to $target URL
 * Don't redirect if a feed service user agent, because that could result 
 * in a loop.
 * Can be executed before wp init b/c checks the URI directly to see if main feed
 * ex. vip_main_feed_redirect( 'http://feeds.feedburner.com/ourfeeds/thefeed' );
 * @author lloydbudd
 */
function vip_main_feed_redirect( $target ) {
	if ( wpcom_vip_is_main_feed_requested() && !wpcom_vip_is_feedservice_ua() ) {
		wp_redirect( $target, '302' );
		die;
	}
}

/*
 * True if any of the formats of the main feed are requested
 * @author lloydbudd
 */ 
function wpcom_vip_is_main_feed_requested() {
	$toMatch = '#^/(wp-(rdf|rss|rss2|atom|rssfeed).php|index.xml|feed|rss)/?$#i';
	$request = $_SERVER['REQUEST_URI'];
	return (bool) preg_match( $toMatch, $request );
}

/*
 * True if feed service user agent
 * batcache aware so that does not serve matched user agents from cache
 * @author lloydbudd
 */ 
function wpcom_vip_is_feedservice_ua() {
	if ( function_exists( 'wpcom_feed_cache_headers' ) ) {
		// Workaround so that no feed request served from nginx wpcom-feed-cache
		// If you are checking you must already know is a feed 
		// and don't want any requests cached
		// ASSUMPTION: you've already confirmed is_feed() b/f calling
		// wpcom_vip_is_feedservice_ua
			header( "X-Accel-Expires: 0" ); 
	}
	if ( function_exists( 'vary_cache_on_function' ) ) { // batcache variant
		vary_cache_on_function(
			'return (bool) preg_match("/feedburner|feedvalidator|MediafedMetrics/i", $_SERVER["HTTP_USER_AGENT"]);'
		);
	}
	return (bool) preg_match("/feedburner|feedvalidator|MediafedMetrics/i", $_SERVER["HTTP_USER_AGENT"]);
}

/*
 * For flash hosted elsewhere to work it looks for crossdomain.xml in 
 * the host's * web root. If requested, this function echos 
 * the crossdomain.xml file in the theme's root directory
 * @author lloydbudd
 */
function vip_crossdomain_redirect() {
	add_action( 'init', '_vip_crossdomain_redirect');
}
function _vip_crossdomain_redirect() {
	$request = $_SERVER['REQUEST_URI'];
	if ( '/crossdomain.xml' == $request ) {
		header( 'Content-Type: text/xml' );
		echo file_get_contents( get_stylesheet_directory() . $request );
		exit();
	}
}

function vip_doubleclick_dartiframe_redirect() {
	add_action( 'init', '_vip_doubleclick_dartiframe_redirect');
}
function _vip_doubleclick_dartiframe_redirect() {
	$dart_file = get_stylesheet_directory() . '/DARTIframe.html';
	if ( stripos( $_SERVER[ 'REQUEST_URI' ], 'DARTIframe.html' ) !== false && file_exists( $dart_file ) ) {
		header( 'Content-Type: text/html' );
		echo file_get_contents( $dart_file );
		exit;
	}
}

/*
 * Send moderation emails to multiple addresses
 * @author nickmomrik
 */
function vip_multiple_moderators($emails) {
	$emails = (array) $emails;

	$email_headers = "From: donotreply@wordpress.com" . "\n" . "CC: " . implode(', ', $emails);
	add_filter('comment_moderation_headers', create_function( '', 'return '.var_export( $email_headers, true ).';') );

	add_filter( 'wpcom_vip_multiple_moderators', create_function( '$existing', 'return array_merge( $existing, ' . var_export( $emails, true ) . ' );') );
}

/**
 * Automatically insert meta description tag into posts/pages. 
 * - can be configured to use either first X chars/words of the post content or post excerpt if available
 * - can use category description for category archive pages if available 
 * - can use tag description for tag archive pages if available 
 * - can use blog description for everything else
 * - can use a default description if no suitable value is found
 * - can use the value of a custom field as description 
 *
 * @usage
 * // add a custom configuration via filter
 * function set_wpcom_vip_meta_desc_settings( $settings ) {
 * 		return array( 'length' => 10, 'length_unit' => 'char|word', 'use_excerpt' => true, 'add_category_desc' => true, 'add_tag_desc' => true, 'add_other_desc' => true, 'default_description' => '', 'custom_field_key' => '' );
 * }
 * add_filter( 'wpcom_vip_meta_desc_settings', 'set_wpcom_vip_meta_desc_settings' ); 
 * add_action( 'wp_head', 'wpcom_vip_meta_desc' );
 *
 * @author Thorsten Ott
 */
function wpcom_vip_meta_desc() {
	$text = wpcom_vip_get_meta_desc();
	if ( !empty( $text ) ) {
		echo "\n<meta name=\"description\" content=\"$text\" />\n";
	}
}

function wpcom_vip_get_meta_desc() {
	$default_settings = array(	
		'length' => 25,              // amount of length units to use for the meta description
		'length_unit' => 'word',     // the length unit can be either "word" or "char"
		'use_excerpt' => true,       // if the post/page has an excerpt it will overwrite the generated description if this is set to true
		'add_category_desc' => true, // add the category description to category views if this value is true
		'add_tag_desc' => true,      // add the category description to category views if this value is true
		'add_other_desc' => true,    // add the blog description/tagline to all other pages if this value is true
		'default_description' => '', // in case no description is defined use this as a default description
		'custom_field_key' => '',    // if a custom field key is set we try to use the value of this field as description
	);

	$settings = apply_filters( 'wpcom_vip_meta_desc_settings', $default_settings );

	extract( shortcode_atts( $default_settings, $settings ) );

	global $wp_query;

	if( is_single() || is_page() ) {
		$post = $wp_query->post;

		// check for a custom field holding a description
		if ( !empty( $custom_field_key ) ) {
			$post_custom = get_post_custom_values( $custom_field_key, $post->ID );
			if ( !empty( $post_custom ) ) 
				$text = $post_custom[0];
		}
		// check for an excerpt we can use
		elseif ( $use_excerpt && !empty( $post->post_excerpt ) ) {
			$text = $post->post_excerpt;
		}
		// otherwise use the content
		else {
			$text = $post->post_content;
		}

		$text = str_replace( array( "\r\n", "\r", "\n", "  " ), " ", $text ); // get rid of all line breaks
		$text = strip_shortcodes( $text ); // make sure to get rid of shortcodes
		$text = apply_filters( 'the_content', $text ); // make sure it's save
		$text = trim( strip_tags( $text ) ); // get rid of tags and html fragments
		if ( empty( $text ) && !empty( $default_description ) )
			$text = $default_description;	

	} else if( is_category() && true == $add_category_desc ) {
		$category = $wp_query->get_queried_object();
		$text = trim( strip_tags( $category->category_description ) );
		if ( empty( $text ) && !empty( $default_description ) )
			$text = $default_description;

	} else if( is_tag() && true == $add_tag_desc ) {
		$tag = $wp_query->get_queried_object();
		$text = trim( strip_tags( $tag->description ) );
		if ( empty( $text ) && !empty( $default_description ) )
			$text = $default_description;

	} else if ( true == $add_other_desc ) {
		$text = trim( strip_tags( get_bloginfo('description') ) );
		if ( empty( $text ) && !empty( $default_description ) )
			$text = $default_description;
	}

	if ( empty( $text ) )
		return;

	if ( 'word' == $length_unit ) {
		$words = explode(' ', $text, $length + 1);
		if ( count( $words ) > $length ) {
			array_pop( $words );
			array_push( $words, '...' );
			$text = implode( ' ', $words );
		}
	} else {
		if ( strlen( $text ) > $length ) {
			$text = mb_strimwidth( $text, 0, $length, '...' );
		}
	}
	
	return $text;
}


/**
 * Get random posts optimized for speed on large tables.
 * as MySQL queries that use ORDER BY RAND() can be pretty challenging and slow on large dataset
 * this function gives and alternative method for getting random posts.
 * @usage
 * $random_posts = vip_get_random_posts( $amount=50 ); // gives 50 random published posts
 * // sometimes you can also add your own where condition
 * global $vip_get_random_post_ids_where_add;
 * $vip_get_random_post_ids_where_add = "AND post_status='publish' AND post_type='post' AND post_date_gmt > '2009-01-01 00:00:00';
 * $random_posts = vip_get_random_posts( $amount=50 ); // would only consider posts after Jan 1st 2009
 * // by default you can also get a list of post_ids instead of their objects
 * $random_posts = vip_get_random_posts( $amount=50, true ); // will return 50 random post ids for published posts.
 * // To enable caching and avoid querying all posts add the following action
 * global $vip_get_random_post_ids_where_add; // make sure we use the same where condition
 * add_action( 'save_post', 'vip_refresh_random_posts_all_ids', 1 ); // add this to functions.php
 * @author tottdev
 */
function vip_get_random_posts( $amount = 1, $return_ids = false ) {
	global $wpdb, $vip_get_random_posts_rnd_ids, $vip_get_random_posts_current_rnd_ids, $vip_get_random_post_ids_where_add;

	if ( empty( $vip_get_random_post_ids_where_add ) )
		$where_add = "AND post_status='publish' AND post_type='post'";
	else 
		$where_add = $vip_get_random_post_ids_where_add;

	$random_posts = array();
	
	if ( !has_action( 'save_post', 'vip_refresh_random_posts_all_ids' ) || !$all_ids = wp_cache_get( 'vip_random_posts_ids_' . md5( $where_add ), 'vip_get_random_posts_all_ids' ) ) {
		$all_ids = vip_refresh_random_posts_all_ids();
	}

	if ( empty( $all_ids ) || is_wp_error( $all_ids ) ) 
		return false;
	
	$seed = hexdec( substr( md5( microtime() ), -8 ) ) & 0x7fffffff;
	mt_srand( $seed );

	$min_id = 0;
	$max_id = count( $all_ids );
	$vip_get_random_posts_rnd_ids = array();
	$cycles = 0;
	$max_cycles = 5 * count( $all_ids );
	do {
		$cycles++;
		$random_id = mt_rand( $min_id, $max_id );
		if ( isset( $vip_get_random_posts_rnd_ids[$random_id] ) )
			continue;
			
		$vip_get_random_posts_rnd_ids[$random_id] = $all_ids[$random_id]->ID;
	} while( count( $vip_get_random_posts_rnd_ids ) < $amount && $cycles <= $max_cycles );

	if ( $return_ids )
		return (array) $vip_get_random_posts_rnd_ids;

	$random_posts = get_posts( array( 'post__in' => $vip_get_random_posts_rnd_ids, 'numberposts' => count( $vip_get_random_posts_rnd_ids ) ) );
	$random_posts = apply_filters( 'vip_get_random_posts_random_posts', $random_posts );
	return $random_posts;
}

/**
 * Helper function for vip_get_random_posts()
 */
function vip_refresh_random_posts_all_ids() {
	global $wpdb, $vip_get_random_post_ids_where_add;
	if ( empty( $vip_get_random_post_ids_where_add ) )
		$where_add = "AND post_status='publish' AND post_type='post'";
	else 
		$where_add = $vip_get_random_post_ids_where_add;

	$all_ids_query = "SELECT ID FROM $wpdb->posts WHERE 1 $where_add";
	$all_ids_query = apply_filters( 'vip_get_random_posts_all_ids_query', $all_ids_query );
	$all_ids = $wpdb->get_results( $all_ids_query );
	if ( empty( $all_ids ) || is_wp_error( $all_ids ) ) 
		return false;

	if ( has_action( 'save_post', 'vip_refresh_random_posts_all_ids' ) )
		wp_cache_set( 'vip_random_posts_ids_' . md5( $where_add ), $all_ids, 'vip_get_random_posts_all_ids' );

	return $all_ids;
}

/** 
 * An extended version of wp_remote_get() 
 * This function prevents interruption of service due to slow 3rd party providers.
 * Once the timeout is hit an error counter is increased up to a threshold value. Once this value
 * is reached a fallback value or error object is returned until a retry time is reached.
 * Then the remote call is executed again and based on the result the counter is decreased or the latest timeout value
 * is increased which causes an other retry to happen after an other retry time period passed.
 * If the call is successful the error counter is decreased and the result returned. If the error counter hits 0 the option is removed
 * @usage
 * // get a url with 1 second timeout, cancel remote calls for 20 seconds after 3 failed attempts in 20 seconds occured
 * $response = vip_safe_wp_remote_get( $url );
 * if ( is_wp_error( $response ) )
 * 		echo 'no value available';
 * else 
 * 		echo wp_remote_retrieve_body( $response );
 * 
 * // get a url with 1 second timeout, cancel remote calls for 60 seconds after 1 failed attempts in 60 seconds occured
 * // display 'n/a' on failure
 * $response = vip_safe_wp_remote_get( $url, 'n/a', 1, 1, 60 );
 * echo $response;
 * 
 * @see http://codex.wordpress.org/HTTP_API
 * @author tottdev
 */
function vip_safe_wp_remote_get( $url, $fallback_value='', $threshold=3, $timeout=1, $retry=20, $args = array() ) {
	global $blog_id;

	$cache_group = "$blog_id:vip_safe_wp_remote_get";
	$cache_key = 'disable_remote_get_' . md5( parse_url( $url, PHP_URL_HOST ) );

	// valid url
	if ( empty( $url ) || !parse_url( $url ) )
		return ( $fallback_value ) ? $fallback_value : new WP_Error('invalid_url', $url );

	// Ensure positive values
	$timeout   = abs( $timeout );
	$retry     = abs( $retry );
	$threshold = abs( $threshold );

	// timeouts > 3 seconds are just not reasonable for production usage
	$timeout = ( (int) $timeout > 3 ) ? 3 : (int) $timeout;
	// retry time < 10 seconds will default to 10 seconds.
	$retry =  ( (int) $retry < 10 ) ? 10 : (int) $retry;
	// more than 10 faulty hits seem to be to much
	$threshold = ( (int) $threshold > 10 ) ? 10 : (int) $threshold;

	$option = wp_cache_get( $cache_key, $cache_group );

	// check if the timeout was hit and obey the option and return the fallback value 
	if ( false !== $option && time() - $option['time'] < $retry ) { 
		if ( $option['hits'] >= $threshold )
			return ( $fallback_value ) ? $fallback_value : new WP_Error('remote_get_disabled', $option );
	}

	$start = microtime( true );
	$response = wp_remote_get( $url, array_merge( $args, array( 'timeout' => $timeout ) ) );
	$end = microtime( true );

	$elapsed = ( $end - $start ) > $timeout; 
	if ( true === $elapsed ) {
		if ( false !== $option && $option['hits'] < $threshold ) 
			wp_cache_set( $cache_key, array( 'time' => floor( $end ), 'hits' => $option['hits']+1 ), $cache_group, $retry );
		else if ( false !== $option && $option['hits'] == $threshold ) 
			wp_cache_set( $cache_key, array( 'time' => floor( $end ), 'hits' => $threshold ), $cache_group, $retry );
		else
			wp_cache_set( $cache_key, array( 'time' => floor( $end ), 'hits' => 1 ), $cache_group, $retry );
	}
	else {
		if ( false !== $option && $option['hits'] > 0 && time() - $option['time'] < $retry ) 
			wp_cache_set( $cache_key, array( 'time' => $option['time'], 'hits' => $option['hits']-1 ), $cache_group, $retry );
		else 
			wp_cache_delete( $cache_key, $cache_group);
	}
	
	if ( is_wp_error( $response ) ) {
		// Log errors for internal WP.com debugging
		error_log( "vip_safe_wp_remote_get: Blog ID {$blog_id}: Fetching $url with a timeout of $timeout failed. Result: " . maybe_serialize( $response ) );
		do_action( 'wpcom_vip_remote_request_error', $url, $response );

		return ( $fallback_value ) ? $fallback_value : $response;
	}

	return $response;
}

/** 
 * Disable comment counts in "Right Now" Dashboard widget as it can take a while to query this.
 */
function disable_right_now_comment_count() {
	if ( !is_admin() )
		return;
	
	add_filter( 'wp_count_comments', '_disable_right_now_comment_count_filter', 0 );
	add_action( 'wp_print_scripts', '_disable_right_now_comment_count_css' );
}

/**
 * this function is internally called by wp_count_comments
 */
function _disable_right_now_comment_count_css() {
	?>
<style type="text/css">
#dashboard_right_now div.table_discussion { display: none; }
#dashboard_right_now div.table_content { width: 100%; }
</style>
	<?php
}
function _disable_right_now_comment_count_filter( $data ) {
	$backtrace = debug_backtrace();
	foreach( $backtrace as $args ) {
		if ( 'wp_dashboard_right_now' == $args['function'] ) {
			return 'n/a';
		}
	}
	return false;
}

/**
 * Returns profile information for a WordPress.com/Gravatar user
 *
 * @param string|int Email, ID, or username for user to lookup
 * @return array Profile info formatted as noted here: http://en.gravatar.com/site/implement/profiles/php/
 */
function wpcom_vip_get_user_profile( $email_or_id ) {

	if ( is_numeric( $email_or_id ) ) {
		$user = get_user_by( 'id', $email_or_id );
		if ( ! $user )
			return false;

		$email = $user->user_email;
	} elseif ( is_email( $email_or_id ) ) {
		$email = $email_or_id;
	} else {
		$user_login = sanitize_user( $email_or_id, true );
		$user = get_user_by( 'login', $user_login );
		if ( ! $user )
			return;

		$email = $user->user_email;
	}

	$hashed_email = md5( strtolower( trim( $email ) ) );	
	$profile_url = esc_url_raw( sprintf( '%s.gravatar.com/%s.php', ( is_ssl() ? 'https://secure' : 'http://www' ), $hashed_email ), array( 'http', 'https' ) );

	$profile = wpcom_vip_file_get_contents( $profile_url, 1, 900 );
	if ( $profile ) {
		$profile = unserialize( $profile );

		if ( is_array( $profile ) && ! empty( $profile['entry'] ) && is_array( $profile['entry'] ) ) {
			$profile = $profile['entry'][0];
		} else {
			$profile = false;
		}
	}
	return $profile;
}


/**
 * Checks to see if a given e-mail address has a Gravatar or not.
 *
 * You can use this function to only call get_avatar() when the user
 * has a Gravatar and display nothing (rather than a placeholder image)
 * when they don't.
 */
function wpcom_vip_email_has_gravatar( $email ) {

	$hash = md5( strtolower( trim( $email ) ) );

	// If not in the cache, check again
	if ( false === $has_gravatar = wp_cache_get( $hash, 'email_has_gravatar' ) ) {

		$request = wp_remote_head( 'http://0.gravatar.com/avatar/' . $hash . '?d=404' );

		$has_gravatar = ( 404 == wp_remote_retrieve_response_code( $request ) ) ? 0 : 1;

		wp_cache_set( $hash, $has_gravatar, 'email_has_gravatar', 86400 ); // Check daily
	}

	return (bool) $has_gravatar;
}

/**
 * Check that a URL matches a given whitelist
 *
 * Example whitelist: array( 'mydomain.com', 'mydomain.net' ) 
 */
function wpcom_vip_is_valid_domain( $url, $whitelisted_domains ) {
	$domain = parse_url( $url, PHP_URL_HOST );

	if ( ! $domain )
		return false;

	// Check if we match the domain exactly
	if ( in_array( $domain, $whitelisted_domains ) )
		return true;

	$valid = false;

	foreach( $whitelisted_domains as $whitelisted_domain ) {
		$whitelisted_domain = '.' . $whitelisted_domain; // Prevent things like 'evilsitetime.com'
		if( strpos( $domain, $whitelisted_domain ) === ( strlen( $domain ) - strlen( $whitelisted_domain ) ) ) {
			$valid = true;
			break;
		}
	}
	return $valid;
}

/**
 * Helper function to enable bulk user management on a per-user basis
 *
 * Example: wpcom_vip_bulk_user_management_whitelist( array( 'userlogin1', 'userlogin2' ) );
 */
function wpcom_vip_bulk_user_management_whitelist( $users ) {
	add_filter( 'bulk_user_management_admin_users', function() use ( $users ) { return $users; } );
}

/**
 * Helper function that provides caching for the normally uncached wp_oembed_get() function.
 *
 * Note that if you're using this within the contents of a post, it's probably better to use
 * the existing WordPress functionality: http://codex.wordpress.org/Embeds
 *
 * This helper function is more meant for other places, such as sidebars.
 */
function wpcom_vip_wp_oembed_get( $url, $args = array() ) {
	$cache_key = md5( $url . '|' . serialize( $args ) );

	if ( false === $html = wp_cache_get( $cache_key, 'wpcom_vip_wp_oembed_get' ) ) {
		$html = wp_oembed_get( $url, $args );

		wp_cache_set( $cache_key, $html, 'wpcom_vip_wp_oembed_get' );
	}

	return $html;
}

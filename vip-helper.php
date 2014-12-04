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
 * 
 * @param array $vip_redirects_array Optional. Elements should be in the form of '/old' => 'http://wordpress.com/new/'
 * @param bool $case_insensitive Optional. Should the redirects be case sensitive? Defaults to false.
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
 * This is basically an alternative to vip_regex_redirects() for when you only need to redirect /foo/bar/* to somewhere else.
 * Using regex to do this simple check would add lots of overhead.
 *
 * @param array $vip_redirects_array Optional. Elements should be in the form of '/some-path/' => 'http://wordpress.com/new/'
 * @param bool $append_old_uri Optional. If true, the full path past the match will be added to the new URL. Defaults to false.
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
 * @param array $vip_redirects_array Optional. Array of key/value pairs to redirect from/to.
 * @param bool $with_querystring Optional. Set this to true if your redirect string is in the format of an absolute URL. Defaults to false (just the path).
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

/**
 * Fetch a remote URL and cache the result for a certain period of time.
 *
 * This function originally used file_get_contents(), hence the function name.
 * While it no longer does, it still operates the same as the basic PHP function.
 *
 * We strongly recommend not using a $timeout value of more than 3 seconds as this
 * function makes blocking requests (stops page generation and waits for the response).
 * 
 * The $extra_args are:
 *  * obey_cache_control_header: uses the "cache-control" "max-age" value if greater than $cache_time.
 *  * http_api_args: see http://codex.wordpress.org/Function_API/wp_remote_get
 *
 * @link http://lobby.vip.wordpress.com/best-practices/fetching-remote-data/ Fetching Remote Data
 * @param string $url URL to fetch
 * @param int $timeout Optional. The timeout limit in seconds; valid values are 1-10. Defaults to 3.
 * @param int $cache_time Optional. The minimum cache time in seconds. Valid values are >= 60. Defaults to 900.
 * @param array $extra_args Optional. Advanced arguments: "obey_cache_control_header" and "http_api_args".
 * @return string The remote file's contents (cached)
 */
function wpcom_vip_file_get_contents( $url, $timeout = 3, $cache_time = 900, $extra_args = array() ) {
	global $blog_id;

	$extra_args_defaults = array(
		'obey_cache_control_header' => true, // Uses the "cache-control" "max-age" value if greater than $cache_time
		'http_api_args' => array(), // See http://codex.wordpress.org/Function_API/wp_remote_get
	);

	$extra_args = wp_parse_args( $extra_args, $extra_args_defaults );

	$cache_key       = md5( serialize( array_merge( $extra_args, array( 'url' => $url ) ) ) );
	$backup_key      = $cache_key . '_backup';
	$disable_get_key = $cache_key . '_disable';
	$cache_group     = 'wpcom_vip_file_get_contents';

	// Temporary legacy keys to prevent mass cache misses during our key switch
	$old_cache_key       = md5( $url );
	$old_backup_key      = 'backup:' . $old_cache_key;
	$old_disable_get_key = 'disable:' . $old_cache_key;

	// Let's see if we have an existing cache already
	// Empty strings are okay, false means no cache
	if ( false !== $cache = wp_cache_get( $cache_key, $cache_group) )
		return $cache;

	// Legacy
	if ( false !== $cache = wp_cache_get( $old_cache_key, $cache_group) )
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
	// Legacy
	elseif ( false !== wp_cache_get( $old_disable_get_key, $cache_group ) ) {
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
					// Note the array_pad() call prevents 'undefined offset' notices when explode() returns less than 2 results
					list( $cache_header_type, $cache_header_time ) = array_pad( explode( '=', trim( $cache_control ), 2 ), 2, null );
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
	// Legacy
	elseif ( $content = wp_cache_get( $old_backup_key, $cache_group ) ) {
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

/**
 * This is the old deprecated version of wpcom_vip_file_get_contents(). Please don't use this function in any new code.
 *
 * @deprecated
 * @link http://lobby.vip.wordpress.com/best-practices/fetching-remote-data/ Fetching Remote Data
 * @param string $url URL to fetch
 * @param bool $echo_content Optional. If true (the default), echo the remote file's contents. If false, return it.
 * @param int $timeout Optional. The timeout limit in seconds; valid values are 1-10. Defaults to 3.
 * @return string|null If $echo_content is true, there will be no return value.
 * @see wpcom_vip_file_get_contents
 */
function vip_wp_file_get_content( $url, $echo_content = true, $timeout = 3 ) {
	$output = wpcom_vip_file_get_contents( $url, $timeout );

	if ( $echo_content )
		echo $output;
	else
		return $output;
}

/**
 * Disables the tag suggest on the post screen.
 *
 * @author mdawaffe
 */
function vip_disable_tag_suggest() {
	add_action( 'admin_init', '_vip_disable_tag_suggest' );
}

/**
 * Helper function for vip_disable_tag_suggest(); disables the tag suggest on the post screen.
 *
 * @see vip_disable_tag_suggest()
 */
function _vip_disable_tag_suggest() {
	if ( !@constant( 'DOING_AJAX' ) || empty( $_GET['action'] ) || 'ajax-tag-search' != $_GET['action'] )
		return;
	exit();
}

/**
 * Disable post autosave
 *
 * @author mdawaffe
 */
function disable_autosave() {
	add_action( 'init', '_disable_autosave' );
}

/**
 * Helper function for disable_autosave(); disables autosave on the post screen.
 *
 * @see disable_autosave()
 */
function _disable_autosave() {
	wp_deregister_script( 'autosave' );
}

/**
 * Redirect http://blog.wordpress.com/feed/ to $target URL
 *
 * Don't redirect if a feed service user agent, because that could result in a loop.
 *
 * This can be executed before WP init because it checks the URI directly to see if the main feed is being requested.
 *
 * @author lloydbudd
 * @link http://vip.wordpress.com/documentation/redirect-the-feed-to-feedburner/ Redirect the Feed To Feedburner
 * @param string $target URL to direct feed services to
 */
function vip_main_feed_redirect( $target ) {
	if ( wpcom_vip_is_main_feed_requested() && !wpcom_vip_is_feedservice_ua() ) {
		wp_redirect( $target, '302' );
		die;
	}
}

/**
 * Returns if any of the formats of the main feed are requested
 *
 * @author lloydbudd
 * @return bool Returns true if main feed is requested
 */
function wpcom_vip_is_main_feed_requested() {
	$toMatch = '#^/(wp-(rdf|rss|rss2|atom|rssfeed).php|index.xml|feed|rss)/?$#i';
	$request = $_SERVER['REQUEST_URI'];
	return (bool) preg_match( $toMatch, $request );
}

/**
 * Returns if the current visitor has a feed service user agent
 *
 * The function is batcache aware so that it does not serve matched user agents from cache.
 *
 * @author lloydbudd
 * @return bool Returns true if the current visitor has a feed service user agent.
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

/**
 * Responds to a blog.wordpress.com/crossdomain.xml request with the contents of a crossdomain.xml file located in the root of your theme.
 *
 * @author lloydbudd
 */
function vip_crossdomain_redirect() {
	add_action( 'init', '_vip_crossdomain_redirect');
}

/**
 * Helper function for vip_crossdomain_redirect(); serves up /vip/your_theme/crossdomain.xml
 *
 * @see vip_crossdomain_redirect()
 */
function _vip_crossdomain_redirect() {
	$request = $_SERVER['REQUEST_URI'];
	if ( '/crossdomain.xml' == $request ) {
		header( 'Content-Type: text/xml' );
		echo file_get_contents( get_stylesheet_directory() . $request );
		exit();
	}
}

/**
 * Responds to a blog.wordpress.com/DARTIframe.html request with the contents of a DARTIframe.html file located in the root of your theme.
 */
function vip_doubleclick_dartiframe_redirect() {
	add_action( 'init', '_vip_doubleclick_dartiframe_redirect');
}

/**
 * Helper function for vip_doubleclick_dartiframe_redirect(); serves up /vip/your_theme/DARTIframe.html
 */
function _vip_doubleclick_dartiframe_redirect() {
	$dart_file = get_stylesheet_directory() . '/DARTIframe.html';
	if ( stripos( $_SERVER[ 'REQUEST_URI' ], 'DARTIframe.html' ) !== false && file_exists( $dart_file ) ) {
		header( 'Content-Type: text/html' );
		echo file_get_contents( $dart_file );
		exit;
	}
}

/**
 * Send comment moderation emails to multiple addresses
 *
 * @author nickmomrik
 * @param array $emails Array of email addresses
 */
function vip_multiple_moderators($emails) {
	$emails = (array) $emails;

	$email_headers = "From: donotreply@wordpress.com" . "\n" . "CC: " . implode(', ', $emails);
	add_filter('comment_moderation_headers', function() use ( $email_headers ) { return var_export( $email_headers, true ); } );

	add_filter( 'wpcom_vip_multiple_moderators', function( $existing ) use ( $emails ) { return array_merge( $existing, var_export( $emails, true ) ); } );
}

/**
 * Automatically insert meta description tag into posts/pages.
 *
 * You shouldn't need to use this function nowadays because WordPress.com and Jetpack takes care of this for you.
 *
 * @author Thorsten Ott
 */
function wpcom_vip_meta_desc() {
	$text = wpcom_vip_get_meta_desc();
	if ( !empty( $text ) ) {
		echo "\n<meta name=\"description\" content=\"$text\" />\n";
	}
}

/**
 * Filter this function to change the meta description value set by wpcom_vip_meta_desc().
 * 
 * Can be configured to use either first X chars/words of the post content or post excerpt if available
 * Can use category description for category archive pages if available
 * Can use tag description for tag archive pages if available
 * Can use blog description for everything else
 * Can use a default description if no suitable value is found
 * Can use the value of a custom field as description
 *
 * Usage:
 * // add a custom configuration via filter
 * function set_wpcom_vip_meta_desc_settings( $settings ) {
 * 		return array( 'length' => 10, 'length_unit' => 'char|word', 'use_excerpt' => true, 'add_category_desc' => true, 'add_tag_desc' => true, 'add_other_desc' => true, 'default_description' => '', 'custom_field_key' => '' );
 * }
 * add_filter( 'wpcom_vip_meta_desc_settings', 'set_wpcom_vip_meta_desc_settings' );
 * add_action( 'wp_head', 'wpcom_vip_meta_desc' );
 *
 * @return string The meta description
 * @see wpcom_vip_meta_desc()
 */
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
 * Get random posts; a simple, more efficient approach..
 *
 * MySQL queries that use ORDER BY RAND() can be pretty challenging and slow on large datasets.
 * This function is an alternative method for getting random posts, though it's not as good but at least it won't destroy your site :).
 *
 * @param int $number Optional. Amount of random posts to get. Default 1.
 * @param string $post_type Optional. Specify the post_type to use when randomizing posts. Default 'post'.
 * @param bool $return_ids Optional. To just get the IDs, set this to true, otherwise post objects are returned (the default).
 * @return array
 */
function vip_get_random_posts( $number = 1, $post_type = 'post', $return_ids = false ) {
	$query = new WP_Query( array( 'posts_per_page' => 100, 'fields' => 'ids', 'post_type' => $post_type ) );

	$post_ids = $query->posts;
	shuffle( $post_ids );
	$post_ids = array_splice( $post_ids, 0, $number );

	if ( $return_ids )
		return $post_ids;

	$random_posts = get_posts( array( 'post__in' => $post_ids, 'numberposts' => count( $post_ids ), 'post_type' => $post_type ) );

	return $random_posts;
}

/**
 * This is a sophisticated extended version of wp_remote_get(). It is designed to more gracefully handle failure than wpcom_vip_file_get_contents() does.
 * 
 * Note that like wp_remote_get(), this function does not cache.
 *
 * @author tottdev
 * @link http://vip.wordpress.com/documentation/fetching-remote-data/ Fetching Remote Data
 * @param string $url URL to fetch
 * @param string $fallback_value Optional. Set a fallback value to be returned if the external request fails.
 * @param int $threshold Optional. The number of fails required before subsequent requests automatically return the fallback value. Defaults to 3, with a maximum of 10.
 * @param int $timeout Optional. Number of seconds before the request times out. Valid values 1-3; defaults to 1.
 * @param int $retry Optional. Number of seconds before resetting the fail counter and the number of seconds to delay making new requests after the fail threshold is reached. Defaults to 20, with a minimum of 10.
 * @param array Optional. Set other arguments to be passed to wp_remote_get().
 * @return string|WP_Error|array Array of results. If fail counter is met, returns the $fallback_value, otherwise return WP_Error.
 * @see wp_remote_get()
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
			return ( $fallback_value ) ? $fallback_value : new WP_Error('remote_get_disabled', 'Remote requests disabled: ' . maybe_serialize( $option ) );
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
 * Disable comment counts in "Right Now" Dashboard widget as it can take a while to query the data.
 */
function disable_right_now_comment_count() {
	if ( !is_admin() )
		return;

	add_filter( 'wp_count_comments', '_disable_right_now_comment_count_filter', 0 );
	add_action( 'wp_print_scripts', '_disable_right_now_comment_count_css' );
}

/**
 * Helper function for disable_right_now_comment_count()
 *
 * @see disable_right_now_comment_count()
 */
function _disable_right_now_comment_count_css() {
	?>
<style type="text/css">
#dashboard_right_now div.table_discussion { display: none; }
#dashboard_right_now div.table_content { width: 100%; }
</style>
	<?php
}

/**
 * Helper function for disable_right_now_comment_count()
 *
 * @return string|bool Returns "n/a" or false if called from outside the "Right Now" Dashboard widget.
 * @see disable_right_now_comment_count()
 */
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
 * @param string|int $email_or_id Email, ID, or username for user to lookup
 * @return false|array Profile info formatted as noted here: http://en.gravatar.com/site/implement/profiles/php/. If user not found, returns false.
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
 * You can use this function to only call get_avatar() when the user has a Gravatar and display nothing (rather than a placeholder image) when they don't.
 *
 * @param string $email Email to check for a gravatar
 * @return bool Returns true if $email has a gravatar
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
 * Check if a URL is in a specified whitelist
 *
 * Example whitelist: array( 'mydomain.com', 'mydomain.net' )
 *
 * @param string $url URL to check for
 * @param array $whitelisted_domains Array of whitelisted domains
 * @return bool Returns true if $url is in the $whitelisted_domains
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
 * @param array $users Array of user logins
 */
function wpcom_vip_bulk_user_management_whitelist( $users ) {
	add_filter( 'bulk_user_management_admin_users', function() use ( $users ) { return $users; } );
}

/**
 * A version of wp_oembed_get() that provides caching.
 *
 * Note that if you're using this within the contents of a post, it's probably better to use the existing
 * WordPress functionality: http://codex.wordpress.org/Embeds. This helper function is more meant for other
 * places, such as sidebars.
 *
 * @param string $url The URL that should be embedded
 * @param array $args Addtional arguments and parameters the embed
 * @return string
 */
function wpcom_vip_wp_oembed_get( $url, $args = array() ) {
	$cache_key = md5( $url . '|' . serialize( $args ) );

	if ( false === $html = wp_cache_get( $cache_key, 'wpcom_vip_wp_oembed_get' ) ) {
		$html = wp_oembed_get( $url, $args );

		wp_cache_set( $cache_key, $html, 'wpcom_vip_wp_oembed_get' );
	}

	return $html;
}

/**
 * Helper function to disable the WordPress.com wide Zemanta Tools for all users.
 */
function wpcom_vip_disable_zemanta_for_all_users() {
	add_filter( 'zemanta_force_disable', '__return_true' );
}

/**
 * Checks if the current site_url() matches from a specified list.
 * 
 * @param array|string $site_urls List of site URL hosts to check against
 * @return bool If current site_url() matches one in the list
 */
function wpcom_vip_check_site_url( $site_urls ) {
	if ( ! is_array( $site_urls ) )
	    	$site_urls = array( $site_urls );

	$current_site_url = site_url();
	$current_site_url =	parse_url( $current_site_url, PHP_URL_HOST ) . parse_url( $current_site_url, PHP_URL_PATH ); // to allow for local subfolder setups like vip.dev/site
	return in_array( $current_site_url, $site_urls );
}

/**
 * Returns the HTTP_HOST for the current site's home_url()
 *
 * @return string
 */
function wpcom_vip_get_home_host() {
	static $host;
	if ( ! isset( $host ) )
		$host = parse_url( home_url(), PHP_URL_HOST );
	return $host;
}

/**
 * Give themes the opportunity to disable WPCOM-specific smilies.
 * Note: Smilies disabled by this method will not fall back to core smilies.
 * @param  mixed $smilies_to_disable List of strings that will not be converted into smilies.
 *               A single string will be converted to an array & work
 * @uses filter smileyproject_smilies
 */
function wpcom_vip_disable_smilies( $smilies_to_disable ) {
	if ( is_string( $smilies_to_disable ) ) {
		$smilies_to_disable = array( $smilies_to_disable );
	}

	if ( ! is_array( $smilies_to_disable ) || ! count( $smilies_to_disable ) ) {
		return;
	}

	add_filter( 'smileyproject_smilies', function( $smilies ) use ( $smilies_to_disable ) {
		foreach ( $smilies_to_disable as $smiley ) {
			if ( is_string( $smiley ) && isset( $smilies[$smiley] ) ) {
				unset( $smilies[$smiley] );
			}
		}
		return $smilies;
	} );
}

/**
 * Get the URL of theme files relative to the home_url
 *
 * @param string $path The path of the file to get a URL for
 */
function wpcom_vip_home_template_uri( $path ) {
	return str_replace( site_url(), home_url(), get_template_directory_uri() . $path );
}

/**
 * Use secure URLs in rel_canonical
 */
function wpcom_vip_https_canonical_url() {
	// Note: rel_canonical is not in core yet
	// https://core.trac.wordpress.org/ticket/30581
	add_filter( 'rel_canonical', function( $link ) {
		return str_replace( 'http://', 'https://', $link );
	}, 99 );
}

<?php
/*
 * VIP Helper Functions
 * 
 * These functions can all be used in your local WordPress environment. Add 
require_once( WP_CONTENT_DIR . '/themes/vip/plugins/vip-helper.php' );
 * in the theme's functions.php to use this
 */

/*
 * Simple 301 redirects
 * array elements should be in the form of:
 * '/old' => 'http://wordpress.com/new/'
 *
 * @author nickmomrik
 */

function vip_redirects( $vip_redirects_array = array() ) {
	$uri = untrailingslashit( $_SERVER['REQUEST_URI'] );

	foreach( (array) $vip_redirects_array as $orig => $new ) {
		if ( untrailingslashit( $orig ) == $uri ) {
			wp_redirect($new, 301);
			exit;
		}
	}
}

/*
 * PHP func file_get_contents() w/ WP_CACHE integration
 * and echos by default
 * @author based on code by CNN
 */

function vip_wp_file_get_content( $url, $echo_content = true, $timeout=3 ) {
	$key = md5( $url );
	if ( $out = wp_cache_get( $key , 'vip') ) {
			if ( $echo_content ) {
					echo $out;
					return;
			} else
					return $out;
	}

	// Don't accept timeouts of 0 (no timeout), or over 3 seconds
	$new_timeout = min( 3, max( 1, (int)$timeout ) );
	// Record the previous default timeout value
	$old_timeout = ini_get( 'default_socket_timeout' );
	// Set the timeout value to avoid holding up php
	ini_set( 'default_socket_timeout', $new_timeout );
	// Make our request
	$page = @file_get_contents( $url );
	// Reset the default timeout to its old value
	ini_set( 'default_socket_timeout', $old_timeout );

	wp_cache_set( $key, $page, 'vip', 600 );

	if ( $echo_content ) {
		echo $page;
		return;
	} else {
		return $page;
	}
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
 * ex. vip_main_feed_redirect( 'http://feeds.feedburner.com/ourfeeds/thefeed' );
 * @author lloydbudd
 */

function vip_main_feed_redirect( $target ) {
	header( "X-Accel-Expires: 0" );
	define('FEEDURL', '#^/(wp-(rdf|rss|rss2|atom|rssfeed).php|index.xml|feed)/?$#i');
	$request = $_SERVER['REQUEST_URI'];
	$agent = $_SERVER['HTTP_USER_AGENT'];

	if ( preg_match( FEEDURL, $request ) || '/feed' == $request ) {
		if ( !preg_match( '#feedburner|feedvalidator|MediafedMetrics#i', $agent ) ) {
			wp_redirect( $target, '302' );
			die;
		}
	}
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
	if ( strpos( $_SERVER[ 'REQUEST_URI' ], 'DARTIframe.html' ) ) {
		header( 'Content-Type: text/html' );
		echo file_get_contents( get_stylesheet_directory() . '/DARTIframe.html' );
		exit;
	}
}

/*
 * Send moderation emails to multiple addresses
 * @author nickmomrik
 */

function vip_multiple_moderators($emails) {
	$email_headers = "From: donotreply@wordpress.com" . "\n" . "CC: " . implode(', ', $emails);
	add_filter('comment_moderation_headers', create_function( '', 'return '.var_export( $email_headers, true ).';') );
}

/*
 * DEPRECATED *
 * This function is no longer necessary. On 3/26/2010 we deployed code that automatically adds the mtime query string to
 * all css|gif|jpeg|jpg|js|png|swf|ico files serverd from the CDN that occur before wp_footer(). This invalidates the browser cache.
 * We also recently made changes that invalidate the file at the CDN when a new copy is committed to SVN.
 *
 * Add an mtime query string to a filename URL.
 *
 * CSS, JS and images hosted in WordPress.com themes are served 
 * through a content delivery network (CDN) for improved visitor experience.
 * For theme files loaded from s?.wordpress.com checking in 
 * an update/modification of the file will not (currently) result 
 * in the CDN cache being flushed.
 * 
 * For style.css and other CSS stylesheet links in index.php header
 * WordPress sitewide automatically appends 
 * an mtime query string, ?m=[date] in the source (wp_head hook).
 *
 * This function can be used so the filename or query string do not have to be manually updated each time a file is modified.
 *
 * Examples:
 * <?php echo wpcom_vip_cache_buster( get_bloginfo('template_directory') . '/print.css' ); ?>
 * <?php echo wpcom_vip_cache_buster( get_bloginfo('template_directory') . '/images/rss-icon.jpg' ); ?>
 * 
 * @author nickmomrik
 */

function wpcom_vip_cache_buster( $url, $mtime = null ) {
	if ( strpos($url, '?m=') )
		return $url;

	if ( is_null($mtime) ) {
		$parts = parse_url( $url );

		if ( !isset($parts['path']) || empty($parts['path']) ) {
			$mtime = false;
		} else {
			$file = ABSPATH . ltrim( $parts['path'], '/' );

			if ( !$mtime = @filemtime( $file ) )
				$mtime = false;
		}
	}

	if ( !$mtime )
		return $url;

	list($url, $q) = explode( '?', $url, 2); //Get rid of any query string
	return "$url?m=$mtime";
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

	if ( !empty( $text ) ) {
		echo "\n<meta name=\"description\" content=\"$text\" />\n";
	}
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

	$all_ids_query = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE 1 $where_add" );
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
function vip_safe_wp_remote_get( $url, $fallback_value='', $threshold=3, $timeout=1, $retry=20 ) {
	global $blog_id;
	
	$cache_group = "$blog_id:vip_safe_wp_remote_get";
	$cache_key = 'disable_remote_get_' . md5( parse_url( $url, PHP_URL_HOST ) );
	
	// valid url
	if ( empty( $url ) || !parse_url( $url ) )
		return ( $fallback_value ) ? $fallback_value : new WP_Error('invalid_url', $url );

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
	$response = wp_remote_get( $url, array( 'timeout' => $timeout ) );
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
	
	if( is_wp_error( $response ) )
		return ( $fallback_value ) ? $fallback_value : $response;

	return $response;
}

?>
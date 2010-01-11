<?php
/*
 * VIP Helper Functions
 * 
 * These functions can all be used in your local WordPress environment.
 *
 *	Add 
include(ABSPATH . 'wp-content/themes/vip/plugins/vip-helper.php');
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
	$uri = $_SERVER['REQUEST_URI'];

	foreach( (array) $vip_redirects_array as $orig => $new ) {
		if ( $orig == untrailingslashit($uri) ) {
			wp_redirect($new, 301);
			exit;
		}
	}
}

/*
 * PHP func file_get_contents() w/ WP_CACHE integration
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
        } else
                return $page;
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
       die( '' );
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
                exit;
        }
}

/*
 * Send moderation emails to multiple addresses
 * @author nickmomrik
 */

function vip_multiple_moderators($emails) {
        $email_headers = "From: donotreply@wordpress.com" . "\n" . "CC: " . implode(', ', $emails);
        add_filter('comment_moderation_headers', returner($email_headers));
}

/*
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
								'length' => 25, // amount of length units to use for the meta description
								'length_unit' => 'word', // the length unit can be either "word" or "char"
								'use_excerpt' => true, // if the post/page has an excerpt it will overwrite the generated description if this is set to true
								'add_category_desc' => true, // add the category description to category views if this value is true
								'add_tag_desc' => true, // add the category description to category views if this value is true
								'add_other_desc' => true, // add the blog description/tagline to all other pages if this value is true
								'default_description' => '', // in case no description is defined use this as a default description
								'custom_field_key' => '', // if a custom field key is set we try to use the value of this field as description
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
		else if( $use_excerpt && !empty( $post->post_excerpt ) ) {
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
 * $random_posts = vip_get_random_posts( $amount=50, "AND post_status='publish' AND post_type='post' AND post_date_gmt > '2009-01-01 00:00:00' ); // would only consider posts after Jan 1st 2009
 * // if you imported your blog or for some other reason you know that your interesting post ids start at a certain value you can be sure that only those are queried.
 * $random_posts = vip_get_random_posts( $amount=50, NULL, NULL, 50000 ); // create only random numbers > 50000
 * // by default you can also get a list of post_ids instead of their objects
 * $random_posts = vip_get_random_posts( $amount=50, NULL, true ); // will return 50 random post ids for published posts.
 * @author tottdev
 */
function vip_get_random_posts( $amount = 1, $where_add = "AND post_status='publish' AND post_type='post'", $return_ids = false, $min_id = 1 ) {
	global $wpdb, $vip_get_random_posts_rnd_ids, $vip_get_random_posts_current_rnd_ids;
	$random_posts = array();
	
	$max_id_query = "SELECT MAX(ID) AS max_id FROM $wpdb->posts";
	$max_id_query = apply_filters( 'vip_get_random_posts_max_id_query', $max_id_query );
	$max_id = $wpdb->get_var( $max_id_query );

	if ( !$max_id ) 
		return false;
	
	$seed = hexdec( substr( md5( microtime() ), -8 ) ) & 0x7fffffff;
	mt_srand( $seed );
		
	do {
		$random_id = mt_rand( $min_id, $max_id );
		
		if ( isset( $vip_get_random_posts_rnd_ids[$random_id] ) )
			continue;
			
		$vip_get_random_posts_rnd_ids[$random_id] = 1;

		if ( $return_ids ) {
			$post_query = "SELECT ID FROM $wpdb->posts WHERE ID>$random_id $where_add LIMIT 1";
			$post_query = apply_filters( 'vip_get_random_posts_post_query', $post_query );
			$rnd_post = $wpdb->get_var( $post_query );
			if ( $rnd_post && !isset( $vip_get_random_posts_current_rnd_ids[$rnd_post] ) ) {
				$vip_get_random_posts_current_rnd_ids[$rnd_post] = 1;
				$random_posts[$random_id] = $rnd_post;
			}
		} else {
			$post_query = "SELECT * FROM $wpdb->posts WHERE ID>$random_id $where_add LIMIT 1";
			$post_query = apply_filters( 'vip_get_random_posts_post_query', $post_query );
			$rnd_post = $wpdb->get_row( $post_query, OBJECT );
			if ( $rnd_post && !isset( $vip_get_random_posts_current_rnd_ids[$rnd_post->ID] ) ) {
				$random_posts[$random_id] = $rnd_post;
				$vip_get_random_posts_current_rnd_ids[$rnd_post->ID] = 1;
			}
		}
		
	} while( count( $random_posts ) < $amount );
	
	$random_posts = apply_filters( 'vip_get_random_posts_random_posts', $random_posts );
	return $random_posts;
}

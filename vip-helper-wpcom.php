<?php
/**
 * VIP Helper Functions that are specific to WordPress.com
 *
 * These functions relate to WordPress.com specific plugins, 
 * filters, and actions that are enabled across all of WordPress.com.
 *
 * To add these functions to your theme add
 * include_once( WP_CONTENT_DIR . '/themes/vip/plugins/vip-helper-wpcom.php' );
 * in the theme's 'functions.php'. This should be wrapped in a 
 * if ( function_exists('wpcom_is_vip') ) { // WPCOM specific
 * so you don't load it in your local environment. This will help alert you if
 * have any unconditional dependencies on the WordPress.com environment.
 */

/**
 * Disable the WordPress.com filter that prevents orphans in titles
 *
 * See http://en.blog.wordpress.com/2006/12/24/no-orphans-in-titles/
 *
 * @author nickmomrik
 */
function vip_allow_title_orphans() {
	remove_filter('the_title', 'widont');
}

/**
 * VIP Legacy Related Posts (HTML formatted results)
 *
 * Don't use for new projects, just use WPCOM_RelatedPosts directly, since it has hooks
 * like jetpack_relatedposts_filter_args, jetpack_relatedposts_filter_filters
 * 
 * @param int $max_num Optional. Maximum number of results you want (default: 5).
 * @param array $additional_stopwords No longer used, we leave the stopwords magic to ES which knows more about word frequencies across articles.
 * @param bool $exclude_own_titles No longer used.
 * @return string Returns an HTML unordered list of related posts from the same blog.
 */
function wpcom_vip_flaptor_related_posts( $max_num = 5, $additional_stopwords = array(), $exclude_own_titles = true ){
	$related_output = '';
	$related_posts = wpcom_vip_get_flaptor_related_posts( $max_num, $additional_stopwords, $exclude_own_titles );

	if ( ! empty( $related_posts ) ) {
		$related_output .= '<ul>';
		foreach( $related_posts as $result ) {
			$related_output .= '<li><a href="' . esc_url( $result['url'] ) . '">'. esc_html( $result['title'] ) . '</a></li>';
		}
		$related_output .= '</ul>';
	}
	return $related_output;
}

/**
 * VIP Legacy Related Posts (get post_id, title, url)
 *
 * Don't use for new projects, just use WPCOM_RelatedPosts directly, since it has hooks
 * like jetpack_relatedposts_filter_args, jetpack_relatedposts_filter_filters
 * 
 * For backwards compatability, this function finds related posts on the current blog 
 * using Elasticsearch, then converts the results to match the original sphere results format.
 * 
 * @param int $max_num Optional. Maximum number of results you want (default: 5).
 * @param array $additional_stopwords No longer used.
 * @param bool $exclude_own_titles No longer used.
 * @return array of related posts.
 */
function wpcom_vip_get_flaptor_related_posts( $max_num = 5, $additional_stopwords = array(), $exclude_own_titles = true ) {
	if ( method_exists( 'WPCOM_RelatedPosts', 'init' ) ) {
		$post_id = get_the_ID();
		$rp = WPCOM_RelatedPosts::init();
		$related = $rp->get_for_post_id( $post_id, array( 
			'size' => $max_num,
		) );
	
		if ( $related ) {
			//rebuilding the array to match sphere related posts (and flaptor related posts)
			$results = array();
			foreach ( $related as $result) {
				if ( $post_id == $result['id'] ) {
					continue;
				}
				$new_result = array();
				//This url contains aggregate click counting for performance tuning of WPCOM_RelatedPosts
				$new_result['url'] = $result['url'];
				$new_result['post_id'] = $result['id'];
				$new_result['title'] = $result['title'];
				$results[] = $new_result;
			}
			$results = array_slice( $results, 0, $max_num );
			return $results;
		}

		return false;
	} else {
		// Fallback for local environments where flaptor isn't available
		$related_posts = array();

		$host = parse_url( home_url(), PHP_URL_HOST );
		$source_info = array(
			'sourcename' => get_bloginfo( 'name' ),
			'sourceurl' => home_url(),
			'sourcetype' => 'same_domain',
		);

		$post_id = get_the_ID();
		$related_query_args = array(
			'posts_per_page' => $max_num,
		);

		$categories = get_the_category( $post_id );
		if ( ! empty( $categories ) )
			$related_query_args[ 'cat' ] = $categories[0]->term_id;

		$related_query = new WP_Query( $related_query_args );

		foreach ( $related_query->get_posts() as $related_post ) {
			$related_post_id = $related_post->ID;
			$related_posts[] = array(
				'url' => get_permalink( $related_post_id ),
				'title' => get_the_title( $related_post_id ),
				'timestamp' => get_the_time( 'Y-m-d', $related_post_id ),
				'host' => $host,
				'source' => $source_info,
			);
		}
		return $related_posts;
	}
}

/**
 * Allows users of contributor role to be able to upload media.
 *
 * Contrib users still can't publish.
 *
 * @author mdawaffe
 * @link http://vip.wordpress.com/documentation/allow-contributors-to-upload-images/ Allow Contributors to Upload Images
 */
function vip_contrib_add_upload_cap() {
	add_action( 'init', '_vip_contrib_add_upload_cap');
	add_action( 'xmlrpc_call', '_vip_contrib_add_upload_cap' ); // User is logged in after 'init' for XMLRPC
}

/**
 * Helper function for vip_contrib_add_upload_cap() to change the user roles
 *
 * @link http://vip.wordpress.com/documentation/allow-contributors-to-upload-images/ Allow Contributors to Upload Images
 * @see vip_contrib_add_upload_cap()
 */
function _vip_contrib_add_upload_cap() {
	if ( ! is_admin() && ! defined( 'XMLRPC_REQUEST' ) )
		return;

	if ( function_exists( 'wpcom_vip_add_role_caps' ) ) {
		wpcom_vip_add_role_caps( 'contributor', array( 'upload_files' ) );
	} else {
		// Temp debug to track down broken themes
		if ( function_exists( 'send_vip_team_irc_alert' ) )
		send_vip_team_irc_alert( '[vip-helper fatal] ' . site_url() . ' add_role_cap no exist for _vip_contrib_add_upload_cap: ' . wp_debug_backtrace_summary() );
	}
}

/**
 * Un-hide the extra size and alignment options in the gallery tab of the media upload box
 *
 * @author tellyworth
 */
function vip_admin_gallery_css_extras() {
	add_action('admin_print_styles', '_vip_admin_gallery_css_extras');
}

/**
 * Helper function for vip_admin_gallery_css_extras()
 *
 * @see vip_admin_gallery_css_extras()
 */
function _vip_admin_gallery_css_extras() {
?>
<style type="text/css">
#gallery-form tr.url, #gallery-form tr.align, #gallery-form tr.image-size { display: table-row; }
#gallery-form tr.submit input.button { display: table-row; }
</style>
<?php
}

/**
 * Completely disable enhanced feed functionality
 */
function wpcom_vip_disable_enhanced_feeds() {
	remove_filter( 'add_to_feed', 'add_categories_to_feed' );
	remove_filter( 'add_to_feed', 'add_tags_to_feed' );

	vip_remove_enhanced_feed_images();
	wpcom_vip_remove_feed_tracking_bug();
}

/**
 * Do not display the images in enhanced feeds.
 * 
 * Helper function for wpcom_vip_disable_enhanced_feeds().
 *
 * @author nickmomrik
 * @see wpcom_vip_disable_enhanced_feeds()
 */
function vip_remove_enhanced_feed_images() {
	remove_filter( 'add_to_feed', 'add_delicious_to_feed' );
	remove_filter( 'add_to_feed', 'add_facebook_to_feed' );
	remove_filter( 'add_to_feed', 'add_twitter_to_feed' );
	remove_filter( 'add_to_feed', 'add_stumbleupon_to_feed' );
	remove_filter( 'add_to_feed', 'add_digg_to_feed' );
	remove_filter( 'add_to_feed', 'add_reddit_to_feed' );
	remove_filter( 'add_to_feed', 'add_commentcount_to_feed' );
}

/**
 * Remove the tracking bug added to all WordPress.com feeds.
 * 
 * Helper function for wpcom_vip_disable_enhanced_feeds().
 *
 * @see wpcom_vip_disable_enhanced_feeds()
 */
function wpcom_vip_remove_feed_tracking_bug() {
	remove_filter( 'the_content', 'add_bug_to_feed', 100 );
	remove_filter( 'the_excerpt_rss', 'add_bug_to_feed', 100 );
}

/**
 * Override default colors of audio player.
 * 
 * Colors specified in the shortcode still can override.
 *
 * @author nickmomrik
 * @param array $colours Key/value array of colours to override
 */
function wpcom_vip_audio_player_colors( $colors ) {
	$default_colors = array("bg" => "0xf8f8f8", "leftbg" => "0xeeeeee", "lefticon" => "0x666666", "rightbg" => "0xcccccc", "rightbghover" => "0x999999", "righticon" => "0x666666", "righticonhover" => "0xffffff", "text" => "0x666666", "slider" => "0x666666", "track" => "0xFFFFFF", "border" => "0x666666", "loader" => "0x9FFFB8");

	add_filter('audio_player_default_colors', create_function( '', 'return '.var_export( array_merge($default_colors, $colors), true ).';') );
}

/**
 * Prints the title of the most popular blog post
 * 
 * @author nickmomrik
 * @param int $days Optional. Number of recent days to find the most popular posts from. Minimum of 2.
 */
function wpcom_vip_top_post_title( $days = 2 ) {
	global $wpdb;

	// Compat for .org
	if ( ! function_exists( 'stats_get_daily_history' ) )
		return array(); // TODO: return dummy data

	$title = wp_cache_get("wpcom_vip_top_post_title_$days", 'output');
	if ( empty($title) ) {
		if ( $days < 2 || !is_int($days) ) $days = 2; // minimum is 2 because of how stats rollover for a new day

		$topposts = array_shift(stats_get_daily_history(false, $wpdb->blogid, 'postviews', 'post_id', false, $days, '', 11, true));
		if ( $topposts ) {
			get_posts(array('include' => join(', ', array_keys($topposts))));
			$posts = 0;
			foreach ( $topposts as $id => $views ) {
				$post = get_post($id);
				if ( empty( $post ) )
					$post = get_page($id);
				if ( empty( $post ) )
					continue;
				$title .= $post->post_title;
				break;
			}
		} else {
			$title .= '';
		}
		wp_cache_add("wpcom_vip_top_post_title_$days", $title, 'output', 1200);
	}
	echo $title;
}

/**
 * Keeps category and tag links local to the blog instead of linking to http://en.wordpress.com/tags/
 */
function make_tags_local() {
	remove_filter( 'the_category', 'globalize_tags' );
	remove_filter( 'the_tags', 'globalize_taxonomy' );
	remove_filter( 'term_links-post_tag', 'globalize_taxonomy' );
}

/**
 * Returns the URL to an image resized and cropped to the given dimensions.
 *
 * You can use this image URL directly -- it's cached and such by our servers.
 * Please use this function to generate the URL rather than doing it yourself as
 * this function uses staticize_subdomain() makes it serve off our CDN network.
 *
 * Somewhat contrary to the function's name, it can be used for ANY image URL, hosted by us or not.
 * So even though it says "remote", you can use it for attachments hosted by us, etc.
 *
 * @link http://vip.wordpress.com/documentation/image-resizing-and-cropping/ Image Resizing And Cropping
 * @param string $url The raw URL to the image (URLs that redirect are currently not supported with the exception of http://foobar.wordpress.com/files/ type URLs)
 * @param int $width The desired width of the final image
 * @param int $height The desired height of the final image
 * @param bool $escape Optional. If true (the default), the URL will be run through esc_url(). Set this to false if you need the raw URL.
 * @return string
 */
function wpcom_vip_get_resized_remote_image_url( $url, $width, $height, $escape = true ) {
	$width = (int) $width;
	$height = (int) $height;

	// Photon doesn't support redirects, so help it out by doing http://foobar.wordpress.com/files/ to http://foobar.files.wordpress.com/
	if ( function_exists( 'new_file_urls' ) )
		$url = new_file_urls( $url );

	$thumburl = jetpack_photon_url( $url, array( 'resize' => array( $width, $height ) ) );

	return ( $escape ) ? esc_url( $thumburl ) : $thumburl;
}

/**
 * Gets the data used by the "Top Posts" widget.
 * 
 * Our Top Posts widget (http://en.support.wordpress.com/widgets/top-posts-widget/) uses a display_top_posts() function to display a list of popular posts.
 * You can use this function in your themes. The function uses data from WordPress.com Stats (http://en.support.wordpress.com/stats/) to generate the list.
 *
 * If you would like more control over the output of display_top_posts(), use the get_top_posts() function.
 *
 * Note: in the results, post_ID = 0 is used to track home page views.
 * 
 * @param int $number Optional. At least 10 posts are always returned; this parameter controls how many extra you want. Valid values: 1-10 (default is 10).
 * @param int $days Optional. How many days of stats should be used in the calculation; defaults to 2.
 * @return array
 */
function get_top_posts( $number = 10, $days = 2 ) {
	global $wpdb;

	// Compat for .org
	if ( ! function_exists( 'stats_get_daily_history' ) )
		return array(); // TODO: return dummy data


	$top_posts = wp_cache_get( "get_top_posts_{$number}_{$days}" );

	if ( !$top_posts ) {
		if ( $number < 1 || $number > 20 || !is_int( $number ) )
			$number = 10;

		if ( $days < 2 || !is_int( $days ) )
			$days = 2; // minimum is 2 because of how stats rollover for a new day

		$top_posts = array_shift( stats_get_daily_history( false, $wpdb->blogid, 'postviews', 'post_id', false, $days, '', $number + 10, true ) );

		wp_cache_add( "get_top_posts_{$number}_{$days}", $top_posts, '', 1200 );
	}

	return $top_posts;
}

/**
 * Prevent Youtube embeds in comments
 *
 * Feature: http://en.support.wordpress.com/videos/youtube/#comment-embeds
 *
 * @author nickmomrik
 */
function wpcom_vip_disable_youtube_comment_embeds() {
	remove_filter( 'comment_text', 'youtube_link', 1 );
	remove_filter( 'comment_text', 'youtube_markup' );
}

/**
 * Overrides a theme's $content_width to remove the image constraint.
 *
 * @author nickmomrik
 */
function wpcom_vip_allow_full_size_images_for_real() {
	remove_filter( 'image_downsize', 'wpcom_resize', 10, 3 );
	add_filter( 'image_downsize', '_wpcom_vip_allow_full_size_images_for_real', 10, 3 );
}

/**
 * Helper function for wpcom_vip_allow_full_size_images_for_real()
 *
 * @param array $ignore This function doesn't make use of this parameter 
 * @param int $id Attachment post ID
 * @param string $size If "full", function will return the full size image, otherwise it will be downscaled to this size.
 * @return array
 * @see wpcom_vip_allow_full_size_images_for_real()
 */
function _wpcom_vip_allow_full_size_images_for_real( $ignore, $id, $size ) {
	if ( 'full' == $size ) {
		$img_url = wp_get_attachment_url( $id );
		$imagedata = wp_get_attachment_metadata( $id );

		if ( $imagedata ) {
			$h = $imagedata['height'];
			$w = $imagedata['width'];
		}

		return array( $img_url, $w, $h, false );
	} else {
		if ( function_exists( 'wpcom_resize' ) )
			return wpcom_resize( $ignore, $id, $size );
	}
	return $ignore;
}

/**
 * Makes the smallest sized thumbnails be cropped (i.e. the ones used in [gallery]).
 *
 * We've removed the checkbox from Settings -> Media on WordPress.com, so this re-enables the feature.
 */
function wpcom_vip_crop_small_thumbnail() {
	if ( defined( 'WPCOM_IS_VIP_ENV' ) && WPCOM_IS_VIP_ENV )
		add_filter( 'pre_option_thumbnail_crop', create_function('', 'return 1;'), 11 ); // 11 = after our disable filter
}

/**
 * Do not display the Polldaddy rating.
 *
 * Usually used for a page or post where ratings are not wanted.
 *
 * @author nickmomrik
 */
function wpcom_vip_remove_polldaddy_rating() {
	remove_filter( 'the_content', 'polldaddy_show_rating', 5 );
	remove_filter( 'post_flair',  'polldaddy_show_rating', 10 );
}

/**
 * Removes the <media:content> tags from the RSS2 feed.
 * 
 * You should really call this when creating a custom feed (best to leave them in your normal feed)
 * For details on creating a custom feed, see http://lobby.vip.wordpress.com/custom-made/altering-feeds/
 */
function wpcom_vip_remove_mediacontent_from_rss2_feed() {
	add_action( 'template_redirect', '_wpcom_vip_remove_mediacontent_from_rss2_feed', 11 );
}

/**
 * Helper function for wpcom_vip_remove_mediacontent_from_rss2_feed()
 *
 * @see wpcom_vip_remove_mediacontent_from_rss2_feed()
 */
function _wpcom_vip_remove_mediacontent_from_rss2_feed() {
	// Really the namespace should be removed too but leave it for legacy reasons
	remove_action( 'rss2_item', 'mrss_item', 10, 0 );
}

/**
 * Disable the post-post screen
 */
function wpcom_vip_disable_postpost() {
	remove_filter( 'redirect_post_location', 'wpcom_maybe_post_post' ); // v1
	add_filter( 'post_post_disable', '__return_true' ); // v2
}

/**
 * Outputs Open Graph tags to various pages on the site
 *
 * @link http://vip.wordpress.com/documentation/open-graph/ Adding Open Graph Tags
 * @see http://developers.facebook.com/docs/opengraph/ Open Graph
 */
function wpcom_vip_enable_opengraph() {
	add_filter( 'jetpack_enable_open_graph', '__return_true', 99 ); // hook later so we don't run into the WP.com filter which disables open graph tags for VIPs

	// Disable the Facebook plugin's Open Graph tags
	add_action( 'template_redirect', '_wpcom_vip_disable_fb_plugin_opengraph' ); 
}

/**
 * Helper function for wpcom_vip_enable_opengraph()
 *
 * @see wpcom_vip_enable_opengraph()
 */
function _wpcom_vip_disable_fb_plugin_opengraph() {
	if( method_exists( 'Facebook_Open_Graph_Protocol', 'add_og_protocol' ) )
		remove_action( 'wp_head', array( 'Facebook_Open_Graph_Protocol', 'add_og_protocol' ) );
}

/**
 * Allows you to customize the /via and follow recommendation for the WP.com Sharing Twitter button.
 *
 * @param string $via Optional. What the /via should be set to. Empty value disables the feature (the default).
 */
function wpcom_vip_sharing_twitter_via( $via = '' ) {
	if( empty( $via ) ) {
		$via_callback = '__return_false';
	} else {
		// sanitize_key() without changing capitizalization
		$raw_via = $via;
		$via = preg_replace( '/[^A-Za-z0-9_\-]/', '', $via );
		$via = apply_filters( 'sanitize_key', $via, $raw_via );

		$via_callback = create_function( '', sprintf( 'return "%s";', $via ) );
	}

	add_filter( 'jetpack_sharing_twitter_via', $via_callback );
	add_filter( 'jetpack_open_graph_tags', function( $tags ) use ( $via ) {
		if ( isset( $tags['twitter:site'] ) ) {
			if ( empty( $via ) )
				unset( $tags['twitter:site'] );
			else
				$tags['twitter:site'] = '@' . $via;
		}
		return $tags;
	}, 99 ); // later so we run after Twitter Cards have run
}

/**
 * Disables WPCOM Post Flair entirely on the frontend.
 * This removes the filters and doesn't allow the stylesheet to be enqueued.
 */
function wpcom_vip_disable_post_flair() {
	add_filter( 'post_flair_disable', '__return_true' );
}

/**
 * Disables WPCOM Sharing in Posts and Pages.
 *
 * Sharing can be disabled in the dashboard, by removing all buttons from Enabled Services.
 * 
 * This function is primary for automating sharing when you have numerous sites to administer.
 * It also assists having consistent CSS containers between development and production.
 * 
 * @link http://en.support.wordpress.com/sharing/ Sharing
 */
function wpcom_vip_disable_sharing() {
	$function = function() {
		remove_filter( 'post_flair', 'sharing_display', 20 );
	};

	// Post Flair sets things up on init so we need to call on that if init hasn't fired yet.
	if ( did_action( 'init' ) )
		call_user_func( $function );
	else
		add_action( 'init', $function, 99 );
}

/**
 * Enables WPCOM Sharing in Posts and Pages.
 *
 * This feature is on by default, so the function is only useful if you've also used wpcom_vip_disable_sharing().
 * 
 * @link http://en.support.wordpress.com/sharing/ Sharing
 */
function wpcom_vip_enable_sharing() {
	add_filter( 'post_flair', 'sharing_display', 20 );
}

/**
 * Disables WP.com Likes for Posts and Custom Post Types
 * 
 * Sharing can also be disabled from the Dashboard (Settings > Sharing).
 * 
 * This function is primarily for programmatic disabling of the feature, for example when working with custom post types.
 */
function wpcom_vip_disable_likes() {
	add_filter( 'wpl_is_likes_visible', '__return_false', 999 );
}

/**
 * Disables WP.com Likes for Posts and Custom Post Types
 * 
 * This feature is on by default, so the function is only useful if you've also used wpcom_vip_disable_sharing().
 */
function wpcom_vip_enable_likes() {
	add_filter( 'wpl_is_likes_visible', '__return_true', 999 );
}

/**
 * Sets the default for subscribe to comments to off
 */
function wpcom_vip_disable_default_subscribe_to_comments() {
	add_filter( 'subscribe_to_comments_override', '__return_false', 99 ); // run late so we override others
}

/**
 * Force a site invitation to a user to only be accepted by a user who has the matching WordPress.com account's email address.
 *
 * The default behavior for invitations is to allow any WordPress.com user accept an invitation
 * regardless of whether their email address matches what the invitation was sent to. This helper
 * function forces the invitation email to match the WordPress.com user's email address.
 *
 * @link http://vip.wordpress.com/documentation/customizing-invites/ Customizing Invites
 */
function wpcom_invite_force_matching_email_address() {
	add_filter( 'wpcom_invite_force_matching_email_address', '__return_true' );
}

/**
 * Reads a postmeta value directly from the master database.
 *
 * This is not intended for front-end usage. This purpose of this function is to avoid race conditions that could appear while the caches are primed.
 * A good scenario where this could be used is to ensure published posts are not syndicated multiple times by checking a postmeta flag that is set on syndication.
 *
 * Note: this looks complicated, but the intention was to use API functions rather than direct DB queries for upward compatibility.
 *
 * @param int $post_id The ID of the post from which you want the data.
 * @param string $key A string containing the name of the meta value you want.
 * @param bool $single Optional. If set to true then the function will return a single result as a string. If false (the default) the function returns an array.
 * @return mixed Value from get_post_meta
 */
function wpcom_uncached_get_post_meta( $post_id, $key, $single = false ) {
	global $wpdb;
	
	// make sure to bypass caching for all get requests
	if ( class_exists( 'WP_Object_Cache' ) ) {
		global $wp_object_cache;
		$old_object_cache = $wp_object_cache;
		if ( !class_exists( 'Fake_WP_Object_Cache' ) ) {
			class Fake_WP_Object_Cache extends WP_Object_Cache {
				function get($id, $group = 'default') {
					return false;
				}
			}
		}
		$wp_object_cache = new Fake_WP_Object_Cache();
	}
	
	// send all reads to master
	$srtm_backup = $changed_srtm = false;
	if ( true <> $wpdb->srtm ) {
		$changed_srtm = true;
		$srtm_backup = $wpdb->srtm;
		if ( is_callable( array( $wpdb, 'send_reads_to_masters' ) ) )
			$wpdb->send_reads_to_masters();
	}
	// update the meta cache
	update_meta_cache( 'post', array( $post_id ) );
	
	// get the postmeta data
	$result = get_post_meta( $post_id, $key, $single );
	
	// put correct object cache back
	$wp_object_cache = $old_object_cache;
	
	// send reads back to where they belong to
	if ( true === $changed_srtm )
		$wpdb->srtm = $srtm_backup;
	
	// check the default method
	$result_chk = get_post_meta( $post_id, $key, $single );
	// delete the meta cache if results differ to make sure subsequent get_post_meta() calls will refresh from db
	if ( $result_chk <> $result ) {
		wp_cache_delete( $post_id, 'post_meta' );
	}
	
	return $result;
}

/**
 * Queries posts by a postmeta key/value pair directly from the master database.
 *
 * This is not intended for front-end usage. This purpose of this function is to avoid race conditions that could appear while the caches are primed.
 * A good scenario where this could be used is to ensure published posts are not syndicated multiple times by checking if a post with a certain meta value already exists.
 * 
 * @param string $meta_key Post meta key to query
 * @param string $meta_value Post meta value to check for
 * @param string $post_type Optional; post_type of the post to query. Defaults to 'post'.
 * @param array $post_stati Optional; array of the post_stati that are supposed to be included. Defaults to: publish, draft, pending, private.
 * @param integer $limit Optional. Amount of possible posts to receive; not more than 10. Default is 1.
 * @return array|WP_Error Array with post objects or a WP_Error
 */
function wpcom_uncached_get_post_by_meta( $meta_key, $meta_value, $post_type = 'post', $post_stati = array( 'publish', 'draft', 'pending', 'private' ), $limit = 1 ) {
	global $wpdb;
	
	if ( empty( $meta_key ) || empty( $meta_value ) || empty( $post_stati ) || !is_array( $post_stati ) || !post_type_exists( $post_type ) )
		return new WP_Error( 'invalid_arguments', __( "At least one of the arguments of wpcom_uncached_get_post_by_meta is invalid" ) );
		
	if ( empty( $limit ) || $limit <= 0 || $limit > 10 )
		return new WP_Error( 'invalid_arguments', __( "Please use a limit between 1 and 10" ) );
	
	$post_status_string = '';
	$_post_status_string = array();
	
	foreach( $post_stati as $post_status )
		$_post_status_string[] = sprintf( "'%s'", esc_sql( $post_status ) );
	$post_status_string = implode( ", ", $_post_status_string );
	
	// send all reads to master
	$srtm_backup = $changed_srtm = false;
	if ( true <> $wpdb->srtm && is_callable( $wpdb, 'send_reads_to_masters' ) ) {
		$changed_srtm = true;
		$srtm_backup = $wpdb->srtm;
		$wpdb->send_reads_to_masters();
	}
	
	// query all posts matching the post_type and meta key/value pair
	$query = $wpdb->prepare( 
					"SELECT $wpdb->posts.*  FROM $wpdb->posts, $wpdb->postmeta 
					WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id 
					AND $wpdb->postmeta.meta_key = %s 
					AND $wpdb->postmeta.meta_value = %s
					AND $wpdb->posts.post_type = %s AND $wpdb->posts.post_status IN ( " . $post_status_string . " ) LIMIT 0, %d", esc_sql( $meta_key ), esc_sql( $meta_value ), esc_sql( $post_type ), (int) $limit
	);
	
	$posts = $wpdb->get_results( $query, OBJECT );

	// send reads back to where they belong to
	if ( true === $changed_srtm )
		$wpdb->srtm = $srtm_backup;
		
	return $posts;
}

/**
 * Removes the mobile app promotion from the bottom of the default mobile theme.
 *
 * Example: "Now Available! Download WordPress for iOS"
 */
function wpcom_disable_mobile_app_promotion() {
	remove_action( 'wp_mobile_theme_footer', 'mobile_app_promo' );
}

/**
 * Deprecated function. This used to work around a term ordering issue on a very old version of WordPress. No longer needed.
 *
 * @deprecated
 * @return bool Always returns false
 */
function wpcom_vip_enable_term_order_functionality() {
	return false;
}

/**
 * Sets a option lock on the master database. This can be used in order to ensure that certain processes are only executed once at a time.
 *
 * @param string $lock_name Name of the lock. 
 * @param int $lock_time Optional; amount of seconds until this lock expires and a new can be created. Minimum of 60 seconds, defaults to 300.
 * @return bool|WP_Error True/false depending on success, and WP_Error if a parameter value was invalid.
 */
function wpcom_set_option_lock( $lock_name, $lock_time = 300 ) {
	global $wpdb;
	
	if ( empty( $lock_name ) ) 
		return new WP_Error( 'invalid_arguments', __( "At least one of the arguments of wpcom_set_option_lock is invalid" ) );
		
	if ( empty( $lock_time ) || $lock_time < 60 )
		return new WP_Error( 'invalid_arguments', __( "Please use a lock time bigger than 60 seconds" ) );

	// query the option lock
	$lock = wpcom_get_option_lock( $lock_name );
	
	$time = microtime( true );
	$new_lock = $time + (int) $lock_time;

	if ( false === $lock ) { // check if lock exists
		// set new lock
		$result = $wpdb->insert( $wpdb->options, array( 'option_name' => '_option_lock_' . esc_attr( $lock_name ), 'option_value' => $new_lock ) );
		return true;
	} else { // check if lock is expired
		if ( $lock < $time ) {
			// update lock
			$result = $wpdb->update( $wpdb->options, array( 'option_name' => '_option_lock_' . esc_attr( $lock_name ), 'option_value' => $new_lock ),  array( 'option_name' => '_option_lock_' . esc_attr( $lock_name ) ) );
			return true;
		}
	}
	
	return false;
}

/**
 * Gets a option lock from the master database. This can be used in order to ensure that certain processes are only executed once at a time.
 *
 * @param string $lock_name name of the lock. 
 * @return bool False or WP_Error, or existing lock time.
 */
function wpcom_get_option_lock( $lock_name ) {
	global $wpdb;
	if ( empty( $lock_name ) ) 
		return new WP_Error( 'invalid_arguments', __( "At least one of the arguments of wpcom_set_option_lock is invalid" ) );
	
	// send all reads to master
	$send_to_master = false;
	if ( is_callable( array( $wpdb, 'send_reads_to_masters' ) ) )
		 $send_to_master = true;
		 
	$srtm_backup = $changed_srtm = false;
	if ( true === $send_to_master && true <> $wpdb->srtm ) {
		$changed_srtm = true;
		$srtm_backup = $wpdb->srtm;
		$wpdb->send_reads_to_masters();
	}
	
	// query the option lock
	$lock = $wpdb->get_var( $wpdb->prepare( "SELECT $wpdb->options.option_value FROM $wpdb->options WHERE $wpdb->options.option_name = %s", '_option_lock_' . esc_attr( $lock_name ) ) );
	
	// send reads back to where they belong to
	if ( true === $send_to_master && true === $changed_srtm )
		$wpdb->srtm = $srtm_backup;

	if ( is_null( $lock ) )
		return false;

	return $lock;
}

/**
 * Allows non-author users to submit any tags allowed via $allowedposttags instead of just $allowedtags
 */
function wpcom_vip_allow_more_html_in_comments() {
	add_action( 'init', '_wpcom_vip_allow_more_html_in_comments', 99 ); // load late so we override the WP.com filter
}

/**
 * Helper function for wpcom_vip_allow_more_html_in_comments()
 *
 * @see wpcom_vip_allow_more_html_in_comments()
 */
function _wpcom_vip_allow_more_html_in_comments() {
	remove_filter( 'pre_comment_content', 'wp_filter_kses' ); 
	add_filter( 'pre_comment_content', 'wp_filter_post_kses' );
}

/**
 * Sends an e-mail when a new user accepts an invite to join a site.
 *
 * @param array $emails Array of email address to notify when a user accepts an invitation to a site
 */
function wpcom_vip_notify_on_new_user_added_to_site( $emails ) {

	if ( ! is_admin() )
		return;

	add_action( 'wpcom_invites_user_accepted_invite', function ( $invitee_login, $invitee_role ) use ( $emails ) {
		global $current_user;

		$invitee = get_user_by( 'login', $invitee_login );

		if ( ! $invitee || is_wp_error( $invitee ) )
			return;

		get_currentuserinfo();

		$invitee_name = ( $invitee->display_name != $invitee->user_login ) ? "{$invitee->display_name} ({$invitee->user_login})" : 'someone with the username "' . $invitee->user_login . '"';
		$inviter_name = ( $current_user->display_name != $current_user->user_login ) ? "{$current_user->display_name} ({$current_user->user_login})" : 'someone with the username "' . $current_user->user_login . '"';
		$blog_name = get_bloginfo( 'name' ) . ' [' . home_url() . ']';

		$invitee_role = ( 'editor' == $invitee_role ) ? 'an ' . $invitee_role : 'a ' . $invitee_role;

		wp_mail(
			$emails,
			'New User Added To ' . $blog_name,
			"Hi,

This e-mail is to notify you that {$invitee_name} has accepted an invitation from {$inviter_name} to join {$blog_name} as {$invitee_role}.

Users for this site can be managed here: " . admin_url( 'users.php' ) . "

If you have any questions, feel free to reply to this e-mail.

-- WordPress.com VIP Support",
			array(
				'From: WordPress.com VIP Support <vip-support@wordpress.com>',
			)
		);

	}, 10, 2 );

}

/**
 * Remove devicepx.js from pageloads
 *
 * devicepx.js loads retina/HiDPI versions of certain files (Gravatars, etc) for devices that run at a
 * higher resolution (such as smartphones), and is distributed inside Jetpack.
 */
function wpcom_vip_disable_devicepx_js() {
	add_filter( 'devicepx_enabled', '__return_false' );
}

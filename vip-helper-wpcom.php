<?php
/*
 *	VIP Helper Functions that are specific to WordPress.com
 *
 * These functions relate to WordPress.com specific plugins, 
 * filters, and actions that are enabled across all of WordPress.com.
 *
 * To add these functions to your theme add
include_once( WP_CONTENT_DIR . '/themes/vip/plugins/vip-helper-wpcom.php' );
 * in the theme's 'functions.php'. This should be wrapped in a 
if ( function_exists('wpcom_is_vip') ) { // WPCOM specific
 * so you don't load it in your local environment. This will help alert you if
 * have any unconditional dependencies on the WordPress.com environment.
 */

/*
 * Disable the WordPress.com filter that prevents orphans in titles
 * http://en.blog.wordpress.com/2006/12/24/no-orphans-in-titles/
 *
 * @author nickmomrik
 */

function vip_allow_title_orphans() {
	remove_filter('the_title', 'widont');
}

/*
 * Only display related posts from own blog
 *
 * 1. Make sure Appearance -> Extras: 'Hide related links on this blog' is NOT checked
 * 2. Call vip_related_posts() in functions.php
 * 3. Call vip_display_related_posts() in the loop where you want them displayed
 *
 * $before and $after can be used to control the HTML that wraps around the entire related posts list
 * ex. vip_related_posts( '<div class="related-posts">', '</div>' );
 *
 * @author nickmomrik
 */

function vip_related_posts($before = '', $after = '') {
	remove_filter('the_content', 'sphere_inline');
	if ( !empty($before) ) add_filter('sphere_inline_before', create_function( '', 'return '.var_export( $before, true ).';') );
	if ( !empty($after) ) add_filter('sphere_inline_after', create_function( '', 'return '.var_export( $after, true ).';') );
}

function vip_display_related_posts( $limit_to_same_domain = true ) {
	echo sphere_inline('', $limit_to_same_domain);
}

/*
 * Retrieves an array of possibly related posts
 *
 * 1. Make sure Appearance -> Extras: 'Hide related links on this blog' is NOT checked
 * 2. Call vip_related_posts() in functions.php to prevent related links from being automatically displayed
 * 3. Use this function to get the related posts into an array.
 *
 * Returns an array of related posts:
 * array =>
 *     array
 *       'url' => string
 *       'title' => string
 *       'timestamp' => string (YYYY-MM-DD)
 *       'host' => string, ie 'blog.wordpress.com' 
 *       'source' => 
 *         array
 *           'sourcename' => string (site name)
 *           'sourceurl' => string (site url)
 *           'sourcetype' => string (site source: same_domain, wpcom, partners)
 *
 * @author yoavf
 */

function wpcom_vip_get_related_posts( $max_num = 5, $limit_to_same_domain = true ){
	$permalink = get_permalink();
	stats_extra( 'related_links', 'vip_get' );
 	return get_sphere_results( $permalink, $max_num, $limit_to_same_domain );
}

/**
 * Experimental: VIP Related posts using WordPress.com search, based on the content of the post
 * Use wpcom_vip_flaptor_related_posts() as a template tag.
 * @param int $max_num - maximum number of results you want (default: 5)
 * @param array $additional_stopwords - Stop words are common words in your content that you want to exclude from the search. Most common english words are ignored by default.
 * @param boolean $exclude_own_titles - Exclude words form the title and description of this blog in the query (default: true)
 * @return string Returns an HTML unordered list of related posts from the same blog.
 * IE:
 * <ul>
 * 	<li><a href="URL">Title</a></li>
 * 	<li><a href="URL">Title</a></li>
 * </ul>
*/

function wpcom_vip_flaptor_related_posts( $max_num = 5, $additional_stopwords = array(), $exclude_own_titles = true ){
 	return flaptor_related_inline( $max_num, $additional_stopwords, $exclude_own_titles );
}

/**
 * Experimental: VIP Related posts using WordPress.com search, based on the content of the post
 * Use wpcom_vip_get_flaptor_related_posts() if you prefer to get an array you can process.
 * @param int $max_num - maximum number of results you want (default: 5)
 * @param array $additional_stopwords - stopwords are common words in your content that you want to exclude from the search. Most common english words are ignored by default.
 * @param boolean $exclude_own_titles - Exclude words form the title and description of this blog in the query (default: true)
 * @return array of related posts, in the following structure:
 * array =>
 *     array
 *       'url' => string
 *       'title' => string
 *       'timestamp' => string (YYYY-MM-DD)
 *       'host' => string, ie 'blog.wordpress.com' 
 *       'source' => 
 *         array
 *           'sourcename' => string (site name)
 *           'sourceurl' => string (site url)
 *           'sourcetype' => string (site source: same_domain, wpcom, partners)
 *
*/

function wpcom_vip_get_flaptor_related_posts( $max_num = 5, $additional_stopwords = array(), $exclude_own_titles = true){
 	return get_flaptor_related( $max_num, $additional_stopwords, $exclude_own_titles );
}

/*
 * Allows users of contributor role to be able to upload media.
 * Contrib users still can't publish.
 * @author mdawaffe
 */

function vip_contrib_add_upload_cap() {
	add_action( 'init', '_vip_contrib_add_upload_cap');
}
function _vip_contrib_add_upload_cap() {
	global $wp_user_roles, $wp_roles, $current_user;

	if ( !is_admin() || !strpos($_SERVER['SERVER_NAME'], 'wordpress.com') )
		return; // only works on wp.com, not wp.org

	$wp_user_roles['contributor']['capabilities']['upload_files'] = true;
	$wp_roles = new WP_Roles;
	$id = $current_user->ID;
	unset( $GLOBALS['current_user'] );
	wp_set_current_user( $id );
}

/*
 * Un-hide the extra size and alignment options in the gallery tab of the media upload box
 * @author tellyworth
 */

function vip_admin_gallery_css_extras() {
	add_action('admin_print_styles', '_vip_admin_gallery_css_extras');
}
function _vip_admin_gallery_css_extras() {
?>
<style type="text/css">
#gallery-form tr.url, #gallery-form tr.align, #gallery-form tr.image-size { display: table-row; }
#gallery-form tr.submit input.button { display: table-row; }
</style>
<?php
}

/*
 * Do not display the images in enhanced feeds
 * @author nickmomrik
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
 * Remove the tracking bug added to all WordPress.com feeds
 */
function wpcom_vip_remove_feed_tracking_bug() {
	remove_filter( 'the_content', 'add_bug_to_feed', 100 );
	remove_filter( 'the_excerpt_rss', 'add_bug_to_feed', 100 );
}

/*
 * Override default colors of audio player. Colors specified in the shortcode still can override
 * @author nickmomrik
 */

function wpcom_vip_audio_player_colors( $colors ) {
	$default_colors = array("bg" => "0xf8f8f8", "leftbg" => "0xeeeeee", "lefticon" => "0x666666", "rightbg" => "0xcccccc", "rightbghover" => "0x999999", "righticon" => "0x666666", "righticonhover" => "0xffffff", "text" => "0x666666", "slider" => "0x666666", "track" => "0xFFFFFF", "border" => "0x666666", "loader" => "0x9FFFB8");

	add_filter('audio_player_default_colors', create_function( '', 'return '.var_export( array_merge($default_colors, $colors), true ).';') );
}

/*
 * Outputs the title of the most popular blog post
 * @author nickmomrik
 */

function wpcom_vip_top_post_title( $days = 2 ) {
		global $wpdb;
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

/*
 * Keeps category and tag links local to the blog instead of linking to http://en.wordpress.com/tags/
 */
function make_tags_local() {
	remove_filter( 'the_category', 'globalize_tags' );
	remove_filter( 'the_tags', 'globalize_taxonomy' );
	remove_filter( 'term_links-post_tag', 'globalize_taxonomy' );
}

/*
 * Returns the URL to an image resized and cropped to the given dimensions.
 * You can use this image URL directly -- it's cached and such by our servers.
 * Please use this function to generate the URL rather than doing it yourself as
 * this function uses staticize_subdomain() makes it serve off our CDN network.
 *
 * Somewhat contrary to the function's name, it can be used for ANY image URL, hosted by us or not.
 * So even though it says "remote", you can use it for attachments hosted by us, etc.
 *
 * $url = the raw URL to the image (URLs that redirect are currently not supported with the exception of http://foobar.wordpress.com/files/ type URLs)
 * $width = the desired width of the final image
 * $height = the desired height of the final image
 * $escape = if true, the URL will be escaped for direct HTML usage (& to &amp; for example). Set this to false if you need the raw URL.
 */
function wpcom_vip_get_resized_remote_image_url( $url, $width, $height, $escape = true ) {
	$width = (int) $width;
	$height = (int) $height;

	// ImgPress doesn't currently support redirects, so help it out by doing http://foobar.wordpress.com/files/ to http://foobar.files.wordpress.com/
	$url = new_file_urls( $url );

	// staticize_subdomain() converts the URL to use one of our CDN's (the main reason to use this function)
	// The "en" is there as staticize_subdomain() expects the passed URL to be something.wordpress.com
	$thumburl = staticize_subdomain( 'http://en.wordpress.com/imgpress?url=' . urlencode( $url ) . "&resize={$width},{$height}" );

	return ( $escape ) ? esc_url( $thumburl ) : $thumburl;
}

/*

Our Top Posts widget ( http://en.support.wordpress.com/widgets/top-posts-widget/ ) uses a display_top_posts() function to display a list of popular posts.
You can use this function in your themes. The function uses data from WordPress.com Stats ( http://en.support.wordpress.com/stats/ ) to generate the list.

The default parameters from the function definition are:

display_top_posts( $number = 10, $days = 2, $before_list = '', $after_list = '', $before_item = '', $after_item = '', $show_comment_count = false, $echo = true )

Notes:
 - The function will echo the output.
 - $days = how many days of stats should be used in the calculation.
   Ex. If you wanted to show the most popular posts for the last week you would use 7.
   The minimum # you can use is 2 because of the way days roll over is our stats.
 - Output is cached for 20 minutes. Each $number value uses a different cache.

If you would like more control over the output of display_top_posts() use the get_top_posts() function below and loop through the array.

get_top_posts() returns an array of post_ID -> views.

 - post_ID 0 is used to track home page views.
 - Posts and pages are included.
 - The array will contain 10 + $number elements.

*/

function get_top_posts( $number = 10, $days = 2 ) {
	global $wpdb;

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

/*
 * Prevent Youtube embeds in comments
 * Feature: http://en.support.wordpress.com/videos/youtube/#comment-embeds
 *
 * @author nickmomrik
 */

function wpcom_vip_disable_youtube_comment_embeds() {
	remove_filter( 'comment_text', 'youtube_link', 1 );
	remove_filter( 'comment_text', 'youtube_markup' );
}

/*
 * When using $content_width in a theme, Full size images are constrained to the width.
 * Use wpcom_vip_allow_full_size_images_for_real() to use actual full size images.
 *
 * @author nickmomrik
 */

function wpcom_vip_allow_full_size_images_for_real() {
	remove_filter( 'image_downsize', 'wpcom_resize', 10, 3 );
	add_filter( 'image_downsize', '_wpcom_vip_allow_full_size_images_for_real', 10, 3 );
}

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
		return wpcom_resize( $ignore, $id, $size );
	}
}

/*
 * Makes the smallest sized thumbnails (i.e. the ones used in [gallery]) be cropped.
 * We've removed the checkbox from Settings -> Media, so this re-enables the feature.
 */

function wpcom_vip_crop_small_thumbnail() {
	add_filter( 'pre_option_thumbnail_crop', create_function('', 'return 1;'), 11 ); // 11 = after our disable filter
}


/*
 * Do not display the Polldaddy rating. Usually used for a page or post where ratings are not wanted.
 *
 * @author nickmomrik
 */

function wpcom_vip_remove_polldaddy_rating() {
	remove_filter( 'the_content', 'polldaddy_show_rating', 5 );
	remove_filter( 'post_flair',  'polldaddy_show_rating', 10 );
}

/*
 * Forcably disables mShots, cannot be re-enabled via Appearance -> Extras with this function called
 */
function wpcom_vip_disable_mshots() {
	// Filter the option to disable regardless of it's true setting
	add_filter( 'pre_option_snap_anywhere', '_wpcom_vip_disable_mshots_option' );

	// Remove the mShots wrapper <div>
	wpcom_vip_remove_snap_preview_div();
}
function _wpcom_vip_disable_mshots_option() {
	return 'nosnap';
}

/*
 * Do not wrap post contents in a mShots/Snap <div>
 * You don't need this function if you are calling wpcom_vip_disable_mshots()
 */
function wpcom_vip_remove_snap_preview_div() {
	remove_filter( 'the_content', 'wrap_snap_div', 8888 );
}

/*
 * Removes the <media:content> tags from the RSS2 feed
 * You should really call this when creating a custom feed (best to leave them in your normal feed)
 * For details on creating a custom feed, see:
 * http://lobby.vip.wordpress.com/custom-made/altering-feeds/
 */
function wpcom_vip_remove_mediacontent_from_rss2_feed() {
	remove_action( 'rss2_item', 'mrss_item' );
}

/**
 * Disable post-post screen
 */
function wpcom_vip_disable_postpost() {
	remove_filter( 'redirect_post_location', 'wpcom_maybe_post_post' ); // v1
	add_filter( 'post_post_disable', '__return_true' ); // v2
}

/**
 * Outputs Open Graph tags to various pages on the site
 * http://developers.facebook.com/docs/opengraph/
 */

function wpcom_vip_enable_opengraph() {
	add_action( 'wp_head', 'wpcom_og_tags' ); // This hook is excluded from VIP sites, but if you see double output, it's because the hook's been added twice
}

/**
 * Allows you to customize the /via and follow recommendation for the WP.com Sharing Twitter button
 * @param $via (string) What the /via should be set to. Empty value disables the feature. 
 */
function wpcom_vip_sharing_twitter_via( $via = '' ) {
	if( empty( $via ) ) {
		$via_callback = '__return_false';
	/*
	} elseif( is_callable( $via ) ) {
		//$via_callback = $via;
	*/
	} else {
		// sanitize_key() without changing capitizalization
		$raw_via = $via;
		$via = preg_replace( '/[^A-Za-z0-9_\-]/', '', $via );
		$via = apply_filters( 'sanitize_key', $via, $raw_via );

		$via_callback = create_function( '', sprintf( 'return "%s";', $via ) );
	}

	add_filter( 'sharing_twitter_via', $via_callback );
}

/**
* Disables WPCOM Sharing in Posts and Pages
* Sharing can be disalbed in the dashboard, by removing all buttons
* from Enabled Services.
* 
* This function is primary for automating sharing when you have numerous
* sites to administer.  It also assists having consistent CSS containers b/w 
* development and production.
* 
* See also http://en.support.wordpress.com/sharing/
*/
function wpcom_vip_disable_sharing() {
	remove_filter( 'pre_post_flair', 'pre_post_flair' );
	remove_filter( 'post_flair', 'sharing_display', 20 );
	remove_filter( 'post_post_flair', 'post_post_flair' );
}
function wpcom_vip_enable_sharing() {
	add_filter( 'post_flair', 'sharing_display', 20 );
}

/**
* Disables WP.com Likes for Posts and Custom Post Types
* Sharing can be disabled from the Dashboard ( Settings > Sharing )
*
* This function is primarily for programmatic disabling of the feature,
* for example when working with custom post types
*/
function wpcom_vip_disable_likes() {
	$disable_function = function() {
		remove_filter( 'comments_template', 'wpl_filter_pre_comments' ); // legacy filter
		remove_filter( 'post_flair', 'wpl_filter_pre_comments', 30 );
	};

	// Post flair is initialized at priority 999, so we need to do this way after that if init hasn't been fired yet
	if ( did_action( 'init' ) )
		call_user_func( $disable_function );
	else
		add_action( 'template_redirect', $disable_function ); 
}
function wpcom_vip_enable_likes() {
	add_filter( 'post_flair', 'wpl_filter_pre_comments', 30 );
}

/**
 * Disables the OnSwipe theme for mobile browsers.
 * OnSwipe can also be disabled from the WordPress admin. This can function
 * automate disabling OnSwipe on a theme by theme basis, and will override the value
 * in the WordPress admin.
 */
 function wpcom_vip_disable_onswipe() {
 	add_filter( 'onswipe_is_disabled', '__return_true' );
 }

 /**
  * Whether or not the OnSwipe/PadPressed theme is active for a site
  * Active means the OnSwipe theme is loaded on the current pageview
  */
function wpcom_vip_is_onswipe_active() {
	if ( defined( 'PADPRESS_IS_ACTIVE' ) && true === PADPRESS_IS_ACTIVE )
		return true;
	return false;
}

/**
 * Reads a postmeta value directly from the master database.
 * This is not intended for front-end usage. This purpose of this function is to avoid
 * race conditions that could appear while the caches are primed.
 * A good scenario where this could be used is to ensure published posts are not syndicated multiple times by checking a postmeta flag that is set on syndication
 * 
 * Note: this looks complicated, but the intention was to use API functions rather than direct DB queries for upward compatibility.
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
 * This is not intended for front-end usage. This purpose of this function is to avoid
 * race conditions that could appear while the caches are primed.
 * A good scenario where this could be used is to ensure published posts are not syndicated multiple times by checking if a post with a certain meta value already exists.
 * @param string $meta_key postmeta key to query
 * @param string $meta_value postmeta value to check for
 * @param string $post_type post_type of the post to query
 * @param array $post_stati array of the post_stati that are supposed to be included
 * @param integer $limit amount of possible posts to receive. not more than 10
 * @return mixed Array with post objects or WP_Error
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
	if ( true <> $wpdb->srtm ) {
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
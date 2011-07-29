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
	remove_filter('add_to_feed', 'add_delicious_to_feed');
	remove_filter('add_to_feed', 'add_stumbleupon_to_feed');
	remove_filter('add_to_feed', 'add_digg_to_feed');
	remove_filter('add_to_feed', 'add_reddit_to_feed');
	remove_filter('add_to_feed', 'add_commentcount_to_feed');
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

	return ( $escape ) ? esc_attr( $thumburl ) : $thumburl;
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
	remove_filter( 'comment_text', 'youtube_link', 5 );
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


/**
 * Looks up the country by $ip address
 * 
 * @return string ISO 3166-1 alpha-2 country code: http://www.iso.org/iso/country_codes.htm
 * @return false if a country couldn't be found
 *
 * This Geo feature is being tested and is free for VIP right now. It may be a paid service in the future.
 */

function wpcom_vip_ip2country( $ip = '' ) {
	require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/plugins/lang-guess/class-guess-lang.php';

	if ( '' == $ip )
		$ip = $_SERVER['REMOTE_ADDR'];

	return ip2country_sql( addslashes( $ip ) );
}

/*
 * Do not display the Polldaddy rating. Usually used for a page or post where ratings are not wanted.
 *
 * @author nickmomrik
 */

function wpcom_vip_remove_polldaddy_rating() {
	remove_filter( 'the_content', 'polldaddy_show_rating', 5 );
	remove_filter( 'social_nascar', 'polldaddy_show_rating', 10 );
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
 * http://keepingtheirblogsgoing.wordpress.com/2011/07/29/on-disabling-post-post-i-think-its-a/
 */
function wpcom_vip_disable_postpost() {
	remove_filter( 'redirect_post_location', 'wpcom_maybe_post_post' );
}

?>

<?php
/*
 *	VIP Helper Functions that are specific to WordPress.com
 *
 * These functions relate to WordPress.com specific plugins, 
 * filters, and actions that are enabled across all of WordPress.com.
 *
 * To add these functions to your theme add
require_once( ABSPATH . 'wp-content/themes/vip/plugins/vip-helper-wpcom.php' );
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
	if ( !empty($before) ) add_filter('sphere_inline_before', returner($before));
	if ( !empty($after) ) add_filter('sphere_inline_after', returner($after));
}

function vip_display_related_posts( $limit_to_same_domain = true ) {
	echo sphere_inline('', $limit_to_same_domain);
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

	add_filter('audio_player_default_colors', returner(array_merge($default_colors, $colors)));
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
 * Returns the URL to an image resized and cropped to the given dimensions
 * You can use this image URL directly -- it's cached and such by our servers
 * Please use this function rather than the URL directly as staticize_subdomain() makes it serve off our CDN network
 *
 * $url = the image to the URL (URLs that redirect are currently not supported with the exception of http://blogname.wordpress.com/files/ type URLs)
 * $width = the desired width of the final image
 * $height = the desired height of the final image
 * $escape = if true, the URL will be escaped for direct HTML usage (& to &amp; for example). Set this to false if you need the raw URL.
 */
function wpcom_vip_get_resized_remote_image_url( $url, $width, $height, $escape = true ) {
	$width = (int) $width;
	$height = (int) $height;

	// ImgPress doesn't currently support redirects, so help it out by doing http://foobar.wordpress.com/files/ to http://foobar.files.wordpress.com/
	$url = new_file_urls( $url );

	// staticize_subdomain() converts the URL to a random one of our CDN's
	$thumburl = staticize_subdomain( 'http://en.wordpress.com/imgpress?url=' . urlencode( $url ) . "&resize={$width},{$height}" );

	return ( $escape ) ? esc_attr( $thumburl ) : $thumburl;
}

/*

Our Top Posts widget ( http://en.support.wordpress.com/widgets/top-posts-widget/ ) uses a display_top_posts() function to display a list of popular posts.
You can use this function in your themes. The function uses data from WordPress.com Stats ( http://en.support.wordpress.com/stats/ ) to generate the list.

The default parameters from the function definition are:

display_top_posts($number = 10, $days = 2, $before_list = '', $after_list = '', $before_item = '', $after_item = '', $show_comment_count = false)

Notes:
 - The function will echo the output.
 - $days = how many days of stats should be used in the calculation.
   Ex. If you wanted to show the most popular posts for the last week you would use 7.
   The minimum # you can use is 2 because of the way days roll over is our stats.
 - Output is cached for 20 minutes. Each $number value uses a different cache.

*/


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

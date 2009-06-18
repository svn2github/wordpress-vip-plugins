<?php
/*
 *	VIP Helper Functions that are specific to WordPress.com
 *
 * These functions relate to WordPress.com specific plugins, 
 * filters, and actions that are enabled across all of WordPress.com.
 *
 * To add these functions to  your theme add
include( ABSPATH . '/wp-content/themes/vip/plugins/vip-helper-wpcom.php' );
 * in the theme's 'functions.php'. This should be wrapped in a 
if ( function_exists('wpcom_is_vip') ) { // WPCOM specific
 * so you don't load it in your local environment. This will help alert you if
 * have any unconditional dependencies on the WordPress.com environment.
 */

/*
 * Disable the WordPress.com filter that prevents orphans in titles
 * http://en.blog.wordpress.com/2006/12/24/no-orphans-in-titles/
 *
 * @author mtdewvirus
 */
function vip_allow_title_orphans() {
	remove_filter('the_title', 'widont');
}

/*
 * Only display related posts from own blog
 * make sure 'Hide related links on this blog' option at Appearance->Extras is not checked
 * Place vip_related_posts in functions.php and vip_display_related_posts in the loop
 *
 * @author mtdewvirus
 */
function vip_related_posts($before = '', $after = '') {
	remove_filter('the_content', 'sphere_inline');
	if ( !empty($before) ) add_filter('sphere_inline_before', returner($before));
	if ( !empty($after) ) add_filter('sphere_inline_after', returner($after));
}

function vip_display_related_posts( $limit_to_same_domain = true ) {
	echo sphere_inline('', $limit_to_same_domain);
}

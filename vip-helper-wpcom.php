<?php
/*
	VIP Helper Functions that are specific to WordPress.com
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

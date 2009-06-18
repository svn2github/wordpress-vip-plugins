<?php
/*
	VIP Helper Functions

	Put include(ABSPATH . '/wp-content/themes/vip/plugins/vip-helper.php'); in the theme's functions.php to use this
 */

/*
 * Simple 301 redirects
 * array elements should be in the form of:
 * '/old' => 'http://wordpress.com/new/'
 *
 * @author mtdewvirus
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

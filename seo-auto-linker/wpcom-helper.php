<?php
/**
 * Restrict the number of links that can be added to the blacklist
 * because we don't want to blow up options
 */
add_filter( 'seoal_blacklist_max', 'wpcom_vip_seoal_blacklist_max' );
function wpcom_vip_seoal_blacklist_max( $orig ) {
	return 30;
}
<?php
/**
 * Because the guest authors functionality is work in progress,
 * only enable it for specific VIP themes
 */
$coauthors_plus_guest_authors_whitelist = array(
		'vip/newyorkobserver',
	);
if ( !in_array( get_option( 'stylesheet' ), $coauthors_plus_guest_authors_whitelist ) )
	add_filter( 'coauthors_guest_authors_enabled', '__return_false' );
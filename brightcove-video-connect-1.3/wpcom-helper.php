<?php

/*
 * WordPress.com has a 50 MB upload limit on admin-ajax.php.
 * We need to switch Brightcove to use admin-post.php to allow larger file uploads.
 */

// Fixes for includes/class-bc-setup.php
add_filter( 'admin_url', 'vip_brightcove_admin_post_url', 10, 3 );
function vip_brightcove_admin_post_url( $url, $path, $blog_id ) {
	if ( 'admin-ajax.php?action=bc_media_upload' === $path ) {
		$url = str_replace( 'admin-ajax.php?action=bc_media_upload', 'admin-post.php?action=bc_media_upload', $url );
	}

	return $url;
}

// Fixes for includes/admin/api/class-bc-admin-media-api.php
add_action( 'init', 'vip_brightcove_admin_post_upload', 15 );
function vip_brightcove_admin_post_upload() {
	if ( class_exists( 'BC_Admin_Media_API' ) ) {
		$admin_media_api = new BC_Admin_Media_API();
		add_action( 'admin_post_bc_media_upload', array( $admin_media_api, 'brightcove_media_upload' ) );
	}
}

// Fixes for brightcove-video-connect.php
global $pagenow;
if ( in_array( $pagenow, array( 'admin-post.php' ), true ) ) {
	add_action( 'init', array( 'BC_Setup', 'action_init' ) );
	add_action( 'init', array( 'BC_Setup', 'bc_check_minimum_wp_version' ) );
}
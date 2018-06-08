<?php
global $pagenow;

if ( in_array( $pagenow, array( 'admin-post.php' ) ) ) {
	add_action( 'init', 'vip_brightcove_wpcom_helper_action_init', 20 );
	add_action( 'init', array( 'BC_Setup', 'action_init' ) );
	add_action( 'init', array( 'BC_Setup', 'bc_check_minimum_wp_version' ) );
}

function vip_brightcove_wpcom_helper_action_init() {
	if ( BC_Utility::current_user_can_brightcove() ) {
		$admin_media_api = new BC_Admin_Media_API();
		add_action( 'admin_post_bc_media_upload', array( $admin_media_api, 'brightcove_media_upload' ) ); // For uploading a file.
	}
}

add_filter( 'admin_url', 'vip_brightcove_wpcom_helper_filter_admin_url', 10, 3 );
function vip_brightcove_wpcom_helper_filter_admin_url( $url, $path, $blog_id ) {
	$url = str_replace( 'admin-ajax.php?action=bc_media_upload', 'admin-post.php?action=bc_media_upload', $url );
	return $url;
}
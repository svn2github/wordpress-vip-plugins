<?php

// XML Client only for a select few for now
add_filter( 'syn_transports', function( $transports ) {
	if ( false === WPCOM_IS_VIP_ENV )
		return $transports;

	if ( ! in_array( parse_url( site_url(), PHP_URL_HOST ), array( 'instylemobile.wordpress.com', 'ewmobile.wordpress.com' ) ) )
		unset( $transports['WP_XML'] );

	return $transports;
} );

add_action( 'syn_post_pull_edit_post', function() {
	wpcom_push_syndication_stats( 'vip-syndication-pull', 'edit' );
} );
add_action( 'syn_post_pull_new_post', function() {
	wpcom_push_syndication_stats( 'vip-syndication-pull', 'new' );
} );
add_action( 'syn_post_push_delete_post', function() {
	wpcom_push_syndication_stats( 'vip-syndication-push', 'delete' );
} );
add_action( 'syn_post_push_edit_post', function() {
	wpcom_push_syndication_stats( 'vip-syndication-push', 'edit' );
} );
add_action( 'syn_post_push_new_post', function() {
	wpcom_push_syndication_stats( 'vip-syndication-push', 'new' );
} );

function wpcom_push_syndication_stats( $stat, $action ) {
	if ( function_exists( 'bump_stats_extras' ) ) {
		bump_stats_extras( $stat, $action );
	}	
}

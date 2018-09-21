<?php
add_action( 'syn_after_init_server', function() {
	if ( true !== WPCOM_IS_VIP_ENV )
		return;

	global $push_syndication_server;

	// Override the plugin's default wp-cron based push handling and use the WP.com jobs system instead
	remove_action( 'syn_schedule_push_content', array( $push_syndication_server, 'schedule_push_content' ), 10 );
	add_action( 'syn_schedule_push_content', function( $post_id, $sites ) {
		$data = new stdClass;
		$data->post_id = $post_id;
		$job_id = queue_async_job( $data, 'vip_async_syndication_push_post' );

		xmpp_message( 'batmoo@im.wordpress.com', '[syn_schedule_push_content] job id for pushing post #' . $post_id . ' ('. home_url() .'): ' . $job_id );
	}, 10, 2 );

	// TODO: override schedule_delete_content and schedule_pull_content

} );

// Failure notifications
add_action( 'syn_post_push_new_post', 'wpcom_vip_push_syndication_debug', 10, 6 );
add_action( 'syn_post_push_edit_post', 'wpcom_vip_push_syndication_debug', 10, 6 );

function wpcom_vip_push_syndication_debug( $result, $post_id, $site, $transport_type, $client, $info ) {

	if ( function_exists( 'has_blog_sticker' ) && has_blog_sticker( 'vip-client-cbs-local' ) ) {
		$info = [
			'result'         => $result,
			'post_id'        => $post_id,
			'site'           => $site,
			'transport_type' => $transport_type,
			'client'         => $client,
			'info'           => $info,
			'timestamp_gmt'      => current_time( 'Y-m-d H:i:s', true ),
			'timestamp_local'      => current_time( 'Y-m-d H:i:s', false ),
		];
		
		$bname = 'CBS Syn Watcher';
		
		$result = is_wp_error( $result ) ? 'SYNDICATION FAIL: ' : 'SYNDICATION SUCCESS:';
		$msg = wp_json_encode( $info, JSON_PRETTY_PRINT ) . "\nJSON status: " . json_last_error_msg() . "\n" . var_export( $info, true );
		
		a8c_slack( '#vip-client-cbs-logs',  $result . $msg, $bname );
		if ( function_exists( 'wp_debug_mail' ) ) {
			wp_debug_mail( 'rinat+wpcomdebug@automattic.com', $result, $msg, [], 60 );
		}
	}
}

// === Stats ===
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

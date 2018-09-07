<?php
add_action( 'syn_after_init_server', function() {
	if ( true !== WPCOM_IS_VIP_ENV )
		return;

	global $push_syndication_server;

	// Override the plugin's default wp-cron based push handling and use the WP.com jobs system instead
	remove_action( 'syn_schedule_push_content', array( $push_syndication_server, 'schedule_push_content' ), 10 );
	add_action( 'syn_schedule_push_content', function( $post_id, $sites ) {

		/**
		 * Wait for shutdown before queuing the post syndication.
		 * A lot of things may happen after `transition_post_status` which is where `syn_schedule_push_content` is triggered from.
		 */
		add_action( 'shutdown', function () use ( $post_id ) {

			$data          = new stdClass;
			$data->post_id = $post_id;
			queue_async_job( $data, 'vip_async_syndication_push_post' );

		} );

	}, 10, 2 );

	// TODO: override schedule_delete_content and schedule_pull_content

} );


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

<?php
// This file is only used on WP.com for custom code and filters

// We need to manually schedule the sync event since we don't run the activation and deactivation filters on WordPress.com
add_action( 'init', 'wpcom_livefyre_schedule_sync' );

function wpcom_livefyre_schedule_sync() {
	global $livefyre;

	if ( ! wp_next_scheduled( 'livefyre_sync' ) )
		$livefyre->AppExtension->schedule_sync( LF_SYNC_LONG_TIMEOUT );
}

<?php

// ajax calls for the jwplayer plugin
// utlizes proxy.php
add_action( 'wp_ajax_jwp_api_proxy', function() {
	if ( isset( $_GET['method'] ) ) {//input var okay
		if ( 'upload_ready' === sanitize_text_field( wp_unslash( $_GET['method'] ) ) ) { // Input var okay
			echo '{"status" : "ok"}';
		} else {
			jwplayer_ajax_jwp_api_proxy();
		}
	}
	wp_die();
} );

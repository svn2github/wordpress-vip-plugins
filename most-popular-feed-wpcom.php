<?php

// This file is now located in the "most-popular-feed-wpcom" folder

if ( function_exists( 'wpcom_vip_load_plugin' ) )
	wpcom_vip_load_plugin( 'most-popular-feed-wpcom' );
else
	include_once( WP_CONTENT_DIR . '/themes/vip/plugins/most-popular-feed-wpcom/most-popular-feed-wpcom.php' );

?>
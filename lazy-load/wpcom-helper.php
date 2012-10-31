<?php

add_filter( 'lazyload_is_enabled', 'wpcom_vip_disable_lazyload_on_mobile' );

function wpcom_vip_disable_lazyload_on_mobile( $enabled ) {
	if ( function_exists( 'jetpack_is_mobile' ) && jetpack_is_mobile() )
		$enabled = false;

	return $enabled;
} 

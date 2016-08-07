<?php

// Since _get_options is a private method, we have this custom helper.
function wpcom_wga_get_tracking_code() {
	$options = get_option( 'wga', array() );

	if ( isset( $options['code'] )
		&& preg_match( '#UA-[\d-]+#', $options['code'], $matches ) ) {
			return $options['code'];
	}

	return false;
}

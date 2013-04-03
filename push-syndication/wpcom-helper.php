<?php

// Stub to prevent breakages in case others are extending this class
class WP_RSS_Client extends Syndication_WP_RSS_Client {}

// XML Client only for a select few for now
add_filter( 'syn_transports', function( $transports ) {
	if ( ! in_array( parse_url( site_url(), PHP_URL_HOST ), array( 'instylemobile.wordpress.com' ) ) )
		unset( $transports['WP_XML'] );
	return $transports;
} );


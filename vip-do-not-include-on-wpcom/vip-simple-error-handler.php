<?php

if ( !function_exists( 'vip_simple_error_handler' ) ):
/**
 * Error handler that doesn't print the call location
 * 
 * @param int $errno Error number
 * @param String $errstr Error message
 */
function vip_simple_error_handler( $errno, $errstr ) {

	// This error code is not included in error_reporting
	if ( ! ( error_reporting() & $errno ) )
		return;

	switch( $errno ) {
		case E_USER_WARNING:
			echo "<p><strong>Warning</strong>: $errstr</p>";
			exit( $errno );
			break;

		case E_USER_ERROR:
			echo "<p><strong>Error</strong>: $errstr</p>";
			exit( $errno );
			break;

		case E_USER_DEPRECATED:
			echo "<p><strong>Deprecated</strong>: $errstr</p>";
			break;

		case E_USER_NOTICE:
			echo "<p><strong>Notice</strong>: $errstr</p>";
			break;

		default:
			echo "<p><strong>Unknown Error</strong>: $errstr</p>";
	}	

	// Don't execute PHP internal error handler
	return true;
}
endif;

<?php

// This file is here for legacy reasons. Please install the Jetpack plugin (http://jetpack.me) instead.

if ( ! function_exists( 'jetpack_is_mobile' ) ):

$bt = debug_backtrace();
$parent = $bt[0];

$file = $parent['file'];
$line = $parent['line'];

switch ( $parent['function'] ) {
	case 'require':
	case 'require_once':
		$action = "Required";
		break;

	case 'include':
	case 'include_once':
	default:
		$action = "Included";
		break;
}

// Build the error message
$error = sprintf( __( '%s <code>is-mobile.php</code> in <strong>%s</strong> on line <strong>%d</strong>. Please install the Jetpack plugin (<a href="http://jetpack.me">http://jetpack.me</a>).' ), $action, $file, $line );

// Error handler that doesn't print the call location
// which is just confusing in this case
require_once 'vip-simple-error-handler.php';
set_error_handler( 'vip_simple_error_handler' );
trigger_error( $error, E_USER_DEPRECATED );
restore_error_handler();

endif;

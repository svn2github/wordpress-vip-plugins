<?php
// If uninstall is not initiated from within WordPress then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// Set option name string
$option_name = 'grabpress_key';
$option_name2 = 'grabpress_verify_wp_clicked';

// Remove GrabPress API key from WPDB
delete_option( $option_name );
delete_option( $option_name2 );
?>
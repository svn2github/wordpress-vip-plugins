<?php
/*
 * Plugin Name: Throttle Media Modal Search Queries
 * Plugin URI:  https://github.com/Automattic/throttle-media-search-queries
 * Description: Requires editors to hit enter before a media search from a media modal on post edit screen is triggerred
 * Version:     0.0.1
 * Author:      Automattic
 * Author URI:  https://automattic.com/
 * License:     GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tmmsq
*/

function tmmsq_enqueu_scripts( $screen ) {
	if ( 'post-new.php' !== $screen && 'post.php' !== $screen ) {
		return;
	}
	wp_register_script( 'tmmsq_script', plugin_dir_url( __FILE__ ) . 'js/script.js', array( 'media-views' ), '0.0.1', true );
	wp_enqueue_script( 'tmmsq_script' );
}

add_action( 'admin_enqueue_scripts', 'tmmsq_enqueu_scripts', 10, 1 );

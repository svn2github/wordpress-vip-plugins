<?php


/*
 * Security check
 * Exit if file accessed directly.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PlaybuzzQuicktags {

	public $name = 'playbuzz';

	function __construct() {
		add_action( 'admin_print_scripts', array( $this, 'init' ) );
	}

	function init() {
		global $wp_version;

		// Check WordPress Version (We need WordPress 3.3 to use QTag 4.0)
		if ( $wp_version < 3.3 ) {
			return;
		}

		// Check if the user has editing privilege
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		wp_enqueue_script(
			'my_custom_quicktags',
			plugins_url( 'js/pb-quicktags.js', __FILE__ ),
			array( 'quicktags' )
		);
	}
}


new PlaybuzzQuicktags();



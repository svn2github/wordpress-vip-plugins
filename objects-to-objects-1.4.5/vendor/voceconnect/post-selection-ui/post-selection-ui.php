<?php

if ( defined( 'ABSPATH' ) && function_exists( 'add_action' ) ) {
	if ( !has_action( 'init', array( 'Post_Selection_UI', 'init' ) ) ) {
		add_action( 'init', array( 'Post_Selection_UI', 'init' ) );
	}
}

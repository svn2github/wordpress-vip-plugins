<?php

/*
Plugin Name: Email Post Changes
Description: Whenever a change to a post or page is made, those changes are emailed to the blog's admin.
Plugin URI: http://wordpress.org/extend/plugins/email-post-changes/
Version: 0.6
Author: Michael D Adams
Author URI: http://blogwaffe.com/
*/

require_once 'class.email-post-changes.php';

add_action( 'init', array( 'Email_Post_Changes', 'init' ) );

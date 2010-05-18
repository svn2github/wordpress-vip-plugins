<?php

/*
Plugin Name: Email Custom DNS Changes
Description: Whenever a change to Custom DNS Settingsis made, those changes are emailed to VIP Support
Version: 0.6
Author: Michael D Adams
Author URI: http://blogwaffe.com/
*/

require_once 'class.email-post-changes.php';

class Email_Custom_DNS_Changes extends Email_Post_Changes {

	function &init() {
		static $instance = null;

		if ( $instance )
			return $instance;

		$class = __CLASS__;
		$instance = new $class;
	}

	function __construct() {
		parent::__construct();

		$this->defaults = array(
			'emails' => array( 'vip-support@wordpress.com' ),
			'post_types' => array( 'custom_dns' )
		);
	}

	function get_options( $just_defaults = false ) {
		return $this->defaults;
	}

	/* Admin */
	function admin_menu() {}
}

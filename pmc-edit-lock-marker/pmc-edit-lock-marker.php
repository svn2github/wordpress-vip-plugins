<?php
/*
Plugin Name: PMC Edit Lock Marker
Plugin URI: http://pmc.com/
Description: A plugin to mark the posts on wp-admin/edit.php page which are currently being edited by other users
Version: 1.0
Author: Amit Gupta
Author URI: http://pmc.com/
*/


//load plugin class
require_once("class-pmc-edit-lock-marker.php");

//hook up the loader function to init
add_action('init', 'pmc_edit_lock_marker_loader');

function pmc_edit_lock_marker_loader() {
	if( ! isset($GLOBALS['pmc_edit_lock_marker']) || empty($GLOBALS['pmc_edit_lock_marker']) ) {
		//init class
		$GLOBALS['pmc_edit_lock_marker'] = PMC_Edit_Lock_Marker::get_instance();
	}
}


//EOF
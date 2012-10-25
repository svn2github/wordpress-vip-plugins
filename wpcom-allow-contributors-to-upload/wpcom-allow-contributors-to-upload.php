<?php
/*
Plugin Name: WordPress.com Allow Contributors to Upload
Plugin URI: http://automattic.com
Description: Allow contributors to upload media. They still won't be able to publish.
Author: Automattic
Version: 1.0
Author URI: http://automattic.com
*/

if ( function_exists( 'vip_contrib_add_upload_cap' ) )
	vip_contrib_add_upload_cap();
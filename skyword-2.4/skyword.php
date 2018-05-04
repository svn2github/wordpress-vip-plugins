<?php 
/*
Plugin Name: Skyword
Plugin URI: http://www.skyword.com
Description: Integration with the Skyword content publication platform.
Version: 2.4.5
Author: Skyword, Inc.
Author URI: http://www.skyword.com
License: GPL2
*/

/*  Copyright 2017  Skyword, Inc.     This program is free software; you can redistribute it and/or modify    it under the terms of the GNU General Public License, version 2, as    published by the Free Software Foundation.     This program is distributed in the hope that it will be useful,    but WITHOUT ANY WARRANTY; without even the implied warranty of    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the    GNU General Public License for more details.     You should have received a copy of the GNU General Public License    along with this program; if not, write to the Free Software    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA */

if ( !defined('SKYWORD_PATH') )
	define( 'SKYWORD_PATH', plugin_dir_path( __FILE__ ) );
if ( !defined('SKYWORD_VERSION') )
	define( 'SKYWORD_VERSION', "2.4.5" );
if ( !defined('SKYWORD_VN') )
	define( 'SKYWORD_VN', "2.45" ); //This CANNOT have two decimal places.
//.1.4 is NOT valid.

register_activation_hook(__FILE__, 'get_skyword_defaults');

// Set defaults on initial plugin activation
function get_skyword_defaults() {
	$tmp = get_option('skyword_plugin_options');
    if(!is_array($tmp)) {
		$arr = array(
		"skyword_api_key"=>null, 
		"skyword_enable_ogtags" => true, 
		"skyword_enable_metatags" => true, 
		"skyword_enable_googlenewstag" => true,
		"skyword_enable_pagetitle" => true,
		"skyword_enable_sitemaps" => true,
		"skyword_generate_all_sitemaps" => true,
		"skyword_generate_news_sitemaps" => true,
		"skyword_generate_pages_sitemaps" => true,
		"skyword_generate_categories_sitemaps" => true,
		"skyword_generate_tags_sitemaps" => true,
		"skyword_generate_new_users_automatically" => true
		);
		update_option('skyword_plugin_options', $arr);
	}
}

require SKYWORD_PATH.'php/class-skyword-publish.php';
require SKYWORD_PATH.'php/class-skyword-sitemaps.php';
require SKYWORD_PATH.'php/class-skyword-shortcode.php';
require SKYWORD_PATH.'php/class-skyword-opengraph.php';
require SKYWORD_PATH.'php/options.php';

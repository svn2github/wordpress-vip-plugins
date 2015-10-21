<?php
/*
Plugin Name: Tinypass
Plugin URI: http://tinypass.com
Description: Plugin for integration with Tinypass VX and Tinypass GO
Version: 4.0.0
Author: Tinypass
Author URI: http://www.tinypass.com
License: GPL2
*/

/*
Copyright 2015  Tinypass, Inc.  (support@tinypass.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


define( 'TINYPASS_PLUGIN_VERSION', '4.0.0' );

// We don't support upgrading from legacy, therefore it should stay the same
define( 'TINYPASS_PLUGIN_FILE_PATH', dirname( __FILE__ ) . '/tinypass.php' );
require_once( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'include.php' ); // Registers autoloader
// Start the plugin
new WPTinypass;



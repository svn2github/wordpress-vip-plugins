<?php
/**
 * @package Qmerce
 */
/*
Plugin Name: Apester Interactive Content
Plugin URI: http://apester.com/
Description: The Apester Interactive Content plugin allows anyone to easily and freely create, embed and share interactive, playful and related content items (polls, trivia, etc.) into posts and articles, in a matter of seconds.
If you wish for better engagement, virality, circulation, native advertisement campaigns and monetization results, you came to the right place!
Version: 2.0.16
Author: Apester
Author URI: http://apester.com/
License: GPLv2 or later
Text Domain: apester
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

define( 'QMERCE_MINIMUM_WORDPRESS_VERSION', '2.8' );
define( 'QMERCE_VERSION', '2.0.16' );
define( 'QMERCE_SDK_VERSION', 'v2.0' );
// For dev: define( 'QMERCE_SDK_VERSION', 'dev' );
define( 'QMERCE_INTERACTION_BASEURL', 'http://interaction.qmerce.com' );
define( 'QMERCE_EDITOR_BASEURL', '//editor.qmerce.com' );
define( 'APESTER_EDITOR_BASEURL', '//app.apester.com' );
define( 'QMERCE_USER_SERVICE', 'http://users.qmerce.com' );
define( 'QMERCE_RENDERER_BASEURL', '//renderer.qmerce.com' );
define( 'QMERCE_RANDOM_BASEURL', '//random.qmerce.com' );
define( 'QMERCE_STATIC_BASEURL', '//d9etzk30b05yg.cloudfront.net/js/sdk' );
define( 'QMERCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'QMERCE_PLUGIN_DIR_RELATIVE', plugin_dir_url(__FILE__) );
define( 'QMERCE__PLUGIN_FILE', __FILE__ );
define( 'APESTER_FONT_URL', 'https://d1azc1qln24ryf.cloudfront.net/124741/ApesterWordpress/style-cf.css?n8f3hf' );
define( 'LORA_FONT_URL', 'https://fonts.googleapis.com/css?family=Lora:400,400i' );

if ( !function_exists( 'add_action') ) {
    throw new Exception( 'Can\'t call plugin directly' );
}

if ( is_admin() ) {
    require_once( QMERCE_PLUGIN_DIR . 'inc/qmerce-settings.class.php' );
    require_once( QMERCE_PLUGIN_DIR . 'inc/qmerce-admin-box.class.php' );
}

require_once( QMERCE_PLUGIN_DIR . 'inc/qmerce-widget.php' );
include_once( QMERCE_PLUGIN_DIR . 'inc/qmerce-options.php' );
require_once( QMERCE_PLUGIN_DIR . 'inc/qmerce-automation.php' );
require_once( QMERCE_PLUGIN_DIR . 'inc/qmerce-tag-composer.php' );
require_once( QMERCE_PLUGIN_DIR . 'inc/qmerce-shortcodes.php' );
include_once( QMERCE_PLUGIN_DIR . 'inc/tinymce.php' );        // Add TinyMCE plugin
include_once( QMERCE_PLUGIN_DIR . 'inc/events.php' );

add_action( 'wp_enqueue_scripts', 'qmerce_add_sdk_for_shortcode' );
add_action('widgets_init', 'qmerce_register_widgets');
add_filter('the_content', array(new QmerceAutomation(), 'renderHtml'));


add_filter( 'script_loader_tag', 'add_async_attribute', 10, 2 );

function add_async_attribute($tag, $handle) {
    if ( 'qmerce_js_sdk' !== $handle )
        return $tag;
    return str_replace( ' src', ' async="async" src', $tag );
}

function qmerce_add_sdk_for_shortcode( ) {
    $configuration = array(
        'rendererBaseUrl' => QMERCE_RENDERER_BASEURL,
        'randomBaseUrl' => QMERCE_RANDOM_BASEURL
    );
    wp_register_script( 'qmerce_js_sdk', QMERCE_STATIC_BASEURL . '/' . QMERCE_SDK_VERSION . '/apester-javascript-sdk.min.js' );
    wp_enqueue_script( 'qmerce_js_sdk' );
    wp_localize_script( 'qmerce_js_sdk', 'configuration', $configuration);
}

function qmerce_register_widgets() {
    register_widget( 'QmerceWidget' );
}

<?php
/*
	Plugin Name: Anvato Video Plugin
	Plugin URI: http://www.anvato.com/
	Description: This plugin allows a WordPress user to browse the Anvato Media Content Platform (MCP), choose a video and auto generate a shortcode to embed video into the post.
	Version: 1.1.2
	Author: Anvato
	Author URI: http://www.anvato.com/
*/
/*
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/


/**
 * Declare the necessary defines
 * Make sure to only declare them if they do not already exist,
 * just in case these are already declared custom
*/
if ( !defined('ANVATO_PATH') )          define( 'ANVATO_PATH', dirname( __FILE__ ) );
if ( !defined('ANVATO_URL') )           define( 'ANVATO_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );
if ( !defined('ANVATO_DOMAIN_SLUG') )   define( 'ANVATO_DOMAIN_SLUG',  "wp_anvato" );

require_once ANVATO_PATH . '/lib/class-anvato-settings.php';
require_once ANVATO_PATH . '/lib/class-anvato-library.php';

if ( !is_admin() ) {
	require_once ANVATO_PATH . '/lib/shortcode.php';
} else {
	require_once ANVATO_PATH . '/mexp/load.php';
}

// Google AMP filter handler
add_action( 'amp_content_embed_handlers', function( $list_of_embeds ) {
	if ( empty( $list_of_embeds) ) {
		$list_of_embeds = array();
	}

	require_once( ANVATO_PATH . '/exports/class-amp-anvplayer-embed.php' );
	$list_of_embeds['ANVATO_AMP_Anvplayer_Embed_Handler'] = array();

	return $list_of_embeds;
});

// Facebook Instant Articles handler
add_filter( 'feed_content_type', function( $content_type, $type ){
	if ( defined('INSTANT_ARTICLES_SLUG') && $type === INSTANT_ARTICLES_SLUG ) {
		require_once ANVATO_PATH . '/exports/fia-anvplayer-embed.php';
	}
	return $content_type;
}, 10, 2 );

// Setup shortcode handle for FIA
if ( is_admin() ) {
	add_action( 'instant_articles_before_transform_post', function ( $this_obj ) {
/*
	Issue:
		Facebook Instant Articles generates post cache on post save.
		In this case, since the proper FIA AnvatoPlayer is not avaiable, shortcode is not handled.

	Solution:
		Check for the "instant_articles_before_transform_post", 
		which is called right before save,
		and init dedicated Anvato Facebook Instant Articles shortcode.
*/

	if ( !function_exists( 'anvato_shortcode_get_parameters' ) ) {
		/*
			because "shortcode" is not included for Admin
			but it needs to be included to get shortcode options
		*/
		require_once ANVATO_PATH . '/lib/shortcode.php';
	}
	require_once ANVATO_PATH . '/exports/fia-anvplayer-embed.php';

	}, 10, 1 );
} // is_admin - instant_articles_before_transform_post

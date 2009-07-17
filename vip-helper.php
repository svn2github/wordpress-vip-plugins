<?php
/*
 * VIP Helper Functions
 * 
 * These functions can all be used in your local WordPress environment.
 *
 *	Add 
include( ABSPATH . '/wp-content/themes/vip/plugins/vip-helper.php' );
 * in the theme's functions.php to use this
 */

/*
 * Simple 301 redirects
 * array elements should be in the form of:
 * '/old' => 'http://wordpress.com/new/'
 *
 * @author mtdewvirus
 */

function vip_redirects( $vip_redirects_array = array() ) {
	$uri = $_SERVER['REQUEST_URI'];

	foreach( (array) $vip_redirects_array as $orig => $new ) {
		if ( $orig == untrailingslashit($uri) ) {
			wp_redirect($new, 301);
			exit;
		}
	}
}

/*
 * PHP func file_get_contents() w/ WP_CACHE integration
 * @author based on code by CNN
 */
function vip_wp_file_get_content( $url, $echo_content = true ) {
        $key = md5( $url );
        if ( $out = wp_cache_get( $key , 'vip') ) {
                if ( $echo_content ) {
                        echo $out;
                        return;
                } else
                        return $out;
        }

        $page = @file_get_contents( $url );
        wp_cache_set( $key, $page, 'vip', 600 );

        if ( $echo_content ) {
                echo $page;
                return;
        } else
                return $page;
}

/*
 * Disable tag suggest on post screen
 * @author mdawaffe
 */
function vip_disable_tag_suggest() {
        add_action( 'admin_init', '_vip_disable_tag_suggest' );
}
function _vip_disable_tag_suggest() {
       if ( !@constant( 'DOING_AJAX' ) || empty( $_GET['action'] ) || 'ajax-tag-search' != $_GET['action'] )
               return;
       die( '' );
}

/*
 * Disable autosave
 * @author mdawaffe
 */

function disable_autosave() {
        add_action( 'init', '_disable_autosave' );
}
function _disable_autosave() {
        wp_deregister_script( 'autosave' );
}

/*
 * Redirect http://blog.wordpress.com/feed/ to $target URL
 * ex. vip_main_feed_redirect( 'http://feeds.feedburner.com/ourfeeds/thefeed' );
 * @author lloydbudd
 */
function vip_main_feed_redirect( $target ) {
        define('FEEDURL', '#^/(wp-(rdf|rss|rss2|atom|rssfeed).php|index.xml|feed)/?$#i');
        $request = $_SERVER['REQUEST_URI'];
        $agent = $_SERVER['HTTP_USER_AGENT'];

        if ( preg_match( FEEDURL, $request ) || '/feed' == $request ) {
                if ( !preg_match( '#feedburner|feedvalidator|MediafedMetrics#i', $agent ) ) {
                        wp_redirect( $target, '302' );
                        die;
                }
        }
}

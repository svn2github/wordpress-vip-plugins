<?php
/**
 * Plugin Name: WPCOM Legacy Redirector
 * Description: Simple plugin for handling legacy redirects in a scalable manner. This is a no-frills plugin (no UI, for example).
 * 
 * Please contact us before using this plugin.
 */

class WPCOM_Legacy_Redirector {
	const POST_TYPE = 'vip-legacy-redirect';

	static function start() {
		add_action( 'init', array( __CLASS__, 'init' ) );
		add_filter( 'template_redirect', array( __CLASS__, 'maybe_do_redirect' ), 0 ); // hook in early, before the canonical redirect
	}
	
	static function init() {
		register_post_type( self::POST_TYPE, array(
			'public' => false,
		) );
	}

	static function maybe_do_redirect() {
		// Avoid the overhead of running this on every single pageload.
		// We move the overhead to the 404 page but the trade-off for site performance is worth it.
		if ( ! is_404() )
			return;

		$request_path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
		if ( $request_path ) {
			$redirect_uri = self::get_redirect_uri( $request_path );

			if ( $redirect_uri ) {
				header( 'X-legacy-redirect: HIT' );
				wp_safe_redirect( $redirect_uri, 301 );
				exit;
			}
		}
	}

	static function insert_legacy_redirect( $url, $post_id ) {
		// We need pre-slashed URLs
		if ( 0 !== strpos( $url, '/' ) )
			$url = '/' . $url;

		$url_hash = self::get_url_hash( $url );
		wp_insert_post( array(
			'post_parent' => $post_id,
			'post_name' => $url_hash,
			'post_title' => $url,
			'post_type' => self::POST_TYPE,
		) );

		wp_cache_delete( $url_hash, self::POST_TYPE );
	}

	static function get_redirect_uri( $url ) {
		global $wpdb;
		
		$url = urldecode( $url );
		$url_hash = self::get_url_hash( $url );

		$redirect_post_id = wp_cache_get( $url_hash, self::POST_TYPE );

		if ( false === $redirect_post_id ) {
			$redirect_post_id = self::get_redirect_post_id( $url );
			wp_cache_add( $url_hash, $redirect_post_id, self::POST_TYPE );
		}

		if ( $redirect_post_id )
			return get_permalink( $redirect_post_id );
		return false;
	}

	static function get_redirect_post_id( $url ) {
		global $wpdb;

		$url_hash = self::get_url_hash( $url );

		$redirect_post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_parent FROM $wpdb->posts WHERE post_type = %s AND post_name = %s LIMIT 1", self::POST_TYPE, $url_hash ) );

		if ( ! $redirect_post_id )
			$redirect_post_id = 0;

		return $redirect_post_id;
	}

	private static function get_url_hash( $url ) {
		return md5( $url );
	}
}

WPCOM_Legacy_Redirector::start();


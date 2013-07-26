<?php
/**
 * Plugin Name: WPCOM Legacy Redirector
 * Description: Simple plugin for handling legacy redirects in a scalable manner. This is a no-frills plugin (no UI, for example).
 * 
 * Please contact us before using this plugin.
 */

class WPCOM_Legacy_Redirector {
	const POST_TYPE = 'wpcom-legacy-redirect';

	static function start() {
		add_action( 'init', array( __CLASS__, 'init' ) );
		add_filter( 'template_redirect', array( __CLASS__, 'maybe_do_redirect' ) );
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
				wp_safe_redirect( $redirect_uri );
				exit;
			}
		}
	}

	static function insert_legacy_redirect( $url, $post_id ) {
		$url_hash = self::get_url_hash( $url );
		wp_insert_post( array(
			'post_parent' => $post_id,
			'post_name' => $url_hash,
			'post_type' => self::POST_TYPE,
		) );
	}

	static function get_redirect_uri( $url ) {
		$slug = self::get_url_hash( $request_path );
		$redirect_post = wpcom_vip_get_page_by_path( $slug, OBJECT, self::POST_TYPE );
		if ( $redirect_post && $redirect_post->post_parent ) {
			return get_permalink( $redirect_post->post_parent );
		}
		return false;
	}

	private static function get_url_hash( $url ) {
		return md5( $url );
	}
}

WPCOM_Legacy_Redirector::start();


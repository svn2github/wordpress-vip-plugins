<?php
if ( !function_exists( 'wpcom_vip_load_permastruct' ) ):
/**
 * Enables a custom permastruct, if the site wants to use one that's not the WP.com default (/yyyy/mm/dd/post-name/)
 *
 * Usage:
 *     wpcom_vip_load_permastruct( '/%category%/%postname%/' );
 */
function wpcom_vip_load_permastruct( $new_permastruct ) {
	define( 'WPCOM_VIP_CUSTOM_PERMALINKS', true );
	global $wpcom_vip_permalink_structure;
	$wpcom_vip_permalink_structure = $new_permastruct;
	add_filter( 'pre_option_permalink_structure', '_wpcom_vip_filter_permalink_structure', 99 ); // needs to be higher priority so we don't conflict with the WP.com filter
}
endif;

if ( !function_exists( '_wpcom_vip_filter_permalink_structure' ) ):
/**
 * Applies the new permalink structure to the option value
 */
function _wpcom_vip_filter_permalink_structure( $permastruct ) {
	global $wpcom_vip_permalink_structure;
	return $wpcom_vip_permalink_structure;
}
endif;

if ( !function_exists( 'wpcom_vip_load_category_base' ) ):
/**
 * Enables a custom or no category base, if the site wants to use one that's not the WP.com default (/category/)
 *
 * Usage:
 *     wpcom_vip_load_category_base( '' );
 *     wpcom_vip_load_category_base( 'section' );
 */
function wpcom_vip_load_category_base( $new_category_base ) {
	define( 'WPCOM_VIP_CUSTOM_CATEGORY_BASE', true );
	global $wpcom_vip_category_base;
	$wpcom_vip_category_base = $new_category_base;
	add_filter( 'pre_option_category_base', '_wpcom_vip_filter_category_base', 99 ); // needs to be higher priority so we don't conflict with the WP.com filter
}
endif;

if ( !function_exists( '_wpcom_vip_filter_category_base' ) ):
/**
 * Applies the new category base to the option value
 */
function _wpcom_vip_filter_category_base( $category_base ) {
	global $wpcom_vip_category_base;
	return $wpcom_vip_category_base;
}
endif;

if ( !function_exists( 'wpcom_vip_load_tag_base' ) ):
/**
 * Enables a custom or no tag base, if the site wants to use one that's not the WP.com default (/tag/)
 *
 * Usage:
 *     wpcom_vip_load_tag_base( '' );
 *     wpcom_vip_load_tag_base( 'section' );
 */
function wpcom_vip_load_tag_base( $new_tag_base ) {
	define( 'WPCOM_VIP_CUSTOM_TAG_BASE', true );
	global $wpcom_vip_tag_base;
	$wpcom_vip_tag_base = $new_tag_base;
	add_filter( 'pre_option_tag_base', '_wpcom_vip_filter_tag_base', 99 ); // needs to be higher priority so we don't conflict with the WP.com filter/ needs to be higher priority so we don't conflict with the WP.com filter
}
endif;

if ( !function_exists( '_wpcom_vip_filter_tag_base' ) ):
/**
 * Applies the new tag base to the option value
 */
function _wpcom_vip_filter_tag_base( $tag_base ) {
	global $wpcom_vip_tag_base;
	return $wpcom_vip_tag_base;
}
endif;

if ( ! function_exists( 'wpcom_vip_set_cdn_url' ) ):
/**
 * Use a custom CDN host for displaying theme images and media library content.
 * 
 * Please get in touch before using this as it can break your site.
 *
 * @param string Hostname of the CDN for media library assets.
 * @param string Hostname of the CDN for static assets.
 * @param bool Whether the custom CDN host should be used in the admin context as well.
 */
function wpcom_vip_load_custom_cdn( $cdn_host_media, $cdn_host_static = '', $include_admin = false ) {
	if ( ! WPCOM_IS_VIP_ENV )
		return;

	if ( ! $include_admin && is_admin() )
		return;

	if ( ! empty( $cdn_host_static ) ) {
		$cdn_host_static = parse_url( esc_url_raw( $cdn_host_static ), PHP_URL_HOST );

		add_filter( 'wpcom_staticize_subdomain_host', function( $host ) use ( $cdn_host_static ) {
			return $cdn_host_static;
		}, 999 );
	}

	if ( ! empty( $cdn_host_media ) ) {
		$cdn_host_media = parse_url( esc_url_raw( $cdn_host_media ), PHP_URL_HOST );

		add_filter( 'wp_get_attachment_url', function( $url, $attachment_id ) use ( $cdn_host_media ) {
			return _wpcom_vip_custom_cdn_replace( $url, $cdn_host_media );
		}, 999, 2 );

		add_filter( 'the_content', function( $content ) use ( $cdn_host_media ) {
			if ( false !== strpos( $content, 'files.wordpress.com' ) ) {
				$content = preg_replace_callback( '#(https?://[\w]+.files.wordpress.com[^\'">]+)#', function( $matches ) use ( $cdn_host_media ) {
					return _wpcom_vip_custom_cdn_replace( $matches[1], $cdn_host_media );
				}, $content );
			}
			return $content;
		}, 999 );
	}
}
function _wpcom_vip_custom_cdn_replace( $url, $cdn_host ) {
	return preg_replace( '|://[^/]+?/|', "://$cdn_host/", $url );
}
endif;
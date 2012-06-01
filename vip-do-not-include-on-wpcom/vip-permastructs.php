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
	add_filter( 'pre_option_permalink_structure', function( $permastruct = '' ) use ( $new_permastruct ) {
		return $new_permastruct;
	}, 99 ); // needs to be higher priority so we don't conflict with the WP.com filter
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
	add_filter( 'pre_option_category_base', function ( $category_base ) use ( $new_category_base ) {
		return $new_category_base;
	}, 99 ); // needs to be higher priority so we don't conflict with the WP.com filter
}
endif;

if ( !function_exists( 'wpcom_vip_load_category_base' ) ):
/**
 * Enables a custom or no tag base, if the site wants to use one that's not the WP.com default (/tag/)
 *
 * Usage:
 *     wpcom_vip_load_tag_base( '' );
 *     wpcom_vip_load_tag_base( 'section' );
 */
function wpcom_vip_load_tag_base( $new_tag_base ) {
	define( 'WPCOM_VIP_CUSTOM_TAG_BASE', true );
	add_filter( 'pre_option_tag_base', function ( $tag_base ) use ( $new_tag_base ) {
		return $new_tag_base;
	}, 99 ); // needs to be higher priority so we don't conflict with the WP.com filter
}
endif;
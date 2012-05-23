<?php
/**
 * Helper functions that make it easy to add roles for WordPress.com sites.
 */

if ( ! function_exists( 'wpcom_vip_get_role_caps' ) ) :
/**
 * Get a list of capabilities for a role.
 */
function wpcom_vip_get_role_caps( $role ) {
	global $wp_user_roles;

	$caps = array();
	if ( isset( $wp_user_roles[ $role ][ 'capabilities' ] ) )
		$caps = $wp_user_roles[ $role ][ 'capabilities' ];
	return $caps;
}
endif;

if ( ! function_exists( 'wpcom_vip_add_role' ) ) :
/**
 * Add a new role
 *
 * Usage:
 *     wpcom_vip_add_role( 'super-editor', array( 'name' => 'Super Editor', 'capabilities' => array( 'level_0' => true ) ) );
 */
function wpcom_vip_add_role( $role, $role_info ) {
	global $wp_user_roles;
	if ( ! isset( $wp_user_roles[ $role ] ) )
		$wp_user_roles[ $role ] = $role_info;
}
endif;

if ( ! function_exists( 'wpcom_vip_merge_role_caps' ) ) :
/**
 * Add new or change existing capabilities for a given role
 *
 * Usage:
 *     wpcom_vip_merge_role_caps( 'author', array( 'publish_posts' => false ) );
 */
function wpcom_vip_merge_role_caps( $role, $caps ) {
	global $wp_user_roles;
	if ( isset( $wp_user_roles[ $role ] ) ) {
		$current_caps = wpcom_vip_get_role_caps( $role );
		$wp_user_roles[ $role ][ 'capabilities' ] = array_merge( $current_caps, (array) $caps );
	}
}
endif;

if ( ! function_exists( 'wpcom_vip_override_role_caps' ) ) :
/**
 * Completely override capabilities for a given role
 *
 * Usage:
 *     wpcom_vip_override_role_caps( 'editor', array( 'level_0' => false) );
 */
function wpcom_vip_override_role_caps( $role, $caps ) {
	global $wp_user_roles;
	if ( isset( $wp_user_roles[ $role ] ) ) {
		$wp_user_roles[ $role ][ 'capabilities' ] = (array) $caps;
	}
}
endif;

if ( ! function_exists( 'wpcom_vip_duplicate_role' ) ) :
/**
 * Duplicate an existing role and modify some caps
 * 
 * Usage:
 *     wpcom_vip_duplicate_role( 'administrator', 'station-administrator', 'Station Administrator', array( 'manage_categories' => false ) );
 */
function wpcom_vip_duplicate_role( $from_role, $to_role_slug, $to_role_name, $modified_caps ) {
	$caps = array_merge( wpcom_vip_get_role_caps( $from_role ), $modified_caps );
	$role_info = array(
		'name' => $to_role_name,
		'capabilities' => $caps,
	);
	wpcom_vip_add_role( $to_role_slug, $role_info );
}
endif;

if ( ! function_exists( 'wpcom_vip_add_role_caps' ) ) :
/**
 * Add capabilities to an existing role
 *
 * Usage:
 *     wpcom_vip_remove_role_caps( 'contributor', array( 'upload_files' ) );
 */
function wpcom_vip_add_role_caps( $role, $caps ) {
	$filtered_caps = array();
	foreach ( (array) $caps as $cap ) {
		$filtered_caps[ $cap ] = true;
	}
	wpcom_vip_merge_role_caps( $role, $filtered_caps );
}
endif;

if ( ! function_exists( 'wpcom_vip_remove_role_caps' ) ) :
/**
 * Remove capabilities from an existing role
 *
 * Usage:
 *     wpcom_vip_remove_role_caps( 'author', array( 'publish_posts' ) );
 */
function wpcom_vip_remove_role_caps( $role, $caps ) {
	$filtered_caps = array();
	foreach ( (array) $caps as $cap ) {
		$filtered_caps[ $cap ] = false;
	}
	wpcom_vip_merge_role_caps( $role, $filtered_caps );
}
endif;
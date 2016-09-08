<?php

fieldmanager_set_baseurl( wpcom_vip_themes_root_uri() . '/plugins/release-candidates/fieldmanager/' );

/**
 * Patch for get_user_attribute to work with Field Manager.
 * @param  integer $user_id User ID.
 * @param  string  $key     Optional key.
 * @param  boolean $single  True to return single value.
 *
 * @see    get_user_meta()
 *
 * @return mixed
 */
function wpcom_fieldmanager_get_user_attribute( $user_id, $key, $single ) {
	$user_data = get_user_attribute( $user_id, $key );
	if ( ! $single ) {
		$user_data = array( $user_data );
	}
	return $user_data;
}

// Replace user meta functions with user attributes.
add_filter( 'fm_user_context_get_data', function() { return 'wpcom_fieldmanager_get_user_attribute'; } );
add_filter( 'fm_user_context_add_data', function() { return 'add_user_attribute'; } );
add_filter( 'fm_user_context_update_data', function() { return 'update_user_attribute'; } );
add_filter( 'fm_user_context_delete_data', function() { return 'delete_user_attribute'; } );

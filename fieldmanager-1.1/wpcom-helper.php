<?php

fieldmanager_set_baseurl( wpcom_vip_themes_root_uri() . '/plugins/fieldmanager-1.1/' );

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

/**
 * Patch to make WPCOM grofiles page trigger 'user' context
 *
 * @param array $calculated_context Array of context and 'type' information.
 * @return array {
 *     Array of context information.
 *
 *     @type  string|null A Fieldmanager context of "post", "quickedit", "term",
 *                        "submenu", or "user", or null if one isn't found.
 *     @type  string|null A "type" dependent on the context. For "post" and
 *                        "quickedit", the post type. For "term", the taxonomy.
 *                        For "submenu", the group name. For all others, null.
 * }
 */
add_filter( 'fm_calculated_context', function( $calculated_context ) {

    $script = substr( $_SERVER['PHP_SELF'], strrpos( $_SERVER['PHP_SELF'], '/' ) + 1 );
    if ( 'users.php' === $script ) {
        $calculated_context[0] = 'user';
    }

    return $calculated_context;
});
 

<?php

// Force sanitize the value if a callback is not set
add_filter( 'custom_metadata_manager_get_sanitize_callback', function( $callback, $field ) {
	if ( empty( $callback ) )
		$callback = '_wpcom_vip_custom_metadata_force_sanitize';
	return $callback;
}, 999, 3 );

function _wpcom_vip_custom_metadata_force_sanitize( $field_slug, $field, $object_type, $object_id, $value ) {
	if ( is_array( $value ) )
		$value = array_map( 'sanitize_text_field', $value );
	else
		$value = sanitize_text_field( $value );

	return $value;
}

// Force user data to be saved as user attributes instead of user meta
add_filter( 'custom_metadata_manager_get_save_callback', function( $callback, $field, $object_type ) {
	if ( 'user' == $object_type )
		$callback = '_wpcom_vip_custom_metadata_save_user_data_as_attributes';
	return $callback;
}, 999, 3 );

function _wpcom_vip_custom_metadata_save_user_data_as_attributes( $object_type, $object_id, $field_slug, $value ) {
	if ( ! empty( $value ) ) {
		update_user_attribute( $object_id, $field_slug, $value );
	} else {
		delete_user_attribute( $object_id, $field_slug );
	}
}


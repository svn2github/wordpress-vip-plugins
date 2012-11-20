<?php

/**
 * Plugin Name: WPCOM Functions
 * Description: Adds wrappers for functions which are available on WP.com to assist with local development
 */

if ( ! function_exists( 'wpcom_is_vip' ) ) : // Do not load these on WP.com

	/**
	 * Update a user's attribute
	 *
	 * There is no need to serialize values, they will be serialized if it is
	 * needed. The metadata key can only be a string with underscores. All else will
	 * be removed.
	 *
	 * Will remove the attribute, if the meta value is empty.
	 *
	 * @param int $user_id User ID
	 * @param string $meta_key Metadata key.
	 * @param mixed $meta_value Metadata value.
	 * @return bool True on successful update, false on failure.
	 */
	function update_user_attribute( $user_id, $meta_key, $meta_value ) {
		do_action( 'updating_user_attribute', $user_id, $meta_key, $meta_value );

		$result = update_user_meta( $user_id, $meta_key, $meta_value );

		if ( $return )
			do_action( 'updated_user_attribute', $user_id, $meta_key, $meta_value );

		return $result;
	}

	/**
	 * Retrieve user attribute data.
	 *
	 * If $user_id is not a number, then the function will fail over with a 'false'
	 * boolean return value. Other returned values depend on whether there is only
	 * one item to be returned, which be that single item type. If there is more
	 * than one metadata value, then it will be list of metadata values.
	 *
	 * @param int $user_id User ID
	 * @param string $meta_key Optional. Metadata key.
	 * @return mixed
	 */
	function get_user_attribute( $user_id, $meta_key ) {
		if ( !$usermeta = get_user_meta( $user_id, $meta_key ) )
			return false;

		if ( count($usermeta) == 1 )
			return reset($usermeta);

		return $usermeta;
	}

	/**
	 * Remove user attribute data.
	 *
	 * @uses $wpdb WordPress database object for queries.
	 *
	 * @param int $user_id User ID.
	 * @param string $meta_key Metadata key.
	 * @param mixed $meta_value Metadata value.
	 * @return bool True deletion completed and false if user_id is not a number.
	 */
	function delete_user_attribute( $user_id, $meta_key, $meta_value = '' ) {
		$result = delete_user_meta( $user_id, $meta_key, $meta_value );

		do_action( 'deleted_user_attribute', $user_id, $meta_key, $meta_value );

		return $result;
	}

	// These functions are defined on WordPress.com and can be a common source of frustration for VIP devs
	// Now they can be frustrated in their local environments as well :)
	if ( ! function_exists( 'widont' ) ) :
		function widont( $str = '' ) {
			return preg_replace( '|([^\s])\s+([^\s]+)\s*$|', '$1&nbsp;$2', $str );
		}
		add_filter( 'the_title', 'widont' );
	endif;

	if ( ! function_exists( 'widont' ) ) :
		function wido( $str = '' ) {
			return str_replace( '&#160;', ' ', $str );
		}
		add_filter( 'the_title_rss', 'wido' );
	endif;
endif;

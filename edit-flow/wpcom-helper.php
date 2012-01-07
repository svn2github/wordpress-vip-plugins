<?php
/**
 * Don't load caps on install for WP.com. Instead, let's add
 * them with the WP.com apprach.
 */
add_filter( 'ef_kill_add_caps_to_role', '__return_true' );
$custom_roles = array(
	'administrator' => array(
		'capabilities' => array (
			'ef_view_calendar' => true,
			'edit_post_subscriptions' => true,
			'edit_usergroups' => true,
			'ef_view_story_budget' => true,
		),
	),
	'editor' => array(
		'capabilities' => array (
			'ef_view_calendar' => true,
			'edit_post_subscriptions' => true,
			'ef_view_story_budget' => true,
		),
	),
	'author' => array(
		'capabilities' => array (
			'ef_view_calendar' => true,
			'edit_post_subscriptions' => true,
			'ef_view_story_budget' => true,
		),
	),
	'contributor' => array(
		'capabilities' => array (
			'ef_view_calendar' => true,
			'ef_view_story_budget' => true,
		),
	),
);
global $wp_user_roles;
foreach ( $custom_roles as $role_name => $role_args ) {
	if ( isset( $wp_user_roles[ $role_name ] ) ) {
		foreach ( $role_args as $arg_name => $arg_values ) {
			if ( is_array( $wp_user_roles[ $role_name ][ $arg_name ] ) )
				$wp_user_roles[ $role_name ][ $arg_name ] = array_merge( $wp_user_roles[ $role_name ][ $arg_name ], $arg_values ); // Change this if the caps should be completely overwritten
			else
				$wp_user_roles[ $role_name ][ $arg_name ] = $arg_values;
		}
	} else {
		$wp_user_roles[ $role_name ] = $role_args;
	}
}

/**
 * Edit Flow loads modules after plugins_loaded, which has already been fired on WP.com
 * Let's run the method at after_setup_themes
 */
add_filter( 'after_setup_theme', 'edit_flow_wpcom_load_modules' );
function edit_flow_wpcom_load_modules() {
	global $edit_flow;
	if ( method_exists( $edit_flow, 'action_ef_loaded_load_modules' ) )
		$edit_flow->action_ef_loaded_load_modules();
}
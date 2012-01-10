<?php
/**
 * Don't load caps on install for WP.com. Instead, let's add
 * them with the WP.com + core caps approach
 */
add_filter( 'ef_kill_add_caps_to_role', '__return_true' );
add_filter( 'ef_view_calendar_cap', function() { return 'edit_posts'; } );
add_filter( 'ef_view_story_budget_cap', function() { return 'edit_posts'; } );
add_filter( 'edit_post_subscriptions', function() { return 'edit_others_posts'; } );
add_filter( 'edit_usergroups', function() { return 'manage_options'; } );

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
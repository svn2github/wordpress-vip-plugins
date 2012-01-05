<?php
/**
 * Don't load caps on install for WP.com. Instead, let's add
 * them with the WP.com apprach.
 */
add_filter( 'ef_kill_add_caps_to_role', '__return_true' );

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
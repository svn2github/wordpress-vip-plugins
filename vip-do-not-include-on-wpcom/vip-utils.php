<?php

/**
 * Utility function to trigger a callback on a hook with priority or execute immediately if the hook has already been fired previously.
 *
 * @param function $function Callback to trigger.
 * @param string $hook Hook name to trigger on.
 * @param priority int Priority to trigger on.
 *
 * @private
 */
function _wpcom_vip_call_on_hook_or_execute( $function, $hook, $priority = 99 ) {
	if ( ! is_callable( $function ) ) {
		_doing_it_wrong( __FUNCTION__, 'Specified $function is not a valid callback!', '3.8-wpcom' );
		return;
	}

	if ( did_action( $hook ) ) {
		call_user_func( $function );
	} else {
		add_action( $hook, $function, $priority );
	}
}

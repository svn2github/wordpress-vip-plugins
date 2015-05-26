<?php

class WPCOM_VIP_CLI_Command extends WP_CLI_Command {

	/**
	 * Clear all of the caches for memory management
 	 */
	protected function stop_the_insanity() {
		/**
		 * @var \WP_Object_Cache $wp_object_cache
		 * @var \wpdb $wpdb
		 */
		global $wpdb, $wp_object_cache;

		$wpdb->queries = array(); // or define( 'WP_IMPORTING', true );

		if ( is_object( $wp_object_cache ) ) {
			$wp_object_cache->group_ops = array();
			$wp_object_cache->stats = array();
			$wp_object_cache->memcache_debug = array();
			$wp_object_cache->cache = array();

			if ( method_exists( $wp_object_cache, '__remoteset' ) ) {
				$wp_object_cache->__remoteset(); // important
			}
		}
	}

}

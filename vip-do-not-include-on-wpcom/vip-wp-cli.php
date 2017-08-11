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

	/**
	 * Stop WordPress.com from creating jobs that send pings via pushpress for every post that is updated.
	 * Disable term counting so that terms are not all recounted after every term operation
	 * Disable ES indexing so that there are not hundreds to thousands of Elasticsearch index jobs created.
	 */
	protected function start_bulk_operation(){
		wpcom_vip_start_bulk_operation();

	}

	/**
	 * Re-enable PushPress
	 * Re-enable Term counting and trigger a term counting operation to update all term counts
	 * Re-enable Elasticsearch indexing and trigger a bulk re-index of the site
	 */
	protected function end_bulk_operation(){
		wpcom_vip_end_bulk_operation();
	}
}

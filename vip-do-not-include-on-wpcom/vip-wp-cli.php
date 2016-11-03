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
		// Do not send notification when post is updated to 'published'
		add_filter( 'wpcom_pushpress_should_send_ping', '__return false' );
		// Disable term count updates for speed
		wp_defer_term_counting( true );
		if ( class_exists( 'ES_WP_Indexing_Trigger' ) ){
			ES_WP_Indexing_Trigger::get_instance()->disable(); //disconnects the wp action hooks that trigger indexing jobs
		}

	}

	/**
	 * Re-enable PushPress
	 * Re-enable Term counting and trigger a term counting operation to update all term counts
	 * Re-enable Elasticsearch indexing and trigger a bulk re-index of the site
	 */
	protected function end_bulk_operation(){
		remove_filter( 'wpcom_pushpress_should_send_ping', '__return false' ); //This shouldn't be required but it's nice to clean up all the settings we changed so they are back to their defaults.
		wp_defer_term_counting( false ); // This will also trigger a term count.
		if ( class_exists( 'ES_WP_Indexing_Trigger' ) ){
			ES_WP_Indexing_Trigger::get_instance()->enable(); //reenable the hooks
			ES_WP_Indexing_Trigger::get_instance()->trigger_bulk_index( get_current_blog_id(), 'wp_cli' ); //queues async indexing job to be sent on wp shutdown hook, this will re-index the site inside Elasticsearch
		}
	}
}

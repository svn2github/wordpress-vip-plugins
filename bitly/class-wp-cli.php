<?php

WP_CLI::add_command( 'bitly', 'Bitly_Command' );

class Bitly_Command extends WP_CLI_Command {
	/**
	 * Subcommand to delete all bitly urls
	 *
	 * NOTE - Doesn't delete the bitly url for the site, and doesn't flip the bitly_processed option back to 0 to allow backfilling
	 * to restart.
	 *
	 * @subcommand delete-bitly-urls [--post_types=<post_types>] [--post_status=<post_status>] --[published_before=<published_before>]
	 */
	public function delete_bitly_urls( $args, $assoc_args ) {
		global $coauthors_plus;

		$defaults = array(
			'post_types' 	=> 'post,page',
			'post_status' 	=> 'any',
		);

		$args = wp_parse_args( $assoc_args, $defaults );

		// Must process in batches for large sites
		$batch_size = 500;

		$query_args = array(
			'post_type' 		=> explode( ',', $args['post_types'] ),
			'post_status' 		=> $args['post_status'],
			'meta_key'			=> 'bitly_url',
			'posts_per_page'	=> $batch_size,
			'order_by'			=> 'ID',
			'order'				=> 'ASC',
			'paged'				=> 0,
			'cache_results'		=> false,
			'update_meta_cache'	=> false,
			'update_term_cache' => false
		);

		if ( $args['published_before'] ) {
			$query_args['date_query'] = array(
				array(
					'before' => $args['published_before']
				)
			);
		}

		$query = new WP_Query( $query_args );

		WP_CLI::line( sprintf( 'Found %d posts to update', $query->found_posts ) );

		while( $query->post_count ) {
			foreach ( $query->posts as $post ) {
				WP_CLI::line( sprintf( 'Deleting bitly url for post %d', $post->ID ) );
				
				delete_post_meta( $post->ID, 'bitly_url' );
			}

			sleep( 2 );

			$this->stop_the_insanity();

			$query = new WP_Query( $query_args );
		}

		WP_CLI::success( 'All done!' );
	}

	/**
	 * Clear all of the caches for memory management
	 */
	private function stop_the_insanity() {
		global $wpdb, $wp_object_cache;

		$wpdb->queries = array(); // or define( 'WP_IMPORTING', true );

		if ( !is_object( $wp_object_cache ) )
			return;

		$wp_object_cache->group_ops = array();
		$wp_object_cache->stats = array();
		$wp_object_cache->memcache_debug = array();
		$wp_object_cache->cache = array();

		if( is_callable( $wp_object_cache, '__remoteset' ) )
			$wp_object_cache->__remoteset(); // important
	}
}
<?php

class WPCOM_Legacy_Redirector_CLI extends WP_CLI_Command {

	/**
 	 * Bulk import redirects from URLs stored as meta values for posts.
 	 *
 	 * @subcommand import-from-meta
 	 * @synopsis --meta_key=<name-of-meta-key> [--start=<start-offset>] [--end=<end-offset>]
 	 */
	function import_from_meta( $args, $assoc_args ) {
		define( 'WP_IMPORTING', true );

		global $wpdb;
		$offset = isset( $assoc_args['start'] ) ? intval( $assoc_args['start'] ) : 0;
		$end_offset = isset( $assoc_args['end'] ) ? intval( $assoc_args['end'] ) : 99999999;;
		$meta_key = isset( $assoc_args['meta_key'] ) ? sanitize_key( $assoc_args['meta_key'] ) : ''; 

		do {
			$redirects = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = %s ORDER BY post_id ASC LIMIT %d, 1000", $meta_key, $offset ) );
			$i = 0;
			$total = count( $redirects );
			WP_CLI::line( "Found $total entries" );

			foreach ( $redirects as $redirect ) {
				$i++;
				WP_CLI::line( "Adding redirect for {$redirect->post_id} from {$redirect->meta_value}" );
				WP_CLI::line( "-- $i of $total (starting at offset $offset)" );
				WPCOM_Legacy_Redirector::insert_legacy_redirect( $redirect->meta_value, $redirect->post_id );

				if ( 0 == $i % 100 ) {
					if ( function_exists( 'stop_the_insanity' ) )
						stop_the_insanity();
					sleep( 1 );
				}
			}
			$offset += 1000;
		} while( $redirects && $offset < $end_offset );
	}

	/**
 	 * Bulk import redirects from a CSV file matching the following structure:
 	 *
 	 * post_id,url
 	 *
 	 * @subcommand import-from-csv
 	 * @synopsis --csv=<path/to/csv>
 	 */
	function import_from_csv( $args, $assoc_args ) {
		define( 'WP_IMPORTING', true );

		if ( empty( $assoc_args['csv'] ) || ! file_exists( $assoc_args['csv'] ) ) {
			WP_CLI::error( "Invalid 'csv' file" );
		}

		global $wpdb;
		if ( ( $handle = fopen( $assoc_args['csv'], "r" ) ) !== FALSE ) {
			while ( ( $data = fgetcsv( $handle, 2000, "," ) ) !== FALSE ) {
				$row++;
				$post_id = $data[ 0 ];
				$url = $data[ 1 ];
				WP_CLI::line( "Adding (CSV) redirect for {$post_id} from {$url}" );
				WP_CLI::line( "-- at $row" );
				WPCOM_Legacy_Redirector::insert_legacy_redirect( $url, $post_id );

				if ( 0 == $row % 100 ) {
					if ( function_exists( 'stop_the_insanity' ) )
						stop_the_insanity();
					sleep( 1 );
				}
			}
			fclose( $handle );
		}
	}

}

WP_CLI::add_command( 'wpcom-legacy-redirector', 'WPCOM_Legacy_Redirector_CLI' );

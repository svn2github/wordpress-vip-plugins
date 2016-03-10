<?php

add_filter( 'msm_sitemap_use_cron_builder', '__return_false', 9999 ); // On WP.com we're going to use the jobs system

if ( function_exists( 'queue_async_job' ) ) {
	add_action( 'msm_update_sitemap_for_year_month_date', 'msm_wpcom_schedule_sitemap_update_for_year_month_date', 10, 2 );
	add_action( 'msm_insert_sitemap_post', 'msm_sitemap_wpcom_queue_cache_invalidation', 10, 4 );
	add_action( 'msm_delete_sitemap_post', 'msm_sitemap_wpcom_queue_cache_invalidation', 10, 4 );
	add_action( 'msm_update_sitemap_post', 'msm_sitemap_wpcom_queue_cache_invalidation', 10, 4 );
}

function msm_wpcom_schedule_sitemap_update_for_year_month_date( $date, $time ) {
	$data = (object) array( 'date' => $date );
	queue_async_job( $data, 'vip_async_generate_sitemap' );
}

/**
 * Queue action to invalidate nginx cache if on WPCOM
 * @param int $sitemap_id
 * @param string $year
 * @param string $month
 * @param string $day
 */
function msm_sitemap_wpcom_queue_cache_invalidation( $sitemap_id, $year, $month, $day ) {
	$sitemap_url = home_url( '/sitemap.xml' );

	$sitemap_urls = array(
		$sitemap_url,
		add_query_arg( array( 'yyyy' => $year, 'mm' => $month, 'dd' => $day ), $sitemap_url ),
	);

	queue_async_job( array( 'output_cache' => array( 'url' => $sitemap_urls ) ), 'wpcom_invalidate_output_cache_job', -16 );
}

//VIP: temporary hack for Google Bot's bug
//Google Bot is crawling encoded URLs instead of unencoded ones
add_action( 'parse_request', function( $query ) {
	if ( true === apply_filters( 'wpcom_vip_redirect_encoded_urls', false ) //filter to turn this on/off
		 && true === array_key_exists( 'sitemap', $query->query_vars )
		 && 'true' === $query->query_vars['sitemap'] //checking string intentionally
	) {

		$args = array(
			'yyyy' => $_GET['yyyy'],
		);

		if ( true === array_key_exists( 'amp;mm', $_GET ) ) {
			$args['mm'] = $_GET['amp;mm'];
		}

		if ( true === array_key_exists( 'amp;dd', $_GET ) ) {
			$args['dd'] = $_GET['amp;dd'];
		}

		if ( true === array_key_exists( 'mm', $args ) || true === array_key_exists( 'dd', $args ) ) {
			
			//sanitize query args
			array_map( 'urlencode', $args );

			$redirect_url = add_query_arg( $args, home_url( '/sitemap.xml' ) );
			//301 redirect
			wp_safe_redirect( esc_url_raw( $redirect_url ), 301 );
			exit;
		}
	}
} );


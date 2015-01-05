<?php
/**
 * Auto-apply Co-Authors Plus template tags on themes that are properly using the_author()
 * and the_author_posts_link()
 */
$wpcom_coauthors_plus_auto_apply_themes = array(
		'premium/portfolio',
	);
if ( in_array( get_option( 'template' ), $wpcom_coauthors_plus_auto_apply_themes ) )
	add_filter( 'coauthors_auto_apply_template_tags', '__return_true' );

/**
 * If Co-Authors Plus is enabled on an Enterprise site and hasn't yet been integrated with the theme
 * show an admin notice
 */
if ( function_exists( 'Enterprise' ) ) {
	if ( Enterprise()->is_enabled() && ! in_array( get_option( 'template' ), $wpcom_coauthors_plus_auto_apply_themes ) )
		add_action( 'admin_notices', function() {

			// Allow this to be short-circuted in mu-plugins
			if ( ! apply_filters( 'wpcom_coauthors_show_enterprise_notice', true ) )
				return;

			echo '<div class="error"><p>' . __( "Co-Authors Plus isn't yet integrated with your theme. Please contact support to make it happen." ) . '</p></div>';
		} );
}

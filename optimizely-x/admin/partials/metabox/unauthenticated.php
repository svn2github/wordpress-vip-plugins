<?php
/**
 * Optimizely X admin metabox partials: Unauthenticated template
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

?>

<p>
	<?php
		printf(
			/* translators: 1: opening <a> tag, 2: </a> */
			esc_html__( 'Please configure your API credentials and project on the %1$sOptimizely configuration page%2$s.', 'optimizely-x' ),
			'<a href="' . esc_url( menu_page_url( 'optimizely-config', false ) ) . '">',
			'</a>'
		);
	?>
</p>

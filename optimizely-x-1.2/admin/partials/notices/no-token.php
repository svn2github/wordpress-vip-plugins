<?php
/**
 * Optimizely X admin notices partials: No token template
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

?>

<div class="notice notice-info">
	<p>
		<?php printf(
			/* translators: 1: opening <a> tag, 2: </a>, 3: opening <a> tag, 4: </a> */
			esc_html__( 'Optimizely is almost ready. You must first add your %1$sAPI Token%2$s on the %3$sconfiguration page%4$s.', 'optimizely-x' ),
			'<a href="https://app.optimizely.com/v2/profile/api" target="_blank">',
			'</a>',
			'<a href="' . esc_url( menu_page_url( 'optimizely-config', false ) ) . '">',
			'</a>'
		); ?>
	</p>
</div>

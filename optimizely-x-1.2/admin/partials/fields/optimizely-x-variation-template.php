<?php
/**
 * Optimizely X admin field partials: optimizely_x_variation_template field
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

// Negotiate URL targeting type value.
$variation_template = get_option( 'optimizely_x_variation_template' );
if ( empty( $variation_template ) ) {
	$variation_template = Optimizely_X\Admin::DEFAULT_VARIATION_TEMPLATE;
}

?>

<div>
	<textarea class="code optimizely-requires-authentication"
		id="optimizely-x-variation-template"
		name="optimizely_x_variation_template"
		rows="5"
	><?php echo esc_textarea( $variation_template ); ?></textarea>
</div>
<p class="description">
	<?php esc_html_e( 'You can use the variables $POST_ID, $OLD_TITLE, and $NEW_TITLE in your code.', 'optimizely-x' ); ?>
</p>
<p class="description">
	<?php printf(
		/* translators: 1: opening <a> tag, 2: </a> */
		esc_html__( 'Optimizely will use this variation code to change headlines on your site. We\'ve provided code that works if you have changed your headlines to have a class with the format optimizely-$POST_ID, but you might want to add or change it to work with your themes and plugins. For more information on how to update your HTML or write custom variation code, please read this %1$sknowledge base article%2$s.', 'optimizely-x' ),
		'<a href="https://help.optimizely.com/Build_Campaigns_and_Experiments/Custom_code_in_Optimizely_X" target="_blank">',
		'</a>'
	); ?>
</p>

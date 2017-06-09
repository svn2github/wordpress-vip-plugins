<?php
/**
 * Optimizely X admin field partials: optimizely_conditional_activation_code field
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

// Negotiate conditional activation code value.
$conditional_activation_code = get_option( 'optimizely_conditional_activation_code' );
if ( empty( $conditional_activation_code ) ) {
	$conditional_activation_code = Optimizely_X\Admin::DEFAULT_CONDITIONAL_TEMPLATE;
}

?>

<div>
	<textarea class="code optimizely-requires-authentication"
		id="optimizely-conditional-activation-code"
		name="optimizely_conditional_activation_code"
		rows="5"
	><?php echo esc_textarea( $conditional_activation_code ); ?></textarea>
</div>
<p class="description">
	<?php esc_html_e( 'You can use the variables $POST_ID and $OLD_TITLE in your code. The code below will activate the experiment if the original title is on the page and its not the first page the user has visited.', 'optimizely-x' ); ?>
</p>

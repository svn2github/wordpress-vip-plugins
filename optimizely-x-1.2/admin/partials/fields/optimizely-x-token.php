<?php
/**
 * Optimizely X admin field partials: optimizely_x_token field
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

$token = get_option( 'optimizely_x_token' );
if ( ! empty( $token ) ) {
	$token = hash( 'ripemd160', $token );
}
?>

<div>
	<input id="optimizely-x-token"
		name="optimizely_x_token"
		type="password"
		maxlength="80"
		value="<?php echo esc_attr( $token ); ?>" class="code"
	/>
</div>
<p class="description">
	<?php printf(
		/* translators: 1: opening <a> tag, 2: </a> */
		esc_html__( 'Once you create an account, you can create a Personal Token on the %1$sdeveloper portal%2$s.', 'optimizely-x' ),
		'<a href="https://app.optimizely.com/v2/profile/api" target="_blank">',
		'</a>'
	); ?>
</p>

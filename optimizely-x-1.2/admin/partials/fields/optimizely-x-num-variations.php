<?php
/**
 * Optimizely X admin field partials: optimizely_x_num_variations field
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

// Negotiate number of variations value.
$num_variations = get_option( 'optimizely_x_num_variations' );
if ( empty( $num_variations ) ) {
	$num_variations = 2;
}

?>

<div>
	<input class="optimizely-requires-authentication"
		id="optimizely-x-num-variations"
		maxlength="1"
		name="optimizely_x_num_variations"
		type="number"
		value="<?php echo absint( $num_variations ); ?>"
	/>
</div>
<p class="description">
	<?php esc_html_e( 'The maximum additional number of variations a user can test per post.', 'optimizely-x' ); ?>
</p>

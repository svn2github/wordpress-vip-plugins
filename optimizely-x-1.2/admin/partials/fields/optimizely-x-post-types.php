<?php
/**
 * Optimizely X admin field partials: optimizely_x_post_types field
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

// Get list of supported post types and selected post types.
$opt_post_types      = Optimizely_X\Admin::supported_post_types();
$selected_post_types = get_option( 'optimizely_x_post_types' );
if ( empty( $selected_post_types ) || ! is_array( $selected_post_types ) ) {
	$selected_post_types = array( 'post' );
}

?>

<fieldset>
	<legend class="screen-reader-text">
		<span><?php esc_html_e( 'Post Types', 'optimizely-x' ); ?></span>
	</legend>
	<?php foreach ( $opt_post_types as $post_type ) : ?>
		<div>
			<label for="optimizely-x-post-types-<?php echo esc_attr( $post_type->name ); ?>">
				<input class="optimizely-requires-authentication"
					id="optimizely-x-post-types-<?php echo esc_attr( $post_type->name ); ?>"
					name="optimizely_x_post_types[]"
					type="checkbox"
					value="<?php echo esc_attr( $post_type->name ); ?>"
					<?php checked( in_array( $post_type->name, $selected_post_types, true ) ); ?>
				/>
				<?php echo esc_html( $post_type->label ); ?>
			</label>
		</div>
	<?php endforeach; ?>
</fieldset>
<p class="description">
	<?php esc_html_e( 'Please choose the post types you would like to conduct A/B testing on.', 'optimizely-x' ); ?>
</p>

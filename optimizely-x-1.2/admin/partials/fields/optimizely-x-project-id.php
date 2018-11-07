<?php
/**
 * Optimizely X admin field partials: optimizely_x_project_id field
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

?>

<div>
	<select class="optimizely-requires-authentication"
		id="optimizely-x-project-id"
		name="optimizely_x_project_id"
	>
		<?php $project_id = absint( get_option( 'optimizely_x_project_id' ) ); ?>
		<?php if ( ! empty( $project_id ) ) : ?>
			<option value=""><?php esc_html_e( 'Disable Optimizely', 'optimizely-x' ); ?></option>
			<option value="<?php echo esc_attr( $project_id ); ?>" selected>
				<?php echo esc_html( get_option( 'optimizely_x_project_name' ) ); ?>
			</option>
		<?php else : ?>
			<option value=""><?php esc_html_e( 'Choose a project...', 'optimizely-x' ); ?></option>
		<?php endif; ?>
	</select>
</div>
<div id="optimizely-project-code" class="hidden">
	<p class="description"><?php esc_html_e( 'Optimizely will add the following project code to your page automatically:', 'optimizely-x' ); ?></p>
</div>

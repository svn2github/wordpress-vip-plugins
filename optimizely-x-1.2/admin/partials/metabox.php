<?php
/**
 * Optimizely X admin partials: Metabox template
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

global $post;

// Get experiment ID (if any) and current status (if any) for this post.
$experiment_id = get_post_meta( $post->ID, 'optimizely_experiment_id', true );
$status = get_post_meta( $post->ID, 'optimizely_experiment_status', true );

// Negotiate the number of variations for this post.
$num_variations = absint( get_option( 'optimizely_x_num_variations' ) );
$post_num_variations = absint( get_post_meta( $post->ID, 'optimizely_variations_num', true ) );
if ( empty( $post_num_variations ) ) {
	$post_num_variations = $num_variations;
}

?>

<div id="optimizely-experiment-container"
	data-optimizely-entity-id="<?php if ( ! empty( $post->ID ) ) { echo absint( $post->ID ); } ?>"
	data-optimizely-experiment-status="<?php if ( ! empty( $status ) ) { echo esc_attr( $status ); } ?>"
>
	<div class="optimizely-loading hidden">
		<p><?php esc_html_e( 'Loading your experiment ...', 'optimizely-x' ); ?></p>
		<img src="<?php echo esc_url( OPTIMIZELY_X_BASE_URL . '/admin/images/ajax-loader.gif' ); ?>" />
	</div>
	<div class="optimizely-new-experiment <?php if ( ! empty( $experiment_id ) ) : ?>hidden<?php endif; ?>">
		<div class="optimizely-experiment">
			<?php for ( $i = 1; $i <= $num_variations; $i++ ) : ?>
				<?php $meta_key = 'post_title_' . $i; ?>
				<div class="optimizely-variation">
					<label for="<?php echo esc_attr( $meta_key ); ?>">
						<?php esc_html_e( 'Variation', 'optimizely-x' ); ?> #<?php echo absint( $i ); ?>
					</label>
					<br />
					<input id="<?php echo esc_attr( $meta_key ); ?>"
						name="<?php echo esc_attr( $meta_key ); ?>"
						placeholder="<?php esc_attr_e( 'Title', 'optimizely-x' ); ?> <?php echo absint( $i ); ?>"
						type="text"
					/>
				</div>
			<?php endfor; ?>
		</div>
		<div id="optimizely-not-created">
			<a id="optimizely-create" class="button button-secondary"><?php esc_html_e( 'Create Experiment', 'optimizely-x' ); ?></a>
		</div>
	</div>
	<div class="optimizely-running-experiment <?php if ( empty( $experiment_id ) ) : ?>hidden<?php endif; ?>">
		<div class="optimizely-experiment">
			<?php for ( $i = 0; $i < $post_num_variations; $i++ ) : ?>
				<p>
					<?php esc_html_e( 'Variation', 'optimizely-x' ); ?>
					#<?php echo absint( $i + 1 ); ?>
					<br />
					<strong class="optimizely-variation-title"><?php echo esc_html( get_post_meta( $post->ID, 'optimizely_variations_' . $i, true ) ); ?></strong>
				</p>
			<?php endfor; ?>
		</div>
		<div id="optimizely-created">
			<p>
				<?php esc_html_e( 'Status', 'optimizely-x' ); ?>:
				<strong id="optimizely-experiment-status-text"><?php echo esc_html( $status ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'Experiment ID', 'optimizely-x' ); ?>:
				<strong id="optimizely-experiment-id"><?php echo esc_html( $experiment_id ); ?></strong>
			</p>
			<p>
				<a class="button button-secondary optimizely-toggle-running optimizely-toggle-running-start <?php if ( 'running' === $status ) : ?>hidden<?php endif; ?>">
					<?php esc_html_e( 'Start Experiment', 'optimizely-x' ); ?>
				</a>
				<a class="button button-secondary optimizely-toggle-running optimizely-toggle-running-pause <?php if ( 'running' !== $status ) : ?>hidden<?php endif; ?>">
					<?php esc_html_e( 'Pause Experiment', 'optimizely-x' ); ?>
				</a>
			</p>
			<p>
				<a class="button button-secondary optimizely-view-link"
					href="<?php echo esc_url( get_post_meta( $post->ID, 'optimizely_editor_link', true ) ); ?>"
					id="optimizely-view"
					target="_blank"
				><?php esc_html_e( 'View on Optimizely', 'optimizely-x' ); ?></a>
			</p>
		</div>
	</div>
</div>

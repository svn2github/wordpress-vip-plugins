<?php
/**
 * Optimizely X admin field partials: optimizely_activation_mode field
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

// Negotiate activation mode value.
$activation_mode = get_option( 'optimizely_activation_mode' );
if ( empty( $activation_mode ) ) {
	$activation_mode = 'immediate';
}

?>

<fieldset id="optimizely-activation-mode">
	<legend class="screen-reader-text">
		<span><?php esc_html_e( 'Activation Mode', 'optimizely-x' ); ?></span>
	</legend>
	<div>
		<label for="optimizely-activation-mode-immediate">
			<input class="optimizely-requires-authentication"
				id="optimizely-activation-mode-immediate"
				name="optimizely_activation_mode"
				type="radio"
				value="immediate"
				<?php checked( $activation_mode, 'immediate' ); ?>
			/>
			<?php esc_html_e( 'Immediate', 'optimizely-x' ); ?>
		</label>
	</div>
	<div>
		<label for="optimizely-activation-mode-conditional">
			<input class="optimizely-requires-authentication"
				id="optimizely-activation-mode-conditional"
				name="optimizely_activation_mode"
				type="radio"
				value="conditional"
				<?php checked( $activation_mode, 'conditional' ); ?>
			/>
			<?php esc_html_e( 'Conditional', 'optimizely-x' ); ?>
		</label>
	</div>
</fieldset>
<p class="description">
	<?php printf(
		/* translators: 1: opening <a> tag, 2: </a> */
		esc_html__( 'You can choose between Immediate Activation Mode or Conditional Activation Mode. If you choose immediate, the experiment will run on every page of your site regardless of whether the headline is on the page or not. Conditional Activation will only run the experiment if the headline is on the page. However, this does require additional coding. For more information about activation modes, please read this %1$sknowledge base article%2$s.', 'optimizely-x' ),
		'<a href="https://help.optimizely.com/Build_Campaigns_and_Experiments/Activation_Mode%3A_Activating_an_experiment_dynamically_after_a_page_has_loaded" target="_blank">',
		'</a>'
	); ?>
</p>

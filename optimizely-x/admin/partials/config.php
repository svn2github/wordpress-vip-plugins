<?php
/**
 * Optimizely X admin partials: Config page template
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

?>

<div class="wrap optimizely-admin">
	<h1><?php esc_html_e( 'Optimizely', 'optimizely-x' ); ?></h1>
	<?php settings_errors(); ?>
	<div class="card">
		<h3><?php esc_html_e( 'Installation Instructions', 'optimizely-x' ); ?></h3>
		<p>
			<?php printf(
				/* translators: 1: opening <a> tag, 2: </a> */
				esc_html__( 'For full instructions on how to configure the settings and use the Optimizely plugin, please read this %1$sknowledge base article%2$s.', 'optimizely-x' ),
				'<a href="https://help.optimizely.com/Integrate_Other_Platforms/Integrate_Optimizely_with_WordPress" target="_blank">',
				'</a>'
			); ?>
		</p>
	</div>
	<div class="card">
		<h3><?php esc_html_e( 'About Optimizely', 'optimizely-x' ); ?></h3>
		<p>
			<?php printf(
				/* translators: 1: opening <a> tag, 2: </a>, 3: opening <a> tag, 4: </a> */
				esc_html__( 'Simple, fast, and powerful. %1$sOptimizely%2$s is a dramatically easier way for you to improve your website through A/B testing. Create an experiment in minutes with absolutely no coding or engineering required. Convert your website visitors into customers and earn more revenue: create an account at %3$soptimizely.com%4$s and start A/B testing today!', 'optimizely-x' ),
				'<a href="https://www.optimizely.com" target="_blank">',
				'</a>',
				'<a href="https://www.optimizely.com" target="_blank">',
				'</a>'
			); ?>
		</p>
	</div>
	<form id="optimizely-config" method="post" action="options.php">
		<?php settings_fields( 'optimizely_config_section' ); ?>
		<?php do_settings_sections( 'optimizely_config_options' ); ?>
		<div class="optimizely-loading hidden">
			<p><?php esc_html_e( 'Loading your configuration ...', 'optimizely-x' ); ?></p>
			<img src="<?php echo esc_url( OPTIMIZELY_X_BASE_URL . '/admin/images/ajax-loader.gif' ); ?>" />
		</div>
		<div class="optimizely-error-message optimizely-invalid-token hidden">
			<?php esc_html_e( 'A request with the Personal Token that you have previously saved failed. Either submit a new token or reload the page to try again.', 'optimizely-x' ); ?>
		</div>
		<?php submit_button(); ?>
	</form>
</div>

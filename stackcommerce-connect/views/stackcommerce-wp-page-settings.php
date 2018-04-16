<?php
if ( ! defined( 'ABSPATH' ) ) {
  die( 'Access denied.' );
}
?>

<div class="wrap">
	<h1 class="stackcommerce-wp-title"><?php esc_html_e( SCWP_NAME ); ?> - Settings</h1>

	<?php
	if ( isset( $_GET['settings-updated'] ) ) {
	add_settings_error(
	  'stackcommerce_wp_messages',
	  'stackcommerce_wp_message',
	  __( 'Settings Saved', 'stackcommerce_wp' ),
	  'updated'
	);
	}

	$scwp_nonce = wp_create_nonce( 'stackcommerce_wp' );
	$scwp_site_url = site_url();
	$scwp_api = SCWP_CMS_API_ENDPOINT;
	$scwp_api_version = SCWP_API_VERSION;
	$scwp_plugin_version = SCWP_PLUGIN_VERSION;

	settings_errors( 'stackcommerce_wp_messages' );
	?>

	<div class="stackcommerce-wp-wrap">

		<input type="hidden" id="stackcommerce_wp_nonce" value="<?php echo esc_attr( $scwp_nonce ); ?>" />

		<input type="hidden" id="stackcommerce_wp_endpoint" value="<?php echo esc_url( $scwp_site_url ); ?>/index.php?sc-api-version=<?php echo esc_attr( $scwp_api_version ); ?>&sc-api-route=posts" />

		<input type="hidden" id="stackcommerce_wp_cms_api_endpoint" value="<?php echo esc_url( $scwp_api ); ?>" />

		<input type="hidden" id="stackcommerce_wp_plugin_version" value="<?php echo esc_attr( $scwp_plugin_version ); ?>" />

		<form method="post" class="stackcommerce-wp-form" id="stackcommerce-wp-form" action="options.php" autocomplete="off" data-stackcommerce-wp-status data-stackcommerce-wp-content-integration>

		<?php settings_fields( 'stackcommerce_wp' ); ?>

		<div class="stackcommerce-wp-section">
			<?php do_settings_sections( 'stackcommerce_wp' ); ?>
		</div>

			<p class="submit">
				<input type="button" class="button-primary" id="stackcommerce-wp-form-submit" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
			</p>
		</form>
	</div>
</div>

<?php
/**
 * Template for main settings page when business model is set to "hard / keyed paywall"
 * @var $currencies array
 * Rendered by @see WPTinypassAdmin
 * @var WPTinypassAdmin $this
 * @var array $currencies
 * @var TinypassResource[] $resources
 * @var TinypassContentSettings $contentSettings
 */
?>
<form method="POST" action="options.php">
	<?php
	settings_fields( 'tinypass-settings-section' );
	?>
	<div class="postbox">
		<div class="inside">
			<?php require( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'views/admin/_connection.php' ); ?>
			<p>
				<label><input type="checkbox"
				              name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_METER_HOME_PAGE ) ) ?>"
				              <?php if ( $this::$track_home_page ): ?>checked<?php endif ?>
				              value="1"/><?php esc_html_e( 'Track on home page visit - visiting your homepage will count as a view.', 'tinypass' ) ?>
				</label>
			</p>

			<p>
				<label><input type="checkbox"
				              name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_DISABLED_FOR_PRIVILEGED ) ) ?>"
				              <?php if ( $this::$disabled_for_privileged ): ?>checked<?php endif ?>
				              value="1"/><?php esc_html_e( 'Disable Tinypass for privileged users - Tinypass will be skipped for website moderators.', 'tinypass' ) ?>
				</label>
			</p>
			<?php // Include the template with "enable dubug" checkbox?>
			<?php require( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'views/admin/_debug.php' ); ?>
			<?php // Include the template with "enable premium tag" checkbox?>
			<?php require( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'views/admin/_premium_tag.php' ); ?>
			<?php // Include the template with first click settings ?>
			<?php require_once( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . '/views/admin/_first_click_options.php' ); ?>
			<?php // Include the template with display mode settings ?>
			<?php require_once( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . '/views/admin/_display_mode.php' ); ?>
			<?php // Include the template with truncation settings ?>
			<?php require( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'views/admin/_truncation_settings.php' ); ?>
			<?php if ( $this->isConfigured() ): ?>
				<h3><?php esc_html_e( 'Default content access settings', 'tinypass' ) ?></h3>
				<div class="postbox">
					<div class="inside">
						<?php // Include the template with content settings ?>
						<?php require( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'views/admin/metabox_metered.php' ); ?>
					</div>
				</div>
			<?php endif ?>
			<?php // Show note about my account shortcode ?>
			<?php require( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'views/admin/_my_account.php' ); ?>
			<?php
			submit_button();
			?>
		</div>
	</div>

</form>
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
	<div class="postbox tinypass-tabbed-postbox">
		<div class="inside">
			<?php require( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'views/admin/_connection.php' ); ?>
			<table>
				<tr>
					<td>
						<ul>
							<li>
								<label>
									<input type="radio" value="1"
									       name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_ENABLE_PPP ) ) ?>"
										<?php checked( $this::$enable_ppp ); ?>
										   class="tinypass-dynamic-display"
										   tinypass-dynamic-display="1"
										   rel=".<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_ENABLE_PPP ) ) ?>"/><?php esc_html_e( 'Yes, offer my users pay-per-post (you will have the option to enable or disable on a per-post basis)', 'tinypass' ) ?>
								</label>
							</li>
							<li>
								<label>
									<input type="radio" value="0"
									       name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_ENABLE_PPP ) ) ?>"
										<?php checked( !$this::$enable_ppp ); ?>
										   class="tinypass-dynamic-display"
										   tinypass-dynamic-display="1"
										   rel=".<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_ENABLE_PPP ) ) ?>"/><?php esc_html_e( 'No, I only want to sell subscriptions', 'tinypass' ) ?>
								</label>
							</li>
						</ul>
					</td>
				</tr>
			</table>
			<?php // Include the template with first click settings ?>
			<?php require_once( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . '/views/admin/_first_click_options.php' ); ?>
			<?php // Include the template with display mode settings ?>
			<?php require_once( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . '/views/admin/_display_mode.php' ); ?>
			<?php // Include the template with environment selection ?>
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
			<?php // Include the template with truncation settings ?>
			<?php require( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'views/admin/_truncation_settings.php' ); ?>
			<?php if ( $this->isConfigured() ): ?>
				<h3><?php esc_html_e( 'Default content access settings', 'tinypass' ) ?></h3>
				<div class="postbox">
					<div class="inside">
						<?php // Include the template with content settings ?>
						<?php require( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'views/admin/metabox.php' ); ?>
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
<?php
/**
 * @var WPTinypassAdmin $this
 */
?>
<div class="postbox">
	<div class="inside">
		<table>
			<tr>
				<td>
					<h3>
						<?php esc_html_e( 'Your application id:', 'tinypass' ) ?>
					</h3>
				</td>
				<td>
					<?php echo esc_html( $this::$app_id ) ?>
				</td>
			</tr>
			<tr>
				<td>
					<h3>
						<?php esc_html_e( 'Your API token:', 'tinypass' ) ?>
					</h3>
				</td>
				<td>
					<?php echo esc_html( $this::$api_token ) ?>
				</td>
			</tr>
			<tr>
				<td>
					<h3>
						<?php esc_html_e( 'Environment', 'tinypass' ) ?>
					</h3>
				</td>
				<td>
					<?php if ( $this::$mode == TinypassConfig::MODE_PRODUCTION ): ?>
						<?php esc_html_e( 'Production', 'tinypass' ) ?>
					<?php elseif ( $this::$mode == TinypassConfig::MODE_SANDBOX ): ?>
						<?php esc_html_e( 'Sandbox', 'tinypass' ) ?>
					<?php elseif ( $this::$mode == TinypassConfig::MODE_CUSTOM ): ?>
						<?php echo esc_html( $this::$dedicated ) ?>
					<?php endif ?>
				</td>
			</tr>
			<?php if ( $this::$business_model == TinypassConfig::BUSINESS_MODEL_METERED ): ?>
				<tr>
					<td>
						<h3>
							<?php esc_html_e( 'Your paywall id:', 'tinypass' ) ?>
						</h3>
					</td>
					<td>
						<?php echo esc_html( $this::$paywall_id ) ?>
					</td>
				</tr>
			<?php endif ?>
			<tr>
				<td>
					<h3>
						<?php esc_html_e( 'Business model', 'tinypass' ) ?>
					</h3>
				</td>
				<td>
					<?php if ( $this::$business_model == TinypassConfig::BUSINESS_MODEL_METERED ): ?>
						<?php esc_html_e( 'Metered paywall', 'tinypass' ) ?>
					<?php elseif ( $this::$business_model == TinypassConfig::BUSINESS_MODEL_SUBSCRIPTION ): ?>
						<?php esc_html_e( 'Hard / keyed paywall', 'tinypass' ) ?>
					<?php endif ?>
				</td>
			</tr>
			<tr>
				<td>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $this::SETTINGS_PAGE_SLUG . '&amp;reset=0' ) ); ?>"><?php esc_html_e( 'Reset settings', 'tinypass' ) ?></a>
				</td>
				<td></td>
			</tr>
		</table>

	</div>
</div>
<?php
/**
 * Template for selecting the business model
 * Rendered by @see WPTinypassAdmin
 * @var WPTinypassAdmin $this
 */
?>
<form method="POST" action="options.php">
	<?php
	settings_fields( 'tinypass-settings-section' );
	?>
	<div class="postbox">
		<div class="inside">
			<table>
				<tr>
					<td>
						<h3><label
								for="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_APP_ID ) ) ?>"><?php esc_html_e( 'Your application id*:', 'tinypass' ) ?></label>
						</h3>
					</td>
					<td>
						<input type="text"
						       name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_APP_ID ) ) ?>"
						       id="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_APP_ID ) ) ?>"
						       value="<?php echo esc_attr( $this::$app_id ) ?>"/>
					</td>
				</tr>
				<tr>
					<td>
						<h3><label
								for="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_API_TOKEN ) ) ?>"><?php esc_html_e( 'Your API token*:', 'tinypass' ) ?></label>
						</h3>
					</td>
					<td>
						<input type="text"
						       name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_API_TOKEN ) ) ?>"
						       id="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_API_TOKEN ) ) ?>"
						       value="<?php echo esc_attr( $this::$api_token ) ?>"/>
					</td>
				</tr>
				<tr>
					<td>
						<h3><label
								for="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_MODE ) ) ?>"><?php esc_html_e( 'Environment', 'tinypass' ) ?></label>
						</h3>
					</td>
					<td>
						<select name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_MODE ) ) ?>"
						        id="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_MODE ) ) ?>"
						        class="tinypass-dynamic-display"
						        tinypass-dynamic-display="<?php echo esc_attr( TinypassConfig::MODE_CUSTOM ) ?>"
						        rel="#<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_DEDICATED_ENVIRONMENT ) ) ?>">
							<option value="<?php echo esc_attr( TinypassConfig::MODE_PRODUCTION ) ?>"
							        <?php if ($this::$mode == TinypassConfig::MODE_PRODUCTION): ?>selected<?php endif ?>><?php esc_html_e( 'Production', 'tinypass' ) ?></option>
							<option value="<?php echo esc_attr( TinypassConfig::MODE_SANDBOX ) ?>"
							        <?php if (( $this::$mode == TinypassConfig::MODE_SANDBOX ) || ! ( $this::$mode )): ?>selected<?php endif ?>><?php esc_html_e( 'Sandbox', 'tinypass' ) ?></option>
							<option value="<?php echo esc_attr( TinypassConfig::MODE_CUSTOM ) ?>"
							        <?php if ($this::$mode == TinypassConfig::MODE_CUSTOM): ?>selected<?php endif ?>><?php esc_html_e( 'Dedicated', 'tinypass' ) ?></option>
						</select>
						<?php // This block will only be visible when environment is set to "dedicated" ?>
						<input style="display: none;" type="text"
						       name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_DEDICATED_ENVIRONMENT ) ) ?>"
						       id="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_DEDICATED_ENVIRONMENT ) ) ?>"
						       value="<?php echo esc_url( $this::$dedicated ) ?>"
						       placeholder="<?php esc_attr_e( 'Your environment url', 'tinypass' ) ?>"/>
					</td>
				</tr>
				<tr id="tr<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_PAYWALL_ID ) ) ?>"
				    style="display: none">
					<td>
						<h3><label
								for="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_PAYWALL_ID ) ) ?>"><?php esc_html_e( 'Your paywall id:', 'tinypass' ) ?>
							</label>
						</h3>
					</td>
					<td>
						<input type="text"
						       name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_PAYWALL_ID ) ) ?>"
						       id="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_PAYWALL_ID ) ) ?>"
						       value="<?php echo esc_attr( $this::$paywall_id ) ?>"/>
					</td>
				</tr>
			</table>
			<p>
				<?php esc_html_e( '* Login to the Tinypass dashboard to find out your application id and API token.', 'tinypass' ) ?>
			</p>

			<h3><?php esc_html_e( 'Select which business model you are implementing', 'tinypass' ) ?></h3>
			<ul>
				<li>
					<label>
						<input type="radio"
						       name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_BUSINESS_MODEL ) ) ?>"
						       value="<?php echo esc_attr( TinypassConfig::BUSINESS_MODEL_METERED ) ?>"
							<?php checked( $this::$business_model == TinypassConfig::BUSINESS_MODEL_METERED ) ?>
						       class="tinypass-dynamic-display"
						       tinypass-dynamic-display="<?php echo esc_attr( TinypassConfig::BUSINESS_MODEL_METERED ) ?>"
						       rel="#tr<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_PAYWALL_ID ) ) ?>"
							/>
						<strong><?php esc_html_e( 'Metered paywall', 'tinypass' ) ?></strong>&nbsp;&mdash;&nbsp;<?php esc_html_e( 'A metered paywall restricts access to content after a user has exceeded the amount of free page views in a given time period.', 'tinypass' ) ?>
					</label>
				</li>
				<li>
					<label>
						<input type="radio"
						       name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_BUSINESS_MODEL ) ) ?>"
						       value="<?php echo esc_attr( TinypassConfig::BUSINESS_MODEL_SUBSCRIPTION ) ?>"
							<?php checked( $this::$business_model == TinypassConfig::BUSINESS_MODEL_SUBSCRIPTION ) ?>
						       class="tinypass-dynamic-display"
						       tinypass-dynamic-display="<?php echo esc_attr( TinypassConfig::BUSINESS_MODEL_METERED ) ?>"
						       rel="#tr<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_PAYWALL_ID ) ) ?>"
							/>
						<strong><?php esc_html_e( 'Hard / keyed paywall', 'tinypass' ) ?></strong>&nbsp;&mdash;&nbsp;<?php esc_html_e( 'A hard or keyed paywall restricts access to specified pieces of content that are designated as premium.', 'tinypass' ) ?>
					</label>
				</li>
			</ul>
			<br/>

			<p>
				<i><?php esc_html_e( 'These settings cannot be changed once they are saved. To change environment or business model use "Reset settings" link or re-install the plugin.', 'tinypass' ) ?></i>
			</p>
			<?php
			submit_button( __( 'Next', 'tinypass' ) );
			?>
		</div>
	</div>

</form>
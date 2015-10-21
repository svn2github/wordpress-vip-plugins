<?php
/**
 * Template for content settings when the business model is set to "metered paywall"
 * Rendered by @see WPTinypassAdmin
 * @var TinypassContentSettings $contentSettings
 * @var bool $isSettingsPage Is template being rendered from settings page
 * Rendered by @see WPTinypassAdmin
 * @var WPTinypassAdmin $this
 */
?>
<li>
	<label>
		<input type="radio"
		       name="<?php echo esc_attr( ( $isSettingsPage ? $this::getOptionName( $this::OPTION_NAME_DEFAULT_ACCESS_SETTINGS ) : $this::META_NAME ) . '[' . $contentSettings->chargeOptionPropertyName() . ']' ) ?>"
		       value="<?php echo esc_attr( $contentSettings::CHARGE_OPTION_ALWAYS ) ?>"
			<?php checked( $contentSettings->chargeOption() == $contentSettings::CHARGE_OPTION_ALWAYS ) ?> />
		<b><?php esc_html_e( 'Always', 'tinypass' ) ?></b> &mdash; <?php esc_html_e( 'Always charge for this content regardless of metered paywall state', 'tinypass' ) ?>
	</label>
</li>
<li>
	<label>
		<input type="radio"
		       name="<?php echo esc_attr( ( $isSettingsPage ? $this::getOptionName( $this::OPTION_NAME_DEFAULT_ACCESS_SETTINGS ) : $this::META_NAME ) . '[' . $contentSettings->chargeOptionPropertyName() . ']' ) ?>"
		       value="<?php echo esc_attr( $contentSettings::CHARGE_OPTION_METERED ) ?>"
			<?php checked( $contentSettings->chargeOption() == $contentSettings::CHARGE_OPTION_METERED ) ?> />
		<b><?php esc_html_e( 'Metered', 'tinypass' ) ?></b> &mdash; <?php esc_html_e( 'Meter this content (will only ask to pay for users over their metered threshold)', 'tinypass' ) ?>
	</label>
</li>
<li>
	<label>
		<input type="radio"
		       name="<?php echo esc_attr( ( $isSettingsPage ? $this::getOptionName( $this::OPTION_NAME_DEFAULT_ACCESS_SETTINGS ) : $this::META_NAME ) . '[' . $contentSettings->chargeOptionPropertyName() . ']' ) ?>"
		       value=""
			<?php checked( ! $contentSettings->chargeOption() ) ?> />
		<b><?php esc_html_e( 'Never', 'tinypass' ) ?></b> &mdash; <?php esc_html_e( 'Never charge for this content (will never present the paywall)', 'tinypass' ) ?>
	</label>
</li>
<?php

/**
 * Template for content settings when the business model is set to "hard / keyed paywall"
 * @var TinypassResource[] $resources
 * @var array $currencies
 * @var TinypassContentSettings $contentSettings
 * @var bool $isSettingsPage Is template being rendered from settings page
 * Rendered by @see WPTinypassAdmin
 * @var WPTinypassAdmin $this
 */
?>
<?php if ( $this::$tinypass->algorithmicKeyAvailable() ): ?>
	<li>
		<label>
			<input type="radio"
			       name="<?php echo esc_attr( ( $isSettingsPage ? $this::getOptionName( $this::OPTION_NAME_DEFAULT_ACCESS_SETTINGS ) : $this::META_NAME ) . '[' . $contentSettings->chargeOptionPropertyName() . ']' ) ?>"
			       value="<?php echo esc_attr( $contentSettings::CHARGE_OPTION_ALGORITHMIC ) ?>"
			       class="tinypass-subscription-selector"
			       rel="#<?php echo esc_attr( $contentSettings->chargeOptionPropertyName() ) ?>-<?php echo esc_attr( $contentSettings::CHARGE_OPTION_ALGORITHMIC ) ?>"
				<?php checked( $contentSettings->chargeOption() == $contentSettings::CHARGE_OPTION_ALGORITHMIC ) ?> />
			<b><?php esc_html_e( 'Algorithmic keying', 'tinypass' ) ?></b> &mdash; <?php esc_html_e( 'Algorithm will determine whether to restrict this content', 'tinypass' ) ?>
		</label>
		<?php $chargeOption = $contentSettings::CHARGE_OPTION_ALGORITHMIC ?>
		<?php require( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'views/admin/_subscription_resources.php' ); ?>
	</li>
<?php endif ?>
<li>
	<label>
		<input type="radio"
		       name="<?php echo esc_attr( ( $isSettingsPage ? $this::getOptionName( $this::OPTION_NAME_DEFAULT_ACCESS_SETTINGS ) : $this::META_NAME ) . '[' . $contentSettings->chargeOptionPropertyName() . ']' ) ?>"
		       value="<?php echo esc_attr( $contentSettings::CHARGE_OPTION_SUBSCRIPTION ) ?>"
		       class="tinypass-subscription-selector"
		       rel="#<?php echo esc_attr( $contentSettings->chargeOptionPropertyName() ) ?>-<?php echo esc_attr( $contentSettings::CHARGE_OPTION_SUBSCRIPTION ) ?>"
			<?php checked( $contentSettings->chargeOption() == $contentSettings::CHARGE_OPTION_SUBSCRIPTION ) ?> />
		<b><?php esc_html_e( 'Always keyed', 'tinypass' ) ?></b> &mdash; <?php esc_html_e( 'Only subscribers can view this content', 'tinypass' ) ?>
	</label>

	<?php $chargeOption = $contentSettings::CHARGE_OPTION_SUBSCRIPTION ?>
	<?php require( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . 'views/admin/_subscription_resources.php' ); ?>
</li>
<li>
	<label>
		<input type="radio"
		       name="<?php echo esc_attr( ( $isSettingsPage ? $this::getOptionName( $this::OPTION_NAME_DEFAULT_ACCESS_SETTINGS ) : $this::META_NAME ) . '[' . $contentSettings->chargeOptionPropertyName() . ']' ) ?>"
		       value=""
		       class="tinypass-subscription-selector"
			<?php checked( ! $contentSettings->chargeOption() ) ?> />
		<b><?php esc_html_e( 'Never keyed', 'tinypass' ) ?></b> &mdash; <?php esc_html_e( 'Anyone can view this content', 'tinypass' ); ?>
	</label>
</li>
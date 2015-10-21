<?php
/**
 * Template for resources checkboxes when the business model is set to "hard / keyed paywall"
 * @var TinypassResource[] $resources
 * @var array $currencies
 * @var TinypassContentSettings $contentSettings
 * @var bool $isSettingsPage Is template being rendered from settings page
 * @var string $chargeOption charge option for which the template is being rendered
 * Rendered by @see WPTinypassAdmin
 * @var WPTinypassAdmin $this
 */
?>
<div
	class="inside"
	id="<?php echo esc_attr( $contentSettings->chargeOptionPropertyName() ) ?>-<?php echo esc_attr( $chargeOption ) ?>">
	<ul>
		<?php if ( empty( $resources ) ): ?>
			<li class="tinypass-no-resources-notice">
				<i><?php esc_html_e( 'No resources were enabled.', 'tinypass' ) ?> <a
						href="<?php echo esc_url( admin_url( 'admin.php?page=' . $this::SETTINGS_RESOURCES_PAGE_SLUG ) ) ?>"><?php esc_html_e( 'Manage resources', 'tinypass' ) ?></a></i>
			</li>
		<?php else: ?>
			<li><?php esc_html_e( 'Users need to have access to one of these', 'tinypass' ) ?> <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $this::SETTINGS_RESOURCES_PAGE_SLUG ) ) ?>">resources</a>
			</li>
			<?php foreach ( $resources as $resource ): ?>
				<li>
					<label>
						<input
							type="checkbox"
							name="<?php echo esc_attr( ( $isSettingsPage ? $this::getOptionName( $this::OPTION_NAME_DEFAULT_ACCESS_SETTINGS ) : $this::META_NAME ) . '[' . $chargeOption . ']' . '[' . $contentSettings->resourceIdsPropertyName() . ']' ) ?>[]"
							value="<?php echo esc_attr( $resource->rid() ) ?>" <?php checked( in_array( $resource->rid(), $contentSettings->resourceIds() ) || ( ( count( $resources ) == 1 ) && ! $this::$enable_ppp ) ) ?>/>
						<?php echo esc_html( $resource->name() ) ?>
					</label>
				</li>
			<?php endforeach ?>
		<?php endif ?>
		<?php if ( $isSettingsPage || $this::$enable_ppp ): ?>
			<li class="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_ENABLE_PPP ) ) ?>" <?php if ( ! $this::$enable_ppp ): ?> style="display: none;" <?php endif; ?>>
				<label>
					<input
						type="checkbox"
						name="<?php echo esc_attr( ( $isSettingsPage ? $this::getOptionName( $this::OPTION_NAME_DEFAULT_ACCESS_SETTINGS ) : $this::META_NAME ) . '[' . $chargeOption . ']' . '[' . $contentSettings->resourceIdsPropertyName() . ']' ) ?>[]"
						value="<?php echo esc_attr( $contentSettings::PPP_RESOURCE_SLUG ) ?>" <?php checked( in_array( $contentSettings::PPP_RESOURCE_SLUG, $contentSettings->resourceIds() ) ) ?> />
					<?php esc_html_e( 'Charge ', 'tinypass' ) ?><input
						name="<?php echo esc_attr( ( $isSettingsPage ? $this::getOptionName( $this::OPTION_NAME_DEFAULT_ACCESS_SETTINGS ) : $this::META_NAME ) . '[' . $chargeOption . ']' . '[' . $contentSettings->pppPricePropertyName() . ']' ) ?>"
						value="<?php echo esc_attr( $contentSettings->pppPrice() ) ?>" size="2"/>
					<select
						name="<?php echo esc_attr( ( $isSettingsPage ? $this::getOptionName( $this::OPTION_NAME_DEFAULT_ACCESS_SETTINGS ) : $this::META_NAME ) . '[' . $chargeOption . ']' . '[' . $contentSettings->pppCurrencyPropertyName() . ']' ) ?>">
						<?php foreach ( $currencies as $currencyCode => $currencyName ): ?>
							<option
								value="<?php echo esc_attr( $currencyCode ) ?>" <?php selected( ( ! $contentSettings->pppCurrency() && ( $currencyCode == 'USD' ) ) || ( $contentSettings->pppCurrency() == $currencyCode ) ) ?>><?php echo esc_html( $currencyCode ) ?></option>
						<?php endforeach ?>
					</select>
					<?php esc_html_e( ' for this individual piece of content', 'tinypass' ); ?>
				</label>
			</li>
		<?php endif ?>
	</ul>
</div>
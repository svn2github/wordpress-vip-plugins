<?php
/**
 * Template for prices editor page
 * @var $resources TinypassResource[]
 * Rendered by @see WPTinypassAdmin
 * @var WPTinypassAdmin $this
 */
?>
<form method="POST" action="options.php">
	<?php
	settings_fields( 'tinypass-resources-section' );
	?>
	<div class="postbox tinypass-resource-editor tinypass-tabbed-postbox"
	     id="tinypass-resource-editor">
		<div class="inside">
			<?php if ( ! count( $resources ) ): ?>
				<h3><?php esc_html_e( 'Couldn\'t load any terms from the API.', 'tinypass' ) ?></h3>
				<p><?php esc_html_e( 'Check the configuration in your tinypass dashboard.', 'tinypass' ) ?></p>
			<?php else: ?>
				<?php
				submit_button();
				?>
				<table class="tinypass-resource-table">
					<tr>
						<td colspan="3" width="100%">
							<h3><?php esc_html_e( 'Resources' ) ?></h3>
						</td>
						<td colspan="2">

						</td>
					</tr>
					<?php foreach ( $resources as $resource ): ?>
						<?php $terms = $resource->terms() ?>
						<tr class="tinypass-resource-row <?php if ( empty( $terms ) ): ?>tinypass-resource-row-noterms<?php endif ?>">
							<td class="tinypass-resource-image-cell">
								<div>
									<?php if ( $resource->imageUrl() ): ?>
										<img width="100%" height="100%"
										     src="<?php echo esc_url( $this::$tinypass->baseImgURL() . $resource->imageUrl() ) ?>">
									<?php endif ?>
								</div>
							</td>
							<td colspan="2" width="100%"
							    class="tinypass-resource-description-cell">
								<h3><?php echo esc_html( $resource->name() ) ?></h3>

								<p><?php echo esc_html( $resource->description() ) ?></p>
							</td>
						</tr>
						<?php if ( ! empty( $terms ) ): ?>
							<tr class="tinypass-terms-separator-row">
								<td>

								</td>
								<td colspan="4"
								    class="tinypass-terms-separator-cell">
									<span><?php esc_html_e( 'TERMS' ) ?></span>
								</td>
							</tr>

							<?php foreach ( $terms as $term ): ?>
								<tr class="tinypass-term-row">
									<td></td>
									<td>
										<input type="checkbox"
										       name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_RESOURCES ) ) ?>[<?php echo esc_attr( $resource->rid() ) ?>][<?php echo esc_attr( $resource->termsPropertyName() ) ?>][<?php echo esc_attr( $term->id() ) ?>][<?php echo esc_attr( $term->isEnabledPropertyName() ) ?>]"
											<?php checked( $term->isEnabled() ) ?>
											   value="1"/>
									</td>
									<td>
										<span><strong><?php echo esc_html( $term->name() ) ?></strong> <?php echo esc_html( $term->billingPlanDescription() ) ?></span>

										<p><?php echo esc_html( $term->description() ) ?></p>
									</td>
								</tr>
							<?php endforeach ?>
						<?php endif ?>
					<?php endforeach ?>
				</table>
				<?php
				submit_button();
				?>
			<?php endif ?>
		</div>
	</div>
</form>


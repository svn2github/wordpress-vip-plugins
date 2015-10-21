<?php
/**
 * Template for batch editing, when the business model is "metered paywall"
 * Rendered by @see WPTinypassAdmin
 * @var WPTinypassAdmin $this
 * @var TinypassContentSettings $contentSettings
 */
?>
<fieldset class="inline-edit-col-right bulk-edit-tinypas">
	<div class="inline-edit-col column-tinypass">
		<h4 class="title"><?php esc_html_e( 'tinypass', 'tinypass' ) ?></h4>
		<ul class="cat-checklist category-checklist">
			<li>
				<label>
					<input type="radio"
					       name="<?php echo esc_attr( $this::META_NAME . '[' . $contentSettings->chargeOptionPropertyName() . ']' ) ?>"
					       value="-1" <?php checked( true ) ?>/>
					<b><?php esc_html_e( '— No Change —', 'tinypass' ) ?></b>
				</label>
			</li>
			<?php // Include the template with settings ?>
			<?php require( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . '/views/admin/_metered_list_items.php' ); ?>
		</ul>
	</div>
</fieldset>
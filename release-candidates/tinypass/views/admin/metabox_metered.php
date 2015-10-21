<?php
/**
 * Template for metabox when the business model is set to "metered paywall"
 * @var $postMeta array
 * @var $isSettingsPage bool Is template being rendered from settings page
 * Rendered by @see WPTinypassAdmin
 */
?>
<p><?php esc_html_e( 'Choose if and when to restrict this content:', 'tinypass' ) ?></p>
<ul>
	<?php // Include the template with settings ?>
	<?php require( plugin_dir_path( TINYPASS_PLUGIN_FILE_PATH ) . '/views/admin/_metered_list_items.php' ); ?>
</ul>
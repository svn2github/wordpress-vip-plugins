<?php
/**
 * The page is normally added under "Plugins"
 */
add_action( 'admin_init', function() {
	global $image_metadata_cruncher;

	add_options_page(
			'Image Metadata Cruncher',
			'Image Metadata Cruncher',
			'manage_options',
			"{$image_metadata_cruncher->prefix}-options",
			array( $image_metadata_cruncher, 'options_cb' )
		);
});
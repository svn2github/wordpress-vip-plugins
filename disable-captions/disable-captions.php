<?php
/*
Plugin Name: Disable Image Captions
Plugin URI: http://wordpress.com
Description: Disables WordPress image captions support.
Version: 1.0
Author: wordpress.com
Author URI: http://wordpress.com
*/

function rename_attachment_fields_to_edit($form_fields) {

	if ( is_array($form_fields) && is_array($form_fields['post_excerpt']) ) {
		$form_fields['post_excerpt']['label'] = __('Alternate Text');
		$form_fields['post_excerpt']['helps'] = array();
		$form_fields['post_excerpt']['helps'][] = __('Alt text for the image, e.g. "The Mona Lisa"');
	}

	return $form_fields;
}
add_filter('attachment_fields_to_edit', 'rename_attachment_fields_to_edit', 11);


function disable_caption_shortcode($a, $b, $content) {
	return "<p>$content</p>";
}
add_filter('img_caption_shortcode', 'disable_caption_shortcode', 10, 3);


function disable_img_captions() {
	return 1;
}
add_filter( 'disable_captions', 'disable_img_captions' );

?>
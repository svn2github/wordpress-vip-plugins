<?php
/*
 * Watermarks all future JPEG and PNG uploads to your site.
 *
 * Uses /your-theme/images/watermark.png as the watermark. A PNG is required
 * so that you can have watermark transparency if you wish.
 *
 * By default, the watermark is placed in the bottom-right of the original
 * image with a 5px padding. However you can use the two filters to move
 * the watermark to a different location. Example for bottom-left:
 *
 * add_filter( 'wpcom_watermark_uploads_destx', 'your_function_x', 10, 3 );
 * add_filter( 'wpcom_watermark_uploads_desty', 'your_function_y', 10, 3 );
 * function your_function_x( $dest_x, $image_width, $watermark_width ) {
 *		return 5; // 5px from left side
 * }
 * function your_function_y( $dest_y, $image_height, $watermark_height ) {
 *		return $image_height - $watermark_height - 5; // 5px from bottom
 * }
 *
 * The numbers are relative to the upper-left corner of the original image.
 *
 * To use this plugin, add the following to your theme's functions.php file:
 *
 * include_once( WP_CONTENT_DIR . '/themes/vip/plugins/watermark-uploads/watermark-uploads.php' );
 *
 * @author Alex M.
 */

add_filter( 'wp_handle_upload_prefilter', 'wpcom_watermark_uploads', 100 );

function wpcom_watermark_uploads( $file ) {

	// Make sure the upload is valid
	if ( 0 == $file['error'] && is_uploaded_file( $file['tmp_name'] ) ) {

		// Load the image into $image
		switch ( $file['type'] ) {
			case 'image/jpeg':
				if ( !$image = @imagecreatefromjpeg( $file['tmp_name'] ) )
					return $file;

				// Get the JPEG quality setting of the original image
				// See http://blog.apokalyptik.com/2009/09/16/quality-time-with-your-jpegs/
				if ( !function_exists('get_jpeg_quality') )
					include_once( WP_PLUGIN_DIR . '/wpcom-images/libjpeg.php' );
				if ( function_exists('get_jpeg_quality') && $imagecontent = file_get_contents( $file['tmp_name'] ) )
					$quality = get_jpeg_quality( $imagecontent );
				if ( empty($quality) )
					$quality = 100;
				break;

			case 'image/png':
				if ( !$image = @imagecreatefrompng( $file['tmp_name'] ) )
					return $file;
				break;

			// Only JPEGs and PNGs are supported
			default;
				return $file;
		}

		// Load the watermark into $watermark
		$watermark_path = STYLESHEETPATH . '/images/watermark.png';
		if ( !file_exists( $watermark_path ) || !$watermark = @imagecreatefrompng( $watermark_path ) )
			return $file;

		// Get the original image dimensions
		$image_width  = imagesx( $image );
		$image_height = imagesy( $image );

		// Get the watermark dimensions
		$watermark_width  = imagesx( $watermark );
		$watermark_height = imagesy( $watermark );

		// Calculate watermark location (see top of file for help with these filters)
		$dest_x = (int) apply_filters( 'wpcom_watermark_uploads_destx', $image_width - $watermark_width - 5, $image_width, $watermark_width );
		$dest_y = (int) apply_filters( 'wpcom_watermark_uploads_desty', $image_height - $watermark_height - 5, $image_height, $watermark_height );

		// Copy the watermark onto the original image
		imagecopy( $image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height );

		imagedestroy( $watermark );

		// Save the new watermarked image
		switch ( $file['type'] ) {
			case 'image/jpeg':
				imagejpeg( $image, $file['tmp_name'], $quality );
			case 'image/png':
				imagepng( $image, $file['tmp_name'] );
		}

		imagedestroy( $image );
	}

	return $file;
}

?>
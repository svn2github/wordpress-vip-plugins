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
 *
 * Plugin Name: WordPress.com Watermark Uploads
 */

class WPcom_Watermark_Uploads {

	// Class initialization
	function __construct() {
		add_filter( 'wp_handle_upload_prefilter', array(&$this, 'handle_file'), 100 );
		add_filter( 'wp_upload_bits_data',        array(&$this, 'handle_bits'), 10, 2 );
	}

	// For filters that pass a $_FILES array
	function handle_file( $file ) {
		// Make sure the upload is valid
		if ( 0 == $file['error'] && is_uploaded_file( $file['tmp_name'] ) ) {

			// Load the image into $image
			switch ( $file['type'] ) {
				case 'image/jpeg':
					if ( !$image = @imagecreatefromjpeg( $file['tmp_name'] ) ) {
						$this->error( '[File] Watermark: Failed to load JPEG image.' );
						return $file;
					}

					// Get the JPEG quality setting of the original image
					if ( $imagecontent = @file_get_contents( $file['tmp_name'] ) )
						$quality = $this->get_jpeg_quality_wrapper( $imagecontent );
					if ( empty($quality) )
						$quality = 100;

					break;

				case 'image/png':
					if ( !$image = @imagecreatefrompng( $file['tmp_name'] ) ) {
						$this->error( '[File] Watermark: Failed to load PNG image.' );
						return $file;
					}
					break;

				// Only JPEGs and PNGs are supported
				default;
					$this->error( '[File] Watermark: Not a PNG or JPEG.' );
					return $file;
			}

			// Run the $image through the watermarker
			$image = $this->watermark( $image );

			// Save the new watermarked image
			switch ( $file['type'] ) {
				case 'image/jpeg':
					imagejpeg( $image, $file['tmp_name'], $quality );
				case 'image/png':
					imagepng( $image, $file['tmp_name'] );
			}

			imagedestroy( $image );
		} else {
			$this->error( '[File] Watermark: Invalid tmp file.' );
		}

		return $file;
	}

	// For filters that pass the image as a string
	function handle_bits( $bits, $file ) {

		// Figure out image type based on filename
		$wp_filetype = wp_check_filetype( $file );
		switch ( $wp_filetype['ext'] ) {
			case 'png':
				$type = 'png';
				break;
			case 'jpg':
			case 'jpeg':
				$type = 'jpg';
				break;
			default;
				$this->error( '[Bits] Watermark: Invalid extension.' );
				return $bits;
		}

		// Convert the $bits into an $image
		if ( !$image = imagecreatefromstring( $bits ) ) {
			$this->error( '[Bits] Watermark: Failed to convert bits to image.' );
			return $bits;
		}

		// Run the $image through the watermarker
		$image = $this->watermark( $image );

		// Get the $image back into a string
		ob_start();
		if ( 'png' == $type ) {
			if ( !imagepng( $image ) ) {
				ob_end_clean();
				$this->error( '[Bits] Watermark: Failed to output PNG.' );
				return $bits;
			}
		} elseif ( 'jpg' == $type ) {
			// Get the JPEG quality setting of the original image
			$quality = $this->get_jpeg_quality_wrapper( $bits );
			if ( empty($quality) )
				$quality = 100;

			if ( !imagejpeg( $image, null, $quality ) ) {
				ob_end_clean();
				$this->error( '[Bits] Watermark: Failed to output JPEG.' );
				return $bits;
			}
		}
		$bits = ob_get_contents();
		ob_end_clean();

		imagedestroy( $image );

		return $bits;
	}

	// Watermarks an $image
	function watermark( $image ) {

		// Load the watermark into $watermark
		$watermark_path = STYLESHEETPATH . '/images/upload-watermark.png';
		if ( !file_exists( $watermark_path ) || !$watermark = @imagecreatefrompng( $watermark_path ) ) {
			$this->error( 'Watermark: Failed to load watermark image file.' );
			return $image;
		}

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

		return $image;
	}

	// Safety wrapper for our get_jpeg_quality() function
	// See http://blog.apokalyptik.com/2009/09/16/quality-time-with-your-jpegs/
	function get_jpeg_quality_wrapper( $imagecontent ) {

		$quality = false;

		if ( !function_exists('get_jpeg_quality') )
			@include_once( WP_PLUGIN_DIR . '/wpcom-images/libjpeg.php' );
		if ( function_exists('get_jpeg_quality') )
			$quality = get_jpeg_quality( $imagecontent );

		return $quality;
	}

	// Report any errors to me, but only while on my sandbox. Does nothing otherwise.
	function error( $message ) {
		if ( function_exists('im') && defined('VIPER_SANDBOX') && VIPER_SANDBOX )
			im( '[' . STYLESHEETPATH . '] ' . $message );
	}
}

// Start this plugin once everything else is loaded up
add_action( 'init', 'WPcom_Watermark_Uploads', 5 );
function WPcom_Watermark_Uploads() {
	global $WPcom_Watermark_Uploads;
	$WPcom_Watermark_Uploads = new WPcom_Watermark_Uploads();
}

?>
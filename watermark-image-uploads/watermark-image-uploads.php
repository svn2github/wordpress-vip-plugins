<?php
/*
 *
 * For help with this plugin, see http://lobby.vip.wordpress.com/plugins/watermark-image-uploads/
 *
 * Plugin Name: WordPress.com Watermark Image Uploads
 * Author:      Alex Mills
 * Author URI:  http://automattic.com/
 */

class WPcom_Watermark_Uploads {

	public $watermark;

	// Class initialization
	function __construct() {

		$this->watermark = apply_filters( 'wpcom_watermark_image', STYLESHEETPATH . '/images/upload-watermark.png' );
		if ( ! file_exists( $this->watermark ) )
			return false;

		add_filter( 'wp_handle_upload_prefilter', array( &$this, 'handle_file' ), 100 );
		add_filter( 'wp_upload_bits_data',        array( &$this, 'handle_bits' ), 10, 2 ); // http://core.trac.wordpress.org/ticket/12493
	}

	// For filters that pass a $_FILES array
	public function handle_file( $file ) {

		if ( false === apply_filters( 'wpcom_watermark_enabled', true ) )
			return $file;

		// Make sure the upload is valid
		if ( 0 == $file['error'] && is_uploaded_file( $file['tmp_name'] ) ) {

			// Check file extension (can't use $file['type'] due to Flash uploader sending application/octet-stream)
			if ( !$type = $this->get_type( $file['name'] ) ) {
				return $file;
			}

			// Load the image into $image
			switch ( $type ) {
				case 'jpeg':
					if ( !$image = @imagecreatefromjpeg( $file['tmp_name'] ) ) {
						return $file;
					}

					// Get the JPEG quality setting of the original image
					if ( $imagecontent = @file_get_contents( $file['tmp_name'] ) )
						$quality = $this->get_jpeg_quality_wrapper( $imagecontent );
					if ( empty($quality) )
						$quality = 100;

					break;

				case 'png':
					if ( !$image = @imagecreatefrompng( $file['tmp_name'] ) ) {
						return $file;
					}
					break;

				default;
					return $file;
			}

			// Run the $image through the watermarker
			$image = $this->watermark( $image );

			// Save the new watermarked image
			switch ( $type ) {
				case 'jpeg':
					imagejpeg( $image, $file['tmp_name'], $quality );
				case 'png':
					imagepng( $image, $file['tmp_name'] );
			}

			imagedestroy( $image );
		}

		return $file;
	}

	// For filters that pass the image as a string
	public function handle_bits( $bits, $file ) {

		if ( false === apply_filters( 'wpcom_watermark_enabled', true ) )
			return $bits;

		// Check file extension
		if ( ! $type = $this->get_type( $file ) ) {
			return $bits;
		}

		// Convert the $bits into an $image
		if ( ! $image = imagecreatefromstring( $bits ) ) {
			return $bits;
		}

		// Run the $image through the watermarker
		$image = $this->watermark( $image );

		// Get the $image back into a string
		ob_start();
		switch ( $type ) {
			case 'jpeg':
				// Get the JPEG quality setting of the original image
				$quality = $this->get_jpeg_quality_wrapper( $bits );
				if ( empty($quality) )
					$quality = 100;

				if ( !imagejpeg( $image, null, $quality ) ) {
					ob_end_clean();
					return $bits;
				}
				break;
			case 'png':
				if ( !imagepng( $image ) ) {
					ob_end_clean();
					return $bits;
				}
				break;

			default;
				ob_end_clean();
				return $bits;
		}
		$bits = ob_get_contents();
		ob_end_clean();

		imagedestroy( $image );

		return $bits;
	}

	// Watermarks an $image
	public function watermark( $image ) {

		// Load the watermark into $watermark
		if ( ! $watermark = @imagecreatefrompng( $this->watermark ) ) {
			return $image;
		}

		// Get the original image dimensions
		$image_width  = imagesx( $image );
		$image_height = imagesy( $image );

		// Get the watermark dimensions
		$watermark_width  = imagesx( $watermark );
		$watermark_height = imagesy( $watermark );

		// Calculate watermark location (see docs for help with these filters)
		$dest_x = (int) apply_filters( 'wpcom_watermark_uploads_destx', $image_width - $watermark_width - 5, $image_width, $watermark_width );
		$dest_y = (int) apply_filters( 'wpcom_watermark_uploads_desty', $image_height - $watermark_height - 5, $image_height, $watermark_height );

		// Copy the watermark onto the original image
		imagecopy( $image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height );

		imagedestroy( $watermark );

		return $image;
	}

	// Safety wrapper for our get_jpeg_quality() function
	// See http://blog.apokalyptik.com/2009/09/16/quality-time-with-your-jpegs/
	public function get_jpeg_quality_wrapper( $imagecontent ) {

		$quality = false;

		if ( ! function_exists( 'get_jpeg_quality' ) )
			@include_once( WP_PLUGIN_DIR . '/wpcom-images/libjpeg.php' );

		if ( function_exists( 'get_jpeg_quality' ) )
			$quality = get_jpeg_quality( $imagecontent );

		return $quality;
	}

	// Figure out image type based on filename
	public function get_type( $filename ) {
		$wp_filetype = wp_check_filetype( $filename );
		switch ( $wp_filetype['ext'] ) {
			case 'png':
				return 'png';
			case 'jpg':
			case 'jpeg':
				return 'jpeg';
			default;
				return false;
		}
	}
}

// Start this plugin once everything else is loaded up
add_action( 'init', 'WPcom_Watermark_Uploads', 5 );
function WPcom_Watermark_Uploads() {
	global $WPcom_Watermark_Uploads;
	$WPcom_Watermark_Uploads = new WPcom_Watermark_Uploads();
}

?>
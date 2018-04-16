<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Handles media discover, upload and library
 *
 * @since      1.2.0
 * @package    StackCommerce_WP
 * @subpackage StackCommerce_WP/includes
 */
class StackCommerce_WP_Media {

	/**
	 * Returns image mime types users are allowed to upload via the API
	 *
	 * @since    1.1.0
	 * @return array
	 */
	protected function allowed_image_mime_types() {
		return array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'bmp'          => 'image/bmp',
			'tiff|tif'     => 'image/tiff',
			'ico'          => 'image/x-icon',
		);
	}

	/**
	 * Upload image from URL
	 *
	 * @since    1.1.0
	 * @param string $image_url
	 * @return array|StackCommerce_WP_Endpoint->response attachment data or error message
	 */
	public function upload_image_from_url( $image_url ) {
		$stackcommerce_wp_endpoint = new StackCommerce_WP_Endpoint();

		$file_name  = basename( current( explode( '?', $image_url ) ) );
		$parsed_url = wp_parse_url( $image_url );

		$errors = array();

		// Check parsed URL.
		if ( ! $parsed_url || ! is_array( $parsed_url ) ) {
			$data       = sprintf( 'Invalid URL %s', $image_url );
			$error_args = array(
				'code'        => 'stackcommerce_wp_invalid_image_url',
				'status_code' => 400,
			);

			$error = array( $data, $error_args );

			$stackcommerce_wp_endpoint->response( $data, $error_args );
			array_push( $errors, $error );
		}

		// Ensure url is valid
		$safe_image_url = esc_url_raw( $image_url );

		// Get the file
		if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
			$response = vip_safe_wp_remote_get( $safe_image_url, array(
				'timeout' => 3,
			) );
		} else {
			$response = wp_safe_remote_get( $safe_image_url, array(
				'timeout' => 3,
			) );
		}

		if ( is_wp_error( $response ) ) {
			$data       = sprintf( 'Error getting remote image %s.', $image_url ) . ' ' . sprintf( 'Error: %s', $response->get_error_message() );
			$error_args = array(
				'code'        => 'stackcommerce_wp_invalid_remote_image_url',
				'status_code' => 400,
			);

			$error = array( $data, $error_args );

			$stackcommerce_wp_endpoint->response( $data, $error_args );
			array_push( $errors, $error );
		} elseif ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$data       = sprintf( 'Error getting remote image %s', $image_url );
			$error_args = array(
				'code'        => 'stackcommerce_wp_invalid_remote_image_url',
				'status_code' => 400,
			);

			$error = array( $data, $error_args );

			$stackcommerce_wp_endpoint->response( $data, $error_args );
			array_push( $errors, $error );
		}

		// Ensure we have a file name and type
		$wp_filetype = wp_check_filetype( $file_name, $this->allowed_image_mime_types() );

		if ( ! $wp_filetype['type'] ) {
			$headers = wp_remote_retrieve_headers( $response );

			if ( isset( $headers['content-disposition'] ) && strstr( $headers['content-disposition'], 'filename=' ) ) {
				$disposition = end( explode( 'filename=', $headers['content-disposition'] ) );
				$disposition = sanitize_file_name( $disposition );
				$file_name   = $disposition;
			} elseif ( isset( $headers['content-type'] ) && strstr( $headers['content-type'], 'image/' ) ) {
				$file_name = 'image.' . str_replace( 'image/', '', $headers['content-type'] );
			}
			unset( $headers );

			// Recheck filetype
			$wp_filetype = wp_check_filetype( $file_name, $this->allowed_image_mime_types() );

			if ( ! $wp_filetype['type'] ) {
				$data       = sprintf( 'Invalid image type: %s', $image_url );
				$error_args = array(
					'code'        => 'stackcommerce_wp_invalid_image_type',
					'status_code' => 400,
				);

				$error = array( $data, $error_args );

				$stackcommerce_wp_endpoint->response( $data, $error_args );
				array_push( $errors, $error );
			}
		}

		// Upload the file
		$upload = wp_upload_bits( $file_name, null, wp_remote_retrieve_body( $response ) );

		if ( $upload['error'] ) {
			$data       = $upload['error'];
			$error_args = array(
				'code'        => 'stackcommerce_wp_image_upload_error',
				'status_code' => 400,
			);

			$error = array( $data, $error_args );

			$stackcommerce_wp_endpoint->response( $data, $error_args );
			array_push( $errors, $error );
		}

		// Get filesize
		$filesize = filesize( $upload['file'] );
		if ( 0 === $filesize ) {
			// @codingStandardsIgnoreLine
			@unlink( $upload['file'] );
			unset( $upload );

			$data       = sprintf( 'Zero size file downloaded: %s', $image_url );
			$error_args = array(
				'code'        => 'stackcommerce_wp_image_upload_file_error',
				'status_code' => 400,
			);

			$error = array( $data, $error_args );

			$stackcommerce_wp_endpoint->response( $data, $error_args );
			array_push( $errors, $error );
		}

		if ( count( $errors ) > 0 ) {
			$upload['error'] = $errors;
		}

		return $upload;
	}

	/**
	 * Set uploaded image as attachment
	 *
	 * @since 1.1.0
	 * @param array $upload Upload information from wp_upload_bits
	 * @param int $id Post ID. Default to 0
	 * @return int Attachment ID
	 */
	public function set_uploaded_image_as_attachment( $upload, $id = 0 ) {
		$stackcommerce_wp_article = new StackCommerce_WP_Article();

		$info        = wp_check_filetype( $upload['file'] );
		$title       = '';
		$content     = '';
		$post_author = $stackcommerce_wp_article->get_admin_fields( 'post_author' );

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/image.php' );
		}

		$image_meta = wp_read_image_metadata( $upload['file'] );

		if ( $image_meta ) {
			if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) ) {
				$title = sanitize_text_field( $image_meta['title'] );
			}

			if ( trim( $image_meta['caption'] ) ) {
				$content = sanitize_text_field( $image_meta['caption'] );
			}
		}

		$attachment = array(
			'post_mime_type' => $info['type'],
			'guid'           => $upload['url'],
			'post_parent'    => $id,
			'post_title'     => $title,
			'post_content'   => $content,
			'post_author'    => $post_author,
		);

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $id );

		if ( ! is_wp_error( $attachment_id ) ) {
			wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
		}

		return $attachment_id;
	}

	/**
	 * Set featured media to a post
	 *
	 * @since    1.0.0
	 */
	public function set_featured_media( $attachment_id, $post_id ) {
		return update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
	}

	/**
	 * Strip first image from post content
	 *
	 * @since    1.0.0
	 */
	public function strip_image( $post_content ) {
		$post_content = preg_replace( '/<img.*?src="([^">]*\/([^">]*?))".*?>/', '', $post_content, 1 );

		return $post_content;
	}

	/**
	 * Get and save all images on article's body
	 *
	 * @since    1.6.5
	 */
	public function process_body_images( $post_content ) {
		$regex           = '/<img.*?src="([^">]*\/([^">]*?))".*?>/';
		$images_regex    = preg_match_all( $regex, $post_content, $matches );
		$images_found    = $matches[1];
		$uploaded_images = [];

		if ( count( $images_found ) > 0 ) {
			foreach ( $images_found as $image ) {

				$upload_image = $this->upload_image_from_url( $image );

				if ( $upload_image['url'] && ! $upload_image['error'] ) {
					$this->set_uploaded_image_as_attachment( $upload_image );

					if ( function_exists( 'wpcom_vip_attachment_url_to_postid' ) ) {
						array_push( $uploaded_images, array(
							'attachment_id' => wpcom_vip_attachment_url_to_postid( $upload_image['url'] ),
							'original'      => $image,
							'upload'        => $upload_image['url'],
						));
					} else {
						array_push( $uploaded_images, array(
							// @codingStandardsIgnoreLine
							'attachment_id' => attachment_url_to_postid( $upload_image['url'] ),
							'original'      => $image,
							'upload'        => $upload_image['url'],
						));
					}
				}
			}
		}

		$processed_post_content = $post_content;
		$attachment_ids         = [];

		foreach ( $uploaded_images as $uploaded_image ) {
			$processed_post_content = str_replace( $uploaded_image['original'], $uploaded_image['upload'], $processed_post_content );

			array_push( $attachment_ids, $uploaded_image['attachment_id'] );
		}

		$new_content = array(
			'attachment_ids' => $attachment_ids,
			'post_content'   => $processed_post_content,
		);

		return $new_content;
	}

	/**
	 * Set post parent for each attachment
	 *
	 * @since    1.6.5
	 */

	public function set_image_parent( $attachment_id = 0, $post_id = 0 ) {
		if ( 'attachment' !== get_post_type( $attachment_id ) || 0 === $attachment_id || 0 === $post_id ) {
			return false;
		}

		wp_update_post( array(
			'ID'          => $attachment_id,
			'post_parent' => $post_id,
		), true );
	}
}

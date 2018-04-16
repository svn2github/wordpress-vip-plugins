<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Validate, sanitize and insert articles
 *
 * @since      1.0.0
 * @package    StackCommerce_WP
 * @subpackage StackCommerce_WP/includes
 */
class StackCommerce_WP_Article {

	const FEATURED_IMAGE_ONLY      = 'featured_image_only';
	const FEATURED_IMAGE_PLUS_BODY = 'featured_image_plus_body';
	const NO_FEATURED_IMAGE        = 'no_featured_image';

	/**
	 * Validate article fields
	 *
	 * @since    1.0.0
	 */
	public function validate( $fields ) {
		$stackcommerce_wp_endpoint = new StackCommerce_WP_Endpoint();

		$errors = array();

		if ( ! array_key_exists( 'post_title', $fields ) || strlen( wp_strip_all_tags( $fields['post_title'] ) ) === 0 ) {
			array_push( $errors, 'Title field cannot be empty.' );
		}

		if ( ! array_key_exists( 'post_content', $fields ) || strlen( $fields['post_content'] ) === 0 ) {
			array_push( $errors, 'Content field cannot be empty.' );
		}

		if ( empty( $errors ) ) {
			$this->check_fields( $fields );
		} else {
			$request_errors = '';

			foreach ( $errors as $error ) {
				$request_errors .= ' ' . $error;
			}

			return $stackcommerce_wp_endpoint->response( $request_errors,
				array(
					'code'        => 'stackcommerce_wp_missing_fields',
					'status_code' => 400,
				)
			);
		}
	}

	/**
	 * Get admin fields
	 *
	 * @since    1.0.0
	 */
	public function get_admin_fields( $name ) {
		switch ( $name ) {
			// @codingStandardsIgnoreStart
			case 'post_author':
				return intval( implode( get_option( 'stackcommerce_wp_author' ) ) );
			break;
			case 'post_status':
				$post_status = [ 'draft', 'pending', 'future' ];
				$post_status_option = intval( implode( get_option( 'stackcommerce_wp_post_status' ) ) );

				return $post_status[ $post_status_option ];
			break;
			case 'post_categories':
				return get_option( 'stackcommerce_wp_categories' );
			break;
			case 'post_tags':
				return get_option( 'stackcommerce_wp_tags' );
			break;
			case 'featured_image':
				switch ( implode( get_option( 'stackcommerce_wp_featured_image' ) ) ) {
					case 'featured_image_only':
						return self::FEATURED_IMAGE_ONLY;
					break;
					case 'featured_image_plus_body':
						return self::FEATURED_IMAGE_PLUS_BODY;
					break;
					case 'no_featured_image':
						return self::NO_FEATURED_IMAGE;
					break;
					default:
						return self::FEATURED_IMAGE_ONLY;
				}
			break;
			// @codingStandardsIgnoreEnd
		}
	}

	/**
	 * Get an integer post status value and convert to a valid string
	 *
	 * @since    1.1.1
	 */
	protected function generate_post_status( $post_status ) {
		$stackcommerce_wp_endpoint = new StackCommerce_WP_Endpoint();

		if ( ! is_int( $post_status ) || ! ( $post_status >= 0 && $post_status <= 2 ) ) {
			$stackcommerce_wp_endpoint->response(
				sprintf( 'An invalid post status has been given: %s', $post_status ),
				array(
					'code'        => 'stackcommerce_wp_invalid_post_status',
					'status_code' => 400,
				)
			);
		}

		$post_statuses = [ 'draft', 'pending', 'future' ];

		return $post_statuses[ $post_status ];
	}

	/**
	 * Prevent duplicated posts by searching the last 15 post titles
	 *
	 * @since    1.6.5
	 */
	protected function search_duplicated( $post ) {
		$stackcommerce_wp_endpoint = new StackCommerce_WP_Endpoint();

		$query = new WP_Query( array(
			'posts_per_page' => 25,
			'post_status'    => 'draft, publish, future, pending, private',
		) );

		$duplicated = [];

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();

				$post_title = get_the_title();

				if ( $post['post_title'] === $post_title ) {
					$duplicated[] = $post_title;
				}
			}
		}

		wp_reset_postdata();

		if ( ! empty( $duplicated ) ) {
			$stackcommerce_wp_endpoint->response(
				'Post cannot be created because it has been recently published',
				array(
					'code'        => 'stackcommerce_wp_duplicated_post',
					'status_code' => 400,
				)
			);
		}
	}

	/**
	 * Register new categories or get ID from existing ones
	 *
	 * @since    1.6.5
	 */
	protected function register_categories( $categories ) {
		if ( empty( $categories ) ) {
			return false;
		}

		if ( is_string( $categories ) ) {
			$categories = explode( ',', $categories );
		}

		$categories_ids = [];

		foreach ( $categories as $cat ) {
			if ( function_exists( 'wpcom_vip_term_exists' ) ) {
				$cat_id = wpcom_vip_term_exists( $cat, 'category' );
			} else {
				// @codingStandardsIgnoreLine
				$cat_id = term_exists( $cat, 'category' );
			}

			if ( ! $cat_id && 0 !== $cat_id ) {
				$cat_id = wp_insert_term( $cat, 'category' );
			}

			if ( ! is_wp_error( $cat_id ) ) {
				$categories_ids[] = (int) $cat_id['term_id'];
			}
		}

		return $categories_ids;
	}

	/**
	 * Convert tags to proper format (array)
	 *
	 * @since    1.6.5
	 */
	protected function convert_tags( $tags ) {
		if ( empty( $tags ) ) {
			return false;
		}

		if ( is_string( $tags ) ) {
			return explode( ',', $tags );
		}

		return false;
	}

	/**
	 * Prepare fields on a $post array
	 *
	 * @since    1.6.5
	 */
	protected function prepare_fields( $fields ) {
		$post = array(
			'post_title'     => wp_strip_all_tags( $fields['post_title'] ),
			'post_content'   => $fields['post_content'],
			'post_type'      => isset( $fields['post_type'] ) ? $fields['post_type'] : 'post',
			'post_author'    => isset( $fields['post_author'] ) ? $fields['post_author'] : $this->get_admin_fields( 'post_author' ),
			'post_name'      => isset( $fields['post_name'] ) ? $fields['post_name'] : '',
			'post_excerpt'   => isset( $fields['post_excerpt'] ) ? $fields['post_excerpt'] : '',
			'featured_media' => isset( $fields['featured_media'] ) ? $fields['featured_media'] : '',
		);

		if ( isset( $fields['post_date_gmt'] ) ) {
			$post['post_date_gmt'] = get_gmt_from_date( $fields['post_date_gmt'] );
		}

		if ( isset( $fields['post_status'] ) ) {
			$post['post_status'] = $this->generate_post_status( $fields['post_status'] );
		}

		if ( isset( $fields['post_category'] ) ) {
			$post['post_category'] = $this->register_categories( $fields['post_category'] );
		}

		if ( isset( $fields['tags_input'] ) ) {
			$post['tags_input'] = $this->convert_tags( $fields['tags_input'] );
		}

		return $post;
	}

	/**
	 * Check post fields before creation
	 *
	 * @since    1.6.5
	 */
	protected function check_fields( $fields ) {
		$stackcommerce_wp_endpoint = new StackCommerce_WP_Endpoint();
		$stackcommerce_wp_media    = new StackCommerce_WP_Media();

		// Prepare and organize all post fields and attributes
		$post = $this->prepare_fields( $fields );

		// Search for duplicated posts
		$this->search_duplicated( $post );

		$featured_image_options = $this->get_admin_fields( 'featured_image' );

		// No featured image should be set, so strip them out
		if ( self::NO_FEATURED_IMAGE === $featured_image_options ) {
			unset( $post['featured_media'] );
		}

		// If there are still featured image set on
		// `featured_media` field, download and process it
		if ( isset( $post['featured_media'] ) && ! empty( $post['featured_media'] ) ) {
			$featured_image         = $stackcommerce_wp_media->upload_image_from_url( $post['featured_media'] );
			$post['featured_media'] = $featured_image;

			// Strip first post image before download and process them
			if ( self::FEATURED_IMAGE_ONLY === $featured_image_options ) {
				$post['post_content'] = $stackcommerce_wp_media->strip_image( $post['post_content'] );
			}
		}

		// Download and process all post_content images inside <img> tags
		$processed_content = $stackcommerce_wp_media->process_body_images( $post['post_content'] );

		$post['post_content'] = $processed_content['post_content'];

		// Proceed to post creation or stop when any error is detected with featured image
		if ( ! isset( $featured_image ) || ( array_key_exists( 'error', $featured_image ) && empty( $featured_image['error'] ) ) ) {
			$this->create( $post, $processed_content['attachment_ids'] );
		} else {
			$stackcommerce_wp_endpoint->response(
				sprintf( 'An error occurred while creating post: %s', $featured_image['error'] ),
				array(
					'code'        => 'stackcommerce_wp_featured_image_error',
					'status_code' => 400,
				)
			);
		}
	}

	/**
	 * Runs post creation
	 *
	 * @since    1.6.5
	 */
	protected function create( $post, $attachments = [] ) {
		$stackcommerce_wp_endpoint = new StackCommerce_WP_Endpoint();
		$stackcommerce_wp_media    = new StackCommerce_WP_Media();

		// Effectively creates a new post
		$post_id = wp_insert_post( $post, true );

		// Set uploaded featured image as attachment
		if ( ! is_wp_error( $post_id ) ) {
			if ( isset( $post['featured_media'] ) && ! empty( $post['featured_media'] ) ) {
				$attachment_id     = $stackcommerce_wp_media->set_uploaded_image_as_attachment( $post['featured_media'], $post_id );
				$featured_media_id = $stackcommerce_wp_media->set_featured_media( $attachment_id, $post_id );

				$post['featured_media'] = $featured_media_id;
			}

			if ( count( $attachments ) > 0 ) {
				foreach ( $attachments as $attachment ) {
					$stackcommerce_wp_media->set_image_parent( $attachment, $post_id );
				}
			}

			// Success. Return post object
			$stackcommerce_wp_endpoint->response( $post,
				array(
					'status_code' => 200,
				)
			);
		} else {
			// Something was wrong. Get error and return
			$stackcommerce_wp_endpoint->response(
				sprintf( 'An error occurred while creating post: %s', $post_id->get_error_message() ),
				array(
					'code'        => 'stackcommerce_wp_post_create_error',
					'status_code' => 400,
				)
			);
		}
	}
}

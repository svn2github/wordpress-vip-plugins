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
				$featured_image = get_option( 'stackcommerce_wp_featured_image' );

				if ( is_array( $featured_image ) && ! empty( $featured_image ) ) {
					return implode( $featured_image );
				} else {
					return 'featured_image_only';
				}

			break;
			// @codingStandardsIgnoreEnd
		}
	}

	/**
	 * Get categories IDs
	 *
	 * @since    1.1.0
	 */
	protected function get_categories_ids( $categories ) {
		if ( empty( $categories ) ) {
			return false;
		}

		$categories_ids = [];

		foreach ( $categories as $category ) {
			// @codingStandardsIgnoreLine
			$category_id = get_category_by_slug( $category );

			array_push( $categories_ids,  $category_id->term_id );
		}

		return $categories_ids;
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
	 * Check if matches the last created post to prevent duplications
	 *
	 * @since    1.0.0
	 */
	protected function check_duplicate( $post ) {
		$stackcommerce_wp_endpoint = new StackCommerce_WP_Endpoint();

		// @codingStandardsIgnoreLine
		$recent_post = wp_get_recent_posts( array(
			'numberposts' => 1,
			'post_status' => 'draft, publish, future, pending, private',
		), ARRAY_A );

		$post_title = $post['post_title'];

		if ( ! empty( $recent_post[0]['post_title'] ) ) {
			$last_post_title = $recent_post[0]['post_title'];
		}

		if ( isset( $last_post_title ) ) {
			$equal_post_title = ( $post_title === $last_post_title );

			if ( $equal_post_title ) {
				$stackcommerce_wp_endpoint->response(
					'Post cannot be created because it has been recently published',
					array(
						'code'        => 'stackcommerce_wp_duplicate_post',
						'status_code' => 400,
					)
				);
			}
		}
	}

	/**
	 * Create missing tags and categories
	 *
	 * @since    1.6.0
	 */
	protected function register_taxonomy( $taxonomies, $type ) {
		if ( empty( $taxonomies ) || empty( $type ) ) {
			return false;
		}

		if ( is_string( $taxonomies ) ) {
			$taxonomies = explode( ',', $taxonomies );
		}

		$taxonomies_ids = [];

		foreach ( $taxonomies as $tax ) {
			if ( function_exists( 'wpcom_vip_term_exists' ) ) {
				$tax_id = wpcom_vip_term_exists( $tax, $type );
			} else {
				// @codingStandardsIgnoreLine
				$tax_id = term_exists( $tax, $type );
			}

			if ( ! $tax_id && 0 !== $tax_id ) {
				$tax_id = wp_insert_term( $tax, $type );
			}

			if ( ! is_wp_error( $tax_id ) ) {
				$taxonomies_ids[] = (int) $tax_id['term_taxonomy_id'];
			}
		}

		return $taxonomies_ids;
	}

	/**
	 * Prepare fields on a $post array
	 *
	 * @since    1.6.1
	 */
	protected function prepare_fields( $fields ) {
		$post = array(
			'post_title'   => wp_strip_all_tags( $fields['post_title'] ),
			'post_content' => $fields['post_content'],
			'post_type'    => isset( $fields['post_type'] ) ? $fields['post_type'] : 'post',
			'post_author'  => isset( $fields['post_author'] ) ? $fields['post_author'] : $this->get_admin_fields( 'post_author' ),
			'post_name'    => isset( $fields['post_name'] ) ? $fields['post_name'] : '',
			'post_excerpt' => isset( $fields['post_excerpt'] ) ? $fields['post_excerpt'] : '',
		);

		if ( isset( $fields['post_date_gmt'] ) ) {
			$post['post_date_gmt'] = get_gmt_from_date( $fields['post_date_gmt'] );
		}

		if ( isset( $fields['post_status'] ) ) {
			$post['post_status'] = $this->generate_post_status( $fields['post_status'] );
		}

		if ( isset( $fields['post_category'] ) ) {
			$post['post_category'] = $this->register_taxonomy( $fields['post_category'], 'category' );
		}

		if ( isset( $fields['tags_input'] ) ) {
			$post['tags_input'] = $this->register_taxonomy( $fields['tags_input'], 'post_tag' );
		}

		return $post;
	}

	/**
	 * Check post fields before creation
	 *
	 * @since    1.6.1
	 */
	protected function check_fields( $fields ) {
		$stackcommerce_wp_endpoint = new StackCommerce_WP_Endpoint();
		$stackcommerce_wp_media = new StackCommerce_WP_Media();

		$post = $this->prepare_fields( $fields );

		$this->check_duplicate( $fields );

		$post['post_content'] = $stackcommerce_wp_media->process_body_images( $fields['post_content'] );

		$featured_image_options = $this->get_admin_fields( 'featured_image' );

		switch ( $featured_image_options ) {
			// @codingStandardsIgnoreStart
			case 'featured_image_only':
				$post = $stackcommerce_wp_media->strip_image( $post );
			break;
			case 'no_featured_image':
				unset( $fields['featured_media'] );
				unset( $post['featured_media'] );
			break;
			// @codingStandardsIgnoreEnd
		}

		if ( isset( $fields['featured_media'] ) && strlen( $fields['featured_media'] ) > 0 ) {
			$featured_image = $stackcommerce_wp_media->upload_image_from_url( $fields['featured_media'] );
			$post['featured_media'] = $featured_image;
		}

		if ( ! isset( $featured_image ) || ( array_key_exists( 'error', $featured_image ) && empty( $featured_image['error'] ) ) ) {
			$this->create( $post );
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
	 * @since    1.2.0
	 */
	protected function create( $post ) {
		$stackcommerce_wp_endpoint = new StackCommerce_WP_Endpoint();
		$stackcommerce_wp_media = new StackCommerce_WP_Media();

		$post_id = wp_insert_post( $post, true );

		if ( ! is_wp_error( $post_id ) ) {
			if ( isset( $post['featured_media'] ) ) {
				$attachment_id = $stackcommerce_wp_media->set_uploaded_image_as_attachment( $post['featured_media'], $post_id );
				$featured_media_id = $stackcommerce_wp_media->set_featured_media( $attachment_id, $post_id );

				$post['featured_media'] = $featured_media_id;
			}

			$stackcommerce_wp_endpoint->response( $post,
				array(
					'status_code' => 200,
				)
			);
		} else {
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

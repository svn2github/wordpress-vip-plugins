<?php

/**
 * Code for post creation
 */
class Skyword_Publish {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_filter( 'xmlrpc_methods', array(
			$this,
			'skyword_xmlrpc_methods'
		) );
	}

	/**
	 * Extend XMLRPC calls
	 */
	public function skyword_xmlrpc_methods( $methods ) {
		$methods['skyword_post']                  = array(
			$this,
			'skyword_post'
		);
		$methods['skyword_newMediaObject']        = array(
			$this,
			'skyword_newMediaObject'
		);
		$methods['skyword_author']                = array(
			$this,
			'skyword_author'
		);
		$methods['skyword_version']               = array(
			$this,
			'skyword_version'
		);
		$methods['skyword_version_number']        = array(
			$this,
			'skyword_version_number'
		);
		$methods['skyword_version_info']          = array(
			$this,
			'skyword_version_info'
		);
		$methods['skyword_getAuthors']            = array(
			$this,
			'skyword_get_authors'
		);
		$methods['skyword_getCategories']         = array(
			$this,
			'skyword_get_categories'
		);
		$methods['skyword_getTags']               = array(
			$this,
			'skyword_get_tags'
		);
		$methods['skyword_getPost']               = array(
			$this,
			'skyword_get_post'
		);
		$methods['skyword_deletePost']            = array(
			$this,
			'skyword_delete_post'
		);
		$methods['skyword_getTaxonomies']         = array(
			$this,
			'skyword_get_taxonomies'
		);
		$methods['skyword_getTaxonomyList']       = array(
			$this,
			'skyword_get_taxonomy_list'
		);
		$methods['skyword_getTaxonomyTermsChunk'] = array(
			$this,
			'skyword_get_taxonomy_terms_chunk'
		);
		$methods['skyword_phpInfo']               = array(
			$this,
			'skyword_php_info'
		);

		return $methods;
	}

	/**
	 * Returns current version of plugin to write.skyword.com.
	 */
	public function skyword_version( $args ) {
		$this->escapeXmlRpcArgs( $args );
		$login = $this->login( $args );
		if ( 'success' === $login['status'] ) {
			return (string) ( 'Wordpress Version: ' . get_bloginfo( 'version' ) . ' Plugin Version: ' . SKYWORD_VERSION );
		} else {
			return $login['message'];
		}
	}

	/**
	 * Returns version number of plugin
	 */
	public function skyword_version_number( $args ) {
		$this->escapeXmlRpcArgs( $args );
		$login = $this->login( $args );
		if ( 'success' === $login['status'] ) {
			return (string) SKYWORD_VN;
		} else {
			return $login;
		}
	}

	public function skyword_version_info( $args ) {
		$this->escapeXmlRpcArgs( $args );
		$login = $this->login( $args );
		if ( 'success' === $login['status'] ) {
			return array(
				'plugin_version'    => SKYWORD_VERSION,
				'wordpress_version' => get_bloginfo( 'version' ),
			);
		}
	}

	/**
	 * Gets author id if they exist, otherwise creates guest author with co-author-plus plugin
	 */
	public function skyword_author( $args ) {
		$this->escapeXmlRpcArgs( $args );
		$login = $this->login( $args );
		if ( 'success' === $login['status'] ) {
			$data    = $args[3];
			$user_id = $this->check_username_exists( $data );

			return (string) $user_id;
		} else {
			return $login['message'];
		}
	}

	/**
	 * Returns list of authors associated with site for ghost writing
	 */
	public function skyword_get_authors( $args ) {
		$this->escapeXmlRpcArgs( $args );
		$login = $this->login( $args );
		if ( 'success' === $login['status'] ) {
			$authors = array();
			foreach (
				get_users( array(
					'fields' => 'all',
					'role_in' => array( 'author', 'editor' )
				) ) as $user
			) {
				$authors[] = array(
					'user_id'      => $user->ID,
					'role'         => $user->role,
					'user_login'   => $user->user_login,
					'display_name' => $user->display_name
				);
			}

			return $authors;
		} else {
			return $login['message'];
		}
	}

	/**
	 * Returns list of categories for write.skyword.com publishing
	 */
	public function skyword_get_categories( $args = '' ) {
		$login = $this->login( $args );
		if ( 'success' === $login['status'] ) {

			do_action( 'xmlrpc_call', 'metaWeblog.getCategories' );

			$categories_struct = array();
            $struct = array();
			if ( $cats = get_categories( array( 'get' => 'all' ) ) ) {
				foreach ( $cats as $cat ) {
					$struct['categoryId']   = $cat->term_id;
					$struct['parentId']     = $cat->parent;
					$struct['categoryName'] = $cat->name;
					$categories_struct[]    = $struct;
				}
			}

			return $categories_struct;
		} else {
			return $login['message'];
		}
	}

	/**
	 * Returns list of tags for write.skyword.com publishing
	 */
	public function skyword_get_tags( $args = '' ) {
		$this->escapeXmlRpcArgs( $args );
		$login = $this->login( $args );
		if ( 'success' === $login['status'] ) {
			do_action( 'xmlrpc_call', 'wp.getKeywords' );

			$tags = array();
            $struct = array();
			if ( $all_tags = get_tags( array( 'hide_empty' => false ) ) ) {
				foreach ( (array) $all_tags as $tag ) {
					$struct['tag_id'] = $tag->term_id;
					$struct['name']   = $tag->name;
					$struct['count']  = $tag->count;
					$struct['slug']   = $tag->slug;
					$tags[]           = $struct;
				}
			}

			return $tags;
		} else {
			return $login['message'];
		}
	}

	public function skyword_get_taxonomies( $termString, $args = '' ) {
		$this->escapeXmlRpcArgs( $args );
		$login = $this->login( $args );
		if ( 'success' === $login['status'] ) {
			$taxonomiesStruct = array();
			$taxonomies       = get_taxonomies( null, "objects" );
            $struct = array();
			if ( $taxonomies ) {
				foreach ( $taxonomies as $taxonomy ) {
					$struct['name'] = $taxonomy->name;
					$hierarchical   = $taxonomy->hierarchical;
					$terms          = get_terms( $struct['name'], array('hide_empty' => 0));
					if ( count( $terms ) > 50000 ) {
						continue;
					}

					$termsArr = array();
					$termStruct = array();
					foreach ( $terms as $term ) {
						$termStruct['name'] = $term->name;
						if ( $hierarchical ) {
							$termStruct['id'] = $term->term_id;
						} else {
							$termStruct['id'] = $term->name;
						}
						$termsArr[] = $termStruct;
					}
					if ( $terms ) {
						$struct['terms']      = $termsArr;
						$struct['termString'] = $termString;
						$taxonomiesStruct[]   = $struct;
					}
					unset( $termsArr );

				}
			}

			return $taxonomiesStruct;
		} else {
			return $login['message'];
		}
	}


	public function skyword_get_taxonomy_list( $args ) {
		$this->escapeXmlRpcArgs( $args );
		$login = $this->login( $args );
		if ( 'success' === $login['status'] ) {

			$taxonomies = get_taxonomies( null, "objects" );

			$taxonomyArray = array();
			foreach ( $taxonomies as $taxonomy ) {

				$count = wp_count_terms( $taxonomy->name );

				if ( $count > 0 ) {
					$taxonomyArray[] = array(
						'name'  => $taxonomy->name,
						'count' => $count
					);
				}
			}

			return $taxonomyArray;

		} else {
			return $login['message'];
		}
	}

	public function skyword_get_taxonomy_terms_chunk( $args ) {
		$this->escapeXmlRpcArgs( $args );
		$login = $this->login( $args );
		if ( 'success' === $login['status'] ) {
			global $wpdb;
            $termResults = array();
            if( ! $termResults = wp_cache_get( $args, 'sky_tax_terms') ) {
                $termResults = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT
                    t.*,
                    tt.*
                  FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
                  WHERE tt.taxonomy IN (%s)
                  LIMIT %d, %d",
                        sanitize_text_field($args[3]),
                        sanitize_text_field($args[4]),
                        sanitize_text_field($args[5]) )
                );

                wp_cache_add( $args, $termResults, 'sky_tax_terms' );
            }

			$terms = array();
			foreach ( $termResults as $taxonomy ) {
				$terms[ $taxonomy->term_id ] = array(
					'term_id' => $taxonomy->term_id,
					'name'    => $taxonomy->name,
				);
			}

			return $terms;
		} else {
			return $login['message'];
		}
	}

	/**
	 * Returns permalink for post to write.skyword.com
	 */
	public function skyword_get_post( $args = '' ) {
		$this->escapeXmlRpcArgs( $args );
		$login = $this->login( $args );
		$response = array();
		if ( 'success' === $login['status'] ) {
			$post_id          = (int) $args[3];
			$response['link'] = get_permalink( $post_id );

			return $response;
		} else {
			return $login['message'];
		}
	}

	/**
	 * Deletes post by id
	 */
	public function skyword_delete_post( $args = '' ) {
		$this->escapeXmlRpcArgs( $args );
		$login = $this->login( $args );
		if ( 'success' === $login['status'] ) {
			do_action( 'xmlrpc_call', 'wp.deletePost' );
			$post_id = $args[3];
			$post    = get_post( $post_id, ARRAY_A );
			if ( empty( $post['ID'] ) ) {
				return new IXR_Error( 404, __( 'Invalid post ID.' ) );
			}


			$result = wp_delete_post( $post_id );

			if ( ! $result ) {
				return new IXR_Error( 500, __( 'The post cannot be deleted.' ) );
			}

			return true;
		} else {
			return $login['message'];
		}

	}

	/**
	 * Creates posts from write.skyword.com
	 */
	public function skyword_post( $args ) {
		global $coauthors_plus;
		$this->escapeXmlRpcArgs( $args );
		$login = $this->login( $args );
		if ( 'success' === $login['status'] ) {
			$data = $args[3];
			if ( null !== $data['post-id'] ) {
				$post_date = get_post_time( 'Y-m-d H:i:s', false, $data['post-id'] );
			} else {
				$post_date = current_time( 'mysql' );
			}
			if ( null !== $data['publication-state'] ) {
				$state = sanitize_text_field( $data['publication-state'] );
			} else {
				$state = "draft";
			}

			$categories    = $data['categories'];
			$post_category = array();
			foreach ( (array) $categories as $category ) {
				$categoryId = (int) $category['id'];
				if ( null !== $categoryId && 0 !== $categoryId ) {
					$post_category[] = $category['id'];
				}
			}

			$skywordContentId = (int) $data['skyword_content_id'];
			$postType = sanitize_text_field( $data['post-type'] );
			$data['post-id']  = $this->check_content_exists($skywordContentId,  $data['post-type'] );
			$new_post         = array(
				'post_status'    => $state,
				'post_date'      => $post_date,
				'post_excerpt'   => wp_kses_post( $data['excerpt'] ),
				'post_type'      => $postType,
				'comment_status' => 'open',
				'post_category'  => $post_category
			);

			if ( null !== $data['title'] ) {
				$new_post['post_title'] = sanitize_text_field( $data['title'] );
			}
			if ( null !== $data['description'] ) {
				$new_post['post_content'] = wp_kses_post( $data['description'] );
			}
			if ( null !== $data['slug'] ) {
				$new_post['post_name'] = sanitize_text_field( $data['slug'] );
			}
			if ( null !== $data['post-id'] ) {
				$new_post['ID'] = (int) $data['post-id'];
			}
			if ( null !== $data['user-id'] && is_numeric( trim( $data['user-id'] ) ) ) {
				$new_post['post_author'] = (int) $data['user-id'];
			}

			$post_id = wp_insert_post( $new_post );

			$utf8string = html_entity_decode( $data['tags-input'] );
			wp_set_post_tags( $post_id, $utf8string, false );

			//attach attachments to new post;
			$this->attach_attachments( $post_id, $data, $data['skyword_content_id'] );
			//add content template/attachment information as meta
			$this->create_custom_fields( $post_id, $data );
			$this->update_custom_field( $post_id, 'skyword_tracking_tag', $data['tracking'] );
			$this->update_custom_field( $post_id, 'skyword_seo_title', wp_kses_post( $data['metatitle'] ) );
			$this->update_custom_field( $post_id, 'skyword_metadescription', wp_kses_post( $data['metadescription'] ) );
			$this->update_custom_field( $post_id, 'skyword_keyword', wp_kses_post( $data['metakeyword'] ) );
			$this->update_custom_field( $post_id, '_yoast_wpseo_title', wp_kses_post( $data['metatitle'] ) );
			$this->update_custom_field( $post_id, '_yoast_wpseo_metadesc', wp_kses_post( $data['metadescription'] ) );
			$this->update_custom_field( $post_id, '_yoast_wpseo_focuskw', wp_kses_post( $data['keyword'] ) );
			$this->update_custom_field( $post_id, 'skyword_content_id', wp_kses_post( $data['skyword_content_id'] ) );

			//add custom taxonomy values
			foreach ( (array) $data["taxonomies"] as $taxonomy ) {

				$taxonomy['values'] = explode( ',', $taxonomy['values'] );
	
				if ( $this->valuesIsNumeric( $taxonomy['values'] ) ) {
					if ( 'post' === $postType ) {
						wp_set_post_terms( $post_id, $this->convertArrayValuesToInt( $taxonomy['values'] ), $taxonomy['name'], true );
					} else {
						wp_set_object_terms( $post_id, $this->convertArrayValuesToInt( $taxonomy['values'] ), $taxonomy['name'], true );
					}
				} else {
					if ( 'post' === $postType ) {
						wp_set_post_terms( $post_id, $taxonomy['values'], $taxonomy['name'], true );
					} else {
						wp_set_object_terms( $post_id, $taxonomy['values'], $taxonomy['name'], true );
					}
				}
			}

			if ( null !== $data['gmwlocation_wppl_street'] ) {
				global $wpdb;
				$gmwLocation = sanitize_text_field( $data['gmwlocation_wppl_street'] );
				$gmwCity     = sanitize_text_field( $data['gmwlocation_wppl_city'] );
				$gmwState    = sanitize_text_field( $data['gmwlocation_wppl_state'] );
				$gmwZipcode = sanitize_text_field( $data['gmwlocation_wppl_zipcode'] );
				$gmwLat      = sanitize_text_field( $data['gmwlocation_wppl_lat'] );
				$gmwLong     = sanitize_text_field( $data['gmwlocation_wppl_long'] );
				$wpdb->replace( $wpdb->prefix . 'places_locator', array(
					'post_id'     => $post_id,
					'feature'     => 0,
					'post_type'   => $postType,
					'post_title'  => sanitize_title( $data['title'] ),
					'post_status' => $state,
					'street'      => $gmwLocation,
					'city'        => $gmwCity,
					'state'       => $gmwState,
					'zipcode'     => $gmwZipcode,
					'lat'         => $gmwLat,
					'long'        => $gmwLong
				) );
				$this->update_custom_field( $post_id, '_wppl_street', $gmwLocation );
				$this->update_custom_field( $post_id, '_wppl_city', $gmwCity );
				$this->update_custom_field( $post_id, '_wppl_state', $gmwState );
				$this->update_custom_field( $post_id, '_wppl_zipcode', $gmwZipcode );
				$this->update_custom_field( $post_id, '_wppl_lat', $gmwLat );
				$this->update_custom_field( $post_id, '_wppl_long', $gmwLong );
				$this->update_custom_field( $post_id, '_wppl_phone', sanitize_text_field($data['gmwlocation_wppl_phone'] ));
			}

			//Create sitemap information
			if ( 'news' === $data['publication-type'] ) {
				$this->update_custom_field( $post_id, 'skyword_publication_type', 'news' );
				if ( null !== $data['publication-access'] ) {
					$this->update_custom_field( $post_id, 'skyword_publication_access', wp_kses_post($data['publication-access'] ));
				}
				if ( null !== $data['publication-name'] ) {
					$this->update_custom_field( $post_id, 'skyword_publication_name', wp_kses_post($data['publication-name'] ));
				}
				if ( null !== $data['publication-geolocation'] ) {
					$this->update_custom_field( $post_id, 'skyword_geolocation', wp_kses_post($data['publication-geolocation'] ));
				}
				if ( null !== $data['publication-keywords'] ) {
					$this->update_custom_field( $post_id, 'skyword_tags', wp_kses_post($data['publication-keywords'] ));
				}
				if ( null !== $data['publication-stocktickers'] ) {
					$this->update_custom_field( $post_id, 'skyword_stocktickers', wp_kses_post($data['publication-stocktickers'] ));
				}
			} else {
				$this->update_custom_field( $post_id, 'skyword_publication_type', 'evergreen' );
			}
			if ( null !== $coauthors_plus ) {
				if ( ! is_numeric( $data['user-id'] ) ) {
					$data['user-id'] = str_replace( 'guest-', '', $data['user-id'] );
					$author          = $coauthors_plus->guest_authors->get_guest_author_by( 'ID', $data['user-id'] );
					$author_term     = $coauthors_plus->update_author_term( $author );
					if ( 'post' === $postType ) {
						wp_set_post_terms( $post_id, $author_term->slug, $coauthors_plus->coauthor_taxonomy, true );
					} else {
						wp_set_object_terms( $post_id, $author_term->slug, $coauthors_plus->coauthor_taxonomy, true );
					}
				}
			}
            do_action('skyword_post_publish', $post_id );
			return (string) $post_id;
		} else {
			return $login['message'];
		}
	}

	/**
	 * Modified image upload based off of xmlrpc newMediaObject function.
	 * Adds ability to include alt title, caption, and description to attachment
	 */
	public function skyword_newMediaObject( $args ) {
		$login = $this->login( $args );
		if ( 'success' === $login['status'] ) {
            $data        = $args[3];
            if(array_key_exists('skywordContentId', $data)) {
                $name        = sanitize_file_name( $data['name'] );
                $type        = esc_html($data['type']);
                $bits        = $data['bits'];
                $title       = esc_html($data['title']);
                $caption     = esc_html( $data['caption']);
                $alttext     = esc_html($data['alttext']);
                $description = esc_html($data['description']);
                $skywordContentId = $data['skywordContentId'];
                $featuredImage = $data['featuredimage'];

                if ( empty( $title ) ) {
                    $title = $name;
                }

                do_action( 'xmlrpc_call', 'metaWeblog.newMediaObject' );

                if ( $upload_err = apply_filters( 'pre_upload_error', false ) ) {
                    return new IXR_Error( 500, $upload_err );
                }

                $upload = wp_upload_bits( $name, null, $bits );
                if ( ! empty( $upload['error'] ) ) {
                    $errorString = sprintf( __( 'Could not write file %1$s (%2$s)' ), $name, $upload['error'] );
                    return new IXR_Error( 500, $errorString );
                }
                // Construct the attachment array
                // attach to post_id 0
                $post_id    = 0;
                $attachment = array(
                    'post_title'     => $title,
                    'post_content'   => '',
                    'post_type'      => 'attachment',
                    'post_parent'    => $post_id,
                    'post_mime_type' => $type,
                    'post_excerpt'   => $caption,
                    'post_content'   => $description,
                    'guid'           => $upload['url']
                );

                // Save the data
                $id = wp_insert_attachment( $attachment, $upload['file'], $post_id );
                wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
                //adds alt text as meta
                add_post_meta( $id, "_wp_attachment_image_alt", $alttext, false );
                add_post_meta( $id, "skywordContentId", $skywordContentId, false );
                if (isset($featuredImage)) {
                    add_post_meta( $id, "featuredImage", $featuredImage, false);
                }

                return apply_filters( 'wp_handle_upload', array(
                    'file' => $name,
                    'url'  => $upload['url'],
                    'type' => $type
                ), 'upload' );
		    } else if (array_key_exists('skywordAuthorId', $data)) {
                $bits        = $data['bits'];
                $skywordAuthorId = $data['skywordAuthorId'];
                $name        = sanitize_file_name( $data['name'] );
                $type        = esc_html( $data['type'] );

                if ( empty( $title ) ) {
                    $title = $name;
                }

                do_action( 'xmlrpc_call', 'metaWeblog.newMediaObject' );

                if ( $upload_err = apply_filters( 'pre_upload_error', false ) ) {
                    return new IXR_Error( 500, $upload_err );
                }

                $upload = wp_upload_bits( $name, null, $bits );
                if ( ! empty( $upload['error'] ) ) {
                    $errorString = sprintf( __( 'Could not write file %1$s (%2$s)' ), $name, $upload['error'] );
                    return new IXR_Error( 500, $errorString );
                }

                $attachment = array(
                    'guid' => $upload['url'],
                    'post_status' => 'inherit',
                    'ping_status' => 'closed',
                    'post_type' => 'attachment',
                    'post_content' => '',
                    'post_name' => 'author-'.$skywordAuthorId,
                    'post_title' => 'author-'.$skywordAuthorId,
                    'post_mime_type' => $type
                );

                $post_id = wp_insert_attachment($attachment);
                wp_update_attachment_metadata( $post_id, wp_generate_attachment_metadata( $post_id, $upload['file'] ) );
                add_post_meta( $post_id, '_wp_attached_file', $upload['file'] );
                add_post_meta( $skywordAuthorId, '_thumbnail_id', $post_id );

                return apply_filters( 'wp_handle_upload', array(
                                    'file' => $name,
                                    'url'  => $upload['url'],
                                ), 'upload' );
		    }
		}

		return $login['message'];
	}

	/**
	* Debug function to get information about the user's configuration.
	* Uncomment if needed for troubleshooting.
	*
	public function skyword_php_info( $args ) {
		$login = $this->login( $args );
		if ( 'success' === $login['status'] ) {

			if ( 'on' === $_SERVER['HTTPS'] ) {
				return phpinfo();
			}

			return new IXR_Error( 403, "You must use a secure connection." );
		}

		return $login['message'];
	}
    */

	/**
	 * Checks if post exists identified by skyword content id, used to avoid duplicates if publishing error occcurs
	 */
	private function check_content_exists( $skywordId, $postType ) {
		$query = array(
			'ignore_sticky_posts' => true,
			'meta_key'            => 'skywordid',
			'meta_value'          => $skywordId,
			'post_type'           => $postType,
			'posts_per_page'      => 1,
			'no_found_rows'       => true,
			'post_status'         => array(
				'publish',
				'pending',
				'draft',
				'auto-draft',
				'future',
				'private',
				'inherit',
				'trash'
			)
		);
		query_posts( $query );
		if ( have_posts() ) :
			while ( have_posts() ) : the_post();
				$str = get_the_ID();

				return $str;
			endwhile;
		else :
			$query = array(
				'ignore_sticky_posts' => true,
				'meta_key'            => 'skyword_content_id',
				'meta_value'          => $skywordId,
				'post_type'           => $postType,
                'posts_per_page'      => 1,
                'no_found_rows'       => true,
				'post_status'         => array(
					'publish',
					'pending',
					'draft',
					'auto-draft',
					'future',
					'private',
					'inherit',
					'trash'
				)
			);
			query_posts( $query );
			if ( have_posts() ) :
				while ( have_posts() ) : the_post();
					$str = get_the_ID();

					return $str;
				endwhile;

				return null;
			else :
				return null;
			endif;
		endif;
	}

	/**
	 * Uses nonce or un/pw to authenticate whether user is able to interact with plugin
	 */
	private function login( $args ) {
		$username = $args[1];
		$password = $args[2];
		global $wp_xmlrpc_server;
		$response = array();
		//Authenticate that posting user is valid
		if ( 'skywordapikey' !== $username ) {
			if ( ! $user = $wp_xmlrpc_server->login( $username, $password ) ) {
				$response['message'] = new IXR_Error( 403, __( 'Invalid UN/PW Combination: UN = ' . $username . ' PW = ' . $password ) );
				$response['status']  = 'error';
			} else if ( ! user_can( $user->ID, 'edit_posts' ) ) {
				$response['message'] = new IXR_Error( 403, __( 'You do not have sufficient privileges to login.' ) );
				$response['status']  = 'error';
			} else {
				$response['status'] = 'success';
			}

			return $response;
		} else {
			$values    = explode( '-', $args[2] );
			$hash      = $values[0];
			$timestamp = $values[1];
			$response  = $this->validate_secret( $hash, $timestamp );
		}

		return $response;
	}

	/**
	 * Validates that nonce is valid
	 */
	private function validate_secret( $hash, $timestamp ) {
		$temp_time = time();
		$options   = get_option( 'skyword_plugin_options' );
		$api_key   = $options['skyword_api_key'];
		$response = array();
		if ( $temp_time - $timestamp <= 20000 && $temp_time - $timestamp >= - 20000 ) {
			if ( '' !== $api_key ) {
				$temp_hash = md5( $api_key . $timestamp );
				if ( $temp_hash === $hash ) {
					$response['status'] = 'success';
				} else {
					$response['message'] = new IXR_Error( 403, __( 'Could not match hash.' ) );
					$response['status']  = 'error';
				}
			} else {
				$response['message'] = new IXR_Error( 403, __( 'Skyword API key not set.' ) );
				$response['status']  = 'error';
			}
		} else {
			$response['message'] = new IXR_Error( 403, __( 'Bad timestamp used. ' . $hash . ' Timestamp sent: ' . $timestamp ) );
			$response['status']  = 'error';
		}

		return $response;
	}

	/**
	 * Checks whether username exists.
	 * Creates Guest Author if not
	 * Depends on Co Author Plus Plugin
	 */
	private function check_username_exists( $data ) {
		global $coauthors_plus;
		$user_id = username_exists( $data['user-name'] );
		if ( ! $user_id ) {
		    $options = get_option( 'skyword_plugin_options' );
            $olduser_name = str_replace( 'sw-', 'skywriter-', $data['user-name'] );
			$user_id      = username_exists( $olduser_name );
			if ( ! $user_id ) {
				if ( null !== $coauthors_plus ) {
					$guest_author                   = array();
					$guest_author['ID']             = '';
					$guest_author['display_name']   = $data['display-name'];
					$guest_author['first_name']     = $data['first-name'];
					$guest_author['last_name']      = $data['last-name'];
					$guest_author['user_login']     = $data['user-name'];
					$guest_author['user_email']     = $data['email'];
					$guest_author['description']    = $data['bio'];
					$guest_author['jabber']         = '';
					$guest_author['yahooim']        = '';
					$guest_author['aim']            = '';
					$guest_author['website']        = $data['website'];
					$guest_author['linked_account'] = '';
					$guest_author['website']        = $data['website'];
					$guest_author['company']        = $data['company'];
					$guest_author['title']          = $data['title'];
					$guest_author['google']         = $data['google'];
					$guest_author['twitter']        = $data['twitter'];

					$retval = $coauthors_plus->guest_authors->create( $guest_author );
					if ( is_wp_error( $retval ) ) {
						$author = $coauthors_plus->guest_authors->get_guest_author_by( 'user_login', $data['user-name'] );
						if ( null !== $author ) {
							$user_id = 'guest-' . $author->ID;
						}
					} else {
						$user_id = 'guest-' . $retval;
					}

				} else if ($options['skyword_generate_new_users_automatically']) {
					//Generate a random password
					$random_password = wp_generate_password( 20, false );
					//Create the account
					$user_id = wp_insert_user( array(
						'first_name'    => $data['first-name'],
						'last_name'     => $data['last-name'],
						'user_nicename' => $data['user-name'],
						'display_name'  => $data['display-name'],
						'user_email'    => $data['email'],
						'role'          => "author",
						'user_login'    => $data['user-name'],
						'user_pass'     => $random_password,
						'description'   => $data['bio']
					) );
				}
				else
				    $user_id = 'check failed';
			}

		}

		return $user_id;
	}

	/**
	 * Attaches attachments provided to a specific post
	 */
	private function attach_attachments( $post_id, $data, $skywordId ) {
		global $wpdb;
		$args = array(
			'post_type'        => 'attachment',
			'post_parent'      => 0,
			'posts_per_page'   => 100,
			'suppress_filters' => false,
                        'meta_query'       => array (
                                'relation' => 'AND',
                                array(
                                        'key' => 'skywordContentId',
                                        'value' => $skywordId,
                                        'compare' => '=' 
                                )   
                        )   
		);

		$attachments = get_posts( $args );

		if ( is_array( $attachments ) ) {
			foreach ( $attachments as $file ) {
				if ( is_array( $data['attachments'] ) ) {
					foreach ( $data['attachments'] as $attachmentExt ) {
						if ( $attachmentExt === $file->guid ) {
							$wpdb->update( $wpdb->posts, array( 'post_parent' => $post_id ), array( 'ID' => $file->ID ) );
						}
						if ( get_post_meta( $file->ID, 'featuredImage', true ) === 'true' ) {
							delete_post_meta( $file->ID, 'featuredImage' );
							delete_post_meta( $post_id, '_thumbnail_id' );
							add_post_meta( $post_id, '_thumbnail_id', $file->ID, false );
							$wpdb->update( $wpdb->posts, array( 'post_parent' => $post_id ), array( 'ID' => $file->ID ) );

						}
					}
				}
			}
		}
	}

	/**
	 * Updates all custom fields provided by write.skyword.com
	 */
	private function create_custom_fields( $post_id, $data ) {
		$custom_fields = explode( ':', $data['custom_fields'] );
		foreach ( $custom_fields as $custom_field ) {
			$fields = explode( '-', $custom_field, 2 );
			delete_post_meta( $post_id, urldecode( $fields[0] ) );
			add_post_meta( $post_id, urldecode( $fields[0] ), urldecode
			( $fields[1] ) );
		}
	}

	/**
	 * Updates specified custom field
	 */
	private function update_custom_field( $post_id, $key, $data ) {
		delete_post_meta( $post_id, $key );
		add_post_meta( $post_id, $key, $data, false );
	}

	/**
	 * Check if array values are numbers
	 */

	private function valuesIsNumeric( $array ) {

		return array_filter( $array, 'is_numeric' ) === $array ;

	}

	/**
	 *
	 * Convert string values to integer
	 *
	 */

	private function convertArrayValuesToInt( $array ) {
		return array_map( 'intval', $array );
	}

	private function escapeXmlRpcArgs(&$args) {
		global $wp_xmlrpc_server;
		$wp_xmlrpc_server->escape( $args );
	}
}

global $skyword_publish;
$skyword_publish = new Skyword_Publish();

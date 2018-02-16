<?php
/**
Plugin Name: Sailthru for WordPress
Plugin URI: http://sailthru.com/
Description: Add the power of Sailthru to your WordPress set up.
Version: 3.2.0
Author: Sailthru
Author URI: http://sailthru.com
Author Email: integrations@sailthru.com
License:

Copyright 2013 (Sailthru)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

**/



/**
 * The current version of the plugin.
 *
 * @since 3.0.6
 * @var   const    $version    The current version of the plugin.
 */
if ( ! defined( 'SAILTHRU_PLUGIN_VERSION' ) ) {
	define( 'SAILTHRU_PLUGIN_VERSION', '3.2.0' );
}

if ( ! defined( 'SAILTHRU_PLUGIN_PATH' ) ) {
	define( 'SAILTHRU_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SAILTHRU_PLUGIN_URL' ) ) {
	define( 'SAILTHRU_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}


/*
 * Sailthru PHP5 Developer Library.
 * Source: http://getstarted.sailthru.com/developers/client-libraries/set-config-file/php5.
 */
require_once SAILTHRU_PLUGIN_PATH . 'lib/Sailthru_Util.php';
require_once SAILTHRU_PLUGIN_PATH . 'lib/Sailthru_Client.php';
require_once SAILTHRU_PLUGIN_PATH . 'lib/Sailthru_Client_Exception.php';
require_once SAILTHRU_PLUGIN_PATH . 'classes/class-wp-sailthru-client.php';

/*
 * Get Sailthru for WordPress plugin classes
 */
require_once SAILTHRU_PLUGIN_PATH . 'classes/class-sailthru-horizon.php';
require_once SAILTHRU_PLUGIN_PATH . 'classes/class-sailthru-concierge.php';
require_once SAILTHRU_PLUGIN_PATH . 'classes/class-sailthru-scout.php';
require_once SAILTHRU_PLUGIN_PATH . 'classes/class-sailthru-mailer.php';

/*
 * Get Sailthru Custom Subscribe Fields classes
 */
require_once SAILTHRU_PLUGIN_PATH . 'classes/class-sailthru-subscribe-fields.php';

/*
 * Sailthru for WordPress admin view settings and registrations.
 */
require_once SAILTHRU_PLUGIN_PATH . 'views/admin.functions.php';

/*
 * Grab and activate the Sailthru Subscribe widget.
 */
require_once SAILTHRU_PLUGIN_PATH . 'widget.subscribe.php';

/*
 * Add the email handler
 */
require_once SAILTHRU_PLUGIN_PATH . 'sailthru_mail.php';


/*
 * Horizon handles the foundational actions like adding menus, meta tags,
 * and javascript files.
 */
if ( class_exists( 'Sailthru_Horizon' ) ) {

	$sailthru_horizon = new Sailthru_Horizon();

	// add a record in the db to keep track of the version of this plugin
	if ( false === get_option( 'sailthru_plugin_version' ) ) {
		add_option( 'sailthru_plugin_version', SAILTHRU_PLUGIN_VERSION );
	} else {
		update_option( 'sailthru_plugin_version', SAILTHRU_PLUGIN_VERSION );
	} // end if

	if ( class_exists( 'Sailthru_Scout' ) ) {
		$sailthru_scout = new Sailthru_Scout();
	}
}



/**
 * Register hooks that are fired when the plugin is activated,
 * deactivated, and uninstalled, respectively.
 */
register_activation_hook( __FILE__, array( 'Sailthru_Horizon', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Sailthru_Horizon', 'deactivate' ) );
register_uninstall_hook( __FILE__, array( 'Sailthru_Horizon', 'uninstall' ) );



// This is called from sailthru_setup_handler()
function sailthru_create_wordpress_template() {

	$wordpress_template = 'WordPress Template';

	if ( sailthru_verify_setup() ) {

		$sailthru   = get_option( 'sailthru_setup_options' );
		$api_key    = $sailthru['sailthru_api_key'];
		$api_secret = $sailthru['sailthru_api_secret'];

		$client = new WP_Sailthru_Client( $api_key, $api_secret );

		// Find out if 'WordPress Template' already exists
		$template_exists = false;

		try {
			if ( $client ) {
				// if we try to grab a template by name that doesn't exist
				// the world blows up. Grab them all and loop through
				$response  = $client->getTemplates();
				$templates = $response['templates'];
				foreach ( $templates as $template ) {
					foreach ( $template as $key => $value ) {
						if ( 'name' === $key ) {
							if ( $value === $wordpress_template ) {
								$template_exists = true;
							}
						}
					}
				}
			}
		} catch ( Sailthru_Client_Exception $e ) {
			//silently fail
			return;
		}

		// the Template doesn't exist, so we need to create it.
		if ( false === $template_exists ) {

			try {
				if ( $client ) {
					$client->saveTemplate(
						'wordpress-template',
						array(
							'name'         => $wordpress_template,
							'subject'      => '{subject}',
							'content_html' => "<html>\n<head>\n<body>\n{body}\n</body>\n</html>",
						)
					);
				}
			} catch ( Sailthru_Client_Exception $e ) {
				//silently fail
				return;
			}
		}
	}

}


// Add and action to handle when a user logs in.
add_action( 'wp_login', 'sailthru_user_login', 10, 2 );


function sailthru_user_login( $user_login, $user ) {
	if ( get_option( 'sailthru_setup_complete' ) ) {
		$sailthru   = get_option( 'sailthru_setup_options' );
		$api_key    = $sailthru['sailthru_api_key'];
		$api_secret = $sailthru['sailthru_api_secret'];

		$client = new WP_Sailthru_Client( $api_key, $api_secret );

		$id      = $user->user_email;
		$options = array(
			'login'  => array(
				'user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ),
				'key'        => 'email',
				'ip'         => sanitize_text_field( $_SERVER['SERVER_ADDR'] ),
				'site'       => sanitize_text_field ($_SERVER['HTTP_HOST'] ) ,
			),
			'fields' => array( 'keys' => 1 ),
		);

		try {
			if ( $client ) {
				$st = $client->saveUser( $id, $options );
			}
		} catch ( Sailthru_Client_Exception $e ) {
			 //silently fail.
			 return;
		}
	}
}


/**
 * Capture the saving of a post and make a Content API call to add
 * the page details and tags to Sailthru's Horizon API for recommendations
 *
 * @param integer $post_id
 */

function sailthru_save_post( $post_id, $post, $post_before ) {

	// Check to see if Content API is disabled

	if ( false === apply_filters( 'sailthru_content_api_enable', true ) ) {
		return;
	}

	if ( 'publish' === $post->post_status ) {
		// Make sure Sailthru is setup
		if ( get_option( 'sailthru_setup_complete' ) ) {
			$sailthru   = get_option( 'sailthru_setup_options' );
			$api_key    = $sailthru['sailthru_api_key'];
			$api_secret = $sailthru['sailthru_api_secret'];
			$client     = new WP_Sailthru_Client( $api_key, $api_secret );
			try {
				if ( $client ) {
					$data = array();
					// Prepare the Content API Params
					$data['url']               = get_permalink( $post->ID );
					$data['title']             = $post->post_title;
					$data['author']            = get_the_author_meta( 'display_name', $post->post_author );
					$data['date']              = $post->post_date;
					$data['vars']['post_type'] = $post->post_type;
					$data['spider']            = 1;
					if ( ! empty( $post->post_excerpt ) ) {
						$data['description'] = $post->post_excerpt;
					} else {
						$data['description'] = wp_trim_words( $post->post_content, 250, '' );
					}
					// image & thumbnail
					if ( has_post_thumbnail( $post->ID ) ) {
						$image                          = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );
						$thumb                          = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'concierge-thumb' );
						$post_image                     = $image[0];
						$data['images']['full']['url']  = esc_attr( $post_image );
						$post_thumbnail                 = $thumb[0];
						$data['images']['thumb']['url'] = $post_thumbnail;
					}
					$post_tags = get_post_meta( $post->ID, 'sailthru_meta_tags', true );
					// WordPress tags.
					if ( empty( $post_tags ) ) {
						$post_tags = get_the_tags();
						if ( $post_tags ) {
							$post_tags = esc_attr( implode( ', ', wp_list_pluck( $post_tags, 'name' ) ) );
						}
					}
					// WordPress categories.
					if ( empty( $post_tags ) ) {
						$post_categories = get_the_category( $post->ID );
						foreach ( $post_categories as $post_category ) {
							$post_tags .= $post_category->name . ', ';
						}
						$post_tags = substr( $post_tags, 0, -2 );
					}
					if ( ! empty( $post_tags ) ) {
						$data['tags'] = $post_tags;
					}
					$post_expiration = get_post_meta( $post->ID, 'sailthru_post_expiration', true );
					if ( ! empty( $post_expiration ) ) {
						$data['expire_date'] = esc_attr( $post_expiration );
					} else {
						// set the expiry date in the future as you can't unset the value via the API
						$data['expire_date'] = date( 'Y-m-d', strtotime( '+5 years' ) );
					}

					// get all the custom fields and add them to the vars
					$custom_fields = get_post_custom( $post_id );
					// exclude  tags
					$exclude_fields = array( '_edit_lock', '_edit_last', '_encloseme', ' sailthru_meta_tags', 'sailthru_post_expiration' );

					foreach ( $custom_fields as $key => $val ) {

						if ( ! in_array( $key, $exclude_fields, true ) ) {

							if ( is_array( $val ) ) {
								$data['vars'][ $key ] = implode( ',', $val );
							} else {
								$data['vars'][ $key ] = $val;
							}
						}
					}
					// Make the API call to Sailthru
					$api = $client->apiPost( 'content', $data );

				}
			} catch ( Sailthru_Client_Exception $e ) {
				//silently fail
				return;
			}
		}
	}
}
add_action( 'save_post', 'sailthru_save_post', 10, 3 );

if ( ! function_exists( 'write_log' ) ) {
	function write_log( $log ) {
		if ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {

			if ( is_array( $log ) || is_object( $log ) ) {
				error_log( print_r( $log, true ) );
			} else {
				error_log( $log );
			}
		}
	}
}



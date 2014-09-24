<?php
/**
 * Plugin Name: GrabPress
 * Plugin URI: http://www.grab-media.com/publisher/grabpress
 * Description: Configure Grab's AutoPoster software to deliver fresh video direct to your Blog. Link a Grab Media Publisher account to get paid!
 * Version: 2.4.0
 * Author: Grab Media, a blinkx company
 * Author URI: http://www.grab-media.com
 * License: GPL2
 */

/*  Copyright 2016  blinkx  (email : licensing@grab-media.com)

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
*/

// Include required files and classes
require_once plugin_dir_path( __FILE__ ) . '/includes/gp-constants.php';
require_once plugin_dir_path( __FILE__ ) . '/class-grabpress-api.php';
require_once plugin_dir_path( __FILE__ ) . '/class-grabpress-views.php';

// Define class only if not defined already
if ( ! class_exists( 'Grabpress' ) ) {
	/**
	 * Grabpress is the main class for the GrabPress plugin.
	 *
	 * @author Grab Media
	 */
	class Grabpress {
		// Properties
		static $version        = CURRENT_VERSION;
		static $api_key        = '';
		static $invalid        = false;
		static $environment    = PRODUCTION_ENV;
		static $message        = false;
		static $error          = false;
		static $grabpress_user = GP_USER;
		static $feed_message   = 'Items marked with an asterisk * are required.';
		static $connector      = null;
		static $connector_user;
		static $providers;
		static $channels;
		static $player_settings;
		static $shortcode_submission_template_id;

		/**
		 * Add default values to request for any value that does not exist already
		 * @param  array $request Associative array containing request data
		 * @return array          Associative array containing request data and any
		 * default values that are necessary
		 */
		static function account_form_default_values( $request ) {
			// Set defaults
			$defaults = array(
				'publish'         => true,
				'click_to_play'   => true,
				'category'        => array(),
				'provider'        => array(),
				'keywords_and'    => '',
				'keywords_not'    => '',
				'keywords_or'     => '',
				'keywords_phrase' => '',
			);

			// Merge default values with request
			$request = array_merge( $defaults, $request );

			// Return updated request
			return $request;
		}

		/**
		 * Adds a GrabPress button to the context
		 * @param string $context Context
		 * @return  string Context with added button
		 */
		static function add_my_custom_button( $context ) {
			// Get path to GrabPress 'G' icon
			$img = Grabpress::get_g_icon_src();

			// Title of popup
			$title = 'Insert GrabMedia Video';

			// Write onclick JavaScript function
			$onclick = 'tb_show("Grab Media Catalog", "admin-ajax.php?action=gp_get_catalog&amp;width=900&amp;height=900" );';

			// Append hyperlinked 'G' icon (button)
			$context .= "<a title='{$title}' href='#' onclick='{$onclick}' ><img src='{$img}' /></a>";

			// Return content with added button
			return $context;
		}

		/**
		 * Check permissions for user based on page provided
		 * @param  string $page String representation of page that permissions are
		 * being checked for
		 * @return [type]       [description]
		 */
		static function check_permissions_for( $page = 'default' ) {
			// Return permissions for user based on current page
			switch ( $page ) {
				case 'gp-autoposter':
					return current_user_can( 'edit_others_posts' ) && current_user_can( 'publish_posts' );
					break;
				case 'gp-account':
					return current_user_can( 'publish_posts' );
				case 'gp-template':
					return current_user_can( 'edit_others_posts' );
					break;
				case 'single-post':
					return current_user_can( 'edit_posts' );
				default:
					return false;
					break;
			}
		}

		/**
		 * [content_by_request description]
		 * @param  [type] $content [description]
		 * @param  [type] $post    [description]
		 * @return [type]          [description]
		 */
		static function content_by_request( $content, $post ) {
			// If pre-content exists and user can edit current post and content was provided
			if ( ! empty( $_REQUEST['pre_content'] ) && current_user_can( 'edit_post', $post->ID ) && '' == $content ) {
				// If post ID exists
				if ( ! empty( $_REQUEST['post_id'] ) ) {
					// Get post ID from request
					$post->ID = $_REQUEST['post_id'];
				}

				// Replace ampersand HTML entities with ampersands
				$content = str_replace( '&amp;', '&', $_REQUEST['pre_content'] );

				// Return stripped content
				return stripslashes( $content );
			}

			// Replace ampersand HTML entities with ampersands
			$content = str_replace( '&amp;', '&', $content );

			// Return altered content
			return $content;
		}

		/**
		 * Delete connector ID and GrabPress user
		 */
		static function delete_connector() {
			// Fetch connector ID from API
			$connector_id = Grabpress_API::get_connector_id();

			// Make calls to deactivate/delete connector ID
			$response = Grabpress_API::call( 'PUT', '/connectors/' . $connector_id . '/deactivate?api_key=' . Grabpress::$api_key );
			$response_delete = Grabpress_API::call( 'DELETE', '/connectors/' . $connector_id . '?api_key=' . Grabpress::$api_key );

			// Remove GrabPress key from WPDB
			delete_option( 'grabpress_key' );

			// Get GP user
			$grab_user = Grabpress::get_user_by( 'login' );

			// Get current WP user
			$current_user = wp_get_current_user();

			// Delete GP user and reassign posts and links to current logged in WP user
			wp_delete_user( $grab_user->ID, $current_user->ID );

			// Output message to admin dashboard
			Grabpress::$message = 'GrabPress has been deactivated. Any posts that used to be credited to the "grabpress" user are now assigned to you. XML-RPC is still enabled, unless you are using it for anything else, we recommend you turn it off.';
		}

		/**
		 * Loads requires assets and scripts based on the requested action and page
		 */
		static function dispatcher() {
			// Check if XML-RPC is enabled
			self::is_xmlrpc_enabled();

			// If request does not contain an action
			if ( ! isset( $_REQUEST['action'] ) ) {
				// Set as default
				$_REQUEST['action'] = 'default';
			}

			// Create shorter reference to action
			$action = $_REQUEST[ 'action' ];

			// Get page
			$page = $_GET['page'];

			// Recursively stripslahses
			$params = self::strip_deep( $_REQUEST );
			// Get GP URL
			$plugin_url = Grabpress::grabpress_plugin_url();

			// Load required assets based on page and action
			switch ( $page ) {
				case 'gp-autoposter':
					$params = Grabpress::account_form_default_values( $params );
					wp_enqueue_script( 'gp-utils', $plugin_url . 'js/utils.js', array( 'jquery' ) );
					wp_enqueue_script( 'gp-autoposter', $plugin_url . 'js/autoposter.js' , array( 'jquery' ) );
					wp_enqueue_script( 'gp-catalog', $plugin_url . 'js/catalog.js' , array( 'jquery' ) );
					switch ( $action ) {
						case 'update':
							Grabpress_Views::do_create_feed( $params );
							break;
						case 'delete':
							Grabpress_Views::delete_feed( $params );
							break;
						case 'modify':
							Grabpress_Views::do_edit_feed( $params );
							break;
						case 'edit-feed':
							Grabpress_Views::edit_feed( $params );
							break;
						case 'prefill':
							Grabpress_Views::prefill_feed( $params );
							break;
						case 'default':
						default:
							Grabpress_Views::feed_management( $params );
							break;
					}
					break;

				case 'gp-account':
					switch ( $params['action'] ) {
						case 'link-user' :
							Grabpress_Views::link_account( $params );
							break;
						case 'unlink-user' :
							Grabpress_Views::unlink_account( $params );
							break;
						case 'create-user':
							Grabpress_Views::create_user( $params );
							break;
						case 'default':
						case 'link':
						case 'unlink':
						case 'create':
						case 'switch':
						default:
							Grabpress_Views::account_management( $params );
							break;
					}
					break;

				case 'gp-catalog':
					switch ( $params['action'] ) {
						case 'update':
							Grabpress_Views::do_create_feed( $params );
						break;
						case 'prefill':
							Grabpress_Views::prefill_feed( $params );
						break;
						case 'catalog-search':
						default:
							Grabpress_Views::catalog_management( $params );
						break;
					}
					wp_enqueue_script( 'gp-utils', $plugin_url . 'js/utils.js', array( 'jquery' ) );
					wp_enqueue_script( 'gp-catalog', $plugin_url . 'js/catalog.js', array( 'jquery' ) );
					break;

				case 'gp-dashboard':
					wp_enqueue_script( 'gp-utils', $plugin_url . 'js/utils.js', array( 'jquery' ) );
					wp_enqueue_script( 'gp-dashboard', $plugin_url . 'js/dashboard.js' , array( 'jquery' ) );
					Grabpress_Views::dashboard_management( $params );
					break;

				case 'gp-template':
					wp_enqueue_script( 'gp-template', $plugin_url . 'js/template.js' , array( 'jquery' ) );
					Grabpress_Views::template_management( $params );
					break;
			}
		}

		/**
		 * Add all required JS and CSS files for a page
		 * @param  string $page String representation of a page
		 */
		static function enqueue_scripts( $page ) {
			// Define vars
			$loadUtilsAndCatalog = false;

			// Get GrabPress URL
			$plugin_url = self::grabpress_plugin_url();

			// Convert page string to array using '_' delimiter
			$handlerparts = explode( '_', $page );

			// If first part is not equal to 'grabpress' page is not post-new, post or index
			if ( $handlerparts[0] != 'grabpress' && $page != 'post-new.php' && $page != 'post.php' && $page != 'index.php' ) {
				// Return out of function
				return;
			} else if ( 'post-new.php' == $page || 'post.php' == $page || 'index.php' == $page ) { // Page is post-new, post or index
				$loadUtilsAndCatalog = true;
			}

			// Add jQuery and jQuery UI to page
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-core' );
			wp_enqueue_script( 'jquery-ui-widget' );
			wp_enqueue_script( 'jquery-ui-position' );
			wp_enqueue_script( 'jquery-ui-dialog' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script(
				'jquery-ui-filter',
				$plugin_url . 'js/ui/multi/jquery.multiselect.filter.min.js',
				array( 'jquery-ui-widget' )
			);
			wp_enqueue_script(
				'jquery-ui-multiselect',
				$plugin_url . 'js/ui/multi/jquery.multiselect.min.js',
				array( 'jquery-ui-widget' )
			);
			wp_enqueue_script(
				'jquery-ui-selectmenu',
				$plugin_url . 'js/ui/jquery.ui.selectmenu.js',
				array( 'jquery-ui-widget' )
			);
			wp_enqueue_script(
				'jquery-simpletip',
				$plugin_url . 'js/jquery.simpletip.min.js',
				array( 'jquery' )
			);
			wp_enqueue_script(
				'jquery-dotdotdot',
				$plugin_url . 'js/jquery.ellipsis.custom.js',
				array( 'jquery' )
			);
			wp_enqueue_script(
				'gp-nanoscroller',
				$plugin_url . 'js/nanoscroller.js',
				array( 'jquery' )
			);
			wp_enqueue_script(
				'jquery-simplePagination',
				$plugin_url . 'js/jquery.simplePagination.js',
				array( 'jquery' )
			);
			wp_enqueue_script(
				'jquery-reveal',
				$plugin_url . 'js/ui/jquery.reveal.js',
				array( 'jquery' )
			);
			wp_enqueue_script(
				'grab-player',
				'http://player.' . self::$environment . '.com/js/Player.js'
			);

			// Get current WordPress version
			$wpversion = floatval( get_bloginfo( 'version' ) );

			// If WP version is 3.1 or lower
			if ( $wpversion <= 3.1 ) {
				// Add backwards compatible of jQuery Placeholder plugin
				wp_enqueue_script(
					'jquery-placeholder',
					$plugin_url . 'js/ui/jquery.placeholder.min.1.8.7.js',
					array( 'jquery' )
				);
			} else { // >=3.2
				// Add modern Placeholder plugin
				wp_enqueue_script(
					'jquery-placeholder',
					$plugin_url . 'js/ui/jquery.placeholder.min.js',
					array( 'jquery' )
				);
			}

			wp_enqueue_script( 'thickbox' );
			wp_enqueue_script( 'media-upload' );

			if ( $loadUtilsAndCatalog ) {
				// Add utils.js and catalog.js to current page
				wp_enqueue_script(
					'gp-utils',
					$plugin_url . 'js/utils.js',
					array( 'jquery' )
				);
				wp_enqueue_script(
					'gp-catalog',
					$plugin_url . 'js/catalog.js',
					array( 'jquery' )
				);
			}

			wp_enqueue_style(
				'jquery-ui-theme',
				'http://ajax.googleapis.com/ajax/libs/jqueryui/1/themes/ui-lightness/jquery-ui.css'
			);
			wp_enqueue_style(
				'gp-bootstrap',
				$plugin_url . 'css/bootstrap-sandbox.css'
			);
			wp_enqueue_style(
				'gp-nanoscroller',
				$plugin_url . 'css/nanoscroller.css'
			);
			wp_enqueue_style(
				'gp-css',
				$plugin_url . 'css/grabpress.css',
				array( 'jquery-ui-theme', 'gp-bootstrap', 'gp-nanoscroller' )
			);
			wp_enqueue_style(
				'jquery-simplePagination',
				$plugin_url . 'css/simplePagination.css'
			);
			wp_enqueue_style(
				'jquery-reveal',
				$plugin_url . 'css/reveal.css'
			);
			wp_enqueue_style(
				'gp-fonts',
				'http://static.grab-media.com/fonts/font-face.css'
			);
			wp_enqueue_style(
				'gp-bootstrap-responsive',
				$plugin_url . 'css/bootstrap-responsive.css'
			);
		}

		/**
		 * [escape_params description]
		 * @param  [type] $params [description]
		 * @return [type]         [description]
		 */
		static function escape_params( $params ) {
			// If array
			if ( is_array( $params ) ) {
				// Generate storable representation of params
				$params = serialize( $params );
			}

			// Return safe URL
			return rawurlencode( stripslashes( urldecode( $params ) ) );
		}

		/**
		 * Recursively HTML escape data
		 * @param  mixed $data Array, object or string that should be escaped.
		 */
		static function escape_params_template( &$data ) {
			// If data is an array or object
			if ( is_array( $data ) || is_object( $data ) ) {
				// Loop through each data item
				foreach ( $data as $key => &$value ) {
					// Go one level deeper
					Grabpress::escape_params_template($value);
				}
			} else { // If not array or object
				// HTML escape data
				$data = htmlentities( stripslashes( $data ), ENT_QUOTES, 'UTF-8' );
			}
		}

		/**
		 * Generate a formatted search string from keywords array
		 * @param  array $keywords Associative array containing keywords sorted by type
		 * @return string           Formatted advanced search string
		 */
		static function generate_adv_search_string( $keywords ) {
			// Create empty advanced search string to start with
			$adv_search_string = '';

			// Remove extra whitespace from beginning/end of keywords_and and add to advanced search string
			$adv_search_string .= trim( $keywords['keywords_and'] );

			// Remove whitespace from beginning/end of keywords_not
			$keywords['keywords_not'] = trim( $keywords['keywords_not'] );

			// If keywords_not exist
			if ( $keywords['keywords_not'] ) {
				// Convert keywords_not to array using whitespace as a delimiter
				$not = preg_split( '/\s+/', $keywords['keywords_not'] );

				// Loop through each keywords_not
				foreach ( $not as $value ) {
					// Prepend a hyphen to the keyword and append it to advanced search string
					$adv_search_string .= ' -' . $value;
				}
			}

			// Remove whitespace from beginning/end of keywords_phrase
			$keywords['keywords_phrase'] = trim( $keywords['keywords_phrase'] );

			// If keywords_phrase exist
			if ( $keywords['keywords_phrase'] ) {
				// Wrap phrase in quotes and append to advances search string
				$adv_search_string .= ' "' . $keywords['keywords_phrase'] . '"';
			}

			// If keywords_or exist
			if ( isset( $keywords['keywords_or'] ) ) {
				// Remove whitespace from beginning/end of keywords_or
				$keywords_or = trim( $keywords['keywords_or'] );
			} else { // No keywords_or
				// If keywords exist
				if ( isset( $keywords['keywords'] ) ) {
					// Remove whitespace from beginning/end of keywords
					$keywords_or = trim( $keywords['keywords'] );
				} else { // No keywords
					// Empty string
					$keywords_or = '';
				}
			}

			// If keywords_or not empty
			if ( ! empty( $keywords_or ) ) {
				// Convert keywords_or to array using whitespace as a delimiter
				$or = preg_split( '/\s+/', $keywords_or );

				// Get total # of ors
				$or_count = count( $or );

				// If only one keywords_or
				if ( 1 == $or_count ) {
					// Append keyword to advanced search string
					$adv_search_string .= ' ' . $or[0];
				} else if ( $or_count > 1) { // If count greater than 1
					if ( $adv_search_string ) {
						$adv_search_string .= ' ';
					}

					// Convert array to string and separate each keyword with ' OR '
					$keywords_or = implode( ' OR ', $or );

					// Append keywords_or to advanced search string
					$adv_search_string .= $keywords_or;
				}
			}

			// Return advanced search string containing all keyword types
			return $adv_search_string;
		}

		/**
		 * Generate catalog URL based on default and provided params
		 * @param  array  $options   Associative array containing params
		 * @param  boolean $unlimited Whether to request an unlimited amount of
		 * items or limit to 20
		 * @return string             The full catalog URL for a request
		 */
		static function generate_catalog_url( $options, $unlimited = false ) {
			// Set defaults
			$defaults = array(
				'providers'  => '',
				'categories' => '',
			);

			// Merged defaults with provided options
			$options = array_merge( $defaults, $options );

			// Escape all options
			$options = array_map( array( 'GrabPress', 'escape_params' ), $options );

			// If keywords_or provided
			if ( isset( $options['keywords_or'] ) ) {
				// Add keywords_or to keywords
				$options['keywords'] = $options['keywords_or'];
			}

			// Build catalog URL
			$catalog_url = 'http://catalog.' . Grabpress::$environment . '.com/catalogs/1/videos/search.json?' . 'keywords_and=' . $options['keywords_and'] . '&categories=' . $options['categories'] . '&providers=' . $options['providers'] . '&keywords_not=' . $options['keywords_not'] . '&keywords=' . $options['keywords'] . '&keywords_phrase=' . $options['keywords_phrase'];

			// If sort_by exists and not empty
			if ( isset( $options['sort_by'] ) && ! empty( $options['sort_by'] ) ) {
				// Append custom sort by to catalog URL
				$catalog_url .= '&sort_by=' . $options['sort_by'];
			} else { // If not
				// Append default sort by
				$catalog_url .= '&sort_by=created_at';
			}

			// If created_after exists and not empty
			if ( isset( $options['created_after'] ) && ! empty( $options['created_after'] ) ) {
				// Append created after to catalog URL
				$catalog_url .= '&created_after=' . $options['created_after'];
			}

			// If created_before exists and not empty
			if ( isset( $options['created_before'] ) && ! empty( $options['created_before'] ) ) {
				// Append created before to catalog URL
				$catalog_url .= '&created_before=' . $options['created_before'];
			}

			// If unlimited
			if ( $unlimited ) {
				// Append to catalog URL
				$catalog_url .= "&limit=-1";
			} else { // Else if limited
				// If page options exists and greater than 0
				if ( isset( $options['page'] ) && $options['page'] > 0 ) {
					// Decrement
					$options['page'] = $options['page'] - 1;
				} else { // Doesn't exist
					// Set value to 0
					$options['page'] = 0;
				}

				// Append offset and limit to catalog URL, limit = 20
				$catalog_url .= '&offset=' . ( ( $options['page'] ) * 20 ) . '&limit=20';
			}

			// Return catalog URL
			return $catalog_url;
		}

		/**
		 * Gets the URL for the GrabPress 'G' icon
		 * @return string The URL path to the GrabPress 'G' icon
		 */
		static function get_g_icon_src() {
			return plugin_dir_url( __FILE__ ) . 'images/icons/g.png';
		}

		/**
		 * Gets the URL for a specified green icon by name
		 * @param  string $name Name of the green icon that should have its URL returned
		 * @return string       The URL path to the green icon
		 */
		static function get_green_icon_src( $name ) {
			return plugin_dir_url( __FILE__ ) . 'images/icons/green/' . $name . '.png';
		}

		/**
		 * Wraps Wordpress' get_user_by function allowing for a custom error message to be logged if the function is not available
		 * @param  string $field String representing a WP field from which a user will be retrieved, can be 'id', 'slug', 'email' or 'login'
		 * @return mixed        WP_User object or false if no user is found. Will also return false if $field does not exist.
		 */
		static function get_user_by( $field ) {
			// If get_user_by() has been defined by Wordpress
			if ( function_exists( 'get_user_by' ) ) {
				// Use WP version to return GP user by field
				return get_user_by( $field, Grabpress::$grabpress_user );
			}
		}

		/**
		 * Returns the WP.com blog ID, either from Jetpack for self-hosted sites or
		 * from the blog itself if WP.com hosted
		 * @return integer WordPress.com blog ID
		 */
		static function get_wpdotcom_blog_id() {
			// Check if Jetpack present and active
			if ( get_option( 'jetpack_activated' ) ) {
				// Get Jetpack options from WPDB and unserialize them
				$jetpack_options = get_option( 'jetpack_options' );
				// Get blog ID from the options
				$wpdotcom_blog_id = isset($jetpack_options['id']) ? $jetpack_options['id'] : '';
			} else { // WordPress.com hosted site
				// Get blog ID directly from global $blog_id
				$wpdotcom_blog_id = get_current_blog_id();
			}
			return $wpdotcom_blog_id;
		}

		/**
		 * Builds embed code for the player and Google Analytics
		 * @param  array $attributes Associative array containing user set attributes
		 * @return string             Player and Google Analytics embed codes
		 */
		static function gp_shortcode( $attributes ) {
			// Build supporte attributes array
			$supported_attributes = array(
				'guid'     => 'default',
				'embed_id' => Grabpress_API::get_connector()->ctp_embed_id,
			);

			// Pull list of supported shortcodes into current scope
			extract( shortcode_atts( $supported_attributes, $attributes, EXTR_SKIP ) );

			// Fetch stored player settings
			$settings = Grabpress_API::get_player_settings_for_embed();

			// Build HTML for embedded player
			$player_script  = '<div id="grabDiv' . $embed_id . '">';
			$player_script .= '	<script type="text/javascript" src="http://player.' .self::$environment . '.com/js/Player.js?id=' . $embed_id . '&content=v' . $guid . '&width=' . $settings["width"] . "&height=" . $settings["height"] . '&tgt=' . self::$environment . '"></script>';
			$player_script .= '	<div id="overlay-adzone" style="overflow:hidden; position:relative"></div>';
			$player_script .= '</div>';
			$player_script .= '<script type="text/javascript">';
			$player_script .= '	var _gaq = _gaq || [];';
			$player_script .= '	_gaq.push(["_setAccount", "UA-31934587-1"]);';
			$player_script .= '	_gaq.push(["_trackPageview"]);';
			$player_script .= '	(function() { var ga = document.createElement("script"); ga.type = "text/javascript"; ga.async = true; ga.src = ("https:" == document.location.protocol ? "https://ssl" : "http://www") + ".google-analytics.com/ga.js"; var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ga, s); })();';
			$player_script .= '</script>';

			// Return player embed script and Google Analytics script
			return $player_script;
		}

		/**
		 * Adds GrabPress menus and submenus to admin dashboard
		 */
		static function grabpress_plugin_menu() {
			// Add top level GrabPress menu to WP admin dashboard
			add_menu_page( 'GrabPress', 'GrabPress', 'manage_options', 'grabpress', array( 'GrabPress', 'dispatcher' ), Grabpress::get_g_icon_src(), 11 );

			// Add Dashboart submenu
			add_submenu_page( 'grabpress', 'Dashboard', 'Dashboard', 'publish_posts', 'gp-dashboard', array( 'GrabPress', 'dispatcher' ) );

			// If has permission to edit GP account info
			if ( Grabpress::check_permissions_for( 'gp-account' ) ) {
				// Add Account submenu
				add_submenu_page( 'grabpress', 'Account', 'Account', 'publish_posts', 'gp-account', array( 'GrabPress', 'dispatcher' ) );
			}

			// If has permission to edit Autoposter
			if ( Grabpress::check_permissions_for( 'gp-autoposter' ) ) {
				// Add AutoPoster submen
				add_submenu_page( 'grabpress', 'AutoPoster', 'AutoPoster', 'publish_posts', 'gp-autoposter', array( 'GrabPress', 'dispatcher' ) );
			}

			// Add Catalog submen
			add_submenu_page( 'grabpress', 'Catalog', 'Catalog', 'publish_posts', 'gp-catalog', array( 'GrabPress', 'dispatcher' ) );

			// If has permission to edit GP player template
			if ( Grabpress::check_permissions_for( 'gp-template' ) && Grabpress_API::verify_wp_connection() ) {
				// Add Template submenu
				add_submenu_page( 'grabpress', 'Template', 'Template', 'publish_posts', 'gp-template', array( 'GrabPress', 'dispatcher' ) );
			}

			// Reference global $submenu
			global $submenu;

			// If user can manage options
			if ( current_user_can( 'manage_options' ) ) {
				// Remove first GrabPress submenu
				// TODO: Update comment with more specific description of submenu being removed
				unset( $submenu['grabpress'][0] );
			}
		}

		/**
		 * Returns the GrabPress plugin URL
		 * @return string GrabPress plugin URL
		 */
		static function grabpress_plugin_url() {
			// Return the URL for the GrabPress
			return plugin_dir_url( __FILE__ ) ;
		}

		/**
		 * Check if XML-RPC is enabled in WordPress
		 */
		static function is_xmlrpc_enabled() {
			// Get option info from WPDB for WP version < 3.5
			$enabled = get_option( 'enable_xmlrpc' );

			// Check if XML-RPC enabled
			if ( $enabled ) {
				// Enabled
				return true;
			} else {
				// Open access to WP version global
				global $wp_version;

				// If version is 3.5+ XML-RPC enabled by default
				if ( version_compare( $wp_version, '3.5', '>=' ) ) {
					return true;
				} else { // Not enabled
					// Generate error message
					self::$error = 'Your WordPress instance does not have XML-RPC enabled. Please enable XML-RPC in your WordPress instance before continuing the use of the GrabPress plugin.';

					// Not enabled
					return false;
				}
			}
		}

		/**
		 * Returns the post title with 'VIDEO ' prepended
		 * @param  string $title Post title
		 * @return string        Modified post title
		 */
		static function modified_post_title ( $title ) {
			// If post title is not empty
			if ( ! empty( $_REQUEST['post_title'] ) ) {
				// Return title in format 'VIDEO: Video Title'
				return $title = 'VIDEO: ' . stripslashes( $_REQUEST['post_title'] );
			}
		}

		/**
		 * Add a border around invalid form elements
		 * @return [type] [description]
		 */
		static function outline_invalid() {
			// If invalid
			if ( Grabpress::$invalid ) {
				// Output style to add 1px dashed red border
				echo 'border:1px dashed red;';
			}
		}

		/**
		 * Parses keywords from advances search string and sorts them by type
		 * @param  string $adv_search Advanced search query to parse
		 * @return array             Associative array of keywords sorted by type
		 */
		static function parse_adv_search_string( $adv_search ) {
			// Trim extra whitespace
			$adv_search = trim( $adv_search );

			// Sort regex matches for text in between /" and "/ into an array
			preg_match_all( '/(?<=\")([^\"]*)(?=\")/', $adv_search, $matched_exact_phrase, PREG_PATTERN_ORDER );

			// Remove text wrapped in quotes, i.e. "this will be removed" and strip slashes
			$sentence = preg_replace( '/\"([^\"]*)\"/', '', stripslashes( $adv_search ) );

			// Sort regex matches for OR, i.e. "something_1 OR something _2" into an array
			preg_match_all( '/[\p{Latin}0-9_]*\s+OR\s+[\p{Latin}0-9_]*/u', $sentence, $result_or, PREG_PATTERN_ORDER );

			// Get total # of results
			$results_count = count( $result_or[0] );

			// Loop through each result
			for ( $i = 0; $i < $results_count; $i++ ) {
				// Remove " OR " with a space " " and strip slashes
				$matched_or[] = str_replace( " OR "," ", stripslashes ( $result_or[0][ $i ] ) );
			}

			// Remove OR statements from remaining sentence
			$sentence_without_or = preg_replace( '/[\p{Latin}0-9_]*\s+OR\s+[\p{Latin}0-9_]*/u', '', stripslashes( $sentence ) );

			// Split string into array with whitspace as a delimiter
			$keywords = preg_split( '/\s+/', $sentence_without_or );

			// Get total # of keywords
			$keywords_count = count( $keywords );

			// Loop through each keyword
			for ( $i = 0; $i < $keywords_count; $i++ ) {
				// If keyword begins with a hyphen
				if ( preg_match( '/^-/', $keywords[ $i ] ) ) {
					// Remove hyphen
					$temp_not = str_replace('-', '', $keywords[ $i ]);

					// Add keyword into keywords_not array
					$keywords_not[] = $temp_not;
				} else { // Does not start with hyphen
					// Add keyword into keywords_and array
					$keywords_and[] = $keywords[ $i ];
				}
			}

			// Convert keyword arrays to string with keywords separated by a space
			$keywords_phrase = isset( $matched_exact_phrase ) ? implode( ' ', $matched_exact_phrase[0] ) : '';
			$keywords_and = isset( $keywords_and ) && ! empty( $keywords_and ) ? implode( ' ', $keywords_and ) : '';
			$keywords_not = isset( $keywords_not ) && ! empty( $keywords_not ) ? implode( ' ', $keywords_not ) : '';
			$keywords_or = isset( $matched_or ) && ! empty( $matched_or ) ? implode( ' ', $matched_or ) : '';

			// Return associative array of keywords sorted by type
			return array(
				'keywords_phrase' => $keywords_phrase,
				'keywords_and'    => $keywords_and,
				'keywords_not'    => $keywords_not,
				'keywords_or'     => $keywords_or,
			);
		}

		/**
		 * Generate general plugin messages to output to admin dashboard
		 */
		static function plugin_messages() {
			// Get feeds from API
			$feeds = Grabpress_API::get_feeds();

			// Get total # of feeds
			$num_feeds = count( $feeds );

			// Get URL to admin area
			$admin_url = get_admin_url();

			// Get URL of current page
			$current_page = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

			// If no feeds
			if ( $num_feeds == 0 ) {
				// Set admin page as 'gp-autoposter'
				$admin_page = $admin_url . 'admin.php?page=gp-autoposter';

				// If the current page is not the admin page
				if ( $current_page != $admin_page ) {
					// Output hyperinked "here" to admin page
					$here = '<a href="' . $admin_page . '">here</a>';
				} else { // If current page is admin page
					// Output text "here"
					$here = 'here';
				}

				// If permission to autopost
				if ( Grabpress::check_permissions_for( 'gp-autoposter' ) ) {
					// Output message to admin dashboard
					Grabpress::$message = 'Thank you for activating GrabPress. Try creating your first Autoposter feed ' . $here . '.';
				}
			} else { // Else if feeds exist
				// Set safe default
				$active_feeds = 0;

				// Loop through each feed
				for ( $i = 0; $i < $num_feeds; $i++ ) {
					// If active feed is greater than 0
					// TODO: Is this a boolean? If so do a boolean check instead.
					if ( $feeds[$i]->feed->active > 0 ) {
						// Increment number of active feeds
						$active_feeds++;
					}
				}

				// If # of active feeds or # of feeds is greater than 0
				if ( $active_feeds > 0 || $num_feeds > 0 ) {
					// Set singular 'feed' text
					$noun = 'feed';
					// If # of active feeds is greater than 1 or # of feeds is 0
					if ( $active_feeds > 1 || 0 == $num_feeds ) {
						// Pluralize 'feeds'
						$noun .= 's';
					}

					// Get GrabPress user
					$user = Grabpress_API::get_user();

					// Check if user email is linked
					$linked = isset( $user->email );

					// If page and action are set and page is account and action is create
					if ( isset( $_REQUEST['page'], $_REQUEST['action'] ) && 'account' == $_REQUEST['page'] && 'create' == $_REQUEST['action'] ) {
						// Plain text "Create"
						$create = 'Create';
					} else { // If not
						// Hyperlink "Create"
						$create = '<a href="admin.php?page=gp-account&action=create">Create</a>';
					}

					// If page and action are set and page is account and action is default
					if ( isset( $_REQUEST['page'], $_REQUEST['action'] ) && 'account' == $_REQUEST['page'] && 'create' == $_REQUEST['action'] ) {
						// Plain text
						$link = 'link an existing';
					} else { // If not
						// Hyperlink
						$link = '<a href="admin.php?page=gp-account&action=default">link an existing</a>';
					}

					// If email is not linked yet, generate revenue CTA message
					$linked_message = $linked ? '' : 'Want to earn money? ' . $create .' or '. $link . ' Grab Publisher account.';

					// If current ENV is development, generate ENV message
					$environment = ( Grabpress::$environment == DEVELOPMENT_ENV ) ? '  ENVIRONMENT = ' . Grabpress::$environment : '';

					// If no active feeds
					if ( $active_feeds == 0 ) {
						// Set # of active feeds equal to total # of feeds
						$active_feeds = $num_feeds;

						// Disable autoposter by default
						$autoposter_status = 'OFF';

						// Set feed as inactive
						$feeds_status = 'inactive';
					} else { // Else active feeds exist
						// Enable autoposter
						$autoposter_status = 'ON';

						// Set feed active
						$feeds_status = 'active';
					}

					// Output status message to admin dashboard
					Grabpress::$message = 'Grab Autoposter is <span id="autoposter-status">' . $autoposter_status . '</span> with <span id="num-active-feeds">' . $active_feeds . '</span> <span id="feeds-status">' . $feeds_status . '</span> <span id="noun-active-feeds"> ' . $noun . '</span>.';

					// If GP account has proper permissions
					if ( Grabpress::check_permissions_for('gp-account') ) {
						// Add revenue CTA to outputted message
						Grabpress::$message .= $linked_message;
					}

					// Add ENV message to outputted message
					Grabpress::$message .= $environment;
				}
			}
		}

		/**
		 * Adds 'access_key' to the GrabPress options group in the WP admin dashboard
		 */
		static function register_settings() {
			// Add access key to Grabpress options to santize and save
			register_setting( 'Grabpress', 'access_key', 'sanitize_access_key' );
		}

		/**
		 * Renders a view using provided data
		 * @param  string $file Path to file
		 * @param  array  $data Associative array containing template or request data
		 * @return string       Contents of output buffer
		 */
		static function render( $file = null, $data = array() ) {
			// Escape HTML
			Grabpress::escape_params_template( $data );

			// Extract the vars to local namespace
			extract( $data );

			// Start output buffering
			ob_start();

			// Import the file
			include $file;

			// Get contents from buffer
			$contents = ob_get_contents();

			// End buffering and discard existing contents
			ob_end_clean();

			// Return contents of OB
			return $contents;
		}

		/**
		 * Callback to sanitize access keys before storing them in WPDB
		 * @param  array $input Input array passed from register_settings()
		 * @return array        Array containing sanitized access key
		 */
		static function sanitize_access_key( $input ) {
			// Trim whitespace from beginning and end
			$sanitized_input['access_key'] = trim( $input['access_key'] );

			// Make sure key is alphanumeric with max length of 255
			if ( ! preg_match( '/[a-z0-9]{0,255}/i', $sanitized_input['access_key'] ) ) {
				// Set key to empty string
				$sanitized_input['access_key'] = '';
			}

			// Return sanitized access key
			return $sanitized_input;
		}

		/**
		 * Outputs a formatted message to the admin dashboard
		 */
		static function show_message() {
			// Set safe default
			$msg = false;

			// If GrabPress error
			if ( Grabpress::$error ) {
				// Get error message
				$msg = Grabpress::$error;

				// Output opening div tag and class for error message
				echo '<div id="message" class="error">';
			} else if ( Grabpress::$message ) { // If GrabPress message
				// Get message
				$msg = Grabpress::$message;

				// Output opening div tag and class for message
				echo '<div id="message" class="updated fade">';
			}

			// If msg is true
			if ( $msg ) {
				// Get GrabPress icon URL
				$icon_src = Grabpress::get_g_icon_src();

				// Output HTML for message and icon display
				echo '<p><img src="' . $icon_src . '" style="vertical-align:top; position:relative; top:-2px; margin-right:2px;"/>' . $msg . '</p></div>';
			}
		}

		/**
		 * Recursively strips slashes
		 * @param  mixed $data String or array that should be processed
		 * @return string       String with slashes stripped
		 */
		static function strip_deep( &$data ) {
			// If array, recursively strip slashes, else strip slashes
			$data = is_array( $data ) ? array_map( array( 'GrabPress', 'strip_deep' ), $data ) : stripslashes( $data );
			return $data;
		}
	}
}

// If user is an admin
if ( is_admin() ) {
	// Add hooks for admin dashboard
	add_action( 'admin_enqueue_scripts', array( 'GrabPress', 'enqueue_scripts' ) );
	register_uninstall_hook( __FILE__, array( 'GrabPress', 'delete_connector' ) );
	add_action( 'admin_menu', array( 'GrabPress', 'grabpress_plugin_menu' ) );
	add_action( 'admin_footer', array( 'GrabPress', 'show_message' ) );
	add_action( 'wp_loaded', array( 'GrabPress', 'plugin_messages' ) );
	add_action( 'wp_ajax_gp_toggle_feed', array( 'Grabpress_Views', 'toggle_feed_callback' ) );
	add_action( 'wp_ajax_gp_delete_feed', array( 'Grabpress_Views', 'delete_feed_callback' ) );
	add_action( 'wp_ajax_gp_feed_name_unique', array( 'Grabpress_Views', 'feed_name_unique_callback' ) );
	add_action( 'wp_ajax_gp_delete_alert', array( 'Grabpress_Views', 'delete-alert_callback' ) );
	add_action( 'wp_ajax_gp_insert_video', array( 'Grabpress_Views', 'insert_video_callback' ) );
	add_action( 'wp_ajax_gp_get_catalog', array( 'Grabpress_Views', 'get_catalog_callback' ) );
	add_action( 'wp_ajax_gp_get_preview', array( 'Grabpress_Views', 'get_preview_callback' ) );
	add_action( 'wp_ajax_gp_toggle_watchlist', array( 'Grabpress_Views', 'toggle_watchlist_callback' ) );
	add_action( 'wp_ajax_gp_verify_wp_clicked', array( 'Grabpress_Views', 'store_verify_wp_clicked') );
	add_action( 'media_buttons_context',  array( 'GrabPress', 'add_my_custom_button') );
	add_filter( 'default_content', array( 'GrabPress', 'content_by_request' ), 10, 2 );
	add_filter( 'default_title', array( 'GrabPress', 'modified_post_title' ) );
}

// Add hook for GP shortcode
add_shortcode( 'grabpress_video', array( 'GrabPress', 'gp_shortcode') );

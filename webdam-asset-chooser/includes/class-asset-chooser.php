<?php

namespace Webdam;

/**
 * TinyMCE Asset Chooser
 *
 * Creates a button on post editors to browse and select
 * any of your WebDAM images.
 *
 * Sideloads selected images into your media library and fetches
 * as much metadata about the image as possible from the WebDAM API.
 */
class Asset_Chooser {

	/**
	 * @var Used to store an internal reference for the class
	 */
	private static $_instance;

	/**
	 * Fetch THE singleton instance of this class
	 *
	 * @param null
	 *
	 * @return Asset_Chooser object instance
	 */
	static function get_instance() {

		if ( empty( static::$_instance ) ){

			self::$_instance = new self();
		}

		// Return the single/cached instance of the class
		return self::$_instance;
	}

	/**
	 * Handles registering hooks that initialize this plugin.
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function __construct() {

		add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );

		// Load up plugin functionality only if we have settings
		if ( \webdam_get_settings() ) {

			add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
			add_action( 'admin_print_scripts', array( $this, 'action_admin_print_scripts' ) );
			add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ) );
			add_filter( 'mce_buttons', array( $this, 'mce_add_button' ) );
			add_filter( 'allowed_http_origins' , array( $this, 'allowed_http_origins' ) );
			
			// Log into the asset chooser if the API integration has been enabled
			if ( \webdam_api_is_enabled() && \webdam_api_is_authenticated() ) {

				// Enable Asset Choose oauth login if it's been enabled
				if ( \webdam_asset_chooser_api_login_is_enabled() ) {
					add_action( 'wp_ajax_nopriv_webdam_get_mock_api_response', array( $this, 'ajax_get_mock_api_response' ) );
				}

				// Turn on the API sideloading if it's been enabled
				if ( \webdam_api_sideloading_is_enabled() ) {
					add_action( 'wp_ajax_pmc-webdam-sideload-image', array( $this, 'handle_ajax_image_sideload' ) );
				}
			}
		}
	}

	/**
	 * Allow the WebDAM domain to query our site
	 *
	 * @since 3.4.0
	 *
	 * @param array $allowed_origins {
	 *     Default allowed HTTP origins.
	 *     @type string Non-secure URL for admin origin.
	 *     @type string Secure URL for admin origin.
	 *     @type string Non-secure URL for home origin.
	 *     @type string Secure URL for home origin.
	 * }
	 *
	 * @return array The possibly modified array of allowed origins
	 */
	function allowed_http_origins( $allowed_origins ) {

		$settings = \webdam_get_settings();

		if ( ! empty( $settings['webdam_account_domain'] ) ) {

			$allowed_origins[] = \webdam_get_site_protocol() . $settings['webdam_account_domain'];

		}

		return $allowed_origins;
	}

	/**
	 * Render out a mock API response for WebDAM to consume
	 *
	 * WebDAM doesn't allow us to simply pass an access_token
	 * in the asset chooser iFrame URL. Instead, the &tokenpath=
	 * query var in the URL itself passes a URL which WebDAM can
	 * query via AJAX to obtain the credentials needed to authenticate.
	 *
	 * On WebDAM's side they're expecting to receive the full API
	 * response given when you authenticate with the API. This
	 * is quite dumb but that's how their system is setup.
	 *
	 * This mock JSON output matches the same output we recieve
	 * in the class-api.php:do_authentication() function, but uses
	 * the access and refresh tokens we already have.
	 *
	 * @see get_current_api_response_url variable in action_admin_enqueue_scripts()
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function ajax_get_mock_api_response() {

		$mock_api_response = array(
			'access_token'  => \webdam_api_get_current_access_token(),
			'expires_in'    => 3600,
			'token_type'    => 'bearer',
			'scope'         => null,
			'refresh_token' => \webdam_api_get_current_refresh_token()
		);

		wp_send_json( $mock_api_response );

		die();
	}

	/**
	 * Enqueue Admin scripts & styles
	 */
	public function action_admin_enqueue_scripts() {

		$screen = get_current_screen();

		// Only enqueue/localize the following items on edit/new post screens
		if ( 'post' !== $screen->base ) {
			return;
		}

		global $post;

		// The [caption] and <img> elements inserted into the content
		// utilize underscore's templating
		wp_enqueue_script( 'underscore' );

		$localized_variables = array();

		if ( $settings = \webdam_get_settings() ) {

			// Build the client webdam url
			$domain_path = '';

			if ( ! empty( $settings['webdam_account_domain'] ) ) {

				$domain_path = $settings['webdam_account_domain'];

				if ( false === strpos( $domain_path, '://' ) ) {
					$domain_path = \webdam_get_site_protocol() . $domain_path;
				}
			}

			// Send some PHP vars to JavaScript
			$localized_variables = array(
				'post_id' => $post->ID,
				'asset_chooser_domain' => $domain_path,

				// The return url is a hidden options page created in
				// \Webdam\Admin::create_set_cookie_page()
				'return_url' => esc_url_raw( \webdam_get_admin_set_cookie_page_url() ),
			);

			// If the WebDAM Note that the API is enable in the localized data
			if ( \webdam_api_is_enabled() && \webdam_api_is_authenticated() ) {

				// If Asset Chooser API login is enabled let JavaScript know
				// so that it processes the Asset Chooser iFrame login via oauth
				if ( \webdam_asset_chooser_api_login_is_enabled() ) {

					$localized_variables['api_login_enabled'] = 1;

					// The response URL is used by WebDAM to back-ping us
					// for the API token to authenticate the asset chooser iFrame
					// Unfortunetly this information can't be passed in the iFrame URL
					$localized_variables['get_current_api_response_url'] = esc_url_raw(
						add_query_arg(
							'action',
							'webdam_get_mock_api_response',
							admin_url( 'admin-ajax.php' )
						)
					);
				}

				// If sideloading is enabled note that in the localized
				// data and include a nonce for that sideloading functionality
				if ( \webdam_api_sideloading_is_enabled() ) {
					$localized_variables['enable_sideloading'] = 1;
					$localized_variables['sideload_nonce'] = wp_create_nonce( 'webdam_sideload_asset' );
				}
			}
		}

		// Allow the localized variables to be filtered
		$localized_variables = apply_filters( 'webdam-asset-chooser-localized-vars', $localized_variables );

		// The main asset chooser js is loaded via TinyMCE
		// as such, we're unable to use it for our localized vars handle
		// Since we're using underscore we'll use that handle instead.
		wp_localize_script( 'underscore', 'webdam', $localized_variables );

		// The following CSS is used to style the asset chooser
		// status popup which displays 'Importing your selection..'
		wp_enqueue_style(
			'webdam-chooser-styles',
			WEBDAM_PLUGIN_URL . 'assets/webdam-asset-chooser.css',
			array(),
			false,
			'screen'
		);
	}

	/**
	 * Enqueue any scripts or styles
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function action_wp_enqueue_scripts() {

		// Enqueue the webdam imported asset CSS
		// This CSS is used to style the imported assets on the frontend

		// Allow this CSS to be optional
		$enqueue_frontend_css = apply_filters( 'webdam-frontend-css', 1 );

		if ( $enqueue_frontend_css ) {
			wp_enqueue_style(
				'webdam-imported-asset',
				WEBDAM_PLUGIN_URL . 'assets/webdam-imported-asset.css',
				array(),
				false,
				'screen'
			);
		}
	}

	/**
	 * Render some HTML templates into the admin header for use by our JS
	 *
	 * @param null
	 *
	 * @return null
	 */
	public function action_admin_print_scripts() {

		$settings = \webdam_get_settings();

		$screen = get_current_screen();

		// Only output the following <script> on edit/new post screens
		if ( 'post' !== $screen->base ) {
			return;
		} ?>

		<!-- The 'Importing your selection' popup -->
		<div class="webdam-asset-chooser-status">
			<div class="working">
				<?php esc_html_e( 'Importing your WebDAM selection..', 'PMC' ); ?>
				<img src="/wp-includes/js/thickbox/loadingAnimation.gif" alt="Waiting" />
			</div>
			<div class="done"></div>
		</div>

		<?php if ( \webdam_api_sideloading_is_enabled() ) : ?>

		<!--
			The inserted [caption] and <img> inserted into content.
			This template is only used when assets are sideloaded.
		-->
		<script type="text/template" id="webdam-insert-image-template">
			[caption id="attachment_<%- attachment_id %>" align="alignnone" class="webdam-imported-asset"]<img class="size-full wp-image-<%- attachment_id %> webdam-imported-asset" src="<%- source %>" alt="<%- alttext %>" width="<%- width %>" height="<%- height %>" /><%- title %> - <%- caption %>[/caption]
		</script>

		<?php endif; ?>

		<?php
	}

	/**
	 * Initialize TinyMCE table plugin and custom TinyMCE plugin
	 *
	 * @param array $plugin_array Array of TinyMCE plugins
	 * @return array Array of TinyMCE plugins
	 */
	public function mce_external_plugins( $plugin_array ) {
		$plugin_array['webdam_asset_chooser'] = WEBDAM_PLUGIN_URL . 'assets/webdam-asset-chooser.js';
		return $plugin_array;
	}

	/**
	 * Add TinyMCE table control buttons
	 *
	 * @param array $buttons Buttons for the second row
	 * @return array Buttons for the second row
	 */
	public function mce_add_button( $buttons ) {
		array_push( $buttons, "separator", 'btnWebDAMAssetChooser' );
        return $buttons;
	}

	/**
	 * Sideload the remote WebDAMN image into WP's media library
	 *
	 * This is executed over AJAX from client-side when an image is chosen
	 * in the WebDAM interface.
	 *
	 * @param null
	 *
	 * @handles $_POST intercept and processing for the
	 *			pmc-webdam-sideload-image AJAX action
	 *
	 * @response JSON object containing status and returned data
	 * @return null
	 */
	public function handle_ajax_image_sideload() {

		// Verify doing ajax
		if ( ! defined( 'DOING_AJAX' ) && ! DOING_AJAX ) {
			return;
		}

		// Verify our nonce to ensure safe origin
		check_ajax_referer( 'webdam_sideload_asset', 'nonce' );

		// Verify we've got the data we need to proceed
		if ( empty( $_POST['post_id'] ) ) {
			wp_send_json_error( array( 'No post ID provided.' ) );
		}

		if ( empty( $_POST['webdam_asset_url'] ) ) {
			wp_send_json_error( array( 'No image source provided.' ) );
		}

		// Sanitize our input
		$post_id          = (int) $_POST['post_id'];
		$webdam_asset_id  = (int) $_POST['webdam_asset_id'];
		$webdam_asset_url = esc_url_raw( $_POST['webdam_asset_url'] );
		$webdam_asset_filename = sanitize_file_name( $_POST['webdam_asset_filename'] );
		
		// Allow the asset url to be filtered when sideloading
		$webdam_asset_url = apply_filters( 'webdam-sideload-asset-url', $webdam_asset_url );

		// Hook into add_attachment so we can obtain the sideloaded image ID
		// media_sideload_image does not return the ID, which sucks.
		add_action( 'add_attachment', array( $this, 'add_attachment' ), 10, 1 );

		// Sideload the image into WP
		$local_image_source  = media_sideload_image( $webdam_asset_url, $post_id, '', 'src' );

		// Grab the sideloaded image ID we just set via the
		// add_attachment actionm hook
		$attachment_id = get_post_meta( $post_id, 'webdam_attachment_id_tmp', true );

		// We don't need this any longer—let's ditch it.
		delete_post_meta( $post_id, 'webdam_attachment_id_tmp' );

		// Broadcast the new attachment ID
		do_action( 'webdam-sideload-attachment-id', $attachment_id );

		// Grab the current image metadata
		$wordpress_image_meta = wp_get_attachment_metadata( $attachment_id );

		// Fetch metadata for the image
		// Some images contain embeded metadata, but that is unreliable
		// and often not present. We could create code to check existing data,
		// and fetch what's needed, but the likelihood of images with data
		// is slim, and depends on the photographer.
		$webdam_image_meta = \webdam_api_get_asset_metadata( $webdam_asset_id );

		// Allow the raw sideloaded asset meta to be filtered
		$webdam_image_meta = apply_filters( 'webdam-sideload-asset-meta', $webdam_image_meta );

		// Set the initial alttext
		$post_alttext = '';

		if ( ! empty( $wordpress_image_meta['image_data']['title'] ) ) {
			$post_alttext = $wordpress_image_meta['image_data']['title'];
		}

		if ( false !== $webdam_image_meta ) {

			$post_title = $post_content = $post_excerpt = $post_credit = '';

			if ( ! empty( $webdam_image_meta->headline ) ) {
				$post_title = $webdam_image_meta->headline;
				$post_alttext = $webdam_image_meta->headline;
			}

			if ( ! empty( $webdam_image_meta->caption ) ) {
				$post_content = $webdam_image_meta->caption;
				$post_excerpt = $webdam_image_meta->caption;
			}

			if ( ! empty( $webdam_image_meta->byline ) ) {
				$post_credit = $webdam_image_meta->byline;
			}

			// Set the attachment post attributes
			$attachment_data = array(
				'ID'           => $attachment_id,
				'post_title'   => $post_title,
				'post_content' => $post_content,
				'post_excerpt' => $post_excerpt,
			);

			// Allow the sideloaded attachment data to be filtered
			$attachment_data = apply_filters( 'webdam-sideload-attachment-data', $attachment_data );

			wp_update_post( $attachment_data );

			// Set the attachment post meta values
			$attachment_post_metas = array(
				'_wp_attachment_image_alt' => $post_alttext,
				'_image_credit'            => $post_credit,
				'_webdam_asset_id'         => $webdam_asset_id,
				'_webdam_asset_filename'   => $webdam_asset_filename,
			);

			// Allow the sideloaded attachment post meta to be filtered
			$attachment_post_metas = apply_filters( 'webdam-sideload-attachment-post-meta', $attachment_post_metas );

			foreach ( $attachment_post_metas as $meta_key => $meta_value ) {
				update_post_meta( $attachment_id, $meta_key, $meta_value );
			}

			// Merge the existing metadata (that WP found embedded within the image)
			// with the metadata from the WebDAM API
			if ( ! empty( $wordpress_image_meta['image_data'] ) && is_array( $wordpress_image_meta['image_data'] ) ){

				$wordpress_image_meta['image_data'] = array_merge( $wordpress_image_meta['image_data'], (array) $webdam_image_meta );

			} else {

				$wordpress_image_meta['image_data'] = (array) $webdam_image_meta;

			}

			// Allow the WordPress image meta to be filtered before saving
			$wordpress_image_meta = apply_filters( 'webdam-sideload-attachment-meta', $wordpress_image_meta );

			// Update the metadata stored for the image by WordPress
			wp_update_attachment_metadata(
				$attachment_id,
				$wordpress_image_meta
			);
		}

		// Return the local image url on success
		// ..error message on failure
		if ( is_wp_error( $local_image_source ) ) {

			wp_send_json_error( array( 'Unable to sideload image.' ) );

		} else {

			if ( false !== $webdam_image_meta ) {

				wp_send_json_success( array(
					'source'        => $local_image_source,
					'alttext'       => $post_alttext,
					'attachment_id' => $attachment_id,
					'width'         => $wordpress_image_meta['width'],
					'height'        => $wordpress_image_meta['height'],
					'title'         => $post_title,
					'caption'       => $post_content,
				) );

			} else {

				wp_send_json_error( array( 'Unable to obtain meta data for image.' ) );

			}
		}
	}

	/**
	 * Helper to obtain sideloaded image ID
	 *
	 * We add this hook before calling media_sideload_image,
	 * and remove it immediately afterwards. This allows us to
	 * capture the newly sideloaded attachment ID. In this context
	 * we can obtain the post_parent and use that to set post meta
	 * on the parent post, which contains the attachment ID.
	 *
	 * It's a little hacky, but by doing so we can call get_post_meta
	 * after calling media_sideload_image to obtain the new attachment ID
	 *
	 * @internal Called via add_attachment action hook
	 *
	 * @param $attachment_id The ID of the newly inserted attachment image
	 *
	 * @return null
	 */
	public function add_attachment( $attachment_id ) {

		// Remove this hook callback so it doesn't fire again
		// We only want this to fire once, right when we're sideloading
		// the image into WP.
		remove_action( 'add_attachment', array( $this, 'add_attachment' ), 10, 1 );

		// Fetch the attachment's post so we may obtain it's parent ID
		// When we call media_sideload_image we specify the original post's ID
		// so that the attachment will be attached to the post.
		$attachment = get_post( $attachment_id );

		// Set temporary post meta on the parent post so we may obtain the
		// attachment id via get_post_meta immediately after calling
		// media_sideload_image()
		add_post_meta( $attachment->post_parent, 'webdam_attachment_id_tmp', $attachment_id );
	}
}

Asset_Chooser::get_instance();

//EOF
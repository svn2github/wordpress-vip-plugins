<?php

class Daylife_Meta_Box {
	public static $instance;

	public function __construct() {
		self::$instance = $this;
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		add_action( 'add_meta_boxes',                array( $this, 'add_meta_box'  ) );
		add_action( 'admin_enqueue_scripts',         array( $this, 'enqueue'       ) );
		add_action( 'wp_ajax_daylife-image-search',  array( $this, 'image_search'  ) );
		add_action( 'wp_ajax_daylife-image-load',    array( $this, 'image_load'    ) );
	}

	public function add_meta_box() {
		add_meta_box( 'daylife-images', __( 'Daylife', 'daylife' ), array( $this, 'render_meta_box' ), 'post', 'normal', 'high' );
	}

	public function render_meta_box() {
		$daylife = get_option( 'daylife' );
		if ( !$daylife['access_key'] || !$daylife['shared_secret'] || !$daylife['api_endpoint'] ) {
			echo sprintf( __( 'Please enter the API credentials for Daylife on the <a href="%s">settings screen</a>.', 'daylife' ), menu_page_url( 'daylife-options', false ) );
			return;
		}
		?><input type="text" id="daylife-search" name="daylife-search" size="16" value=""><?php
		wp_nonce_field( 'daylife-search-nonce', 'daylife-search-nonce-field' );
		wp_nonce_field( 'daylife-add-nonce', 'daylife-add-nonce-field' );
		submit_button( __( 'Search', 'daylife' ), 'secondary', 'daylife-search-button', false );
		submit_button( __( 'Suggest Images', 'daylife' ), 'secondary', 'daylife-suggest-button', false );
		?><div class="daylife-response" style="display: none">Loading</div><?php
	}

	public function enqueue( $hook ) {
		if ( 'post-new.php' != $hook && 'post.php' != $hook )
			return;

		wp_enqueue_script( 'daylife', plugins_url( 'daylife.js', __FILE__ ), array( 'jquery', 'media' ), '20120304', true );
		wp_enqueue_style( 'daylife', plugins_url( 'daylife.css', __FILE__ ), array(), '20120305' );
	}

	public function image_search() {
		check_ajax_referer( 'daylife-search-nonce', 'nonce' );
		require_once( dirname( __FILE__ ) . '/class-wp-daylife-api.php' );
		$daylife = new WP_Daylife_API( get_option( 'daylife' ) );
		$page = isset( $_POST['daylife_page'] ) ? absint( $_POST['daylife_page'] ) : 0;

		$args = array( 'offset' => 8 * $page );
		if ( isset( $_POST['keyword'] ) ) {
			$args['query'] = sanitize_text_field( $_POST['keyword'] );
			$images = $daylife->search_getRelatedImages( $args );
		} else {
			$args['content'] = substr( sanitize_text_field( $_POST['content'] ), 0, 1000 );
			$images = $daylife->content_getRelatedImages( $args );
		}

		$next = 8 == count( $images ) ? $page + 1 : 0;
		$prev = 0;//$next > 2 ? $page - 1 : 0;

		if ( !$images ) {
			echo __( 'No images found.', 'daylife' );
			die;
		}

		if ( $next || $prev ) {
			echo '<div class="tablenav">';
			if ( $next )
				echo '<div class="tablenav-pages"><a href="' . esc_url( '#' . $next ) . '" id="daylife-paging" class="next page-numbers">' . __( 'Next &raquo', 'daylife' ) . '</a></div>';
			if ( $prev )
				echo '<div class="tablenav-pages"><a href="' . esc_url( '#' . $prev ) . '" id="daylife-paging" class="prev page-numbers">' . __( '&laquo; Prev', 'daylife' ) . '</a></div>';
			echo '</div>';
		}

		foreach( $images as $image ) {
			$url = str_replace( '/45x45.jpg', '/125x125.jpg', $image->thumb_url );
			echo '<div class="daylife-image-wrap">';
			echo '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $image->caption ) . '" thumb_url="' . esc_attr( $image->thumb_url ) . '" url="' . esc_attr( $image->url ) . '" credit="' . esc_attr( $image->credit ) . '" caption="' . esc_attr( $image->caption ) . '" daylife_url="' . esc_attr( $image->daylife_url ) . '" image_title="' . esc_attr( $image->image_title ) . '" />';
			echo '<div class="daylife-overlay"></div>';
			echo '<button class="daylife-ste button">' . __( 'Insert into Post', 'daylife' ) . '</button>';
			echo '</div>';
		}
		echo '<div class="clear"></div>';
		die;
	}

	/**
	 * Based off media_sideload_image().  We need this functionality, but we
	 * want the ID not the HTML
	 */
	public function media_sideload_image_get_id( $file, $post_id, $desc = null ) {
		if ( ! empty($file) ) {
			// Download file to temp location
			$tmp = download_url( $file );

			// Set variables for storage
			// fix file filename for query strings
			preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $file, $matches);
			$file_array['name'] = basename($matches[0]);
			$file_array['tmp_name'] = $tmp;

			// If error storing temporarily, unlink
			if ( is_wp_error( $tmp ) ) {
				@unlink($file_array['tmp_name']);
				$file_array['tmp_name'] = '';
			}

			// do the validation and storage stuff
			$id = media_handle_sideload( $file_array, $post_id, $desc );
			// If error storing permanently, unlink
			if ( is_wp_error($id) ) {
				@unlink($file_array['tmp_name']);
			}
			return $id;
		}
		return false;
	}

	public function image_load() {
		check_ajax_referer( 'daylife-add-nonce', 'nonce' );
		if ( ! current_user_can( 'upload_files' ) ) {
			_e( "You don't have permissions to upload files.", 'daylife' );
			die;
		}
		$_POST = stripslashes_deep( $_POST );
		$url = str_replace( '/45x45.jpg', '/600x600.jpg', esc_url_raw( $_POST['thumb_url'] ) );
		$attachment_id = $this->media_sideload_image_get_id( $url, absint( $_POST['post_id'] ), sanitize_text_field( $_POST['image_title'] ) );

		// Set caption to caption + credit
		wp_update_post( array( 'ID' => $attachment_id, 'post_excerpt' => wp_kses_post( $_POST['caption'] ) . "<br /><br />Credit: " . wp_kses_post( $_POST['credit'] ) ) );

		$attachment = get_post( $attachment_id, ARRAY_A );
		$attachment['image-size'] = 'full';
		$attachment['url'] = '';//$_POST['daylife_url'];
		$to_editor = image_media_send_to_editor( wp_get_attachment_url( $attachment_id ), $attachment_id, $attachment );
		echo $to_editor;
		die;
	}
}

new Daylife_Meta_Box;

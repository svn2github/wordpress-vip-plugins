<?php

class Simplechart_Save {

	private $_default_img_type = 'png';
	private $_allowed_template_tags = array( 'nvd3', 'datamaps', 'highchart' );
	private $_errors = array();
	private $_debug_messages = array();
	private $_show_debug_messages = false;

	function __construct(){
		add_action( 'save_post', array( $this, 'save_post_action' ), 10, 1 );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	// use remove-add to prevent infinite loop
	function save_post_action( $post_id ){

		//ignore all other post types
		if ( 'simplechart' !== get_post_type( $post_id ) ){
			return;
		}

		// verify nonce
		if ( empty( $_POST['simplechart-nonce'] ) || ! wp_verify_nonce( $_POST['simplechart-nonce'], 'simplechart_save' ) ){
			return;
		}

		// check user caps
		if ( ! current_user_can( 'edit_post', $post_id ) ){
			return;
		}

		// only worry about the real post
		if ( wp_is_post_revision( $post_id ) ){
			return;
		}

		remove_action( 'save_post', array($this, 'save_post_action' ), 10, 1 );
		$post = get_post( $post_id );
		$this->do_save_post( $post );
		add_action( 'save_post', array($this, 'save_post_action' ), 10, 1 );
	}

	function admin_notices(){
		// skip if not editing single post in Chart post type
		$screen = get_current_screen();
		if ( 'simplechart' !== $screen->post_type || 'post' !== $screen->base ){
			return;
		}

		global $post;

		// print error messages
		$errors = maybe_unserialize( get_post_meta( $post->ID, 'simplechart-errors', true ) );
		if ( is_array( $errors ) ){
			foreach ( $errors as $error ){
				echo '<div class="error"><p>' . esc_html( $error ) . '</p></div>';
			}
		}

		// print debug messages
		if ( $this->_show_debug_messages ){
			$messages = maybe_unserialize( get_post_meta( $post->ID, 'simplechart-debug', true ) );
			if ( is_array( $messages ) ){
				foreach ( $messages as $message ){
					echo '<div class="update-nag"><p>' . esc_html( $message ) . '</p></div>';
				}
			}
		}
	}

	function do_save_post( $post ){

		// handle base64 image string if provided
		if ( ! empty( $_POST['simplechart-png-string'] ) ){
			$this->_save_chart_image( $post, $_POST['simplechart-png-string'], $this->_default_img_type );
		}

		// sanitize and validate JSON formatting of chart data
		$json_data = $this->_validate_json( stripslashes( $_POST['simplechart-data'] ) );
		if ( $json_data ){
			update_post_meta( $post->ID, 'simplechart-data',  $json_data );
		}

		// validate template HTML fragment
		$template_fragment = $this->validate_template_fragment( stripslashes( $_POST['simplechart-template'] ) );
		if ( $template_fragment ){
			update_post_meta( $post->ID, 'simplechart-template',  $template_fragment );
		}

		// save chart URL if provided
		if ( ! empty( $_POST['simplechart-chart-url'] ) ){
			update_post_meta( $post->ID, 'simplechart-chart-url',  esc_url( $_POST['simplechart-chart-url'] ) );
		}

		// save chart ID if provided
		if ( ! empty( $_POST['simplechart-chart-id'] ) ){
			update_post_meta( $post->ID, 'simplechart-chart-id',  sanitize_text_field( $_POST['simplechart-chart-id'] ) );
		}

		// save error messages
		if ( empty( $this->_errors ) ){
			delete_post_meta( $post->ID, 'simplechart-errors' );
		} else {
			update_post_meta( $post->ID, 'simplechart-errors', $this->_errors );
		}

		// save debug messages
		if ( $this->_show_debug_messages && ! empty( $this->_debug_messages ) ) {
			update_post_meta( $post->ID, 'simplechart-debug', $this->_debug_messages, true );
		} else {
			delete_post_meta( $post->ID, 'simplechart-debug' );
		}

	}

	private function _save_chart_image( $post, $data_uri, $img_type ){
		$perm_file_name = 'simplechart_' . $post->ID . '.' . $img_type;
		$temp_file_name = 'temp_' . $perm_file_name;

		// make sure we have valid base64 data then proceed
		$img_data = $this->_process_data_uri( $data_uri, $img_type );

		if ( is_wp_error( $img_data ) ){
			$this->_errors = array_merge( $this->_errors, $img_data->get_error_messages() );
			return false;
		}

		// delete existing chart image if present
		// so we can upload the new one to the same URL
		if ( has_post_thumbnail( $post->ID ) ){
			$thumbnail_id = get_post_thumbnail_id( $post->ID );
			$old_file_path = get_post_meta( $thumbnail_id, '_wp_attached_file', true );
			$this->_debug_messages[] = sprintf( __( 'Found post thumbnail at %s', 'simplechart' ), $old_file_path );
			wp_delete_attachment( $thumbnail_id, true );
		} else {
			$this->_debug_messages[] = __( 'No existing post thumbnail found', 'simplechart' );
		}

		// upload to temporary file location
		$temp_file = wp_upload_bits( $temp_file_name, null, base64_decode( $img_data ) );
		if ( is_wp_error( $temp_file ) ){
			$this->_errors = array_merge( $this->_errors, $temp_file->get_error_messages() ); // translation handled inside wp_upload_bits()
			return false;
		}
		elseif ( false !== $temp_file['error'] ){
			$this->_errors[] = $temp_file['error']; // translation handled inside wp_upload_bits()
			return false;
		}
		$this->_debug_messages[] = sprintf( __( 'wp_upload_bits() stored in %s', 'simplechart' ), $temp_file['file'] );

		// import to media library
		$desc = 'Chart: ' . sanitize_text_field( get_the_title( $post->ID ) );
		$attachment_id = media_handle_sideload( array(
			'name' => $perm_file_name,
			'tmp_name' => $temp_file['file'],
		), $post->ID, $desc);
		$new_file_path = get_post_meta( $attachment_id, '_wp_attached_file', true );
		$this->_debug_messages[] = sprintf( __( 'media_handle_sideload() to %s', 'simplechart' ), $new_file_path );
		$this->_debug_messages[] = $new_file_path === $old_file_path ? __( 'New file path matches old file path', 'simplechart' ) : __( 'New file path does NOT match old file path', 'simplechart' );

		if ( is_wp_error( $attachment_id ) ){
			$this->_errors = array_merge( $this->_errors, $attachment_id->get_error_messages() );
			return false;
		}

		// set as post featured image and store string in postmeta
		set_post_thumbnail( $post->ID, $attachment_id );

		// delete the temporary file!
		if ( file_exists( $temp_file['file'] ) ){
			unlink( $temp_file['file'] );
			$this->_debug_messages[] = sprintf( __( 'Deleted chart image %s', 'simplechart' ), $temp_file['file'] );
		} else {
			$this->_debug_messages[] = sprintf( __( '%s was already deleted', 'simplechart' ), $temp_file['file'] );
		}
	}

	private function _process_data_uri( $data_uri, $img_type ){
		$data_prefix = 'data:image/' . $img_type . ';base64,';

		// validate input format for data URI
		if ( 0 !== strpos( $data_uri, $data_prefix ) ){
			$this->_errors[] = __( 'Incorrect data URI formatting', 'simplechart' );
		}

		// remove prefix to get base64 data
		$img_data = str_replace( $data_prefix, '', $data_uri );

		return $img_data;
	}

	/**
	 * HTML fragment to render the chart must be a single tag in one of the allowed tags, with no children
	 * use this instead of wp_kses_* because there are a large number of potential attributes for these tags
	 */
	function validate_template_fragment( $fragment ){
		libxml_use_internal_errors( true );
		$el = simplexml_load_string( $fragment );

		if ( $el && in_array( $el->getName(), $this->_allowed_template_tags ) && 0 === count( $el->children() ) ){
			return $fragment;
		} else {
			foreach ( libxml_get_errors() as $error ){
				$this->_errors[] = sprintf( __( 'SimpleXML error in template fragment: %s', 'simplechart' ), $error->message );
			}
			libxml_clear_errors();
			return false;
		}
	}

	private function _validate_json( $data ){
		if ( 'undefined' === $data ){
			$this->_errors[] = "JS app set value of input to 'undefined'";
			return false;
		}
		elseif ( $decoded = json_decode( $data ) ){
			return json_encode( $decoded ); // returns a valid JSON string!
		}
		elseif ( function_exists( 'json_last_error_msg' ) ){
			$this->_errors[] = sprintf( __( 'JSON error: %s', 'simplechart' ), json_last_error_msg() );
			return false;
		}
		elseif ( function_exists( 'json_last_error' ) ) {
			$this->_errors[] = sprintf( __( 'JSON error code: %s', 'simplechart' ), json_last_error() );
			return false;
		}
		else {
			$this->_errors[] = __( 'Attempted to save invalid JSON', 'simplechart' );;
			return false;
		}
	}

}
<?php

class Post_Selection_UI {

	public static function init() {
		add_action('wp_ajax_psu_box', array(__CLASS__, 'handle_ajax_search'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'));
	}

	public static function admin_enqueue_scripts() {
		wp_enqueue_style('post-selection-ui', self::local_url( 'post-selection-ui.css', __DIR__ ), array() );
		wp_enqueue_script( 'post-selection-ui', self::local_url( 'post-selection-ui.js', __DIR__ ), array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ), null, true );

		wp_localize_script( 'post-selection-ui', 'PostSelectionUI', array(
			'nonce' => wp_create_nonce( 'psu_search' ),
			'spinner' => admin_url( 'images/wpspin_light.gif' ),
			'clearConfirmMessage' => __( 'Are you sure you want to clear the selected items?' ),
		) );

	}

	public static function local_url($relative_path, $plugin_path) {
		$template_dir = get_template_directory();

		foreach ( array( 'template_dir', 'plugin_path' ) as $var ) {
			$$var = str_replace( '\\', '/', $$var ); // sanitize for Win32 installs
			$$var = preg_replace( '|/+|', '/', $$var );
		}
		if ( 0 === strpos( $plugin_path, $template_dir ) ) {
			$url = get_template_directory_uri();
			$folder = str_replace( $template_dir, '', dirname( $plugin_path ) );
			if ( '.' != $folder ) {
				$url .= '/' . ltrim( $folder, '/' );
			}
			if ( !empty( $relative_path ) && is_string( $relative_path ) && strpos( $relative_path, '..' ) === false ) {
				$url .= '/' . ltrim( $relative_path, '/' );
			}
			return $url;
		} else {
			return plugins_url( $relative_path, $plugin_path );
		}
	}

	public static function handle_ajax_search() {
		check_ajax_referer('psu_search');

		$args = array(
			'post_type' => array()
		);

		if (!empty($_GET['post_type']) ) {
			$unsanitized_post_types = array_map('sanitize_key', explode(',', $_GET['post_type']));
			foreach($unsanitized_post_types as $post_type) {
			 if(($post_type_obj = get_post_type_object( $post_type )) && current_user_can($post_type_obj->cap->read)) {
				 $args['post_type'][] = $post_type;
			 }
			}
		}

		if (count($args['post_type']) < 1) {
			die('-1');
		}
		if (!empty($_GET['paged'])) {
			$args['paged'] = absint($_GET['paged']);
		}
		if (!empty($_GET['s'])){
			$args['s'] = sanitize_text_field( $_GET['s'] );
		}
		if ( ! empty( $_GET['order'] ) && ( in_array( strtolower( $_GET['order'] ), array( 'asc', 'desc' ) ) ) ) {
			$args['order'] = $_GET['order'];
		}
		if (!empty($_GET['orderby'])) {
			$args['orderby'] = sanitize_text_field( $_GET['orderby'] );
		}
		if ( !empty($_GET['post_status']) ) {
			$post_statuses = explode( ',', $_GET['post_status'] );

			$defined_statuses = array_keys( get_post_statuses() );
			$allowed_statuses = array_merge( $defined_statuses, array( 'inherit', 'any' ) );
			$filtered_statuses = array_intersect( $post_statuses, $allowed_statuses );
			if ( !empty( $filtered_statuses ) ) {
				$args['post_status'] = $filtered_statuses;
			}
		}
		if ( !empty($_GET['include']) ) {
			$args['post__in'] = array_filter(array_map('intval',explode(',', $_GET['include'])));
		}

		if (!empty($_GET['exclude'])) {
			$selected = array_map('intval', explode(',', $_GET['exclude']));
		} else {
			$selected = array();
		}

		$name = 'foobar';
		if ( !empty($_GET['name']) ) {
			$_name = sanitize_text_field($_GET['name']);
			if ($_name) {
				$name = $_name;
			}
		}

		$psu_box = new Post_Selection_Box($name, array('post_type' => $args['post_type'], 'selected' => $selected));

		$response = new stdClass();
		$response->rows = $psu_box->render_results($args);
		die(json_encode($response));
	}

}
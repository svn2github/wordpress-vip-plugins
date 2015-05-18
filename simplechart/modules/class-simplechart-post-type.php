<?php

/*
 * This class manages what happens inside the "Chart" post type
 */

class Simplechart_Post_Type {

	public function __construct(){
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'after_setup_theme', array( $this, 'support_thumbnails' ) );
	}

	public function support_thumbnails(){
		if ( ! current_theme_supports( 'post-thumbnails' ) ){
			add_theme_support( 'post-thumbnails', array( 'simplechart' ) );
		}
	}

	public function register_post_type() {
		$args = array(
			'labels' => array(
				'name' => esc_html__( 'Charts', 'simplechart' ),
				'singular_name' => esc_html__( 'Chart', 'simplechart' ),
				'plural_name' => esc_html__( 'All Charts', 'simplechart' ),
				'add_new' => esc_html__( 'Add New', 'simplechart' ),
				'add_new_item' => esc_html__( 'Add New Chart', 'simplechart' ),
				'edit_item' => esc_html__( 'Edit Chart', 'simplechart' ),
				'new_item' => esc_html__( 'New Chart', 'simplechart' ),
				'view_item' => esc_html__( 'View Chart', 'simplechart' ),
				'search_items' => esc_html__( 'Search Charts', 'simplechart' ),
				'not_found' => esc_html__( 'No charts found', 'simplechart' ),
				'not_found_in_trash' => esc_html__( 'No charts found in Trash', 'simplechart' ),
			),

			// external publicness
			'public' => true,
			'exclude_from_search' => true,
			'publicly_queryable' => true,

			// wp-admin publicness
			'show_in_nav_menus' => false,
			'show_ui' => true,

			// just below Media
			//'menu_position' => 11,

			// enable single pages without permalink for checking template rendering
			'rewrite' => false,
			'has_archive' => false,

			'menu_icon' => 'dashicons-chart-pie',
			'supports' => array( 'title', 'thumbnail' )
		);

		register_post_type( 'simplechart', $args );
	}

	public function render_meta_box( $post, $args ) {
		global $simplechart;
		$plugin_dir_path = $args['args'][0];
		$json_data = $args['args'][1];
		$meta_box_html = file_get_contents( $plugin_dir_path . 'templates/meta-box.html' );
		$nonce = wp_create_nonce( 'simplechart_save' );
		$template_html = get_post_meta( $post->ID, 'simplechart-template', true );
		$chart_url = get_post_meta( $post->ID, 'simplechart-chart-url', true );
		$chart_id = get_post_meta( $post->ID, 'simplechart-chart-id', true );
		$app_url = $simplechart->get_config( 'web_app_iframe_src' );

		$html = sprintf( $meta_box_html,
			__( 'Launch Simplechart App', 'simplechart' ),
			__( 'Clear Simplechart Data', 'simplechart' ),
			esc_url( $app_url ),
			'simplechart-data',
			json_encode( json_decode( $json_data ) ), // escapes without converting " to &quot
			$simplechart->save->validate_template_fragment( $template_html ),
			__( 'Close Modal', 'simplechart' ),
			esc_attr( $nonce ),
			json_encode( json_decode( $json_data ) ),
			esc_attr( $template_html ),
			esc_url( $chart_url ),
			esc_attr( $chart_id )
		);

		echo $html;
	}


}

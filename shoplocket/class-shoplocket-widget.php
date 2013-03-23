<?php

class ShopLocket_Widget extends WP_Widget {
	function __construct() {
		parent::__construct( false, __( 'ShopLocket', 'shoplocket' ), array(
			'description' => __( 'Embed your ShopLocket product', 'shoplocket' ),
			'classname' => 'widget-shoplocket'
		), array(
			'width' => 325,
		) );
	}

	function widget( $args, $instance ) {
		$instance = ShopLocket::normalize_args( $instance );

		echo $args['before_widget'];
		echo $args['before_title'] . $instance['title'] . $args['after_title'];
		unset( $instance['title'] ); // in case the shortcode also has title output
		echo ShopLocket::render_shortcode( $instance );
		echo $args['after_widget'];
	}

	function update( $new_instance, $old_instance ) {
		$instance = array();
		$new_instance = ShopLocket::normalize_args( $new_instance );

		if ( isset( $new_instance['code'] ) ) {
			$code = trim( $new_instance['code'] );

			if ( ShopLocket::is_shoplocket_url( $code ) ) {
				$instance['id'] = ShopLocket::get_id_from_url( $code );
			} elseif ( preg_match( ShopLocket::IFRAME_REGEX_PATTERN, $code, $matches ) ) {
				$instance['id'] = sanitize_text_field( $matches[2] );
			} else {
				$instance['id'] = sanitize_text_field( $code );
			}
			// TODO: do a remote test to check valid?
		}

		if ( isset( $new_instance['title'] ) )
			$instance['title'] = sanitize_text_field( $new_instance['title'] );
		
		return $instance;
	}

	function form( $instance ) {
		$instance = ShopLocket::normalize_args( $instance );

		if ( ! empty( $instance['id'] ) )
			$instance['url'] = ShopLocket::get_product_url_from_id( $instance['id'] );

		// TODO: show error on invalid product id
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">
				<?php esc_html_e( 'Title:', 'shoplocket' ); ?>
				<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'code' ); ?>">
				<?php _e( 'Product:' ); ?>
			</label>
			
			<!-- TODO: Add Refresh Button -->
			
			<select name="<?php echo $this->get_field_name( 'code' ); ?>">
				<option data-url="<?php ShopLocket::get_product_url_from_id( $instance['id'] ); ?>" value="">&#8212; Select &#8212;</option>
				<?php echo ShopLocket::shoplocket_get_options_for_product_list(get_option("shoplocket_products_json"),$instance['id']); ?>  
			</select>
		</p>
		<?php
	}

	function register() {
		register_widget( __CLASS__ );
	}
}

add_action( 'widgets_init', array( 'ShopLocket_Widget', 'register' ) );
<?php
/*
Plugin Name: Powered by WordPress.com VIP
Description: Provide functions and a widget that can be used to display a 'Powered by WordPress.com VIP" text or image link.
Version: 1.0
Author: Nick Momrik

This plugin is automatically enabled on all WordPress.com VIP blogs and is provided here for your local test environments.
Use the widget in your sidebar or the functions in your theme.
*/

/**
 * The "Powered by WordPress.com VIP widget".
 *
 * @link http://vip.wordpress.com/documentation/powered-by-wordpress-com-vip/ Powered By WordPress.com VIP
 */
class WPCOM_Widget_VIP_Powered extends WP_Widget {

	/**
	 * Constructor. Sets up widget options.
	 */
	function WPCOM_Widget_VIP_Powered() {
		$widget_ops = array('classname' => 'widget_vip_powered_wpcom', 'description' => __( "Powered by WordPress.com VIP") );
                $control_ops = array( 'width' => 295 );

		$this->WP_Widget('vip-powered', __('VIP Powered'), $widget_ops, $control_ops );
	}

	/**
	 * Outputs the widget content
	 *
	 * @param array $args Override widget options
	 * @param array $instance This widget's settings
	 */
	function widget( $args, $instance ) {
		extract( $args );
		
		$display = $instance['display'];
		
		echo $before_widget;
		echo vip_powered_wpcom($display);
		echo $after_widget;
	}

	/**
	 * Option update callback when the widget's settings are updated
	 *
	 * @param array $new_instance The new settings
	 * @param array $old_instance The widget's old settings (pre-save)
	 * @return array The new settings
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['display'] = $new_instance['display'];
		$instance['bg_color_preview'] = $new_instance['bg_color_preview'];

		return $instance;
	}

	/**
	 * The widget settings form for the wp-admin Widgets screen
	 *
	 * @param array $instance The widget's settings
	 */
	function form( $instance ) {
		//Defaults
		$instance         = wp_parse_args( (array) $instance, array( 'display' => 'text' ) );
		$display          = !empty( $instance['display']          ) ? $instance['display']          : 'text';
		$bg_color_preview = !empty( $instance['bg_color_preview'] ) ? $instance['bg_color_preview'] : '#ffffff';
		$choices          = array(
			'text' => 'Text Link',
			1      => 'Dark 166x26 Image',
			2      => 'Dark 208x56 Image',
			3      => 'Dark 295x56 Image',
			4      => 'Light 166x26 Image',
			5      => 'Light 208x56 Image',
			6      => 'Light 295x56 Image'
		);

		echo "<p>Display option. Images have a transparent background.<br />
			<select id='" . $this->get_field_id( 'display' ) . "' name='" . $this->get_field_name( 'display' ) . "'>";

		foreach ( $choices as $value => $text ) {
			$selected = '';
			if ( $display == $value) 
				$selected = " selected='selected'";

			echo "<option value='$value'$selected>$text</option>";
		}
		echo "</select></p>
			<p><label for='" . $this->get_field_id('bg_color_preview') . "'>Background Color (only used for preview):
			<input class='small-text' id='" . $this->get_field_id('bg_color_preview') . "' name='" . $this->get_field_name('bg_color_preview') . "' type='text' value='" . esc_attr($bg_color_preview) . "' /> ex. #ffffff
			</label></p>

			<p>Preview (Save to refresh):</p><div style='background:$bg_color_preview'>" . vip_powered_wpcom( $display ) . "</div>";

	}
}

/**
 * Registers the "Powered by WordPress.com VIP" widget
 */
function vip_powered_wpcom_widget_init() {
	register_widget('WPCOM_Widget_VIP_Powered');
}
add_action( 'widgets_init', 'vip_powered_wpcom_widget_init' );

/**
 * Returns a link the WordPress.com VIP site wrapped around an image (the VIP logo).
 *
 * @param int $image Which variant of the VIP logo to use; between 1-6.
 * @return string HTML
 */
function vip_powered_wpcom_img_html( $image ) {
	$vip_powered_wpcom_images = array(
		//image file, width, height
		1 => array('vip-powered-light-small.png', 187, 26),
		2 => array('vip-powered-light-normal.png', 209, 56),
		3 => array('vip-powered-light-long.png', 305, 56),
		4 => array('vip-powered-dark-small.png', 187, 26),
		5 => array('vip-powered-dark-normal.png', 209, 56),
		6 => array('vip-powered-dark-long.png', 305, 56)
		);

		if ( array_key_exists( $image, $vip_powered_wpcom_images ) )
			return '<a href="' . esc_url( vip_powered_wpcom_url() ) . '" rel="generator nofollow" class="powered-by-wpcom"><img src="' . esc_url( plugins_url( 'images/' . $vip_powered_wpcom_images[$image][0], __FILE__ ) ) . '" width="' . esc_attr( $vip_powered_wpcom_images[$image][1] ) . '" height="' . esc_attr( $vip_powered_wpcom_images[$image][2] ) . '" alt="'. esc_attr__( 'Powered by WordPress.com VIP' ) .'" /></a>';
		else
			return '';
}

/**
 * Returns the "Powered by WordPress.com VIP" widget's content.
 *
 * @link http://vip.wordpress.com/documentation/code-and-theme-review-process/ Code Review
 * @link http://vip.wordpress.com/documentation/powered-by-wordpress-com-vip/ Powered By WordPress.com VIP
 * @param string $display Optional. Either: 1-6 or "text"*. If an integer, wrap an image in the VIP link. Otherwise, just return the link.  
 * @param string $before_text Optional. Text to go in front of the VIP link. Defaults to 'Powered by '.
 * @return string HTML
 */
function vip_powered_wpcom( $display = 'text', $before_text = 'Powered by ' ) {
	switch ($display) {
		case 'text':
			$output = $before_text . '<a href="' . esc_url( vip_powered_wpcom_url() ) . '" rel="generator nofollow" class="powered-by-wpcom">WordPress.com VIP</a>';
			break;
		case 1:
		case 2:
		case 3:
		case 4:
		case 5:
		case 6:
			$output = vip_powered_wpcom_img_html($display);
			break;
		default:
			$output = '';
	}

	return $output;
}

/**
 * Returns the URL to the WordPress.com VIP site
 *
 * @return string
 */
function vip_powered_wpcom_url() {
	return 'http://vip.wordpress.com/';
}

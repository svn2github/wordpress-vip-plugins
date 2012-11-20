<?php
/*
Plugin Name: Powered by WordPress.com VIP
Description: Provide functions and a widget that can be used to display a 'Powered by WordPress.com VIP" text or image link.
Version: 1.0
Author: Nick Momrik

This plugin is automatically enabled on all WordPress.com VIP blogs and is provided here for your local test environments.
Use the widget in your sidebar or the functions in your theme.
*/

class WPCOM_Widget_VIP_Powered extends WP_Widget {
	function WPCOM_Widget_VIP_Powered() {
		$widget_ops = array('classname' => 'widget_vip_powered_wpcom', 'description' => __( "Powered by WordPress.com VIP") );
                $control_ops = array( 'width' => 295 );

		$this->WP_Widget('vip-powered', __('VIP Powered'), $widget_ops, $control_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );
		
		$display = $instance['display'];
		
		echo $before_widget;
		echo vip_powered_wpcom($display);
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['display'] = $new_instance['display'];
		$instance['bg_color_preview'] = $new_instance['bg_color_preview'];

		return $instance;
	}

	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array('display' => 'text') );

		$display = $instance['display'];
		$bg_color_preview = $instance['bg_color_preview'];
		if ( '' == $bg_color_preview )
			$bg_color_preview = '#ffffff';

		$choices = array(
			'text' => 'Text Link',
			1 => 'Dark 166x26 Image',
			2 => 'Dark 208x56 Image',
			3 => 'Dark 295x56 Image',
			4 => 'Light 166x26 Image',
			5 => 'Light 208x56 Image',
			6 => 'Light 295x56 Image'
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

add_action( 'widgets_init', 'vip_powered_wpcom_widget_init' );
function vip_powered_wpcom_widget_init() {
	register_widget('WPCOM_Widget_VIP_Powered');
}

function vip_powered_wpcom_img_html( $image ) {
	$vip_powered_wpcom_images = array(
		//image file, width, height
		1 => array('vip-powered-wpcom-dark-small.png', 166, 26),
		2 => array('vip-powered-wpcom-dark.png', 208, 56),
		3 => array('vip-powered-wpcom-dark-long.png', 295, 56),
		4 => array('vip-powered-wpcom-light-small.png', 166, 26),
		5 => array('vip-powered-wpcom-light.png', 208, 56),
		6 => array('vip-powered-wpcom-light-long.png', 295, 56)
		);

		if ( array_key_exists( $image, $vip_powered_wpcom_images ) )
				return '<a href="' . esc_url( vip_powered_wpcom_url() ) . '" rel="generator nofollow"><img src="' . esc_url( plugins_url( 'images/' . $vip_powered_wpcom_images[$image][0], __FILE__ ) ) . '" width="' . esc_attr( $vip_powered_wpcom_images[$image][1] ) . '" height="' . esc_attr( $vip_powered_wpcom_images[$image][2] ) . '" /></a>';
		else
			return '';
}

function vip_powered_wpcom( $display = 'text', $before_text = 'Powered by ' ) {
	switch ($display) {
		case 'text':
			$output = $before_text . '<a href="' . esc_url( vip_powered_wpcom_url() ) . '" rel="generator nofollow">WordPress.com VIP</a>';
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

function vip_powered_wpcom_url() {
	return 'http://vip.wordpress.com/';
}

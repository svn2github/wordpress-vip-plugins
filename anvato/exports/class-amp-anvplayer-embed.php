<?php

if(defined(AMP__DIR__))
{
	require_once( AMP__DIR__ . '/includes/embeds/class-amp-base-embed-handler.php' );
}

class ANVATO_AMP_Anvplayer_Embed_Handler extends AMP_Base_Embed_Handler {

	// This should be kept up to date!
	private static $script_slug = 'amp-iframe';
	private static $script_src = 'https://cdn.ampproject.org/v0/amp-iframe-0.1.js';

	public function register_embed() {
		add_shortcode( 'anvplayer', array( $this, 'shortcode' ) );
	} // register_embed

	public function unregister_embed() {
		remove_shortcode( 'anvplayer' );
	} // unregister_embed

	public function get_scripts() {
		if ( ! $this->did_convert_elements ) {
			return array();
		}

		return array( self::$script_slug => self::$script_src );
	} // get_scripts

	public function shortcode( $attr ) {

		$parameters = anvato_shortcode_get_parameters__for_exports( $attr );

		$iframe_src = 'https://w3.cdn.anvato.net/player/prod/v3/anvload.html?key=' . base64_encode( wp_json_encode( $parameters['json'] ) );

		$iframe_width = 640;
		if ( !empty( $parameters['player']['width'] ) && 'px' === $parameters['player']['width_type'] ) {
			$iframe_width = $parameters['player']['width'];
		}
		$iframe_height = 360;
		if ( !empty( $parameters['player']['height'] ) && 'px' === $parameters['player']['height_type'] ) {
			$iframe_height = $parameters['player']['height'];
		}

		return $this->render( array(
			'src' => $iframe_src,
			'width' => $iframe_width,
			'height' => $iframe_height,
			'placeholder_image_src' => '',
		) );

	} // shortcode

	public function render( $args ) {

		$html = '';
		if ( empty( $args ) ) return $html;

		$this->did_convert_elements = true;

		// Construct the element
		$iframe_html =
			'<amp-iframe ' .
				'src="' . esc_url( $args['src'] ) . '" ' .
				'width="' . esc_attr( $args['width'] ) . '" height="' . esc_attr( $args['height'] ) . '" ' .
				'sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox" ' .
				'allowfullscreen ' .
				'layout="responsive" ' .
				'scrolling="no" ' .
				'frameborder="0" ' .
			'>';

		// placeholder
		if ( !empty( $args['placeholder_image_src'] )) {
			$iframe_html .= '<amp-img layout="fill" src="' . esc_url( $args['placeholder_image_src'] ) . '" placeholder></amp-img>';
		} else {
			// default placeholder
			$iframe_html .= '<div placeholder="" class="amp-wp-iframe-placeholder"></div>';
		}

		$iframe_html .= '</amp-iframe>';

		return $iframe_html;

	} // render

} // class ANVATO_AMP_Anvplayer_Embed_Handler

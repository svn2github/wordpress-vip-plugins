<?php

require_once( AMP__DIR__ . '/includes/embeds/class-amp-base-embed-handler.php' );

class GraphiqSearch_AMP_Embed extends AMP_Base_Embed_Handler {

	private static $script_slug = 'amp-iframe';
	private static $script_src = 'https://cdn.ampproject.org/v0/amp-iframe-0.1.js';

	public function register_embed() {
		add_shortcode( 'graphiq', array( $this, 'shortcode' ) );
		add_shortcode( GRAPHIQ_OLD_SLUG, array( $this, 'shortcode' ) );
	}

	public function unregister_embed() {
		remove_shortcode( 'graphiq' );
		remove_shortcode( GRAPHIQ_OLD_SLUG );
	}

	public function get_scripts() {
		if ( ! $this->did_convert_elements ) {
			return array();
		}
		return array( self::$script_slug => self::$script_src );
	}

	public function shortcode( $attr ) {
		if ( empty( $attr ) ) {
			return '';
		}

		$this->did_convert_elements = true;

		$instance = GraphiqSearch::get_instance();

		$arguments = $instance->parse_shortcode_attributes( $attr );

		return $instance->render( 'embed-code-amp', $arguments );
	}
}

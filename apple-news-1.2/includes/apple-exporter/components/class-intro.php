<?php
namespace Apple_Exporter\Components;

/**
 * Some Exporter_Content object might have an intro parameter.
 * This component does not need a node so no need to implement match_node.
 *
 * @since 0.2.0
 */
class Intro extends Component {

	/**
	 * Register all specs for the component.
	 *
	 * @access public
	 */
	public function register_specs() {
		$this->register_spec(
			'json',
			__( 'JSON', 'apple-news' ),
			array(
				'role' => 'intro',
				'text' => '#text#',
			)
		);

		$this->register_spec(
			'default-intro',
			__( 'Style', 'apple-news' ),
			array(
				'fontName' => '#body_font#',
				'fontSize' => '#body_size#',
				'lineHeight' => '#body_line_height#',
				'textColor' => '#body_color#',
			)
		);
	}

	/**
	 * Build the component.
	 *
	 * @param string $text
	 * @access protected
	 */
	protected function build( $text ) {
		$this->register_json(
			'json',
			array(
				'#text#' => $text . "\n",
			)
	 	);

		$this->set_style();
	}

	/**
	 * Set the style for the component.
	 *
	 * @access private
	 */
	private function set_style() {
		$this->register_style(
			'default-intro',
			'default-intro',
			array(
				'#body_font#' => $this->get_setting( 'body_font' ),
				'#body_size#' => intval( $this->get_setting( 'body_size' ) ),
				'#body_line_height#' => intval( $this->get_setting( 'body_line_height' ) ),
				'#body_color#' => $this->get_setting( 'body_color' ),
			),
			'textStyle'
		);
	}

}


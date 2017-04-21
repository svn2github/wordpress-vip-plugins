<?php
namespace Apple_Exporter\Components;

use \Apple_Exporter\Exporter as Exporter;

/**
 * A byline normally describes who wrote the article, the date, etc.
 *
 * @since 0.2.0
 */
class Byline extends Component {

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
				'role' => 'byline',
				'text' => '#text#',
			)
		);

		$this->register_spec(
			'default-byline',
			__( 'Style', 'apple-news' ),
			array(
				'textAlignment' => '#text_alignment#',
				'fontName' => '#byline_font#',
				'fontSize' => '#byline_size#',
				'lineHeight' => '#byline_line_height#',
				'tracking' => '#byline_tracking#',
				'textColor' => '#byline_color#',
			)
		);

		$this->register_spec(
			'byline-layout',
			__( 'Layout', 'apple-news' ),
			array(
				'margin' => array(
					'top' => 10,
					'bottom' => 10,
				),
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
				'#text#' => $text,
			)
	 	);

		$this->set_default_style();
		$this->set_default_layout();
	}

	/**
	 * Set the default style for the component.
	 *
	 * @access private
	 */
	private function set_default_style() {
		$this->register_style(
			'default-byline',
			'default-byline',
			array(
				'#text_alignment#' => $this->find_text_alignment(),
				'#byline_font#' => $this->get_setting( 'byline_font' ),
				'#byline_size#' => intval( $this->get_setting( 'byline_size' ) ),
				'#byline_line_height#' => intval( $this->get_setting( 'byline_line_height' ) ),
				'#byline_tracking#' => intval( $this->get_setting( 'byline_tracking' ) ) / 100,
				'#byline_color#' => $this->get_setting( 'byline_color' ),
			),
			'textStyle'
		);
	}

	/**
	 * Set the default layout for the component.
	 *
	 * @access private
	 */
	private function set_default_layout() {
		$this->register_full_width_layout(
			'byline-layout',
			'byline-layout',
			array(),
			'layout'
		);
	}

}


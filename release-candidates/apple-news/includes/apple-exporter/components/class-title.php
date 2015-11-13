<?php
namespace Apple_Exporter\Components;

class Title extends Component {

	/**
	 * Build the component.
	 *
	 * @param string $text
	 * @access protected
	 */
	protected function build( $text ) {
		$this->json = array(
			'role' => 'title',
			'text' => $text,
		);

		$this->set_style();
	}

	/**
	 * Set the style for the component.
	 *
	 * @access private
	 */
	private function set_style() {
		$this->json[ 'textStyle' ] = 'default-title';
		$this->register_style( 'default-title', array(
			'fontName'      => $this->get_setting( 'header_font' ),
			'fontSize'      => intval( $this->get_setting( 'header1_size' ) ),
			'lineHeight'    => intval( $this->get_setting( 'header_line_height' ) ),
			'textColor'     => $this->get_setting( 'header_color' ),
			'textAlignment' => $this->find_text_alignment(),
		) );
	}

}


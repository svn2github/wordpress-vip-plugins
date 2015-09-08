<?php
namespace Apple_Exporter\Components;

/**
 * An HTML's blockquote representation.
 *
 * @since 0.2.0
 */
class Quote extends Component {

	/**
	 * Look for node matches for this component.
	 *
	 * @param DomNode $node
	 * @return mixed
	 * @static
	 * @access public
	 */
	public static function node_matches( $node ) {
		if ( 'blockquote' == $node->nodeName ) {
			return $node;
		}

		return null;
	}

	/**
	 * Build the component.
	 *
	 * @param string $text
	 * @access protected
	 */
	protected function build( $text ) {
		preg_match( '#<blockquote.*?>(.*?)</blockquote>#si', $text, $matches );
		$text = $matches[1];

		$this->json = array(
			'role'   => 'quote',
			'text'   => $this->markdown->parse( $text ),
			'format' => 'markdown',
		);

		$this->set_style();
		$this->set_layout();
	}

	/**
	 * Set the layout for the component.
	 *
	 * @access private
	 */
	private function set_layout() {
		$this->json['layout'] = 'quote-layout';
		$this->register_layout( 'quote-layout', array(
			'margin' => array( 'top' => 15, 'bottom' => 15 ),
		) );
	}

	/**
	 * Set the style for the component.
	 *
	 * @access private
	 */
	private function set_style() {
		$this->json[ 'textStyle' ] = 'default-pullquote';
		$this->register_style( 'default-pullquote', array(
			'fontName'      => $this->get_setting( 'pullquote_font' ),
			'fontSize'      => intval( $this->get_setting( 'pullquote_size' ) ),
			'textColor'     => $this->get_setting( 'pullquote_color' ),
			'textTransform' => $this->get_setting( 'pullquote_transform' ),
			'lineHeight'    => intval( $this->get_setting( 'pullquote_line_height' ) ),
			'textAlignment' => $this->find_text_alignment(),
		) );
	}

}


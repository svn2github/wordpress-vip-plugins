<?php
namespace Apple_Exporter\Components;

/**
 * An HTML's divider (hr) representation.
 *
 * @since 0.2.0
 */
class Divider extends Component {

	/**
	 * Look for node matches for this component.
	 *
	 * @param DomNode $node
	 * @return mixed
	 * @static
	 * @access public
	 */
	public static function node_matches( $node ) {
		if ( 'hr' == $node->nodeName ) {
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
		$this->json = array(
			'role'   => 'divider',
			'layout' => 'divider-layout',
			'stroke' => array( 'color' => '#E6E6E6', 'width' => 1 ),
		);

		$this->register_full_width_layout( 'divider-layout', array(
			'margin' => array( 'top' => 25, 'bottom' => 25 )
		) );
	}

}


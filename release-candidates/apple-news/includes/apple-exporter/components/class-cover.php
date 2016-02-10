<?php
namespace Apple_Exporter\Components;

use \Apple_Exporter\Exporter as Exporter;

/**
 * A cover is optional and displayed at the very top of the article. It's
 * loaded from the Exporter_Content's cover attribute, if present.
 * This component does not need a node so no need to implement match_node.
 *
 * In a WordPress context, the Exporter_Content's cover attribute is a post's
 * thumbnail, a.k.a featured image.
 *
 * @since 0.2.0
 */
class Cover extends Component {

	/**
	 * Build the component.
	 *
	 * @param string $text
	 * @access protected
	 */
	protected function build( $url ) {
		$filename = \Apple_News::get_filename( $url );

		// Save image into bundle
		$this->bundle_source( $filename, $url );

		$this->json = array(
			'role' => 'container',
			'layout' => 'headerContainerLayout',
			'style' => array(
				'fill' => array(
					'type' => 'image',
					'URL' => 'bundle://' . $filename,
					'fillMode' => 'cover',
				),
			),
			'behavior' => array(
				'type' => 'background_parallax',
			),
		);

		$this->set_default_layout();
	}

	/**
	 * Set the default layout for the component.
	 *
	 * @access private
	 */
	private function set_default_layout() {
		$this->register_full_width_layout( 'headerContainerLayout', array(
			'ignoreDocumentMargin' => true,
			'minimumHeight'        => '50vh',
			'margin'               => array( 'top' => 0, 'bottom' => 25 ),
		) );
	}

}


<?php
namespace Apple_Exporter\Components;

/**
 * Represents an Article Format Advertisment. It gets generated automatically
 * so not much to do here but define the static JSON.
 *
 * @since 0.4.0
 */
class Advertisement extends Component {

	/**
	 * Build the component.
	 *
	 * @param string $text
	 * @access protected
	 */
	protected function build( $text ) {
		$this->json = array(
			'role'       => 'banner_advertisement',
			'bannerType' => 'standard',
		);

		$this->set_layout();
	}

	/**
	 * Set the layout for the component.
	 *
	 * @access private
	 */
	private function set_layout() {
		$this->json['layout'] = 'advertisement-layout';
		$this->register_full_width_layout( 'advertisement-layout', array(
			'margin' => array( 'top' => 25, 'bottom' => 25 ),
		) );
	}

}

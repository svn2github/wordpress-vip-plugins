<?php

use \Apple_Exporter\Exporter_Content as Exporter_Content;
use \Apple_Exporter\Settings as Settings;
use \Apple_Exporter\Builders\Component_Text_Styles as Component_Text_Styles;

class Component_Text_Styles_Tests extends WP_UnitTestCase {

	public function setup() {
		$this->settings = new Settings();
		$this->content  = new Exporter_Content( 1, 'My Title', '<p>Hello, World!</p>' );
	}

	public function testBuiltArray() {
		$styles = new Component_Text_Styles( $this->content, $this->settings );
		$styles->register_style( 'some-name', 'my value' );
		$result = $styles->to_array();

		$this->assertEquals( 1, count( $result ) );
		$this->assertEquals( 'my value', $result[ 'some-name' ] );
	}

}

<?php

require_once __DIR__ . '/class-component-testcase.php';

use Apple_Exporter\Components\Divider as Divider;

class Divider_Test extends Component_TestCase {

	public function testBuildingRemovesTags() {
		$component = new Divider( '<hr/>', null, $this->settings,
			$this->styles, $this->layouts );
		$result = $component->to_array();

		$this->assertEquals( 'divider', $result['role'] );
		$this->assertEquals( 'divider-layout', $result['layout'] );
		$this->assertNotNull( $result['stroke'] );
	}

	public function testFilter() {
		$component = new Divider( '<hr/>', null, $this->settings,
			$this->styles, $this->layouts );

		add_filter( 'apple_news_divider_json', function( $json ) {
			$json['layout'] = 'fancy-layout';
			return $json;
		} );

		$result = $component->to_array();
		$this->assertEquals( 'fancy-layout', $result['layout'] );
	}

}


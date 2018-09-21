<?php

require_once __DIR__ . '/class-component-testcase.php';

use Apple_Exporter\Components\Advertisement as Advertisement;

class Advertisement_Test extends Component_TestCase {

	public function testGeneratedJSON() {
		$component = new Advertisement( null, null, $this->settings, $this->styles,
			$this->layouts );
		$json = $component->to_array();

		$this->assertEquals( 'banner_advertisement', $json['role'] );
		$this->assertEquals( 'standard', $json['bannerType'] );
	}

	public function testFilter() {
		$component = new Advertisement( null, null, $this->settings, $this->styles,
			$this->layouts );
		$json = $component->to_array();

		add_filter( 'apple_news_advertisement_json', function( $json ) {
			$json['bannerType'] = 'double_height';
			return $json;
		} );

		$json = $component->to_array();
		$this->assertEquals( 'double_height', $json['bannerType'] );
	}

}


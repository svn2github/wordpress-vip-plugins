<?php

require_once __DIR__ . '/class-component-testcase.php';

use Apple_Exporter\Components\Audio as Audio;

class Audio_Test extends Component_TestCase {

	public function testGeneratedJSON() {
		$workspace = $this->prophet->prophesize( '\Exporter\Workspace' );

		// Pass the mock workspace as a dependency
		$component = new Audio( '<audio><source src="http://someurl.com/audio-file.mp3?some_query=string"></audio>',
			$workspace->reveal(), $this->settings, $this->styles, $this->layouts );

		$json = $component->to_array();
		$this->assertEquals( 'audio', $json['role'] );
		$this->assertEquals( 'http://someurl.com/audio-file.mp3?some_query=string', $json['URL'] );
	}

	public function testFilter() {
		$workspace = $this->prophet->prophesize( '\Exporter\Workspace' );

		// Pass the mock workspace as a dependency
		$component = new Audio( '<audio><source src="http://someurl.com/audio-file.mp3?some_query=string"></audio>',
			$workspace->reveal(), $this->settings, $this->styles, $this->layouts );

		add_filter( 'apple_news_audio_json', function( $json ) {
			$json['URL'] = 'http://someurl.com/file.mp3';
			return $json;
		} );

		$json = $component->to_array();
		$this->assertEquals( 'audio', $json['role'] );
		$this->assertEquals( 'http://someurl.com/file.mp3', $json['URL'] );
	}

}


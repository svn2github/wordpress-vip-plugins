<?php

use \Apple_Exporter\Exporter_Content as Exporter_Content;
use \Apple_Exporter\Settings as Settings;
use \Apple_Exporter\Builders\Component_Layouts as Component_Layouts;
use \Apple_Exporter\Components\Component as Component;

class Component_Layouts_Test extends WP_UnitTestCase {

	protected $prophet;

	public function setup() {
		$this->prophet  = new \Prophecy\Prophet;
		$this->settings = new Settings();
		$this->content  = new Exporter_Content( 1, 'My Title', '<p>Hello, World!</p>' );
	}

	public function tearDown() {
		$this->prophet->checkPredictions();
	}

	public function testRegisterLayout() {
		$layouts = new Component_Layouts( $this->content, $this->settings );
		$layouts->register_layout( 'l1', 'val1' );
		$layouts->register_layout( 'l2', 'val2' );
		$result = $layouts->to_array();

		$this->assertEquals( 2, count( $result ) );
		$this->assertEquals( 'val1', $result[ 'l1' ] );
		$this->assertEquals( 'val2', $result[ 'l2' ] );
	}

	public function testLeftLayoutGetsAdded() {
		$layouts = new Component_Layouts( $this->content, $this->settings );

		$this->assertFalse( array_key_exists( 'anchor-layout-left', $layouts->to_array() ) );

		$component = $this->prophet->prophesize( '\Apple_Exporter\Components\Component' );
		$component->get_anchor_position()
			->willReturn( Component::ANCHOR_LEFT )
			->shouldBeCalled();
		$component->is_anchor_target()
			->willReturn( false )
			->shouldBeCalled();
		$component->set_json( 'layout', 'anchor-layout-left' )->shouldBeCalled();

		$layouts->set_anchor_layout_for( $component->reveal() );

		$this->assertTrue( array_key_exists( 'anchor-layout-left', $layouts->to_array() ) );
	}

}

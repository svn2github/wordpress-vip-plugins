<?php

use \Apple_Actions\Index\Delete as Delete;
use \Apple_Exporter\Settings as Settings;
use \Prophecy\Argument as Argument;

class Admin_Action_Index_Delete_Test extends WP_UnitTestCase {

	private $prophet;

	public function setup() {
		parent::setup();

		$this->prophet = new \Prophecy\Prophet;
		$this->settings = new Settings();
		$this->settings->set( 'api_key', 'foo' );
		$this->settings->set( 'api_secret', 'bar' );
		$this->settings->set( 'api_channel', 'baz' );
	}

	public function tearDown() {
		$this->prophet->checkPredictions();
	}

	public function testActionPerform() {
		$remote_id = uniqid();
		$api = $this->prophet->prophesize( '\Apple_Push_API\API' );
		$api->delete_article( $remote_id )
			->shouldBeCalled();

		// Create post with dummy remote id
		$post_id = $this->factory->post->create();
		update_post_meta( $post_id, 'apple_news_api_id', $remote_id );

		$action = new Delete( $this->settings, $post_id );
		$action->set_api( $api->reveal() );
		$action->perform();

		$this->assertNotEquals( null, get_post_meta( $post_id, 'apple_news_api_deleted', true ) );
		$this->assertEquals( null, get_post_meta( $post_id, 'apple_news_api_id', true ) );
	}

	public function testActionPerformWhenNotPushed() {
		// Expect an exception
		$this->setExpectedException( '\Apple_Actions\Action_Exception', 'This post has not been pushed to Apple News, cannot delete.' );

		$api = $this->prophet->prophesize( '\Push_API\API' );
		$post_id = $this->factory->post->create();

		$action = new Delete( $this->settings, $post_id );
		$action->set_api( $api->reveal() );
		$action->perform();
	}

}


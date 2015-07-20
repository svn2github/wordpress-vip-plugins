<?php
/**
 * Blimply test suite
 */

// Composer autoload
require_once BLIMPLY_ROOT . '/vendor/autoload.php';

use UrbanAirship\WpAirship as Airship;
use UrbanAirship\UALog;
use UrbanAirship\Push as P;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NullHandler;

class Blimply_UnitTestCase extends WP_UnitTestCase {
	public $blimply;

	/**
	 * Init
	 *
	 * @return [type] [description]
	 */
	function setup() {
		parent::setup();
		global $blimply;
		$this->blimply = $blimply;
		$this->blimply->action_admin_init();

		$this->set_options();
	}

	function teardown() {
	}

	// Check if settings get set up on activation
	function test_default_settings() {
		$this->assertNotEmpty( $this->blimply->options );
		$this->assertInternalType( 'array', $this->blimply->options );
	}

	function test_airship_init() {
		$this->assertInstanceOf( 'UrbanAirship\Airship', $this->blimply->airship );
	}

	// Check if errors are handled properly
	function test_error_handling() {

	}

	function test_api_keys() {
		foreach ( $this->blimply->options as $key => $value ) {
			// Only test required API creds
			if ( ! in_array( $key, array( 'blimply_app_key', 'blimply_app_secret' ) ) )
				continue;

			$this->assertNotEmpty( $value );
		}
	}

	function set_options() {
		$this->blimply->options = array(
			'blimply_name' => 'UnitTestApp',
			'blimply_app_key' => 'Vzc24B_bSG-v198jzn9yGQ',
			'blimply_app_secret' => 'Nx6tBhu1R4SGLhz3Z4Lrew',
			'blimply_character_limit' => '140',
			'blimply_quiet_time_from' => '',
			'blimply_quiet_time_to' => '',
			'blimply_enable_quiet_time' => '',
		);

		$this->blimply->airship = new Airship( $this->blimply->options['blimply_app_key'], $this->blimply->options['blimply_app_secret'] );
	}

	function test_successful_broadcast() {
		$response = $this->blimply->_send_broadcast_or_push( 'My valid test message! From ' . home_url( '/' ), 'broadcast' );

		$this->assertFalse( is_wp_error( $response ) );
	}

	function test_successful_push_to_tag() {
		$response = $this->blimply->_send_broadcast_or_push( 'My valid test message! From ' . home_url( '/' ), 'news' );

		$this->assertFalse( is_wp_error( $response ) );
	}

	function test_successful_push_with_extra_and_sound() {
		$response = $this->blimply->_send_broadcast_or_push( 'My valid test message with url and sound! From ' . home_url( '/' ), 'broadcast', home_url( '/' ), false );
		$this->assertFalse( is_wp_error( $response ) );
	}

	function test_successful_push_with_extra_no_sound() {
		$response = $this->blimply->_send_broadcast_or_push( 'My valid test message with url and no sound! From ' . home_url( '/' ), 'broadcast', home_url( '/' ), true );

		$this->assertFalse( is_wp_error( $response ) );
	}

	function test_catch_bad_request() {
		add_filter( 'blimply_payload_override', function( $payload ) { return array( 'malformed payload' ); } );
		$bad_payload_response = $this->blimply->_send_broadcast_or_push( 'My valid test message with url and no sound! From ' . home_url( '/' ), 'broadcast', home_url( '/' ), true );
		// $response = $this->
		$this->assertTrue( is_wp_error( $bad_payload_response ) );
	}

	function test_create_tag() {
		remove_action( 'create_term', array( $this->blimply, 'action_create_term' ) );
		$term = wp_insert_term( 'Test Blimply Term ' . current_time( 'timestamp' ), 'blimply_tags' );

		$response = $this->blimply->action_create_term( $term['term_id'], $term['term_taxonomy_id' ], 'blimply_tags' );
		$this->assertFalse( is_wp_error( $response ) );
	}

}

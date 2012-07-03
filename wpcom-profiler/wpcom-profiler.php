<?php

/**
 * Plugin Name: WordPress.com Profiler
 * Plugin URI:  http://vip.wordpress.com/
 * Description: Helps figure out what's being slow.
 * Author:      Alex Mills, Automattic
 * Author URI:  http://automattic.com/
 */

class WPcom_Profiler {

	private static $instance;

	public $max_priority = 20;
	public $counter = array();

	public $log = array();

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WPcom_Profiler;
			self::$instance->setup_actions();
		}
		return self::$instance;
	}

	public function setup_actions() {
		add_action( 'shutdown', array( $this, 'send_log' ) );

		$to_profile = array(
			'save_post',
			'publish_post',
		);

		for ( $priority = 0; $priority <= $this->max_priority; $priority++ ) {
			foreach ( $to_profile as $action ) {
				add_action( $action, array( $this, 'log' ), $priority );
			}
		}
	}

	public function log() {
		list( $current_filter, $priority ) = $this->get_identifier();

		$this->log[ $current_filter ][ $priority ] = microtime( true );
	}

	public function get_identifier() {
		$current_filter = current_filter();

		if ( ! isset( $this->counter[$current_filter] ) ) {
			$this->counter[$current_filter] = 0;
		} else {

			// If the counter is already at max_priority,
			// then the action is being called for a second time.
			// This is a problem if it's called a third time...
			if ( $this->counter[$current_filter] >= $this->max_priority )
				$current_filter = $current_filter . '2';

			$this->counter[$current_filter]++;
		}

		return array( $current_filter, $this->counter[$current_filter] );
	}

	public function send_log() {
		if ( empty( $this->log ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) )
			return;

		$message = '';

		foreach ( $this->log as $action => $priorities ) {
			$start = reset( $priorities );
			$stop = end( $priorities );
			$duration = $stop - $start;

			$message .= "{$action} took {$duration} seconds to run.\n";

			foreach ( $priorities as $priority => $timestamp ) {
				$message .= "\t{$priority} at +" . ( $timestamp - $start ) . "\n";
			}
		}

		wp_mail( 'alexm@automattic.com', 'Action debugging for ' . home_url(), $message );
	}
}

WPcom_Profiler::instance();
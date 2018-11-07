<?php
/**
 * Optimizely X: AJAX_Config class
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

namespace Optimizely_X;

/**
 * Defines AJAX endpoints that communicate with
 * the API via the Optimizely X Config page
 *
 * @since 1.0.0
 */
class AJAX_Config extends AJAX {

	use Singleton;

	/**
	 * An AJAX endpoint for optimizely_x_get_projects.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function get_projects() {

		// Get the response from the API and check for errors.
		$response = $this->api->get( '/projects', array(), true );
		$this->maybe_send_error_response( $response );

		// Ensure there are results to loop over.
		if ( empty( $response['json'] ) || ! is_array( $response['json'] ) ) {
			wp_send_json_error( __(
				'Failed to get a list of projects.',
				'optimizely-x'
			) );
		}

		// Loop over results from response and add each to the list.
		$projects = array();
		foreach ( $response['json'] as $project ) {
			if ( empty( $project['is_classic'] )
				&& ! empty( $project['status'] )
				&& 'active' === $project['status']
				&& ! empty( $project['name'] )
				&& ! empty( $project['id'] )
			) {

				// Sanitize name and ID.
				$name = sanitize_text_field( $project['name'] );
				$id = absint( $project['id'] );

				// Make sure we still have a name and ID before adding.
				if ( ! empty( $name ) && ! empty( $id ) ) {
					$projects[ $name ] = $id;
				}
			}
		}

		// Send the AJAX response.
		wp_send_json_success( $projects );
	}

	/**
	 * Registers action and filter hooks and initializes the API object.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function setup() {
		parent::setup();

		// Register action hooks.
		add_action(
			'wp_ajax_optimizely_x_get_projects',
			array( $this, 'get_projects' )
		);
	}

}

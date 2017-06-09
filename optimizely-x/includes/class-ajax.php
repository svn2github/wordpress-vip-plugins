<?php
/**
 * Optimizely X: AJAX class
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

namespace Optimizely_X;

/**
 * Defines AJAX endpoints that communicate with the API.
 *
 * @since 1.0.0
 */
class AJAX {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var AJAX
	 */
	private static $instance;

	/**
	 * An instance of the Optimizely API object, used to communicate with the API.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var API
	 */
	private $api;

	/**
	 * Gets the singleton instance.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return AJAX
	 */
	public static function instance() {

		// Initialize the instance, if necessary.
		if ( ! isset( self::$instance ) ) {
			self::$instance = new AJAX;
			self::$instance->setup();
		}

		return self::$instance;
	}

	/**
	 * An AJAX endpoint for optimizely_x_change_status.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function change_status() {

		// Validate nonce.
		if ( ! check_ajax_referer( 'optimizely-metabox', 'nonce', false ) ) {
			wp_send_json_error( __(
				'Failed to validate the nonce',
				'optimizely-x'
			) );
		}

		// Check for error condition.
		if ( empty( $_POST['entity_id'] ) || empty( $_POST['status'] ) ) {
			wp_send_json_error( __(
				'Missing entity_id or status value.',
				'optimizely-x'
			) );
		}

		// Sanitize postdata before proceeding.
		$post_id = absint( $_POST['entity_id'] );
		$status = sanitize_text_field( $_POST['status'] );

		// Ensure we have an experiment ID before proceeding.
		$experiment_id = get_post_meta( $post_id, 'optimizely_experiment_id', true );
		if ( empty( $experiment_id ) ) {
			wp_send_json_error( __( 'Missing experiment ID.', 'optimizely-x' ) );
		}

		// Build API request URL.
		$action = ( 'paused' === $status || 'not_started' === $status ) ? 'start' : 'pause';
		$operation = '/experiments/' . $experiment_id . '?action=' . $action;

		// Process the request and check for errors.
		$response = $this->api->patch( $operation );
		$this->maybe_send_error_response( $response );

		// Ensure we got a status in the response.
		if ( empty( $response['json']['status'] ) ) {
			wp_send_json_error( __(
				'No status included in the API response.',
				'optimizely-x'
			) );
		}

		// Update the status in postmeta.
		update_post_meta(
			$post_id,
			'optimizely_experiment_status',
			sanitize_text_field( $response['json']['status'] )
		);

		// Return the status in the AJAX response.
		wp_send_json_success( array(
			'experiment_status' => sanitize_text_field( $response['json']['status'] ),
		) );
	}

	/**
	 * An AJAX endpoint for optimizely_x_create_experiment.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function create_experiment() {

		// Validate nonce.
		if ( ! check_ajax_referer( 'optimizely-metabox', 'nonce', false ) ) {
			wp_send_json_error( __(
				'Failed to validate the nonce',
				'optimizely-x'
			) );
		}

		// Check for error conditions.
		if ( empty( $_POST['entity_id'] ) || empty( $_POST['variations'] ) ) {
			wp_send_json_error( __(
				'Missing entity_id or variations.',
				'optimizely-x'
			) );
		}

		// Ensure we have a project ID before proceeding.
		$project_id = absint( get_option( 'optimizely_project_id' ) );
		if ( empty( $project_id ) ) {
			wp_send_json_error( __( 'Missing project ID.', 'optimizely-x' ) );
		}

		// Extract variations from the values sent from the metabox.
		$variations = json_decode( wp_unslash( $_POST['variations'] ), true );
		if ( empty( $variations ) || ! is_array( $variations ) ) {
			wp_send_json_error( __(
				'Missing or malformed variations.',
				'optimizely-x'
			) );
		}

		// Sanitize variations before proceeding.
		$variations = array_map( 'sanitize_text_field', $variations );
		$variations = array_filter( $variations );
		if ( empty( $variations ) ) {
			wp_send_json_error( __(
				'Variations failed sanitization.',
				'optimizely-x'
			) );
		}

		// Try to get a post from the entity ID.
		$post = get_post( absint( $_POST['entity_id'] ) );
		if ( empty( $post ) ) {
			wp_send_json_error( __(
				'Failed to look up the post by ID.',
				'optimizely-x'
			) );
		}

		// Try to build a targeting page for this post.
		$targeting_id = $this->build_targeting_page( $project_id, $post );
		if ( empty( $targeting_id ) ) {
			wp_send_json_error( __(
				'An error occurred during the creation of a targeting page.',
				'optimizely-x'
			) );
		}

		// Try to build an event page for this post.
		$event_id = $this->build_event_page( $project_id, $post );
		if ( empty( $event_id ) ) {
			wp_send_json_error( __(
				'An error occurred during the creation of an event page.',
				'optimizely-x'
			) );
		}

		// Try to create an experiment for this post.
		$experiment_id = $this->build_experiment(
			$project_id,
			$post,
			$variations,
			$targeting_id,
			$event_id
		);
		if ( empty( $experiment_id ) ) {
			wp_send_json_error( __(
				'An error occurred during the creation of an event page.',
				'optimizely-x'
			) );
		}

		// Get the editor link for the experiment.
		$editor_link = sprintf(
			'https://app.optimizely.com/v2/projects/%d/experiments/%d',
			absint( $project_id ),
			absint( $experiment_id )
		);

		// Store the editor link in postmeta.
		add_post_meta(
			$post->ID,
			'optimizely_editor_link',
			esc_url_raw( $editor_link )
		);

		// Store the experiment ID in postmeta.
		add_post_meta(
			$post->ID,
			'optimizely_experiment_id',
			absint( $experiment_id )
		);

		// Store the experiment status in postmeta.
		add_post_meta(
			$post->ID,
			'optimizely_experiment_status',
			'not_started'
		);

		// Store the number of variations in postmeta.
		add_post_meta(
			$post->ID,
			'optimizely_variations_num',
			count( $variations )
		);

		// Loop over variations and store each in postmeta.
		$total_variations = count( $variations );
		for ( $i = 0; $i < $total_variations; $i ++ ) {
			add_post_meta(
				$post->ID,
				sprintf( 'optimizely_variations_%d', absint( $i ) ),
				sanitize_text_field( $variations[ $i ] )
			);
		}

		// Send the response.
		wp_send_json_success(
			array(
				'editor_link' => esc_url_raw( $editor_link ),
				'experiment_id' => absint( $experiment_id ),
			)
		);
	}

	/**
	 * An AJAX endpoint for optimizely_x_get_projects.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function get_projects() {

		// Get the response from the API and check for errors.
		$response = $this->api->get( '/projects' );
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
	 * Empty clone method, forcing the use of the instance() method.
	 *
	 * @see self::instance()
	 *
	 * @access private
	 */
	private function __clone() {
	}

	/**
	 * Empty constructor, forcing the use of the instance() method.
	 *
	 * @see self::instance()
	 *
	 * @access private
	 */
	private function __construct() {
	}

	/**
	 * Empty wakeup method, forcing the use of the instance() method.
	 *
	 * @see self::instance()
	 *
	 * @access private
	 */
	private function __wakeup() {
	}

	/**
	 * Registers action and filter hooks and initializes the API object.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function setup() {

		// Initialize the API.
		$this->api = new API;

		// Register action hooks.
		add_action(
			'wp_ajax_optimizely_x_change_status',
			array( $this, 'change_status' )
		);
		add_action(
			'wp_ajax_optimizely_x_create_experiment',
			array( $this, 'create_experiment' )
		);
		add_action(
			'wp_ajax_optimizely_x_get_projects',
			array( $this, 'get_projects' )
		);
	}

	/**
	 * Uses the API to build an event page for a given post.
	 *
	 * @param int $project_id The project ID to use when building the page.
	 * @param \WP_Post $post A post object to operate on.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return int|bool An event ID on success, or false on failure.
	 */
	private function build_event_page( $project_id, $post ) {

		// Ensure we have a project ID.
		$project_id = absint( $project_id );
		if ( empty( $project_id ) ) {
			return false;
		}

		// Build the data array for the event page.
		$event_page = array(
			'activation_type' => 'immediate',
			'conditions' => array(
				'and',
				array(
					'or',
					array(
						'match_type' => 'substring',
						'type' => 'url',
						'value' => get_permalink( $post ),
					),
				),
			),
			'edit_url' => get_permalink( $post ),
			'name' => sprintf(
				/* translators: 1: post ID, 2: post title */
				esc_html__(
					'WordPress [%1$d]: %2$s event page',
					'optimizely-x'
				),
				absint( $post->ID ),
				esc_html( $post->post_title )
			),
			'page_type' => 'url_set',
			'project_id' => absint( $project_id ),
		);

		// Collapse conditions (API request must be formatted this way).
		$event_page['conditions'] = wp_json_encode( $event_page['conditions'] );

		// Get the API response and check for errors.
		$response = $this->api->post( '/pages', $event_page );
		$this->maybe_send_error_response( $response );

		// Ensure we got an ID.
		if ( empty( $response['json']['id'] ) ) {
			return false;
		}

		return absint( $response['json']['id'] );
	}

	/**
	 * Uses the API to create an experiment for a given post.
	 *
	 * @param int $project_id The project ID to use when building the page.
	 * @param \WP_Post $post A post object to operate on.
	 * @param array $variations The variations to test.
	 * @param int $targeting_id The ID of the targeting page for this post.
	 * @param int $event_id The ID of the event page for this post.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return int|bool An event ID on success, or false on failure.
	 */
	private function build_experiment(
		$project_id,
		$post,
		$variations,
		$targeting_id,
		$event_id
	) {

		// Check preconditions.
		$project_id = absint( $project_id );
		$targeting_id = absint( $targeting_id );
		$event_id = absint( $event_id );
		if ( empty( $project_id )
			|| empty( $variations )
			|| ! is_array( $variations )
			|| empty( $targeting_id )
			|| empty( $event_id )
		) {
			return false;
		}

		// Build the data array for the experiment.
		$experiment = array(
			'metrics' => array(
				array(
					'aggregator' => 'unique',
					'event_id' => absint( $event_id ),
					'scope' => 'visitor',
				),
			),
			'name' => sprintf(
				/* translators: 1: post ID, 2: post title */
				esc_html__(
					'WordPress [%1$d]: %2$s',
					'optimizely-x'
				),
				absint( $post->ID ),
				esc_html( $post->post_title )
			),
			'project_id' => absint( $project_id ),
			'status' => 'paused',
			'variations' => $this->generate_variations(
				$variations,
				$targeting_id,
				$post
			),
		);

		// Get the API response and check for errors.
		$response = $this->api->post( '/experiments', $experiment );
		$this->maybe_send_error_response( $response );

		// Ensure we got an ID.
		if ( empty( $response['json']['id'] ) ) {
			return false;
		}

		return absint( $response['json']['id'] );
	}

	/**
	 * Uses the API to build a targeting page for a given post.
	 *
	 * @param int $project_id The project ID to use when building the page.
	 * @param \WP_Post $post A post object to operate on.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return int|bool A targeting ID on success, or false on failure.
	 */
	private function build_targeting_page( $project_id, $post ) {

		// Ensure we have a project ID.
		$project_id = absint( $project_id );
		if ( empty( $project_id ) ) {
			return false;
		}

		// Start building a data array for the targeting page.
		$targeting_page = array(
			'activation_type' => 'immediate',
			'conditions' => array(
				'and',
				array(
					'or',
					array(
						'match_type' => 'substring',
						'type' => 'url',
						'value' => get_site_url(),
					),
				),
			),
			'edit_url' => get_permalink( $post ),
			'name' => sprintf(
				/* translators: 1: post ID, 2: post title. */
				esc_html__(
					'WordPress [%1$d]: %2$s targeting page',
					'optimizely-x'
				),
				absint( $post->ID ),
				esc_html( $post->post_title )
			),
			'page_type' => 'url_set',
			'project_id' => absint( $project_id ),
		);

		// Override activation type if specified activation mode is conditional.
		if ( 'conditional' === get_option( 'optimizely_activation_mode' ) ) {
			$targeting_page['activation_type'] = 'polling';

			// Try to set conditional activation code.
			$activation_code = get_option( 'optimizely_conditional_activation_code' );
			if ( ! empty( $activation_code ) ) {
				$targeting_page['activation_code'] = str_replace(
					'$POST_ID',
					$post->ID,
					$activation_code
				);
			}
		}

		// Override URL targeting if options are set.
		$url_targeting = get_option( 'optimizely_url_targeting' );
		$url_targeting_type = get_option( 'optimizely_url_targeting_type' );
		if ( ! empty( $url_targeting ) && ! empty( $url_targeting_type ) ) {
			$targeting_page['conditions'][1][1]['match_type'] = sanitize_text_field(
				$url_targeting_type
			);
			$targeting_page['conditions'][1][1]['value'] = sanitize_text_field(
				$url_targeting
			);
		}

		// Collapse conditions (API request must be formatted this way).
		$targeting_page['conditions'] = wp_json_encode( $targeting_page['conditions'] );

		// Get the API response and check for errors.
		$response = $this->api->post( '/pages', $targeting_page );
		$this->maybe_send_error_response( $response );

		// Ensure we got an ID.
		if ( empty( $response['json']['id'] ) ) {
			return false;
		}

		return absint( $response['json']['id'] );
	}

	/**
	 * Builds the data for a variation to be included with the JSON request.
	 *
	 * @param string $title The title of the variation.
	 * @param int $weight The variation weight.
	 * @param int $targeting_id The ID of the targeting page.
	 * @param \WP_Post $post The post object for which to generate variations.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return array An array of data about the variation.
	 */
	private function generate_variation( $title, $weight, $targeting_id, $post ) {

		// Load the variation template and swap out dynamic values.
		$template = get_option( 'optimizely_variation_template' );
		$template = str_replace( '$POST_ID', $post->ID, $template );
		$template = str_replace( '$NEW_TITLE', $title, $template );
		$template = str_replace( '$OLD_TITLE', $post->post_title, $template );

		return array(
			'actions' => array(
				array(
					'changes' => array(
						array(
							'async' => false,
							'dependencies' => array(),
							'type' => 'custom_code',
							'value' => sanitize_text_field( $template ),
						),
					),
					'page_id' => absint( $targeting_id ),
				),
			),
			'name' => sanitize_text_field( $title ),
			'weight' => absint( $weight ),
		);
	}

	/**
	 * Builds the data for variations to be included with the JSON request.
	 *
	 * @param array $variations The config data for the variations to build.
	 * @param int $targeting_id The ID of the targeting page.
	 * @param \WP_Post $post The post object for which to generate variations.
	 *
	 * @since 1.0.0
	 * @access private
	 * @return array An array of data about the variations.
	 */
	private function generate_variations( $variations, $targeting_id, $post ) {

		// Verify the preconditions.
		if ( empty( $variations )
			|| ! is_array( $variations )
			|| empty( $targeting_id )
			|| empty( $post->ID )
		) {
			return array();
		}

		// Calculate configuration values.
		$num_variations = count( $variations ) + 1;
		$variation_weight = floor( 10000 / $num_variations );
		$leftover_weight = 10000 - ( $variation_weight * $num_variations );

		// Build a list of variation weights that are guaranteed to add to 10,000.
		$variation_weights = array_merge(
			array_fill( 0, count( $variations ), $variation_weight ),
			array( $variation_weight + $leftover_weight )
		);

		// Start building the variation data, starting with the original variation.
		$variation_data = array(
			array(
				'actions' => array(
					array(
						'changes' => array(),
						'page_id' => absint( $targeting_id ),
					),
				),
				'name' => esc_html__( 'Original', 'optimizely-x' ),
				'weight' => absint( $variation_weight ),
			),
		);

		// Loop over provided variations and add each.
		$total_variations = count( $variations );
		for ( $i = 0; $i < $total_variations; $i ++ ) {
			$variation_data[] = $this->generate_variation(
				$variations[ $i ],
				$variation_weights[ $i + 1 ],
				$targeting_id,
				$post
			);
		}

		return $variation_data;
	}

	/**
	 * Handles sending error responses, given an API response array.
	 *
	 * @param array $response The response array, returned from the API class.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function maybe_send_error_response( $response ) {

		// If the operation was successful, don't send an error.
		if ( ! empty( $response['status'] ) && 'SUCCESS' === $response['status'] ) {
			return;
		}

		// Flatten the error list, if necessary.
		if ( is_array( $response['error'] ) ) {
			$response['error'] = implode( "\n", $response['error'] );
		}

		// Send the error data in the response.
		wp_send_json_error( sanitize_text_field( $response['error'] ) );
	}
}

<?php
/**
 * Optimizely X: Results class
 *
 * @package Optimizely_X
 * @since 1.2.0
 */

namespace Optimizely_X;

/**
 * Controls the experiment results logic for the results page
 *
 * @since 1.2.0
 */
class Results {

	use Singleton;

	/**
	 * An instance of the Optimizely API object, used to communicate with the API.
	 *
	 * @since 1.2.0
	 * @access private
	 * @var API
	 */
	private $api;

	/**
	 * Return all active experiments that are from WordPress.
	 *
	 * Experiments are considered NOT active when their status is:
	 *   archived
	 *   draft
	 *   not_started
	 *
	 * @return array
	 */
	public function get_active_experiments() {
		$running_experiments = [];

		if ( Admin::is_initialized() ) {
			$project_id = absint( get_option( 'optimizely_x_project_id' ) );
			$experiments = $this->api->get( '/experiments', [
				'project_id' => $project_id,
			] );

			if ( ! empty( $experiments['json'] ) && is_array( $experiments['json'] ) ) {
				foreach ( $experiments['json'] as $experiment ) {
					if (
						false !== strpos( $experiment['name'], 'WordPress' ) // only load WordPress experiments
						&& ! empty( $experiment['status'] )
						&& 'archived' !== $experiment['status']
						&& 'draft' !== $experiment['status']
						&& 'not_started' !== $experiment['status']
					) {
						$running_experiments[] = $experiment;
					}
				}
			}

		}

		return $running_experiments;
	}

	/**
	 * Get the experiment details by an experiment id
	 *
	 * @param int $experiment_id
	 * @return array
	 */
	public function get_experiment( $experiment_id ) {
		$experiment = [];

		if ( Admin::is_initialized() ) {
			$response = $this->api->get( '/experiments/' . $experiment_id );

			if ( ! empty( $response['json'] ) && is_array( $response['json'] ) ) {
				$experiment = $response['json'];
			}

		}

		return $experiment;
	}

	/**
	 * Get the experiment results by an experiment id
	 *
	 * @param int $experiment_id
	 * @return array
	 */
	public function get_experiment_results( $experiment_id ) {
		$results = [];

		if ( Admin::is_initialized() ) {
			$response = $this->api->get( '/experiments/' . $experiment_id . '/results' );

			if ( ! empty( $response['json']['metrics'] ) && is_array( $response['json']['metrics'] ) ) {
				$results = $response['json']['metrics'];
			}

		}

		return $results;
	}

	/**
	 * Get the experiment results and format them specifically
	 * for use by WP_List_Table->prepare_items()
	 *
	 * @param int $experiment_id The Optimizely experiment id
	 * @param int $event_id Optional Event ID (defaults to the first event)
	 * @return array
	 */
	public function get_results_table_data( $experiment_id, $event_id = 0 ) {
		$results_table_data = [];

		if ( Admin::is_initialized() ) {
			$metrics = $this->get_experiment_results( $experiment_id );
			if ( ! empty( $metrics ) ) {
				foreach ( $metrics as $index => $metric ) {
					// If we're looking for a specific metric, continue until we find it
					// otherwise just get the first metric in the collection
					if ( $event_id && $event_id !== $metric['event_id'] ) {
						continue;
					} else {
						foreach ( $metric['results'] as $variation_id => $variation ) {

							// Format some of the numbers based on the locale, but
							// only if the variation isn't the baseline (because the
							// baseline has no relevant statistics).
							if ( ! $variation['is_baseline'] ) {
								$improvement = number_format_i18n( $variation['lift']['value'] * 100, 2 ) . '%';
								$confidence = number_format_i18n( $variation['lift']['significance'] );
								$visitors_remaining = $variation['lift']['visitors_remaining'] > 100000 ? '> ' . number_format_i18n( 100000 ) : number_format_i18n( $variation['lift']['visitors_remaining'] );
								$launch = '<a class="button launch" href="#"></a>';
							} else {
								$improvement = __( 'Baseline', 'optimizely-x' );
								$confidence = '';
								$visitors_remaining = '';
								$launch = '';
							}

							$results_table_data[] = [
								'variation' => esc_html( $variation['name'] ),
								'visitors' => absint( $variation['samples'] ),
								'converstions' => absint( $variation['value'] ),
								'rate' => ! empty( $variation['rate'] ) ? esc_html( number_format_i18n( $variation['rate'] * 100, 2 ) . '%' ) : '',
								'improvement' => esc_html( $improvement ),
								'confidence' => esc_html( $confidence ),
								'visitors_remaining' => esc_html( $visitors_remaining ),
								'launch' => $launch,
							];

						}
						break;
					}
				}
			}
		}

		return $results_table_data;
	}

	/**
	 * Registers action and filter hooks.
	 *
	 * @since 1.2.0
	 * @access private
	 */
	private function setup() {
		// Initialize the API.
		$this->api = new API;
	}

}

<?php
/**
 * Optimizely X: Table class
 *
 * @package Optimizely_X
 * @since 1.2.0
 */

namespace Optimizely_X;

// Required in order to extend the core WP_List_Table class. This
// class is usually already loaded at this point.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * A class to render table views using the core WP_List_Table class
 *
 * @since 1.2.0
 */
class Results_Table extends \WP_List_Table {

	/**
	 * The Optimizely experiment id for this specific table
	 *
	 * @var int
	 */
	private $experiment_id;

	/**
	 * Default constructor
	 *
	 * @param int $experiment_id The Optimizely experiment id
	 * @return void
	 */
	public function __construct( $experiment_id ) {
		parent::__construct();
		$this->experiment_id = $experiment_id;
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'variation' => __( 'Variation', 'optimizely-x' ),
			'visitors' => __( 'Visitors', 'optimizely-x' ),
			'converstions' => __( 'Conversions', 'optimizely-x' ),
			'rate' => __( 'Rate', 'optimizely-x' ),
			'improvement' => __( 'Improvement', 'optimizely-x' ),
			'confidence' => __( 'Confidence', 'optimizely-x' ),
			'visitors_remaining' => __( '~Visitors Remaining', 'optimizely-x' ),
			'launch' => __( 'Launch', 'optimizely-x' ),
		];
		return $columns;
	}

	/**
	 * Prepares the list of experiment variations to display.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$sanitized_fields = $this->get_sanitized_fields();
		$selected_metric = ! empty( $sanitized_fields['experiment_metric'] ) ? $sanitized_fields['experiment_metric'] : false;
		$this->set_column_headers();
		$this->items = Results::instance()->get_results_table_data( $this->experiment_id, $selected_metric );
	}

	/**
	 * Overriden method from WP_List_Table that renders
	 * out the Metric dropdown and Optimizely actions.
	 *
	 * @param string $which The 'top' or 'bottom' tablenav section
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) :
			$experiment_results = Results::instance()->get_experiment_results( $this->experiment_id );
			if ( ! empty( $experiment_results ) ) :
				?>

				<div class="alignleft actions experiment-actions">
					<h3><?php echo esc_html__( 'Metric', 'optimizely-x' ); ?>:</h3>
					<label
						for="experiment-metric-<?php echo esc_attr( $this->experiment_id ); ?>"
						class="screen-reader-text"><?php echo esc_html__( 'Select Metric', 'optimizely-x' ); ?>
					</label>
					<select
						id="experiment-metric-<?php echo esc_attr( $this->experiment_id ); ?>"
						name="experiment_metric[<?php echo esc_attr( $this->experiment_id ); ?>]"
					>

					<?php
					// Experiments may have multiple metrics (but require at least 1).
					foreach ( $experiment_results as $metric ) :
						$sanitized_fields = $this->get_sanitized_fields();
						$selected_metric = ! empty( $sanitized_fields['experiment_metric'] ) ? $sanitized_fields['experiment_metric'] : false;
						?>

						<option <?php selected( $selected_metric, $metric['event_id'] ); ?> value="<?php echo esc_attr( $metric['event_id'] ); ?>"><?php echo esc_html( $metric['name'] ); ?></option>

						<?php
					endforeach;
					?>

					</select>
					<input id="doaction" class="button action" value="<?php esc_html_e( 'Apply', 'optimizely-x' ); ?>" type="submit">
				</div>

				<?php
			endif;
			?>

			<?php
			$experiment = Results::instance()->get_experiment( $this->experiment_id );
			if ( ! empty( $experiment ) ) :

				// Build a link to the editor for this specific experiment
				$editor_link = sprintf(
					'https://app.optimizely.com/v2/projects/%d/experiments/%d',
					absint( $experiment['project_id'] ),
					absint( $this->experiment_id )
				);

				// Build a link to the metric page for this specific experiment
				$metrics_link = sprintf(
					'https://app.optimizely.com/v2/projects/%d/results/%d/experiments/%d',
					absint( $experiment['project_id'] ),
					absint( $experiment['campaign_id'] ),
					absint( $this->experiment_id )
				);

				?>
				<div class="alignright actions optimizely-actions">
					<a class="button start status" <?php echo 'running' === $experiment['status'] ? 'style="display:none;"' : ''; ?> href="#" data-status="start"><?php echo esc_html__( 'Start', 'optimizely-x' ); ?></a>
					<a class="button pause status" <?php echo 'paused' === $experiment['status'] ? 'style="display:none;"' : ''; ?> href="#" data-status="pause"><?php echo esc_html__( 'Pause', 'optimizely-x' ); ?></a>
					<a class="button edit" target="_blank" href="<?php echo esc_url( $editor_link ); ?>"><?php echo esc_html__( 'Edit', 'optimizely-x' ); ?></a>
					<a class="button metrics" target="_blank" href="<?php echo esc_url( $metrics_link ); ?>"><?php echo esc_html__( 'Metrics', 'optimizely-x' ); ?></a>
					<a class="button archive" href="#" data-status="archive"><?php echo esc_html__( 'Archive', 'optimizely-x' ); ?></a>
				</div>

				<?php
			endif;
		endif;
	}

	/**
	 * Default text to display when no results are found.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'There are no active Variations to display.', 'optimizely-x' );
	}

	/**
	 * Overriden method from WP_List_Table to define column mappings
	 *
	 * @param string $item Column array
	 * @param string $column_name Column name
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		if ( ! empty( $item[ $column_name ] ) ) {
			return $item[ $column_name ];
		} else {
			return '';
		}
	}

	/**
	 * Generates and display row actions links for the list table.
	 *
	 * Overriden method from WP_List_Table. We do not want to display
	 * any row actions for the columns.
	 *
	 * @param object $item        The item being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @return string The row actions HTML, or an empty string if the current column is the primary column.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		return '';
	}

	/**
	 * Helper method to bootstrap, verify, and sanitize the URL
	 * parameters for this request.
	 *
	 * As features continue to develop, register your URL parameters
	 * here in the $sanitized_fields array.
	 *
	 * @return array
	 */
	private function get_sanitized_fields() {

		$sanitized_fields = [];

		if ( ! current_user_can( Filters::admin_capability() ) ) {
			return $sanitized_fields;
		}

		// Verify the nonce before atttempting to use any globals
		if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'bulk-' . $this->_args['plural'] ) ) { // Input var okay.
			// Each experiment may have more than 1 metric.
			// For experiments with multiple metrics, we need to check whether
			// the user selected a specific metric.
			if ( isset( $_GET['experiment_metric'][ $this->experiment_id ] ) ) { // Input var okay.
				$sanitized_fields['experiment_metric'] = absint( wp_unslash( $_GET['experiment_metric'][ $this->experiment_id ] ) ); // Input var okay.
			}
		}

		return $sanitized_fields;
	}

	/**
	 * Set the column headers for the table
	 *
	 * This property requires a 4-value array :
	 *  The first value is an array containing column slugs and titles (see the get_columns() method).
	 *  The second value is an array containing the values of fields to be hidden.
	 *  The third value is an array of columns that should allow sorting (see the get_sortable_columns() method).
	 *  The fourth value is a string defining which column is deemed to be the primary one, displaying the row's actions (edit, view, etc). The value should match that of one of your column slugs in the first value.
	 * @return void
	 */
	private function set_column_headers() {
		$this->_column_headers = [
			$this->get_columns(),
			[], // Currently no hidden fields
			[], // Currently no sortable fields
			'variation',
		];
	}
}

<?php
/**
 * Optimizely X admin partials: Config page template
 *
 * @package Optimizely_X
 * @since 1.2.0
 */

// Set some default pagination values
$opt_paged           = 1;
$opt_per_page        = 3;
$total_pages         = 0;
$current_experiments = [];

// If the user is paging, verify the nonce and override the default page number
if ( isset( $_GET['paged'] ) ) { // Input var okay.
	if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'bulk-optimizely_page_optimizely-results' ) ) { // Input var okay.
		$opt_paged = absint( wp_unslash( $_GET['paged'] ) ); // Input var okay.
	}
}

$active_experiments = Optimizely_X\Results::instance()->get_active_experiments();

// Based on the pagination settings, initialize a subset
// of the active experiments for the current page.
if ( ! empty( $active_experiments ) ) {
	$total_pages = ceil( count( $active_experiments ) / $opt_per_page );
	$start_index = ( $opt_paged * $opt_per_page ) - $opt_per_page;
	$current_experiments = array_slice( $active_experiments, $start_index, $opt_per_page );
}

?>

<div class="wrap optimizely-admin">
	<h1><?php esc_html_e( 'Optimizely Results', 'optimizely-x' ); ?></h1>
	<div class="optimizely-results">
		<form method="GET">
		<input type="hidden" name="page" value="optimizely-results" />
		<input type="hidden" name="paged" value="<?php echo esc_attr( $opt_paged ); ?>" />
		<?php
		if ( ! empty( $current_experiments ) ) {
			foreach ( $current_experiments as $experiment ) :
				?>

				<div class="experiment-wrapper <?php echo esc_attr( $experiment['id'] ); ?>" data-experiment-id="<?php echo esc_attr( $experiment['id'] ); ?>">
					<span class="experiment-header">
						<h2><?php echo esc_html__( 'Experiment: ', 'optimizely-x' ) . esc_html( $experiment['name'] ); ?></h2>
					</span>

					<?php
					$table = new Optimizely_X\Results_Table( $experiment['id'] );
					$table->prepare_items();
					$table->display();
					?>

				</div>

				<?php
			endforeach;
		} else {
			?>

			<div class="experiment-no-results">
				<span class="experiment-header">
					<h2><?php echo esc_html__( 'There are currently no active experiments.', 'optimizely-x' ); ?></h2>
				</span>
			</div>

			<?php
		}

		// If we have more than 1 page, create a pagination wrapper
		if ( 1 < $total_pages ) :
		?>
		<div class="navigation">
			<?php
			// If there are previous pages, render a Previous button
			if ( 1 < $opt_paged ) {
				$url = sprintf(
					'%sadmin.php?page=optimizely-results&_wpnonce=%s&paged=%d',
					get_admin_url(),
					wp_create_nonce( 'bulk-optimizely_page_optimizely-results' ),
					$opt_paged - 1
				);
				echo '<a href="' . esc_url( $url ) . '" class="button">' . esc_html__( 'Previous', 'optimizely-x' ) . '</a>';
			}
			// If there are next pages, render a Next button
			if ( $total_pages > $opt_paged ) {
				$url = sprintf(
					'%sadmin.php?page=optimizely-results&_wpnonce=%s&paged=%d',
					get_admin_url(),
					wp_create_nonce( 'bulk-optimizely_page_optimizely-results' ),
					$opt_paged + 1
				);
				echo '<a href="' . esc_url( $url ) . '" class="button">' . esc_html__( 'Next', 'optimizely-x' ) . '</a>';
			}
			?>
		</div>
		<?php endif; ?>
		</form>
	</div>
</div>

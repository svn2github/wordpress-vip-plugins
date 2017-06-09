<?php
/**
 * Optimizely X public partials: Head JS
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

// If we don't have a valid project ID, bail.
$project_id = get_option( 'optimizely_project_id' );
if ( empty( $project_id ) ) {
	return;
}
?>

<script src="https://cdn.optimizely.com/js/<?php echo absint( $project_id ); ?>.js"></script>

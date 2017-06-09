<?php
/**
 * Optimizely X admin field partials: optimizely_project_name field
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

?>

<div>
	<input class="optimizely-field-hidden"
		id="optimizely-project-name"
		name="optimizely_project_name"
		type="hidden"
		value="<?php echo esc_attr( get_option( 'optimizely_project_name' ) ); ?>"
	/>
</div>

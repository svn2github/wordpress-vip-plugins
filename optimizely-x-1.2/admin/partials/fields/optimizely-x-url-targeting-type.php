<?php
/**
 * Optimizely X admin field partials: optimizely_x_url_targeting_type field
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

// Negotiate URL targeting type value.
$url_targeting_type = get_option( 'optimizely_x_url_targeting_type' );
if ( empty( $url_targeting_type ) ) {
	$url_targeting_type = 'substring';
}

// Define URL targeting types.
$url_types = array(
	'simple' => __( 'simple', 'optimizely-x' ),
	'exact' => __( 'exact', 'optimizely-x' ),
	'substring' => __( 'substring', 'optimizely-x' ),
	'regex' => __( 'regex', 'optimizely-x' ),
);

?>

<div>
	<select class="optimizely-requires-authentication"
		id="optimizely-x-url-targeting-type"
		name="optimizely_x_url_targeting_type"
	>
		<?php foreach ( $url_types as $type => $label ) : ?>
			<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $type, $url_targeting_type ); ?>>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</div>

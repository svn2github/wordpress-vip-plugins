<?php


/* ------------------------------------------------------------------------ *
 * CONCIERGE SETUP
 * ------------------------------------------------------------------------ */
function sailthru_intialize_concierge_options() {

	if ( false === get_option( 'sailthru_concierge_options' ) ) {
		add_option( 'sailthru_concierge_options' );
	} // end if

	add_settings_section(
		'sailthru_concierge_settings_section',   // ID used to identify this section and with which to register options
		__( 'Sailthru Concierge Options', 'sailthru-for-wordpress' ), // Title to be displayed on the administration page
		'sailthru_concierge_options_callback',   // Callback used to render the description of the section
		'sailthru_concierge_options'     // Page on which to add this section of options
	);

	add_settings_field(
		'sailthru_concierge_is_on',
		__( 'Enable Concierge', 'sailthru-for-wordpress' ),
		'sailthru_toggle_feature_callback',
		'sailthru_concierge_options',
		'sailthru_concierge_settings_section',
		array(
			'sailthru_concierge_options',
			'sailthru_concierge_is_on',
			'1',
			'sailthru_concierge_is_on',
			'Yes',
		)
	);

	/*
		 * If Conceirge is not on, let's not show all the options
		 */
	$concierge = get_option( 'sailthru_concierge_options' );

	if ( isset( $concierge['sailthru_concierge_is_on'] ) && $concierge['sailthru_concierge_is_on'] ) {

		add_settings_field(
			'sailthru_concierge_from',
			__( 'Recommended box to display from', 'sailthru-for-wordpress' ),
			'sailthru_concierge_from_callback',
			'sailthru_concierge_options',
			'sailthru_concierge_settings_section',
			array(
				'sailthru_concierge_options',
				'sailthru_concierge_from',
				'top',
				'sailthru_concierge_from',
			)
		);

		add_settings_field(
			'sailthru_concierge_delay',
			__( 'Delay Concierge for ', 'sailthru-for-wordpress' ),
			'sailthru_concierge_delay_callback',
			'sailthru_concierge_options',
			'sailthru_concierge_settings_section',
			array(
				'sailthru_concierge_options',
				'sailthru_concierge_delay',
				'1',
				'sailthru_concierge_delay',
			)
		);

		add_settings_field(
			'sailthru_concierge_threshold',
			__( 'A lower threshold value means the box will display within shorter page', 'sailthru-for-wordpress' ),
			'sailthru_html_text_input_callback',
			'sailthru_concierge_options',
			'sailthru_concierge_settings_section',
			array(
				'sailthru_concierge_options',
				'sailthru_concierge_threshold',
				'',
				'sailthru_concierge_threshold',
			)
		);

		add_settings_field(
			'sailthru_concierge_offsetBottom',
			__( 'Higher the value, recommendation box will offset the window bottom', 'sailthru-for-wordpress' ),
			'sailthru_html_text_input_callback',
			'sailthru_concierge_options',
			'sailthru_concierge_settings_section',
			array(
				'sailthru_concierge_options',
				'sailthru_concierge_offsetBottom',
				'20',
				'sailthru_concierge_offsetBottom',
			)
		);

		add_settings_field(
			'sailthru_concierge_cssPath',
			__( 'Custom CSS path to decorate recommendation box', 'sailthru-for-wordpress' ),
			'sailthru_html_text_input_callback',
			'sailthru_concierge_options',
			'sailthru_concierge_settings_section',
			array(
				'sailthru_concierge_options',
				'sailthru_concierge_cssPath',
				'https://ak.sail-horizon.com/horizon/recommendation.css',
				'sailthru_concierge_cssPath',
			)
		);

		add_settings_field(
			'sailthru_concierge_filter',
			__( 'To only return content tagged a certain way, pass comma separated tags', 'sailthru-for-wordpress' ),
			'sailthru_html_text_input_callback',
			'sailthru_concierge_options',
			'sailthru_concierge_settings_section',
			array(
				'sailthru_concierge_options',
				'sailthru_concierge_filter',
				'',
				'sailthru_concierge_filter',
			)
		);

	} // end if concierge is on

	register_setting(
		'sailthru_concierge_options',     // Settings group. Must match the setting section.
		'sailthru_concierge_options',     // Option name to sanitize and save
		'sailthru_sanitize_text_input'     // Sanitize callback
	);

} // end sailthru_intialize_concierge_options
add_action( 'admin_init', 'sailthru_intialize_concierge_options' );




/* ------------------------------------------------------------------------ *
 * Section Callbacks
 * ------------------------------------------------------------------------ */
/**
 * Provides a simple description for each setup page respectively.
 */
function sailthru_concierge_options_callback() {
	echo '<p>Concierge is a Horizon-powered on-site recommendation tool, allowing a small "slider" to appear in a user\'s browser window at the end of an article. The slider will suggest another story based on a user\'s interest. </p><p>For full documentation of Concierge features visit our <a href="http://docs.sailthru.com/documentation/products/concierge">documentation</a>.</p>';
} // end sailthru_concierge_options_callback



/* ------------------------------------------------------------------------ *
 * Field Callbacks - Helpers to render form elements specific to this section
 * ------------------------------------------------------------------------ */
/**
 * Creates a Top/Bottom dropdown whose values are top/bottom
 */
function sailthru_concierge_from_callback( $args ) {

	$scout       = get_option( 'sailthru_concierge_options' );
	$saved_value = isset( $scout['sailthru_concierge_from'] ) ? $scout['sailthru_concierge_from'] : '';

	echo  '<select name="sailthru_concierge_options[sailthru_concierge_from]">';
	echo  '<option value="top" ' . esc_attr( selected( $saved_value, 'top', false ) ) . '>Top</option>';
	echo  '<option value="bottom" ' . esc_attr( selected( $saved_value, 'bottom', false ) ) . '>Bottom</option>';
	echo '</select>';

}

/**
 * Creates a dropdown for the concierge delay
 */
function sailthru_concierge_delay_callback( $args ) {

	$scout       = get_option( 'sailthru_concierge_options' );
	$saved_value = isset( $scout['sailthru_concierge_delay'] ) ? $scout['sailthru_concierge_delay'] : '';

	echo '<select name="sailthru_concierge_options[sailthru_concierge_delay]">';
	echo '<option value="100" ' . esc_attr( selected( $saved_value, '100', false ) ) . '>1 sec</option>';
	echo '<option value="200" ' . esc_attr( selected( $saved_value, '200', false ) ) . '>2 secs</option>';
	echo '<option value="300" ' . esc_attr( selected( $saved_value, '300', false ) ) . '>3 secs</option>';
	echo '<option value="400" ' . esc_attr( selected( $saved_value, '400', false ) ) . '>4 secs</option>';
	echo '<option value="500" ' . esc_attr( selected( $saved_value, '500', false ) ) . '>5 secs</option>';
	echo '<option value="600" ' . esc_attr( selected( $saved_value, '600', false ) ) . '>6 secs</option>';
	echo '<option value="700" ' . esc_attr( selected( $saved_value, '700', false ) ) . '>7 secs</option>';
	echo '<option value="800" ' . esc_attr( selected( $saved_value, '800', false ) ) . '>8 secs</option>';
	echo '<option value="900" ' . esc_attr( selected( $saved_value, '900', false ) ) . '>9 secs</option>';
	echo '<option value="1000" ' . esc_attr( selected( $saved_value, '1000', false ) ) . '>10 secs</option>';
	echo '</select>';
}

/* ------------------------------------------------------------------------ *
 * Setting Callbacks
 * ------------------------------------------------------------------------ */

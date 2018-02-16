<?php


/* ------------------------------------------------------------------------ *
 * SCOUT SETUP
 * ------------------------------------------------------------------------ */
function sailthru_intialize_scout_options() {

	if ( false === get_option( 'sailthru_scout_options' ) ) {
		add_option( 'sailthru_scout_options' );
	} // end if

	add_settings_section(
		'sailthru_scout_settings_section',
		__( 'Scout Options', 'sailthru-for-wordpress' ),
		'sailthru_scout_options_callback',
		'sailthru_scout_options'
	);

	add_settings_field(
		'sailthru_scout_is_on',
		__( 'Scout Enabled', 'sailthru-for-wordpress' ),
		'sailthru_toggle_feature_callback',
		'sailthru_scout_options',
		'sailthru_scout_settings_section',
		array(
			'sailthru_scout_options',
			'sailthru_scout_is_on',
			'1',
			'sailthru_scout_is_on',
			'Yes',
		)
	);

	/*
		 * If Scout is not on, let's not show all the options
		 */
	$scout = get_option( 'sailthru_scout_options' );

	if ( isset( $scout['sailthru_scout_is_on'] ) && $scout['sailthru_scout_is_on'] ) {

		add_settings_field(
			'sailthru_scout_numVisible',
			__( 'The number of items to render at a time', 'sailthru-for-wordpress' ),
			'sailthru_scout_items_callback',
			'sailthru_scout_options',
			'sailthru_scout_settings_section',
			array(
				'sailthru_scout_options',
				'sailthru_scout_numVisible',
				'10',
				'sailthru_scout_numVisible',
			)
		);

		add_settings_field(
			'sailthru_scout_includeConsumed',
			__( 'Include content that has already been consumed by the user?', 'sailthru-for-wordpress' ),
			'sailthru_scout_includeConsumed_callback',
			'sailthru_scout_options',
			'sailthru_scout_settings_section',
			array(
				'sailthru_scout_options',
				'sailthru_scout_includeConsumed',
				'false',
				'sailthru_scout_includeConsumed',
			)
		);

		add_settings_field(
			'sailthru_scout_renderItem',
			__( 'Override rendering function? (Please do not include &lt;p&gt;&lt;/p&gt; tags -- <a href="http://docs.sailthru.com/documentation/products/scout" target="_blank">details here</a>.)', 'sailthru-for-wordpress' ),
			'sailthru_scout_renderItem_callback',
			'sailthru_scout_options',
			'sailthru_scout_settings_section',
			array(
				'sailthru_scout_options',
				'sailthru_scout_renderItem',
				'false',
				'sailthru_scout_renderItem',
			)
		);

	} // end if concierge is on

	register_setting(
		'sailthru_scout_options',
		'sailthru_scout_options',
		'sailthru_sanitize_text_input'
	);

} // end sailthru_intialize_concierge_options
add_action( 'admin_init', 'sailthru_intialize_scout_options' );




/* ------------------------------------------------------------------------ *
 * Section Callbacks
 * ------------------------------------------------------------------------ */

/**
 * Provides a simple description for each setup page respectively.
 */
function sailthru_scout_options_callback() {
	echo '<p>Scout is an on-site tool that displays relevant content to users when viewing a particular page.</p>';
} // end sailthru_scout_options_callback



/* ------------------------------------------------------------------------ *
 * Field Callbacks - Helpers to render form elements specific to this section
 * ------------------------------------------------------------------------ */


/**
 * Creates a dropdown for the number of scout options
 */
function sailthru_scout_items_callback( $args ) {

	$scout       = get_option( 'sailthru_scout_options' );
	$saved_value = isset( $scout['sailthru_scout_numVisible'] ) ? $scout['sailthru_scout_numVisible'] : 5;

	echo '<select name="sailthru_scout_options[sailthru_scout_numVisible]">';

	$i = 0;
	while ( $i <= 40 ) {
		echo  '<option value="' . esc_attr( $i ) . '" ' . esc_attr( selected( $saved_value, $i, false ) ) . '>' . esc_attr( $i ) . '</option>';
		$i++;
	}
	echo  '</select>';

}

/**
 * Creates a Yes/No drop down for Scout whose values are True/False
 */
function sailthru_scout_includeConsumed_callback( $args ) {

	$scout       = get_option( 'sailthru_scout_options' );
	$saved_value = isset( $scout['sailthru_scout_includeConsumed'] ) ? $scout['sailthru_scout_includeConsumed'] : '';

	echo  '<select name="sailthru_scout_options[sailthru_scout_includeConsumed]">';
	echo  '<option value="false" ' . esc_attr( selected( $saved_value, 'false', false ) ) . '>No</option>';
	echo  '<option value="true" ' . esc_attr( selected( $saved_value, 'true', false ) ) . '>Yes</option>';
	echo  '</select>';

}


/**
 * Just a textbox, but not a general function because we don't (oddly) strip
 * HTML tags.
 */
function sailthru_scout_renderItem_callback( $args ) {

	$scout       = get_option( 'sailthru_scout_options' );
	$saved_value = isset( $scout['sailthru_scout_renderItem'] ) ? $scout['sailthru_scout_renderItem'] : '';

	echo '<textarea name="sailthru_scout_options[sailthru_scout_renderItem]">' . esc_textarea( $saved_value ) . '</textarea>';

}


/* ------------------------------------------------------------------------ *
 * Setting Callbacks
 * ------------------------------------------------------------------------ */

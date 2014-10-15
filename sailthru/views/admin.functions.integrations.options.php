<?php

/* ------------------------------------------------------------------------ *
 * INTEGRATIONS SETUP
 * ------------------------------------------------------------------------ */
function sailthru_initialize_integrations_options(){
	
	if ( false == get_option( 'sailthru_integrations_options' ) ) {
		add_option( 'sailthru_integrations_options' );
	} // end if
	add_settings_section(
			'sailthru_integrations_settings_section',			// ID used to identify this section and with which to register options
			__( 'Integrations', 'sailthru-for-wordpress' ),				// Title to be displayed on the administration page
			'sailthru_integrations_callback',			// Callback used to render the description of the section
			'sailthru_integrations_options'			// Page on which to add this section of options
		);

	$integrations_options = get_option('sailthru_integrations_options');



		add_settings_field(
				'sailthru_twitter_enabled',	// ID used to identify the field throughout the theme
				__( 'Twitter Lead Cards', 'sailthru-for-wordpress' ),		// The label to the left of the option interface element
				'sailthru_twitter_enabled_callback',
				'sailthru_integrations_options',
				'sailthru_integrations_settings_section',
				array(
					'sailthru_integrations_options',
					'sailthru_twitter_enabled',
					'',
					'sailthru_twitter_enabled',
					'Enable Twitter Lead Cards',
				)
			);

		if( isset( $integrations_options['sailthru_twitter_enabled']) && $integrations_options['sailthru_twitter_enabled']) {

			add_settings_field(
					'sailthru_twitter_url',	// ID used to identify the field throughout the theme
					__( 'Twitter Submit URL', 'sailthru-for-wordpress' ),		// The label to the left of the option interface element
					'sailthru_html_text_input_callback',
					'sailthru_integrations_options',
					'sailthru_integrations_settings_section',
					array(
						'sailthru_integrations_options',
						'sailthru_twitter_url',
						'/sailthru/twitter/',
						'sailthru_twitter_url',
						'The address you enter is automatically prefixed with ' . get_bloginfo('url')
					)
				);

			add_settings_field(
					'sailthru_twitter_salt',	// ID used to identify the field throughout the theme
					__( 'Twitter Shared Salt', 'sailthru-for-wordpress' ),		// The label to the left of the option interface element
					'sailthru_html_text_input_callback',
					'sailthru_integrations_options',
					'sailthru_integrations_settings_section',
					array(
						'sailthru_integrations_options',
						'sailthru_twitter_salt',
						'',
						'sailthru_twitter_salt',
						'Twitter salt is provided by ads platform. Copy and paste it here.'
					)
				);		

		}		

		

		add_settings_field(
				'sailthru_gigya_enabled',	// ID used to identify the field throughout the theme
				__( 'Gigya Integration', 'sailthru-for-wordpress' ),		// The label to the left of the option interface element
				'sailthru_gigya_enabled_callback',
				'sailthru_integrations_options',
				'sailthru_integrations_settings_section',
				array(
					'sailthru_integrations_options',
					'sailthru_gigya_enabled',
					'',
					'sailthru_gigya_enabled',
					'Enable Gigya Social Login',
				)
			);

		if( isset( $integrations_options['sailthru_gigya_enabled']) && $integrations_options['sailthru_gigya_enabled']) {

			add_settings_field(
					'sailthru_gigya_url',	// ID used to identify the field throughout the theme
					__( 'Gigya Callback URL', 'sailthru-for-wordpress' ),		// The label to the left of the option interface element
					'sailthru_html_text_input_callback',
					'sailthru_integrations_options',
					'sailthru_integrations_settings_section',
					array(
						'sailthru_integrations_options',
						'sailthru_gigya_url',
						'/sailthru/gigya/',
						'sailthru_gigya_url',
						'The address you enter is automatically prefixed with ' . get_bloginfo('url')
					)
				);		

			add_settings_field(
					'sailthru_gigya_key',	// ID used to identify the field throughout the theme
					__( 'Gigya Secret Key', 'sailthru-for-wordpress' ),		// The label to the left of the option interface element
					'sailthru_html_text_input_callback',
					'sailthru_integrations_options',
					'sailthru_integrations_settings_section',
					array(
						'sailthru_integrations_options',
						'sailthru_gigya_key',
						'',
						'sailthru_gigya_key',
						'Your secret key is provided by Gigya. Copy and paste it here.'
					)
				);

		}


			
		
	register_setting(
		'sailthru_integrations_options',
		'sailthru_integrations_options',
		'sailthru_integrations_handler'
	);
	
}
add_action( 'admin_init', 'sailthru_initialize_integrations_options' );








/* ------------------------------------------------------------------------ *
 * Section Callbacks
 * ------------------------------------------------------------------------ */

/**
 * Provides a simple description for each setup page respectively.
 */

function sailthru_integrations_callback() {
	echo '<p>Options for Sailthru integrations</p>';
}


/* ------------------------------------------------------------------------ *
 * Field Callbacks - Helpers to render form elements specific to this section
 * ------------------------------------------------------------------------ */

function sailthru_twitter_enabled_callback() {

    $options = get_option( 'sailthru_integrations_options' );
    $load_type = isset($options['sailthru_twitter_enabled']) ? $options['sailthru_twitter_enabled'] : '';
   	echo '<input type="checkbox" id="sailthru_twitter_enabled" name="sailthru_integrations_options[sailthru_twitter_enabled]" value="1"' . checked( 1, esc_attr($load_type), false ) . '/>';
  	echo 'Enable Twitter Lead Cards';

}

function sailthru_gigya_enabled_callback() {
	$options = get_option( 'sailthru_integrations_options' );
	$load_type = isset($options['sailthru_gigya_enabled']) ? $options['sailthru_gigya_enabled'] : '';
   	echo '<input type="checkbox" id="sailthru_gigya_enabled" name="sailthru_integrations_options[sailthru_gigya_enabled]" value="1"' . checked( 1, esc_attr($load_type), false ) . '/>';
  	echo 'Enable Gigya Social Login';
}


/* ------------------------------------------------------------------------ *
 * Setting Callbacks
 * ------------------------------------------------------------------------ */
 

function sailthru_integrations_handler( $input ) {



		$output = array();

		/* 
		 * Twitter 
		 *
		 * Pretty urls must be enabled in order for this to work properly.
		 */
		if( isset( $input['sailthru_twitter_enabled'] ) ) {
			$output['sailthru_twitter_enabled'] = '1';
		} else { 
			$output['sailthru_twitter_enabled'] = false;
		}


		if ( $output['sailthru_twitter_enabled'] ) {


			if( isset( $input['sailthru_twitter_url'] ) ) {
				$output['sailthru_twitter_url'] = filter_var( $input['sailthru_twitter_url'], FILTER_SANITIZE_STRING );
			} else {
				$output['sailthru_twitter_url'] = false;
			}

			if( isset( $input['sailthru_twitter_salt']) ) {
				$output['sailthru_twitter_salt'] = filter_var( $input['sailthru_twitter_salt'], FILTER_SANITIZE_STRING );
			} else {
				$output['sailthru_twitter_salt'] = false;
			}
			
		}



		/*
		 * Gigya
		 */
		if( isset( $input['sailthru_gigya_enabled'] ) ) {
	 		$output['sailthru_gigya_enabled'] = 1;
	 	} else {
	 		$output['sailthru_gigya_enabled'] = false;
	 	}

		
		if( $output['sailthru_gigya_enabled'] ){ 

			if ( isset( $input['sailthru_gigya_key'] ) ) {
				$output['sailthru_gigya_key'] = filter_var( $input['sailthru_gigya_key'], FILTER_SANITIZE_STRING );
			} else {
				$output['sailthru_gigya_key'] = false;
			}

			if( isset( $input['sailthru_gigya_url']) ) {
				$output['sailthru_gigya_url'] = filter_var( $input['sailthru_gigya_url'], FILTER_SANITIZE_STRING );
			} else {
				$output['sailthru_gigya_url'] = false;
			}

		}
	 
	return $output;

}

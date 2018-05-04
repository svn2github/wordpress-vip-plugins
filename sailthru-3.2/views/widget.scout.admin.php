	<div id="icon-sailthru" class="icon32"></div>

	<?php

		$sailthru = get_option( 'sailthru_setup_options' );

		// check to see if Sailthru is setup first
	if ( ! is_array( $sailthru ) ) {

		echo '<p>Please return to the <a href="' . esc_url( menu_page_url( 'sailthru_configuration_menu', false ) ) . '">Sailthru Settings screen</a> and set up your API key and secret before setting up this widget.</p>';
		return;

	}

	if ( isset( $sailthru['sailthru_js_type'] ) && ( 'personalize_js' === $sailthru['sailthru_js_type'] || 'personalize_js_custom' === $sailthru['sailthru_js_type'] ) ) {
		// Use Personalize JS
		$api_key    = $sailthru['sailthru_api_key'];
		$api_secret = $sailthru['sailthru_api_secret'];
		$client     = new WP_Sailthru_Client( $api_key, $api_secret );

		$spm_enabled = sailthru_spm_ready();

		// Get Sections
		try {

			if ( $spm_enabled ) {

				$sections = $client->apiGet( 'section' );
			}
		} catch ( Exception $e ) {
			write_log($e);
			$spm_err = true;
		}

		echo '<div id=" ' . esc_attr( $this->get_field_id( 'title' ) )  . '_div" style="display: block; margin:15px 0">';

		if ( sailthru_spm_ready() && ! isset( $spm_err) ) {

			// pass the active section_id from form function in class-sailthru-scout.php 
			sailthru_spm_admin_widget($sections, $active_section_id, esc_attr( $this->get_field_name( 'sailthru_spm_section' ) ) ); 

		} else {
			
			if ( isset ( $spm_err ) ) {
				echo '<p>Sections could not be retrieved. Please contact <a href="mailto:support@sailthru.com">support@sailthru.com</a> if Site Personalization Manager is enabled on your account. </p>';
			} else {
				echo '<p>Site Personalization Manager is not enabled for this account, please contact your Account Manager to find out more. </p>';
			}

		}

		echo '</div>';


	} else {

		/*
		* If Scout is not on, advise the user
		*/
		$scout = get_option( 'sailthru_scout_options' );

		if ( ! isset( $scout['sailthru_scout_is_on'] ) || ! $scout['sailthru_scout_is_on'] ) {

			echo '<p>Don\'t forget to <a href="' . esc_url( menu_page_url( 'scout_configuration_menu', false ) ) . '">enable Scout</a> before setting up this widget.</p>';
			return;

		}
	?>

		<div id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>_div" style="display: block;">
		<p>Use the Scout configuration page to choose your settings for this sidebar widget.</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
			<?php esc_html_e( 'Title:' ); ?>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</label>
		</p>
		</div>
		<?php
	}

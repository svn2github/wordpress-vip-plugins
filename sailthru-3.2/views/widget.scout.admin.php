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


		// Check the user has access to SPM.
		$settings = $client->apiGet( 'settings' );
		// set to be disabled by default for a safe fallback
		$spm_enabled = false;

		// Get the SPM settings
		if ( isset( $settings['features']['spm_enabled'] ) && $settings['features']['spm_enabled'] ) {
			$spm_enabled = $settings['features']['spm_enabled'];
		}

		// Get Sections
		try {

			if ( $spm_enabled ) {

				$sections = $client->apiGet( 'section' );

				// get sections
				if ( is_array( $sections ) ) {

					$section_dropdown  = '<select name="' . esc_attr( $this->get_field_name( 'sailthru_spm_section' ) ) . '">';
					$section_dropdown .= '<option value="">-- Select --</option>';

					foreach ( $sections as $list ) {
						foreach ( $list as $section ) {
							if ( $section['section_id'] === $section_id ) {
								$selected = ' selected';
							} else {
								$selected = '';
							}
							$section_dropdown .= '<option value="' . esc_attr( $section['section_id'] ) . '"' . esc_attr( $selected ) . '>' . esc_attr( $section['name'] ) . '</option>';
						}
					}
					$section_dropdown .= '</select>';
				}
			}
		} catch ( Exception $e ) {
			print 'We could not retrieve the SPM sections.';
		}

		?>

		<?php if ( sailthru_spm_ready() ) : ?>
			<input type="hidden" value="personalize_js" name="sailthru_widget_type" />
			<div id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>_div" style="display: block; margin:15px 0">
				<p>Choose the personalization section to display on your site by selecting the section from the drop down menu below. </p>
				<input type="hidden" value="personalize_js" name="sailthru_widget_type" />
				<?php echo $section_dropdown; ?>
				<p class="small">Manage Site this personalization block in <a href="https://my.sailthru.com/spm">Sailthru</a></p>

			</div>
			<?php else : ?>
			<p>SPM is not enabled for this account, please contact your Account Manager to find out more. </p>
			<?php endif; ?>

		<?php

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

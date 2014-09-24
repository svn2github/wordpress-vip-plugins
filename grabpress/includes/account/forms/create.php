<fieldset id="account" class="create">
	<legend>Create Account</legend>
	<form method="post" id="register">
		<input id="id_action" type="hidden" name="action" value="create-user" />
		<table>
				<tr>
					<td>Email *</td>
					<td><input id="id_email" type="text" name="email" maxlength="255" /></td>
					<td><img alt="Checkmark" src="<?php echo esc_url( Grabpress::get_green_icon_src( 'Ok' ) ); ?>" style="visibility:hidden" id="email_ok" /></td>
				</tr>
			<tr>
				<td>Create Password* (minimum 6 characters)</td>
				<td><input type="password" name="password" id="id_password" /></td>
				<td><img alt="Checkmark" src="<?php echo esc_url( Grabpress::get_green_icon_src( 'Ok' ) ); ?>" style="visibility:hidden" id="pass1_ok" /></td>
			</tr>
			<tr>
				<td>Re-enter Password*</td>
				<td><input type="password" name="password2" id="id_password2" /></td>
				<td><img alt="Checkmark" src="<?php echo esc_url( Grabpress::get_green_icon_src( 'Ok' ) ); ?>" style="visibility:hidden" id="pass2_ok" /></td>
			</tr>
			<tr>
				<td>First Name*</td>
				<td><input id="id_first_name" type="text" name="first_name" maxlength="255" /></td>
				<td><img alt="Checkmark" src="<?php echo esc_url( Grabpress::get_green_icon_src( 'Ok' ) ); ?>" style="visibility:hidden" id="first_ok" /></td>
			</tr>
			<tr>
				<td>Last Name*</td>
				<td><input id="id_last_name" type="text" name="last_name" maxlength="255" /></td>
				<td><img alt="Checkmark" src="<?php echo esc_url( Grabpress::get_green_icon_src( 'Ok' ) ); ?>" style="visibility:hidden" id="last_ok" /></td>
			</tr>
			<tr>
				<td>Company</td>
				<td><input id="company" type="text" name="company" maxlength="255" /></td>
				<td></td>
			</tr>
			<tr>
				<td>Content Category</td>
				<td>
					<select name="publisher_category_id" id="publisher_category_id">
						<?php
							// Build category array
							$category_arr = array(
								'2'  =>"Entertainment",
								'3'  =>"Fashion & Beauty",
								'4'  =>"Food & Beverage",
								'5'  =>"Gaming",
								'6'  =>"Health",
								'8'  =>"Lifestyle General",
								'9'  =>"Men's Lifestyle",
								'10' =>"Business & Finance",
								'11' =>"News",
								'15' =>"Sports",
								'16' =>"Technology",
								'17' =>"Woman's Lifestyle",
							);

							// Begin options HTML string with "All Categories" option
							$options_html = "<option value=\"1\">All Categories</option>\n";

							// Loop through each category
							foreach ( $category_arr as $key => $val ) {
								// Append category as option to options HTML
								$options_html .= '<option value="' . $key . '">' . $val . '</option>' . "\n";
							}

							// Output options HTML
							echo $options_html;
						?>
					</select>
				</td>
				<td></td>
			</tr>
			<tr>
				<td>Address 1*</td>
				<td><input id="id_address1" type="text" name="address1" maxlength="255" /></td>
				<td><img alt="Checkmark" src="<?php echo esc_url( Grabpress::get_green_icon_src( 'Ok' ) ); ?>" style="visibility:hidden" id="address_ok" /></td>
			</tr>
			<tr>
				<td>Address 2</td>
				<td><input id="id_address2" type="text" name="address2" maxlength="255" /></td>
				<td></td>
			</tr>
			<tr>
				<td>City*</td>
				<td><input id="id_city" type="text" name="city" maxlength="255" /></td>
				<td><img alt="Checkmark" src="<?php echo esc_url( Grabpress::get_green_icon_src( 'Ok' ) ); ?>" style="visibility:hidden" id="city_ok" /></td>
			</tr>
			<tr>
				<td>State*</td>
				<td>
					<select name="state" id="id_state">
						<?php
							// Build state array
							$states = array(
								'AL'=>"Alabama",
								'AK'=>"Alaska",
								'AZ'=>"Arizona",
								'AR'=>"Arkansas",
								'CA'=>"California",
								'CO'=>"Colorado",
								'CT'=>"Connecticut",
								'DE'=>"Delaware",
								'DC'=>"District Of Columbia",
								'FL'=>"Florida",
								'GA'=>"Georgia",
								'HI'=>"Hawaii",
								'ID'=>"Idaho",
								'IL'=>"Illinois",
								'IN'=>"Indiana",
								'IA'=>"Iowa",
								'KS'=>"Kansas",
								'KY'=>"Kentucky",
								'LA'=>"Louisiana",
								'ME'=>"Maine",
								'MD'=>"Maryland",
								'MA'=>"Massachusetts",
								'MI'=>"Michigan",
								'MN'=>"Minnesota",
								'MS'=>"Mississippi",
								'MO'=>"Missouri",
								'MT'=>"Montana",
								'NE'=>"Nebraska",
								'NV'=>"Nevada",
								'NH'=>"New Hampshire",
								'NJ'=>"New Jersey",
								'NM'=>"New Mexico",
								'NY'=>"New York",
								'NC'=>"North Carolina",
								'ND'=>"North Dakota",
								'OH'=>"Ohio",
								'OK'=>"Oklahoma",
								'OR'=>"Oregon",
								'PA'=>"Pennsylvania",
								'RI'=>"Rhode Island",
								'SC'=>"South Carolina",
								'SD'=>"South Dakota",
								'TN'=>"Tennessee",
								'TX'=>"Texas",
								'UT'=>"Utah",
								'VT'=>"Vermont",
								'VA'=>"Virginia",
								'WA'=>"Washington",
								'WV'=>"West Virginia",
								'WI'=>"Wisconsin",
								'WY'=>"Wyoming",
							);

							// Begin options HTML with "Select Your State"
							$options_html = "<option value=\"\">Select Your State</option>\n";

							// Loop through each state
							foreach( $states as $k => $v ) {
								// Append state as option to options HTML
								$options_html .= '<option value="' . $k . '">' . $v . '</option>' . "\n";
							}

							// Output options HTML
							echo $options_html;
						?>
					</select>
				</td>
				<td><img alt="Checkmark" src="<?php echo esc_url( Grabpress::get_green_icon_src( 'Ok' ) ); ?>" style="visibility:hidden" id="state_ok" /></td>
			</tr>
			<tr>
				<td>Zip*</td>
				<td><input id="id_zip" type="text" name="zip" maxlength="255" /></td>
				<td><img alt="Checkmark" src="<?php echo esc_url( Grabpress::get_green_icon_src( 'Ok' ) ); ?>" style="visibility:hidden" id="zip_ok" /></td>
			</tr>
			<tr>
				<td>Phone Number</td>
				<td><input id="id_phone_number" type="text" name="phone_number" maxlength="255" /></td>
				<td></td>
			</tr>
			<tr>
				<td>Paypal ID</td>
				<td><input id="id_paypal_id" type="text" name="paypal_id" maxlength="255" /></td>
				<td></td>
			</tr>
			<tr>
				<td>Website Domain*
					<select id= "id-protocol">
						<option>http://</option>
						<option>https://</option>
					</select>
				</td>
				<td><input id="id-site" type="text" name="site" maxlength="255" /></td>
				<td><img alt="Checkmark" src="<?php echo esc_url( Grabpress::get_green_icon_src( 'Ok' ) ); ?>" style="visibility:hidden" id="url_ok" /></td>
			</tr>
			<tr>
				<td id="id-tos">I agree to Grab Networks' <a href="http://www.grab-media.com/terms/" target="_blank">Terms of Service*</a></td>
				<td><input type="checkbox" name="tos" id="id-agree" value="agree" /></td>
				<td></td>
			</tr>
		</table>
		<input type="hidden" name="url" id="id_url" />
	</form>
	<div id="buttons">
		<span id="required" class="account-help">Note: All fields marked with an asterisk* are required.</span>
		<a id="clear-form" href ="#">clear form</a>
		<input type="button" class="button-primary" disabled="disabled" id="submit-button" value="<?php esc_attr( _e( $text = 'switch' == $request[ 'action' ] ? 'Change' : 'Link' . ' Account' ) ); ?>"/>
		<input type="button" class="button-secondary" id="cancel-button" value="<?php esc_attr( _e( 'Cancel' ) ); ?>"/>
	</div>
</fieldset>
<script>
	// TODO: Remove inline JavaScript

	// Create jQuery $ scope
	(function($){

		// DOM ready
		$(function() {

			// Define vars
			var submitBtn = $( '#submit-button' ),
					register = $( '#register' ),
					formChanged = false,
					formAndAllInputs = $( ':input', 'form' ),
					cancelBtn = $( '#cancel-button' ),
					allFormInputs = $( 'input' ),
					allDropdowns = $( 'select' ),
					clearForm = $( '#clear-form' ),
					message = $( '#message p' )
			;

			// On submit button click
			submitBtn.on( 'click', function() {
				// Submit register
				register.submit();
			});

			// On form or input change
			formAndAllInputs.on( 'change', function() {
				// Update form changed status to true
				formChanged = true
			});

			// On cancel button click
			cancelBtn.on( 'click', function() {
				// If form changed
				if ( formChanged ) {
					// Define vars
					var confirm = window.confirm( 'Are you sure you want to cancel creation?\n\nAds played due to this plug-in will continue to not earn you any money, and your changes to this form will be lost.' ),
							register = $( '#register' ),
							firstRegister = register[0],
							action = $( '#id_action' )
					;
					// If confirm
					if ( confirm ) {
						// Reset form
						firstRegister.reset();
						action.val( 'default' );
						register.submit();
					} else {
						// Do nothing
					}
				} else { // Form unchanged
					// Redirect to account page
					window.location = 'admin.php?page=gp-account';
				}
			});

			// Validate forms as change and interactions happen
			allFormInputs
				.on( 'keyup', doValidation )
				.on( 'click', doValidation )
			;
			allDropdowns.on( 'change', doValidation );
			clearForm.on( 'click', function() {
				var register = $( '#register' ),
					firstRegister = register[0];
				firstRegister.reset();
				doValidation();
			});

			// On any change to form or its inputs
			formAndAllInputs.on( 'change', function() {
				// Enable confirmation (onbeforeunload)
				setConfirmUnload( true );
			});

			// On register submit
			register.on( 'submit', function() {
				// Disable confirmation (onbeforeunload)
				setConfirmUnload( false );
			});

			/**
			 * Executes validate and toggles availability of submit button
			 */
			function doValidation() {
				// Define vars
				var submitBtn = $( '#submit-button' );

				// If all form fields are valid
				if ( validate() ) {
					// Enable submit button
					submitBtn
						.removeAttr( 'disabled' )
						.on( 'click' )
					;
				} else { // Not all form fields valid
					// Disable submit button
					submitBtn
						.attr( 'disabled', 'disabled' )
						.on( 'click' )
					;
				}
			}

			/**
			 * Checks whether an element's value is empty
			 * @param  {String} id ID of element to be checked
			 * @return {Boolean}    Value is not empty
			 */
			function notEmpty( id ) {
				// Define vars
				var el = $( '#' + id );

				// Strip white space from value
				el.val( el.val().replace( /^\s*/, '' ) );

				// If value is not empty
				if ( el.val() ) {
					return true;
				}

				return false;
			}

			/**
			 * Presents a message to the user to confirm that the window should unload
			 * its resources even though changes have not been saved.
			 * @param {Boolean} shouldDisplay Should display unload message
			 */
			function setConfirmUnload( shouldDisplay ) {
				window.onbeforeunload = ( shouldDisplay ) ? unloadMessage : null;
			}

			/**
			 * Returns unload confirm message
			 * @return {String} Unload confirm message
			 */
			function unloadMessage() {
				return 'You have entered new data on this page. If you navigate away ' +
				'from this page without first saving your data, the changes will be ' +
				'lost.';
			}

			/**
			 * Validates all form fields
			 * @return {Boolean} All form fields are valid
			 */
			function validate() {
				// Define vars
				var valid,
						pass = $( '#id_password' ).val(),
						phone = $( '#id_phone_number' ).val(),
						url = $( '#id-protocol' ).val() + $( '#id-site' ).val(),
						emailValid = $( '#id_email' ).val().match( /[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}/i ),
						emailOk = $( '#email_ok' ),
						passValid = ( pass.length > 5 ),
						passMatch = passValid && $( '#id_password2' ).val() === pass,
						passOneOk = $( '#pass1_ok' ),
						passTwoOk = $( '#pass2_ok' ),
						firstValid = notEmpty( 'id_first_name' ),
						lastValid = notEmpty( 'id_last_name' ),
						firstOk = $( '#first_ok' ),
						lastOk = $( '#last_ok' ),
						addressValid = notEmpty( 'id_address1' ),
						addressOk = $( '#address_ok' ),
						cityValid = notEmpty( 'id_city' ),
						cityOk = $( '#city_ok' ),
						stateValid = notEmpty( 'id_state' ),
						stateOk = $( '#state_ok' ),
						zipValid = notEmpty( 'id_zip' ),
						zipOk = $( '#zip_ok' ),
						agreeValid = $( '#id-agree' ).attr( 'checked' ),
						urlValid = url.match( /([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,6}/gi ),
						urlOk = $( '#url_ok' ),
						message = $( '#message p' ),
						allFormInputs = $( ':input' )
				;

				// Update visibility of error messages based on validation
				emailOk.css( 'visibility', emailValid ? 'visible': 'hidden' );
				passOneOk.css( 'visibility', passValid ? 'visible': 'hidden' );
				passTwoOk.css( 'visibility', passMatch ? 'visible': 'hidden' );
				firstOk.css( 'visibility', firstValid ? 'visible': 'hidden' );
				lastOk.css( 'visibility', lastValid ? 'visible': 'hidden' );
				addressOk.css( 'visibility', addressValid ? 'visible': 'hidden' );
				cityOk.css( 'visibility', cityValid ? 'visible': 'hidden' );
				stateOk.css( 'visibility', stateValid ? 'visible': 'hidden' );
				zipOk.css( 'visibility', zipValid ? 'visible': 'hidden' );
				urlOk.css( 'visibility', urlValid ? 'visible': 'hidden' );

				// Check if all fields are valid
				if ( emailValid && passValid && passMatch && firstValid && lastValid && addressValid && cityValid && stateValid && zipValid && agreeValid && urlValid ) {
					valid = true; // All fields valid
				} else {
					valid = false; // Not all fields are valid
				}

				// Check if error message exists
				if ( 'There was an error connecting to the API! Please try again later!' === message.text() ) {
					valid = false; // Not valid if error exists

					// Disable all form inputs
					allFormInputs.attr( 'disabled', 'disabled' );
				}

				// Return valid status
				return valid
			}

			// If error message exists
			if ( 'There was an error connecting to the API! Please try again later!' === message.text() ) {
				// Disable all form inputes
				allFormInputs.attr( 'disabled', 'disabled' );
			}
		});

	})(jQuery); // End of jQuery $ scope
</script>
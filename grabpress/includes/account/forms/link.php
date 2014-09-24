<fieldset id="account">
	<legend><?php echo $request['action'] == 'switch' ? esc_html( 'Another' ) : esc_html( 'Existing' ); ?> Account</legend>
	<form id="link-existing" method="post">
		<input id="action-link-user" type="hidden" name="action" value="link-user" />
		<table>
			<tr>
				<td>Email address<input name="email" id="id_email" type="text" value="<?php echo $email = ( isset($request['email'] ) && $request['email'] != null ) ? esc_attr( $request['email'] ) : ''; ?>" /></td>
			</tr>
			<tr>
				<td>Password<input name="password" id="password" type="password" /></td>
			</tr>
			<tr>
				<td class = "account-help"><a href="http://www.grab-media.com/publisherAdmin/forgotpw" target="_blank">Forgot password?</a>
					<input type="button" class="button-primary" disabled="disabled" id="submit_button" value="<?php esc_attr( _e( ( 'switch' == $request[ 'action' ] ? 'Change' : 'Link' ) . ' Account') ); ?>" /><input type="button" class="button-secondary" id="cancel_button" value="<?php esc_attr( _e( 'Cancel' ) ); ?>" /></td>
			</tr>
		</table>
	</form>
</fieldset>
<script>
	// TODO: Remove inline JavaScript

	// Create jQuery $ scope
	(function($){

		// Define vars
		var submitBtn = $( '#submit_button' ),
				formChanged = false,
				formAndAllInputs = $( ':input', 'form' ),
				cancelBtn = $( '#cancel_button' ),
				allFormInputs = $( 'input' ),
				allDropdowns = $( 'select' ),
				message = $( '#message p' ),
				linkExisting = $( '#link-existing' ),
				register = $( '#register' )
		;

		// On submit button click
		submitBtn.on( 'click', function() {
			// Submit register
			register.submit();
		});

		// On form or input change
		formAndAllInputs.on( 'change', function() {
			// Update form changed status to true
			formChanged = true;
		});

		// Validate forms as change and interactions happen
		allFormInputs
			.on( 'keyup', doValidation )
			.on( 'click', doValidation )
		;
		allDropdowns.on( 'change', doValidation );

		// On any change to form or its inputs
		formAndAllInputs.on( 'change', function() {
			// Enable confirmation (onbeforeunload)
			setConfirmUnload( true );

			// Update form changed status
			formChanged = true;
		});

		// On register submit
		linkExisting.on( 'submit', function() {
			// Disable confirmation (onbeforeunload)
			setConfirmUnload( false );
		});

		// On cancel button click
		cancelBtn.on( 'click', function() {
			// Define vars
			var confirm,
					firstRegister = register[0],
					action = $( '#id_action' )
			;

			// If form changed
			if ( formChanged ) {

				// Build dynamic confirmation message
				confirm = window.confirm( 'Are you sure you want to cancel linking?\n\n' +
				<?php
					$user = Grabpress_API::get_user();

					// Check email linked to user account
					$linked = isset( $user->email );

					// If email linked
					if( $linked ) {
				?>
					'Money earned with this installation will continue to be credited to the account associated with the email address <?php echo $user->email; ?>.'
				<?php } else { ?>
					'Ads played due to this plug-in installation will not earn you any money.'
				<?php } ?>);

				// Reset form
				firstRegister.reset();
				action.val( 'default' );
				linkExisting.submit();
			}
		});

		/**
		 * Executes validate and toggles availability of submit button
		 */
		function doValidation() {
			// Define vars
			var submitBtn = $( '#submit_button' ),
					linkExisting = $( '#link-existing' )
			;

			// If all form fields are valid
			if ( validate() ) {
				// Enable submit button
				submitBtn
					.removeAttr( 'disabled' )
					.on( 'click', function() {
						// Submit link existing
						linkExisting.submit();
					})
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
		function validate(){
			// Define vars
			var valid,
					emailValid = $( '#id_email' ).val().match( /[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}/i ),
					passValid = ( 0 < $( '#password' ).val().length ),
					allFormInputs = $( ':input' )
			;

			// Check if all fields are valid
			if ( emailValid && passValid ) {
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
			return valid;
		}

		// DOM ready
		$(function() {
			// If error message exists
			if ( 'There was an error connecting to the API! Please try again later!' === message.text() ) {
				// Disable all form inputes
				allFormInputs.attr( 'disabled', 'disabled' );
			}
		});

	})(jQuery); // End jQuery $ scope
</script>
<fieldset id="account">
	<legend>Are You Sure?</legend>
	<form id="unlink" method="post">
		<input id="action" type="hidden" name="action" value="unlink-user" />
		<table>
			<tr>
				<td><span class="warning">WARNING:</span> You will no longer earn money using GrabPress on this site!</td>
			</tr>
			<tr>
				<td id="acknowledge" class="account-help">I understand and still want to unlink my Publisher account<input id="confirm" name="confirm" type="checkbox" /></td>
			</tr>
			<tr>
				<td class="account-help"><input type="button" id="submit_button" disabled="disabled" class="button-primary" value="Unlink Account" /><input type="button" class="button-secondary" id= "cancel_button" value="<?php esc_attr( _e( 'Cancel' ) ); ?>" /></td>
			</tr>
		</table>
	</form>
</fieldset>
<script>

	// Create jQuery $ scope
	(function($){

		// Define vars
		var confirm = $( '#confirm' )
				message = $( '#message p' ),
				submitBtn = $( '#submit_button' ),
				unlink = $( '#unlink' ),
				cancelBtn = $( '#cancel_button' ),
				action = $( '#action' )
		;

		// On confirm click
		confirm.on( 'click', function() {
			// If confirmed and no error message
			if ( confirm.attr( 'checked' ) && 'There was an error connecting to the API! Please try again later!' !== message ) {
				// Enable submit button
				submitBtn
					.removeAttr( 'disabled' )
					.on( 'click', function() {
						unlink.submit();
					})
				;
			} else { // Not confirmed and/or error message exists
				// Disable submit button
				submitBtn
					.attr( 'disabled', 'disabled' )
					.off( 'click' )
				;
			}
		});

		// On cancel button click
		cancelBtn.on( 'click', function() {
			// Reset form
			unlink[0].reset();
			action.val( 'default' );
			unlink.submit();
		});

	})(jQuery); // End jQuery $ scope
</script>
/**
 * Override Thickbox's tb_remove() function so that it re-enables the
 * post's "save" buttons.
 *
 * @see http://stackoverflow.com/questions/6091998/how-would-you-trigger-an-event-when-a-thickbox-closes
 */
var original_tb_remove = window.tb_remove;
tb_remove = function() {
	original_tb_remove(); // calls the tb_remove() of the Thickbox plugin
	pmc_post_savior.enable_buttons();
	setTimeout( "pmc_post_savior.login_check()", 15000 );
};

/**
 * PMC Post Savior class
 */
pmc_post_savior = {

	/**
	 * AJAX data object
	 *
	 * @see this.login_check()
	 */
	login_check_data: {
		action: "pmc_post_savior_check",
		nonce: pmc_post_savior_opts.nonce
	},

	/**
	 * AJAX poll to see if the user is still logged in
	 * If one of the login cookies is missing, display a "login" window and
	 * disable the save buttons.
	 *
	 * @return void
	 */
	login_check: function() {
		jQuery.post( ajaxurl, this.login_check_data, function( response ) {
			if ( "logged_in" == response ) {
				setTimeout( "pmc_post_savior.login_check()", 15000 );
			} else {
				// Authentication failure, this message should persist on the screen so the logged out user can click the login link.
				pmc_post_savior.notification( '<p>' + pmc_post_savior_text.not_logged_in + '</p><p><a href="' + pmc_post_savior_opts.login_url + '" class="thickbox">' + pmc_post_savior_text.log_in + '</a></p>' );

				pmc_post_savior.disable_buttons();
			}
		} );
	},

	/**
	 * Disable the save/publish/update/move to trash buttons.
	 *
	 * @return void
	 */
	disable_buttons: function() {
		blockSave = true;

		jQuery(':button, :submit, #post-preview, .submitdelete', '#submitpost').each(function(){
			var t = jQuery(this);
			if ( t.hasClass('button-primary') )
				t.addClass('button-primary-disabled');
			else
				t.addClass('button-disabled');

			t.prop("disabled", true);
		});
	},

	/**
	 * Re-enable the save/publish/update/move to trash buttons.
	 *
	 * @return void
	 */
	enable_buttons: function() {
		blockSave = false;

		jQuery(':button, :submit, #post-preview, .submitdelete', '#submitpost').each(function(){
			var t = jQuery(this);
			if ( t.hasClass('button-primary-disabled') )
				t.removeClass('button-primary-disabled');
			else
				t.removeClass('button-disabled');

			t.prop("disabled", false);
		});
	},

	/**
	 * Creates a nice notification overlay on the screen.
	 * Based on P2's newNotification() function
	 *
	 * @param string message
	 * @return void
	 */
	notification: function( message ) {
		// Calculate the highest z-index on the page, so that this can overlay
		// on top of it
		var highest_z_index = jQuery( "#pmc-post-savior-notice" ).css( "z-index" );
		jQuery( "body" ).find( "*" ).each( function() {
			var this_z_index = jQuery( this ).css( "z-index" );
			if ( "auto" !== this_z_index && this_z_index > highest_z_index ) {
				highest_z_index = this_z_index;
			}
		} );

		jQuery("#pmc-post-savior-notice")
			.css( "z-index", highest_z_index )
			.stop( true )
			.prepend( message + '<br />' )
			.fadeIn()
			.animate({ opacity: 0.7 }, 2000);
		jQuery("#pmc-post-savior-notice a").each(function() {
			jQuery(this).click(function() {
				jQuery( '#pmc-post-savior-notice' )
					.stop( true )
					.fadeOut( 'fast' )
					.html( '' );
			});
		});
	},

	/**
	 * This targets the "Close" button on wp-login.php's "interim login"
	 * screen, and binds the click event to close the modal popup.
	 *
	 * @return void
	 */
	interim_login: function() {
		jQuery("#login :button").click(function(e) {
			parent.window.tb_remove();
			e.preventDefault();
			return false;
		});
	}

}

jQuery(document).ready( function() {
	if ( "true" == pmc_post_savior_opts.interim_login ) {
		// Run this on the login modal popup
		pmc_post_savior.interim_login();
	} else {
		// Poll for login
		pmc_post_savior.login_check();
	}
});

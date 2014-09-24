<script type="text/javascript">
	// Create jQuery $ scope
	(function($){

		// Define vars
		var gpRedirectTime,
				message = $( '#message' ),
				wpBodyContent = $( '#wpbody-content' ),
				gpRedirectURL = 'admin.php?page=gp-autoposter',
				gpRedirectSeconds = 4
		;

		// Hide message
		message.hide();

		function gpRedirect() {
			// Update page title to show redirect message
			document.title = 'Redirecting in ' + gpRedirectSeconds + ' seconds';

			// Decrement redirect seconds
			gpRedirectSeconds = gpRedirectSeconds - 1;

			// Set timeout loop for every 1 second to refire gpRedirect() and update title
			// with # of seconds remaining to redirect
			gpRedirectTime = setTimeout( gpRedirect, 1000 );

			// Once seconds get below 0
			if ( -1 == gpRedirectSeconds ) {
				// Clear timeout loop
				clearTimeout( gpRedirectTime );

				// Update title to show active redirecting
				document.title='Redirecting ...';

				// Redirect
				window.location = gpRedirectURL;
			}
		}

		// DOM ready
		$(function() {
			// Append message to WP body content
			wpBodyContent.append( message );

			// Redirect with countdown
			gpRedirect();
		});

	})(jQuery); // End jQuery $ scope
</script>
<div id="message-feed-created" class="updated fade">
	<p>Template updated successfully.  Redirecting in 5 seconds ...  If you are not redirected automatically, please press <a href="admin.php?page=gp-template">here</a></p>
</div>
<div class="grabgear">
	<?php echo '<img src="' . plugin_dir_url( __FILE__ ) . 'images/grabgear.gif" alt="Grab">'; ?>
</div>
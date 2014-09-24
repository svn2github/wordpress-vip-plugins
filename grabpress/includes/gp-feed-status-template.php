<?php

?>
<script type="text/javascript">
	// Create jQuery $ scope
	(function($){

		// Define vars
		var gpRedirectTime,
				wpBodyContent = $( '#wpbody-content' ),
				gpRedirectURL = 'admin.php?page=gp-autoposter',
				gpRedirectSeconds = 4
		;

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
			message = $( '#message' );
			wpBodyContent.append( message );
			message.hide();

			// Redirect with countdown
			gpRedirect();
		});

	}(jQuery)); // End jQuery $ scope
</script>

<div id="message-feed-created" class="updated fade">
	<p>Feed <?php esc_html( $request['status_text'] ); ?> successfully.  Redirecting in 5 seconds ...  If you are not redirected automatically, please press <a href="admin.php?page=gp-autoposter">here</a></p>
</div>

<?php
	// If page and action set in request and page is autoposter and action is either update or modify
	if ( isset( $request['page'], $request['action'] ) && 'autoposter' == $request['page'] && ( 'update' == $request['action'] || 'modify' == $request['action'] ) ) {
		// If current ENV is development
		if ( DEVELOPMENT_ENV == Grabpress::$environment ) {
			// Create times and values arrays for development
			$times = array(
				'15 mins',
				'30 mins',
				'45 mins',
				'01 hr',
				'02 hrs',
				'06 hrs',
				'12 hrs',
				'01 day',
				'02 days',
				'03 days',
			);
			$values = array(
				15*60,
				30*60,
				45*60,
				60*60,
				120*60,
				360*60,
				720*60,
				1440*60,
				2880*60,
				4320*60,
			);
		} else { // Current ENV is production
			// Create times and values arrays for production
			$times = array(
				'06 hrs',
				'12 hrs',
				'01 day',
				'02 days',
				'03 days',
			);
			$values = array(
				360*60,
				720*60,
				1440*60,
				2880*60,
				4320*60,
			);
		}

		// If schedule was set in request
		if ( isset( $request['schedule'] ) ) {
			// Loop through times
			for ( $o = 0; $o < count( $times ); $o++ ) {
				// Get current iterations time and value
				$time = $times[ $o ];
				$value = $values[ $o ];

				// If requested schedule is current value
				if( $value == $request['schedule'] ) {
					// Output schedule confirmation message with time frequency
					Grabpress::$message = 'A new draft or post will be created every ' . $time . ' if videos that meet your search criteria have been added to our catalog.';
				}
			}
		}
	}
?>
<div class="grabgear">
	<?php echo '<img src="' . plugin_dir_url( __FILE__ ) . 'images/grabgear.gif" alt="Grab">'; ?>
</div>
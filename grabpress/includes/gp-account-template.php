<div class="wrap">
<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'images/logo-dark.png' ); ?>" alt="Logo" />
	<h2>GrabPress: Earn money with a Grab Publisher Account</h2>
	<?php
		$user = Grabpress_API::get_user();

		// Check if email linked to user account
		$linked = isset( $user->email );

		// If email linked
		if( $linked ) {
	?>
		<p class="account-help">This installation is linked to <?php echo esc_html( $user->email ); ?></p>
	<?php } else { ?>
		<p class="account-help">This installation is not linked to a Publisher account. <a href="#">what is that?</a><br />
		Linking GrabPress to your account allows us to keep track of the video ads displayed with your Grab content and make sure you get paid.</p>
	<?php } ?>
	<p class="account-help">From here you can:</p>
	<?php
		echo $linked ? Grabpress::render( 'includes/account/chooser/linked.php', array( 'request' => $request ) ) : Grabpress::render( 'includes/account/chooser/unlinked.php', array( 'request' => $request ) );
	?>
	<?php
		switch( $request['action'] ) {
			case 'default':
			case NULL:
				if( $linked ) {
					break;
				}
			case 'switch':
				echo Grabpress::render( 'includes/account/forms/link.php', array( 'request' => $request ) );
				break;
			case 'create':
				echo Grabpress::render('includes/account/forms/create.php', array( 'request' => $request ) );
				break;
			case 'unlink':
				echo Grabpress::render('includes/account/forms/unlink.php', array( 'request' => $request ) );
				break;
		}
	?>
	<script>
		// Create jQuery $ scope
		(function($){

			// DOM ready
			$(function() {
				// On account chooser input change
				$( '#account-chooser input' ).on( 'change', function() {
					// Submit account chooser
					$( '#account-chooser' ).submit();
				});
			});

		})(jQuery); // End jQuery $ scope
	</script>
</div>
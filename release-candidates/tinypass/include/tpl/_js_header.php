<?php
/**
 * Rendered by @see TinypassInternal
 * @var TinypassInternal $this
 */
?>
<script type="text/javascript">
	tp = window['tp'] || [];

	tp.push( ['setTrackPages', true] );

	<?php if ( $this->appId() ): ?>
	tp.push( ['setAid', <?php echo json_encode( $this->appId() )?>] );
	<?php if ( $this->environment() == $this::MODE_PRODUCTION ): ?>
	tp.push( ['setSandbox', false] );
	<?php elseif ( $this->environment() == $this::MODE_SANDBOX ): ?>
	tp.push( ['setSandbox', true] );
	<?php else: ?>
	tp.push( ['setEndpoint', <?php echo json_encode( $this->baseURL() ) ?>] );
	<?php endif ?>
	<?php if ( $this->userProvider() == TinypassConfig::USER_PROVIDER_TINYPASS_ACCOUNTS ): ?>
	tp.push( ['setUseTinypassAccounts', true] );
	<?php endif ?>
	<?php endif ?>

	<?php if ( $this->userId() && $this->nativeUsersAvailable() ): ?>
	<?php /*
    User ref token is used for identification of the user inside tinypass, it is base64-encoded string with user's data, i.e:
	tp.push(['setUserRef', 'aHR0cHM6Ly93d3cueW91dHViZS5jb20vd2F0Y2g/dj1kUXc0dzlXZ1hjUQ==']);
	 */?>
	tp.push( [
		'setUserRef',
		<?php echo json_encode( $this->createUserRef() ) ?>
	] );
	<?php endif ?>


	tp.push( [
		'init', function () {
			<?php if ( $this->userProvider() == TinypassConfig::USER_PROVIDER_JANRAIN ): ?>
			tp.janrain.init( {
				appName: <?php echo json_encode( $this->features()->{ TinypassConfig::FEATURE_NAME_JANRAIN_APP_NAME } ) ?>,
				appId: <?php echo json_encode( $this->features()->{ TinypassConfig::FEATURE_NAME_JANRAIN_APP_ID } ) ?>,
				clientId: <?php echo json_encode( $this->features()->{ TinypassConfig::FEATURE_NAME_JANRAIN_CLIENT_ID } ) ?>
			});
			<?php endif ?>

			var oldCookieVal = tp.util.findCookieByName( <?php echo json_encode( strval( $this::RESOURCES_COOKIE_NAME ) ) ?> );

			tp.user.refreshAccessToken( true, function ( newCookieVal ) {
				if (
					(((typeof oldCookieVal) != "string") || (oldCookieVal.length == 0))
					&&
					(((typeof newCookieVal) == "string") && (newCookieVal.length > 0))
					) {
					location.reload();
				}
			} );
		}
	] );

	(
		function () {
			var a = document.createElement( 'script' );
			a.type = 'text/javascript';
			a.async = true;
			a.src = <?php echo json_encode($this->jsURL()) ?>;
			var b = document.getElementsByTagName( 'script' )[0];
			b.parentNode.insertBefore( a, b )
		}
	)();
</script>

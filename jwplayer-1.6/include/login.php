<?php

function jwplayer_login_create_pages() {
	//adds the login page
	add_submenu_page( null, 'JW Player Authorization', 'JW Player Authorization', 'manage_options', 'jwplayer_login_page', 'jwplayer_login_page' );
	//adds the logout page
	add_submenu_page( null, 'JW Player Deauthorization', 'JW Player Deauthorization', 'manage_options', 'jwplayer_logout_page', 'jwplayer_login_logout' );
}

function jwplayer_login_print_error( $message ) {
	?>
	<div class='error fade'>
		<p>
			<strong><?php echo esc_html( $message ); ?></strong>
		</p>
	</div>
	<?php
}

// The login page
function jwplayer_login_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		jwplayer_login_print_error( 'You do not have sufficient privileges to access this page.' );
		return;
	}

	if ( ! isset( $_POST['apikey'], $_POST['apisecret'] ) ) { // Input var okay
		jwplayer_login_form();
		return;
	}

	// Check the nonce (counter XSRF)
	if ( isset( $_POST['_wpnonce'] ) ) { // Input var okay
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'jwplayer-login-nonce' ) ) { // Input var okay
			jwplayer_login_print_error( 'Could not verify the form data.' );
			jwplayer_login_form();
			return;
		}
	}

	$api_key = isset( $_POST['apikey'] ) ? sanitize_text_field( wp_unslash( $_POST['apikey'] ) ) : false; // Input var okay

	$api_secret = isset( $_POST['apisecret'] ) ? sanitize_text_field( wp_unslash( $_POST['apisecret'] ) ) : false; // Input var okay

	$api_verified = jwplayer_login_verify_api_key_secret( $api_key, $api_secret );

	if ( null === $api_verified ) {
		$login_error = 'Communications with the JW Player API failed';
		if ( version_compare( PHP_VERSION, JWPLAYER_MINIMUM_PHP_VERSION, '<' ) ) {
			$login_error .= ', because you are using PHP version ' . esc_html( PHP_VERSION ) . '. ';
			$login_error .= 'You need at least version ' . esc_html( JWPLAYER_MINIMUM_PHP_VERSION ) . 'to use the JW Player plugin.';
		} else {
			$login_error .= '. Please try again later.';
		}
		jwplayer_login_print_error( $login_error );
		jwplayer_login_form();
	} elseif ( false === $api_verified ) {
		jwplayer_login_print_error( 'Your API credentials were not accepted. Please try again.' );
		jwplayer_login_form();
	} else {
		// Perform the login.
		update_option( 'jwplayer_api_key', $api_key );
		update_option( 'jwplayer_api_secret', $api_secret );
		$settings_page = get_admin_url( null, 'options-general.php?page=jwplayer_settings' );
		?>
		<h2>Authorization succesful</h2>
		<p>
			You have successfully authorized the plugin to access your JW Player account.
		</p>
		<p>
			You can now update <a href="<?php echo esc_url( $settings_page ); ?>">the settings of the JW Player plugin</a>.
		</p>
		<?php
	}
}

// Print the login page
function jwplayer_login_form() {
	?>
	<div class="wrap">
		<h2>Plugin Authorization</h2>

		<form method="post" action="">
			<p>
				In order to use the JW Player plugin, you need to authorize the plugin
				to access the data in your JW Player account. (Don't have a JW Player
				account yet? <a href="https://www.jwplayer.com/pricing/">Sign up
				here</a>).
			</p>
			<p>
				Insert your JW Player API Credentials below. These are located in the
				<strong>Account > API Credentials </strong> section of your dashboard.
				Then choose property and click <strong>Show Credentials</strong>.
			</p>
			<table class="form-table">

				<tr valign="top">
					<th scope="row">API Key</th>
					<td><input type="text" name="apikey"></td>
				</tr>

				<tr valign="top">
					<th scope="row">API Secret</th>
					<td><input type="password" name="apisecret">
				</tr>

			</table>

			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'jwplayer-login-nonce' ) ); ?>">

			<p class="submit"><input type="submit" class="button-primary" value="Authorize plugin"></p>

		</form>
	</div>
	<?php
}

/**
 * Verify the API key and secret that the user has given, by making a call to
 * the API.
 *
 * If the credentials are invalid, return false.
 *
 * If the API call failed, return NULL.
 */
function jwplayer_login_verify_api_key_secret( $key, $secret ) {
	// Create an API object with the provided key and secret.
	$api = new JWPlayer_api( $key, $secret );
	$response = $api->call( '/accounts/show' );
	return jwplayer_api_response_ok( $response );
}

// The logout page
function jwplayer_login_logout() {
	if ( ! current_user_can( 'manage_options' ) ) {
		jwplayer_login_print_error( 'You do not have sufficient privileges to access this page.' );
		return;
	}

	if ( ! isset( $_POST['logout'] ) ) { // Input var okay
		jwplayer_login_logout_form();
		return;
	}

	// Check the nonce (counter XSRF)

	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'jwplayer-logout-nonce' ) ) { // Input var okay
		jwplayer_login_print_error( 'Could not verify the form data.' );
		jwplayer_login_logout_form();
		return;
	}

	// Perform the logout.
	update_option( 'jwplayer_login', null );
	update_option( 'jwplayer_api_key', '' );
	update_option( 'jwplayer_api_secret', '' );

	$login_url = get_admin_url( null, 'admin.php?page=jwplayer_login_page' );
	$plugins_url = get_admin_url( null, 'plugins.php' );

	?>
	<h2>Deauthorization successful.</h2>
	<p>
		You can <a href="<?php echo esc_url( $login_url ); ?>">authorized the plugin with different credentials</a> or
		disable the JW Player plugin on <a href="<?php echo esc_url( $plugins_url ); ?>">the plugins page</a>.
	</p>
	<?php
}

// Print the logout page
function jwplayer_login_logout_form() {
	?>
	<div class="wrap">
		<h2>JW Player deauthorization</h2>

		<form method="post" action="">
			<p>You can use this page to deauthorize access to your JW Player account.<br>
				Note that, while deauthorized, videos will not be embedded.</p>

			<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'jwplayer-logout-nonce' ) ); ?>">

			<p class="submit"><input type="submit" class="button-primary" value="Deauthorize" name="logout"></p>

		</form>
	</div>
	<?php
}

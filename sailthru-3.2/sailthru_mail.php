<?php

/**
 * Override the WordPress mailing system to use Sailthru
 *
 * Using the Sailthru plugin the customer can select specific templates to Send via Sailthru
 * if any of the options are selected we override the mail functionality but give the option
 * to only use Sailthru in explicit cases using the phpmailer_init action. When sailthru
 * is not declared as the sender the mailing will still continue to use the default options
 */


if ( get_option( 'sailthru_setup_complete' ) && ! function_exists( 'wp_mail' ) ) {



	// configure the mail override function.
	function sailthru_configure_mailer( $phpmailer, $template = '' ) {
		$phpmailer->Mailer = 'sailthru';
	}

	function sailthru_set_html_mail_content_type() {
		return 'text/html';
	}


	// check each of the options for transactionals

	$sailthru = get_option( 'sailthru_setup_options' );

	$sailthru_template_fields = array(
		'sailthru_setup_new_user_override_template',
		'sailthru_setup_password_reset_override_template',
		'sailthru_setup_email_template',
	);

	// look to see if any of the fields have a template selected and override the mailer.
	foreach ( $sailthru_template_fields as $field ) {

		if ( isset( $sailthru[ $field ] ) && ! empty( $sailthru[ $field ] ) ) {
			// We know a template is being used so include our wp_mail to override.
			require_once SAILTHRU_PLUGIN_PATH . 'sailthru-wpmail.php';
			add_action( 'phpmailer_init', 'sailthru_configure_mailer', 1, 3 );
			break;
		}
	}


	if ( isset( $sailthru['sailthru_setup_new_user_override_template'] ) &&
					! empty( $sailthru['sailthru_setup_new_user_override_template'] ) ) {


		if ( ! function_exists( 'wp_new_user_notification' ) ) {


			function wp_new_user_notification( $user_id, $deprecated = null, $notify = '' ) {
				if ( null !== $deprecated ) {
					_deprecated_argument( __FUNCTION__, '4.3.1' );
				}

				global $wpdb, $wp_hasher;
				$user            = get_userdata( $user_id );
				$user_vars       = get_user_meta( $user_id );
				$sailthru_params = array();

				$sailthru_params['vars']['user'] = $user_vars;

				$sailthru = get_option( 'sailthru_setup_options' );

				if ( isset( $sailthru['sailthru_setup_new_user_override_template'] ) && ! empty( $sailthru['sailthru_setup_new_user_override_template'] ) ) {
					$sailthru_params['template'] = $sailthru['sailthru_setup_new_user_override_template'];
				}

				// The blogname option is escaped with esc_html on the way into the database in sanitize_option
				// we want to reverse this for the plain text arena of emails.
				$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

				if ( 'user' !== $notify ) {
					$switched_locale = switch_to_locale( get_locale() );
					$message         = sprintf( __( 'New user registration on your site %s:' ), $blogname ) . "\r\n\r\n";
					$message        .= sprintf( __( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
					$message        .= sprintf( __( 'Email: %s' ), $user->user_email ) . "\r\n";

					add_filter( 'wp_mail_content_type', 'sailthru_set_html_mail_content_type' );

					try {
						wp_mail( get_option( 'admin_email' ), sprintf( __( '[%s] New User Registration' ), $blogname ), $message, '', '', $sailthru_params );
					} catch ( Exception $e ) {
						// write log will only write to the logs when in debug mode.
						write_log( $e );
					}
					remove_filter( 'wp_mail_content_type', 'sailthru_set_html_mail_content_type' );

					if ( $switched_locale ) {
						restore_previous_locale();
					}
				}

				// `$deprecated was pre-4.3 `$plaintext_pass`. An empty `$plaintext_pass` didn't sent a user notification.
				if ( 'admin' === $notify || ( empty( $deprecated ) && empty( $notify ) ) ) {
					return;
				}

				// Generate something random for a password reset key.
				$key = wp_generate_password( 20, false );

				/** This action is documented in wp-login.php */
				do_action( 'retrieve_password_key', $user->user_login, $key );

				// Now insert the key, hashed, into the DB.
				if ( empty( $wp_hasher ) ) {
					$wp_hasher = new PasswordHash( 8, true );
				}
				$hashed = time() . ':' . $wp_hasher->HashPassword( $key );
				$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user->user_login ) );

				$switched_locale = switch_to_locale( get_user_locale( $user ) );

				$url .= network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' );

				$message  = sprintf( __( 'Username: %s' ), $user->user_login ) . '<br/><br/>';
				$message .= __( 'To set your password, visit the following address:' ) . '<br/><br/>';
				$message .= '<a href="' . $url . '">' . wp_login_url() . '</a><br/>';

				add_filter( 'wp_mail_content_type', 'sailthru_set_html_mail_content_type' );
				wp_mail( $user->user_email, sprintf( __( '[%s] Your username and password info' ), $blogname ), $message, '', '', $sailthru_params );
				remove_filter( 'wp_mail_content_type', 'sailthru_set_html_mail_content_type' );

				if ( $switched_locale ) {
					restore_previous_locale();
				}
			}
		}
	}
}

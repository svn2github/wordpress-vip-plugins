<?php
/*
Plugin Name: Sailthru for WordPress
Plugin URI: http://sailthru.com/
Description: Add the power of Sailthru to your Wordpress set up.
Version: 3.0.5
Author: Sailthru
Author URI: http://sailthru.com
Author Email: nick@sailthru.com
License:

  Copyright 2013 (Sailthru)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/


if( ! defined('SAILTHRU_PLUGIN_PATH') )
	define( 'SAILTHRU_PLUGIN_PATH', plugin_dir_path(__FILE__) );

if( ! defined('SAILTHRU_PLUGIN_URL') )
	define( 'SAILTHRU_PLUGIN_URL', plugin_dir_url(__FILE__) );


/*
 * Sailthru PHP5 Developer Library
 * Source: http://getstarted.sailthru.com/developers/client-libraries/set-config-file/php5
 */
require_once( SAILTHRU_PLUGIN_PATH . 'lib/Sailthru_Util.php' );
require_once( SAILTHRU_PLUGIN_PATH . 'lib/Sailthru_Client.php' );
require_once( SAILTHRU_PLUGIN_PATH . 'lib/Sailthru_Client_Exception.php' );
require_once( SAILTHRU_PLUGIN_PATH . 'classes/class-wp-sailthru-client.php');

/*
 * Get Sailthru for Wordpress plugin classes
 */
require_once( SAILTHRU_PLUGIN_PATH . 'classes/class-sailthru-horizon.php' );
require_once( SAILTHRU_PLUGIN_PATH . 'classes/class-sailthru-concierge.php' );
require_once( SAILTHRU_PLUGIN_PATH . 'classes/class-sailthru-scout.php' );


/*
 * Get Sailthru Custom Subscribe Fields classes
 */
require_once( SAILTHRU_PLUGIN_PATH . 'classes/class-sailthru-subscribe-fields.php');

/*
 * Sailthru for Wordpress admin view settings and registrations.
 */
require_once( SAILTHRU_PLUGIN_PATH . 'views/admin.functions.php' );

/*
 * Grab and activate the Sailthru Subscribe widget.
 */
require_once( SAILTHRU_PLUGIN_PATH . 'widget.subscribe.php' );


/*
 * Horizon handles the foundational actions like adding menus, meta tags,
 * and javascript files.
 */
if( class_exists( 'Sailthru_Horizon' ) ) {

	$sailthru_horizon = new Sailthru_Horizon();

	if( class_exists( 'Sailthru_Scout' ) ) {
		$sailthru_scout = new Sailthru_Scout();
	}
}



/**
 * Register hooks that are fired when the plugin is activated,
 * deactivated, and uninstalled, respectively.
 */
register_activation_hook( __FILE__, array( 'Sailthru_Horizon', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Sailthru_Horizon', 'deactivate' ) );
register_uninstall_hook(  __FILE__, array( 'Sailthru_Horizon', 'uninstall' ) );



// This is called from sailthru_setup_handler()
function sailthru_create_wordpress_template(){

	$wordpress_template = 'Wordpress Template';

	if (get_option('sailthru_setup_complete')) {

		$sailthru = get_option('sailthru_setup_options');
		$api_key = $sailthru['sailthru_api_key'];
		$api_secret = $sailthru['sailthru_api_secret'];

		$client = new WP_Sailthru_Client( $api_key, $api_secret);

		// Find out if 'Wordpress Template' already exists
		$template_exists = false;

		try {
			if ($client) {
				// if we try to grab a template by name that doesn't exist
				// the world blows up. Grab them all and loop through
				$response = $client->getTemplates();
				$templates = $response['templates'];
				foreach($templates as $template) {
					foreach($template as $key=>$value) {
						if( $key == 'name' ) {
							if( $value == $wordpress_template) {
								$template_exists = true;
							}
						}
					}
				}
			}
		}
		catch (Sailthru_Client_Exception $e) {
			//silently fail
			return;
		}

		// the Template doesn't exist, so we need to create it.
		if( $template_exists === false ) {

			try {
				if ($client) {
					$new_template = $client->saveTemplate('wordpress-template',
						array(	'name' 			=> $wordpress_template,
								'subject'		=> '{subject}',
								'content_html'	=>  "<html>\n<head>\n<body>\n{body}\n</body>\n</html>",
						)
					);
				}
			}
			catch (Sailthru_Client_Exception $e) {
				//silently fail
				return;
			}

		}

	}

}


// Add and action to handle when a user logs in
add_action('wp_login', 'sailthru_user_login', 10, 2);


function sailthru_user_login($user_login, $user) {

	if (get_option('sailthru_setup_complete')) {

		$sailthru = get_option('sailthru_setup_options');
		$api_key = $sailthru['sailthru_api_key'];
		$api_secret = $sailthru['sailthru_api_secret'];

		//$client = new Sailthru_Client( $api_key, $api_secret );
		$client = new WP_Sailthru_Client( $api_key, $api_secret);

		$id = $user->user_email;
		$options = array(
				'login' => array(
				'user_agent' => $_SERVER['HTTP_USER_AGENT'],
				'key' => 'email',
				'ip' => $_SERVER['SERVER_ADDR'],
				'site' => $_SERVER['HTTP_HOST'],
			),
			'fields' => array('keys' => 1),
		);

		try {
			if ($client) {
				$st = $client->saveUser($id, $options);
			}
		}
		catch (Sailthru_Client_Exception $e) {
			//silently fail
			return;
		}

	}

}


/*
 * If this plugin is active, override native WP email functions
 * sailthru_override_other_emails
 */
if( get_option('sailthru_override_wp_mail')
	  && get_option('sailthru_setup_complete')
		&& !function_exists('wp_mail') ) {

	/*
	 *	Override All Mail
	 */
	function wp_mail($to, $subject, $message, $headers = '', $attachments = array(), $vars = array(), $__template='') {

	  $sailthru_options = get_option('sailthru_setup_options');
	  // we'll be going through Sailthru so we'll handle text/html emails there already
	  // replace the <> in the reset password message link to allow the link to display.
	  // in HTML emails
	  $message = preg_replace( '#<(https?://[^*]+)>#', '$1', $message );

	  extract( apply_filters( 'wp_mail', compact( $to, $subject, $message, $headers = '', $attachments = array() ) ) );

		// recipients
		$recipients = is_array($to) ? implode(',', $to) : $to;

		// as the client library accepts these...
		$vars = array(
			'subject' => $subject,
			'body' => $message
		);


		// template to use
		if( empty($__template) ) {
			$template = $sailthru_options['sailthru_setup_email_template'];
		} else {
			$template = $__template;
		}


		// SEND (ALL EMAILS)
		$sailthru = get_option('sailthru_setup_options');
			$api_key = $sailthru['sailthru_api_key'];
			$api_secret = $sailthru['sailthru_api_secret'];
		$client = new WP_Sailthru_Client( $api_key, $api_secret);
		try {
			if ($client) {
				$r = $client->send($template, $recipients, $vars, array());
			}
		}
		catch (Sailthru_Client_Exception $e) {
			//silently fail
			return;
		}

		return true;

	}





	$sailthru_options = get_option('sailthru_setup_options');
	if( $sailthru_options['sailthru_override_other_emails'] ) {

		if( !empty( $sailthru_options['sailthru_setup_new_user_override_template'] ) ) {

			// Redefine user notification function
			if ( !function_exists('wp_new_user_notification') ) {

				function wp_new_user_notification( $user_id, $plaintext_pass = '' ) {

					if ( empty( $plaintext_pass ) )
						return;

					$sailthru_options = get_option('sailthru_setup_options');
					$template = $sailthru_options['sailthru_setup_new_user_override_template'];

					$vars = array();

					$to = '';
						if ($user = new WP_User($user_id)) {
							$vars['user_login'] = stripslashes($user->user_login);
							$vars['user_email'] = stripslashes($user->user_email);
							$vars['first_name'] = $user->first_name;
							$vars['last_name'] = $user->last_name;
							$to = stripslashes($user->user_email);
						}
					$subject = sprintf( __('[%s] Your username and password'), get_option('blogname') );

					$message  = __('Hi there,') . "\r\n\r\n";
						$message .= sprintf( __("Welcome to %s! Here's how to log in:"), get_option('blogname')) . "\r\n\r\n";
						$message .= wp_login_url() . "\r\n";
						$message .= sprintf( __('Username: %s'), $user_login ) . "\r\n";
						$message .= sprintf( __('Password: %s'), $plaintext_pass ) . "\r\n\r\n";
						$message .= sprintf( __('If you have any problems, please contact me at %s.'), get_option('admin_email') ) . "\r\n\r\n";
						$message .= __('Adios!');
					$headers = '';

					$attachments = array();


					wp_mail($to, $subject, $message, $headers, $attachments, $vars, $template);



				}

			}

		}


		if( !empty( $sailthru_options['sailthru_setup_password_reset_override_template'] ) ) {

			// Redefine admin notification function for password reset
			if ( !function_exists('wp_password_change_notification') ) {

				function wp_password_change_notification(&$user) {
					// send a copy of password change notification to the admin
					// but check to see if it's the admin whose password we're changing, and skip this
					if ( 0 !== strcasecmp( $user->user_email, get_option( 'admin_email' ) ) ) {
						$message = sprintf(__('Password Lost and Changed for user: %s'), $user->user_login) . "\r\n";
						// The blogname option is escaped with esc_html on the way into the database in sanitize_option
						// we want to reverse this for the plain text arena of emails.
						$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
						wp_mail(get_option('admin_email'), sprintf(__('[%s] Password Lost/Changed'), $blogname), $message);
					}

				} // end wp_password_change_notification()

			}

		} // end check to override password reset email

	}


}






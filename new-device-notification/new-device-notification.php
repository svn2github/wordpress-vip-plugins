<?php /*

**************************************************************************

Plugin Name:  New Device Notification
Description:  Uses a cookie to identify new devices that are used to log in. On new device detection, an e-mail is sent. This provides some basic improved security against compromised accounts.
Author:       Automattic VIP Team
Author URI:   http://vip.wordpress.com/

**************************************************************************/

class New_Device_Notification {

	// Notifications won't be sent for a certain period of time after the plugin is enabled.
	// This is to get all of the normal users into the logs and avoid spamming inboxes.
	public $grace_period = 604800; // 1 week

	public $cookie_name = 'deviceseenbefore';

	public $cookie_hash;

	function __construct() {
		// Log when this plugin was first activated
		add_option( 'newdevicenotification_installedtime', time() );

		// Wait until "admin_init" to do anything else
		//add_action( 'admin_init', array( &$this, 'admin_init' ), 99 );
		add_action( 'init', array( &$this, 'admin_init' ), 99 );
	}

	public function admin_init() {
		global $current_user;

		// IP whitelist
		if ( in_array( $_SERVER['REMOTE_ADDR'], array( '72.233.96.227' ) ) )
			return;

		get_currentuserinfo();

		// Users to skip:
		// * Super admins
		// * Users who don't have wp-admin access
		// * Anyone using 2-step auth enabled ( http://en.support.wordpress.com/text-messaging/ )
		//if ( is_super_admin() || ! current_user_can( 'edit_posts' ) || sms_user_has_two_step_auth( $current_user->ID ) )
		if ( ! current_user_can( 'edit_posts' ) )
			return;

		// Set up the per-blog salt
		$salt = get_option( 'newdevicenotification_salt' );
		if ( ! $salt ) {
			$salt = wp_generate_password( 64, true, true );
			add_option( 'newdevicenotification_salt', $salt );
		}

		$this->cookie_hash = hash_hmac( 'md5', $current_user->ID, $salt );

		// Seen this device before?
		if ( $this->verify_cookie() )
			return;

		// Attempt to mark this device as seen via a cookie
		$this->set_cookie();

		// Maybe we've seen this user+IP+agent before but they don't accept cookies?
		$memcached_key = 'lastseen_' . $current_user->ID . '_' . md5( $_SERVER['REMOTE_ADDR'] . '|' . $_SERVER['HTTP_USER_AGENT'] );
		if ( wp_cache_get( $memcached_key, 'newdevicenotification' ) )
			return;

		// As a backup to the cookie, record this IP address (only in memcached for now, proper logging will come later)
		wp_cache_set( $memcached_key, time(), 'newdevicenotification' );

		$this->notify_of_new_device();

	}

	public function verify_cookie() {
		if ( ! empty( $_COOKIE[$this->cookie_name] ) && $_COOKIE[$this->cookie_name] === $this->cookie_hash )
			return true;

		return false;
	}

	public function set_cookie() {
		if ( headers_sent() )
			return false;

		$tenyrsfromnow = time() + 315569260;

		// Front end (probably a mapped domain)
		$parts = parse_url( home_url() );
		setcookie( $this->cookie_name, $this->cookie_hash, $tenyrsfromnow, COOKIEPATH, $parts['host'], false, true );

		// If admin area is on a different domain, set a cookie there too
		if ( site_url() != home_url() ) {
			$parts = parse_url( site_url() );
			setcookie( $this->cookie_name, $this->cookie_hash, $tenyrsfromnow, SITECOOKIEPATH, $parts['host'], false, true );
		}
	}

	public function notify_of_new_device() {
		global $current_user;

		get_currentuserinfo();

		$location = $this->ip_to_city( $_SERVER['REMOTE_ADDR'] );
		$blogname = html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		send_vip_team_debug_message( "[NDN] New device detected for {$current_user->user_login} on " . parse_url( home_url(), PHP_URL_HOST ) . ': ' . $_SERVER['REMOTE_ADDR'] . " ({$location->human}) using " . $_SERVER['HTTP_USER_AGENT'] );

		// If we're still in the grace period, don't send an e-mail
		$installed_time = get_option( 'newdevicenotification_installedtime' );
/*
		if ( time() - $installed_time < $this->grace_period )
			return false;
*/

		$subject = sprintf( apply_filters( 'ndn_subject', '[%1$s] %2$s has logged in from an unknown device' ), $blogname, $current_user->display_name );
		$message = sprintf( apply_filters( 'ndn_message',
'Hello,

This is an automated email to all site moderators to inform you that %1$s has logged into %2$s ( %3$s ) from a device that we don\'t recognize or that had last been used before %9$s when this monitoring was first enabled.

While they are likely simply logging in from a new web browser or computer (in which case this email can be safely ignored), there is also a chance that their account has been compromised and someone else has logged into their account.

Here are some details about the login to help verify if it was legitimate:

WP.com Username: %8$s
IP Address: %4$s
Hostname: %5$s
Guessed Location: %6$s  (likely completely wrong for mobile devices)
Browser User Agent: %7$s

If you believe that this log in was unauthorized, please immediately reply to this e-mail and our VIP team will work with you to remove this user\'s access.

You should also immediately have the user change their password:

http://support.wordpress.com/passwords/

Feel free to also reply to this e-mail if you have any questions whatsoever.

- WordPress.com VIP' ),
			$current_user->display_name,               // 1
			$blogname,                                 // 2
			trailingslashit( home_url() ),             // 3
			$_SERVER['REMOTE_ADDR'],                   // 4
			gethostbyaddr( $_SERVER['REMOTE_ADDR'] ),  // 5
			$location->human,                          // 6
			strip_tags( $_SERVER['HTTP_USER_AGENT'] ), // 7, strip_tags() is better than nothing
			$current_user->user_login,                 // 8
			date( 'F jS, Y', $installed_time )         // 9, Not adjusted for timezone but close enough
		);

		// "admin_email" plus any e-mails passed to the vip_multiple_moderators() function
		$emails = array_unique( apply_filters( 'wpcom_vip_multiple_moderators', array( get_option( 'admin_email' ) ) ) );

		$headers  = 'From: "WordPress.com VIP Support" <vip-support@wordpress.com>' . "\r\n";
		$headers .= 'CC: ' . $current_user->user_email . "\r\n";

		wp_mail( $emails, $subject, $message, $headers );

		return true;
	}

	public function ip_to_city( $ip ) {
		$location = ip2location( $ip );

		$human = array();

		if ( ! empty( $location->city ) && '-' != $location->city )
			$human[] = $location->city;

		if ( ! empty( $location->region ) && '-' != $location->region && ( empty( $location->city ) || $location->region != $location->city ) )
			$human[] = $location->region;

		if ( ! empty( $location->country_long ) && '-' != $location->country_long )
			$human[] = $location->country_long;

		if ( ! empty( $human ) ) {
			$human = array_map( 'trim',       $human );
			$human = array_map( 'strtolower', $human );
			$human = array_map( 'ucwords',    $human );

			$location->human = implode( ', ', $human );
		} else {
			$location->human = 'Unknown';
		}

		return $location;
	}
}

$new_device_notification = new New_Device_Notification();

?>
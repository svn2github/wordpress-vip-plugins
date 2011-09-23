<?php /*

**************************************************************************

Plugin Name:  New Device Notification
Description:  Uses a cookie to identify new devices that are used to log in. On new device detection, an e-mail is sent. This provides some basic improved security against compromised accounts.
Author:       Automattic VIP Team
Author URI:   http://vip.wordpress.com/

**************************************************************************/

class New_Device_Notification {

	public $cookie_name = 'deviceseenbefore';

	public $cookie_hash;

	function __construct() {
		add_action( 'init', array( &$this, 'init' ), 99 );

	}

	public function init() {
		global $current_user;

		// IP whitelist
		if ( in_array( $_SERVER['REMOTE_ADDR'], array( '72.233.96.227' ) ) )
			return;

		get_currentuserinfo();

		// Users to skip:
		// * Super admins
		// * Users who don't have wp-admin access
		// * Anyone using 2-step auth enabled ( http://en.support.wordpress.com/text-messaging/ )
		if ( is_super_admin() || ! current_user_can( 'edit_posts' ) || sms_user_has_two_step_auth( $current_user->ID ) )
			return;

		$salt = get_option( 'newdevicenotification_salt' );
		if ( ! $salt ) {
			$salt = wp_generate_password( 64, true, true );
			add_option( 'newdevicenotification_salt', $salt );
		}

		$this->cookie_hash = hash_hmac( 'md5', $current_user->user_login , $salt );

		if ( $this->verify_cookie() )
			return;

		$this->set_cookie();
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

		$subject = sprintf( apply_filters( 'ndn_subject', '[%1$s] %2$s has logged in from an unknown computer' ), $blogname, $current_user->display_name );
		$message = sprintf( apply_filters( 'ndn_message',
'Hello,

This automated e-mail to all site moderators is to inform you that %1$s has logged into %2$s ( %3$s ) from a computer that the site has never seen them log in from before.

Chances are that they are just logging in from a new browser or computer and that this e-mail can safely be ignored.

However there is also a chance that their account has been compromised and someone else has logged into their account.

Here are some details about the login to help verify if it was legitimate or not:

IP Address: %4$s
Hostname: %5$s
Location Based On IP Address: %6$s  (this is an educated guess)
Browser User Agent: %7$s

If you think this log in is potentially not legitmate, please immediately reply to this e-mail so that the WordPress.com VIP staff can assist you in revoking the user\'s access.

Feel free to also reply to this e-mail if you have any questions whatsoever.

- Your WordPress.com VIP Support Team' ),

			$current_user->display_name,              // 1
			$blogname,                                // 2
			trailingslashit( home_url() ),            // 3
			$_SERVER['REMOTE_ADDR'],                  // 4
			gethostbyaddr( $_SERVER['REMOTE_ADDR'] ), // 5
			$location->human,                         // 6
			strip_tags( $_SERVER['HTTP_USER_AGENT'] ) // 7, strip_tags() is better than nothing
		);

		// "admin_email" plus any e-mails passed to the vip_multiple_moderators() function
		$emails = array_unique( apply_filters( 'wpcom_vip_multiple_moderators', array( get_option( 'admin_email' ), $current_user->user_email ) ) );

		$headers = 'From: WordPress.com VIP Support <vip-support@wordpress.com>' . "\r\n";

		foreach ( $emails as $email ) {
			wp_mail( $email, $subject, $message, $headers );
		}
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
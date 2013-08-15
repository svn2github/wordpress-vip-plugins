<?php
/**
 * @package Janrain Capture
 */
/*
Plugin Name: Social User Registration and Profile Storage with Janrain Capture for Wordpress VIP
Plugin URI: http://www.janrain.com/capture/
Description: Collect, store and leverage user profile data from social networks in a flexible, lightweight hosted database.
Version: 0.5.0
Author: Janrain
Author URI: http://developers.janrain.com/extensions/wordpress-for-capture/
License: Apache License, Version 2.0
 */

if ( ! class_exists( 'JanrainCapture' ) ) {
	class JanrainCapture {
		public $path;
		public $basename;
		public $url;
		public static $ui;
		public static $name = 'janrain_capture';
	
		/**
		 * Initializes the plugin.
		 */
		function init() {
			$this->path = plugin_dir_path( __FILE__ );
			$this->url  = plugin_dir_url( __FILE__ );
	
			if ( is_admin() ) {
				require_once $this->path . 'janrain-capture-admin.php';
				$admin = new JanrainCaptureAdmin();
				add_action( 'wp_ajax_' . self::$name . '_redirect_uri', array( &$this, 'redirect_uri' ) );
				add_action( 'wp_ajax_nopriv_' . self::$name . '_redirect_uri', array( &$this, 'redirect_uri' ) );
				add_action( 'wp_ajax_' . self::$name . '_profile', array( &$this, 'profile' ) );
				add_action( 'wp_ajax_nopriv_' . self::$name . '_profile', array( &$this, 'profile' ) );
				add_action( 'wp_ajax_' . self::$name . '_logout', array( &$this, 'logout' ) );
				add_action( 'wp_ajax_nopriv_' . self::$name . '_logout', array( &$this, 'logout' ) );
			} else {
				add_shortcode( self::$name, array( &$this, 'shortcode' ) );
				add_shortcode( 'janrain_share', array( &$this, 'shortcode_share' ) );
			}


			
			require_once $this->path . 'janrain-capture-ui.php';
			$this->ui = new JanrainCaptureUi();
		}
	
		/**
		 * Method used for the janrain_capture_redirect_uri action on admin-ajax.php.
		 */
		function redirect_uri() {
			$url_type = isset( $_REQUEST['url_type'] ) ? $_REQUEST['url_type'] : false;
		    
			// allow only alpha-numeric (and dashes) to prevent hyjacking
		    if ( ! ctype_alnum( str_replace( '-', '', $url_type ) ) ) {
		    	header( 'HTTP/1.1 400 Bad Request' );
		    	exit();
		    }
		    
		    // just in case the url_type isn't specified in the
		    	// capture setting: verify_email_url
		    if ( isset( $_REQUEST['verification_code'] ) ) {
		    	$url_type = 'verify';
		    }
		    
		    // did we even specify a url_type?
			if ( $url_type ) {
				$this->widget_show_screen( $url_type );
				exit();
			}
		    
			// check our redirect
			$r = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( $_SERVER['HTTP_REFERER'] ) : home_url();
			$r = wp_validate_redirect( $r, home_url() );
			echo <<<REDIRECT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
	<head>
	<title>Janrain Capture</title>
	</head>
	<body>
	<script type="text/javascript">
		window.location.href = '$r';
	</script>
	</body>
</html>
REDIRECT;
			exit();
		}
		
	    /**
	     * Method used to write the output of the screen
	     * displays the forgot password and email verification screens
	     */
	    function widget_show_screen( $url_type ){
	        $widget_js = $this->ui->widget_js();
	        echo <<<SCREEN
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/1999/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
    <head>
        <title>Janrain Capture</title>
        <script type="text/javascript">
SCREEN;
	        locate_template( 'janrain-capture-screens/' . $url_type . '.js' , true );
	        echo <<<SCREEN2
        </script>
    </head>
    <body>
SCREEN2;
	        locate_template( 'janrain-capture-screens/' . $url_type . '.html' , true );
	    }
    
		/**
		 * Method used for the janrain_capture_profile action on admin-ajax.php.
		 * This method prints javascript to retreive the access_token from a cookie and
		 * render the profile screen if a valid access_token is found.
		 */
		
		function profile() {
			$ui = isset($ui) ? $ui : new JanrainCaptureUi;
			$display = $ui->edit_screen();
			return $display;
		}
	
		/**
		 * Method used for retrieving a field value
		 *
		 * @param string $name
		 *	 The name of the field to retrieve
		 * @param array $user_entity
		 *	 The user entity returned from Capture
		 * @return string
		 *	 Value retrieved from Capture
		 */
		function get_field( $name, $user_entity ) {
			if ( strpos( $name, '.' ) ) {
				$names = explode( '.', $name );
				$value = $user_entity;
				foreach ( $names as $n ) {
					$value = $value[$n];
				}
				return $value;
			} else {
				return $user_entity[$name];
			}
		}
	
		/**
		 * Method used for the janrain_capture_logout action on admin-ajax.php.
		 */
		function logout() {
			$s = isset($_SERVER['HTTPS']) ? '; secure' : '';
			$n = self::$name;
			$r = isset( $_GET['source'] ) ? esc_url_raw( $_GET['source'] ) : home_url();
			$r = wp_validate_redirect( $r, home_url() );
			echo <<<LOGOUT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	 "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" >
	<head>
	<title>Janrain Capture</title>
	</head>
	<body>
	<script type="text/javascript">
		document.cookie = 'backplane-channel=; expires=Thu, 01-Jan-70 00:00:01 GMT; path=/$s';
		window.location.href = '$r';
	</script>
	</body>
</html>
LOGOUT;
			exit();
		}
	
		/**
		 * Implementation of the janrain_capture shortcode.
		 *
		 * @param string $args
		 *	 Arguments appended to the shortcode
		 *
		 * @return string
		 *	 Text or HTML to render in place of the shortcode
		 */
		function shortcode( $args ) {
			$atts = array(
					'text' => 'Sign in / Register',
					'action' => 'signin',
			);
				
			$atts = shortcode_atts( $atts, $args );
			 
			 if ( strpos( $atts['action'], 'edit_profile' ) === 0 ) {
			 	return $this->profile();
			 }
	
			$link = '<a id="janrain_auth" href="#" class="capture_modal_open" >' . sanitize_text_field( $atts['text'] ) . '</a>
					 <script>
						 if(localStorage && localStorage.getItem("janrainCaptureToken")) {
						 var authLink = document.getElementById("janrain_auth");
						 authLink.innerHTML = "Log out";
						 authLink.setAttribute("href", "'.admin_url().'admin-ajax.php?action=janrain_capture_logout&source='.esc_js( JanrainCaptureUi::current_page_url() ).'");
						 authLink.setAttribute("onclick", "janrain.capture.ui.endCaptureSession()");
						 authLink.setAttribute("class","");
						 }
					 </script>';
			
			return $link;
		}
	
		/**
		 * Implementation of the janrain_share shortcode.
		 *
		 * @param string $args
		 *	 Arguments appended to the shortcode
		 *
		 * @return string
		 *	 Text or HTML to render in place of the shortcode
		 */
		function shortcode_share( $args ) {
			if ( self::share_enabled() ) {
				global $post;
				$atts = array(
						'url' => ($post->ID ? get_permalink() : ''),
						'title' => ($post->ID ? get_the_title() : ''),
						'description' => ($post->ID ? get_the_excerpt() : ''),
						'img' => $image,
						'text' => 'Share on',
				);
				
				$atts = shortcode_atts( $atts, $args );
				$url		 = addslashes( sanitize_text_field( $atts['url'] ) );
				$description = addslashes( sanitize_text_field( $atts['description'] ) );
				$title		 = addslashes( sanitize_text_field( $atts['title'] ) );
				$img		 = addslashes( sanitize_text_field( $atts['img'] ) );
				$text		 = sanitize_text_field( $atts['text'] );
				$onclick     = "setShare('$url', '$title', '$description', '$img', this.getAttribute('rel'))";
				$link		 = '<div class="janrain-share-container">';
				if ( $icons = self::social_icons( $onclick ) )
					$link .= '<span class="janrain-share-text">' . $text . '</span>'.$icons;
				$link     .= '</div>';
				return $link;
			} else
				return '';
		}
	
		/**
		 * Sanitization method to remove special chars
		 *
		 * @param string $s
		 *	 String to be sanitized
		 *
		 * @return string
		 *	 Sanitized string
		 */
		static function sanitize( $s ) {
			return preg_replace( '/[^a-z0-9\._-]+/i', '', $s );
		}
		
		/**
		 * Returns the main site or network option if using multisite
		 *
		 * @param string $key
		 *	 The option key to retrieve
		 * @param mixed $default
		 *	 The default value to use
		 *
		 * @return string
		 *	 The saved option or default value
		 */
		static function get_option( $key, $default = '' ) {
			$value = get_option( $key, $default );
			return $value;
		}
	
		/**
		 * Updates the main site or network option if using multisite
		 *
		 * @param string $key
		 *	 The option key to update
		 * @param mixed $value
		 *	 The value to store in options
		 *
		 * @return boolean
		 *	 True if option value changed, false if not or if failed
		 */
		static function update_option( $key, $value ) {
			if ( is_string( $value ) )
				$value = stripslashes( $value );
			return update_option( $key, $value );
		}
	
		/**
		 * Retrieves the plugin version.
		 *
		 * @return string
		 *	 String version
		 */
		static function share_enabled() {
			$enabled = self::get_option( self::$name . '_ui_share_enabled' );
			if ($enabled == '0')
				return false;
	
			$realm			 = self::get_option( self::$name . '_rpx_realm' );
			$share_providers = JanrainCapture::get_option( JanrainCapture::$name . '_rpx_share_providers' );
			$share_providers = implode( "', '", array_map( 'esc_js', $share_providers ) );
			return ($realm && "['$share_providers']");
		}
	
		/**
		 * Returns markup for enabled social sharing icons.
		 *
		 * @param string $onclick
		 *	 The onclick value for each generated icon
		 * @return string
		 *	 String version
		 */
		static function social_icons( $onclick ) {
			$social_providers = self::get_option( self::$name . '_rpx_share_providers' );
			if ( is_array( $social_providers ) ) {
				$rpx_social_icons = '';
				foreach ( $social_providers as $val ) {
					$val = esc_js( $val );
					$rpx_social_icons .= '<span class="janrain-provider-icon-16 janrain-provider-icon-'.$val.'" rel="'.$val.'" onclick="'.$onclick.'"></span>';
				}
				$buttons = '<span class="rpx_social_icons">' . $rpx_social_icons . '</span>';
				return $buttons;
			}
			return false;
		}
	}
}

$capture = new JanrainCapture;
$capture->init();

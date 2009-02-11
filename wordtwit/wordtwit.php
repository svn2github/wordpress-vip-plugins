<?php
/*
Plugin Name: WordTwit
Plugin URI: http://www.bravenewcode.com/wordtwit
Description: Generates Twitter Updates from Blog Postings
Author: Duane Storey and Dale Mugford, BraveNewCode Inc.
Version: 1.3
Author URI: http://www.bravenewcode.com
*/

// Some ideas taken from http://twitter.slawcup.com/twitter.class.phps

if ( ABSPATH ) {
   require_once( ABSPATH . 'wp-config.php' );
   require_once( ABSPATH . 'wp-includes/class-snoopy.php' );
} else {
   require_once( '../../../wp-config.php' );
   require_once( '../../../wp-includes/class-snoopy.php' );
}
require_once( 'xml.php' );

$twit_plugin_name = 'WordTwit';
$twit_plugin_prefix = 'wordtwit_';
$wordtwit_version = '1.3';

// set up hooks for WordPress
add_action( 'publish_post', 'post_now_published' );
add_action( 'admin_head', 'wordtwit_admin_css' );

function twit_hit_server( $location, $username, $password, &$output, $post = false, $post_fields = '' ) {
   global $wordtwit_version;
   $output = '';
   
   $snoopy = new Snoopy;
   $snoopy->agent = 'WordTwit ' . $wordtwit_version;
   
   if ( $username ) {
      $snoopy->user = $username;
      if ( $password ) {
         $snoopy->pass = $password;      
      }
   }
   
   if ( $post ) {
      // need to do the actual post
      $result = $snoopy->submit( $location, $post_fields );
      if ( $result ) {
         return $true;  
      }
   } else {
      $result = $snoopy->fetch( $location );
      if ( $result ) {
         $output = $snoopy->results;  
      }
      
      $code = explode( ' ', $snoopy->response_code );
      if ( $code[1] == 200) {
         return true;
      } else {
         return false;
      }
   }
}

function twit_update_status( $username, $password, $new_status ) {
   $output = '';
   return twit_hit_server( 'http://twitter.com/statuses/update.xml', $username, $password, $output, true, array( 'status' => $new_status, 'source' => 'wordtwit' ) );
}

function twit_verify_credentials( $username, $password, &$cred ) {
   $output = '';
   
   $result = twit_hit_server( 'http://twitter.com/account/verify_credentials.xml', $username, $password, $output );  
   if ( $result ) {
        $cred = wordtwit_parsexml( $output );
   } 
   return $result;
}

function twit_get_tiny_url( $link ) {
   $output = '';
   $result = twit_hit_server( 'http://tinyurl.com/api-create.php?url=' . $link, '', '', $output );
   
   return $output;
}

function post_now_published( $post_id ) {
	global $twit_plugin_prefix;

	$has_been_twittered = get_post_meta( $post_id, 'has_been_twittered', true );
	if (!($has_been_twittered == 'yes')) {
		query_posts('p=' . $post_id);

		if (have_posts()) {
			the_post();
			$i = 'New blog entry \'' . the_title('','',false) . '\' - ' . get_permalink();
			$message = get_option( $twit_plugin_prefix . 'message' );
			$message = str_replace( '[title]', get_the_title(), $message );
			$message = str_replace( '[link]', twit_get_tiny_url( get_permalink() ), $message );
			
			$twit_username = get_option( $twit_plugin_prefix . 'username', 0 );
			$twit_password = get_option( $twit_plugin_prefix . 'password', 0 );
			
			twit_update_status( $twit_username, $twit_password, $message );
	
			add_post_meta( $post_id, 'has_been_twittered', 'yes' );
		}
	}
}

function wordtwit_admin_css() {
	$url = get_bloginfo('wpurl');
	echo '<link rel="stylesheet" type="text/css" href="' . get_bloginfo('wpurl') . '/wp-content/themes/vip/plugins/wordtwit/css/admin.css" />';
}

function wordtwit_plugin_url( $str = '' ) {
	$dir_name = '/wp-content/themes/vip/plugins/wordtwit';
	echo($dir_name . $str);
}

function bnc_stripslashes_deep( $value ) {
	$value = is_array($value) ?
   array_map('bnc_stripslashes_deep', $value) :
   stripslashes($value);
	return $value;
}

function wordtwit_options_subpanel() {
	if (get_magic_quotes_gpc()) {
    	 $_POST = array_map( 'bnc_stripslashes_deep', $_POST );
	    $_GET = array_map( 'bnc_stripslashes_deep', $_GET );
	    $_COOKIE = array_map( 'bnc_stripslashes_deep', $_COOKIE );
	    $_REQUEST = array_map( 'bnc_stripslashes_deep', $_REQUEST );
	}

	global $twit_plugin_name;
	global $twit_plugin_prefix;

  	if (isset($_POST['info_update'])) {
		if (isset($_POST['username'])) {
			$username = $_POST['username'];
		} else {
			$username = '';
		}

		if (isset($_POST['password'])) {
			$password = $_POST['password'];
		} else {
			$password = '';
		}

		if (isset($_POST['message'])) {
			$message = $_POST['message'];
		} else {
			$message = '';
		}

		update_option( $twit_plugin_prefix . 'username', $username );
		update_option( $twit_plugin_prefix . 'password', $password );
		update_option( $twit_plugin_prefix . 'message', stripslashes($message) );

	} 

	$username = get_option($twit_plugin_prefix . 'username');
	$password = get_option($twit_plugin_prefix . 'password');
	$message = get_option($twit_plugin_prefix . 'message');
	
	if (strlen($message) == 0) {
		$message = "New Blog Entry, \"[title]\" - [link]"; 
		update_option($twit_plugin_prefix . 'message', $message);
	}

   include( 'html/options.php' );
}

function wordtwit_add_plugin_option() {
	global $twit_plugin_name;
	if (function_exists('add_options_page')) {
		add_options_page($twit_plugin_name, $twit_plugin_name, 0, basename(__FILE__), 'wordtwit_options_subpanel');
   }	
}

add_action('admin_menu', 'wordtwit_add_plugin_option');

?>

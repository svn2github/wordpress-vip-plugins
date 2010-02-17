<?php
/*
Plugin Name: WordTwit
Plugin URI: http://www.bravenewcode.com/wordtwit
Description: Generates Twitter Updates from Blog Postings
Author: Duane Storey and Dale Mugford, BraveNewCode Inc., Modified and extended for WordPress.com by Thorsten Ott
Version: 1.3-mod
Author URI: http://www.bravenewcode.com
*/

/*
 *	You can use the following filters to change the twitter message to your needs.
 *
 *	$message = apply_filters( 'wordtwit_pre_proc_message', $message, $post->ID );
 *	this filter is executed before the message string is parsed and replacements for [title] and [link] are done
 * 
 *	$message = apply_filters( 'wordtwit_post_proc_message', $message, $post->ID );
 *	is applied after the replacements for [title] and [link] are processed.
 *
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
$wordtwit_version = '1.3-mod';

// set up hooks for WordPress
add_action( 'publish_post', 'post_now_published' );
add_action( 'admin_head', 'wordtwit_admin_css' );

if ( true == get_option( $twit_plugin_prefix . 'user_override' ) ) {
	add_action( 'show_user_profile', 'twit_show_user_profile', 10, 1 );
	add_action( 'edit_user_profile', 'twit_edit_user_profile', 10, 1 );
	add_action( 'personal_options_update', 'twit_personal_options_update', 10, 1);
	add_action( 'edit_user_profile_update', 'twit_edit_user_profile_update', 10, 1);
}


/*
 * make sure so far unencrypted passwords get encrypted
 */
add_action( 'init', 'twit_encrypt_passwords' );
function twit_encrypt_passwords() {
	global $wpdb, $twit_plugin_prefix;
	if ( empty( $wpdb->blogid ) )
		return;
	
	$encryption_status = get_option( $twit_plugin_prefix . 'encryption_status' );
	if ( 'complete' == $encryption_status )
		return;
	else if ( 'intermediate' != $encryption_status ) {
		update_option( $twit_plugin_prefix . 'encryption_status', 'intermediate' );
		
		$user_options = get_option($twit_plugin_prefix . 'user_options');
		if( is_array( $user_options) && !empty( $user_options) ) {
			foreach( $user_options as $key => $values ) {
				if( isset( $values['twitter_password'] ) && !empty( $values['twitter_password'] ) ) 
					$user_options[$key]['twitter_password'] = wp_encrypt($user_options[$key]['twitter_password']);
			}
			update_option( $twit_plugin_prefix . 'user_options', $user_options);
		}
		
		$twitpw = get_option( $twit_plugin_prefix . 'password', 0 );
		if( !empty( $twitpw ) )
			update_option( $twit_plugin_prefix . 'password', wp_encrypt( $twitpw ) );
		
		update_option( $twit_plugin_prefix . 'encryption_status', 'complete' );
	}
}


function twit_show_user_profile( $user ) {
	global $wpdb, $twit_plugin_prefix;
	if ( empty( $wpdb->blogid ) )
		return;

	$user_options = get_option($twit_plugin_prefix . 'user_options');
   
	if ( isset( $user_options[ $user->ID ] ) ) {
		$twit_options = $user_options[ $user->ID ];
		$twitter_username = $twit_options[ 'twitter_username' ];
		$twitter_password = wp_decrypt( $twit_options[ 'twitter_password' ] );
		$twitter_message = $twit_options[ 'twitter_message' ];
	} else {
		$twitter_username = $twitter_password = $twitter_message = '';
	}
	?>
	
	<div class="section-info">
	<h3>WordTwit Twitter Info</h3>
		WordTwit allows you to publish a Twitter tweet whenever a new blog entry is published.	To enable it, simply enter your Twitter username and password.<br /><br />You can also customize the message Twitter posts to your account by using the "message" field below.	You can use [title] to represent the title of the blog entry, and [link] to represent the permalink.
		<br /><br /><b>Note:</b> These options are stored on a per blog basis to allow different settings for each of your blogs.<br /><br />
	</div>
																													   
	<?php if ( $twitter_username ) { ?>
	  <div class="wrap" id="wordtwit">									 
	  <div class="plugin-section bottom-spacer">
		 <div class="section-info">
			<h3>Twitter Profile</h3>
			The following information is associated with the Twitter credentials supplied below.
		 </div>
		 
		 <div id="twitter-profile" class="editable-area">
			<?php $ok = twit_verify_credentials( $twitter_username, $twitter_password, $result );  ?>
			<?php if ( $ok ) { ?>
			   <div class="avatar">
				  <img src="<?php echo $result['user']['profile_image_url']; ?>" alt="Profile Image" />
			   </div>
			   
			   <div class="info">
				  <h4><?php echo $result['user']['name']; ?>, <?php echo $result['user']['followers_count'] . ' ' . __('followers'); ?></h4>
				  <h5><?php if ( is_array( $result['user']['description'] ) ) _e('No Description On Account'); else echo $result['user']['description']; ?></h5>
			   </div>
			<?php } else { ?>
			   <div class="sorry">
				  <?php _e('Sorry, the credentials you have supplied are invalid.  <br />Please re-enter them again below.'); ?>
			   </div>
			<?php } ?> 
		 </div>
		</div>
	  </div>							   
	  <?php } ?>

	<table class="form-table">
		<tr>
			<th><label for="twitter_username">Twitter Username <span class="description"> (required)</span></label></th>
			<td><input type="text" name="twitter_username" id="twitter_username" value="<?php echo esc_attr($twitter_username) ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="twitter_password">Twitter Password <span class="description"> (required)</span></label></th>
			<td><input type="password" name="twitter_password" id="twitter_password" value="<?php echo esc_attr($twitter_password) ?>" class="regular-text" /></td>
		</tr>
		<tr>
			<th><label for="twitter_message">Twitter Message</label></th>
			<td><input type="text" name="twitter_message" id="twitter_message" value="<?php echo htmlentities($twitter_message) ?>" class="regular-text" /></td>
		</tr>
	</table>											   
	<?php
}

function twit_edit_user_profile( $user ) {
	twit_show_user_profile( $user );
}

function twit_personal_options_update( $user_id ) {
	global $wpdb, $twit_plugin_prefix;

	if ( empty( $wpdb->blogid ) )
		return;
	
	$user_options = get_option($twit_plugin_prefix . 'user_options');
	
	$user_options[ $user_id ] = array(
									  'twitter_username' => $_POST[ 'twitter_username' ],
									  'twitter_password' => wp_encrypt( $_POST[ 'twitter_password' ] ),
									  'twitter_message' => $_POST[ 'twitter_message' ],
									  );
   
	update_option( $twit_plugin_prefix . 'user_options', $user_options );
}

function twit_edit_user_profile_update( $user_id ) {
	twit_personal_options_update( $user_id );
}


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

function twit_get_bitly_url( $link ) {
	global $twit_plugin_prefix;
	$bitly_user_name = get_option( $twit_plugin_prefix . 'bitly_user_name' );
	$bitly_api_key = get_option( $twit_plugin_prefix . 'bitly_api_key' );
	$output = false;
	$result = twit_hit_server( 'http://api.bit.ly/shorten?version=2.0.1&longUrl=' . urlencode( $link ) . '&format=xml&login=' . $bitly_user_name . '&apiKey=' . $bitly_api_key, '', '', $output );
	preg_match( '#<shortUrl>(.*)</shortUrl>#iUs', $output, $url );
	
	if ( isset( $url[1] ) ) {
		return $url[1];	
	} else {
		return $link;
	}
}

// workaround for local tests
if ( !function_exists( 'get_shortlink' ) ) {
	function get_shortlink( $post_id ) {
		$post = get_post( $post_id );
		return $post->guid;
	}
}

function post_now_published( $post_id ) {
	global $twit_plugin_prefix, $post;

	$has_been_twittered = get_post_meta( $post_id, 'has_been_twittered', true );
	if (!($has_been_twittered == 'yes')) {
		query_posts('p=' . $post_id);

		if (have_posts()) {
			the_post();
			global $post;
			$max_age = get_option( $twit_plugin_prefix . 'max_age', 0 );
			if ( $max_age > 0 && ( (current_time('timestamp', 1) - get_post_time('U', true) ) / 3600 ) > $max_age ) {
				return;
			}
			$i = 'New blog entry \'' . the_title('','',false) . '\' - ' . get_permalink();

			$user_override = get_option( $twit_plugin_prefix . 'user_override' );
			$user_preference = get_option( $twit_plugin_prefix . 'user_preference' );
			$user_options = get_option( $twit_plugin_prefix . 'user_options' );

			// get user settings
			if ( $user_override && isset( $user_options[ $post->post_author ] ) ) {
				$twit_options = $user_options[ $post->post_author ];
				$twit_username = $twit_options[ 'twitter_username' ];
				$twit_password = wp_decrypt( $twit_options[ 'twitter_password' ] ) ;
				$message = $twit_options[ 'twitter_message' ];
				
				if ( empty( $message ) ) {
					$message = get_option( $twit_plugin_prefix . 'message' );
				}
			
			}

			// no user settings available or allowed then use global settings 
			if ( ( ( empty( $twit_username ) || empty( $twit_password ) ) && false == $user_preference )
				 || false == $user_override ) {
					$message = get_option( $twit_plugin_prefix . 'message' );
					$twit_username = get_option( $twit_plugin_prefix . 'username', 0 );
					$twit_password = wp_decrypt ( get_option( $twit_plugin_prefix . 'password', 0 ) );
			}

			if ( empty( $twit_username ) || empty( $twit_password ) )
				return;
			
			$message = apply_filters( 'wordtwit_pre_proc_message', $message, $post->ID );
			
			$message = str_replace( '[title]', $post->post_title, $message );
			
			$wordtwit_url_type = get_option( $twit_plugin_prefix . 'wordtwit_url_type' );
			
			if ( strstr( $message, "[link]" ) ) {
				if( 'tinyurl' == $wordtwit_url_type || empty( $wordtwit_url_type ) )
					$message = str_replace( '[link]', twit_get_tiny_url( get_permalink() ), $message );
				elseif( 'bitly' == $wordtwit_url_type )
					$message = str_replace( '[link]', twit_get_bitly_url( get_permalink() ), $message );
				elseif( 'wpme' == $wordtwit_url_type )
					$message = str_replace( '[link]', get_shortlink( $post->ID ), $message );
			}
			
			$message = apply_filters( 'wordtwit_post_proc_message', $message, $post->ID );
			
			twit_update_status( $twit_username, $twit_password, $message );
	
			add_post_meta( $post_id, 'has_been_twittered', 'yes' );
		}
	}
}

function wordtwit_admin_css() {
	$url = get_bloginfo('wpurl');
	echo '<link rel="stylesheet" type="text/css" href="' . get_bloginfo('wpurl') . '/wp-content/themes/vip/plugins/wordtwit-1.3-mod/css/admin.css" />';
}

function wordtwit_plugin_url( $str = '' ) {
	$dir_name = '/wp-content/themes/vip/plugins/wordtwit-1.3-mod';
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
			$password = wp_encrypt( $_POST['password'] );
		} else {
			$password = '';
		}

		if (isset($_POST['message'])) {
			$message = $_POST['message'];
		} else {
			$message = '';
		}

		if (isset($_POST['user_override'])) {
			$user_override = ( $_POST['user_override'] == "true" ) ? true : false;
		} else {
			$user_override = false;
		}

		if (isset($_POST['user_preference'])) {
			$user_preference = ( $_POST['user_preference'] == "true" ) ? true : false;
		} else {
			$user_preference = false;
		}
		
		if (isset($_POST['wordtwit_url_type'])) {
			$wordtwit_url_type = ( in_array( $_POST['wordtwit_url_type'], array( 'bitly', 'tinyurl', 'wpme' ) ) ) ? $_POST['wordtwit_url_type'] : 'wpme';
			
		} else {
			$wordtwit_url_type = 'wpme';
		}
		
		if ( 'bitly' === $wordtwit_url_type ) {
			if( isset( $_POST['bitly_user_name'] ) && isset( $_POST['bitly_api_key'] ) ) {
				$bitly_user_name = $_POST['bitly_user_name'];
				$bitly_api_key = $_POST['bitly_api_key'];
			}	
		}
		
		if (isset($_POST['max_age'])) {
			$max_age = (int) $_POST['max_age'];
		} else {
			$max_age = 24;
		}

		update_option( $twit_plugin_prefix . 'username', $username );
		update_option( $twit_plugin_prefix . 'password', $password );
		update_option( $twit_plugin_prefix . 'message', stripslashes($message) );
		update_option( $twit_plugin_prefix . 'user_override', $user_override );
		update_option( $twit_plugin_prefix . 'user_preference', $user_preference );
		update_option( $twit_plugin_prefix . 'max_age', $max_age );
		update_option( $twit_plugin_prefix . 'wordtwit_url_type', $wordtwit_url_type );
		update_option( $twit_plugin_prefix . 'bitly_user_name', $bitly_user_name );
		update_option( $twit_plugin_prefix . 'bitly_api_key', $bitly_api_key );
	} 

	$username = get_option($twit_plugin_prefix . 'username');
	$password = wp_decrypt ( get_option($twit_plugin_prefix . 'password' ) );
	$message = get_option($twit_plugin_prefix . 'message');	
	$user_override = get_option( $twit_plugin_prefix . 'user_override' );
	$user_preference = get_option( $twit_plugin_prefix . 'user_preference' );
	$max_age = get_option( $twit_plugin_prefix . 'max_age' );
	$wordtwit_url_type = get_option( $twit_plugin_prefix . 'wordtwit_url_type' );
	$bitly_user_name = get_option( $twit_plugin_prefix . 'bitly_user_name' );
	$bitly_api_key = get_option( $twit_plugin_prefix . 'bitly_api_key' );
	
	
	if (strlen($message) == 0) {
		$message = 'New Blog Entry, "[title]" - [link]'; 
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

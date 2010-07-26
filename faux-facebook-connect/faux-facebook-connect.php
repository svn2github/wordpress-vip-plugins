<?php
/*
Plugin Name: Faux Facebook Connect
Plugin URI: http://automattic.com/
Description: Faux Facebook Connect is a basic integration to allow Facebook users to comment on a WordPress blog. It provides single sign on, and avatars. It is tuned for WordPress MU usage as it does not perform any database alterations or adds any users and is mainly a javascript integration. It requires a <a href="http://www.facebook.com/developers/">Facebook API Key</a> for use. Thanks go to Beau Lebens for writing a <a href="http://dentedreality.com.au/2008/12/implementing-facebook-connect-on-wordpress-in-reality/">good introduction</a> and Adam Hupp for his inspireing <a href="http://wordpress.org/extend/plugins/wp-facebookconnect/">WP-Facebookconnect plugin</a>
Version: 0.1
Author: Thorsten Ott
Author URI: http://blog.webzappr.com/
*/

if ( function_exists('wpcom_is_vip') ) {
	add_filter( 'fauxfb_plugin_url', 'fauxfb_plugin_url' );
	function fauxfb_plugin_url() {
		return get_bloginfo('home') . '/wp-content/themes/vip/plugins/faux-facebook-connect';
	}
}


if ( !class_exists( "FauxFacebook" ) ) {
	class FauxFacebook {
		private static $instance = false;
		private static $fb = false;
		
		private static $api_key = false;
		private static $app_secret = false;
		private static $template_bundle = false;
		
		public $plugin_prefix = "fauxfb";
		public static $plugin_prefix_static = "fauxfb";
		private $plugin_name = "FauxFacebook Connect";
		public $admin_notice = '';
		
		public $feature_loader = 'http://static.ak.connect.facebook.com/js/api_lib/v0.4/FeatureLoader.js.php';
		
		public $note_mapping = array(
										'Invalid API key'	=> 'Error: The API Key is not valid! Reverted changes.',
										'Incorrect signature' => 'Error: The Application Secret is invalid! Reverted changes.',
										'Template bundles must include at least one one line story template.' => 'Error: Template bundles must include at least one one line story template. Please check your config.php',
									);
		
		public $fauxfb_one_line_stories = array(
												'{*actor*} commented on the {*blog*} in post {*post*}.',
												'{*actor*} posted comments on {*blog*}.'
												);

		public $fauxfb_short_story_templates = array(
													array(	'template_title'	=>	'{*actor*} commented on the {*blog*} in post {*post*}.',
															'template_body'		=> 	''),
													array(	'template_title'	=>	'{*actor*} posted comments on {*blog*}.',
															'template_body'		=> '')
													);
		
		function __construct() {
			require_once( dirname( __FILE__ ) .  DIRECTORY_SEPARATOR . 'facebook-client' .  DIRECTORY_SEPARATOR . 'facebook.php' );
			// Apply some filters to allow customization via functions.php
			$this->fauxfb_one_line_stories = apply_filters( $this->plugin_prefix . '_one_line_stories_filter', $this->fauxfb_one_line_stories );
			$this->fauxfb_short_story_templates = apply_filters( $this->plugin_prefix . '_short_story_templates_filter', $this->fauxfb_short_story_templates );
		}
		
		public static function get_instance() {
			if ( ! self::$instance ) {
				self::$instance = new self;
			}
			return self::$instance; 
		}
		
		public static function get_fb() {
			if ( ! self::$fb ) {
				self::$fb = new Facebook( 
											self::get_api_key(),
											self::get_app_secret(),
											true,
											'connect.facebook.com'
										);
			}
			return self::$fb; 
		}
		
		public static function get_api_key() {
			if ( ! self::$api_key )
				self::$api_key = get_option( self::$plugin_prefix_static . '_api_key' );
				
			if ( empty( self::$api_key ) && !empty( $_POST[ self::$plugin_prefix_static . '_api_key'] ) )
				self::$api_key = $_POST[ self::$plugin_prefix_static . '_api_key'];
				
			return self::$api_key;
		}
		public static function get_app_secret() {
			if ( ! self::$app_secret )
				self::$app_secret = get_option( self::$plugin_prefix_static . '_app_secret' );
						
			if ( empty( self::$app_secret ) && !empty( $_POST[ self::$plugin_prefix_static . '_app_secret'] ) )
				self::$app_secret = $_POST[ self::$plugin_prefix_static . '_app_secret'];
				
			return self::$app_secret;
		}
		public static function get_template_bundle() {
			if ( ! self::$template_bundle )
				self::$template_bundle = get_option( self::$plugin_prefix_static . '_template_bundle' );
				
			if ( empty( self::$template_bundle ) && !empty( $_POST[ self::$plugin_prefix_static . '_template_bundle'] ) )
				self::$template_bundle = $_POST[ self::$plugin_prefix_static . '_template_bundle'];
				
			return self::$template_bundle;
		}
		
		public function get_fb_api() {
			return $this->get_fb()->api_client;
		}
		
		
		/**
		 * Installation callback.
		 */
		public function activate() {
		}
		
		/**
		 * Deinstallation callback.
		 */
		public function deactivate() {
		}
		
		public function backend_init() {
			// make sure to hook in validation prior to updating the options
			if ( !empty($_POST['option_page']) && $this->plugin_prefix . '-settings' == $_POST['option_page']  ) {
				// submitted data, verify it.
				if ( 'update' == $_POST['action'] ) {
					check_admin_referer( $_POST['option_page'] . '-options' );
					$error = null;
					$data_ok = $this->verify_app_config( $_POST[ $this->plugin_prefix . '_api_key' ], $_POST[ $this->plugin_prefix . '_app_secret' ], $error );
					if ( !$data_ok ) {
						update_option( $this->plugin_prefix . '-notice', esc_attr( $error ) );
						wp_redirect( remove_query_arg( 'updated', wp_get_referer() ) );
						exit;
					}
					
					if ( empty( $_POST[ $this->plugin_prefix . '_template_bundle' ] ) || 1 == (int) $_POST[ $this->plugin_prefix . '_force_template_reload' ] ) {
						$error = null;
						$bundle_id = $this->register_templates( $force=true, $error );

						$_POST[ $this->plugin_prefix . '_template_bundle' ] = $bundle_id; // make sure it's not overwritten by options.php
						if ( !$bundle_id ) {
							update_option( $this->plugin_prefix . '-notice', esc_attr( $error ) );
							wp_redirect( remove_query_arg( 'updated', wp_get_referer() ) );
							exit;
						}
					}
				}
			}
			add_action( 'admin_menu', array( &$this, 'register_admin_panel' ) );
			add_action( 'admin_head', array( &$this, 'admin_header' ) );
			add_action( 'admin_init', array( &$this, 'register_settings' ), 10 );
			add_filter( 'get_avatar', array( &$this, 'get_avatar' ), 10, 4);
		}
		
		public function frontend_init() {
			if ( $this->is_configured() ) {
				add_filter( 'language_attributes', array( &$this, 'add_namespace' ) );
				add_action( 'wp_enqueue_scripts', array( &$this, 'frontend_scripts' ) );
				add_action( 'wp_head', array( &$this, 'frontend_header' ) );
				add_action( 'wp_footer', array( &$this, 'frontend_footer' ) );
				add_filter( 'get_avatar', array( &$this, 'get_avatar' ), 10, 4);
			}
		}
		
		public function register_admin_panel() {
			if ( function_exists( 'add_options_page' ) ) {
				add_options_page('Faux Facebook', 'Faux Facebook', 8, $this->plugin_prefix . '-options', array( &$this, 'admin_options_page' ) );
			} else {
				die( __( 'This version of WordPress is incompatible with this plugin' ) );
			}
		}
		
		public function register_settings() {
			$options = array(
								$this->plugin_prefix . '_api_key',
								$this->plugin_prefix . '_app_secret',
								$this->plugin_prefix . '_template_bundle',
								$this->plugin_prefix . '_force_template_reload',
			);
			
			foreach ( $options as $option_name ) {
				register_setting( $this->plugin_prefix . '-settings', $option_name );
			}
		}
		
		public function admin_options_page() {
		?>
		<div class="wrap">
		<h2><?php echo $this->plugin_name; ?></h2>
		
		<h3>API Setup procedure</h3>
		<ol>
			<li>Visit <a target="_blank" href="http://www.facebook.com/developers/createapp.php?version=new">the Facebook application registration page</a>.
			<li>Enter a descriptive name for your blog in the "Application Name"
				field.  This will be seen by users when they sign up for your
				site.</li>
			<li>Submit</li>
			<li>Copy the displayed API Key and Secret into the form below.</li>
			<li>Edit the settings of your App and go to the "connect" tab and enter <b></code><?php echo $this->get_plugin_url(); ?>/xd_receiver.php</b> as Connect URL</li>
			<li>Recommended: Upload icon images on the app configuration page. These images are seen as the icon in newsfeed stories and when the user is registering with your application</li>
			<li>Save the Facebook settings and Submit the form below.</li>
			<li>You should receive a Template Bundle ID or an error message describing the nature of any problem that might occur.</li>
		</ol>
		
		<h3>API Settings</h3>
		<form method="post" action="options.php">
			<?php settings_fields( $this->plugin_prefix . '-settings' ); ?>
			<table class="form-table">
				<tr valign="top">
				<th scope="row">API Key:</th>
				<td><input type="text" name="<?php echo $this->plugin_prefix; ?>_api_key" value="<?php echo $this->get_api_key(); ?>" /></td>
				</tr>
				
				<tr valign="top">
				<th scope="row">Secret:</th>
				<td><input type="text" name="<?php echo $this->plugin_prefix; ?>_app_secret" value="<?php echo $this->get_app_secret(); ?>" /></td>
				</tr>
				
				<tr valign="top">
				<th scope="row">Template Bundle ID:</th>
				<td><input type="text" name="<?php echo $this->plugin_prefix; ?>_template_bundle" value="<?php echo $this->get_template_bundle(); ?>" /></td>
				</tr>
				
				<tr valign="top">
				<th scope="row">Force Template Bundle Reload:</th>
				<td><input type="checkbox" name="<?php echo $this->plugin_prefix; ?>_force_template_reload" value="1" /></td>
				</tr>
			</table>
			
			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>
		</div>
		
		<h3>Frontend Setup procedure</h3>
		<ol>
			<li>Locate the comments.php file in your theme folder</li>
			<li>Find the section where a user would normally enter their name/email/URL and wrap these input fields in a div-tag with the class "fauxfb_hide_on_login". This should look similar to this:
			<p>
			<pre style="white-space: pre-wrap;white-space: -moz-pre-wrap;white-space: -o-pre-wrap;">
<b>&lt;div class=&quot;fauxfb_hide_on_login&quot;&gt;</b>
&lt;p&gt;&lt;input type=&quot;text&quot; name=&quot;author&quot; id=&quot;author&quot; value=&quot;&lt;?php echo esc_attr($comment_author); ?&gt;&quot; size=&quot;22&quot; tabindex=&quot;1&quot; &lt;?php if ($req) echo &quot;aria-required='true'&quot;; ?&gt; /&gt;
&lt;label for=&quot;author&quot;&gt;&lt;small&gt;Name &lt;?php if ($req) echo &quot;(required)&quot;; ?&gt;&lt;/small&gt;&lt;/label&gt;&lt;/p&gt;

&lt;p&gt;&lt;input type=&quot;text&quot; name=&quot;email&quot; id=&quot;email&quot; value=&quot;&lt;?php echo esc_attr($comment_author_email); ?&gt;&quot; size=&quot;22&quot; tabindex=&quot;2&quot; &lt;?php if ($req) echo &quot;aria-required='true'&quot;; ?&gt; /&gt;
&lt;label for=&quot;email&quot;&gt;&lt;small&gt;Mail (will not be published) &lt;?php if ($req) echo &quot;(required)&quot;; ?&gt;&lt;/small&gt;&lt;/label&gt;&lt;/p&gt;

&lt;p&gt;&lt;input type=&quot;text&quot; name=&quot;url&quot; id=&quot;url&quot; value=&quot;&lt;?php echo esc_attr($comment_author_url); ?&gt;&quot; size=&quot;22&quot; tabindex=&quot;3&quot; /&gt;
&lt;label for=&quot;url&quot;&gt;&lt;small&gt;Website&lt;/small&gt;&lt;/label&gt;&lt;/p&gt;
<b>&lt;/div&gt;</b>
			</pre></p>
			</li>
			<li>Find a place for the Facebook login button and copy the following code where it should appear:
			<p>
			<pre style="white-space: pre-wrap;white-space: -moz-pre-wrap;white-space: -o-pre-wrap;">
&lt;?php print_fb_connect_button(); ?&gt;
			</pre>
			</p>
			</li>
			<li>Add a onClick event to the submit button of the commentform. This should look similar to this:
			<p>
			<pre style="white-space: pre-wrap;white-space: -moz-pre-wrap;white-space: -o-pre-wrap;">
&lt;input name=&quot;submit&quot; type=&quot;submit&quot; id=&quot;submit&quot; tabindex=&quot;5&quot; value=&quot;&lt;?php _e('Submit Comment', 'kubrick'); ?&gt;&quot; <b>onClick=&quot;fauxfb_update_form_values();&quot;</b>/&gt;
			</pre>
			</p>
			</li>
			<li>Save the file and open a post with comments enabled. Once you logout of WordPress a Facebook Connect button should appear.</li>
			
		</ol>
		
		<?php
		}
		
		public static function get_plugin_url() {
			$path = explode( DIRECTORY_SEPARATOR, dirname( __FILE__ ) );
			if ( defined( 'WP_PLUGIN_URL' ) ) {
				$plugin_url = WP_PLUGIN_URL . '/' . array_pop( $path );
			} else {
				$plugin_url = dirname( __FILE__ );
			}
			return apply_filters( self::$plugin_prefix_static . '_plugin_url', $plugin_url );	// filter for usage in custom environments
		}

		public function print_admin_notice() {
			$admin_notice = get_option( $this->plugin_prefix . '-notice' );
			if ( !$admin_notice )
				return;
			else
				delete_option( $this->plugin_prefix . '-notice' );
			
			
			if( isset( $this->note_mapping[ $admin_notice ] ) )
				$notice = __( $this->note_mapping[ $admin_notice ] );
			else
				$notice = __( $admin_notice );
			?>
			<div id="message" class="updated fade"><p><strong><?php echo $notice ?></strong></p></div>
			<?php
		}
		
		/**
		 * Print header scripts via admin_header hook.
		 */
		public function admin_header() {
			if ( $this->plugin_prefix . 'options' == $GLOBALS['plugin_page'] ) {
				// whatever admin stuff might be needed
			}
			// print admin notice in case of notice strings given
			if ( get_option( $this->plugin_prefix . '-notice' ) ) {
					add_action('admin_notices' , array( &$this, 'print_admin_notice' ) );
			}
		}
		
		public function frontend_scripts() {
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'fauxfb_feature_loader', $this->feature_loader );
				wp_enqueue_script( 'fauxfb_js', $this->get_plugin_url() . '/fauxfb.js', array( 'fauxfb_feature_loader' ) );
				wp_enqueue_style( 'fauxfb_style', apply_filters( $this->plugin_prefix . '_style_url', $this->get_plugin_url() . '/fauxfb_style.css' ) );
		}
		
		public function frontend_header() {
			$current_url = esc_url( get_bloginfo('home') . $_SERVER['REQUEST_URI'] );
			$fauxfb_api_key = $this->get_api_key();
			$fauxfb_template_bundle = $this->get_template_bundle();
			$blog_link = '<a href="' .  get_bloginfo('home') . '">' . addslashes( get_bloginfo( 'name' ) ) . '</a>';
			$current_link = '<a href="' . $current_url . '">' . addslashes( get_the_title() ) . '</a>';
			$xd_receiver = apply_filters( $this->plugin_prefix . '_xd_receiver_url', $this->get_plugin_url() . '/xd_receiver.php' );
			echo <<<EOF
			
<script>
var current_url='$current_url';
var fauxfb_api_key='$fauxfb_api_key';
var fauxfb_template_bundle='$fauxfb_template_bundle';
var blog_link='$blog_link';
var current_link='$current_link';
var xd_receiver='$xd_receiver';
</script>

EOF;
		}
		
		public function frontend_footer() {
		
			echo <<<EOF
<script> 
FB_RequireFeatures(["Connect", "CanvasUtil", "XFBML"], function() {
	FB.init( fauxfb_api_key, xd_receiver );
	FB.Facebook.init( fauxfb_api_key, xd_receiver );
	FB.XdComm.Server.init( xd_receiver );
	FB.Connect.ifUserConnected(fauxfb_update_user_details, fauxfb_empty_user_details);
	if (fauxfb_get_coockie('fauxfb_connect') == 'yes') {
		fauxfb_set_coockie('fauxfb_connect', null);
		//FB.Connect.showFeedDialog( fauxfb_template_bundle, {'blog':blog_link, 'post':current_link}, null, null, null, FB.RequireConnect.promptConnect);
	}
} );
</script>

EOF;
		}
		
		public function print_fb_connect_button() {
			$user = wp_get_current_user();
			if( $user->ID )
				return;

			if ( $this->get_fb()->get_loggedin_user() ) {
				echo '<script>fauxfb_update_user_details();</script>';
				return;
			}
			
			echo apply_filters( $this->plugin_prefix . '_facebook_login_button', '<div class="fauxfb_login_button"><fb:login-button onlogin="fauxfb_update_user_details();"></fb:login-button></div>' );
		}
		
		public function get_avatar( $avatar, $id_or_email, $size, $default ) {
			$fbuid = $this->get_fbuid( $id_or_email );
			if ( $fbuid > 0 ) {
				$logo = apply_filters( $this->plugin_prefix . '_avatar_show_fb_logo', 'true' );
				return $this->render_fb_profile_pic( $fbuid, $size, $logo );
			} else {
				return $avatar;
			}
			return $avatar;
		}
		
		function render_fb_profile_pic( $user, $size=32, $logo=true ) {
			return <<<EOF
				<div class="avatar avatar-$size fbconnect-avatar">
					<fb:profile-pic uid="$user" facebook-logo="$logo" width="$size" height="$size"></fb:profile-pic>
				</div>
EOF;
		}
		
		public function is_configured() {
			$error = null;
			if ( $this->verify_app_config( $this->get_api_key(), $this->get_app_secret(), $error, true ) )
				return true;
			return false;
		}
		
		function get_fbuid( $user=false ) {
			if ( !$user ) {
				$user = wp_get_current_user();
				if( $user->ID ) {
					return false;
				}
				return $this->get_fb()->get_loggedin_user();
			} else if ( is_object( $user ) && !empty( $user->comment_author_url ) ) {
				$fbuid = array_pop( explode( "?id=", $user->comment_author_url ) );
				if ( empty( $fbuid ) || intval( $fbuid ) != $fbuid || $fbuid < 1  ) {
					return false;
				}
				return $fbuid;
			} else if ( !is_object( $user ) && preg_match( '/([0-9]+)@facebook\.com/', $user, $match ) ) {
				return $match[1];
			} else {
				return false;
			}
		}
		
		public function verify_app_config( $api_key, $secret, &$error, $anon=false ) {
			$facebook = new Facebook(
										$api_key,
										$secret,
										false,
										'connect.facebook.com'
									);
			$api_client = $facebook->api_client;
			try { 
				if ( $anon ) {
					$api_client->user = 0;
  					$api_client->session_key = null;
  					$success = $api_client;
  				} else {
					$api_client->feed_getRegisteredTemplateBundles();
					$success = true;
				}
			} catch(Exception $e) {
				$success = false;
				$error = $e->getMessage();
			}
			return $success;
		}

		function register_templates( $force=false, &$error ) {
		
			if ( !$force ) {
				$bundle_id = $this->get_template_bundle();
				if ( $bundle_id ) 
					return $bundle_id;
			}
			
			try {
				$bundle_id = $this->get_fb_api()->feed_registerTemplateBundle(
																				$this->fauxfb_one_line_stories,
																				$this->fauxfb_short_story_templates,
																				null,
																				null
																			);
			
				update_option( $this->plugin_prefix . '_template_bundle', "$bundle_id" );
			} catch(Exception $e) {
				$bundle_id = false;
				$error = $e->getMessage();

			}
			return $bundle_id;
		}
		
		function add_namespace( $output ) {
			return $output . ' xmlns:fb="http://www.facebook.com/2008/fbml"';
		}

	}
}

if ( class_exists( "FauxFacebook" ) ) {
	$fauxfb = FauxFacebook::get_instance();
}

if ( is_object( $fauxfb ) ) {
	register_activation_hook( __FILE__, array( &$fauxfb, 'activate' ) );
	register_deactivation_hook( __FILE__, array( &$fauxfb, 'deactivate' ) );
	
	if ( is_admin() ) {
		if ( is_callable( array( &$fauxfb, 'backend_init' ) ) )
			call_user_func( array( &$fauxfb, 'backend_init' ) );
	} else {
		if ( is_callable( array( &$fauxfb, 'frontend_init' ) ) )
			call_user_func( array( &$fauxfb, 'frontend_init' ) );
		if ( is_callable( array( &$fauxfb, 'print_fb_connect_button' ) ) ) {
			function print_fb_connect_button() {
				global $fauxfb;
				call_user_func( array( &$fauxfb, 'print_fb_connect_button' ) );
			}
		}
	}
}


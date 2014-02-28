<?php
/**
 * @package Janrain Capture
 *
 * Methods for inserting UI elements
 *
 */
class JanrainCaptureUi {

	/**
	 * Sets up actions, initializes plugin name.
	 *
	 * @param string $name
	 *	 The plugin name to use as a namespace
	 */
	function __construct() {
		if ( ! is_admin() && JanrainCapture::get_option( JanrainCapture::$name . '_address' ) != false ) {
			add_action( 'wp_head', array( $this, 'head' ) );
		}
		if ( JanrainCapture::get_option( JanrainCapture::$name . '_backplane_enabled' ) > 0 ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'backplane_head' ) );
			add_action( 'wp_footer', array( $this, 'backplane_js' ) );
		}
	}

	/**
	 * Method bound to the wp_head action.
	 */
	function head() {
		$this->widget_js();
		// are we on the edit page?
		if ( strstr( $this->current_page_url(), JanrainCapture::get_option( JanrainCapture::$name . '_widget_edit_page' ) ) === false ) {
			add_action( 'wp_footer', array( $this, 'sign_in_screen' ) );
			$this->sign_in_screen_js();
		} else {
			$this->edit_screen_js();
		}
		// do we feel like sharing?
		if ( JanrainCapture::share_enabled() ) {
			wp_enqueue_style( 'janrain_share', plugin_dir_url( __FILE__ ) . 'stylesheet.css' );
			if ( has_action( 'wp_footer', array( $this, 'share_js' ) == false ) ) {
				add_action( 'wp_footer', array( $this, 'share_js' ) );
			}
		}
	}

	/**
	 * Returns the current page URL
	 *
	 * @return string
	 *	 Page URL
	 */
	function current_page_url() {
		$pageURL = 'http';
		if ( isset( $_SERVER['HTTPS'] ) ) {
			if ( $_SERVER['HTTPS'] == 'on' ) $pageURL .= 's';
		}
		$pageURL .= '://';
		if ( $_SERVER['SERVER_PORT'] != '80' ) {
			$pageURL .= sanitize_text_field( $_SERVER['SERVER_NAME'] ) . ':' . sanitize_text_field( $_SERVER['SERVER_PORT'] ) . sanitize_text_field( $_SERVER['REQUEST_URI'] );
		} else {
			$pageURL .= sanitize_text_field( $_SERVER['SERVER_NAME'] ) . sanitize_text_field( $_SERVER['REQUEST_URI'] );
		}
		return $pageURL;
	}

	/**
	 * Outputs Engage Share widget js settings block to the footer.
	 */
	function share_js() {
		$realm     = JanrainCapture::get_option( JanrainCapture::$name . '_engage_url' );
		$realm     = str_ireplace( 'https://', '', $realm );
		$realm     = str_ireplace( 'http://', '', $realm );
		$realm     = str_ireplace( '.rpxnow.com', '', $realm );
		$providers = JanrainCapture::get_option( JanrainCapture::$name . '_rpx_share_providers' );
		$providers = implode( "', '", array_map( 'esc_js', $providers ) );
		echo
		"<script type='text/javascript'>
		(function() {
			if (typeof window.janrain !== 'object') window.janrain = {};
			if (typeof window.janrain.settings !== 'object') window.janrain.settings = {};
			if (typeof window.janrain.settings.share !== 'object') window.janrain.settings.share = {};
			if (typeof window.janrain.settings.packages !== 'object') janrain.settings.packages = ['share'];
			else janrain.settings.packages.push('share');

			janrain.settings.share.message = '';
			janrain.settings.share.providers = ['$providers'];

		})();
		function setShare(url, title, desc, img, provider) {
			if(img=='') img = null;
			janrain.engage.share.setUrl(url);
			janrain.engage.share.setTitle(title);
			janrain.engage.share.setDescription(desc);
			janrain.engage.share.setImage(img);
			janrain.engage.share.showProvider(provider);
			janrain.engage.share.show();
		}
		</script>
		<style>
		#janrain-share { z-index: 99999 !important; }
		</style>";
	}

	/**
	 * Outputs Capture widget js settings block
	 */
	function widget_js() {

		$folder = get_stylesheet_directory_uri() . '/janrain-capture-screens/';

		$settings['capture.redirectUri']        = admin_url( 'admin-ajax.php' ) . '?action=' . JanrainCapture::$name . '_redirect_uri';
		$settings['capture.appId']              = JanrainCapture::get_option( JanrainCapture::$name . '_app_id' );
		$settings['capture.clientId']           = JanrainCapture::get_option( JanrainCapture::$name . '_client_id' );
		$settings['capture.captureServer']      = JanrainCapture::get_option( JanrainCapture::$name . '_address' );
		$settings['capture.packages']	        = JanrainCapture::get_option( JanrainCapture::$name . '_packages' );
		$janrain_packages                       = implode( "','", array_map( 'esc_js', $settings['capture.packages'] ) );
		$settings['capture.loadJsUrl']          = JanrainCapture::get_option( JanrainCapture::$name . '_load_js' );
		$settings['appUrl']                     = JanrainCapture::get_option( JanrainCapture::$name . '_engage_url' );
		$settings['capture.federate']           = JanrainCapture::get_option( JanrainCapture::$name . '_sso_enabled' );
		$settings['capture.federateServer']     = JanrainCapture::get_option( JanrainCapture::$name . '_sso_address' );
		$settings['capture.federateXdReceiver'] = wpcom_vip_noncdn_uri( dirname( __FILE__ ) ) . '/xdcomm.html';

		$settings['capture.backplane']          = JanrainCapture::get_option( JanrainCapture::$name . '_backplane_enabled' ) ? 'true' : 'false';
		$settings['capture.backplaneBusName']   = JanrainCapture::get_option( JanrainCapture::$name . '_bp_bus_name' );
		$settings['capture.backplaneVersion']   = JanrainCapture::get_option( JanrainCapture::$name . '_bp_version' );
		// prepare backplane server url
		$backplaneBaseUrl = untrailingslashit( JanrainCapture::get_option( JanrainCapture::$name . '_bp_server_base_url' ) );
		// append version string
		$settings['capture.backplaneServerBaseUrl'] = "{$backplaneBaseUrl}/v{$settings['capture.backplaneVersion']}";
		$settings['capture.stylesheets']        = $folder . 'stylesheets/styles.css';
		$settings['capture.mobileStylesheets']  = $folder . 'stylesheets/mobile-styles.css';

		// escape JS before printing
		foreach ( $settings as $key => &$setting ) {
			if ( is_string( $setting ) ) {
				$setting = esc_js( $setting );
			}
		}
		// entity-encoded ampersand breaks when called from the federated site
		$settings['capture.federateLogoutUri']  = str_replace( '&amp;', '&', wp_logout_url() );


		echo
		"<script type='text/javascript'>
		function janrainSignOut(){
			janrain.capture.ui.endCaptureSession();
		}
		(function() {
			if (typeof window.janrain !== 'object') window.janrain = {};
			window.janrain.settings = {};
			window.janrain.settings.capture = {};

			// capture settings
			janrain.settings.capture.redirectUri = '{$settings["capture.redirectUri"]}';
			janrain.settings.capture.appId= '{$settings["capture.appId"]}';
			janrain.settings.capture.clientId = '{$settings["capture.clientId"]}';
			janrain.settings.capture.responseType = 'token';
			janrain.settings.capture.captureServer = '{$settings["capture.captureServer"]}';
			janrain.settings.capture.registerFlow = 'socialRegistration';
			janrain.settings.packages = ['$janrain_packages'];

			janrain.settings.capture.setProfileCookie = true;
			janrain.settings.capture.keepProfileCookieAfterLogout = true;
			janrain.settings.capture.setProfileData = 'true';

			janrain.settings.capture.federateEnableSafari = true;

			// styles
			janrain.settings.capture.stylesheets = ['{$settings["capture.stylesheets"]}'];
			janrain.settings.capture.mobileStylesheets = ['{$settings["capture.mobileStylesheets"]}'];
			janrain.settings.capture.recaptchaPublicKey = '6LeVKb4SAAAAAGv-hg5i6gtiOV4XrLuCDsJOnYoP';\n";
		if ( in_array( 'login', $settings['capture.packages'] ) ) {
			echo
			"// engage settings
			janrain.settings.appUrl = '{$settings['appUrl']}';
			janrain.settings.tokenAction = 'event';\n";
		}

		if ( $settings['capture.backplane'] ) {
			echo
			"//backplane settings
			janrain.settings.capture.backplane = {$settings['capture.backplane']};
			janrain.settings.capture.backplaneBusName = '{$settings['capture.backplaneBusName']}';
			janrain.settings.capture.backplaneVersion = {$settings['capture.backplaneVersion']};\n";
			if ( isset($settings['capture.backplaneServerBaseUrl']) && $settings['capture.backplaneServerBaseUrl'] != '' ) {
				echo "janrain.settings.capture.backplaneServerBaseUrl = 'https://{$settings['capture.backplaneServerBaseUrl']}';";
			}
		}
		if ( $settings['capture.federate'] ) {
			echo
			"// federate settings
			janrain.settings.capture.federate = {$settings['capture.federate']};
			janrain.settings.capture.federateServer = 'https://{$settings['capture.federateServer']}';
			janrain.settings.capture.federateXdReceiver = '{$settings['capture.federateXdReceiver']}';
			janrain.settings.capture.federateLogoutUri = '{$settings['capture.federateLogoutUri']}';\n";
		}

		echo
			"function isReady() { janrain.ready = true; };
			if (document.addEventListener) {
				document.addEventListener('DOMContentLoaded', isReady, false);
			} else {
				window.attachEvent('onload', isReady);
			}
			var e = document.createElement('script');
			e.type = 'text/javascript';
			e.id = 'janrainAuthWidget'
			var url = document.location.protocol === 'https:' ? 'https://' : 'http://';
			url += '{$settings["capture.loadJsUrl"]}';
			e.src = url;
			var s = document.getElementsByTagName('script')[0];
			s.parentNode.insertBefore(e, s);
		})();
		</script>";
	}

	/**
	 * Outputs backplane.js include file
	 */
	function backplane_head() {
		if ( JanrainCapture::get_option( JanrainCapture::$name . '_bp_version', 1.2 ) != 2 ) {
			wp_register_script( 'backplane', 'http://d134l0cdryxgwa.cloudfront.net/backplane.js' );
		} else {
			wp_register_script( 'backplane', 'http://d134l0cdryxgwa.cloudfront.net/backplane2.js' );
		}
		wp_enqueue_script( 'backplane' );
	}

	/**
	 * Outputs backplane setttings js block
	 */
	function backplane_js() {
		$bus = esc_js( JanrainCapture::get_option( JanrainCapture::$name . '_bp_bus_name' ) );
		$ver = esc_js( JanrainCapture::get_option( JanrainCapture::$name . '_bp_version', 1.2 ) );
		$server = esc_js( untrailingslashit( JanrainCapture::get_option( JanrainCapture::$name . '_bp_server_base_url' ) ) );
		if ( $ver == 1.2 ) {
			echo
				"<script type='text/javascript'>
				function bp_ready() {
					// called when bp init completes
					if (Backplane.getChannelID() != undefined) {
						// backplane loaded
						return false;
					}
				}
				Backplane(bp_ready);
				Backplane.init({
					serverBaseURL: 'https://$server/v$ver',
					busName: '$bus'
				});
				</script>\n";
		}
	}

	/**
	 * Outputs the Sign in screen
	 */
	function sign_in_screen() {
		$screen = locate_template( 'janrain-capture-screens/signin.html' );
		if ( $screen ) {
			readfile( $screen );
		}
	}

	/**
	 * Outputs the Sign in screen js
	 */
	function sign_in_screen_js() {
		$screen = locate_template( 'janrain-capture-screens/signin.js' );
		if ( $screen ) {
			echo '<script type="text/javascript">';
			readfile( $screen );
			echo '</script>';
		}
	}

	/**
	 * Outputs the Edit Profile screen
	 */
	function edit_screen() {
		$screen = locate_template( 'janrain-capture-screens/edit-profile.html' );
		if ( $screen ) {
			readfile( $screen );
		}
	}

	/**
	 * Outputs the Edit Profile screen js
	 */
	function edit_screen_js() {
		$screen = locate_template( 'janrain-capture-screens/edit-profile.js' );
		if ( $screen ) {
			echo '<script type="text/javascript">';
			readfile( $screen );
			echo '</script>';
		}
	}
}

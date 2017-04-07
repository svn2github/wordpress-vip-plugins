<?php
/*
Plugin name: Ooyala
Plugin URI: https://www.oomphinc.com/work/ooyala-wordpress-plugin/
Description: Easy embedding of Ooyala Videos from one or more linked Ooyala Accounts.
Author: ooyala
Author URI: https://www.oomphinc.com/
Version: 2.4.0
*/

/*  Copyright 2017  Ooyala

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

/***
 ** Ooyala: The WordPress plugin!
 ***/
class Ooyala {
	// Define and register singleton
	private static $instance = false;
	public static function instance() {
		if( !self::$instance )
			self::$instance = new Ooyala;

		return self::$instance;
	}

	private function __clone() { }

	const shortcode = 'ooyala';
	const settings_key = 'ooyala';
	const capability = 'edit_posts';
	const api_base = 'https://api.ooyala.com';
	const chunk_size = 200000; //bytes per chunk for upload
	const per_page = 50; // #of assets to load per api request
	const polling_delay = 5000; // ms to wait before polling the API for status changes after an upload
	const polling_frequency = 5000; //ms to wait before polling again each time after the first try
	const css_pattern = '([^\r\n,{}]+)(,(?=[^}]*{)|\s*{)'; // validation pattern for CSS rules (JS regex)
	const v4_url_base = '//player.ooyala.com/static/v4/production/latest/';
	const account_settings_slug = 'ooyala_accounts';
	const player_settings_slug = 'ooyala_player';

	// defaults for player display options
	// some of these field names differ from the player API bc WP lowercases shortcode params, they are mapped below
	public $playerDefaults = array(
		'code' => '',
		'player_id' => '',
		'platform' => 'html5-fallback',
		'width' => 500, //if none are provided by the asset's streams
		'height' => 400,
		'enableChannels' => false,
		'wmode' => 'opaque',
		'initialTime' => 0,
		'initialVolume' => 1,
		'auto' => false,
		'loop' => false,
		'autoplay' => false,
		'chromeless' => false,
		'wrapper_class' => 'ooyala-video-wrapper',
		'callback' => 'recieveOoyalaEvent',
		'locale' => '', //equivalent to "User Default" aka providing no locale
		'additional_params_json' => '', //these will come through as the shortcode content, if supplied
		'pulse_params_json' => '',
	);

	// mapping of shortcode param => API param
	protected $paramMapping = array(
		'enable_channels' => 'enableChannels',
		'initial_time' => 'initialTime',
		'initial_volume' => 'initialVolume',
	);

	public $allowed_values = array(
		'wmode' => array( 'window', 'transparent', 'opaque', 'gpu', 'direct' ),
		'platform' => array( 'flash', 'flash-only', 'html5-fallback', 'html5-priority' ),
		'plugins' => array( 'main_html5.min.js', 'bit_wrapper.min.js', 'osmf_flash.min.js' ),
		'ad_plugin' => array( 'freewheel.min.js', 'ad_manager.vast.min.js', 'google_ima.min.js', 'pulse' ),
		'optional_plugins' => array( 'discovery_api.min.js' ),
		'player_version' => array( 'v3', 'v4' ),
	);

	// Ad plugins which don't exist within the normal URL structure
	public $special_ads = array(
		'pulse' => 'http://vp.videoplaza.com/resources/pulse-sdk-html5-bridges/v4/pulse-4.2.7.3.min.js'
	);

	protected $settings_default = array(
		'api_key' => '',
		'api_secret' => '',
		'alt_accounts' => array(),
		'video_width' => '',
		'player_id' => '', //default player ID
		'player_version' => 'v3',
		'tweaks' => '',
		'plugins' => array( 'main_html5.min.js' ),
		'ad_plugin' => '',
		'optional_plugins' => array(),
		'additional_params_raw' => '',
		'additional_params_json' => '',
		'pulse_params_raw' => '', // This field is populated from pulse-params.js in the admin_init
		'pulse_params_json' => '',
		'custom_css' => '',
		'override' => array(),
	);

	/**
	 * Register actions and filters
	 *
	 * @uses add_action, add_filter
	 * @return null
	 */
	private function __construct() {
		// Enqueue essential assets
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );

		// Add the Ooyala media button
		add_action( 'media_buttons', array( $this, 'media_buttons' ), 20 );

		// Emit configuration nag
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		// Create view templates used by the Ooyala media manager
		add_action( 'print_media_templates', array( $this, 'print_media_templates' ) );

		// Register shorcodes
		add_action( 'init', array( $this, 'action_init' ) );

		// Do not texturize our shortcode content!
		add_filter( 'no_texturize_shortcodes', function( $codes ) { $codes[] = Ooyala::shortcode; return $codes; } );

		// Register settings screen
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		// Capture $plugin/player.css endpoint and serve
		add_action( 'parse_request', array( $this, 'player_css' ) );

		// Handle signing requests
		add_action( 'wp_ajax_ooyala_sign_request', array( $this, 'ajax_sign_request' ) );

		// Handle image downloads
		add_action( 'wp_ajax_ooyala_download', array( $this, 'ajax_download' ) );

		// Handle thumbnail lookups
		add_action( 'wp_ajax_ooyala_get_image_id', array( $this, 'ajax_get_image_id' ) );
	}

	/**
	 * Register shortcodes
	 *
	 * @action init
	 */
	function action_init() {
		add_shortcode( self::shortcode, array( $this, 'shortcode' ) );
	}

	/**
	 * Register menu item
	 *
	 * @action admin_menu
	 */
	function admin_menu() {
		add_options_page( esc_html__( 'Ooyala Account Settings', 'ooyala' ), esc_html__( 'Ooyala Account Settings', 'ooyala' ), self::capability, self::account_settings_slug, array( $this, 'account_settings_screen' ) );
		add_options_page( esc_html__( 'Ooyala Video Player Settings', 'ooyala' ) , esc_html__( 'Ooyala Video Player Settings', 'ooyala' ), self::capability, self::player_settings_slug, array( $this, 'player_settings_screen' ) );
	}

	/**
	 * Register settings screen and validation callback
	 *
	 * @action admin_init
	 */
	function admin_init() {
		register_setting( 'ooyala', self::settings_key, array( $this, 'validate_settings' ) );

		// Pull in default Pulse settings template from its JS file
		$this->settings_default['pulse_params_raw'] = file_get_contents( dirname( __FILE__ ) . '/pulse-params.js' );
	}

	/**
	 * Emit account settings screen
	 */
	function account_settings_screen() { ?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ooyala Account Settings', 'ooyala' ); ?></h1>

			<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
			<?php
				settings_fields( 'ooyala' );
				$this->account_settings_fields();
				submit_button();
			?>
			</form>
		</div>
	<?php
	}

	/**
	 * Emit player settings screen
	 */
	function player_settings_screen() { ?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ooyala Video Player Settings', 'ooyala' ); ?></h1>

			<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
			<?php
				settings_fields( 'ooyala' );
				$this->player_settings_fields();
				submit_button();
			?>
			</form>
		</div>
	<?php
	}

	/**
	 * Emit global player customization CSS
	 *
	 * @action parse_request
	 */
	function player_css( $wp ) {
		$css_path = ltrim( parse_url( plugins_url( '/player.css', __FILE__ ), PHP_URL_PATH ), '/' );
		$request_path = $wp->request;
		$permalink = get_option( 'permalink_structure' );

		if( !empty( $permalink ) && strpos( $request_path, $css_path ) === 0 ||
		    empty( $permalink ) && isset( $_GET['ooyala_player_css'] ) ) {
			$settings = $this->get_settings();

			if( !empty( $settings['custom_css'] ) ) {
				http_response_code( 200 );

				header( 'Content-Type: text/css' );
				echo $this->prefix_css( $settings['custom_css'], '.ooyala-player' );
			}
			else {
				http_response_code( 404 );
			}

			exit(0);
		}
	}

	/**
	 * Emit account settings fields
	 */
	function account_settings_fields() {
		$option = $this->get_settings();
		?>
		<table class="form-table ooyala-settings" id="ooyala">
			<tbody>
				<tr>
					<td><input type="hidden" name="ooyala[account_settings_page]" value="true"></td>
				</tr>
				<tr>
					<th scope="row"><label for="ooyala-apikey"><?php esc_html_e( "Default API Key", 'ooyala' ); ?></label></th>
					<td scope="row"><input type="text" name="ooyala[api_key]" class="widefat" id="ooyala-apikey" value="<?php echo esc_attr( $option['api_key'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="ooyala-apisecret"><?php esc_html_e( "Default API Secret", 'ooyala' ); ?></label></th>
					<td scope="row"><input type="text" name="ooyala[api_secret]" class="widefat" id="ooyala-apisecret" value="<?php echo esc_attr( $option['api_secret'] ); ?>" /></td>
				</tr>
				<tr>
					<td colspan="2">
						<p class="description"><?php esc_html_e( "You can obtain these values in the Ooyala Backlot administration area under 'Account > Settings'", 'ooyala' ); ?></p>
					</td>
				</tr>
				<tr>
					<td><?php submit_button(); ?></td>
				</tr>
			</tbody>
			<tbody id="alt-accounts-heading">
				<tr>
					<th>
						<h2><?php esc_html_e( 'Alternate Accounts', 'ooyala' ) ?></h2>
					</th>
					<td>
						<button class="button" type="button" id="add-account"><?php esc_html_e( 'Add Account', 'ooyala' ) ?></button>
					</td>
				</tr>
			</tbody>
			<?php
			$i = 0;
			foreach ( $option['alt_accounts'] as $nickname => $account ):
				$i++;
				$key = $account['api_key'];
				$secret = $account['api_secret'];
			?>
				<tbody class="alt-accounts-wrap">
					<tr>
						<th><label for="ooyala_alt_nickname_<?php echo (int) $i; ?>"><?php esc_html_e( 'Nickname', 'ooyala' ); ?></label></th>
						<td><input required id="ooyala_alt_nickname_<?php echo (int) $i; ?>" class="widefat" type="text" name="ooyala[alt_nickname][]" value="<?php echo esc_attr( $nickname ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="ooyala_alt_api_key_<?php echo (int) $i; ?>"><?php esc_html_e( 'API Key', 'ooyala' ); ?></label></th>
						<td><input id="ooyala_alt_api_key_<?php echo (int) $i; ?>" class="widefat" type="text" name="ooyala[alt_api_key][]" value="<?php echo esc_attr( $key ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="ooyala_alt_api_secret_<?php echo (int) $i; ?>"><?php esc_html_e( 'API Secret', 'ooyala' ); ?></label></th>
						<td><input id="ooyala_alt_api_secret_<?php echo (int) $i; ?>" class="widefat" type="text" name="ooyala[alt_api_secret][]"  value="<?php echo esc_attr( $secret ); ?>" /></td>
					</tr>
					<tr>
						<td><button type="button" class="button delete-account"><?php esc_html_e( 'Delete Account', 'ooyala' ) ?></button></td>
					</tr>

				</tbody>
			<?php
			endforeach;
			?>
			<tbody>
				<tr>
					<td>
						<script type="text/html" id="ooyala_add_account_template">
							<tbody class="alt-accounts-wrap">
								<tr>
									<th><label for="ooyala_add_nickname_%d"><?php esc_html_e( 'Nickname', 'ooyala' ); ?></label></th>
									<td><input required id="ooyala_add_nickname_%d" class="widefat" type="text" name="ooyala[alt_nickname][]" placeholder="<?php esc_attr_e( 'Enter a nickname for this account', 'ooyala' ); ?>" /></td>
								</tr>
								<tr>
									<th><label for="ooyala_add_api_key_%d"><?php esc_html_e( 'API Key', 'ooyala' ); ?></label></th>
									<td><input id="ooyala_add_api_key_%d" class="widefat" type="text" name="ooyala[alt_api_key][]" /></td>
								</tr>
								<tr>
									<th><label for="ooyala_add_api_secret_%d"><?php esc_html_e( 'API Secret', 'ooyala' ); ?></label></th>
									<td><input id="ooyala_add_api_secret_%d" class="widefat" type="text" name="ooyala[alt_api_secret][]" /></td>
								</tr>
								<tr>
									<td><button type="button" class="button delete-account"><?php esc_html_e( 'Delete Account', 'ooyala' ) ?></button></td>
								</tr>

							</tbody>
						</script>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Emit video player settings fields
	 */
	function player_settings_fields() {
		$option = $this->get_settings();
		$is_v3_player = $option['player_version'] === 'v3';

		?>
		<table class="form-table ooyala-settings <?php echo $is_v3_player ? 'ooyala-v3-settings' : 'ooyala-v4-settings'; ?>" id="ooyala">
			<tbody>
				<tr>
					<th scope="row"><label for="ooyala-playerid"><?php esc_html_e( 'Default Player ID', 'ooyala' ); ?></label></th>
					<td scope="row"><input type="text" name="ooyala[player_id]" class="widefat" id="ooyala-playerid" value="<?php echo esc_attr( $option['player_id'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="ooyala-videowidth"><?php esc_html_e( 'Default Video Width', 'ooyala' ); ?></label></th>
					<td scope="row"><input type="number" min="10" max="2000" name="ooyala[video_width]" id="ooyala-videowidth" value="<?php echo esc_attr( $option['video_width'] ); ?>" />px</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Player Version', 'ooyala' ); ?></th>
					<td scope="row">
						<label>
							<input type="radio" class="ooyala-player-version" name="ooyala[player_version]" id="ooyala-player-version-v3" value="v3" <?php checked( $is_v3_player ); ?> />
							<?php esc_html_e( 'Version 3', 'ooyala' ); ?>
						</label>
						<br/>
						<label>
							<input type="radio" class="ooyala-player-version" name="ooyala[player_version]" id="ooyala-player-version-v4" value="v4" <?php checked( !$is_v3_player ); ?> />
							<?php esc_html_e( 'Version 4', 'ooyala' ); ?>
						</label>
					</td>
				</tr>
				<tr class="ooyala-v3-setting">
					<th scope="row"><?php esc_html_e( 'Tweaks (V3 Player)', 'ooyala' ); ?></th>
					<td scope="row">
						<label>
							<input type="radio" name="ooyala[tweaks]" id="ooyala-tweaks-none" value="" <?php checked( !$option['tweaks'] ); ?> />
							<?php esc_html_e( 'None', 'ooyala' ); ?>
						</label>
						<br/>
						<label>
							<input type="radio" name="ooyala[tweaks]" id="ooyala-tweaks-force-mp4" value="force-mp4" <?php checked( $option['tweaks'], 'force-mp4' ); ?> />
							<?php esc_html_e( 'Force MP4', 'ooyala' ); ?>
						</label>
						<br/>
						<label>
							<input type="radio" name="ooyala[tweaks]" id="ooyala-tweaks-android-enable-hls" value="android-enable-hls" <?php checked( $option['tweaks'], 'android-enable-hls' ); ?> />
							<?php esc_html_e( 'Enable HLS (Android)', 'ooyala' ); ?>
						</label>
					</td>
				</tr>

				<tr class="ooyala-v4-setting">
					<th scope="row"><?php esc_html_e( 'Plugins (V4 Player)', 'ooyala' ); ?></th>

					<td scope="row">

						<strong><?php esc_html_e( 'Streaming Plugins', 'ooyala' ); ?></strong>
						<br/>

						<p class="description"><?php esc_html_e( 'You must choose at least one streaming plugin.', 'ooyala' ); ?></p>

						<br/>

						<label>
							<input type="checkbox" name="ooyala[plugins][]" value="main_html5.min.js" <?php checked( in_array( 'main_html5.min.js', $option['plugins'] ) ); ?> />
							<?php esc_html_e( 'Default video plugin for HLS and MP4 video streams', 'ooyala' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Allows for support of HLS and MP4 video streams, and allows for VAST, VPAID and Freewheel ad playback.', 'ooyala' ); ?>
						</p>

						<br/>

						<label>
							<input type="checkbox" name="ooyala[plugins][]" value="bit_wrapper.min.js" <?php checked( in_array( 'bit_wrapper.min.js', $option['plugins'] ) ); ?> />
							<?php esc_html_e( 'Bitmovin Video Plugin for DASH and HLS', 'ooyala' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Allows for support of HLS, DASH and MP4 video streams.', 'ooyala' ); ?>
						</p>

						<br/>

						<label>
							<input type="checkbox" name="ooyala[plugins][]" value="osmf_flash.min.js" <?php checked( in_array( 'osmf_flash.min.js', $option['plugins'] ) ); ?> />
							<?php esc_html_e( 'OSMF Flash Video Plugin for HDS', 'ooyala' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Allows for support of HDS video streams in an Flash player.', 'ooyala' ); ?>
						</p>

						<br/>
						<br/>

						<strong><?php esc_html_e( 'Ad Plugins', 'ooyala' ); ?></strong>
						<br/>

						<p class="description"><?php esc_html_e( 'You may optionally choose one ad plugin.', 'ooyala' ); ?></p>

						<br/>

						<label>
							<input type="radio" name="ooyala[ad_plugin]" value="" <?php checked( empty( $option['ad_plugin'] ) ); ?> />
							<?php esc_html_e( 'None', 'ooyala' ); ?>
						</label>

						<br/>

						<label>
							<input type="radio" name="ooyala[ad_plugin]" value="freewheel.min.js" <?php checked( $option['ad_plugin'], 'freewheel.min.js' ); ?> />
							<?php esc_html_e( 'Freewheel ad support', 'ooyala' ); ?>
						</label>

						<br/>

						<label>
							<input type="radio" name="ooyala[ad_plugin]" value="ad_manager_vast.min.js" <?php checked( $option['ad_plugin'], 'ad_manager_vast.min.js' ); ?> />
							<?php esc_html_e( 'VAST and VPAID ad support', 'ooyala' ); ?>
						</label>

						<br/>

						<label>
							<input type="radio" name="ooyala[ad_plugin]" value="google_ima.min.js" <?php checked( $option['ad_plugin'], 'google_ima.min.js' ); ?> />
							<?php esc_html_e( 'Google IMA ad support', 'ooyala' ); ?>
						</label>

						<br/>

						<label>
							<input type="radio" name="ooyala[ad_plugin]" value="pulse" <?php checked( $option['ad_plugin'], 'pulse' ); ?> />
							<?php esc_html_e( 'Pulse ad network support', 'ooyala' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Enables support for advertisements through the Ooyala Pulse ad network.', 'ooyala' ); ?>
						</p>

						<div class="ad_plugin-pulse-options" <?php if( $option['ad_plugin'] !== 'pulse' ) echo 'style="display: none"'; ?>>
							<h4><?php esc_html_e( 'Additional options for the Pulse plugin', 'ooyala' ); ?></h4>

							<textarea id="ooyala-pulse-params" class="ooyala-raw-json ooyala-pulse-params-raw widefat" name="ooyala[pulse_params_raw]" placeholder="<?php esc_attr_e( 'Key/value pairs in JSON or JavaScript object literal notation', 'ooyala' ); ?>" rows="8"><?php echo esc_textarea( $option['pulse_params_raw'] ); ?></textarea>
							<input type="hidden" class="ooyala-json" id="ooyala-pulse-params-json" name="ooyala[pulse_params_json]" value="<?php echo esc_attr( $option['pulse_params_json'] ); ?>" />
							<p class="description"><?php echo wp_kses_post( __( 'Review the <a href="http://help.ooyala.com/video-platform/concepts/pbv4_ads_dev_pulse.html">Pulse integration reference</a> for details on acceptable parameters.', 'ooyala' ) ); ?></p>

							<br/>

							<label><?php esc_html_e( 'Override plugin URL', 'ooyala' ); ?></label>
							<input type="text" class="widefat" name="ooyala[override][pulse]" value="<?php echo esc_attr( !empty( $option['override']['pulse'] ) ? $option['override']['pulse'] : '' ); ?>" placeholder="<?php echo esc_attr( $this->special_ads['pulse'] ); ?>" />
						</div>

						<br/>
						<br/>

						<strong><?php esc_html_e( 'Optional Plugins', 'ooyala' ); ?></strong>
						<br/>

						<p class="description"><?php esc_html_e( 'Plugins that provide additional functionality to your player.', 'ooyala' ); ?></p>

						<br/>

						<label>
							<input type="checkbox" name="ooyala[optional_plugins][]" value="discovery_api.min.js" <?php checked( in_array( 'discovery_api.min.js', $option['optional_plugins'] ) ); ?> />
							<?php esc_html_e( 'Discovery', 'ooyala' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Keep users engaged by directing them to related and relevant content on your site.', 'ooyala' ); ?>
						</p>
					</td>
				</tr>

				<tr class="ooyala-setting ooyala-v4-setting">
					<th scope="row"><label for="ooyala-additional-params"><?php esc_html_e( 'Additional JSON Skin', 'ooyala' ); ?></label></th>

					<td>
						<textarea id="ooyala-additional-params" class="ooyala-raw-json ooyala-additional-params-raw widefat" name="ooyala[additional_params_raw]" placeholder="<?php esc_attr_e( 'Key/value pairs in JSON or JavaScript object literal notation', 'ooyala' ); ?>" rows="8"><?php echo esc_textarea( $option['additional_params_raw'] ); ?></textarea>
						<input type="hidden" class="ooyala-json" id="ooyala-additional-params-json" name="ooyala[additional_params_json]" value="<?php echo esc_attr( $option['additional_params_json'] ); ?>" />
						<p class="description"><?php echo wp_kses_post( __( 'Review the <a href="http://support.ooyala.com/developers/documentation/reference/pbv4_skin_schema_docs.html">JSON skinning reference</a> for details on acceptable parameters.', 'ooyala' ) ); ?></p>
					</td>
				</tr>

				<tr class="ooyala-setting ooyala-v4-setting">
					<th scope="row"><label for="custom-css"><?php esc_html_e( 'Custom CSS Skin', 'ooyala' ); ?></label></th>

					<td>
						<textarea id="custom-css" class="ooyala-custom-css widefat" name="ooyala[custom_css]" placeholder="<?php echo esc_attr_e( 'Additional CSS rules to be applied to all players', 'ooyala' ); ?>" rows="8"><?php echo esc_html( $option['custom_css'] ); ?></textarea>

						<p class="description"><?php echo wp_kses_post( __( 'Review the <a href="http://support.ooyala.com/developers/documentation/concepts/pbv4_css.html">CSS skinning reference</a> for details on how to style your player using CSS.', 'ooyala' ) ); ?></p>
					</td>
				</tr>
			</tbody>

		</table>
	<?php
	}

	/**
	 * Prefix CSS rules with a selector. Adds a prefix selector before each selector.
	 */
	function prefix_css( $css, $prefix ) {
		return preg_replace_callback( '/([^{]+)(\{[^}]*}\s*)/', function( $matches ) use ( $prefix ) {
			// Add the prefix to each selector in a comma-separated group.
			// This WILL break if a selector has an embedded comma, like [href="foo,bar"]!
			return implode( ',', array_map( function( $selector ) use ( $prefix ) {
				return $prefix . ' ' . $selector;
			}, explode( ',', $matches[1] ) ) ) . $matches[2];
		}, $css );
	}

	/**
	 * Validate option value
	 */
	function validate_settings( $settings ) {
		$validated = get_option( self::settings_key, $this->settings_default );

		foreach( $this->settings_default as $key => $default ) {
			if( isset( $settings[$key] ) ) {
				$setting = $settings[$key];

				// For array values, intersect with the list of possible values
				if( is_array( $default ) && is_array( $setting ) ) {
					if( isset( $this->allowed_values[$key] ) && is_array( $this->allowed_values[$key] ) ) {
						$validated[$key] = array_intersect( $this->allowed_values[$key], $setting );
					}
					else {
						//Don't flatten alt_accounts into a string – it's sanitized below
						if ( $key === 'alt_accounts' ) {
							$validated[$key] = $setting;
						} else {
							// Or just accept arbitrary values, but sanitize them all, implicitly
							// accepting only one level of depth
							$validated[$key] = array_map( 'sanitize_text_field', $setting );
						}
					}
				}

				// For string values, check against $allowed_values for it, or just do plain ol' sanitize
				else if( is_string( $default ) && is_string( $setting ) ) {
					if( isset( $this->allowed_values[$key] ) && is_array( $this->allowed_values[$key] ) && in_array( $setting, $this->allowed_values[$key] ) ) {
						$validated[$key] = $setting;
					}
					// For JSON raw, formatted, and CSS fields, use verbatim. CSS is only output
					// to the client in a text/css response so can be safely arbitrary.
					// The _raw forms of JSON fields are saved verbatim and only exposed in the settings screen.
					// The _json forms of JSON fields are validated in the following block.
					else if( substr( $key, -4 ) === '_raw' || substr( $key, -5 ) === '_json' || substr( $key, -4 ) === '_css' ) {
						$validated[$key] = $setting;
					}
					// Otherwise sanitize as plain text
					else {
						$validated[$key] = sanitize_text_field( $setting );
					}
				}
			}
		}

		// Coerce alternate account settings into one array on save and sanitize
		if ( isset( $settings['account_settings_page'] ) ) {
			if (
				isset( $settings['alt_api_key'], $settings['alt_api_secret'], $settings['alt_nickname'] )
				&& is_array( $settings['alt_api_key'] )
				&& is_array( $settings['alt_api_secret'] )
				&& is_array( $settings['alt_nickname'] )
			) {
				$validated['alt_accounts'] = array();
				foreach ( $settings['alt_nickname'] as $i => $nickname ) {
					$nickname = sanitize_text_field( $nickname );
					if ( !empty( $nickname ) ) {
						$validated['alt_accounts'][$nickname] = array(
							'api_key' =>  sanitize_text_field( $settings['alt_api_key'][$i] ),
							'api_secret' => sanitize_text_field( $settings['alt_api_secret'][$i] ),
						);
					}
				}
			} elseif (
				empty( $settings['alt_api_key'] )
				&& empty( $settings['alt_api_secret'] )
				&& empty( $settings['alt_nickname'] )
			) {
				//Delete accounts only if all three fields are empty
				$validated['alt_accounts'] = array();
			}
		}

		// Check that additional params is valid JSON. Since it's manipulated by JavaScript
		// in a hidden element, it should be strictly well-formed JSON. Simply throw out
		// the value if it can't pass our simple test.
		foreach( array( 'additional_params', 'pulse_params' ) as $json_field ) {
			$raw_field = $json_field . '_raw';
			$json_field = $json_field . '_json';

			if( !empty( $validated[$json_field] ) ) {
				$decoded = json_decode( $validated[$json_field], true );

				if( $decoded ) {
					$validated[$json_field] = json_encode( $decoded );
				}
				else {
					$validated[$json_field] = '';
				}
			}
		}

		// TODO: How do we check without a lot of extra code that Custom CSS is valid CSS?
		return $validated;
	}

	/**
	 * Get the user's saved settings for this plugin, filled in with default values.
	 * @return array settings or defaults
	 */
	function get_settings() {
		return $this->validate_settings( get_option( self::settings_key, $this->settings_default ) );
	}

	/**
	 * Look up an attachment ID based on a given Ooyala thumbnail URL
	 *
	 * @param string $url
	 * @return int
	 */
	function get_attachment_id( $url ) {
		// Though this is a query on postmeta, it's only invoked by administrative
		// users on a relatively infrequent basis
		$query = new WP_Query( array(
			'post_type' => 'attachment',
			'meta_query' => array( array(
				'key' => 'ooyala_source',
				'value' => $url
			) ),
			'post_status' => 'any',
			'fields' => 'ids',
			'posts_per_page' => 1
		) );

		return $query->posts ? $query->posts[0] : 0;
	}

	/**
	 * Process signing request
	 *
	 * @action wp_ajax_ooyala_sign_request
	 */
	function ajax_sign_request() {
		$settings = $this->get_settings();

		if( !$this->configured() ) {
			$this->ajax_error( __( "Plugin not configured", 'ooyala' ) );
		}

		// check nonce
		$this->ajax_check();

		$request = json_decode( file_get_contents( 'php://input' ), true );

		if( !isset( $request ) || !is_array( $request ) ) {
			$this->ajax_error( __( "Invalid request", 'ooyala' ) );
		}

		$request = wp_parse_args( $request, array(
			'account' => '',
			'method' => '',
			'path' => '',
			'body' => '',
			'params' => array()
		) );

		// Make damn sure $request['params'] is an array even if it
		// was fed in as the wrong type
		if( !is_array( $request['params'] ) ) {
			$request['params'] = array();
		}

		// If an account nickname was sent with the request,
		// use its credentials
		if (
			!empty( $request['account'] )
			&& !empty( $settings['alt_accounts'][$request['account']] )
		) {
			$api_key = $settings['alt_accounts'][$request['account']]['api_key'];
			$api_secret = $settings['alt_accounts'][$request['account']]['api_secret'];
		} else {
			//Otherwise use credentials from the default account
			$api_key = $settings['api_key'];
			$api_secret = $settings['api_secret'];
		}

		$request['params']['api_key'] = $api_key;
		$request['params']['expires'] = time() + 300;

		$to_sign = $api_secret . $request['method'] . $request['path'];

		$param_sorted = array_keys( $request['params'] );
		sort( $param_sorted );

		foreach( $param_sorted as $key ) {
			$to_sign .= $key . '=' . $request['params'][$key];
		}

		$to_sign .= $request['body'];
		// Sign the payload in $to_sign
		$hash = hash( "sha256", $to_sign, true );

		$base64_hash = base64_encode( $hash );
		$request['params']['signature'] = rtrim( substr( $base64_hash, 0, 43 ), '=' );

		$url = self::api_base . $request['path'] . '?' . http_build_query( $request['params'] );

		$this->ajax_success( null, array(
			'url' => $url
		) );
	}

	/**
	 * Process download, return image ID to use as featured image.
	 *
	 * @action wp_ajax_ooyala_download
	 */
	function ajax_download() {
		if( !$this->configured() ) {
			$this->ajax_error( __( 'Plugin not configured', 'ooyala' ) );
		}

		// check nonce
		$this->ajax_check();

		$post_id = (int) filter_input( INPUT_POST, 'post_id', FILTER_VALIDATE_INT );
		$url = filter_input( INPUT_POST, 'image_url', FILTER_SANITIZE_URL );

		// sanity check inputs
		if( empty( $url ) ) {
			$this->ajax_error( __( 'No image URL given', 'ooyala' ) );
		}

		// First check that we haven't already downloaded this image.
		$existing_id = $this->get_attachment_id( $url );

		if( $existing_id ) {
			$this->ajax_success( __( 'Attachment already exists', 'ooyala' ), array( 'id' => $existing_id ) );
		}

		// The following code is copied and modified from media_sideload_image to
		// handle downloading of thumbnail assets from Ooyala.
		$image_name = basename( $url );

		// Assume JPEG by default for Ooyala-downloaded thumbnails
		if( !preg_match( $image_name, '/\.(jpe?g|png|gif)$/i', $image_name ) ) {
			$image_name .= '.jpg';
		}

		$file_array = array(
			'name' => $image_name
		);

		// Download file to temp location.
		$file_array['tmp_name'] = download_url( $url );

		// If error storing temporarily, return the error.
		if( is_wp_error( $file_array['tmp_name'] ) ) {
			$this->ajax_error( sprintf( __( 'Failed to download image at %s', 'ooyala' ), $url ) );
		}

		// Do the validation and storage stuff.
		$id = media_handle_sideload( $file_array, $post_id );

		// If error storing permanently, unlink.
		if( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );

			$this->ajax_error( __( 'Failed to store downloaded image', 'ooyala' ) );
		}

		update_post_meta( $id, 'ooyala_source', $url );

		$this->ajax_success( __( 'Successfully downloaded image', 'ooyala' ), array( 'id' => $id ) );
	}

	/**
	 * Look up an attachment ID from a preview URL
	 *
	 * @action wp_ajax_ooyala_get_image_id
	 */
	function ajax_get_image_id() {
		if( !$this->configured() ) {
			$this->ajax_error( __( 'Plugin not configured', 'ooyala' ) );
		}

		// check nonce
		$this->ajax_check();

		$post_id = (int) filter_input( INPUT_POST, 'post_id', FILTER_VALIDATE_INT );
		$url = filter_input( INPUT_POST, 'image_url', FILTER_SANITIZE_URL );

		// sanity check inputs
		if( empty( $url ) ) {
			$this->ajax_error( __( 'No image URL given', 'ooyala' ) );
		}

		// First check that we haven't already downloaded this image.
		$existing_id = $this->get_attachment_id( $url );

		$this->ajax_success( __( 'Found attachment ID', 'ooyala' ), array( 'id' => $existing_id ) );
	}

	/**
	 * Emit an error result via AJAX
	 */
	function ajax_error( $message = null, $data = array() ) {
		if( !is_null( $message ) ) {
			$data['message'] = $message;
		}

		wp_send_json_error( $data );
	}

	/**
	 * Emit a success message via AJAX
	 */
	function ajax_success( $message = null, $data = array() ) {
		if( !is_null( $message ) ) {
			$data['message'] = $message;
		}

		wp_send_json_success( $data );
	}

	/**
	 * Check against a nonce to limit exposure, all AJAX handlers must use this
	 */
	function ajax_check() {
		if( !isset( $_GET['nonce'] ) || !wp_verify_nonce( $_GET['nonce'], 'ooyala' ) ) {
			$this->ajax_error( __( 'Invalid nonce', 'ooyala' ) );
		}
	}

	/**
	 * Include all of the templates used by Backbone views
	 */
	function print_media_templates() {
		include( __DIR__ . '/ooyala-templates.php' );
	}

	/**
	 * Enqueue all assets used for admin view. Localize scripts.
	 */
	function admin_enqueue() {
		global $pagenow;

		wp_register_style( 'ooyala', plugins_url( '/ooyala.css', __FILE__ ) );

		// Use stylesheet on options and edit post pages
		if (
			$pagenow === 'options-general.php'
			&& isset( $_GET['page'] )
			&& ( basename( $_GET['page'] ) === self::account_settings_slug || basename( $_GET['page'] ) === self::player_settings_slug )
		) {
			wp_enqueue_style( 'ooyala' );
			wp_enqueue_script( 'ooyala-settings', plugins_url( '/js/ooyala-settings.js', __FILE__ ), array( 'jquery' ), 1, true );
			wp_localize_script( 'ooyala-settings', 'ooyala', array( 'cssPattern' => self::css_pattern ) );
			return;
		}

		// Only operate on edit post pages
		if( $pagenow != 'post.php' && $pagenow != 'post-new.php' )
			return;

		// Ensure all the files required by the media manager are present
		wp_enqueue_style( 'ooyala' );
		wp_enqueue_media();

		wp_enqueue_script( 'spin-js', plugins_url( '/js/spin.js', __FILE__ ), array(), 1, true );
		wp_enqueue_script( 'ooyala-views', plugins_url( '/js/ooyala-views.js', __FILE__ ), array( 'spin-js', 'jquery-ui-autocomplete' ), 1, true );
		wp_enqueue_script( 'ooyala-models', plugins_url( '/js/ooyala-models.js', __FILE__ ), array(), 1, true );
		// load up our special edition of plupload which is catered to ooyala's API needs
		// the API requires unique URLs per chunk which cannot be fulfilled by the current version of plupload as of this writing
		wp_enqueue_script( 'ooyala-plupload', plugins_url( '/js/plupload.js', __FILE__ ), array(), 1, true );
		wp_enqueue_script( 'ooyala', plugins_url( '/js/ooyala.js', __FILE__ ), array( 'ooyala-views', 'ooyala-models', 'ooyala-plupload' ), 1, true );

		// Nonce 'n' localize!
		wp_localize_script( 'ooyala-views', 'ooyala',
			array(
				'model' => array(), // Backbone models
				'view' => array(), // Backbone views
				'settings' => array_intersect_key( $this->get_settings(), array_flip( array(
					'player_version',
					'tweaks',
					'ad_plugin',
					'plugins',
					'player_id',
					'alt_accounts',
					'api_key',
				) ) ),
				'sign' => admin_url( 'admin-ajax.php?action=ooyala_sign_request&nonce=' . wp_create_nonce( 'ooyala' ) ),
				'download' => admin_url( 'admin-ajax.php?action=ooyala_download&nonce=' . wp_create_nonce( 'ooyala' ) ),
				'imageId' => admin_url( 'admin-ajax.php?action=ooyala_get_image_id&nonce=' . wp_create_nonce( 'ooyala' ) ),

				// display-option-to-shortcode-param mapping
				'paramMapping' => array_flip( $this->paramMapping ),
				'playerDefaults' => $this->playerDefaults,
				'tag' => self::shortcode,
				'chunk_size' => self::chunk_size,
				'perPage' => self::per_page,
				'pollingDelay' => self::polling_delay,
				'pollingFrequency' => self::polling_frequency,
				'cssPattern' => self::css_pattern,
				'text' => array(
					// Ooyala search field placeholder
					'searchPlaceholder' => __( "Search...", 'ooyala' ),
					// Search button text
					'search' => __( "Search", 'ooyala' ),
					// This will be used as the default button text
					'title'  => __( "Ooyala", 'ooyala' ),
					// this warning is shown when a user tries to navigate while an upload is in progress
					'uploadWarning' => __( 'WARNING: You have an upload in progress.', 'ooyala' ),
					// alert for success or failure upon upload
					'successMsg' => __( 'Your asset "%s" has finished processing and is now ready to be embedded.', 'ooyala' ),
					'errorMsg' => __( 'Your asset "%s" encountered an error during processing.', 'ooyala' ),

					// Results
					'oneResult' => __( "%d result", 'ooyala' ),
					'results' => __( "%d results", 'ooyala' ),
					'noResults' => __( "Sorry, we found zero results matching your search.", 'ooyala' ),
					'recentlyViewed' => __( "Recently Viewed", 'ooyala' ),
					'refresh' => __( "Refresh search results", 'ooyala' ),

					// Button for inserting the embed code
					'insertAsset' => __( "Embed Asset", 'ooyala' ),
				)
			)
		);
	}

	/**
	 * Add "Ooyala..." button to edit screen
	 *
	 * @action media_buttons
	 */
	function media_buttons( $editor_id = 'content' ) {
		$classes = 'button ooyala-activate add_media';

		if( !$this->configured() ) {
			$classes .= ' disabled';
		} ?>
		<button id="insert-ooyala-button" class="<?php echo esc_attr( $classes ); ?>"
			data-editor="<?php echo esc_attr( $editor_id ); ?>"
			title="<?php if( $this->configured() ) esc_attr_e( "Embed assets from your Ooyala account.", 'ooyala' ); else esc_attr_e( "This button is disabled because your Ooyala API credentials are not configured in Ooyala Settings.", 'ooyala' ); ?>">
			<span class="ooyala-buttons-icon"></span><?php esc_html_e( "Add Ooyala Video", 'ooyala' ); ?></button>
	<?php
	}

	/**
	 * Is this module configured?
	 *
	 * @return bool
	 */
	function configured() {
		$settings = $this->get_settings();

		return !empty( $settings['api_key'] ) && !empty( $settings['api_secret'] );
	}

	/**
	 * Notify the user if the API credentials have not been entered
	 *
	 * @action admin_notices
	 */
	function admin_notices() {
		global $pagenow;

		$page = self::account_settings_slug;

		$url = admin_url( 'options-general.php?page=' . $page );

		if( $this->configured() || !current_user_can( 'manage_options' ) ||
		  ( $pagenow === 'options-general.php' && isset( $_GET['page'] ) && $_GET['page'] === $page ) ) {
			return;
		}
		?>
		<div class="update-nag">
			<?php echo wp_kses_post( sprintf( __( 'Your Ooyala API credentials are not configured in <a href="%s">Ooyala Account Settings</a>.', 'ooyala' ), esc_url( $url ) ) ); ?>
		</div>
		<?php
	}

	/**
	 * Determine if the supplied shortcode param is the default for the player
	 * @param  string  $field shortcode field name
	 * @param  mixed  $value
	 * @return boolean   determination
	 */
	function is_default( $field, $value ) {
		return isset( $this->playerDefaults[$field] ) && $this->playerDefaults[$field] == $value;
	}

	/**
	 * Render the Ooyala shortcode
	 */
	function shortcode( $atts, $content = null ) {
		static $num, $v4_scripts;

		// What to do if not even provided the right type by the shortcode processor?
		if( !is_array( $atts ) ) {
			return;
		}

		// do not display markup in feeds
		if( is_feed() ) {
			return;
		}
		// handle the 'legacy' shortcode format: [ooyala code12345]
		if( empty( $atts['code'] ) ) {
			if( isset( $atts[0] ) ) {
				$atts['code'] = $atts[0];
			} else {
				// we need a code!
				return;
			}
		}
		$num++;
		$settings = $this->get_settings();
		// fill in defaults saved in user settings
		if( empty( $atts['player_id'] ) && !empty( $settings['player_id'] ) ) {
			$atts['player_id'] = $settings['player_id'];
		}
		// set a width from some defaults
		if( empty( $atts['width'] ) ) {
			if( !empty( $settings['video_width'] ) ) {
				$atts['width'] = $settings['video_width'];
			} elseif( !empty( $GLOBALS['content_width'] ) ) {
				$atts['width'] = $GLOBALS['content_width'];
			}
		}

		// map shortcode attributes to their internal names
		foreach( $this->paramMapping as $param => $real ) {
			if( isset( $atts[$param] ) ) {
				$atts[$real] = $atts[$param];
				unset( $atts[$param] );
			}
		}

		// fill in remaining player defaults
		$atts = shortcode_atts( apply_filters( 'ooyala_default_query_args', $this->playerDefaults ), $atts );

		// coerce string true and false to their respective boolean counterparts
		$atts = array_map( function( $value ) {
			$lower = strtolower( $value );
			$map = array( 'true' => true, 'false' => false );

			return isset( $map[$lower] ) ? $map[$lower] : $value;
		}, $atts );

		// match against allowed values
		foreach( array( 'wmode', 'platform' ) as $att ) {
			$atts[$att] = in_array( $atts[$att], $this->allowed_values[$att] ) ? $atts[$att] : $this->playerDefaults[$att];
		}

		if( !empty( $settings['tweaks'] ) ) {
			$atts['tweaks'] = $settings['tweaks'];
		}

		$width = (int) $atts['width'];
		$height = (int) $atts['height'];

		$player_id = 'ooyalaplayer-' . (int) $num;
		$player_style = '';

		ob_start();

		if( $atts['auto'] ) {
			// Auto-size the player by stretching it into a fixed-ratio container
			$container_style = 'max-width:' . $width . 'px;';

			$player_style = 'position:absolute;top:0;right:0;bottom:0;left:0';

			$sizer_style =
				'width:auto;' .
				'padding-top:' . ($height / $width * 100) . '%;' .
				'position:relative';

	?>
	<div class="ooyala-container" style="<?php echo esc_attr( $container_style ); ?>">
		<div class="ooyala-sizer" style="<?php echo esc_attr( $sizer_style ); ?>">
	<?php
		}

		// With a given player ID we can use the V3 or V4 version of the player
		if( !empty( $atts['player_id'] ) ) {
			$player_version = !empty( $atts['version'] ) && in_array( $atts['version'], $this->allowed_values['player_version'] ) ? $atts['version'] : $settings['player_version'];

			// player query string parameters
			$query_params = array(
				'namespace' => 'OoyalaPlayer' . $num // each player has its own namespace to avoid collisions
			);
			// JS parameters - start with passed json, if any
			if( $content
				&& ( $json = json_decode( $content, true ) )
				&& is_array( $json )
				&& count( array_filter( array_keys( $json ), 'is_string' ) ) //only if assoc array
			) {
				$js_params = $json;
			} else {
				$js_params = array();
			}

			if( !empty( $js_params['css'] ) ) {
				$css = $js_params['css'];
				unset( $js_params['css'] );
			}

			// pick out all other params
			foreach( $atts as $key => $value ) {
				switch ( $key ) {
					// no-op bc these have special placement in the embed code
					case 'width':
					case 'height':
						if( ! $atts['auto'] ) {
							$js_params[$key] = (int) $value;
						}

						break;

					case 'code':
					case 'player_id':
					case 'pcode':
						break;

					// these are query params and are appended to the player script URL
					case 'platform':
					case 'tweaks':
						$query_params[$key] = $value;
						break;

					case 'chromeless':
						if( !$this->is_default( $key, $value ) ) {
							$js_params['layout'] = 'chromeless';
						}
						break;

					// all other params become JS parameters
					// these will override values of the same name supplied from the JSON content block
					default:
						if( !$this->is_default( $key, $value ) ) {
							$js_params[$key] = $value;
						}
					break;
				}
			}

			if( $player_version === 'v4' ) {
				// "Provider code" is the API key up to '.'
				$pcode = !empty( $atts['pcode'] ) ? $atts['pcode'] : substr( $settings['api_key'], 0, strpos( $settings['api_key'], '.' ) );
				$inline = !empty( $json ) ? $json : array();

				$config = array(
					'pcode' => $pcode,
					'playerBrandingId' => $atts['player_id'],
					'autoplay' => !!$atts['autoplay'],
					'loop' => !!$atts['loop'],
					'skin' => array(
						'config' => self::v4_url_base . 'skin-plugin/skin.json',
						'inline' => &$inline,
					),
				);

				if( isset( $js_params['initialTime'] ) ) {
					$config['initialTime'] = (int) $atts['initialTime'];
				}

				if( isset( $js_params['initialVolume'] ) ) {
					$config['initialVolume'] = (double) $js_params['initialVolume'];
				}

				$params = array(
					"ooyalaplayer-$num",
					$atts['code'],
					&$config,
				);

				if( !empty( $settings['additional_params_json'] ) && ( $inline_skin = json_decode( $settings['additional_params_json'], true ) ) ) {
					$inline = $inline_skin + $inline;
				}

				// Emit scripts for the V4 player, ONCE
				if( !$v4_scripts ) {
					// Redundantly compute the default if somehow we are not given an array...
					$plugins = is_array( $settings['plugins'] ) ?
						$settings['plugins'] : $this->settings_defaults['plugins'];

					$scripts = array( self::v4_url_base . 'core.min.js' );

					foreach( $plugins as $plugin ) {
						$scripts[] = self::v4_url_base . 'video-plugin/' . $plugin;
					}

					if( $settings['ad_plugin'] ) {
						// Allow for certain ad plugins to host their JS elsewhere
						if( isset( $this->special_ads[$settings['ad_plugin']] ) ) {
							// Allow manual override of this plugin URL
							if( !empty( $settings['override'][$settings['ad_plugin']] ) ) {
								$ad_plugin = $settings['override'][$settings['ad_plugin']];
							}
							// Or just use the hard-coded URL
							else {
								$ad_plugin = $this->special_ads[$settings['ad_plugin']];
							}
						}
						else {
							$ad_plugin = self::v4_url_base . 'ad-plugin/' . $settings['ad_plugin'];
						}

						// Add pulse rules to config if given
						if( $settings['ad_plugin'] === 'pulse' ) {
							if( $settings['pulse_params_json'] && ( $pulse_settings = json_decode( $settings['pulse_params_json'], true ) ) ) {
								// Allow the user to specify the "videoplaza-ads-manager" object key, or imply it
								if( !isset( $pulse_settings['videoplaza-ads-manager'] ) ) {
									$pulse_settings = array( 'videoplaza-ads-manager' => $pulse_settings );
								}

								// But only allow that key to be added to the config
								$config += array_intersect_key( $pulse_settings, array_flip( array( 'videoplaza-ads-manager' ) ) );
							}
						}

						$scripts[] = $ad_plugin;
					}

					$optional_plugins = is_array( $settings['optional_plugins'] ) ?
						$settings['optional_plugins'] : $this->settings_defaults['optional_plugins'];

					foreach( $optional_plugins as $plugin ) {
						$scripts[] = self::v4_url_base . 'other-plugin/' . $plugin;
					}

					// Add the default skin, which depends on the previous scripts being loaded beforehand
					$scripts[] = self::v4_url_base . 'skin-plugin/html5-skin.min.js';

					foreach( $scripts as $script ) { ?>
						<script src="<?php echo esc_url( $script ); ?>"></script><?php
					} ?>
					<link rel="stylesheet" href="<?php echo esc_url( self::v4_url_base . 'skin-plugin/html5-skin.min.css' ); ?>" />

					<?php
					// Load custom stylesheet if there is one
					if( !empty( $settings['custom_css'] ) ) {
						$permalink = get_option( 'permalink_structure' );
						$md5 = md5( $settings['custom_css'] );

						if( empty( $permalink ) ) {
							$css_link = home_url( '?ooyala_player_css=' . $md5 );
						}
						else {
							$css_link = plugins_url( '/player.css/' . $md5, __FILE__ );
						}
					?>
						<link rel="stylesheet" href="<?php echo esc_url( $css_link  ); ?>" /><?php
					}

					// Flag that we've emitted the V4 scripts on this page, and we shouldn't do it again
					$v4_scripts = true;
				}

				?>

				<div id="<?php echo esc_attr( $player_id ); ?>" class="ooyala-player <?php echo esc_attr( $atts['wrapper_class'] ); ?>" style="<?php echo esc_attr( $player_style ); ?>" ></div>
				<script>
					var ooyalaplayers = ooyalaplayers || [];

					OO.ready(function() {
						var op = typeof window.ooyalaParameters === 'function' ? window.ooyalaParameters : function(params) { return params; };
						ooyalaplayers.push(OO.Player.create.apply(OO.Player, op(<?php echo wp_json_encode( $params ); ?>)));
					});
				</script>
				<noscript><div><?php esc_html_e( 'Please enable Javascript to watch this video', 'ooyala' ); ?></div></noscript>
			<?php }
			else { ?>
				<script src="<?php echo esc_url( '//player.ooyala.com/v3/' . $atts['player_id'] . '?' . http_build_query( $query_params ) ); ?>"></script>
				<div id="<?php echo esc_attr( $player_id ); ?>" class="ooyala-player <?php echo esc_attr( $atts['wrapper_class'] ); ?>" style="<?php echo esc_attr( $player_style ); ?>" ></div>
				<script>
					var ooyalaplayers = ooyalaplayers || [];
					<?php
					$player = 'OoyalaPlayer' . $num;
					$params = array( $player_id, $atts['code'] );
					if( count( $js_params ) ) {
						$params[] = $js_params;
					}
					printf( 'window[%1$s].ready(function() { var op = typeof window.ooyalaParameters === \'function\' ? window.ooyalaParameters : function(params) { return params; }; ooyalaplayers.push(window[%1$s].Player.create.apply(this, op(%2$s))); });', wp_json_encode( $player ), wp_json_encode( $params ) );
					?>
				</script>
				<noscript><div><?php esc_html_e( 'Please enable Javascript to watch this video', 'ooyala' ); ?></div></noscript>
			<?php
			}
		// no player id, use the v2 player
		} else {
			if( !$atts['auto'] ) {
				$player_style = '';
			}

			$script_url = add_query_arg( array(
				'width' => $atts['width'],
				'height' => $atts['height'],
				'embedCode' => $atts['code'],
				'autoplay' => $atts['autoplay'] ? '1' : '0',
				'callback' => $atts['callback'],
				'wmode' => $atts['wmode'],
				'tweaks' => $settings['tweaks'],
				'version' => 2,
			), 'https://player.ooyala.com/player.js' );
			?>
			<div id="ooyalaplayer-<?php echo (int) $num; ?>" class="<?php echo esc_attr( $atts['wrapper_class'] ); ?>" style="<?php echo esc_attr( $player_style ); ?>">
				<script src="<?php echo esc_url( $script_url ); ?>"></script>
				<noscript>
					<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="<?php echo (int) $atts['width']; ?>" height="<?php echo (int) $atts['height']; ?>" codebase="http://fpdownload.macromedia.com/get/flashplayer/current/swflash.cab">
						<param name="movie" value="<?php echo esc_url( 'http://player.ooyala.com/player.swf?embedCode=' . $atts['code'] . '&version=2' ); ?>">
						<param name="bgcolor" value="#000000">
						<param name="allowScriptAccess" value="always">
						<param name="allowFullScreen" value="true">
						<param name="wmode" value="<?php echo esc_attr( $atts['wmode'] ); ?>">
						<param name="flashvars" value="embedType=noscriptObjectTag&amp;embedCode=###VID###">
						<embed src="<?php echo esc_url( 'http://player.ooyala.com/player.swf?embedCode=' . $atts['code'] . '&version=2' ); ?>" bgcolor="#000000" width="<?php echo (int) $atts['width']; ?>" height="<?php echo (int) $atts['height']; ?>" align="middle" play="true" loop="false" allowscriptaccess="always" allowfullscreen="true" type="application/x-shockwave-flash" flashvars="&amp;embedCode=<?php echo esc_attr( $atts['code'] ); ?>" pluginspage="http://www.adobe.com/go/getflashplayer">
						</embed>
					</object>
				</noscript>
			</div>
			<?php
		}

		if( $atts['auto'] ) { ?>
			</div>
		</div>
		<?php
		}

		return ob_get_clean();
	}
}

Ooyala::instance();

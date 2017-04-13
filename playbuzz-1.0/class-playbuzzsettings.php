<?php
/*
 * Security check
 * Exit if file accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Playbuzz Settings
 * Add playbuzz settings page and menus in WordPress dashboard.
 *
 * @since 0.1.0
 */
class PlaybuzzSettings {

	protected static $option_name = 'playbuzz';


	/*
	 * Constructor
	 */
	public function __construct() {

		// Admin sub-menu
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

		}

	}


	/*
	 * Get option name
	 */
	public function get_option_name() {

		return self::$option_name;

	}


	/*
	 * Register setting page using the Settings API.
	 */
	public function admin_init() {

		register_setting(
			'playbuzz',
			$this->get_option_name()
		);

	}



	/*
	 * Add entry in the settings menu.
	 */
	public function add_settings_page() {

		add_options_page(
			__( 'Playbuzz', 'playbuzz' ),
			__( 'Playbuzz', 'playbuzz' ),
			'manage_options',
			'playbuzz',
			array( $this, 'playbuzz_settings_page' )
		);

	}

	/*
	 * Print the menu page itself.
	 */
	public function playbuzz_settings_page() {

		// Load settings
		$options = get_option( $this->get_option_name() );
		$nonce_key = PbConstants::$pb_nonce_key;
		$nonce_value = PbConstants::$pb_nonce_value;

		// Set default tab
		$playbuzz_active_tab = 'embed';

		if ( isset( $_GET['tab'], $_GET[ $nonce_key ] ) && wp_verify_nonce( sanitize_key( $_GET[ $nonce_key ] ) , $nonce_value ) ) {
			$playbuzz_active_tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
		}

		$nonce = wp_create_nonce( $nonce_value );
		// Display the page
		?>
		<a name="top"></a>
		<div class="wrap" id="playbuzz-admin">
			<h1><?php esc_html_e( 'Playbuzz Plugin', 'playbuzz' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<a href="?page=<?php echo urlencode( $this->get_option_name() ); ?>&tab=embed&<?php echo esc_html( $nonce_key ) . '=' . esc_html( $nonce ); ?>"      class="nav-tab <?php echo  ( 'embed' == $playbuzz_active_tab || 'start' == $playbuzz_active_tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Site Settings',   'playbuzz' ); ?></a>
				<a href="?page=<?php echo urlencode( $this->get_option_name() ); ?>&tab=shortcodes&<?php echo esc_html( $nonce_key ) . '=' . esc_html( $nonce ); ?>" class="nav-tab <?php echo  'shortcodes' == $playbuzz_active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Shortcodes',      'playbuzz' ); ?></a>
				<a href="?page=<?php echo urlencode( $this->get_option_name() ); ?>&tab=feedback&<?php echo esc_html( $nonce_key ) . '=' . esc_html( $nonce ); ?>"   class="nav-tab <?php echo  'feedback' == $playbuzz_active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Feedback',        'playbuzz' ); ?></a>
			</h2>

			<?php  if ( 'embed' == $playbuzz_active_tab || 'start' == $playbuzz_active_tab ) { ?>

				<form method="post" action="options.php">

					<?php settings_fields( 'playbuzz' ); ?>

					<div class="playbuzz_embed">

						<h3><?php esc_html_e( 'Playbuzz channel', 'playbuzz' ); ?></h3>

						<label for="pb-channel-alias">
							<?php esc_html_e( 'Playbuzz account name', 'playbuzz' ); ?>
							<input type="text" class="regular-text" id="pb-channel-alias" name="<?php echo esc_attr( $this->get_option_name() ); ?>[pb_user]" value="<?php echo esc_attr( isset( $options['pb_user'] ) ? str_replace( ' ', '', $options['pb_user'] ) : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. sarahpark10', 'playbuzz' ); ?>">
						</label>
						<p class="description description-width"><?php esc_html_e( 'Configuring the account will enable you to: create Story items within WordPress, and search for items you created on Playbuzz.com, even if they were not made public.', 'playbuzz' ); ?></p>
						<p class="description description-width"><?php printf( esc_html__( 'You can find your account name by visiting your profile page on %s and examining the URL ending.', 'playbuzz' ), '<a href="https://www.playbuzz.com/" target="_blank">Playbuzz.com</a>' ); ?></p>
						<p class="description"><strong><?php esc_html_e( 'Example:', 'playbuzz' ); ?></strong> www.playbuzz.com/<strong class="red">username10</strong></p>

						<label for="pb_channel_id">
							<input type="hidden" class="regular-text" id="pb_channel_id" name="<?php echo esc_attr( $this->get_option_name() ); ?>[pb_channel_id]" value="<?php echo esc_attr( isset( $options['pb_channel_id'] ) ? str_replace( ' ', '', $options['pb_channel_id'] ) : '' ); ?>" >
						</label>

					</div>

					<div class="playbuzz_embed">

						<h3><?php esc_html_e( 'Embed Preferences', 'playbuzz' ); ?></h3>
						
						<label for="<?php echo esc_attr( $this->get_option_name() ); ?>[jshead]">
							<input type="checkbox" id="<?php echo esc_attr( $this->get_option_name() ); ?>[info]" name="<?php echo esc_attr( $this->get_option_name() ); ?>[jshead]" value="1" <?php if ( isset( $options['jshead'] ) && ( '1' == $options['jshead'] ) ) { echo 'checked="checked"';} ?>>
							<?php esc_html_e( 'Load scripts in the header', 'playbuzz' ); ?>
						</label>
						<p class="description indent"><?php esc_html_e( 'Will load Playbuzz JS script in head tag. Please note that this will increase loading time.', 'playbuzz' ); ?></p>
						
						<label for="<?php echo esc_attr( $this->get_option_name() ); ?>[info]">
							<input type="checkbox" id="<?php echo esc_attr( $this->get_option_name() ); ?>[info]" name="<?php echo esc_attr( $this->get_option_name() ); ?>[info]" value="1" <?php if ( isset( $options['info'] ) && ( '1' == $options['info'] ) ) { echo 'checked="checked"';} ?>>
							<?php esc_html_e( 'Display item information', 'playbuzz' ); ?>
						</label>
						<p class="description indent">
							<?php esc_html_e( 'Show the item’s thumbnail, name, description and creator.', 'playbuzz' ); ?><br>
							<?php esc_html_e( 'Note: This preference is only relevant for embedded items. Story items created within WordPress do not include this information as default.', 'playbuzz' ); ?>
						</p>

						<label for="<?php echo esc_attr( $this->get_option_name() ); ?>[shares]">
							<input type="checkbox" id="<?php echo esc_attr( $this->get_option_name() ); ?>[shares]" name="<?php echo esc_attr( $this->get_option_name() ); ?>[shares]" value="1" <?php if ( isset( $options['shares'] ) && ( '1' == $options['shares'] ) ) { echo 'checked="checked"';} ?>>
							<?php esc_html_e( 'Display share bar', 'playbuzz' ); ?>
						</label>
						<p class="description indent"><?php esc_html_e( 'Display the item’s share buttons, including links to a predetermined website.', 'playbuzz' ); ?></p>

						<label for="<?php echo esc_attr( $this->get_option_name() ); ?>[comments]">
							<input type="checkbox" id="<?php echo esc_attr( $this->get_option_name() ); ?>[comments]" name="<?php echo esc_attr( $this->get_option_name() ); ?>[comments]" value="1" <?php if ( isset( $options['comments'] ) && ( '1' == $options['comments'] ) ) { echo 'checked="checked"';} ?>>
							<?php esc_html_e( 'Display facebook comments', 'playbuzz' ); ?>
						</label>
						<p class="description indent"><?php esc_html_e( 'Display the item’s facebook comments.', 'playbuzz' ); ?></p>

						<label for="<?php echo esc_attr( $this->get_option_name() ); ?>[margin-top]">
							<?php esc_html_e( 'Sticky header preferences', 'playbuzz' ); ?>
							<input type="text" id="<?php echo esc_attr( $this->get_option_name() ); ?>[margin-top]" name="<?php echo esc_attr( $this->get_option_name() ); ?>[margin-top]" value="<?php echo esc_attr( isset( $options['margin-top'] ) ? $options['margin-top'] : 0 ); ?>" class="small-text">
							<?php esc_html_e( 'px', 'playbuzz' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Use this if your website includes a header that remains visible as users scroll.', 'playbuzz' ); ?></p>

					</div>


					<div class="playbuzz_embed">

						<h3><?php esc_html_e( 'Language', 'playbuzz' ); ?></h3>

						<label for="<?php echo esc_attr( $this->get_option_name() ); ?>[locale]">
							<?php esc_html_e( 'Create Story items in', 'playbuzz' ); ?>
							<select id="<?php echo esc_attr( $this->get_option_name() ); ?>[locale]" name="<?php echo esc_attr( $this->get_option_name() ); ?>[locale]">
								<option value="sq-AL" <?php if ( isset( $options['locale'] ) && ( 'sq-AL' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Albanian', 'playbuzz' ); ?></option>
								<option value="ar" <?php if ( isset( $options['locale'] ) && ( 'ar' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Arabic', 'playbuzz' ); ?></option>
								<option value="zh-CN" <?php if ( isset( $options['locale'] ) && ( 'zh-CN' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Chinese (Simplified)', 'playbuzz' ); ?></option>
								<option value="zh-HK" <?php if ( isset( $options['locale'] ) && ( 'zh-HK' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Chinese (Traditional)', 'playbuzz' ); ?></option>
								<option value="hr-HR" <?php if ( isset( $options['locale'] ) && ( 'hr-HR' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Croatian', 'playbuzz' ); ?></option>
								<option value="cs-CZ" <?php if ( isset( $options['locale'] ) && ( 'cs-CZ' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Czech', 'playbuzz' ); ?></option>
								<option value="da-DK" <?php if ( isset( $options['locale'] ) && ( 'da-DK' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Danish', 'playbuzz' ); ?></option>
								<option value="nl-NL" <?php if ( isset( $options['locale'] ) && ( 'nl-NL' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Dutch', 'playbuzz' ); ?></option>
								<option value="en-US" <?php if ( isset( $options['locale'] ) && ( 'en-US' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'English', 'playbuzz' ); ?></option>
								<option value="et-EE" <?php if ( isset( $options['locale'] ) && ( 'et-EE' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Estonian', 'playbuzz' ); ?></option>
								<option value="fi-FI" <?php if ( isset( $options['locale'] ) && ( 'fi-FI' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Finnish', 'playbuzz' ); ?></option>
								<option value="fr-FR" <?php if ( isset( $options['locale'] ) && ( 'fr-FR' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'French', 'playbuzz' ); ?></option>
								<option value="de-DE" <?php if ( isset( $options['locale'] ) && ( 'de-DE' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'German', 'playbuzz' ); ?></option>
								<option value="el-GR" <?php if ( isset( $options['locale'] ) && ( 'el-GR' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Greek', 'playbuzz' ); ?></option>
								<option value="he-IL" <?php if ( isset( $options['locale'] ) && ( 'he-IL' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Hebrew', 'playbuzz' ); ?></option>
								<option value="hu-HU" <?php if ( isset( $options['locale'] ) && ( 'hu-HU' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Hungarian', 'playbuzz' ); ?></option>
								<option value="hy-AM" <?php if ( isset( $options['locale'] ) && ( 'hy-AM' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Armenian', 'playbuzz' ); ?></option>
								<option value="id-ID" <?php if ( isset( $options['locale'] ) && ( 'id-ID' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Indonesian', 'playbuzz' ); ?></option>
								<option value="it-IT" <?php if ( isset( $options['locale'] ) && ( 'it-IT' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Italian', 'playbuzz' ); ?></option>
								<option value="ja-JP" <?php if ( isset( $options['locale'] ) && ( 'ja-JP' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Japanese', 'playbuzz' ); ?></option>
								<option value="ko-KR" <?php if ( isset( $options['locale'] ) && ( 'ko-KR' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Korean', 'playbuzz' ); ?></option>
								<option value="lv-LV" <?php if ( isset( $options['locale'] ) && ( 'lv-LV' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Latvian', 'playbuzz' ); ?></option>
								<option value="mn-MN" <?php if ( isset( $options['locale'] ) && ( 'mn-MN' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Mongolian (Cyrillic)', 'playbuzz' ); ?></option>
								<option value="nb-NO" <?php if ( isset( $options['locale'] ) && ( 'nb-NO' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Norwegian (Bokmål)', 'playbuzz' ); ?></option>
								<option value="pl-PL" <?php if ( isset( $options['locale'] ) && ( 'pl-PL' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Polish', 'playbuzz' ); ?></option>
								<option value="pt-BR" <?php if ( isset( $options['locale'] ) && ( 'pt-BR' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Portuguese', 'playbuzz' ); ?></option>
								<option value="ro-RO" <?php if ( isset( $options['locale'] ) && ( 'ro-RO' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Romanian', 'playbuzz' ); ?></option>
								<option value="ro-RO" <?php if ( isset( $options['locale'] ) && ( 'ro-RO' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Romanian', 'playbuzz' ); ?></option>
								<option value="ru-RU" <?php if ( isset( $options['locale'] ) && ( 'ru-RU' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Russian', 'playbuzz' ); ?></option>
								<option value="es-ES" <?php if ( isset( $options['locale'] ) && ( 'es-ES' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Spanish', 'playbuzz' ); ?></option>
								<option value="sl-SI" <?php if ( isset( $options['locale'] ) && ( 'sl-SI' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Slovenian', 'playbuzz' ); ?></option>
								<option value="sv-SE" <?php if ( isset( $options['locale'] ) && ( 'sv-SE' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Swedish', 'playbuzz' ); ?></option>
								<option value="th-TH" <?php if ( isset( $options['locale'] ) && ( 'th-TH' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Thai', 'playbuzz' ); ?></option>
								<option value="tr-TR" <?php if ( isset( $options['locale'] ) && ( 'tr-TR' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Turkish', 'playbuzz' ); ?></option>
								<option value="en" <?php if ( isset( $options['locale'] ) && ( 'en' == $options['locale'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Other', 'playbuzz' ); ?></option>
							</select>
						</label>


					</div>

					<div class="playbuzz_embed">

						<h3><?php esc_html_e( 'Appearance Preferences', 'playbuzz' ); ?></h3>

						<label for="<?php echo esc_attr( $this->get_option_name() ); ?>[embeddedon]">
							<?php esc_html_e( 'Display embedded items on:', 'playbuzz' ); ?>
							<select id="<?php echo esc_attr( $this->get_option_name() ); ?>[embeddedon]" name="<?php echo esc_attr( $this->get_option_name() ); ?>[embeddedon]">
								<option value="content" <?php if ( isset( $options['embeddedon'] ) && ( 'content' == $options['embeddedon'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'Posts & Pages Only',                  'playbuzz' ); ?></option>
								<option value="all"     <?php if ( isset( $options['embeddedon'] ) && ( 'all' == $options['embeddedon'] ) ) { echo 'selected';} ?>><?php esc_html_e( 'All pages (singular, archive, ect.)', 'playbuzz' ); ?></option>
							</select>
						</label>
						<p class="description"><?php printf( esc_html__( 'Choose between displaying the embedded content in %1$s only, or in %2$s as well.', 'playbuzz' ), '<a href="https://codex.wordpress.org/Function_Reference/is_singular" target="_blank">singular pages</a>' , '<a href="https://codex.wordpress.org/Template_Hierarchy" target="_blank">archive page</a>' ); ?></p>

					</div>

					<div class="playbuzz_embed"  style="display:none;">

						<h3><?php esc_html_e( 'Experiment Mode', 'playbuzz' ); ?></h3>

						<label for="<?php echo esc_attr( $this->get_option_name() ); ?>[experiment-mode]">
							<input type="checkbox" id="<?php echo esc_attr( $this->get_option_name() ); ?>[experiment-mode]" name="<?php echo esc_attr( $this->get_option_name() ); ?>[experiment-mode]" value="1" <?php if ( isset( $options['experiment-mode'] ) && ( '1' == $options['experiment-mode'] ) ) { echo 'checked="checked"';} ?>>
							<?php esc_html_e( 'Active experiment mode', 'playbuzz' ); ?>
						</label>
						<p class="description indent"><?php esc_html_e( 'Enable experiment mode features', 'playbuzz' ); ?></p>

					</div>



					<?php submit_button(); ?> 

				</form>

			<?php } elseif ( 'shortcodes' == $playbuzz_active_tab ) { ?>

				<div class="playbuzz_shortcodes">

					<h3><?php esc_html_e( 'Item Shortcode', 'playbuzz' ); ?></h3>
					<p><?php printf( esc_html__( 'Choose any Playful Content item from %s and easily embed it in a post.', 'playbuzz' ), '<a href="https://www.playbuzz.com/" target="_blank">playbuzz.com</a>' ); ?></p>
					<p><?php esc_html_e( 'For basic use, paste the item URL into your text editor and go to the visual editor to make sure it loads.', 'playbuzz' ); ?></p>
					<p><?php esc_html_e( 'For more advance usage, use the following shortcode if you want to adjust the item appearance:', 'playbuzz' ); ?></p>
					<p><code>[playbuzz-item url="https://www.playbuzz.com/llamap10/how-weird-are-you" comments="false"]</code></p>
					<p><?php printf( esc_html__( 'You can set default appearance settings in the %s tab.', 'playbuzz' ), '<a href="?page=' . esc_html( $this->get_option_name() ) . '&tab=embed&' . esc_html( $nonce_key ) . '=' . esc_html( $nonce ) . '">here</a>' ); ?></p>
					<p><?php esc_html_e( 'Or you can override the default appearance and customize each item with the following shortcode attributes:', 'playbuzz' ); ?></p>
					<dl>
						<dt>url</dt>
						<dd>
							<p><?php esc_html_e( 'The URL of the item that will be displayed.', 'playbuzz' ); ?></p>
							<p><?php esc_html_e( 'Type: URL', 'playbuzz' ); ?></p>
						</dd>
						<dt>info</dt>
						<dd>
							<p><?php esc_html_e( 'Show item info (thumbnail, name, description, editor, etc).', 'playbuzz' ); ?></p>
							<p><?php esc_html_e( 'Type: Boolean (true/false) ; Default: true', 'playbuzz' ); ?></p>
						</dd>
						<dt>shares</dt>
						<dd>
							<p><?php esc_html_e( 'Show sharing buttons.', 'playbuzz' ); ?></p>
							<p><?php esc_html_e( 'Type: Boolean (true/false) ; Default: true', 'playbuzz' ); ?></p>
						</dd>
						<dt>comments</dt>
						<dd>
							<p><?php esc_html_e( 'Show comments control from the item page.', 'playbuzz' ); ?></p>
							<p><?php esc_html_e( 'Type: Boolean (true/false) ; Default: true', 'playbuzz' ); ?></p>
						</dd>
						<dt>recommend</dt>
						<dd>
							<p><?php esc_html_e( 'Show recommendations for more items.', 'playbuzz' ); ?></p>
							<p><?php esc_html_e( 'Type: Boolean (true/false) ; Default: true', 'playbuzz' ); ?></p>
						</dd>
						<dt>links</dt>
						<dd>
							<p><?php esc_html_e( 'Destination page, containing the [playbuzz-section] shortcode, where new items will be displayed.', 'playbuzz' ); ?></p>
							<p><?php esc_html_e( 'Type: URL ; Default: https://www.playbuzz.com/', 'playbuzz' ); ?></p>
						</dd>
						<dt>width</dt>
						<dd>
							<p><?php esc_html_e( 'Define custom width in pixels.', 'playbuzz' ); ?></p>
							<p><?php esc_html_e( 'Type: String ; Default: auto', 'playbuzz' ); ?></p>
						</dd>
						<dt>height</dt>
						<dd>
							<p><?php esc_html_e( 'Define custom height in pixels.', 'playbuzz' ); ?></p>
							<p><?php esc_html_e( 'Type: String ; Default: auto', 'playbuzz' ); ?></p>
						</dd>
						<dt>margin-top</dt>
						<dd>
							<p><?php esc_html_e( 'Define custom margin-top in pixels.', 'playbuzz' ); ?></p>
							<p><?php esc_html_e( 'Type: String ; Default: 0px', 'playbuzz' ); ?></p>
						</dd>
					</dl>

				</div>

			<?php } elseif ( 'feedback' == $playbuzz_active_tab ) { ?>

				<div class="playbuzz_feedback">

					<h3><?php esc_html_e( 'We Are Listening', 'playbuzz' ); ?></h3>

					<p><?php esc_html_e( 'We’d love to know about your experiences with our WordPress plugin and beyond. Drop us a line using the form below', 'playbuzz' ); ?></p>

					<div class="playbuzz_feedback_message">
						<p><br></p>
					</div>

					<form id="playbuzz_feedback_form" method="post">
						<p>
							<label for="fullName"><?php esc_html_e( 'Your Name', 'playbuzz' ); ?></label>
							<input type="text" name="fullName" class="regular-text">
						</p>
						<p>
							<label for="email"><?php esc_html_e( 'Email (so we can write you back)', 'playbuzz' ); ?></label>
							<input type="text" name="email" class="regular-text" value="<?php echo esc_attr( get_bloginfo( 'admin_email' ) ); ?>">
						</p>
						<p>
							<label for="message"><?php esc_html_e( 'Message', 'playbuzz' ); ?></label>
							<textarea name="message" rows="5" class="widefat" placeholder="<?php esc_attr_e( 'What\'s on your mind?', 'playbuzz' ); ?>"></textarea>
						</p>
						<input type="hidden" name="subject" value="<?php esc_attr_e( 'WordPress plugin feedback', 'playbuzz' ); ?>">
						<input type="button" name="button" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Submit', 'playbuzz' ); ?>">
					</form>

				</div>

				<div class="playbuzz_feedback">

					<h3><?php esc_html_e( 'Enjoying the Playbuzz WordPress Plugin?', 'playbuzz' ); ?></h3>
					<p><?php printf( esc_html__( '%s on the WordPress Plugin Directory to help others to discover the engagement value of Playbuzz embeds!', 'playbuzz' ), '<a href="https://wordpress.org/support/view/plugin-reviews/playbuzz#postform" target="_blank">Rate us</a>' ); ?></p>

				</div>

				<div class="playbuzz_feedback">

					<h3><?php esc_html_e( 'Become a Premium Playbuzz Publisher', 'playbuzz' ); ?></h3>
					<p><?php esc_html_e( 'Want to learn how Playbuzz can take your publication’s engagement to new heights?', 'playbuzz' ); ?> <a href="https://publishers.playbuzz.com/" target="_blank"><?php esc_html_e( 'Lets Talk!', 'playbuzz' ); ?></a></p>

				</div>

				<div class="playbuzz_feedback">

					<h3><?php esc_html_e( 'Join the Playbuzz Publishers Community', 'playbuzz' ); ?></h3>
					<p>
						<a href="https://www.facebook.com/playbuzz" target="_blank" class="playbuzz_facebook"></a>
						<a href="https://twitter.com/play_buzz" target="_blank" class="playbuzz_twitter"></a>
						<a href="https://plus.google.com/+Playbuzz" target="_blank" class="playbuzz_googleplus"></a>
						<a href="https://www.linkedin.com/company-beta/2709152/?pathWildcard=2709152" target="_blank" class="playbuzz_linkedin"></a>
						<a href="https://www.pinterest.com/playbuzz4biz/" target="_blank" class="playbuzz_pinterest"></a>
						<a href="http://play-buzz.tumblr.com/" target="_blank" class="playbuzz_tumblr"></a>
						<a href="https://instagram.com/play_buzz" target="_blank" class="playbuzz_instagram"></a>
					</p>

				</div>

			<?php }// End if().
	?>

		</div>
		<?php

	}

}
new PlaybuzzSettings();

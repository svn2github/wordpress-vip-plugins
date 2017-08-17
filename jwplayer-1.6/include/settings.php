<?php


// Add the JW Player settings to the media page in the admin panel
function jwplayer_settings_init() {
	add_options_page( 'JW Player Plugin Settings', 'JW Player', 'manage_options', 'jwplayer_settings', 'jwplayer_settings_page' );
	add_settings_section( 'jwplayer_setting_basic_section', null, '__return_true', 'jwplayer_settings' );

	if ( get_option( 'jwplayer_api_key' ) ) {
		add_settings_field( 'jwplayer_logout_link', 'Authorization', 'jwplayer_setting_logout_link', 'jwplayer_settings', 'jwplayer_setting_basic_section' );
		add_settings_field( 'jwplayer_player', 'Default player', 'jwplayer_setting_player', 'jwplayer_settings', 'jwplayer_setting_basic_section' );
		add_settings_field( 'jwplayer_show_widget', 'Authoring page widget', 'jwplayer_setting_show_widget', 'jwplayer_settings', 'jwplayer_setting_basic_section' );
		add_settings_field( 'jwplayer_nr_videos', 'Videos in widget', 'jwplayer_setting_nr_videos', 'jwplayer_settings', 'jwplayer_setting_basic_section' );

		add_settings_section( 'jwplayer_setting_advanced_section', 'Advanced Settings', 'jwplayer_settings_advanced', 'jwplayer_settings' );
		add_settings_field( 'jwplayer_timeout', 'Timeout for signed links', 'jwplayer_setting_timeout', 'jwplayer_settings', 'jwplayer_setting_advanced_section' );
		add_settings_field( 'jwplayer_content_mask', 'Content DNS mask', 'jwplayer_setting_content_mask', 'jwplayer_settings', 'jwplayer_setting_advanced_section' );
		add_settings_field( 'jwplayer_enable_sync', 'Enable JW Sync', 'jwplayer_setting_enable_sync', 'jwplayer_settings', 'jwplayer_setting_advanced_section' );
		add_settings_field( 'jwplayer_custom_shortcode_parser', 'Custom shortcode parser', 'jwplayer_setting_custom_shortcode', 'jwplayer_settings', 'jwplayer_setting_advanced_section' );

		register_setting( 'jwplayer_settings', 'jwplayer_nr_videos', 'absint' );
		register_setting( 'jwplayer_settings', 'jwplayer_timeout', 'absint' );
		register_setting( 'jwplayer_settings', 'jwplayer_content_mask', 'jwplayer_validate_content_mask' );
		register_setting( 'jwplayer_settings', 'jwplayer_player', 'jwplayer_validate_player' );
		register_setting( 'jwplayer_settings', 'jwplayer_show_widget', 'jwplayer_validate_boolean' );
		register_setting( 'jwplayer_settings', 'jwplayer_enable_sync', 'jwplayer_validate_boolean' );
		register_setting( 'jwplayer_settings', 'jwplayer_custom_shortcode_parser', 'jwplayer_validate_boolean' );
	} else {
		add_settings_field( 'jwplayer_login_link', 'Authorization', 'jwplayer_setting_login_link', 'jwplayer_settings', 'jwplayer_setting_basic_section' );
	}

	if ( get_option( 'jwplayer_api_key' ) && get_option( 'jwplayer_custom_shortcode_parser' ) ) {
		add_settings_section( 'jwplayer_shortcode_section', 'Shortcode Settings', 'jwplayer_setting_section_shortcode', 'jwplayer_settings' );

		add_settings_field( 'jwplayer_shortcode_category_filter', 'Category pages', 'jwplayer_setting_custom_shortcode_category', 'jwplayer_settings', 'jwplayer_shortcode_section' );
		add_settings_field( 'jwplayer_shortcode_search_filter', 'Search pages', 'jwplayer_setting_custom_shortcode_search', 'jwplayer_settings', 'jwplayer_shortcode_section' );
		add_settings_field( 'jwplayer_shortcode_tag_filter', 'Tag pages', 'jwplayer_setting_custom_shortcode_tag', 'jwplayer_settings', 'jwplayer_shortcode_section' );
		add_settings_field( 'jwplayer_shortcode_home_filter', 'Home page', 'jwplayer_setting_custom_shortcode_home', 'jwplayer_settings', 'jwplayer_shortcode_section' );

		register_setting( 'jwplayer_settings', 'jwplayer_shortcode_category_filter', 'jwplayer_validate_custom_shortcode' );
		register_setting( 'jwplayer_settings', 'jwplayer_shortcode_search_filter', 'jwplayer_validate_custom_shortcode' );
		register_setting( 'jwplayer_settings', 'jwplayer_shortcode_tag_filter', 'jwplayer_validate_custom_shortcode' );
		register_setting( 'jwplayer_settings', 'jwplayer_shortcode_home_filter', 'jwplayer_validate_custom_shortcode' );
	}

	// Legacy redirect
	$botr_active = is_plugin_active( 'bits-on-the-run/bitsontherun.php' );
	if ( $botr_active || get_option( 'jwplayer_import_done' ) ) {
		add_settings_section( 'jwplayer_setting_media_section', 'JW Player Plugin', '__return_true', 'media' );
		add_settings_field( 'jwplayer_setting_media', 'JW Player Plugin Settings', 'jwplayer_setting_media_redirect', 'media', 'jwplayer_setting_media_section' );
	}
}

function jwplayer_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return null;
	}
	echo '<div class="wrap">';
	echo '<h2>JW Player Plugin Settings</h2>';
	echo '<form method="post" action="options.php">';
	settings_fields( 'jwplayer_settings' );
	do_settings_sections( 'jwplayer_settings' );
	submit_button();
	echo '</form>';
	echo '</div>';
}

// The logout link on the settings page
function jwplayer_setting_logout_link() {
	$logout_url = get_admin_url( null, 'admin.php?page=jwplayer_logout_page' );
	$api_key = get_option( 'jwplayer_api_key' );
	echo 'Authorized with api key <em>' . esc_html( $api_key ) . '</em>. <a href="' . esc_url( $logout_url ) . '">Deauthorize</a>.';
}

// The setting for the number of videos to show in the widget
function jwplayer_setting_nr_videos() {
	$nr_videos = absint( get_option( 'jwplayer_nr_videos', JWPLAYER_NR_VIDEOS ) );

	echo 'Show <input name="jwplayer_nr_videos" id="jwplayer_nr_videos" type="text" size="2" value="' . esc_attr( $nr_videos ) . '" /> videos.';
}

// Adds the Advanced Settings submenu intro
function jwplayer_settings_advanced() {
	echo '<p>Please make sure to read the documentation before changing any of the settings below. If these settings are used incorrectly, your plugin or Wordpress site might not function properly.</p>';
}

// The setting for the signed player timeout
function jwplayer_setting_timeout() {
	$timeout = absint( get_option( 'jwplayer_timeout', JWPLAYER_TIMEOUT ) );

	echo '<input name="jwplayer_timeout" id="jwplayer_timeout" type="text" size="7" value="' . esc_attr( $timeout ) . '" /> ';
	echo '<label for="jwplayer_timeout">minutes</label>';
	echo '<p class="description">The duration for which a <a href="' . esc_url( 'http://support.jwplayer.com/customer/portal/articles/1433647-secure-platform-videos-with-signing' ) . '">signed player</a> will be valid. To turn signing off, set "0".</p>';
}

// The setting for the content mask
function jwplayer_setting_content_mask() {
	$content_mask = jwplayer_get_content_mask();
	if ( ! $content_mask ) {
		// An empty content mask, or the variable was somehow removed entirely
		$content_mask = JWPLAYER_CONTENT_MASK;
		update_option( 'jwplayer_content_mask', $content_mask );
	}
	echo '<input name="jwplayer_content_mask" id="jwplayer_content_mask" type="text" value="' . esc_attr( $content_mask ) . '" class="regular-text" />';
	echo '<p class="description">';
	echo 'The <a href="' . esc_url( 'http://support.jwplayer.com/customer/portal/articles/1433702-dns-masking-the-jw-platform' ) . '">DNS mask</a> of the content server.<br />';
	echo '<strong>Note:</strong>	 https embeds will not work with a content mask.';
	echo '</p>';
}

// The setting for the default player
function jwplayer_setting_player() {
	$api_key = get_option( 'jwplayer_api_key' );
	$loggedin = ! empty( $api_key );
	if ( $loggedin ) {
		$response = jwplayer_api_call( '/players/list' );
		$player = get_option( 'jwplayer_player' );

		echo '<select name="jwplayer_player" id="jwplayer_player" />';

		if ( is_array( $response ) ) {
			foreach ( $response['players'] as $i => $p ) {
				if ( is_array( $p ) ) {
					$key = $p['key'];
					if ( $p['responsive'] ) {
						$description = htmlentities( $p['name'] ) . ' (Responsive, ' . $p['aspectratio'] . ')';
					} else {
						$description = htmlentities( $p['name'] ) . ' (Fixed size, ' . $p['width'] . 'x' . $p['height'] . ')';
					}
					echo '<option value="' . esc_attr( $key ) . '"' . esc_attr( selected( $key === $player, true, false ) ) . '>' . esc_html( $description ) . '</option>';
				}
			}
		}

		echo '</select>';

		echo '
			<p class="description">
				Embedded videos will use this player if no other is specified. To edit
				this player,
				<a href="' . esc_url( JWPLAYER_DASHBOARD . '#/players/list' ) . '">go
				to your JW Player dashboard</a>.<br />
				To override this selection, add a dash and the corresponding player
				key to the video key in the shortcode.<br />
				For example: <code>[jwplayer MdkflPz7-35rdi1pO]</code>
			</p>
		';
	} else {
		echo '<input type="hidden" name="jwplayer_player" value="' . esc_attr( JWPLAYER_PLAYER ) . '" />';
		echo 'You have to save log in before you can set this option.';
	}
}

// The setting which determines whether we show the widget on the authoring page (or only in the "Add media" window)
function jwplayer_setting_show_widget() {
	$show_widget = get_option( 'jwplayer_show_widget', JWPLAYER_SHOW_WIDGET );
	echo '<input name="jwplayer_show_widget" id="jwplayer_show_widget" type="checkbox" ';
	checked( true, $show_widget );
	echo ' value="true" /> ';
	echo '<label for="jwplayer_show_widget">Show</label>';
	echo '<p class="description"><strong>Note:</strong> The widget is always accessible from the <em>Add media</em> window.</p>';
}

// The settings which determines if external media is imported into the JW Player account or left as is.
function jwplayer_setting_enable_sync() {
	$enable_sync = get_option( 'jwplayer_enable_sync', JWPLAYER_ENABLE_SYNC );
	echo '<input name="jwplayer_enable_sync" id="jwplayer_enable_sync" type="checkbox" ';
	checked( true, $enable_sync );
	echo ' value="1" /> ';
	echo '<label for="jwplayer_enable_sync">Sync media to JW Player</label>';
	echo '<p class="description">Enabling this setting will make it possible to sync your local media files to your JW Player account.</p>';
	echo '<p class="description">For synced media you can manage metadata and see video statistics inside the JW Player dashboard.</p>';
}

// The setting which determines whether we use the built-in shortcode parser or our own filters.
function jwplayer_setting_custom_shortcode() {
	$use_custom = get_option( 'jwplayer_custom_shortcode_parser' );
	echo '<input name="jwplayer_custom_shortcode_parser" id="jwplayer_custom_shortcode_parser" type="checkbox" ';
	checked( true, $use_custom );
	echo ' value="true" /> ';
	echo '<label for="jwplayer_custom_shortcode_parser">Use a custom shortcode parser to support shortcode replacement in different page types.</label>';
	// TODO: Update URL to point to new docs.
	echo '<p class="description"><a href="' . esc_url( 'https://support.jwplayer.com/customer/portal/articles/1403714-jw6-wordpress-plugin-reference' )  . '">Learn more.</a></p>';
}

// The login link on the settings page
function jwplayer_setting_login_link() {
	$login_url = get_admin_url( null, 'admin.php?page=jwplayer_login_page' );
	echo 'In order to use this plugin, please <a href="' . esc_url( $login_url ) . '">authorize</a> it first.';
}

function jwplayer_setting_section_shortcode() {
	echo '<p>';
	echo '    You can configure wether you want JW Player to embed in overview pages (home, tags, etc). Depending';
	echo '    upon your WordPress theme, the JW Player plugin must render the shortcodes from either ';
	echo '    <code>the_excerpt</code> or <code>the_content</code>. The third option is to disable player embeds';
	echo '    on a specific page type. This will strip out the shortcode.';
	echo '</p>';
}

function jwplayer_setting_custom_shortcode_filter( $page_type ) {
	$option_name = 'jwplayer_shortcode_' . $page_type . '_filter';
	$current_value = get_option( $option_name );
	$current_value = ( $current_value ) ? $current_value : 'content';
	echo '<fieldset>';
	foreach ( json_decode( JWPLAYER_CUSTOM_SHORTCODE_OPTIONS ) as $option ) {
			$option_label = ( 'strip' === $option ) ? 'Strip shortcode' : "Use $option";
			echo '<label title="' . esc_attr( $option ) . '">';
			echo '<input type="radio" value="' . esc_attr( $option ) . '" name="' . esc_attr( $option_name ) . '" '. checked( $current_value, $option, false ) . '/>';
			echo '<span>&nbsp;' . esc_html( $option_label ) . '</span>';
			echo '</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	}
	echo '</fieldset>';
}

function jwplayer_setting_custom_shortcode_category( $page_type = 'category' ) {
	return jwplayer_setting_custom_shortcode_filter( 'category' );
}

function jwplayer_setting_custom_shortcode_search() {
	return jwplayer_setting_custom_shortcode_filter( 'search' );
}

function jwplayer_setting_custom_shortcode_tag() {
	return jwplayer_setting_custom_shortcode_filter( 'tag' );
}

function jwplayer_setting_custom_shortcode_home() {
	return jwplayer_setting_custom_shortcode_filter( 'home' );
}

function jwplayer_setting_media_redirect() {
	$redirect_url = get_admin_url( null, 'options-general.php?page=jwplayer_settings' );
	echo 'JW Player plugin settings have moved. Please <a href="' . esc_url( $redirect_url ) . '" title="Manage JW Player Plugin">go here</a> now.';
}

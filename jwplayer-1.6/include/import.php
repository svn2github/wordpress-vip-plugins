<?php

// ONLY USED BY WORDPRESS.ORG PLUGINS, SEE jw-player.php:107

function jwplayer_import_check_and_init() {
	if ( get_option( 'jwplayer_api_key' ) && get_option( 'jwp6_plugin_version' ) && ! get_option( 'jwplayer_import_done' ) ) {
		$nr_of_players = jwplayer_import_nr_of_players();
		$nr_of_playlists = jwplayer_import_nr_of_playlists();
		$botr_active = is_plugin_active( 'bits-on-the-run/bitsontherun.php' );
		$jwp_active = is_plugin_active( 'jw-player-plugin-for-wordpress/jwplayermodule.php' );
		$vip_active = false;  // is_plugin_active( '????????' );
		if ( $nr_of_players || $nr_of_playlists ) {
			add_action( 'admin_notices', 'jwplayer_import_legacy_notice' );
			add_submenu_page( null, 'JW Player Legacy Plugin Import', 'JW Player Import', 'manage_options', 'jwplayer_import', 'jwplayer_import_page' );
			add_settings_section( 'jwplayer_import_section', null, 'jwplayer_import_section_html', 'jwplayer_import' );
			if ( $nr_of_players ) {
				add_settings_field( 'jwplayer_import_include_players', 'Import Players', 'jwplayer_import_include_players', 'jwplayer_import', 'jwplayer_import_section' );
				register_setting( 'jwplayer_import', 'jwplayer_import_include_players', 'jwplayer_import_players_check' );
			}
			if ( $nr_of_playlists ) {
				add_settings_field( 'jwplayer_import_include_playlists', 'Import Playlists', 'jwplayer_import_include_playlists', 'jwplayer_import', 'jwplayer_import_section' );
				register_setting( 'jwplayer_import', 'jwplayer_import_include_playlists', 'jwplayer_import_playlists_check' );
			}
		} elseif ( $botr_active || $jwp_active || $vip_active ) {
			add_action( 'admin_notices', 'jwplayer_import_disable_notice' );
		} else {
			delete_option( 'jwplayer_import_include_players' );
			delete_option( 'jwplayer_import_include_playlists' );
			add_option( 'jwplayer_import_done', true );
		}
	}
}

function jwplayer_import_legacy_notice() {
	if ( isset( $_GET['page'] ) && 'jwplayer_import' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // Input var okay
		return;
	} elseif ( get_option( 'jwplayer_api_key' ) ) {
		$import_url = get_admin_url( null, 'admin.php?page=jwplayer_import' );
		echo '
			<div class="update-nag fade">
				<p>
					<strong>Please Note:</strong>
					We noticed that you were using the old JW Player Plugin.
					<a href="' . esc_url( $import_url ) . '">Use this tool</a> to import
					player settings and playlists into your JW player account, so that
					they display correctly.
				</p>
			</div>
		';
	} else {
		return;
	}
}

function jwplayer_import_disable_notice() {
	$screen_info = get_current_screen();
	if ( isset( $_GET['page'] ) && 'jwplayer_import' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // Input var okay
		return;
	} elseif ( 'plugins' === $screen_info->id ) {
		return;
	} else {
		$botr_active = is_plugin_active( 'bits-on-the-run/bitsontherun.php' );
		$jwp_active = is_plugin_active( 'jw-player-plugin-for-wordpress/jwplayermodule.php' );
		$vip_active = false;  // is_plugin_active( '????????' );
		$plugins_url = get_admin_url( null, 'plugins.php' );
		echo '
			<div class="update-nag fade">
				<p>
					<strong>Note:</strong>
		';
		if ( $botr_active && $jwp_active ) {
			echo '
					It looks like you have not deactivated the old JW Player and
					the old JW Platform plugins yet.
			';
		} elseif ( $botr_active ) {
			echo '
					It looks like you have not deactivated the old JW Platform plugin yet.
			';
		} elseif ( $jwp_active ) {
			echo '
					It looks like you have not deactivated the old JW Player plugin yet.
			';
		}
		echo '
						You will need to do that on the <a href="' . esc_url( $plugins_url ) . '"
						title="Go to the plugins page">plugins page</a> (These plugins
						cannot be used at the same time).
					</p>
				</div>
			';
	}
}

function jwplayer_import_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return null;
	}
	echo '<div class="wrap">';
	echo '<h2>JW Player Legacy Plugin Import</h2>';
	echo '<form method="post" action="options.php">';
	settings_fields( 'jwplayer_import' );
	do_settings_sections( 'jwplayer_import' );
	submit_button( 'Start Import' );
	echo '</form>';
	echo '</div>';
}

function jwplayer_import_section_html() {
	echo '
		<p>
			This tool will migrate your existing JW Player Playlists and Players from
			your old JW Player Plugin to your JW Player account, which will allow
			them to be used by the new JW Player plugin. You will also be able to
			track analytics for your videos in the JW Player dashboard.
		</p>
		<p>
			If you have many Playlists and Player configurations, we recommend doing
			the Player & Playlist imports one at a time.
		</p>
		<p>
			<strong>Note:</strong> Please make sure you have a backup to revert to in
			case something goes wrong.
		</p>
	';
}

function jwplayer_import_check_redirect() {
	$nr_of_players = jwplayer_import_nr_of_players();
	$nr_of_playlists = jwplayer_import_nr_of_playlists();
	if ( ! $nr_of_players && ! $nr_of_playlists ) {
		wp_redirect( admin_url( 'index.php' ) );
		exit;
	}
}

function jwplayer_import_include_players() {
	$nr = jwplayer_import_nr_of_players();
	echo '<input name="jwplayer_import_include_players" id="jwplayer_import_include_players" type="checkbox" value="true" /> ';
	echo '
		<label for="jwplayer_import_include_players">
			Import all player configurations. <strong>(' . esc_html( $nr ). ' from old plugin)</strong>
		</label>
	';
	// TODO: Have URL to support doc explaining the custom shortcode parser.
	echo '
		<p class="description">
			You can always decide to edit or delete these players after the import —
			they will be in your Players list int he JW Player dashboard, with a
			"WordPress" name.
		</p>
	';
}

function jwplayer_import_include_playlists() {
	$nr = jwplayer_import_nr_of_playlists();
	echo '<input name="jwplayer_import_include_playlists" id="jwplayer_import_include_playlists" type="checkbox" value="true" /> ';
	echo '
		<label for="jwplayer_import_include_playlists">
			Import all playlists and content.
			<strong>(' . esc_html( $nr ). ' from old plugin)</strong>
		</label>
	';
	// TODO: Have URL to support doc explaining the custom shortcode parser.
	echo '
		<p class="description">
			The media from these playlists will be added to your JW Player account as
			externally-hosted URL references, and wil be tagged with
			<strong>wp_media</strong> for easy reference.
		</p>
	';
}

function jwplayer_import_players_check( $input ) {
	if ( $input ) {
		jwplayer_import_players();
	}
	return null;
}

function jwplayer_import_playlists_check( $input ) {
	if ( $input ) {
		jwplayer_import_playlists();
	}
	return null;
}

function jwplayer_import_legacy_playlists() {
	$playlist_query = new WP_Query( array(
		'post_type' => 'jw_playlist',
		'post_status' => null,
		'post_parent' => null,
	));
	$playlists = array();
	while( $playlist_query->have_posts() ) {
		$playlists[] = $playlist_query->the_post();
	}
  wp_reset_postdata();
	return $playlists;
}

function jwplayer_import_skin_list() {
	$params = array(
		'player_version' => 7,
		'result_limit' => 200,
	);
	$skins = array();
	$response = jwplayer_api_call( '/accounts/skins/list', $params );
	if ( jwplayer_api_response_ok( $response ) ) {
		foreach ( $response['skins'] as $skin ) {
			$skins[ strtolower( $skin['name'] ) ] = $skin['key'];
		}
	}
	return $skins;
}

function jwplayer_import_players() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$player_ids = get_option( 'jwp6_players' );
	$imported_players = get_option( 'jwplayer_imported_players' );
	if ( ! $imported_players ) {
		$imported_players = array();
		add_option( 'jwplayer_imported_players', $imported_players );
	}
	$skins = jwplayer_import_skin_list();
	foreach ( $player_ids as $player_id ) {
		// These are the default settings.
		if ( array_key_exists( $player_id, $imported_players ) ) {
			continue;
		}
		$params = array(
			'description' => 'WordPress imported player',
			'width' => 480,
			'height' => 270,
			'aspectratio' => null,
			'controls' => true,
			'stretching' => 'uniform',
			'skin' => null,
			'autostart' => false,
			'mute' => false,
			'primary' => 'flash',
			'repeat' => false,
			'logo__file' => '',
			'logo__hide' => 'false',
			'logo__link' => '',
			'logo__margin' => 8,
			'logo__position' => 'top-right',
			'advertising__client' => null,
			'advertising__tag' => '',
		);
		// Fetch the settings for the player we are importing
		$player = get_option( 'jwp6_player_config_' . $player_id );
		// Overwrite any settings that are not default
		foreach ( $player as $param => $value ) {
			if ( array_key_exists( $param, $params ) ) {
				$params[ $param ] = $value;
			}
		}
		// Translate these params into parameters that the API accepts.
		if ( 'Default and fallback player (unremovable).' === $params['description'] ) {
			$params['name'] = 'Default WordPress plugin player';
		} else {
			$params['name'] = $params['description'] . ' (Imported WordPress player)';
		}
		if ( $params['aspectratio'] ) {
			$params['responsive'] = true;
		} else {
			unset( $params['aspectratio'] );
		}
		if ( $params['skin'] && array_key_exists( $params['skin'], $skins ) ) {
			$params['skin_key'] = $skins[ $params['skin'] ];
		}
		$params['repeat'] = ( $params['repeat'] ) ? 'list' : 'none';
		if ( $params['logo__file'] ) {
			// TODO: Upload and add watermark.
		}
		if ( $params['advertising__client'] && $params['advertising__tag'] ) {
			$params['advertising_client'] = $params['advertising__client'];
			$params['advertising_tag'] = $params['advertising__tag'];
		}
		$delete_params = array(
			'skin',
			'logo__file',
			'logo__link',
			'logo__position',
			'logo__margin',
			'logo__hide',
			'advertising__tag',
			'advertising__client',
			'description',
		);
		foreach ( $delete_params as $delete_param ) {
			unset( $params[ $delete_param ] );
		}
		$params['version'] = 7;
		$response = jwplayer_api_call( '/players/create', $params );
		if ( jwplayer_api_response_ok( $response ) ) {
			$imported_players[ $player_id ] = $response['player']['key'];
		} else {
			jwplayer_log( 'ERROR CREATING IMPORTED PLAYER' );
			jwplayer_log( $params, true );
			jwplayer_log( $response, true );
		}
	}
	update_option( 'jwplayer_imported_players', $imported_players );
	jwplayer_import_check_redirect();
}

function jwplayer_import_playlists() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$imported_playlists = get_option( 'jwplayer_imported_playlists' );
	if ( ! $imported_playlists ) {
		$imported_playlists = array();
		add_option( 'jwplayer_imported_playlists', $imported_playlists );
	}
	$playlists = jwplayer_import_legacy_playlists();
	foreach ( $playlists as $playlist ) {
		if ( array_key_exists( $playlist->ID, $imported_playlists ) ) {
			continue;
		}
		$media_ids = explode( ',', get_post_meta( $playlist->ID, 'jwplayermodule_playlist_items', true ) );
		$media_hashes = array();
		foreach ( $media_ids as $media_id ) {
			$media_hash = jwplayer_media_hash( intval( $media_id ) );
			$media_hashes[] = $media_hash;
		}
		if ( empty( $media_hashes ) ) {
			continue;
		}

		$params = array(
			'title' => $playlist->post_title,
			'type' => 'manual',
		);
		$response = jwplayer_api_call( '/channels/create', $params );
		if ( jwplayer_api_response_ok( $response ) ) {
			$hash = $response['channel']['key'];
			$imported_playlists[ $playlist->ID ] = $hash;
			foreach ( $media_hashes as $media_hash ) {
				$params = array(
					'channel_key' => $hash,
					'video_key' => $media_hash,
				);
				$response = jwplayer_api_call( '/channels/videos/create', $params );
				if ( ! jwplayer_api_response_ok( $response ) ) {
					jwplayer_log( 'ERROR ADDING VIDEO TO PLAYLIST' );
					jwplayer_log( $params, true );
					jwplayer_log( $response, true );
				}
			}
		} else {
			jwplayer_log( 'ERROR CREATING NEW PLAYLIST' );
			jwplayer_log( $params, true );
			jwplayer_log( $response, true );
		}
	}
	update_option( 'jwplayer_imported_playlists', $imported_playlists );
	jwplayer_import_check_redirect();
}

function jwplayer_import_nr_of_players() {
	$imported_players = get_option( 'jwplayer_imported_players' );
	$player_ids = get_option( 'jwp6_players' );
	if ( ! $imported_players ) {
		return count( $player_ids );
	}
	$nr = 0;
	foreach ( $player_ids as $player_id ) {
		if ( ! array_key_exists( $player_id, $imported_players ) ) {
			$nr++;
		}
	}
	return $nr;
}

function jwplayer_import_nr_of_playlists() {
	$imported_playlists = get_option( 'jwplayer_imported_playlists' );
	$playlists = jwplayer_import_legacy_playlists();
	if ( ! $imported_playlists ) {
		return count( $playlists );
	}
	$nr = 0;
	foreach ( $playlists as $playlist ) {
		if ( ! array_key_exists( $playlist->ID, $imported_playlists ) ) {
			$nr++;
		}
	}
	return $nr;
}

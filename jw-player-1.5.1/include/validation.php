<?php

// Validate the settings for the default player
function jwplayer_validate_player( $player_key ) {
	$api_key = get_option( 'jwplayer_api_key' );
	$loggedin = ! empty( $api_key );
	if ( $loggedin ) {
		$response = jwplayer_api_call( '/players/list' );
		foreach ( $response['players'] as $i => $p ) {
			if ( $player_key === $p['key'] ) {
				return $player_key;
			}
		}
		return $response['players'][0]['key'];
	}
	return '';
}

// Function to validate for a valid content mask
function jwplayer_validate_content_mask( $dns_mask ) {
	$pattern = '/^([a-z0-9]([-a-z0-9]*[a-z0-9])?\\.)+((a[cdefgilmnoqrstuwxz]|aero|arpa)|(b[abdefghijmnorstvwyz]|biz)|(c[acdfghiklmnorsuvxyz]|cat|com|coop)|d[ejkmoz]|(e[ceghrstu]|edu)|f[ijkmor]|(g[abdefghilmnpqrstuwy]|gov)|h[kmnrtu]|(i[delmnoqrst]|info|int)|(j[emop]|jobs)|k[eghimnprwyz]|l[abcikrstuvy]|(m[acdghklmnopqrstuvwxyz]|mil|mobi|museum)|(n[acefgilopruz]|name|net)|(om|org)|(p[aefghklmnrstwy]|pro)|qa|r[eouw]|s[abcdeghijklmnortvyz]|(t[cdfghjklmnoprtvwz]|travel)|u[agkmsyz]|v[aceginu]|w[fs]|y[etu]|z[amw])$/i';
	if ( preg_match( $pattern, $dns_mask ) ) {
		return $dns_mask;
	}
	return '';
}

function jwplayer_validate_boolean( $value ) {
	if ( $value ) {
		return true;
	}
	return false;
}

function jwplayer_validate_custom_shortcode( $value ) {
	if ( in_array( $value, json_decode( JWPLAYER_CUSTOM_SHORTCODE_OPTIONS ), true ) ) {
		return $value;
	}
	return 'content';
}

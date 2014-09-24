<?php

// Get total # of providers
$providers_total = count( $list_providers );

// If total # of providers is equal to the # of providers in array or array
// is empty
if ( ( $providers_total == count( $providers ) ) || empty( $providers ) ) {
	// Update provider text to all providers
	$provider_text = 'All Providers';

	// Set providers to empty string
	$providers = '';
} else { // Not all providers
	// Update provider text to # of # selected
	$provider_text = count( $providers ) . ' of ' . $providers_total . ' selected';
}

// Get total # of channels
$channels_total = count( $list_channels );

// If total # of providers is equal to the # of providers in array or array
// is empty
if ( ( $channels_total == count( $channels ) ) || empty( $channels ) ) {
	// Update channel text to all providers
	$channel_text = "All Video Categories";
} else {
	// Update channel text to # of # selected
	$channel_text = count( $channels ) . ' of ' . $channels_total . ' selected';
}

// Get connector ID from API
$id = Grabpress_API::get_connector_id();

// Get player data and ID from API
$player_json = Grabpress_API::call( 'GET', '/connectors/' . $id . '/?api_key=' . Grabpress::$api_key );
$player_data = json_decode( $player_json, true );
$player_id = isset( $player_data['connector']['ctp_embed_id'] ) ? $player_data['connector']['ctp_embed_id'] : '';
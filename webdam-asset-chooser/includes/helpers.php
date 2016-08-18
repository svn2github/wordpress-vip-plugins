<?php
/**
 * GENERIC HELPERS
 */

/**
 * Determine the current protocol used
 *
 * @param null
 *
 * @return string The site protocol for use in a URL, e.g. http:// or https://
 */
function webdam_get_site_protocol() {

	$protocol = 'http://';

	if ( is_ssl() ) {
		$protocol = 'https://';
	}

	return $protocol;
}

/**
 * Getter function to obtain the admin settings page url
 *
 * @return string Unescaped url
 */
function webdam_get_admin_settings_page_url() {

	if ( class_exists( 'Webdam\Admin' ) ) {
		$settings = Webdam\Admin::get_instance();

		return $settings->get_admin_settings_page_url();
	}

	return false;
}

/**
 * Getter function to obtain the admin set cookie page url
 *
 * @return string Unescaped url
 */
function webdam_get_admin_set_cookie_page_url() {

	if ( class_exists( 'Webdam\Admin' ) ) {
		$settings = Webdam\Admin::get_instance();

		return $settings->get_admin_set_cookie_page_url();
	}

	return false;
}

/**
 * Get the WebDAM settings from the options table
 *
 * @param null
 *
 * @return array|false Array of settings values if available, false otherwise
 */
function webdam_get_settings() {

	$settings = get_option( 'webdam_settings' );

	if ( null !== $settings ) {
		return $settings;
	}

	return false;
}

/**
 * API HELPERS
 */

/**
 * Determine if the WebDAM API integration is enabled
 *
 * @param null
 *
 * @return bool True when the API is enabled. False otherwise.
 */
function webdam_api_is_enabled() {

	$settings = webdam_get_settings();

	if ( ! empty( $settings['enable_api'] ) ) {
		return true;
	}

	return false;
}

/**
 * Is the WebDAM API authenticated?
 *
 * @param null
 *
 * @return bool True if the API is authenticated, false if it is not.
 */
function webdam_api_is_authenticated() {

	if ( class_exists( 'Webdam\API' ) ) {

		$api = Webdam\API::get_instance();

		if ( $api->is_authenticated() ) {
			return true;
		}
	}

	return false;
}

/**
 * Determine if the Asset Chooser API login setting is enabled
 *
 * @param null
 *
 * @return bool True when the API login is enabled. False otherwise.
 */
function webdam_asset_chooser_api_login_is_enabled() {

	$settings = webdam_get_settings();

	if ( ! empty( $settings['enable_asset_chooser_api_login'] ) ) {
		return true;
	}

	return false;
}


/**
 * Determine if the WebDAM API sideloading is enabled
 *
 * @param null
 *
 * @return bool True when sideloading is enabled. False otherwise.
 */
function webdam_api_sideloading_is_enabled() {

	$settings = webdam_get_settings();

	if ( ! empty( $settings['enable_sideloading'] ) ) {
		return true;
	}

	return false;
}

/**
 * Get the authorization url
 *
 * @param null
 *
 * @return string|bool The authorization URL. False on failure.
 */
function webdam_api_get_authorization_url() {

	if ( class_exists( 'Webdam\API' ) ) {

		$api = Webdam\API::get_instance();

		return $api->get_authorization_url();
	}

	return false;
}

/**
 * Get the current access_token
 *
 * @param null
 *
 * @return string|bool The access token. False on failure.
 */
function webdam_api_get_current_access_token() {

	if ( class_exists( 'Webdam\API' ) ) {

		$api = Webdam\API::get_instance();

		return $api->get_current_access_token();
	}

	return false;
}

/**
 * Get the current refresh_token
 *
 * @param null
 *
 * @return string|bool The refresh token. False on failure.
 */
function webdam_api_get_current_refresh_token() {

	if ( class_exists( 'Webdam\API' ) ) {

		$api = Webdam\API::get_instance();

		return $api->get_current_refresh_token();
	}

	return false;
}

/**
 * Fetch asset metadata from WebDAM
 *
 * @param array $asset_ids An array of assets to fetch meta for.
 *                         e.g. array( XXXXXXX, XXXXXX, ... )
 *
 * @return array|bool Array of metadata on success. False on failure.
 */
function webdam_api_get_asset_metadata( $asset_ids = array() ) {

	if ( class_exists( 'Webdam\API' ) ) {

		$asset_ids = (array) $asset_ids;

		$asset_meta = Webdam\API::get_instance()->get_asset_metadata( $asset_ids );

		if ( $asset_meta ) {
			return $asset_meta;
		}
	}

	return false;
}

// EOF
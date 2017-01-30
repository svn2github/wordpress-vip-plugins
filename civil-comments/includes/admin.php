<?php
/**
 * Admin Settings page
 *
 * @package Civil_Comments
 */

namespace Civil_Comments;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_init', __NAMESPACE__ . '\\register_settings' );
/**
 * Register setting.
 */
function register_settings() {
	register_setting( 'civil_comments', 'civil_comments', __NAMESPACE__ . '\\sanitize_civil_comments_setting' );
}

/**
 * Sanitize civil comments settings on save.
 *
 * @param  array $settings Settings to save.
 * @return array
 */
function sanitize_civil_comments_setting( $settings ) {
	$sanitized_setting = array();

	$sanitized_setting['enable'] = ! empty( $settings['enable'] ) ? absint( $settings['enable'] ) : '';
	$sanitized_setting['hide'] = ! empty( $settings['hide'] ) ? absint( $settings['hide'] ) : '';
	$sanitized_setting['publication_slug'] = ! empty( $settings['publication_slug'] ) ? sanitize_text_field( $settings['publication_slug'] ) : '';
	$sanitized_setting['lang'] = ! empty( $settings['lang'] ) ? sanitize_text_field( $settings['lang'] ) : 'en_US';
	$sanitized_setting['start_date'] = ! empty( $settings['start_date'] ) ? sanitize_text_field( $settings['start_date'] ) : '';
	$sanitized_setting['enable_sso'] = ! empty( $settings['enable_sso'] ) ? absint( $settings['enable_sso'] ) : false;
	$sanitized_setting['sso_secret'] = ! empty( $settings['sso_secret'] ) ? sanitize_text_field( $settings['sso_secret'] ) : false;

	return $sanitized_setting;
}

add_action( 'admin_menu', __NAMESPACE__ . '\\add_settings_page' );
/**
 * Add settings page to Comments menu.
 */
function add_settings_page() {
	add_submenu_page(
		'edit-comments.php',
		__( 'Civil Comments', 'civil-comments' ),
		__( 'Civil Comments', 'civil-comments' ),
		'moderate_comments', // @TODO: Add cap filter.
		'civil-comments',
		__NAMESPACE__ . '\\render_settings_page'
	);
}

/**
 * Render the settings page.
 */
function render_settings_page() {
	require_once CIVIL_PLUGIN_DIR . '/templates/settings.php';
}

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts' );
/**
 * Enque admin scripts.
 *
 * @param  string $hook Admin page hook.
 */
function enqueue_scripts( $hook ) {
	if ( 'comments_page_civil-comments' !== $hook ) {
		return;
	}

	wp_enqueue_style( 'jquery-ui-base', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.24/themes/base/jquery-ui.css', false, '1.8.24', false );
	wp_enqueue_script( 'cc-timepicker', CIVIL_PLUGIN_URL . '/assets/js/vendor/jquery.timepicker.js', array( 'jquery', 'jquery-ui-slider', 'jquery-ui-datepicker' ), '0.9.7', true );
	wp_enqueue_script( 'civil-comments', CIVIL_PLUGIN_URL . '/assets/js/civil-comments.js', array( 'cc-timepicker' ), CIVIL_VERSION, true );
}

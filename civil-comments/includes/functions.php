<?php
/**
 * Civil Comments functions
 *
 * @package Civil_Comments
 */

namespace Civil_Comments;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wrapper to set defaults when getting plugin settings.
 *
 * @return array
 */
function get_settings() {
	$defaults = array(
		'enable'           => '',
		'lang'             => 'en_US',
		'publication_slug' => '',
		'start_date'       => '',
		'enable_sso'       => '',
		'sso_secret'       => '',
	);

	$settings = get_option( 'civil_comments', array() );

	return wp_parse_args( $settings, $defaults );
}

/**
 * Determines whether to show civil comments on a specific post.
 *
 * @param  WP_Post $post A post object.
 * @return boolean
 */
function can_replace( $post ) {
	$replace = true;

	if ( ! ( is_singular() && 'open' === $post->comment_status ) ) {
		$replace = false;
	}

	// Only show on publish or private posts.
	if ( ! in_array( $post->post_status, array( 'publish', 'private' ), true ) ) {
		$replace = false;
	}

	// Only show on password protected posts once password has been entered.
	if ( post_password_required( $post ) ) {
		$replace = false;
	}

	$settings = get_settings( 'civil_comments' );
	$start_date = ! empty( $settings['start_date'] ) ? $settings['start_date'] : '';

	// Only show on posts past the start date.
	if ( ! empty( $start_date )
		&& mysql2date( 'U', $post->post_date ) < strtotime( $start_date ) ) {
		$replace = false;
	}

	/**
	* Allows user to override whether to show civil comments on a specific post.
	*
	* @since 0.1.0
	*
	* @param bool   $replace Boolean can replace comments or not.
	* @param object $post    Post object of the post/page being tested.
	*/
	return apply_filters( 'civil_can_replace', $replace, $post );
}

/**
 * Determines whether Civil Comments is enabled and has a publication slug.
 *
 * @return boolean
 */
function is_enabled() {
	$settings = get_settings( 'civil_comments' );
	$installed = ! empty( $settings['publication_slug'] )? true : false;
	$enabled = isset( $settings['enable'] ) && (bool) $settings['enable'] ? true : false;

	/**
	* Allows user to override whether Civil Comments is enabled.
	*
	* @since 0.1.0
	*
	* @param mixed $replace Boolean is enabled or not.
	*/
	return apply_filters( 'civil_comments_enabled', $enabled && $installed );
}

/**
 * Generate a UUID.
 *
 * Used for the jti in JWT.
 *
 * @return string
 */
function generate_uuid() {
	return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0x0fff ) | 0x4000,
		mt_rand( 0, 0x3fff ) | 0x8000,
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
	);
}

/**
 * Get JWT token for SSO.
 *
 * @uses JWT
 *
 * @param  WP_User $user WP User object for the user to auth.
 * @param  string  $key  Secret key from Civil Comments.
 * @return string        Signed JWT token.
 */
function get_jwt_token( $user, $key ) {
	include_once CIVIL_PLUGIN_DIR . '/includes/vendor/JWT.php';
	$expires = 86400;

	$payload = array(
		'exp'        => time() + (int) $expires,
		'iat'        => time(),
		'jti'        => generate_uuid(),
		'id'         => $user->ID,
		'name'       => $user->display_name,
		'email'      => $user->user_email,
		'avatar_url' => get_avatar_url( $user ),
	);

	try {
		$token = JWT::encode( $payload, $key, 'HS256' );
	} catch ( Exception $e ) {
		return null;
	}

	return $token;
}

add_filter( 'comments_template', __NAMESPACE__ . '\\comments_template' );
/**
 * Load the custom Civil Comments template.
 *
 * @param  string $template Path to a template file.
 * @return string
 */
function comments_template( $template ) {
	global $post;

	if ( empty( $post ) ) {
		return $template;
	}

	if ( ! is_enabled() ) {
		return $template;
	}

	if ( ! can_replace( $post ) ) {
		return $template;
	}

	return locate( 'templates/civil-comments.php' );
}

/**
 * Locate the Civil Comments output template.
 *
 * Checks the theme first before loading from the plugin.
 *
 * @param  string $template Template path.
 * @return string
 */
function locate( $template ) {
	// Check in active theme for templates/civil-comments.php.
	$found = locate_template( array( $template ) );

	// Include template from plugin, if exists.
	if ( ! $found ) {
		$file = CIVIL_PLUGIN_DIR . '/' . $template;
		if ( file_exists( $file ) ) {
			$found = $file;
		}
	}

	return $found;
}

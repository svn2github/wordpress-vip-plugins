<?php

/**
 * We have a special version of this plugin running for WordPress.com VIP
 *
 * - It uses the settings API to add itself to the mobile-options page
 * - It trusts the jetpack_is_mobile() function implicitly
 * - It knows that Jetpack functions will always be available
 */

/**
 * Load up the settings fields
 */
function wpcom_jp_mini_featured_init() {
	add_settings_field( 'jp_mini_featured_evwhere', __( 'Featured Images' ), 'wpcom_jp_mini_featured_evwhere', 'mobile-options', 'wp_mobile_settings_theme' );
	register_setting( 'mobile-options', 'jp_mini_featured_evwhere', 'intval' );
}
add_action( 'admin_init', 'wpcom_jp_mini_featured_init' );

/**
 * Maybe conditionally add the filter to add the featured image to the title
 * area of minileven.
 */
function wpcom_tweakjp_maybe_add_filter() {

	// Bail if not mobile
	if ( ! jetpack_is_mobile() )
		return;

	// Do we want to display the Featured images only on the home page?
	if ( ! is_home() && get_option( 'jp_mini_featured_evwhere' ) )
		return;

	add_filter( 'the_title', 'wpcom_tweakjp_minileven_featuredimage' );
}
add_action( 'wp_head', 'tweakjp_maybe_add_filter' );

/**
 * Maybe conditionally add the featured image to the title area of minileven
 *
 * @param string $title
 * @return string
 */
function wpcom_tweakjp_minileven_featuredimage( $title = '' ) {

	// Maybe add the thumbnail after the title
	if ( has_post_thumbnail() && in_the_loop() ) {
		$title = $title . get_the_post_thumbnail();
	}

	return $title;
}

/**
 * Admin bar for logged out users setting field
 */
function wpcom_jp_mini_featured_evwhere() {
?>
	
	<input id="jp_mini_featured_evwhere" name="jp_mini_featured_evwhere" type="checkbox" value="1" <?php checked( true, (bool) get_option( 'jp_mini_featured_evwhere' ), true ); ?> />
	<label for="jp_mini_featured_evwhere"><?php _e( 'Also show on single posts and archives', 'jetpack' ); ?></label>

<?php
}

<?php
/*
 * Plugin Name: Jetpack Mobile Theme Featured images
 * Plugin URI: http://wordpress.org/extend/plugins/jetpack-mobile-theme-featured-images/
 * Description: Adds Featured Images before the content on the home page, in Jetpack Mobile theme
 * Author: Jeremy Herve
 * Version: 1.4
 * Author URI: http://jeremyherve.com
 * License: GPL2+
 * Text Domain: jetpack
 */

// Check if we are on mobile
// Props @saracannon http://ran.ge/2012/12/05/parallax-and-mobile/
function tweakjp_is_mobile() {
    if ( ! class_exists( 'Jetpack_User_Agent_Info' ) )
    	return false;
    if ( !isset($_SERVER["HTTP_USER_AGENT"]) || (isset($_COOKIE['akm_mobile']) && $_COOKIE['akm_mobile'] == 'false') )
		return false;

    $ua_info = new Jetpack_User_Agent_Info();
    return ( jetpack_is_mobile() );
}

// Let's add the Featured Image
function tweakjp_maybe_add_filter() {
	
	// On mobile?
	if ( tweakjp_is_mobile() ) {
	
		// Do we want to display the Featured images only on the home page?
		if ( !is_home() && get_option( 'jp_mini_featured_evwhere' ) != '1' ) {
			return;
			
		// Add the image
		} else {
			add_filter( 'the_title', 'tweakjp_minileven_featuredimage' );
		}
	
	}
}
add_action( 'wp_head', 'tweakjp_maybe_add_filter' );

function tweakjp_minileven_featuredimage( $title ) {
	$tweak = has_post_thumbnail() && in_the_loop();
	$featured_content = ( $tweak ) ? get_the_post_thumbnail() : '';

	return $title . $featured_content;
}

/*
 * Options page
 */

add_action( 'admin_init', 'jp_mini_featured_init' );

// Init plugin options
function jp_mini_featured_init() {
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'jp_mini_featured_action_links' );
	
	register_setting( 'jp_mini_featured_options', 'jp_mini_featured_strings', 'jp_mini_featured_validate' );
	
	// Add settings to the Minileven Options page
	add_action( 'jetpack_module_configuration_screen_minileven', 'jp_mini_featured_configuration_load' );
	add_action( 'jetpack_module_configuration_screen_minileven', 'jp_mini_featured_do_page' );
}

// Plugin settings link
function jp_mini_featured_action_links( $actions ) {
	return array_merge(
		array( 'settings' => sprintf( '<a href="admin.php?page=jetpack&configure=minileven">%s</a>', __( 'Settings', 'jetpack' ) ) ),
		$actions
	);
	return $actions;
}

// Prepare option page
function jp_mini_featured_configuration_load() {
	if ( isset( $_POST['action'] ) && $_POST['action'] == 'save_options' && $_POST['_wpnonce'] == wp_create_nonce( 'jp_mini_featured' ) ) {

		update_option( 'jp_mini_featured_evwhere', ( isset( $_POST['jp_mini_featured_evwhere'] ) ) ? '1' : '0' );

		Jetpack::state( 'message', 'module_configured' );
		wp_safe_redirect( Jetpack::module_configuration_url( 'minileven' ) );
		exit;
	}
}

// Draw the menu page itself
function jp_mini_featured_do_page() {
	$feat_home = ( '1' == get_option( 'jp_mini_featured_evwhere' ) ) ? 1 : 0;
	?>
	<h3>Featured Images</h3>
	<form method="post">
		<input type="hidden" name="action" value="save_options" />
		<?php wp_nonce_field( 'jp_mini_featured' ); ?>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e( 'Featured Images', 'jetpack' ); ?></th>
			<td>
				<label>
					<input name="jp_mini_featured_evwhere" type="checkbox" value="1" <?php checked( 1, $feat_home, true ); ?> />
					<?php _e ( 'Display Featured Images on Post Pages as well', 'jetpack' ); ?>
				</label>
			</td>
			</tr>
		</table>
		<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e( 'Save Configuration', 'jetpack' ) ?>" />
		</p>
	</form>
	<?php	
}

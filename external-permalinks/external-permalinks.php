<?php
/*
Plugin Name: External Permalinks
Plugin URI: http://vip.wordpress.com
Description: Allows you to point WordPress pages or posts to a URL of your choosing. Inspired by Page Links To by Mark Jaquith http://txfx.net/wordpress-plugins/page-links-to/
Version: 1.0 
Author: Erick Hitter, C. Murray Consulting
Author URI: http://www.cmurrayconsulting.com/
*/

function external_permalinks_filter_post_permalink( $permalink, $post ) {
	if( $external_link = get_post_meta( $post->ID, '_external_permalink', true ) ) {
		$permalink = $external_link;
	}
	
	return $permalink;
}
add_filter( 'post_link', 'external_permalinks_filter_post_permalink', 1, 2 );

function external_permalinks_filter_page_permalink( $link, $id ) {
	if( $external_link = get_post_meta( $id, '_external_permalink', true ) ) {
		$link = $external_link;
	}

	return $link;	
}
add_filter( 'page_link', 'external_permalinks_filter_page_permalink', 1, 2 );

/*
 * Redirect to external link if page requested directly.
 * @uses get_post_meta, wp_redirect
 * @action pre_get_posts
 * @return null
 */
function external_permalinks_redirect_postpage_request( $query ) {
	if( $query->is_single && $link = get_post_meta( $query->query[ 'p' ], '_external_permalink', true ) ) {
		wp_redirect( $link, 307 );
		exit;
	}
}
add_action( 'pre_get_posts', 'external_permalinks_redirect_postpage_request', 1 );

/*
 * Add meta box to posts and pages to set external link
 * @uses add_meta_box
 * @action admin_init
 * @return null
 */
function external_permalinks_add_meta_box() {
	add_meta_box( 'external-permalinks', 'External Link', 'external_permalinks_meta_box', 'post', 'normal' );
	add_meta_box( 'external-permalinks', 'External Link', 'external_permalinks_meta_box', 'page', 'normal' );
}
add_action( 'admin_init', 'external_permalinks_add_meta_box' );

/*
 * Render meta box to set external link
 * @uses get_post_meta, wp_create_nonce
 * @return html
 */
function external_permalinks_meta_box( $post ) {
	$link = esc_url( get_post_meta( $post->ID, '_external_permalink', true ) );
?>
	<div class="inside">
		<label for="external-permalink-url"><?php _e( 'Destination Address:' ); ?></label><br />
		<input name="external-permalink-url" id="external-permalink-url" type="text" value="<?php echo $link; ?>" style="width:99%;" />
		<p><strong><?php printf( __( 'Be sure to include %s before the destination address.' ), '<em>http://</em>' );?></strong></p>
		<p><?php _e( 'To restore the original permalink, remove the link entered above.' ); ?></p>
		<input type="hidden" name="external-permalink-url-old" value="<?php echo $link; ?>" />
		<input type="hidden" name="external-permalink-nonce" value="<?php echo wp_create_nonce( 'external-permalinks' ); ?>" />
	</div>
<?php
}

/*
 * Save external link, or delete if one was set.
 * @uses wp_verify_nonce, $post, esc_url, update_post_meta, delete_post_meta
 * @action save_post
 * @return null
 */
function external_permalinks_save_link() {
	if( wp_verify_nonce( $_POST[ 'external-permalink-nonce' ], 'external-permalinks' ) ) {		
		global $post;
		if( !empty( $_POST[ 'external-permalink-url' ] ) && $_POST[ 'external-permalink-url' ] != $_POST[ 'external-permalink-url-old' ] ) {	
			$link = esc_url( trim( $_POST[ 'external-permalink-url' ] ) );
			update_post_meta( $post->ID, '_external_permalink', $link );
		}
		elseif( empty( $_POST[ 'external-permalink-url' ] ) && !empty( $_POST[ 'external-permalink-url-old' ] ) ) {
			delete_post_meta( $post->ID, '_external_permalink' );
		}
	}
}
add_action( 'save_post', 'external_permalinks_save_link' );

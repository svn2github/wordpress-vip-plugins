<?php
/*
Plugin Name: External Permalinks Redux
Plugin URI: 
Description: Allows users to point WordPress objects (posts, pages, custom post types) to a URL of your choosing. Inspired by and backwards-compatible with <a href="http://txfx.net/wordpress-plugins/page-links-to/">Page Links To</a> by Mark Jaquith. Written for use on WordPress.com VIP.
Version: 1.0.1
Author: Erick Hitter (Oomph, Inc.)
Author URI: http://www.thinkoomph.com/
*/

class external_permalinks_redux {
	/*
	 * Class variables
	 */
	var $ns = 'epr';
	
	var $meta_key_target;
	var $meta_key_type;
	
	/*
	 * Register actions and filters, set meta key
	 * @uses add_action, add_filter, apply_filters
	 * @return null
	 */
	function __construct() {
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		add_action( 'save_post', array( $this, 'action_save_post' ) );
		
		add_filter( 'post_link', array( $this, 'filter_post_permalink' ), 1, 2 );
		add_filter( 'post_type_link', array( $this, 'filter_post_permalink' ), 1, 2 );
		add_filter( 'page_link', array( $this, 'filter_page_link' ), 1, 2 );
		add_action( 'wp', array( $this, 'action_wp' ) );
		
		$this->meta_key_target = apply_filters( 'epr_meta_key_target', '_links_to' );
		$this->meta_key_type = apply_filters( 'epr_meta_key_target', '_links_to_type' );
	}
	
	/*
	 * Add meta box
	 * @uses apply_filters, add_meta_box
	 * @action admin_init
	 * @return null
	 */
	function action_admin_init() {
		$post_types = apply_filters( 'epr_post_types', array( 'post', 'page' ) );
		
		if( !is_array( $post_types ) )
			return;
		
		foreach( $post_types as $post_type )
			add_meta_box( 'external-permalinks-redux', 'External Permalinks Redux', array( $this, 'meta_box' ), $post_type, 'normal' );
	}
	
	
	/*
	 * Render meta box
	 * @param object $post
	 * @uses _e, esc_url, get_post_meta, selected, wp_create_nonce
	 * @return string
	 */
	function meta_box( $post ) {
		$type = get_post_meta( $post->ID, $this->meta_key_type, true );
	?>
		<p>
			<label for="epr-url"><?php _e( 'Destination Address:', $this->ns ); ?></label><br />
			<input name="<?php echo $this->meta_key_target; ?>_url" class="large-text code" id="epr-url" type="text" value="<?php echo esc_url( get_post_meta( $post->ID, $this->meta_key_target, true ) ); ?>" />
		</p>
		
		<p class="description"><?php _e( 'To restore the original permalink, remove the link entered above.', $this->ns ); ?></p>
		
		<p>&nbsp;</p>
		
		<p>
			<label for="epr-type"><?php _e( 'Redirect Type:', $this->ns ); ?></label>
			<select name="<?php echo $this->meta_key_target; ?>_type" id="epr-type">
				<option value="302"<?php selected( 302, intval( $type ), true ); ?>>Temporary (302)</option>
				<option value="301"<?php selected( 301, intval( $type ), true ); ?>>Permanent (301)</option>
			</select>
		</p>
		
		<input type="hidden" name="<?php echo $this->meta_key_target; ?>_nonce" value="<?php echo wp_create_nonce( 'external-permalinks-redux' ); ?>" />
	<?php
	}
	
	/*
	 * Save meta box input
	 * @param int $post_id
	 * @uses wp_verify_nonce, esc_url_raw, update_post_meta, delete_post_meta
	 * @action save_post
	 * @return null
	 */
	function action_save_post( $post_id ) {
		if( isset( $_POST[ $this->meta_key_target . '_nonce' ] ) && wp_verify_nonce( $_POST[ $this->meta_key_target . '_nonce' ], 'external-permalinks-redux' ) ) {
			//Target
			$url = esc_url_raw( $_POST[ $this->meta_key_target . '_url' ] );
			
			if( !empty( $url ) )
				update_post_meta( $post_id, $this->meta_key_target, $url );
			else
				delete_post_meta( $post_id, $this->meta_key_target, $url );
			
			//Redirect type
			$type = intval( $_POST[ $this->meta_key_target . '_type' ] );
			
			if( !empty( $url ) && ( $type == 301 || $type == 302 ) )
				update_post_meta( $post_id, $this->meta_key_type, $type );
			else
				delete_post_meta( $post_id, $this->meta_key_type );
		}
	}
	
	/*
	 * Filter post and custom post type permalinks
	 * @param string $permalink
	 * @param object $post
	 * @uses get_post_meta
	 * @filter post_link, post_type_link
	 * @return string
	 */
	function filter_post_permalink( $permalink, $post ) {
		if( $external_link = get_post_meta( $post->ID, $this->meta_key_target, true ) )
			$permalink = $external_link;
		
		return $permalink;
	}
	
	/*
	 * Filter page permalinks
	 * @param string $link
	 * @param int $id
	 * @uses get_post_meta
	 * @filter page_link
	 * @return string
	 */
	function filter_page_link( $link, $id ) {
		if( $external_link = get_post_meta( $id, $this->meta_key_target, true ) )
			$link = $external_link;
	
		return $link;	
	}
	
	/*
	 * Redirect to external link if object requested directly.
	 * @uses get_post_meta, wp_redirect
	 * @action pre_get_posts
	 * @return null
	 */
	function action_wp() {
		global $post;
		
		if( is_singular() && ( $link = get_post_meta( $post->ID, $this->meta_key_target, true ) ) ) {
			$type = intval( get_post_meta( $post->ID, $this->meta_key_type, true ) );
			if( !$type )
				$type = 302;
			
			wp_redirect( $link, $type );
			exit;
		}
	}
}
global $external_permalinks_redux;
if( !is_a( $external_permalinks_redux, 'external_permalinks_redux' ) )
	$external_permalinks_redux = new external_permalinks_redux;

/*
 * Wrapper for meta box function
 * Can be used as an alternative to the epr_post_types filter found in the plugin classes's action_admin_init function.
 * @param object $post
 * @uses $external_permalinks_redux
 * @return string
 */
function external_permalinks_redux_meta_box( $post ) {
	global $external_permalinks_redux;
	if( !is_a( $external_permalinks_redux, 'external_permalinks_redux' ) )
		$external_permalinks_redux = new external_permalinks_redux;
	
	$external_permalinks_redux->meta_box( $post );
}
?>
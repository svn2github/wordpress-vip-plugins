<?php

/**
 * PMC Edit Lock Marker class
 * 
 * @author Amit Gupta
 * 
 * @since 2012-07-02 Amit Gupta
 * @version 2012-07-20 Amit Gupta
 */

class PMC_Edit_Lock_Marker {
	
	const plugin_id = "pmc-edit-lock-marker";
	
	/**
	 * This var contains the only class instance in existence
	 * 
	 * @var obj Contains class instance
	 */
	private static $_instance;
	
	/**
	 * constructor private, singleton implemented
	 */
	private function __construct() {
		//setup our style/script enqueuing
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_stuff' ) );
		//setup ajax function handler
		add_action( 'wp_ajax_pmc-post-edit-lock-marker', array( $this, 'fetch_locked_posts' ) );
	}
	
	/**
	 * function to give the singleton instance if exists or make one
	 */
	public static function get_instance() {
		if( ! is_a(self::$_instance, __CLASS__) ) {
			$class_name = __CLASS__;
			self::$_instance = new $class_name();
		}
		return self::$_instance;
	}

	/**
	 * function to enqueue stuff in wp-admin
	 */
	public function enqueue_stuff($hook_suffix) {
		if( ! is_admin() || $hook_suffix !== 'edit.php' ) {
			//either page is not in wp-admin or not wp-admin/edit.php, so bail out
			return false;
		}
		
		//load stylesheet
		wp_enqueue_style( self::plugin_id, plugins_url('styles/pmc-edit-lock-marker.css', __FILE__), false );
		//load script
		wp_enqueue_script( self::plugin_id, plugins_url('js/pmc-edit-lock-marker.js', __FILE__), array('jquery') );
		
		wp_localize_script( self::plugin_id, 'pmc_edit_lock_marker', array(
			'nonce' => wp_create_nonce(self::plugin_id . '-nonce')
		) );
	}


	/**
	 * Check to see if a post is currently being edited by another user.
	 *
	 * @param int $post_id ID of the post to check for editing
	 * @return bool|int False: not locked or locked by current user. Int: user ID of user with lock.
	 * 
	 * @since 0.1
	 * @version 0.1
	 */
	protected function _check_post_lock( $post_id ) {
		$post_id = intval( $post_id );
		$post = get_post( $post_id );
		if ( ! $post ) {
			return false;
		}
		
		$lock = get_post_meta( $post->ID, '_edit_lock', true );
		if ( ! $lock ) {
			return false;
		}
		
		$lock = explode( ':', $lock );
		$time = $lock[0];
		$user = isset( $lock[1] ) ? $lock[1] : get_post_meta( $post->ID, '_edit_last', true );
		
		$time_window = apply_filters( 'wp_check_post_lock_window', AUTOSAVE_INTERVAL * 2 );
		
		if ( $time && ( $time > (time() - $time_window) ) && $user != get_current_user_id() ) {
			return $user;
		}
		
		unset( $time_window, $user, $time, $lock, $post );
		
		return false;
	}
	
	/**
	 * function to handle AJAX requests, check if the post IDs are locked or not
	 * and return the IDs which are locked (if any) by other users
	 */
	public function fetch_locked_posts() {
		check_ajax_referer(self::plugin_id . '-nonce', '_pmc_elm_ajax_nonce');
		$post_ids = ( isset($_POST['post_ids']) && ! empty($_POST['post_ids']) ) ? explode( ',', sanitize_text_field($_POST['post_ids']) ) : array();
		if ( empty($post_ids) || ! is_array($post_ids) || ! current_user_can( 'edit_posts' ) ) {
			die();	//used die() instead of wp_die() as it prints -1 in AJAX output which screws up the data format
		}
		
		$locked_posts = array();
		//loop through the array & check each post's lock status
		foreach( $post_ids as $post_id ) {
			$post_id = intval($post_id);
			if( empty($post_id) || in_array($post_id, $locked_posts) ) {
				//ID is empty or already checked, skip to next iteration
				continue;
			}
			$lock_status = $this->_check_post_lock($post_id);
			if( $lock_status !== false && intval($lock_status) > 0 ) {
				//post is locked by another user, bag the ID
				$locked_posts[] = $post_id;
			}
			unset($lock_status);
		}
		$response = array(
			'nonce' => wp_create_nonce(self::plugin_id . '-nonce'),		//lets refresh the nonce for our next ajax call
			'posts' => implode(',', $locked_posts)		//convert locked post IDs to string
		);
		
		header("Content-Type: application/json");
		echo json_encode( $response );		//we want json
		unset( $locked_posts, $response );	//clean up
		die();	//used die() instead of wp_die() as it prints -1 in AJAX output which screws up the data format
	}
	
//end of class
}


//EOF
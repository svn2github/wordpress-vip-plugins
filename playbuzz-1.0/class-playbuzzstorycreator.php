<?php
/**
 * Created by PhpStorm.
 * User: Lior
 * Date: 27/11/2016
 * Time: 17:45
 */


/**
 * Security check
 * Exit if file accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Playbuzz Story Creator
 * Add playbuzz story creator post.
 *
 * @since 0.9.5
 */
class PlaybuzzStoryCreator {

	private $options;

	private $post_type = 'post';

	private $pb_story_post_identify_key = 'pb_is_story_post';

	private $pb_story_post_item_id_key = 'pb_item_id';

	private $pb_story_post_identify_value = 'true';

	private $pb_resource_version = '1.0.0';


	public function __construct() {

		// get the playbuzz plugin options.
		$this -> options = (array) get_option( 'playbuzz' );

		//initialize Playbuzz story creator on frontend
		// note - using wp_enqueue_scripts - only hook where we can inject scripts to the header
		// and we have access to the global $post param.
		// @docs - https://developer.wordpress.org/reference/functions/wp_enqueue_script/
		add_action( 'wp_enqueue_scripts', array( $this, 'init_frontend' ) );

		//add the new story link to admin bar.
		// note - this is out the is_admin scope, in case of admin view the content page.
		add_action( 'admin_bar_menu', array( $this, 'add_story_links_admin_bar' ), 70 );

		// checks if the dashboard or the administration panel is displayed.
		if ( ! is_admin() ) {
			return;
		}

		//add the new story link to admin menu.
		add_action( 'admin_menu', array( $this, 'add_story_links_admin_menu' ) );

		//initialize Playbuzz story creator on admin
		// note - using admin_enqueue_scripts - only hook where we can inject scripts to the header
		// and we have access to the global $post param.
		// @docs - https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
		add_action( 'admin_enqueue_scripts', array( $this, 'init_post_page_admin' ) );

		//register action to handle post data.
		add_action( 'wp_ajax_save_post_action', array( $this, 'save_post_action_callback' ) );

	}

	/**
	 * frontend screen init
	 */
	public function init_frontend() {

		// use the global post object
		global $post;

		// checks if the post object is available.
		if ( ! $post ) {
			return;
		}

		//make sure edit story post
		if ( ! $this -> is_pb_story_post( $post ) ) {
			return;
		}

		//set the embed code to the front end page
		add_filter( 'the_content', array( $this, 'set_post_template_on_frontend' ) );

		$site_jshead = ( ( ( array_key_exists( 'jshead', $this -> options ) ) && ( '1' == $this -> options['jshead'] ) ) ? true : false );

		if ( $site_jshead ) {

			//load the feed.js to the front end head section.
			$this -> load_resource_frontend();

		}

	}

	/**
	 * admin panel init
	 */
	public function init_post_page_admin( $hook ) {

		// make sure to load on post page only.
		if ( 'post-new.php' != $hook && 'post.php' != $hook ) {
			return;
		}

		// checks if user can edit posts or pages
		if ( ! $this -> user_have_access() ) {
			return;
		}

		// use the global post object
		global $post;

		// checks if the post object is available.
		if ( ! $post ) {
			return;
		}

		//make sure edit story post
		if ( ! $this -> is_pb_story_post( $post ) ) {
			return;
		}

		//add story creator placeholder template.
		//important - inject the template here (server) made for prevent the page flick
		// when edit this from the client.
		add_action( 'edit_form_after_title', array( $this, 'set_story_creator_template' ) );

		//set default meta data to story post.
		$this -> set_story_post_meta(
			$post -> ID,
			$this -> pb_story_post_identify_key,
			$this -> pb_story_post_identify_value
		);

		// load js and css needed for pb story creator to the end of the body.
		$this -> load_resource( $post, $hook );

	}


	/**
	 * load frontend resources
	 */
	public function load_resource_frontend() {
		$embed_codes = new PlaybuzzEmbedCodes();
		wp_enqueue_script( $embed_codes -> item_script_handle, $embed_codes -> item_script_url , null , null, false );
	}

	/**
	 * load scripts and styles.
	 * @param $post
	 */
	public function load_resource( $post, $hook ) {

		$footer = true;
		$version = $this -> pb_resource_version;

		//register css
		wp_register_style( 'pb-story-creator', plugins_url( 'css/story-creator/pb-story-creator.css',     __FILE__ ), false, $version, false );
		wp_enqueue_style( 'pb-story-creator' );

		//register js
		wp_register_script( 'pb-creator-sdk', '//cdn.playbuzz.com/creator-sdk/creator-sdk.js' , '', '', false );
		wp_enqueue_script( 'pb-creator-sdk' );

		wp_register_script( 'pb-story-creator-model', plugins_url( 'js/story-creator/pb-story-creator-model.js', __FILE__ ), array( 'jquery' ), $version, $footer );
		wp_enqueue_script( 'pb-story-creator-model' );

		wp_register_script( 'pb-story-creator-view', plugins_url( 'js/story-creator/pb-story-creator-view.js', __FILE__ ), array( 'jquery' ), $version, $footer );
		wp_enqueue_script( 'pb-story-creator-view' );

		wp_register_script( 'pb-story-creator-controller', plugins_url( 'js/story-creator/pb-story-creator-controller.js', __FILE__ ), array( 'jquery' ), $version, $footer );
		wp_enqueue_script( 'pb-story-creator-controller' );

		wp_register_script( '_pb-story-creator', plugins_url( 'js/story-creator/_pb-story-creator.js', __FILE__ ), array( 'jquery' ), $version, $footer );
		wp_localize_script('_pb-story-creator', 'pb', array(
				'post' => $post,
				'options' => (array) get_option( 'playbuzz' ),
				'itemId' => $this -> get_story_post_meta( $post -> ID, $this -> pb_story_post_item_id_key ),
			)
		);
		wp_enqueue_script( '_pb-story-creator' );

		wp_enqueue_script( 'wp-api' );
	}


	/**
	 * Handle save post data.
	 * TODO: change action name
	 */
	public function save_post_action_callback() {

		$post_id = '';
		$item_id = '';

		$nonce_value = PbConstants::$pb_nonce_value;
		$nonce_key = PbConstants::$pb_nonce_key;

		if ( isset( $_POST['postId'], $_POST[ $nonce_key ] ) && wp_verify_nonce( sanitize_key( $_POST[ $nonce_key ] ) , $nonce_value ) ) {
			$post_id = sanitize_text_field( wp_unslash( $_POST['postId'] ) );
		}

		if ( isset( $_POST['itemId'], $_POST[ $nonce_key ] ) && wp_verify_nonce( sanitize_key( $_POST[ $nonce_key ] ) , $nonce_value ) ) {
			$item_id = sanitize_text_field( wp_unslash( $_POST['itemId'] ) );
		}

		$this -> set_story_post_meta( $post_id, $this -> pb_story_post_item_id_key , $item_id );
		$this -> set_post_template_on_admin( $post_id, $item_id );

		echo 'OK';

		//close connection
		wp_die();
	}


	public function get_post_template( $item_id, $options ) {

		$embed_codes = new PlaybuzzEmbedCodes();
		$options['info'] = 'false';
		$story_post_content = $embed_codes -> item( $item_id, $options );

		return $story_post_content;
	}

	public function set_post_template_on_admin( $post_id, $item_id ) {

		$options = $this -> options;

		$post = array(
			'ID'           => $post_id,
			'post_content' => $this -> get_post_template( $item_id, $options ),
		);

		// Update the post into the database
		wp_update_post( $post );
	}

	/**
	 * set post template on content load
	 * @param $content
	 * @return string
	 */
	public function set_post_template_on_frontend( $content ) {

		global $post;

		$options = $this -> options;
		$item_id = $this -> get_story_post_meta( $post -> ID, $this -> pb_story_post_item_id_key );
		$story_post_content = $this -> get_post_template( $item_id, $options );

		$embeddedon     = ( ( ( array_key_exists( 'embeddedon',  $options ) ) ) ? $options['embeddedon'] : 'content' );

		if ( 'content' == $embeddedon ) {
			if ( is_singular() ) {
				return $story_post_content;
			}
		} // End if().
		elseif ( 'all' == $embeddedon ) {

			return $story_post_content;

		}

	}

	/**
	 * Print story creator template.
	 * @param $post
	 */
	public function set_story_creator_template( $post ) {

		$nonce_value = wp_create_nonce( PbConstants::$pb_nonce_value );

		echo '<div id="pb-story-creator"></div> 
             <input type="hidden" id="pb-story-creator-nonce" name="pb-story-creator-nonce" value="' . esc_attr( $nonce_value ) . '">';
	}


	/**
	 * Make sure user can edit posts or pages.
	 * @return bool
	 */
	public function user_have_access() {

		return current_user_can( 'edit_posts' );

	}

	/**
	 * Check if is old story post
	 * @param $post
	 * @return bool
	 */
	public function is_old_pb_story_post( $post ) {

		$identify_key = $this -> get_story_post_meta( $post -> ID, $this -> pb_story_post_identify_key );

		return $identify_key == $this -> pb_story_post_identify_value;
	}

	/**
	 * Check if is old story post
	 * @return bool
	 */
	public function is_new_pb_story_post() {

		$pb_url_param_key = PbConstants::$pb_url_param_key;
		$pb_url_param_value = PbConstants::$pb_url_param_value;

		$param_from_url_value = '';

		if ( isset( $_GET[ $pb_url_param_key ] ) ) {
			$param_from_url_value = sanitize_text_field( wp_unslash( $_GET[ $pb_url_param_key ] ) );
		}

		return $param_from_url_value == $pb_url_param_value;
	}

	/**
	 * Checks if editing pb story post.
	 * @param $post
	 * @return bool
	 */
	public function is_pb_story_post( $post ) {

		//check if is story new post
		$is_new_pb_story_post = $this -> is_new_pb_story_post();

		//check if is old story post
		$is_old_pb_story_post = $this -> is_old_pb_story_post( $post );

		return $is_new_pb_story_post || $is_old_pb_story_post;
	}


	/**
	 * Set mata data to post.
	 * @param $id - post id to add
	 * @param $key
	 * @param $value
	 */
	public function set_story_post_meta( $id, $key, $value ) {

		if ( $this -> get_story_post_meta( $id, $key ) == '' ) {
			add_post_meta( $id,$key,$value );
		}

	}

	/**
	 * Get mata data to post.
	 * @param $id
	 * @param $key
	 * @return mixed
	 */

	public function get_story_post_meta( $id, $key ) {

		return get_post_meta( $id, $key, true );
	}

	/**
	 * Remove post editor from edit post interface.
	 */
	public function remove_post_editor() {
		remove_post_type_support( $this -> post_type, 'editor' );
	}

	/**
	 * Get new post url.
	 * @return string
	 */
	function get_new_post_url() {

		$pb_url_param_key = PbConstants::$pb_url_param_key;
		$pb_url_param_value = PbConstants::$pb_url_param_value;

		if ( ! is_admin() ) {
			return site_url( 'wp-admin/post-new.php?' . $pb_url_param_key . '=' . $pb_url_param_value );
		}

		return 'post-new.php?' . $pb_url_param_key . '=' . $pb_url_param_value;
	}

	/**
	 * Add entry in the admin bar.
	 * TODO -- translation
	 * @param $wp_admin_bar
	 */
	public function add_story_links_admin_bar( $wp_admin_bar ) {

		$args = array(
			'id' => 'add-new-story',
			'parent' => 'new-content',
			'title' => __( 'Story', 'Story' ),
			'href' => $this -> get_new_post_url(),
			'meta' => array(
				'class' => 'pb-add-new-story',
				'title' => 'Story',
			),
		);

		$wp_admin_bar->add_node( $args );
	}

	/**
	 * Add entry in the Posts menu.
	 * TODO -- translation
	 */
	public function add_story_links_admin_menu() {

		add_submenu_page(
			'edit.php',
			__( 'Add New Story', 'Add New Story' ),
			__( 'Add New Story', 'Add New Story' ),
			'edit_posts',
			$this -> get_new_post_url()
		);

	}


}


//Create new PbStoryCreator.
new PlaybuzzStoryCreator();





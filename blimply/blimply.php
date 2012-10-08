<?php
/*
Plugin Name: Blimply
Plugin URI: http://doejo.com
Description: Blimply allows you to send push notifications to your mobile users utilizing Urban Airship API. It sports a post meta box and a dashboard widgets. You have the ability to broadcast pushes, and to push to specific Urban Airship tags as well.
Author: Rinat Khaziev, doejo
Version: 0.2.2
Author URI: http://doejo.com

GNU General Public License, Free Software Foundation <http://creativecommons.org/licenses/GPL/2.0/>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

define( 'BLIMPLY_VERSION', '0.2.2' );
define( 'BLIMPLY_ROOT' , dirname( __FILE__ ) );
define( 'BLIMPLY_FILE_PATH' , BLIMPLY_ROOT . '/' . basename( __FILE__ ) );
define( 'BLIMPLY_URL' , plugins_url( '/', __FILE__ ) );
define( 'BLIMPLY_PREFIX' , 'blimply' );

// Bootstrap
require_once BLIMPLY_ROOT . '/lib/wp-urban-airship/urbanairship.php';
require_once BLIMPLY_ROOT . '/lib/settings-api-class/class.settings-api.php';
require_once BLIMPLY_ROOT . '/lib/blimply-settings.php';

class Blimply {

	protected $airships, $airship, $options, $tags;
	/**
	 * Instantiate
	 */
	function __construct() {
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		add_action( 'save_post', array( $this, 'action_save_post' ) );
		add_action( 'add_meta_boxes', array( $this, 'post_meta_boxes' ) );
		add_action( 'update_option_blimply_options', array( $this, 'sync_airship_tags' ), 5, 2 );
		add_action( 'register_taxonomy', array( $this, 'after_register_taxonomy' ), 5, 3 );
		add_action( 'create_term', array( $this, 'action_create_term' ), 5, 3 );
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'dashboard_setup' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts_and_styles' ) );
		add_action( 'wp_ajax_blimply-send-push', array( $this, 'handle_ajax_post' ) );
	}

	function dashboard_setup() {
		if ( is_blog_admin() && current_user_can( 'edit_posts' ) )
			wp_add_dashboard_widget( 'dashboard_blimply', __( 'Send a Push Notification' ), array( $this, 'dashboard_widget' ) );
	}

	/**
	 *  Init hook
	 * 
	 */
	function action_init() {
		register_taxonomy( 'blimply_tags', array( 'post' ), array(
				'public' => false,
				'labels' => array(
					'name' => __( 'Urban Airship Tags', 'blimply' ),
					'singular_name' => __( 'Urban Airship Tags', 'blimply' ),
				),
				'show_in_nav_menus' => false,
				'show_ui' => false
			) );
		load_plugin_textdomain( 'blimply', false, dirname( plugin_basename( __FILE__ ) ) . '/lib/languages/' );
	}
	/**
	 * Set basic app properties
	 *
	 */
	function action_admin_init() {
		global $pagenow;
		// Init the plugin only on proper pages and if doing ajax request
		if ( ! in_array( $pagenow, array( 'post-new.php', 'post.php', 'index.php', 'options.php' ) ) && ! defined( 'DOING_AJAX' ) )
			return;

		$this->options = get_option( 'urban_airship' );
		$this->airships[ $this->options['blimply_name'] ] = new Airship( $this->options['blimply_app_key'], $this->options['blimply_app_secret'] );
		// Pass the reference to convenience var
		// We don't use multiple Airships yet.
		// Although we can, there's no UI for switching Airships.
		$this->airship = &$this->airships[ $this->options['blimply_name'] ];
		// We don't use built-in WP UI, instead we choose tag in custom Blimply meta box
		$this->tags = get_terms( 'blimply_tags', array( 'hide_empty' => 0 ) );
	}

	/**
	 * Register scripts and styles
	 *
	 */
	function register_scripts_and_styles() {
		global $pagenow;
		// Only load this on the proper page
		if ( ! in_array( $pagenow, array( 'post-new.php', 'post.php', 'index.php' ) ) )
			return;
		wp_enqueue_style( 'blimply-style', BLIMPLY_URL . '/lib/css/blimply.css' );
		wp_enqueue_script( 'blimply-js', BLIMPLY_URL . '/lib/js/blimply.js', array( 'jquery' )  );
		wp_localize_script( 'blimply-js', 'Blimply', array(
				'push_sent' => __( 'Push notification successfully sent', 'blimply' ),
				'push_error' => __( 'Sorry, there was some error while we were trying to send your push notification. Try again later!', 'blimply' ),
			)
		);
	}

	/**
	 * Sync our newly created tag with Urban Airship
	 *
	 * @param int     $term_id  term_id
	 * @param int     $tt_id    term_taxonomy_id
	 * @param string  $taxonomy
	 */
	function action_create_term( $term_id, $tt_id, $taxonomy ) {
		if ( 'blimply_tags' != $taxonomy )
			return;
		$tag = get_term( $term_id, $taxonomy );
		// Let's sync
		if ( ! is_wp_error( $tag ) ) {
			try {
				$response = $this->airship->_request( BASE_URL . "/tags/{$tag->slug}", 'PUT', null );
			} catch ( Exception $e ) {
				// @todo do something with exception
			}
			if ( isset( $response[0] ) && $response[0] == 201 ) {
				// @todo process ok result
			}
		}
	}

	/**
	 * Send a push notification if checkbox is checked
	 *
	 * @param int     $post_id
	 */
	function action_save_post( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || false  === wp_is_post_revision( $post_id ) )
			return;
		if ( !wp_verify_nonce( $_POST['blimply_nonce'], BLIMPLY_FILE_PATH ) )
			return;
		if ( 1 == get_post_meta( $post->ID, 'blimply_push_sent', true ) )
			return;

		if ( 1 == $_POST['blimply_push'] ) {
			$alert = !empty( $_POST['blimply_push_alert'] ) ? esc_attr( $_POST['blimply_push_alert'] ) : esc_attr( $_POST['post_title'] );
			$this->_send_broadcast_or_push( $alert, $_POST['blimply_push_tag'] );
			update_post_meta( $post_id, 'blimply_push_sent', true );
		}
	}

	/**
	 * Method to handle AJAX request for Dashboard Widget
	 */
	function handle_ajax_post() {
		if ( !wp_verify_nonce( $_POST['_wpnonce'], 'blimply-send-push' ) )
			return;

		$response = false;
		$alert = wp_kses( $_POST['blimply_push_alert'], array() );
		$this->_send_broadcast_or_push( $alert, $_POST['blimply_push_tag'] );
		echo 'ok';
		exit;
	}

	/**
	 * Private method to send push or broadcast.
	 *
	 * @param string  $alert
	 * @param string  $tag
	 *
	 */
	function _send_broadcast_or_push( $alert, $tag ) {
		// Strip escape slashes, otherwise double escaping would happen
		$alert = stripcslashes( $alert );
		$payload = array( 'aps' => array( 'alert' => $alert, 'badge' => '+1' ) );
		if ( $tag === 'broadcast' ) {
			$response =  $this->request( $this->airship, 'broadcast', $payload );
		} else {
			// Adding tags field to payload, no problem.
			$payload['tags'] = array( $tag );
			$response = $this->request( $this->airship, 'push', $payload );
		}
	}

	/**
	 * Register metabox for selected post types
	 *
	 * @todo implement ability to actually pick specific post types
	 */
	function post_meta_boxes() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_types as $post_type => $props )
			add_meta_box( BLIMPLY_PREFIX, __( 'Push Notification', 'blimply' ), array( $this, 'post_meta_box' ), $post_type, 'side' );
	}

	/**
	 * Render HTML
	 */
	function post_meta_box( $post ) {
		$is_push_sent = get_post_meta( $post->ID, 'blimply_push_sent', true );
		if ( 1 != $is_push_sent ) {
			echo '<div class="blimply-wrapper">';
			wp_nonce_field( BLIMPLY_FILE_PATH, 'blimply_nonce' );
			echo '<label for="blimply_push_alert">';
			_e( 'Push message', 'blimply' );
			echo '</label><br/> ';
			echo '<textarea id="blimply_push_alert" name="blimply_push_alert" class="bl_textarea">' . $post->post_title . '</textarea><br/>';
			echo '<strong>' . __( 'Send Push to following Urban Airship tags', 'blimply' ) . '</strong>';
			foreach ( (array) $this->tags as $tag ) {
				echo '<input type="radio" name="blimply_push_tag" id="blimply_tag_' .$tag->term_id . '" value="' . $tag->slug . '"/>';
				echo '<label class="selectit" for="blimply_tag_' .$tag->term_id . '" style="margin-left: 4px">';
				echo $tag->name;
				echo '</label><br/>';
			}

			if ( $this->options['blimply_allow_broadcast'] == 'on' ) {
				echo '<input type="radio" name="blimply_push_tag" id="blimply_tag_broadcast" value="broadcast"/>';
				echo '<label class="selectit" for="blimply_tag_broadcast" style="margin-left: 4px">';
				_e( 'Broadcast (send to all tags)', 'blimply' );
				echo '</label><br/>';
			}

			echo '<br/><input type="hidden" id="" name="blimply_push" value="0" />';
			echo '<input type="checkbox" id="blimply_push" name="blimply_push" value="1" disabled="disabled" />';
			echo '<label for="blimply_push">';
			_e( 'Check to confirm sending', 'blimply' );
			echo '</label> ';
			echo '</div>';

		} else {
			_e( 'Push notification is already sent', 'blimply' );
		}
	}

	/**
	 * Wrapper to make a remote request to Urban Airship
	 *
	 * @param Airship $airship an instance of Airship passed by reference
	 * @param string  $method
	 * @param mixed   $args
	 * @param mixed   $tokens
	 * @return mixed response or Exception or error
	 */
	function request( Airship &$airship, $method = '', $args = array(), $tokens = array() ) {
		if ( in_array( $method, array( 'register', 'deregister', 'feedback', 'push', 'broadcast' ) ) ) {
			try {
				$response = $airship->$method( $args, $tokens );
			} catch ( Exception $e ) {
				$exception_class = get_class( $e );
				if ( is_admin() ) {
					// @todo implement admin notification of misconfiguration
				}
			}
			return $response;
		} else {
			// @todo illegal request
		}
	}

	/**
	 * Dashboard widget
	 *
	 */
	function dashboard_widget() {
?>
		<form name="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" id="blimply-dashboard-widget">
			<h4 id="content-label"><label for="content"><?php _e( 'Send Push Notification' ) ?></label></h4>
			<div class="textarea-wrap">
				<textarea name="blimply_push_alert" id="content" rows="3" cols="15" tabindex="2" placeholder="Your push message"></textarea>
			</div>
			<h4><label for="tags-input"><?php _e( 'Choose a tag' ) ?></label></h4>
<?php
		foreach ( (array) $this->tags as $tag ) {
			echo '<label class="float-left f-left selectit" for="blimply_tag_' .$tag->term_id . '" style="margin-left: 4px">';
			echo '<input type="radio" class="float-left f-left" style="float:left" name="blimply_push_tag" id="blimply_tag_' .$tag->term_id . '" value="' . $tag->slug . '"/>';
			echo $tag->name;
			echo '</label><br/>';
		}

		if ( $this->options['blimply_allow_broadcast'] == 'on' ) {
			echo '<label class="selectit" for="blimply_tag_broadcast" style="margin-left: 4px">';
			echo '<input type="radio" style="float:left" name="blimply_push_tag" id="blimply_tag_broadcast" value="broadcast"/>';
			_e( 'Broadcast (send to all tags)', 'blimply' );
			echo '</label><br/>';
		}	
?>
			<p class="submit">
				<input type="hidden" name="action" id="blimply-push-action" value="blimply-send-push" />
				<?php wp_nonce_field( 'blimply-send-push' ); ?>
				<input type="reset" value="<?php esc_attr_e( 'Reset' ); ?>" class="button" />
				<span id="publishing-action">
					<?php
		if ( current_user_can( apply_filters( 'blimply_push_cap', 'publish_posts' ) ) ):
?>
					<input type="submit" name="publish" disabled="disabled" id="blimply_push_send" accesskey="p" tabindex="5" class="button-primary" value="<?php  esc_attr_e( 'Send push notification' ) ?>" />
					<?php endif; ?>
					<img class="waiting" src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" alt="" />
				</span>
				<br class="clear" />
			</p>
		</form>
<?php
	}
}

// define BLIMPLY_NOINIT constant somewhere in your theme to easily subclass Blimply
if ( ! defined( 'BLIMPLY_NOINIT' ) || defined( 'BLIMPLY_NOINIT' ) && BLIMPLY_NOINIT ) {
	global $blimply;
	$blimply = new Blimply;
}

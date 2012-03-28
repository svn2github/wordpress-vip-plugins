<?php
/*
Plugin Name: Livefyre Realtime Comments
Plugin URI: http://livefyre.com/wordpress#
Description: Implements livefyre realtime comments for WordPress
Author: Livefyre, Inc.
Version: 3.17
Author URI: http://livefyre.com/
*/


require_once( dirname( __FILE__ ) . "/livefyre_core.php" );

// Constants
define( 'LF_CMETA_PREFIX', 'livefyre_cmap_' );
define( 'LF_AMETA_PREFIX', 'livefyre_amap_' );
define( 'LF_DEFAULT_HTTP_LIBRARY', 'Livefyre_Http_Extension' );
define( 'LF_WP_VIP', true );

class Livefyre_Application {

	function __construct( $lf_core ) {
	
		$this->lf_core = $lf_core;
		
	}

	function home_url() {
	
		return $this->get_option( 'home' );
		
	}
	
	function delete_option( $optionName ) {
	
		return delete_option( $optionName );
		
	}
	
	function update_option( $optionName, $optionValue ) {
	
		return update_option( $optionName, $optionValue );
		
	}
	
	function get_option( $optionName, $defaultValue = '' ) {
	
		return get_option( $optionName, $defaultValue );
		
	}
	
	function reset_caches() {
	
		global $cache_path, $file_prefix;
		if ( function_exists( 'prune_super_cache' ) ) {
			prune_super_cache( $cache_path, true );
		}
		if ( function_exists( 'wp_cache_clean_cache' ) ) {
			wp_cache_clean_cache( $file_prefix );
		}
	}

	function setup_activation( $Obj ) {
	
		register_activation_hook( __FILE__, array( &$Obj, 'activate' ) );
		register_deactivation_hook( __FILE__, array( &$Obj, 'deactivate' ) );

	}
	
	function setup_health_check( $Obj ) {

		add_action( 'init', array( &$Obj, 'livefyre_health_check' ) );

	}

	function setup_sync( $obj ) {

		add_action( 'livefyre_sync', array( &$obj, 'do_sync' ) );
		add_action( 'init', array( &$obj, 'comment_update' ) );
		add_filter( 'save_post' , array( &$obj, 'save_post' ) , 99, 1 );
	
	}

	function debug_log( $debugStr ) {

		if ( $this->lf_core->debug_mode ) {
			// disabled for production
			return true;
		}
		return false;
	
	}

	function save_post( $post_id, $update = true ) {
	
		$parent_id = wp_is_post_revision( $post_id );
		if ( $parent_id ) {
			$post_id = $parent_id;
		}
		
		$is_page = is_page( $post_id );
		if ( $is_page ) {
			$record = get_page( $post_id );
			if ( $parent_id ) {
			    $parent = get_page( $parent_id );
			}
		} else {
			$record = get_post( $post_id );
			if ( $parent_id ) {
			    $parent = get_post( $parent_id );
			}
		}
		
		if ( ( isset( $parent ) && $parent->post_status == 'publish' ) || $record->post_status == 'publish' )	{
			$tags = false;
			if ( !$is_page ) {
				$tags = get_the_tags( $post_id );
			}
			if ( $tags ) {
				$tagnames = array();
				foreach( $tags as $tag ) {
					array_push( $tagnames, $tag->name );
				}
				$tagStr = implode( ', ', $tagnames );
			} else {
				$tagStr = '';
			}

			$postdata = array( 'title' => $record->post_title, 'tags' =>$tagStr, 'source_url' => get_permalink( $post_id ), 'article_identifier' => $post_id );
			$http = $this->lf_core->lf_domain_object->http;
			$http->request( $this->lf_core->http_url . '/import/wordpress/' . get_option( 'livefyre_site_id' ) . '/post', array( 'data' => $postdata, 'method' => 'POST' ) );
		}
	
	}
	
	function activity_log( $wp_comment_id = "", $lf_comment_id = "", $lf_activity_id = "" ) {
	
		// Use meta keys that will allow us to lookup by Livefyre comment i
		update_comment_meta( $wp_comment_id, LF_CMETA_PREFIX . $lf_comment_id, $lf_comment_id );
		update_comment_meta( $wp_comment_id, LF_AMETA_PREFIX . $lf_activity_id, $lf_activity_id );
		return false;

	}
	
	function get_app_comment_id( $lf_comment_id ) {
	
		global $wpdb;
		$wp_comment_id = wp_cache_get( $lf_comment_id, 'livefyre-comment-map' );
		if ( false === $wp_comment_id ) {
			$wp_comment_id = $wpdb->get_var( $wpdb->prepare( "SELECT comment_id FROM $wpdb->commentmeta WHERE meta_key = %s LIMIT 1", LF_CMETA_PREFIX . $lf_comment_id ) );
			if ( $wp_comment_id ) {
				wp_cache_set( $lf_comment_id, $wp_comment_id, 'livefyre-comment-map' );
			}
		}
		return $wp_comment_id;

	}
	
	function schedule_sync( $timeout ) {
	
		$hook = 'livefyre_sync';
		
		// try to clear the hook, for race condition safety
		wp_clear_scheduled_hook( $hook );
		$this->debug_log( time() . " scheduling sync to occur in $timeout" );
		wp_schedule_single_event( time() + $timeout, $hook );
	
	}
	
	private static $comment_fields = array(
		"comment_author",
		"comment_author_email",
		"comment_author_url",
		"comment_author_IP",
		"comment_content",
		"comment_ID"
	);
	
	function sanitize_inputs ( $data ) {
		
		// sanitize inputs
		$cleaned_data = array();
		foreach ( $data as $key => $value ) {
			// 1. do we care ? if so, add it
			if ( in_array( $key, self::$comment_fields ) ) {
				$cleaned_data[ $key ] = $value;
			}
		}
		return wp_filter_comment( $cleaned_data );
		
	}
	
	function delete_comment( $data ) {

		return wp_delete_comment( $this->sanitize_inputs( $data ) );

	}

	function insert_comment( $data ) {

		return wp_insert_comment( $this->sanitize_inputs( $data ) );

	}

	function update_comment( $data ) {

		return wp_update_comment( $this->sanitize_inputs( $data ) );

	}
	
	function update_comment_status( $app_comment_id, $status ) {
	
		// Livefyre says unapproved, WordPress says hold.
		wp_set_comment_status( $app_comment_id, ( $status == 'unapproved' ? 'hold' : $status) );
	
	}

} // Livefyre_Application

class Livefyre_Admin {
	function __construct( $lf_core ) {
		$this->lf_core = $lf_core;

		add_action( 'admin_menu', array( &$this, 'register_admin_page' ) );
		add_action( 'admin_init', array( &$this, 'site_options_init' ) );
	
	}

	function register_admin_page() {
		
		add_submenu_page( 'options-general.php', 'Livefyre Settings', 'Livefyre', 
			'manage_options', 'livefyre', array( &$this, 'site_options_page' ) );

	}

	function settings_callback() {}

	function network_options_init( $options_page = 'livefyre_domain_options' ) {
	
		register_setting($options_page, 'livefyre_domain_name', 'sanitize_text_field' );
		register_setting($options_page, 'livefyre_domain_key', 'sanitize_text_field');
		register_setting($options_page, 'livefyre_use_backplane', 'sanitize_text_field');

		add_settings_section('lf_domain_settings',
			'Livefyre Domain Settings',
			array( &$this, 'settings_callback' ),
			'livefyre_network');
		
		add_settings_field('livefyre_domain_name',
			'Livefyre Domain Name',
			array( &$this, 'domain_name_callback' ),
			'livefyre_network',
			'lf_domain_settings');
		
		add_settings_field('livefyre_domain_key',
			'Livefyre Domain Key (required if you don\'t use livefyre.com profiles)',
			array( &$this, 'domain_key_callback' ),
			'livefyre_network',
			'lf_domain_settings');
		
		add_settings_field('livefyre_use_backplane',
			'Livefyre Backplane Integration',
			array( &$this, 'use_backplane_callback' ),
			'livefyre_network',
			'lf_domain_settings');
		
	}
	
	function site_options_init() {
	
		$site_options = 'livefyre_site_options';
		register_setting($site_options, 'livefyre_site_id', 'sanitize_text_field');
		register_setting($site_options, 'livefyre_site_key', 'sanitize_text_field');
		
		add_settings_section('lf_site_settings',
			'Livefyre Site Settings',
			array( &$this, 'settings_callback' ),
			'livefyre');
		
		add_settings_field('livefyre_site_id',
			'Livefyre Site ID',
			array( &$this, 'site_id_callback' ),
			'livefyre',
			'lf_site_settings');
		
		add_settings_field('livefyre_site_key',
			   'Livefyre Site Key',
			   array( &$this, 'site_key_callback' ),
			   'livefyre',
			   'lf_site_settings');
		
	    $this->network_options_init( $site_options );
	
	}

	function site_options_page() {
		?>
			<div class="wrap">
				<h2>Livefyre Settings Page</h2>
				<h3>WordPress.com VIP Instructions</h3>
				<p style="font-size:1em;">
					<ol style="font-size:inherit;">
						<li>Go to http://www.livefyre.com/install/</li>
						<li>Create a Livefyre account</li>
						<li>Enter your Site URL</li>
						<li>Select "Custom" platform (gear symbol)</li>
						<li>Click "keep going" to see "Livefyre is ready for use on…"</li>
						<li>Email WordPressVIP@livefyre.com with the following information:
						    <ul style="font-size:inherit;">
						        <li>URL of your site</li>
						        <li>Any additional questions about Livefyre</li>
						    </ul>
						</li>
						<li>Livefyre will email you the Livefyre Site ID and Site Key to enter on the Livefyre Settings Page in your WP-Admin.</li>
						<li>No changes need to be made for Livefyre Domain Settings.</li>
						<li>Click "Save Changes" and you're all set!</li>
					</ol>
				</p>
				<p style="font-size:1em;"><strong>Do you have your own profile system where users login?</strong> Do you want users creating profiles for your site? Let us know and we'll schedule a time to talk through Livefyre's Enterprise Platform to show you how it will meet the needs of your community.</p>
				<form method="post" action="options.php">
					<?php
						settings_fields( 'livefyre_domain_options' );
						do_settings_sections( 'livefyre_network' );
						settings_fields( 'livefyre_site_options' );
						do_settings_sections( 'livefyre' );
					?>
					<p class="submit">
						<input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ) ?>" />
					</p>
				</form>
			</div>
		<?php
	}

	function site_id_callback() {
	
		echo "<input name='livefyre_site_id' value='". esc_attr( get_option( 'livefyre_site_id' ) ) ."' />";
		
	}
	
	function site_key_callback() { 
	
		echo "<input name='livefyre_site_key' value='". esc_attr( get_option( 'livefyre_site_key' ) ) ."' />";
		
	}
	
	function domain_name_callback() {
	
		echo "<input name='livefyre_domain_name' value='". esc_attr( get_option( 'livefyre_domain_name', LF_DEFAULT_PROFILE_DOMAIN ) ) ."' />";
		
	}
	
	function domain_key_callback() { 
	
		echo "<input name='livefyre_domain_key' value='". esc_attr( get_option( 'livefyre_domain_key' ) ) ."' />";
		
	}
	
	function use_backplane_callback() {
	
		echo "<input name='livefyre_use_backplane' type='checkbox' value='1' " . checked( get_option('livefyre_use_backplane', false), true, false ) . "/>";
	
	}

	function get_app_comment_id( $lf_comment_id ) {

		return $this->lf_core->AppExtension->get_app_comment_id( $lf_comment_id );

	}

}


class Livefyre_Display {

	function __construct( $lf_core ) {
	
		$this->lf_core = $lf_core;
		
		if ( ! $this->livefyre_comments_off() ) {
			add_action( 'wp_head', array( &$this, 'lf_embed_head_script' ) );
			add_filter( 'comments_template', array( &$this, 'livefyre_comments' ) );
			add_filter( 'comments_number', array( &$this, 'livefyre_comments_number' ), 10, 2 );
		}
	
	}

	function livefyre_comments_off() {
	
		return ( $this->lf_core->AppExtension->get_option( 'livefyre_site_id', '' ) == '' );

	}

	function lf_embed_head_script() {

		global $post, $current_user;
		if ( comments_open() && $this->livefyre_show_comments() ) {// is this a post page?
			if( $parent_id = wp_is_post_revision( $post->ID ) ) {
				$post_id = $parent_id;
			} else {
				$post_id = $post->ID;
			}
			
			$domain = $this->lf_core->lf_domain_object;
			$site = $domain->site( $this->lf_core->AppExtension->get_option( 'livefyre_site_id' ) );
			$article = $site->article( $post_id );
			$conv = $article->conversation();
			$use_backplane = $this->lf_core->AppExtension->get_option( 'livefyre_use_backplane', false );
			if ( $use_backplane || $this->lf_core->AppExtension->get_option( 'livefyre_domain_name', LF_DEFAULT_PROFILE_DOMAIN ) == LF_DEFAULT_PROFILE_DOMAIN || defined( LF_WP_VIP ) ) {
				/* In these scenarios, we can't make assumptions about how user auth
				   events need to be set up.  For livefyre.com profiles all defaults are
				   inferred.  In the case of Backplane and/or WP VIP this shall be set
				   up using a child theme (for those not using livefyre.com profiles).
				*/
				echo $conv->to_initjs( null, null, $use_backplane );
			} else {
				foreach ( array( 'login', 'logout' ) as $handler ) {
					$func = "wp_". $handler . "_url";
					$code = $this->lf_core->AppExtension->get_option( "livefyre_$handler", false );
					if ( !$code ) {
						$code = 'function(){document.location.href="' . $func( get_permalink() ) . '"}';
					}
					$conv->add_js_delegate( "auth_$handler", $code );
				}
    			if ( $current_user ) {
    				$user = $domain->user( $current_user->ID );
    				echo $conv->to_initjs( $user, $current_user->display_name, $use_backplane );
    			} else {
    				echo $conv->to_initjs( null, null, $use_backplane);
    			}
			}
		}

	}

	function livefyre_comments( $cmnts ) {

		return dirname( __FILE__ ) . '/comments-template.php';

	}

	function livefyre_show_comments(){

		return ( is_single() || is_page() ) && ! is_preview();

	}


	function livefyre_comments_number( $count, $post ) {

		global $post;
		return '<span article_id = "' . $post->ID . '" class = "livefyre-ncomments">' . $count . '</span>';

	}
	
}

if( !class_exists( 'WP_Http' ) )
	include_once( ABSPATH . WPINC. '/class-http.php' );

class Livefyre_Http_Extension {
    // Map the Livefyre request signature to what WordPress expects.
    // This just means changing the name of the payload argument.
    public function request( $url, $args = array() ) {
        $http = new WP_Http;
        if ( isset( $args[ 'data' ] ) ) {
            $args[ 'body' ] = $args[ 'data' ];
            unset( $args[ 'data' ] );
        }
        return $http->request( $url, $args );
    }
}


$livefyre = new Livefyre_core;


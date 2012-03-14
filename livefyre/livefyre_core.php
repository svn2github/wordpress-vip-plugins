<?php
/*
Livefyre Realtime Comments Core Module

This library is shared between all Livefyre plugins.

Author: Livefyre, Inc. 
Version: 3.16
Author URI: http://livefyre.com/
*/
define( 'LF_DEFAULT_PROFILE_DOMAIN', 'livefyre.com' );
define( 'LF_DEFAULT_TLD', 'i4.livefyre.com' );
define( 'LF_SYNC_LONG_TIMEOUT', 25200 );
define( 'LF_SYNC_SHORT_TIMEOUT', 3 );
define( 'LF_SYNC_MAX_INSERTS', 50 );
define( 'LF_SYNC_ACTIVITY', 'lf-activity' );
define( 'LF_SYNC_MORE', 'more-data' );
define( 'LF_SYNC_ERROR', 'error' );
define( 'LF_PLUGIN_VERSION', '3.16' );

global $livefyre;

class Livefyre_core {

	function __construct() { 

		$this->add_extension();
		$this->require_php_api();
		$this->define_globals();
		$this->require_subclasses();
		
	}
	
	function define_globals() {
	
		$this->options = array( 
			'livefyre_site_id', // - name ( id ) of the livefyre record associated with this blog
			'livefyre_site_key' // - shared key used to sign requests to/from livefyre
		);

		$client_key = $this->AppExtension->get_option( 'livefyre_domain_key', '' );
		$profile_domain = $this->AppExtension->get_option( 'livefyre_domain_name', LF_DEFAULT_PROFILE_DOMAIN );
		$this->lf_domain_object = new Livefyre_Domain( $profile_domain, $client_key );
		$this->debug_mode = false;
		$this->top_domain = ( $profile_domain == LF_DEFAULT_PROFILE_DOMAIN ? LF_DEFAULT_TLD : $profile_domain );

		$this->http_url = ( strpos(LF_DEFAULT_TLD, 'livefyre.com') === 0 ? "http://www." . LF_DEFAULT_TLD : "http://" . LF_DEFAULT_TLD );
		$this->api_url = "http://api.$this->top_domain";
		$this->quill_url = "http://quill.$this->top_domain";
		$this->admin_url = "http://admin.$this->top_domain";
		$this->bootstrap_url = "http://bootstrap.$this->top_domain";
		$this->home_url = $this->AppExtension->home_url();
		$this->plugin_version = LF_PLUGIN_VERSION;

	}
	
	function require_php_api() {

		require_once(dirname(__FILE__) . "/livefyre-api/libs/php/Livefyre.php");

	}

	function add_extension() {

		if ( class_exists( 'Livefyre_Application' ) ) {
			$this->AppExtension = new Livefyre_Application( $this );
		} else {
			die( "There is no Application Module ( WordPress, Joomla, or other )included with this plugin .  Error: Class Livefyre_Application not defined . " );
		}
	}

	function require_subclasses() {

		$this->Health_Check = new Livefyre_Health_Check( $this );
		$this->Activation = new Livefyre_Activation( $this );
		$this->Sync = new Livefyre_Sync( $this );
		$this->Admin = new Livefyre_Admin( $this );
		$this->Display = new Livefyre_Display( $this );

	}

} //  Livefyre_core

class Livefyre_Health_Check {

	function __construct( $lf_core ) {

		$this->lf_core = $lf_core;
		$this->lf_core->AppExtension->setup_health_check( $this );

	}

	function livefyre_health_check() {

		if ( !isset( $_GET[ 'livefyre_ping_hash' ] ) )
			return;

		//check the signature
		if ( $_GET[ 'livefyre_ping_hash' ] != md5( $this->lf_core->home_url ) ) {
			echo "hash does not match! my url is: $this->lf_core->home_url";
			exit;
		} else {
			echo "\nhash matched for url: $this->lf_core->home_url\n";
			echo "site's server thinks the time is: " . gmdate( 'd/m/Y H:i:s', time() );
			$notset = '[NOT SET]';
			foreach ( $this->lf_core->options as $optname ) {
				echo "\n\nlivefyre option: $optname";
				$optval = $this->lf_core->AppExtension->get_option( $optname, $notset );
				#obscure the secret key ( first 2 chars only )
				$val = ( $optname == 'livefyre_secret' && $optval != $notset ) ? substr( $optval, 0, 2 ) : $optval;
				echo "\n		  value: $val";
			}
			exit;
		}
	}
}

class Livefyre_Activation {

	function __construct( $lf_core ) {
	
		$this->lf_core = $lf_core;
		$this->lf_core->AppExtension->setup_activation( $this );

	}

	function deactivate() {

		$this->reset_caches();

	}

	function activate() {
	
		$blogname = $this->lf_core->AppExtension->get_option( 'livefyre_site_id', null );
		if ( !$this->lf_core->AppExtension->get_option( 'livefyre_domain_name', false ) ) {
			// Initialize default profile domain i.e. livefyre.com
			$this->lf_core->AppExtension->update_option( 'livefyre_domain_name', LF_DEFAULT_PROFILE_DOMAIN );
		}
	
	}

	function reset_caches() {
	
		$this->lf_core->AppExtension->reset_caches();
		
	}

}

class Livefyre_Sync {
	
	function __construct( $lf_core ) {

		$this->lf_core = $lf_core;
		$this->lf_core->AppExtension->setup_sync( $this );

	}

	function do_sync() {
	
		/*
			Fetch comments from the livefyre server, providing last activity id we have.
			Schedule the next sync if we got >50 or the server says "more-data".
			If there are no more comments, schedule a sync for several hrs out.
		*/
		$this->lf_core->AppExtension->debug_log( time() . ' livefyre synched' );
		$max_activity = $this->lf_core->AppExtension->get_option( 'livefyre_activity_id', '0' );
		if ( $max_activity == '0' ) {
			$final_path_seg = '';
		} else {
			$final_path_seg = $max_activity . '/';
		}
		$url = $this->site_rest_url() . '/sync/' . $final_path_seg;
		$sigcreated_param = 'sig_created=' . time();
		$key = $this->lf_core->AppExtension->get_option( 'livefyre_site_key' );
		$url .= '?' . $sigcreated_param . '&sig=' . urlencode( getHmacsha1Signature( base64_decode( $key ), $sigcreated_param ) );
		$result = $this->lf_core->lf_domain_object->http->request( $url );
		if (is_array( $result ) && isset($result['response']) && $result['response']['code'] == 200) {
			$str_comments = $result['body'];
		} else {
			$str_comments = '';
		}
		$json_array = json_decode( $str_comments );
		if ( !is_array( $json_array ) ) {
			$this->schedule_sync( LF_SYNC_LONG_TIMEOUT );
			$this->livefyre_report_error( 'Error during do_sync: Invalid response ( not a valid json array ) from sync request to url: ' . $url . ' it responded with: ' . $str_comments );
			return;
		}
		$data = array();
		$inserts_remaining = LF_SYNC_MAX_INSERTS;
		// What to record for the "latest" id we know about, when done inserting
		$last_activity_id = 0;
		// By default, we don't queue an other near-term sync unless we discover the need to
		$timeout = LF_SYNC_LONG_TIMEOUT;
		foreach ( $json_array as $json ) {
			$mtype = $json->message_type;
			if ( $mtype == LF_SYNC_ERROR ) {
				// An error was encountered, don't schedule next sync for near-term
				$timeout = LF_SYNC_LONG_TIMEOUT;
				break;
			}
			if ( $mtype == LF_SYNC_MORE ) {
				// There is more data we need to sync, schedule next sync soon
				$timeout = LF_SYNC_SHORT_TIMEOUT;
				break;
			}
			if ( $mtype == LF_SYNC_ACTIVITY ) {
				$last_activity_id = $json->activity_id;
				$inserts_remaining--;
				$comment_date  = (int) $json->created;
				$comment_date = get_date_from_gmt( date( 'Y-m-d H:i:s', $comment_date ) );
				$data = array( 
					'lf_activity_id'  =>  $json->activity_id,
					'lf_action_type'  => $json->activity_type,
					'comment_post_ID'  => $json->article_identifier,
					'comment_author'  => $json->author,
					'comment_author_email'  => $json->author_email,
					'comment_author_url'  => $json->author_url,
					'comment_type'  => '', 
					'lf_comment_parent'  => $json->lf_parent_id,
					'lf_comment_id'  => $json->lf_comment_id,
					'user_id'  => null,
					'comment_author_IP'  => $json->author_ip,
					'comment_agent'  => 'Livefyre, Inc .  Comments Agent', 
					'comment_date'  => $comment_date,
					'lf_state'  => $json->state
				);
				if ( isset( $json->body_text ) ) {
					$data[ 'comment_content' ] = $json->body_text;
				}
				$this->livefyre_insert_activity( $data );
				if ( !$inserts_remaining ) {
					$timeout = LF_SYNC_SHORT_TIMEOUT;
					break;
				}
			}
		}
		if ( $last_activity_id ) {
			$this->lf_core->AppExtension->update_option( 'livefyre_activity_id', $last_activity_id );
		}
		$this->schedule_sync( $timeout );
	
	}


	function schedule_sync( $timeout ) {

		$this->lf_core->AppExtension->schedule_sync( $timeout );

	}
	
	function comment_update() {
		
		if (isset($_GET['lf_wp_comment_postback_request']) && $_GET['lf_wp_comment_postback_request']=='1') {
			$this->do_sync();
			// Instruct the backend to use the site sync postback mechanism for future updates.
			echo "{\"status\":\"ok\",\"plugin-version\":\"" . LF_PLUGIN_VERSION . "\",\"message\":\"sync-initiated\"}";
			exit;
		}
	
	}

	function save_post( $post_id ) {

		$this->lf_core->AppExtension->save_post( $post_id );
	
	}

	function post_param( $name, $plain_to_html = false, $default = null ) {

		$in = ( isset( $_POST[$name] ) ) ? trim( $_POST[$name] ) : $default;
		if ( $plain_to_html ) {
			$out = str_replace( "&", "&amp;", $in );
			$out = str_replace( "<", "&lt;", $out );
			$out = str_replace( ">", "&gt;", $out );
		} else {$out = $in;}
		return $out;

	}
	
	function site_rest_url() {

		return $this->lf_core->http_url . '/site/' . $this->lf_core->AppExtension->get_option( 'livefyre_site_id' );

	}

	function livefyre_report_error( $message ) { 

		$args = array( 'data' => array( 'message' => $message, 'method' => 'POST' ) );
		$this->lf_core->lf_domain_object->http->request( $this->site_rest_url() . '/error', $args );

	}

	function livefyre_insert_activity( $data ) {

		$ext = $this->lf_core->AppExtension;
		if ( isset( $data[ 'lf_comment_parent' ] ) && $data[ 'lf_comment_parent' ]!= null ) {
			$app_comment_parent = $ext->get_app_comment_id( $data[ 'lf_comment_parent' ] );
			if ( $app_comment_parent == null ) {
				//something is wrong.  might want to log this, essentially flattening because parent is not mapped
			}
		} else { 
			$app_comment_parent = null;
		}
		$app_comment_id = $ext->get_app_comment_id( $data[ 'lf_comment_id' ] );
		$at = $data[ 'lf_action_type' ];
		$data[ 'comment_approved' ] = ( ( isset( $data[ 'lf_state' ] ) && $data[ 'lf_state' ] == 'active' ) ? 1 : 0 );
		$data[ 'comment_parent' ] = $app_comment_parent;
		$action_types = array( 
			'comment-add', 
			'comment-moderate:mod-approve', 
			'comment-moderate:mod-hide', 
			'comment-update'
		);
		if ( $app_comment_id > '' && in_array( $at, $action_types ) ) {
			// update existing comment
			$data[ 'comment_ID' ] = $app_comment_id;
			$at_parts = explode( ':', $at );
			$action = $at_parts[ 0 ];
			$mod = count( $at_parts ) > 1 ? $at_parts[ 1 ] : '';
			if ( $action == 'comment-moderate' ) {
				if ( $mod == 'mod-approve' ) {
					$ext->update_comment_status( $app_comment_id, 'approve' );
				} elseif ( $mod == 'mod-hide' && $data[ 'lf_state' ] == 'hidden' ) {
					$ext->update_comment_status( $app_comment_id, 'spam' );
				}
			} elseif ( ($action == 'comment-update' || $action == 'comment-add') && isset( $data[ 'comment_content' ] ) && $data[ 'comment_content' ] != '' ) {
				// even if its supposed to be an "add", when we find the app comment ID, it must be an update
				$ext->update_comment( $data );
				if ( $data[ 'lf_state' ] == 'unapproved' ) {
					$ext->update_comment_status( $app_comment_id, 'hold' );
				}
			}
		} elseif ( in_array( $at, array( 'comment-add', 'comment-moderate:mod-approve' ) ) ) {
			// insert new comment
			if ( !isset( $data[ 'comment_content' ] ) ) {
				livefyre_report_error( 'comment_content missing for synched activity id:' . $data[ 'lf_activity_id' ] );
			}
			if ( $data[ 'lf_state' ] != 'deleted' && $data[ 'lf_state' ] != 'hidden' ) {
				$app_comment_id = $ext->insert_comment( $data );
				if ( $data[ 'lf_state' ] == 'unapproved' ) {
					$ext->update_comment_status( $app_comment_id, 'unapproved' );
				}
			}
		} else {
			return false; //we do not know how to handle this condition
		}

		if ( !( $app_comment_id > 0 ) ) return false;
		$ext->activity_log( $app_comment_id, $data[ 'lf_comment_id' ], $data[ 'lf_activity_id' ] );
		return true;
	}
	
}


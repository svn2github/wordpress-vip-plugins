<?php
/*
Plugin Name: SocialFlow
Plugin URI: http://wordpress.org/extend/plugins/socialflow/
Description: SocialFlow's WordPress plugin enhances your WordPress experience by allowing you to utilize the full power of SocialFlow from right inside your WordPress dashboard.
Author: SocialFlow, Stresslimit, PeteMall
Version: 1.1
Author URI: http://socialflow.com/
License: GPLv2 or later
Text Domain: socialflow
Domain Path: /i18n
*/

class SocialFlow_Plugin {

	public static $instance;
	const consumer_key = 'acbe74e2cc182d888412';
	const consumer_secret = '650108a50ea3cb2bd6f9';

	public function __construct() {
		global $pagenow;
		self::$instance = $this;

		$this->l10n = array(
			'prompt'     => __( 'This message will be sent to SocialFlow along with link to blog post:', 'socialflow' ),
			'true'       => __( 'Yes',         'socialflow' ),
			'false'      => __( 'No',          'socialflow' ),
			'optimize'   => __( 'Optimize',    'socialflow' ),
			'publishnow' => __( 'Publish Now', 'socialflow' ),
			'hold'       => __( 'Hold',        'socialflow' ),
			'1'          => __( 'Enable',      'socialflow' ),
			'0'          => __( 'Disable',     'socialflow' ),
			'max'        => 'index.php' == $pagenow ? 140 : 118
		);
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Load textdomain and hooks.
	 */
	public function init() {
		// Translations
		load_plugin_textdomain( 'socialflow', false, basename( dirname( __FILE__ ) ) . '/i18n' );

		add_action( 'post_row_actions',       array( $this, 'row_actions'               ), 10, 2 );
		add_action( 'page_row_actions',       array( $this, 'row_actions'               ), 10, 2 );
		add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue'                   ), 10, 2 );
		add_action( 'transition_post_status', array( $this, 'transition_post_status'    ), 10, 3 );
		add_action( 'add_meta_boxes',         array( $this, 'add_meta_box'              ) );
		add_action( 'save_post',              array( $this, 'save_post'                 ) );
		add_action( 'wp_dashboard_setup',     array( $this, 'register_dashboard_widget' ) );
		add_action( 'wp_ajax_sf-shorten-msg', array( $this, 'shorten_message'           ) );
		add_action( 'admin_init',             array( $this, 'admin_init'                ) );
		add_action( 'admin_notices',          array( $this, 'admin_notices'             ) );

		if ( is_admin() )
			require_once( dirname( __FILE__ ) . '/includes/settings.php' );
	}

	public function admin_init() {
		if ( isset( $_GET['sf_oauth'], $_GET['oauth_token'] ) ) {
			$options = get_option( 'socialflow' );
			if ( ! isset( $options['oauth_token'] ) )
				return;

			if ( $options['oauth_token'] == $_GET['oauth_token'] ) {
				require_once( dirname( __FILE__ ) ) . '/includes/class-wp-socialflow.php';
				$sf = new WP_SocialFlow( self::consumer_key, self::consumer_secret, $options['oauth_token'], $options['oauth_token_secret'] );
				$options['access_token'] = $sf->get_access_token( $_GET['oauth_verifier'] );
				unset( $options['oauth_token'] );
				unset( $options['oauth_token_secret'] );
				$options['publish_option'] = empty( $options['publish_option'] ) ? 'optimize' : $options['publish_option'];
				$options['enable'] = true;
				$options['accounts'] = $sf->get_account_list();
				foreach ( $options['accounts'] as &$account )
					$account['status'] = 'on';
				update_option( 'socialflow', $options );
				wp_redirect( admin_url() );
				exit;
			}
		} elseif ( isset( $_GET['action'], $_GET['_wpnonce'], $_GET['post'] ) && 'sf-publish' == $_GET['action'] && wp_verify_nonce( $_GET['_wpnonce'], 'sf-publish_' . $_GET['post'] ) ) {
			$referer = remove_query_arg( array( 'action', '_wpnonce', 'post' ), wp_get_referer() );

			if ( $this->transition_post_status( 'publish', '', get_post( absint( $_GET['post'] ) ) ) )
				$sent = true;
			else
				$sent = 0;

			wp_redirect( add_query_arg( 'sf_sent', $sent, $referer ) );
			exit;
		}
	}

	public function add_meta_box() {
		add_meta_box( 'socialflow', __( 'SocialFlow', 'socialflow' ), array( $this, 'display_compose_form' ), 'post', 'side', 'high', array( 'post_page' => true ) );
	}

	public function enqueue( $hook ) {		
		if ( in_array( $hook, array( 'index.php', 'post-new.php', 'post.php' ) ) ) {
			$color = 'fresh' == get_user_meta( get_current_user_id(), 'admin_color', true ) ? '#F1F1F1' : '#F5FAFD';
			?>
<style type="text/css">
	#socialflow div.inside { margin: 0; padding: 0 }
	#socialflow h3 { background: url(<?php echo plugins_url( 'images/socialflow.png', __FILE__ ); ?>) 7px <?php echo $color; ?> no-repeat; }
	#socialflow h3 span { margin-left: 25px; }
	#socialflow th { width: 100px; }
	#socialflow fieldset { line-height: 1.4em; padding: 10px 0px }
	#socialflow fieldset span { padding-right: 15px }
	#shorten-links, #sf_char_count { float: right; margin-left: 25px }
	#minor-publishing #compose { float: left; }
	#sf-text { margin-bottom: 2px; height: 6em; width: 100%; }
	#socialflow #sf-shorten-message { margin: 0 10px 10px;}
	.sf-error { color: #BC0B0B; }
	#sf-hidden { background: none; border: none; font-weight: 900; vertical-align: middle; }
	.misc-pub-section:first-child { border-top-width: 0; }
	#message-option-display, #sf-username-display, #sf-passcode-display, #enable-display { font-weight: bold; }
	#message-option-select, #post-format, #edit-accounts-div { line-height: 2.5em; margin-top: 3px; }
	#socialflow .submit { text-align: center; padding: 10px 10px 8px !important; clear: both; border-top: none; }
	#enable-select, #post-format { line-height: 2.5em; margin-top: 3px; }
	.socialflow-authorize { margin: 10px; }
	#shorten-links #ajax-loading { padding: 0 5px;}
	div.sf-updated { background-color: #ffffe0; border-color: #e6db55; }
	div.sf-error { background-color: #FFEBE8; border-color: #C00; }
	div.sf-updated, div.sf-error { -webkit-border-radius: 3px; border-radius: 3px; border-width: 1px; border-style: solid; padding: 5px; margin: 10px; }
	div.sf-updated p, div.sf-error p { margin: 0.5em 0; padding: 2px; }
	#sf-accounts { max-height: 250px; overflow: auto; }
	#sf-view { float: left; }
	.sf-count-error { color: red; }
</style>
			<?php
			$options = get_option( 'socialflow' );
			if ( 
				! $options ||
				empty( $options['access_token'] ) ||
				empty( $options['access_token']['oauth_token'] ) ||
				empty( $options['access_token']['oauth_token_secret'] ) )
				return;
			
			wp_enqueue_script( 'socialflow', plugins_url( 'js/socialflow-widget.js', __FILE__ ), array( 'jquery' ), '20111020' );
			wp_localize_script( 'socialflow', 'sf_l10n', $this->l10n );
		} elseif ( 'edit.php' == $hook ) {
			wp_enqueue_script( 'socialflow', plugins_url( 'js/socialflow.js', __FILE__ ), array( 'jquery' ), '20111020' );
			wp_localize_script( 'socialflow', 'sf_l10n', $this->l10n );
		}
	}

	/**
	 * Add SocialFlow link to row actions for posts and pages.
	 */
	public function row_actions( $actions, $post ) {
		if ( 'publish' == $post->post_status ) {
			$url = add_query_arg( array( 'action' => 'sf-publish', 'post' => $post->ID ), admin_url() );
			$timestamp = get_post_meta( $post->ID, 'sf_timestamp', true );
			$title = $timestamp ? $timestamp : __( 'Send to SocialFlow', 'socialflow' );
			$actions['sf_publish'] = '<a href="' . wp_nonce_url( $url, "sf-publish_{$post->ID}" ) . '" style="color: #532F64;" title="' . esc_attr( $title ) . '">' . __( 'Send to SocialFlow', 'socialflow' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Register the SocialFlow dashboard widget.
	 */
	public function register_dashboard_widget() {
		if ( current_user_can( 'publish_posts' ) )
			wp_add_dashboard_widget( 'socialflow', __( 'SocialFlow', 'socialflow' ), array( $this, 'dashboard_widget' ) );
	}

	/**
	 * Display the SocialFlow dashboard widget.
	 */
	public function dashboard_widget() {
		$options = get_option( 'socialflow' );

		if ( isset( $_POST['socialflow'], $_POST['sf_textnonce'] ) && wp_verify_nonce( $_POST['sf_textnonce'], plugin_basename( __FILE__ ) ) ) {
			require_once( dirname( __FILE__ ) ) . '/includes/class-wp-socialflow.php';
			$sf = new WP_SocialFlow( self::consumer_key, self::consumer_secret, $options['access_token']['oauth_token'], $options['access_token']['oauth_token_secret'] );
			$message = sanitize_text_field( $_POST['socialflow']['text'] );
			$publish_option = sanitize_text_field( $_POST['socialflow']['message_option'] );
			$publish_option = 'publishnow' == $publish_option ? 'publish now' : $publish_option;
			$account = sanitize_text_field( $_POST['socialflow']['account'] );

			if ( !$account ) {
				?><div class="sf-error"><p><?php _e( 'No social account selected.', 'socialflow' ); ?></p></div><?php
				return;
			}

			if ( $this->send_message( $message, $publish_option, $account ) ) {
				?><div class="sf-updated"><p><?php _e( 'Your message has been sent.', 'socialflow' ); ?></p></div><?php
			} else {
				?><div class="sf-error"><p><?php _e( 'There was a problem communicating with the SocialFlow API. Please Try again later. If this problem persists, please email support@socialflow.com', 'socialflow' ); ?></p></div><?php
			}
		}

		if ( 
			! $options ||
			empty( $options['access_token'] ) ||
			empty( $options['access_token']['oauth_token'] ) ||
			empty( $options['access_token']['oauth_token_secret'] ) ) {
			$this->display_authorize_form();
		} else {
			$this->display_compose_form();
		}
	}

	/**
	 * Display the authorize form for the dashboard widget.
	 */
	private function display_authorize_form() {
		require_once( dirname( __FILE__ ) ) . '/includes/class-wp-socialflow.php';
		$sf = new WP_SocialFlow( self::consumer_key, self::consumer_secret );
		if ( ! $request_token = $sf->get_request_token( add_query_arg( 'sf_oauth', true, admin_url() ) ) ) {
			?><div class="misc-pub-section"><p><span class="sf-error"><?php _e( 'There was a problem communicating with the SocialFlow API. Please Try again later. If this problem persists, please email support@socialflow.com', 'socialflow' ); ?></p></div><?php
			return;
		}

		$signup = 'http://socialflow.com/signup';
		if ( $links = $sf->get_account_links( self::consumer_key ) )
			$signup = $links->signup;

		$options = get_option( 'socialflow' );
		$options['oauth_token'] = $request_token['oauth_token'];
		$options['oauth_token_secret'] = $request_token['oauth_token_secret'];
		
		update_option( 'socialflow', $options );
		?>
		<div class="socialflow-authorize">
			<p><?php _e( 'Optimize publishing to Twitter and Facebook using <a href="http://socialflow.com/">SocialFlow</a>.', 'socialflow' ); ?></p>
			<p><?php printf( __( 'Don’t have a SocialFlow account? <a href="%s">Sign Up</a>', 'socialflow' ), esc_url( $signup ) ); ?></p>
			<p><a href="http://support.socialflow.com/entries/20573086-wordpress-plugin-faq-help"><?php _e( 'Help/FAQ', 'socialflow' ); ?></a></p>

			<p><a class="button-primary" href="<?php echo esc_url( $sf->get_authorize_url( $request_token ) ); ?>"><?php _e( 'Connect to SocialFlow', 'socialflow' ); ?></a></p>
		</div>
		
		<?php
	}

	/**
	 * Display the compose message form for the dashboard widget.
	 */
	public function display_compose_form( $post = null, $metabox = array( 'args' => array( 'post_page' => false ) ) ) {
		$message_option = 'optimize';
		$options = get_option( 'socialflow' );

		?>
		<?php if ( empty( $options['accounts'] ) ) :
			?><div class="misc-pub-section"><p><span class="sf-error"><?php _e( 'You have not authorized SocialFlow to optimize any Twitter accounts or Facebook Pages. Please go to <a href="https://app.socialflow.com">SocialFlow</a> to set this up.', 'socialflow' ); ?></p></div><?php
		else : ?>
		
		<form name="sf-post" method="post" id="sf-post">
		<?php wp_nonce_field( plugin_basename( __FILE__ ), 'sf_textnonce' ); ?>
		<div class="submitbox">
			<div id="minor-publishing">
				<div id="minor-publishing-actions">
					<ul>
						<li id='compose'><?php _e( 'Compose', 'socialflow' ); ?></li>
						<li id="sf_char_count"><span>140</span></li>
						<li id="shorten-links">
							<img src="<?php echo admin_url( 'images/wpspin_light.gif' ); ?>" class="ajax-loading" id="ajax-loading" alt="">
							<?php if ( empty( $post ) ) : ?>
							<a href="#" class="shorten-links"><?php _e( 'Shorten Links', 'socialflow' ); ?></a>
							<?php endif; ?>
						</li>
					</ul>
					<textarea rows="1" cols="40" name="socialflow[text]" tabindex="6" id="sf-text"><?php if ( $metabox['args']['post_page'] ) echo esc_textarea( get_post_meta( $post->ID, 'sf_text', true ) ); ?></textarea>
				</div>

			<?php if ( empty( $post ) ) : ?>
				<div id="misc-publishing-actions">

					<div class="misc-pub-section">
						<label for="sf_message_option"><?php _e( 'Default Message Option:', 'socialflow' ); ?></label>
						<select id="sf_message_option" name="socialflow[message_option]">
							<option value="optimize" <?php selected( $options['publish_option'], 'optimize' ); ?>><?php _e( 'Optimize', 'socialflow' ); ?></option>
							<option value="publishnow" <?php selected( $options['publish_option'], 'publishnow' ); ?>><?php _e( 'Publish Now', 'socialflow' ); ?></option>
							<option value="hold" <?php selected( $options['publish_option'], 'hold' ); ?>><?php _e( 'Hold', 'socialflow' ); ?></option>
						</select>
					</div>

					<div class="misc-pub-section misc-pub-section-last" id="sf-accounts">						
						<label for="sf-account-display"><?php _e( 'Send composed message to this account:', 'socialflow' ); ?></label>
						<div id="edit-accounts-div">
							<?php if ( !empty( $options['accounts'] ) ) {
								$default = reset( $options['accounts'] );
								foreach ( $options['accounts'] as $account ) {
									if ( 'publishing' != $account['service_type'] )
										continue;
									$id = esc_attr( $account['client_service_id'] );
									?><label for="<?php echo 'account-' . $id; ?>" class="selectit"><input type="radio" name="socialflow[account]" id="<?php echo 'account-' . $id; ?>" value="<?php echo $id; ?>" <?php checked( $id, $default['client_service_id'] ); ?>/> <?php echo esc_html( $account['name'] . ' - ' . ucfirst( $account['account_type'] ) ); ?></label><br><?php
									}
								} ?>
						</div>
					</div>

				</div>
			</div>

			<div id="major-publishing-actions">
				<div id="sf-view">View Message Activity on <a href="http://www.socialflow.com/">SocialFlow</a></div>
				<div id="publishing-action"><?php submit_button( __( 'Send to SocialFlow', 'socialflow' ), 'primary', 'authorize', false ); ?></div>
				<div class="clear"></div>
			</div>
			<?php else : ?>
				<div id="sf-shorten-message"><?php _e( 'Link to blog post will automatically be shortened and appended to this message.', 'socialflow' ); ?></div>
		</div>
			<?php endif; ?>
		</div>
			</form>
		<?php endif;
	}

	function save_post( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;

		if ( ! isset( $_POST['sf_textnonce'] ) || ! wp_verify_nonce( $_POST['sf_textnonce'], plugin_basename( __FILE__ ) ) )
			return;

		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return;
		} else {
		    if ( ! current_user_can( 'edit_post', $post_id ) )
		        return;
		}

		update_post_meta( $post_id, 'sf_text', sanitize_text_field( $_POST['socialflow']['text'] ) );
	}

	function transition_post_status( $new_status, $old_status, $post ) {
		if ( 'publish' != $new_status || 'publish' == $old_status )
		 	return;

		$message = isset( $_GET['sf_text'] ) ? sanitize_text_field( $_GET['sf_text'] ) : get_post_meta( $post->ID, 'sf_text', true );
		$message .= ' ' . get_permalink( $post->ID );

		$options = get_option( 'socialflow' );
		$options['publish_option'] = 'publishnow' == $options['publish_option'] ? 'publish now' : $options['publish_option'];

		require_once( dirname( __FILE__ ) ) . '/includes/class-wp-socialflow.php';
		$sf = new WP_SocialFlow( self::consumer_key, self::consumer_secret, $options['access_token']['oauth_token'], $options['access_token']['oauth_token_secret'] );

		$accounts = $options['accounts'];
		$service_user_ids = array();
		$account_types = array();
		
		foreach ( $accounts as $key => $account ) {
			if ( 'publishing' != $account['service_type'] || 'on' != $account['status'] ) {
				unset( $accounts[ $key ] );
			} else {
				$service_user_ids[] = $account['service_user_id'];
				$account_types[] = $account['account_type'];
			}
		}

		$service_user_ids = implode( ',', $service_user_ids );
		$account_types = implode( ',', $account_types );

		$return = $sf->add_multiple( $message, $service_user_ids, $account_types, $options['publish_option'], 1 );

		if ( $return )
			update_post_meta( $post->ID, 'sf_timestamp', date_i18n( 'Y-m-d G:i:s', false, 'gmt' ) . ' UTC' );

		return $return;
	}

	public function shorten_message( $message = '' ) {
		if ( !$message = $_REQUEST['sf_message'] )
			die;

		$account = $_REQUEST['sf_account'];
		$options = get_option( 'socialflow' );
		$account = $options['accounts'][$account];

		require_once( dirname( __FILE__ ) ) . '/includes/class-wp-socialflow.php';
		$sf = new WP_SocialFlow( self::consumer_key, self::consumer_secret, $options['access_token']['oauth_token'], $options['access_token']['oauth_token_secret'] );

		echo $sf->shorten_links( $message, $account['service_user_id'], $account['account_type'] );
		die;
	}

	public function send_message( $message = '', $message_option = 'publish now', $account_id, $shorten_links = 0 ) {
		$options = get_option( 'socialflow' );
		require_once( dirname( __FILE__ ) ) . '/includes/class-wp-socialflow.php';
		$sf = new WP_SocialFlow( self::consumer_key, self::consumer_secret, $options['access_token']['oauth_token'], $options['access_token']['oauth_token_secret'] );

		if ( empty( $options['accounts'] ) )
			return false;


		return $sf->add_message( $message, $options['accounts'][$account_id]['service_user_id'], $options['accounts'][$account_id]['account_type'], $message_option, $shorten_links );
	}

	public function admin_notices() {
		if ( !isset( $_GET['sf_sent'] ) ) 
			return;

		if ( $_GET['sf_sent'] ) {
			?><div class="updated"><p><?php _e( 'The post has been sent to SocialFlow.', 'socialflow' ); ?></p></div><?php
		} else {
			?><div class="error"><p><?php _e( 'There was a problem communicating with the SocialFlow API. Please Try again later. If this problem persists, please email support@socialflow.com', 'socialflow' ); ?></p></div><?php
		}
		$_SERVER['REQUEST_URI'] = remove_query_arg( 'sf_sent', $_SERVER['REQUEST_URI'] );
	}
}

new SocialFlow_Plugin;
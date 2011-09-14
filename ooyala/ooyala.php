<?php
/*
Plugin Name: Ooyala Video
Plugin URI: http://www.ooyala.com/wordpressplugin/
Description: Easy Embedding of Ooyala Videos based off an Ooyala Account as defined in the <a href="options-general.php?page=ooyalavideo_options_page"> plugin settings</a>.
Version: 1.4.1
License: GPL
Author: David Searle

Contact mail: wordpress@ooyala.com
*/

require_once( dirname(__FILE__) . '/class-ooyala-backlot-api.php' );

class Ooyala_Video {
	
	const VIDEOS_PER_PAGE = 8;
	var $plugin_dir;
	var $plugin_url;
	var $partner_code;
	var $secret_code;
	
	/**
	 * Singleton
	 */
	function &init() {
		static $instance = false;

		if ( !$instance ) {
			load_plugin_textdomain( 'ooyalavideo', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			$instance = new Ooyala_Video;
		}

		return $instance;
	}

	/**
	 * Constructor
	 */
	function __construct() {
		
		$this->plugin_dir = plugin_dir_path( __FILE__ );
		$this->plugin_url = plugin_dir_url( __FILE__ );

		$this->partner_code = get_option( 'ooyalavideo_partnercode' );
		$this->secret_code  = get_option( 'ooyalavideo_secretcode' );
	
		// Add link to settings page
		
		add_action( 'admin_menu', 				array( &$this, 'options_page' 		) );
		add_action( 'admin_init', 				array( &$this, 'register_settings'	) );
		add_action( 'admin_init', 				array( &$this, 'register_script' 	) );
		add_action( 'media_buttons', 			array( &$this, 'media_button'		), 999 );
		add_action( 'wp_ajax_ooyala_popup', 	array( &$this, 'popup' 				) );
		add_action( 'wp_ajax_ooyala_set', 		array( &$this, 'ooyala_set' 		) );
		add_action( 'wp_ajax_ooyala_request', 	array( &$this, 'ooyala_request' 	) );

		add_filter( 'plugin_action_links',      array( $this, 'add_settings_link'   ), 10, 2 );
		
		add_shortcode( 'ooyala', array(&$this, 'shortcode') );
		
	}
	
	function Ooyala_Video() {
		$this->__construct();
	}

	/**
	* Migrate the secret and partner code from the config.php file, if exists.
	* Only runs on plugin activation if option is not set.
	*/
	function migrate_config() {

		// Check no options are set yet
		if ( false === get_option( 'ooyalavideo_partnercode' ) && false === get_option( 'ooyalavideo_secretcode' ) ) {
			$config_file = dirname(__FILE__).'/config.php';

			if ( file_exists( $config_file ) ) {
				include_once( $config_file);		
				if ( defined( 'OOYALA_PARTNER_CODE' ) )
					update_option( 'ooyalavideo_partnercode', esc_attr( OOYALA_PARTNER_CODE ) );
				if ( defined( 'OOYALA_SECRET_CODE' ) )
					update_option( 'ooyalavideo_secretcode', esc_attr( OOYALA_SECRET_CODE ) );
			}
				
		}
	}
	

	/**
	* Settings link in the plugin actions links
	*/
	function add_settings_link( $links, $file ) {

		if ( plugin_basename( __FILE__ ) == $file ) {
			$settings_link = '<a href="' . menu_page_url( 'ooyalavideo_options', false ). '">' . __( 'Settings', 'ooyalavideo' ) . '</a>';
			array_unshift( $links, $settings_link );
		}

		return $links;
	}
	
	/**
	 * Registers and localizes the plugin javascript
	 */
	function register_script() {
		wp_register_script( 'ooyala', $this->plugin_url . 'js/ooyala.js', array( 'jquery' ), '1.4' );
		wp_localize_script( 'ooyala', 'ooyalaL10n', array(
			'latest_videos' => __( 'Latest Videos', 'ooyalavideo' ),
			'search_results' => __( 'Search Results', 'ooyalavideo' ),
			'done' => __( 'Done!', 'ooyalavideo' ),
			'upload_error' => __( 'Upload Error', 'ooyalavideo' ),
			'use_as_featured' => __( 'Use as featured image', 'ooyalavideo' ),
		) );
	}
	
	/**
	 * Shortcode Callback
	 * @param array $atts Shortcode attributes
	 */
	function shortcode( $atts ) {
		
		/* Example shortcodes:
		  Legacy: [ooyala NtsSDByMjoSnp4x3NibMn32Aj640M8hbJ]
		  Updated: [ooyala code="NtsSDByMjoSnp4x3NibMn32Aj640M8hbJ" width="222" ]
		*/
		
		extract(shortcode_atts(array(
			'width' => '',
			'code' => ''), $atts
		));
		
		
		if ( empty($width) )
			$width = get_option('ooyalavideo_width');
		if ( empty($width) )
			$width = $GLOBALS['content_width'];
		if ( empty($width) )
				$width = 500;

		$width = (int) $width;		
		$height = floor( $width*9/16 );
			
		if ( empty( $code ) )
			if ( isset( $atts[0] ) )
				$code = $atts[0];
			else 
				return '<!--Error: Ooyala shortcode is missing the code attribute -->';
	
		if( preg_match( "/[^a-z^A-Z^0-9^\-^\_]/i", $code ) )
			return '<!--Error: Ooyala shortcode attribute contains illegal characters -->';			
				
		if ( !is_feed() ) {
			$output = "<script src='http://player.ooyala.com/player.js?width={$width}&height={$height}&embedCode={$code}'></script><noscript><object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' id='ooyalaPlayer_7n2iz_gewtz7xi' width='{$width}' height='{$height}' codebase='http://fpdownload.macromedia.com/get/flashplayer/current/swflash.cab'><param name='movie' value='http://player.ooyala.com/player.swf?embedCode={$code}&version=2' /><param name='bgcolor' value='#000000' /><param name='allowScriptAccess' value='always' /><param name='allowFullScreen' value='true' /><param name='flashvars' value='embedType=noscriptObjectTag&embedCode=###VID###' /><embed src='http://player.ooyala.com/player.swf?embedCode={$code}&version=2' bgcolor='#000000' width='{$width}' height='{$height}' name='ooyalaPlayer_7n2iz_gewtz7xi' align='middle' play='true' loop='false' allowscriptaccess='always' allowfullscreen='true' type='application/x-shockwave-flash' flashvars='&embedCode={$code}' pluginspage='http://www.adobe.com/go/getflashplayer'></embed></object></noscript>";		
			
			// add HTML comment
			$output .= "\n<!-- Shortcode generated by WordPress plugin Ooyala Video -->\n";	
		} elseif ( 'true' == get_option('ooyalavideo_showinfeed')  ) {
			$output = __('[There is a video that cannot be displayed in this feed. ', 'ooyalavideo').'<a href="'.get_permalink().'">'.__('Visit the blog entry to see the video.]','ooyalavideo').'</a>';		
		}
		
		return $output;
	}
	
	/**
	 * Add options page
	 */
	function options_page() {
		add_options_page( 'Ooyala Video', 'Ooyala Video', 'manage_options', 'ooyalavideo_options', array(&$this, 'display_options') );
	}
		
	/**
	 * Register settings for the options page
	 */	
	function register_settings() {
		register_setting( 'ooyala-settings-group', 'ooyalavideo_partnercode', 'esc_attr');
		register_setting( 'ooyala-settings-group', 'ooyalavideo_secretcode', 'esc_attr');
		register_setting( 'ooyala-settings-group', 'ooyalavideo_showinfeed');
		register_setting( 'ooyala-settings-group', 'ooyalavideo_width', array( &$this, 'validate_video_size')  );
	}
	
	/**
	 * Width setting validation Callback
	 * @param string $width 
	 * @return int 
	 */
	function validate_video_size( $width ) {
		$width = absint( $width );

		if ( $width > 800 )
			$width = 800;
		elseif ( $width < 250 )
			$width = 250;
						
		return $width;
	}
	
	/**
	 * Callback that display the options form
	 */
	function display_options() {
		
		if (! current_user_can('manage_options') )
			return;
		?>

		<div style="width:75%;" class="wrap" id="ooyalavideo_options_panel">
			<h2><?php echo _e('Ooyala Video','ooyalavideo'); ?></h2>

			<a href="http://www.ooyala.com/"><img src="<?php echo $this->plugin_url; ?>img/ooyala_72dpi_dark_sm.png" title="<?php echo _e('Ooyala') ?>" alt="<?php echo _e('Ooyala Logo') ?>" /></a>

			<form action="options.php" method="post">
				<?php settings_fields( 'ooyala-settings-group' ); ?>
				<?php 
					$ooyalavideo_partnercode = get_option('ooyalavideo_partnercode' ); 
					$ooyalavideo_secretcode= get_option('ooyalavideo_secretcode' ); 
					$ooyalavideo_width = get_option('ooyalavideo_width' ); 
					$ooyalavideo_showinfeed = get_option('ooyalavideo_showinfeed' );
				?>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><label for="ooyalavideo_partnercode"><?php  _e('Ooyala Partner Code','ooyalavideo'); ?></label></th>
							<td><input type="text" value="<?php echo esc_attr( $ooyalavideo_partnercode ) ?>" name="ooyalavideo_partnercode" />
								<span class="description"><?php  _e('You can find your Partner and Secret codes under the Developers area of the Backlot Account tab','ooyalavideo'); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="ooyalavideo_secretcode"><?php  _e('Ooyala Secret Code','ooyalavideo'); ?></label></th>
							<td><input type="text" value="<?php echo esc_attr( $ooyalavideo_secretcode ) ?>" name="ooyalavideo_secretcode" />
								<span class="description"><?php  _e('You can find your Partner and Secret codes under the Developers area of the Backlot Account tab','ooyalavideo'); ?></span>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="ooyalavideo_showinfeed"><?php  _e('In feed, show link to blog post','ooyalavideo'); ?></label></th>
							<td><input type="checkbox" name="ooyalavideo_showinfeed" value="1" <?php checked($ooyalavideo_showinfeed); ?> />
								<span class="description"><?php  _e('Video embedding in feeds is not yet available','ooyalavideo'); ?></span>
							</td>
						</tr>

						<tr valign="top">
							<th scope="row"><label for="ooyalavideo_width"><?php _e('Video object width','ooyalavideo'); ?></label></th>
							<td><input type="text" value="<?php echo (int) $ooyalavideo_width ?>" name="ooyalavideo_width" size="5" maxlength="3" />
								<span class="description">(250-800)</span>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input name="Submit" type="submit" value="<?php esc_attr_e('Save Settings', 'ooyalavideo'); ?>" class="button-primary"/>
				</p>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Adds the Ooyala button to the media upload
	 */
	function media_button() {
		
		global $post_ID, $temp_ID;
		$iframe_post_id = (int) ( 0 == $post_ID ? $temp_ID : $post_ID );
		
		$title = esc_attr__( 'Embed Ooyala Video', 'ooyalavideo' );
		$plugin_url = esc_url( $this->plugin_url );
		$site_url = admin_url( "/admin-ajax.php?post_id=$iframe_post_id&amp;ooyala=popup&amp;action=ooyala_popup&amp;TB_iframe=true&amp;width=768" );
		echo '<a href="' . $site_url . '&id=add_form" class="thickbox" title="' . $title . '"><img src="' . $plugin_url . 'img/ooyalavideo-button.png" alt="' . $title . '" width="13" height="12" /></a>';
	}
	
	
	/**
	 * Callback for ajax popup call. Outputs ooyala-popup.php
	 */
	function popup() {
		require_once( $this->plugin_dir . 'ooyala-popup.php' );
		die();
	}
	
	/**
	 * Adds a .jpg extension to the filename (for use with filenames retrieved from the thumbnail api)
	 * Called by set_thumbnail()
	 * @param string $filename 
	 * @return filename with added jpg extension
	 */
	function add_extension( $filename ) {
	    $info = pathinfo($filename);
	    $ext  = empty($info['extension']) ? '.jpg' : '.' . $info['extension'];
	    $name = basename($filename, $ext);
	    return $name . $ext;
	}

	/**
	 * Sets an external URL as post featured image ('thumbnail')
	 * Contains most of core media_sideload_image(), modified to allow fetching of files with no extension 
	 *
	 * @param string $url 
	 * @param int $_post_ID 
	 * @return $thumbnail_id - id of the thumbnail attachment post id
	 */
	function set_thumbnail( $url,  $_post_id ) {

		if ( !current_user_can( 'edit_post', $_post_id ) )
			die( '-1' );
		
		if ( empty( $_post_id) )
			die( '0');
					
		add_filter('sanitize_file_name', array(&$this, 'add_extension' ) );

		// Download file to temp location
		$tmp = download_url( $url );
		remove_filter('sanitize_file_name', array(&$this, 'add_extension' ) );

		preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $tmp, $matches);
		$file_array['name'] = basename($matches[0]);
		$file_array['tmp_name'] = $tmp;

		// If error storing temporarily, unlink
		if ( is_wp_error( $tmp ) ) {
			@unlink($file_array['tmp_name']);
			$file_array['tmp_name'] = '';
		}

		// do the validation and storage stuff
		$thumbnail_id = media_handle_sideload( $file_array, $_post_id, '' );
		
		// If error storing permanently, unlink
		if ( is_wp_error($thumbnail_id) ) {
			@unlink($file_array['tmp_name']);
			return false;
		}
		
		return $thumbnail_id;
	}
	
	/**
	 * Ajax callback that sets a post thumbnail based on an ooyala embed id
	 *
	 * @uses OoyalaBacklotAPI::get_promo_thumbnail to get the thumbnail url
	 * @uses Ooyala_Video::set_thumbnail() to set fetch the image an set it
	 * @uses core's set_post_thumbnail() to set the link between post and thumbnail id
	 *
	 * output html block for the meta box (from _wp_post_thumbnail_html() )
	 */
	function ooyala_set() {
		global $post_ID;

		$nonce = isset( $_POST ['_wpnonce'] ) ?  $_POST['_wpnonce'] : '';

		if (! wp_verify_nonce($nonce, 'ooyala') )
		 	die('Security check');
		
		$embed =  isset( $_POST['embed'] ) ? esc_attr( $_POST['embed'] ) : '';
		$_post_id = absint( $_POST['postid'] );

		// Make sure the global is set, otherwise the nonce check in set_post_thumbnail() will fail
		$post_ID = (int) $_post_id;
		
		//Let's set the thumbnails size
		if ( isset($_wp_additional_image_sizes['post-thumbnail']) ) {
			$thumbnail_width = $_wp_additional_image_sizes['post-thumbnail']['width'];
			$thumbnail_height = $_wp_additional_image_sizes['post-thumbnail']['height'];
		}
		else {
			$thumbnail_width = 640;
			$thumbnail_height = 640;
		}

		if ( !empty( $embed ) )
			$results = OoyalaBacklotAPI::query(array(
				'embedCode' => $embed,
				'range' => '0-1',
				'resolution' => $thumbnail_width . 'x' . $thumbnail_height
			), 'thumbnails' );
		else
			return;
		
		$url = OoyalaBacklotAPI::get_promo_thumbnail( $results);
		$thumbnail_id = $this->set_thumbnail( $url, $_post_id );
		
		if ( false !== $thumbnail_id ) {
			set_post_thumbnail( $_post_id, $thumbnail_id );	
			die( _wp_post_thumbnail_html( $thumbnail_id ) );
		}
		
	}
	
	/**
	 * Ajax callback that handles the request to Ooyala API from the Ooyala popup
	 *
	 * @uses OoyalaBacklotAPI::query() to run the queries
	 * @uses OoyalaBacklotAPI::print_results() to output the results
	 */
	
	function ooyala_request() {
		
		global $_wp_additional_image_sizes;
		
		if ( !isset( $_GET['ooyala'] ) )
			die('-1');
			
		$do = $_GET['ooyala'];
				
		$limit = Ooyala_Video::VIDEOS_PER_PAGE;
		
		$key_word = isset( $_GET['key_word'] ) ? esc_attr( $_GET['key_word'] ) : '';
		$pageid = isset( $_GET['pageid'] ) ? (int) $_GET['pageid'] : '';

		switch( $do ) {		
			case 'search':
				if ( '' != $pageid &&  '' != $key_word ) {
					$results = OoyalaBacklotAPI::query(	array(
						'text' => $key_word,
						'status' => 'live',
						'orderBy' => 'uploadedAt,desc',
						'limit' => $limit,
						'pageID' => $pageid,
						'queryMode' => 'AND' 
					) );
				} else if ( '' != $key_word ) {
					$results = OoyalaBacklotAPI::query(	array(
						'text' => $key_word,
						'status' => 'live',
						'orderBy' => 'uploadedAt,desc',
						'limit' => $limit,
						'queryMode' => 'AND' 
					));
				}
				else {
					echo 'Please enter a search term!';
					die();
				}
			break;
	 		case 'last_few':
				if ( !empty( $pageid) ) {
					$results = OoyalaBacklotAPI::query(array(
						'status' => 'live',
						'orderBy' => 'uploadedAt,desc',
						'pageID' => $pageid,
						'limit' => $limit
					));
				} else {		  
					$results = OoyalaBacklotAPI::query(array(
						'status' => 'live',
						'orderBy' => 'uploadedAt,desc',
						'limit' => $limit,
						'pageID' => '0'
					));
				}
			break;
		}

		// Check if OoyalaBacklotAPI::query returned an error
		if ( !is_wp_error( $results ) ) {
			$results = OoyalaBacklotAPI::print_results( $results );
		}

		// We may have got an error from OoyalaBacklotAPI::print_results this time, so check again
		if ( is_wp_error( $results ) ) 
			echo '<div class="error"></p>'. __('Error:', 'ooyalavideo') . " {$results->get_error_message()} </p></div>";
		else 
			echo $results;

		die();
	}

}

//Run option migration on activation
register_activation_hook( __FILE__ , array( 'Ooyala_Video', 'migrate_config' ) );

//Launch
add_action( 'init', array( 'Ooyala_Video', 'init' ) );
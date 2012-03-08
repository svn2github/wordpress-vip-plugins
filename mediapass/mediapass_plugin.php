<?php

require_once(dirname(__FILE__) . "/mediapass_api.php");
require_once(dirname(__FILE__) . "/mediapass_plugin_content_list_ex.php");
require_once(dirname(__FILE__) . "/mediapass_plugin_content_filters.php");

class MediaPass_Plugin {

	const PLUGIN_NAME	=	'mediapass';
	const API_ENV		=	'stage';
	const CLIENT_ID		=	'97B9A5B07E8FCC853F1588FA6C024E36';
	
	const API_PREFIX	=	'https://api-staging.mediapass.com/';
	const FE_PREFIX		=	'https://web-staging.mediapass.com/';
	
	const NONCE			=	'mp-nonce';
	
	private $faq_feed_url = 'http://mymediapass.com/wordpress/2011/06/faq/feed/?withoutcomments=1';
	
	private $api_url;
	
	public static $auth_login_url;
	public static $auth_register_url;
	
	private $auth_refresh_url;
	private $auth_deauth_url;
	
	private $api_client;
	
	private $content_list_extensions;
	
	/**
	 * Options:
	 *  - mp_placement_categories - array( category_id ) specifying categories to include in subscription
	 *  - mp_user_number - ASSET ID fetched via authentication process
	 *  - mp_access_token - OAUTH token
	 *  - mp_installed_url - The site's url, cleaned up a bit
	 */
	 
	const OPT_INSTALLED_URL = 'mp_installed_url';
	const OPT_USER_ID		= 'mp_user_id'		;
	const OPT_USER_URL		= 'mp_user_url'		;
	const OPT_USER_ERROR	= 'mp_user_err'		;
	const OPT_USER_NUMBER	= 'mp_user_number'	;
	
	const OPT_PLACEMENT_CATEGORIES 	= 'mp_placement_categories';
	const OPT_PLACEMENT_AUTHORS		= 'mp_placement_authors';
	const OPT_PLACEMENT_TAGS		= 'mp_placement_tags';
	const OPT_PLACEMENT_DATES		= 'mp_placement_dates';
	
	const OPT_DEFAULT_PLACEMENT_MODE	=	'overlay';
	
	const OPT_ACCESS_TOKEN			= 'mp_access_token';
	const OPT_REFRESH_TOKEN			= 'mp_refresh_token';
	
	public function __construct() {
		$this->init_api_strings();

		$this->set_site_identifier_options();
		
		if( is_admin() ) {
			add_action('admin_init', array(&$this,'init_for_admin'));
			add_action('admin_menu', array(&$this,'add_admin_panel'));
		} 
		
		add_action('init', array(&$this,'init'));
	}
	
	private function check_nonce(){
		check_admin_referer( self::NONCE );
		
		return true;
	}
	private function is_vip() {
		return function_exists('wpcom_is_vip') && wpcom_is_vip();	
	}
	
	private function is_good_post(){
		return !empty($_POST) && $this->check_nonce();
	}
	
	private function init_api_strings() {
		$p = self::API_PREFIX;
		$c = self::CLIENT_ID;
		
		// not used yet, assuming always vip
		$partner = $this->is_vip() ? "wp-vip" : "wp";
		
		self::$auth_login_url 		= $p . 'account/auth/?partner=wp-vip&client_id='. $c .'&scope='. $p . 'auth.html&response_type=token&redirect_uri=';
		self::$auth_register_url 	= $p . 'account/authregister/?partner=wp-vip&client_id='. $c .'&scope=' . $p . 'auth.html&response_type=token&redirect_uri='; 
		$this->auth_refresh_url		= $p . 'oauth/refresh?client_id='. $c .'&scope=' . $p . 'auth.html&grant_type=refresh_token&redirect_uri=';
		$this->auth_deauth_url		= $p . 'oauth/unauthorize?client_id='. $c .'&scope=' . $p . 'auth.html&redirect_uri=';
		
		$this->api_client = new MediaPass('','',self::API_ENV);
	}
	
	private function set_site_identifier_options() {
		$mp_base_url = split("/", site_url());
    	$mp_strip_endurl = $mp_base_url[0] ."//". $mp_base_url[2];
		$mp_str_url = 'www.' . str_replace(array('http://','https://'), '', $mp_strip_endurl);

		$installed_url = get_option( self::OPT_INSTALLED_URL );
		if ( ! $installed_url || $installed_url != $mp_str_url )
			return;
		
		update_option( self::OPT_INSTALLED_URL , $mp_str_url );
	}
	
	private function register_scripts_for_admin() {
		wp_register_style(  'MPAdminStyles'   , plugins_url('/styles/admin.css', __FILE__));
		wp_register_script( 'MPAdminScripts'  , plugins_url('/js/admin.js',__FILE__));
		
		wp_register_script( 'MPAdminContentListEx', plugins_url('/js/mp-content-list-extensions.js',__FILE__));
		wp_register_script( 'MPAdminContentEditorEx', plugins_url('/js/mp-content-editor-extensions.js',__FILE__));
		
		wp_register_script( 'MPAdminCharts'	  , plugins_url('/js/charting.js',__FILE__));
		wp_register_script( 'MPAdminQuickTags', plugins_url('/js/qtags.js',__FILE__));
		
		wp_register_script( 'fieldselection'  , plugins_url('/js/fieldselection.min.js',__FILE__));
		wp_register_script( 'formfieldlimiter', plugins_url('/js/formfieldlimiter.js', __FILE__));
		
		wp_register_script( 'highcharts', 'http://www.highcharts.com/js/highcharts.js');
		
		wp_register_style( 'jquery-ui-style-flick', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/flick/jquery-ui.css', true);
	}
	
	private function enqueue_scripts_for_admin() {
		$hasPage = !empty($_GET['page']);
	
		wp_enqueue_script( 'MPAdminScripts' );
		wp_enqueue_script( 'MPAdminContentListEx');
		wp_enqueue_script( 'MPAdminContentEditorEx');
		
		wp_enqueue_style(  'MPAdminStyles'  );
		
		if( ! $hasPage ) {
			return;
		}
		
		$the_page = $_GET['page'];
		
		if ($the_page == 'mediapass_benefits') {
			wp_enqueue_script('media-upload');
			wp_enqueue_script('thickbox');
			wp_enqueue_script('formfieldlimiter');
			wp_enqueue_style('thickbox');
		} else if( $the_page == 'mediapass' || $the_page == 'mediapass_reporting') {
			wp_enqueue_script('highcharts');
			wp_enqueue_script('MPAdminCharts');		
		}
	}
	
	public function add_editor_buttons($buttons) {
   		array_push($buttons, "overlay_button", "inpage_button", "video_button");
   	
		return $buttons;
	}
	
	public function add_editor_plugins() {
		$plugin_array['overlay_button']	= plugins_url( '/js/overlay.js', __FILE__);
		$plugin_array['inpage_button'] 	= plugins_url( '/js/inpage.js' , __FILE__);
		$plugin_array['video_button'] 	= plugins_url( '/js/video.js'  , __FILE__);
	
		return $plugin_array;
	}
	
	private function init_editor_customizations() {
	   if ( current_user_can('edit_posts') &&  current_user_can('edit_pages') )
	   {
	     add_filter('mce_external_plugins'	, array(&$this, 'add_editor_plugins') );
	     add_filter('mce_buttons'			, array(&$this, 'add_editor_buttons') );
	   }
	}
	
	public function init_for_admin() {
		$this->register_scripts_for_admin();
		$this->init_editor_customizations();
		$this->enqueue_scripts_for_admin();
		
		$this->content_list_extensions = new MediaPass_Plugin_ContentListExtensions();
		
		add_action('add_meta_boxes', array(&$this,'add_meta_to_editor'));
	}
	
	public function init() {
		if( ! $this->has_publisher_data() ) {
			$this->get_publisher_data();
		}
		
		$this->content_filters = new MediaPass_Plugin_ContentFilters();
	}
	
	// check if installed domain matches the user account domain
	private function check_mp_match() {
		if( ! $this->has_publisher_data() ) {
			add_action('admin_notices', array(&$this, 'print_mismatch_error') );
		}
	
		if (!empty($_GET['deauthed'])) {
			add_action('admin_notices', array(&$this, 'print_deauthed_message') );
		}
	}

	public function print_mismatch_error() {
		echo "<div class='error'><p>The Web site you have installed the MediaPass plugin on doesn't have an associated MediaPass.com account. Please <a href='admin.php?page=mediapass'>connect your account here</a> or contact support@mediapass.com for help.</div>";
	}

	public	function print_deauthed_message() {
		echo "<div class='error'><p>You have successfully de-authorized this plugin and unlinked your MediaPass account.</p></div>";
	}

	private function has_publisher_data() {
		return get_option( self::OPT_USER_ID ) != 0;	
	}
	
	private function get_publisher_data() {
		// Prevent repeated lookups across pageloads when a site isn't authorized
		$cached = get_transient( 'mp_get_publisher_data_lock' );
		if ( false !== $cached )
			return;

		$pub = $this->api_client->get_publisher_data( get_option(self::OPT_USER_NUMBER) );
		
		$mp_userID 		= $pub['Id'];
		$mp_userURL 	= $pub['Domain'];
		$mp_userERROR 	= $pub['Error'];
		
		$mp_str_user_URL = str_replace(array('http://','https://'), '', $mp_userURL);
		
		update_option( self::OPT_USER_ID 	, $mp_userID );
		update_option( self::OPT_USER_URL	, $mp_str_user_URL );
		update_option( self::OPT_USER_ERROR	, $mp_userERROR );
		
		set_transient( 'mp_get_publisher_data_lock', true, 600 ); // 15 minutes seems like a healthy period
	}
	
	public function add_admin_panel(){
		if (!empty($_GET['access_token']) && !empty($_GET['refresh_token']) && !empty($_GET['id'])) {
			list( $access_token , $refresh_token, $id ) = array( $_GET['access_token'], $_GET['refresh_token'], $_GET['id'] );
		
			update_option( self::OPT_ACCESS_TOKEN , $access_token 	);
			update_option( self::OPT_REFRESH_TOKEN, $refresh_token 	);
			update_option( self::OPT_USER_NUMBER  , $id 			);
			
			delete_transient( 'mp_get_publisher_data_lock' ); // delete the transient to make sure we ping the API for fresh data
			$this->get_publisher_data();
		}
	
		$mp_user_ID 		= get_option(self::OPT_USER_NUMBER  );
		$mp_access_token 	= get_option(self::OPT_ACCESS_TOKEN );
		$mp_refresh_token 	= get_option(self::OPT_REFRESH_TOKEN);
		
		$this->check_mp_match();
		
		if ( $this->has_publisher_data() ) {
				
			$this->api_client = new MediaPass( $mp_access_token, $mp_user_ID , self::API_ENV );
			
			add_menu_page('MediaPass General Information', 'MediaPass', 'read', 'mediapass',array(&$this,'menu_default'));
			
			add_submenu_page('mediapass', 'MediaPass Account Information', 'Account Info', 'edit_others_posts', 'mediapass_accountinfo',array(&$this,'menu_account_info'));
			add_submenu_page('mediapass', 'MediaPass Reporting', 'Reporting', 'edit_others_posts', 'mediapass_reporting', array(&$this,'menu_reporting'));
			add_submenu_page('mediapass', 'MediaPass Placement Configuration', 'Placement', 'edit_others_posts', 'mediapass_placement', array(&$this,'menu_placement'));
		    add_submenu_page('mediapass', 'MediaPass Price Points', 'Price Points', 'update_core', 'mediapass_pricepoints',array(&$this,'menu_price_points'));
		    add_submenu_page('mediapass', 'MediaPass Update Benefits', 'Logo and Benefits', 'edit_others_posts', 'mediapass_benefits',array(&$this,'menu_benefits'));
			add_submenu_page('mediapass', 'MediaPass Metered Settings', 'Metered Settings', 'edit_others_posts', 'mediapass_metered_settings',array(&$this,'menu_metered'));
			add_submenu_page('mediapass', 'MediaPass Network Settings', 'Network Settings', 'update_core', 'mediapass_network_settings',array(&$this,'menu_network'));
		    add_submenu_page('mediapass', 'MediaPass FAQs, Terms and Conditions', 'FAQs', 'edit_posts', 'mediapass_faqs_tc',array(&$this,'menu_faqs_tc'));
		    add_submenu_page('mediapass', 'De-authorize MediaPass Account', 'De-Authorize', 'update_core', 'mediapass_deauth',array(&$this,'menu_deauth'));
			
			// Disabled for now, pending further development and refinement.
			//
			// add_submenu_page('mediapass', 'MediaPass eCPM Floor', 'eCPM Floor', 'administrator', 'mediapass_ecpm_floor',array(&$this,'menu_ecpm_floor'));
		} else {
			add_menu_page('MediaPass General Information', 'MediaPass', 'read', 'mediapass',array(&$this,'menu_signup'));
		}
	}
	

	public function add_meta_to_editor(){
		wp_enqueue_script('fieldselection');
		wp_enqueue_script('MPAdminQuickTags');
		
		add_meta_box(
			'mp-display-opts',
			'MediaPass Content Protection',
			array(&$this,'print_meta_section'),
			'post',
			'core',
			'high'
		);
		
		add_action('admin_print_footer_scripts', array(&$this,'print_init_quicktags'));
	}
		
	public function print_meta_section(){
		echo '<div class="misc-pub-section">Protect Full Page</div>';
		echo '<div class="misc-pub-section">Protect Partial Page Content</div>';
		echo '<div class="misc-pub-section">Protect Video</div>';
		echo '<p class="howto">TIP: For in-page or video content protection, hilight the content in the editor you wish to protect and select the appropriate protection type above.</p>';
	}
	
	public function print_init_quicktags(){
		echo '<script type="text/javascript">mp_init_quicktags();</script>';
	}
	
	private function render_or_error($data,$success) {
		if ($data['Status'] != 'fail') {
			include_once($success);
		} else {
			$error = $data['Msg'];
			include_once('includes/error.php');
		}
	}
	
	public function menu_reporting() {
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_style('jquery-ui-style-flick');
		
		include_once('includes/reporting.php');	
	}
	
	public function menu_signup() {
		include_once('includes/signup.php');
	}
	
	
	public function menu_metered() {
		$ok = isset($_POST['Status']) && isset($_POST['Count']);
		
		if ($this->is_good_post() && $ok) {
			list( $status, $count ) = array( $_POST['Status'], $_POST['Count'] );
		
			$data = $this->api_client->set_request_metering_status( $status, $count );
		} else {
			$data = $this->api_client->get_request_metering_status();
		}
		
		$this->render_or_error($data,'includes/metered.php');	
	}
	
	public function menu_default() {
		$un = get_option(self::OPT_USER_NUMBER);
		
		$period  = isset($_GET['period']) ? $_GET['period'] : false;
		$stats   = $this->api_client->get_reporting_summary_stats( $un, $period );
		$earning = $this->api_client->get_reporting_summary_earnings( $un );
		
		if ($stats['Status'] == 'success' && $earning['Status']['success']) {
			$data = array(
				'stats' => $stats['Msg'],
				'earning' => $earning['Msg']
			);
			include_once('includes/summary_report.php');
		} else {
			$error = "";
			if ($stats['Status'] != 'success') {
				$error .= $stats['Msg'];
			}
			if ($earning['Status'] != 'success') {
				$error .= $earning['Msg'];
			}
			include_once('includes/error.php');
		}
	}

	public function menu_placement() {
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('jquery-ui-tabs');
			
		$isPost = $this->is_good_post();
		
		$categories = get_categories();
		$tags		= get_tags();
		$authors    = get_users(array('who' => 'authors', 'fields' => 'all_with_meta'));
		
		if( $isPost ) {
		  	$to_update = $_POST['placement-update-section'];
			$checks = isset($_POST['checked']) ? $_POST['checked'] : array();
			
			$selected = array();
		  	
		  	foreach($checks as $check) {
		    	$selected[] = intval( $check ); // we're saving IDs
		  	}
			
		  	if( $to_update == 'category') {
			  	update_option(MediaPass_Plugin::OPT_PLACEMENT_CATEGORIES, $selected);
		  	} else if( $to_update == 'tag') {
		  		update_option(MediaPass_Plugin::OPT_PLACEMENT_TAGS, $selected);
		  	} else if( $to_update == 'author') {
		  		update_option(MediaPass_Plugin::OPT_PLACEMENT_AUTHORS, $selected);
		  	}
		}
		
		$selected   		= get_option(self::OPT_PLACEMENT_CATEGORIES);
		$selected_tags 		= get_option(self::OPT_PLACEMENT_TAGS);
		$selected_authors 	= get_option(self::OPT_PLACEMENT_AUTHORS);
		
		include_once('includes/placement.php');
	}
	
	public function menu_benefits() {
		$user_number = get_option(self::OPT_USER_NUMBER);
		
		if ($this->is_good_post()) {
			
			if (!empty($_POST['upload_image'])) {
				$pathinfo = pathinfo($_POST['upload_image']);
				if (in_array($pathinfo['extension'], array('jpg', 'jpeg'))) {
					$logo = $this->api_client->set_logo( $user_number, $_POST['upload_image']);
				}
			}
			
			$benefit = $this->api_client->set_benefits_text( $_POST['benefits'] );
		} else {
			$benefit = $this->api_client->get_benefits_text();
			$logo = $this->api_client->get_logo( $user_number );
		}
		
		$data = array(
			'Status' => $benefit['Status'],
			'Msg' => array(
				'benefit' => $benefit['Msg'],
				'logo' => $logo['Msg']
			)
		);
		
		$this->render_or_error($data,'includes/benefits.php');
	}
	
	public function menu_network() {
		$isPost = $this->is_good_post();
		$isActiveSiteUpdate = $isPost && isset($_POST['update-active-site-action']);
		$isPricingUpdate = $isPost && isset($_POST['update-active-network-pricing']);
		
		if ($isActiveSiteUpdate) {
			$networkSelected = $_POST['network-selected'];
	
			update_option( self::OPT_USER_NUMBER, $networkSelected );
		} else if( $isPricingUpdate ) {
			$increment_map = $this->api_client->membership_duration_increments;
			
			$price_model = array();
			
			foreach ($_POST['prices'] as $key => $price) {
				$price_model[$key] = $increment_map[$price['pricing_period']];
				$price_model[$key]['Price'] = $price['price'];
				$price_model[$key]['Type'] = 0;
			}
			
			$this->api_client->set_network_pricing( $price_model );
			
		} else if( $isPost ) {
			$data = $this->api_client->create_network_site( $_POST['Title'],  $_POST['Domain'],  $_POST['BackLink'] );
		} 
			
		$data = $this->api_client->get_network_list();
		$data['pricing_data'] = $this->api_client->get_network_pricing();
		
		$this->render_or_error($data, 'includes/network.php');
	}
	
	public function menu_account_info() {
		$user_number = get_option(self::OPT_USER_NUMBER);
		
		if ($this->is_good_post()) {
			$data = $this->api_client->api_call(array(
				'method' => 'POST',
				'action' => 'Account',
				'body' => array_merge(array(
					'Id' => (int) $user_number,
				), (array) $_POST)
			));
		} else {
			$data = $this->api_client->get_account_data($user_number);
		}
		
		$this->render_or_error($data,'includes/account_info.php');
	}
	
	public function menu_ecpm_floor() {
		if ($this->is_good_post()) {
			$data = $this->api_client->set_ecpm_floor( $_POST['ecpm_floor'] );
		} else {
			$data = $this->api_client->get_ecpm_floor();
		}
		
		$this->render_or_error($data,'includes/ecpm_floor.php');
	}
	
	public function menu_price_points() {
		// Increment: 2592000 for month, 31104000 for year, 86400 for day.
		// Type: 0 for memebership, 1 for single article
		$increment_map = $this->api_client->membership_duration_increments;
		
		$user_number = get_option(self::OPT_USER_NUMBER);
		
		if ($this->is_good_post()) {
			
			$price_model = array();
			
			switch ($_POST['subscription_model']) {
				case 'membership':
					foreach ($_POST['prices'] as $key => $price) {
						$price_model[$key] = $increment_map[$price['pricing_period']];
						$price_model[$key]['Price'] = $price['price'];
						$price_model[$key]['Type'] = 0;
					}
					break;
				
				case 'single':
					$price_model[] = array(
						'Type' => 1,
						'Length' => 1,
						'Increment' => 31104000,
						'Price' => $_POST['price']
					);
					break;
			}
			
			$this->api_client->set_active_pricing( $user_number, $price_model );	
		} 
		
		$data = $this->api_client->get_active_pricing($user_number);
		
		if ($data['Status'] == 'success') {
			$data = array(
				'subscription_model' => (count($data['Msg']) > 1) ? 'membership' : 'single',
				'prices' => $data['Msg']
			);
			include_once('includes/price_points.php');
		} else {
			$error = $data['Msg'];
			include_once('includes/error.php');
		}
	}
	
	public function menu_faqs_tc() {
		if ( ! function_exists( 'fetch_feed' ) )
			include_once(ABSPATH . WPINC . '/feed.php');
		$faq_feed = fetch_feed($this->faq_feed_url);
		if (!is_wp_error($faq_feed)) {
			$faq_items = $faq_feed->get_items(0, $faq_feed->get_item_quantity(5));
		}
		include_once('includes/faq_tc.php');
	}
	
	public function menu_deauth() {
		$escaped_uri = esc_url($_SERVER['REQUEST_URI']);
		$logo = plugins_url('/js/images/mplogo.gif', __FILE__);
		
		echo '<div class="mp-wrap">';
		echo 	'<h2 class="header"><img width="24" height="24" src="'. $logo . '" class="mp-icon"> De-Authorize Plugin</h2>';
		echo 	'<p>Are you sure you want to de-authorize this plugin?</p>';
		echo  	'<p><a href="' . $escaped_uri . '&deauth=true">Click here to de-authorize this plugin and unlink your MediaPass account.</a></p>';
		echo '</div>';
	}
	
	/**
	 * Clear the options database of all data.
	 * Generally for use during uninstall
	 */
	public static function delete_all_options() {
		$opts = array( 
			self::OPT_INSTALLED_URL, self::OPT_USER_ID, self::OPT_USER_URL, self::OPT_USER_ERROR, 
			self::OPT_USER_NUMBER, self::OPT_PLACEMENT_CATEGORIES, self::OPT_PLACEMENT_AUTHORS, 
			self::OPT_PLACEMENT_DATES, self::OPT_PLACEMENT_TAGS, self::OPT_ACCESS_TOKEN, 
			self::OPT_REFRESH_TOKEN, self::OPT_DEFAULT_PLACEMENT_MODE
		);
		
		foreach($opts as $o){
			delete_option($o);
		}
	}
	
	public function update_auth_status() {
		// De-authorize account if requested
		if (!empty($_GET['deauth'])) {
			$this->delete_all_options();
			
			wp_redirect( $this->auth_deauth_url . urlencode( menu_page_url( 'mediapass_deauth_success', false ) ) );
			exit;
		}
		
		if (!empty($_GET['page']) && $_GET['page'] == self::PLUGIN_NAME) {
			$mp_user_id 	  = get_option(self::OPT_USER_NUMBER   );
			$mp_access_token  = get_option(self::OPT_ACCESS_TOKEN  );
			$mp_refresh_token = get_option(self::OPT_REFRESH_TOKEN );
	
			if ($mp_user_id != 0 && $mp_refresh_token != 0 && $mp_access_token !== 0) {
	
				$response = $api_client->get_account_data($mp_user_id);
				
				if ($response['Msg'] == 'HTTP Error 401 Unauthorized') {
					$refresh_redirect = MP_AUTH_REFRESH_URL . urlencode("http" . ( is_ssl() ? "s" : null ) . "://" . $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']) . '&refresh_token=' . $mp_refresh_token;
					wp_redirect($refresh_redirect);
					exit;
				}
			}
		}
	}
}

?>

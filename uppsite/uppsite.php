<?php
/*
 Plugin Name: UppSite - App Your Site
 Plugin URI: http://www.uppsite.com/learnmore/
 Description: Uppsite is a fully automated plugin to transform your blog into native smartphone apps. **** DISABLING THIS PLUGIN WILL PREVENT YOUR APP USERS FROM USING THE APPS! ****
 Author: UppSite
 Version: 4.0
 Author URI: http://www.uppsite.com
  
 */

require_once( __DIR__ . '/fbcomments_page.inc.php' );

if (!defined('MYSITEAPP_AGENT')):

/** Plugin version **/
define('MYSITEAPP_PLUGIN_VERSION', '4.0');

/** User-Agent for mobile requests **/
define('MYSITEAPP_AGENT','MySiteApp');
/** Template for mobile requests **/
define('MYSTIEAPP_TEMPLATE','mysiteapp');
/** Template for web app **/
define('MYSITEAPP_TEMPLATE_WEBAPP', 'webapp');
/** API url **/
define('MYSITEAPP_WEBSERVICES_URL', 'http://api.uppsite.com');
/** Push services url **/
define('MYSITEAPP_PUSHSERVICE', MYSITEAPP_WEBSERVICES_URL.'/push/notification.php');
/** URL for application download **/
define('MYSITEAPP_APP_DOWNLOAD_URL', MYSITEAPP_WEBSERVICES_URL.'/click/get_app_download_link.php');
/** URL for last click **/
define('MYSITEAPP_APP_CLICK_URL', MYSITEAPP_WEBSERVICES_URL.'/click/click.php');
/** URL for report generator **/
define('MYSITEAPP_APP_DOWNLOAD_SETTINGS', MYSITEAPP_WEBSERVICES_URL.'/settings/options_response.php');
/** Facebook comments url **/
define('MYSITEAPP_FACEBOOK_COMMENTS_URL','http://graph.facebook.com/comments/?ids=');
/** Video width **/
define('MYSITEAPP_VIDEO_WIDTH', 270);

// Few constants
if (!defined('MYSITEAPP_PLUGIN_BASENAME'))
	define('MYSITEAPP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
if (!defined( 'WP_CONTENT_URL' ))
	define('WP_CONTENT_URL', get_option('siteurl').'/wp-content');
if (!defined('WP_CONTENT_DIR'))
	define('WP_CONTENT_DIR', ABSPATH.'wp-content');
if (!defined( 'WP_PLUGIN_URL'))
    define( 'WP_PLUGIN_URL', WP_CONTENT_URL.'/plugins');
if (!defined('WP_PLUGIN_DIR'))
    define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins');

/** Download link for the native application **/
$mysiteapp_download_link = false;


/**
 * Helper class which provides functions to detect mobile  
 *
 */
class MySiteAppPlugin {
	/**
	 * Is mobile device?
	 * @var boolean
	 */
	var $is_mobile = false;
	/**
	 * Is using MySiteApp's User-Agent
	 * (Probably mobile app)
	 * @var boolean
	 */
	var $is_agent = false;
	
	/**
	 * Constructor
	 */
	function MySiteAppPlugin() {
		/** Admin panel options **/
		if ( is_admin() )
			require_once( __DIR__ . '/uppsite_options.php' );

		$this->detect_user_agent();

		// Don't change the template directory when in the admin panel
		if ($this->is_mobile && ! is_admin() ) {
            add_filter( 'stylesheet', array(&$this, 'get_stylesheet') );
			add_filter( 'theme_root', array(&$this, 'theme_root') );
			add_filter( 'theme_root_uri', array(&$this, 'theme_root_uri') );
			add_filter( 'template', array(&$this, 'get_template') );
		}
	}
	
	/**
	 * Tell if this is a mobile browser
	 * @return boolean
	 */
	function is_webapp() {
		return $this->is_mobile && !$this->is_agent;
	}
	
	/**
	 * Tell if this is a navtive app of ours
	 * @return boolean
	 */
	function is_device() {
		return $this->is_mobile && $this->is_agent;
	}
	
	function detect_user_agent() {

		if (strpos($_SERVER['HTTP_USER_AGENT'], MYSITEAPP_AGENT) !== false) {
			// Mobile (from our applications)
			$this->is_mobile = true;
			$this->is_agent = true;
		} else {
			// Regular user
			$this->is_mobile = false;
			$this->is_agent = false;
		}
		
	}
	
	/**
	 * Returns the correct theme name
	 * @return mixed	Theme name
	 */
	function get_theme() {
		if ($this->is_agent) {
			// Native app
			return MYSTIEAPP_TEMPLATE;
		} elseif ($this->is_mobile) {
			// Mobile browser
			return MYSITEAPP_TEMPLATE_WEBAPP;
		} else {
			return null;
		}
	}

	/**
	 * Get the stylesheet according to the flow
	 * @param string $stylesheet	Current CSS
	 */
	function get_stylesheet($stylesheet) {
		$theme = $this->get_theme();
		if ($theme !== null) {
			$stylesheet = $theme;
		}

		return $stylesheet;
	}
	
	/**
	 * Returns the current template according to the flow
	 * @param string $template	Current template
	 */
	function get_template( $template ) {
		if ($this->is_agent) {
			define("MYSITEAPP_RUNNING","1");
			if ( function_exists( 'add_theme_support' ) )
				add_theme_support( 'post-thumbnails');
			return MYSTIEAPP_TEMPLATE;
		} elseif ($this->is_mobile) {
			return MYSITEAPP_TEMPLATE_WEBAPP;
		} else {
			return $template;
		}
	}
	
	/**
	 * Returns the theme root directory
	 * @param string $path
	 */
	function theme_root( $path ) {
		if ($this->is_mobile) {
			$pluginDir = dirname(__FILE__);
			if (defined('WP_PLUGIN_DIR')) {
				$pluginDir = WP_PLUGIN_DIR . '/' . mysiteapp_get_plugin_name();
			}
			return $pluginDir.'/themes';
		} else {
			return $path;
		}
	}
		  
	/**
	 * Returns the theme root uri
	 * @param string $url
	 */
	function theme_root_uri( $url ) {
		if ($this->is_mobile) {
			return WP_PLUGIN_URL.'/'.mysiteapp_get_plugin_name().'/themes';
		} else {
			return $url;
		}
	}
}

/**
 * Helper class to print MySiteApp XML
 */
class MysiteappXmlParser {
	/**
	 * The main function for converting to an XML document.
	 * Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
	 *
	 * @param array $data
	 * @param string $rootNodeName - what you want the root node to be - defaultsto data.
	 * @param SimpleXMLElement $xml - should only be used recursively
	 * @return string XML
	 */
	public static function array_to_xml($data, $rootNodeName = 'data', $xml=null)
	{
		// turn off compatibility mode as simple xml throws a wobbly if you don't.
		if (ini_get('zend.ze1_compatibility_mode') == 1) {
			ini_set ('zend.ze1_compatibility_mode', 0);
		}
		
		if ($xml == null) {
			$xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$rootNodeName />");
		}
		
		$childNodeName = substr($rootNodeName, 0, strlen($rootNodeName)-1);
		// loop through the data passed in.
		foreach($data as $key => $value) {
			// no numeric keys in our xml
			if (is_numeric($key)) {
				// make string key...
				$key = $childNodeName;
			}
			// if there is another array found recrusively call this function
			if (is_array($value)) {
				$node = $xml->addChild($key);
				// recrusive call.
				self::array_to_xml($value, $key, $node);
			} else  {
				// add single node.
				if (is_string($value)) {
	                $value = htmlspecialchars($value);// htmlentities($value);
					$xml->addChild($key,$value);
				} else {
					$xml->addAttribute($key,$value);
				}
			}
		}
		// pass back as string. or simple xml object if you want!
		return $xml->asXML();
	}
	
	public static function print_headers() {
		header("Content-type: text/xml");
	}
	
	public static function print_xml($parsed_xml) {
		self::print_headers();
		print $parsed_xml;
	}
}

// Create a global instance of MySiteAppPlugin
global $msap;
$msap = new MySiteAppPlugin();

/**
 * Returns the name of the plugin (as in the directory)
 * @return string
 */
function mysiteapp_get_plugin_name() {
	return trim( dirname( MYSITEAPP_PLUGIN_BASENAME ), '/' );
}


/**
 * Fixes youtube <embed> tags to fit mobile
 * @param array $matches
 */
function mysiteapp_fix_youtube_helper(&$matches) {
	$new_width = MYSITEAPP_VIDEO_WIDTH;

	$toreturn = $matches['part1']."%d".$matches['part2']."%d".$matches['part3'];
	$height = is_numeric($matches['objectHeight']) ? $matches['objectHeight'] : $matches['embedHeight'];
	$width = is_numeric($matches['objectWidth']) ? $matches['objectWidth'] : $matches['embedWidth'];
	$new_height = ceil(($new_width / $width) * $height);
	return sprintf($toreturn, $new_width, $new_height);
}

/**
 * Searches for youtube links and fixes them
 * @param array $matches
 */
function mysiteapp_fix_helper(&$matches) {
	if (strpos($matches['url1'], "youtube.com") !== false) {
		return mysiteapp_fix_youtube_helper($matches);
	}
	return $matches['part1'].$matches['objectWidth'].$matches['part2'].$matches['objectHeight'].$matches['part3'];
}

/**
 * Wrapper function for 'wp_logout_url', as WP below 2.7.0 doesn't support it.
 * 
 * @return string	Logout url
 */
function mysiteapp_logout_url_wrapper() {
	if (function_exists('wp_logout_url')) {
		return wp_logout_url();
	}
	// Create the URL ourselves
	$logout_url = site_url('wp-login.php') . "?action=logout";
	if (function_exists('wp_create_nonce')) {
		// Only create nonce if can
		// @since WP 2.0.3
		$logout_url .= "&amp;_wpnonce=" . wp_create_nonce('log-out');
	} 
	return $logout_url;
}

/**
 * Fix youtube embed videos, to show on mobile
 * @param string $subject	Text to search for youtube links
 * @return array	Matches
 */
function mysiteapp_fix_videos(&$subject) {
	$matches = preg_replace_callback("/(?P<part1><object[^>]*width=['\"])(?P<objectWidth>\d+)(?P<part2>['\"].*?height=['\"])(?P<objectHeight>\d+)(?P<part3>['\"].*?value=['\"](?P<url1>[^\"]+)['|\"].*?<\/object>)/ms", "mysiteapp_fix_helper", $subject);
	return $matches;
}

/**
 * Prints the post according to the layout
 *
 * @param int $iterator	Post number in the loop
 * @param string $posts_layout	Post layout
 */
function mysiteapp_print_post($iterator = 0, $posts_layout = 'full') {
	set_query_var('mysiteapp_should_show_post', mysiteapp_should_show_post_content($iterator, $posts_layout));
	if (defined("MYSITEAPP_RUNNING")) {
		get_template_part('post');
	}
}

/**
 * Lists the categories
 * @param string $thelist Category list
 * @return string	XML List of categories
 */
function mysiteapp_list_cat($thelist){
	if(defined("MYSITEAPP_RUNNING")){
		$thelist = mysiteapp_html_data_to_xml($thelist, 'category');
	}
	return $thelist;
}

/**
 * List of tags
 * @param string $thelist Tags list
 * @return string	XML containing the tags
 */
function mysiteapp_list_tags($thelist){
	if (defined("MYSITEAPP_RUNNING")) {
		$thelist = mysiteapp_html_data_to_xml($thelist, 'tag');
	}
	return $thelist;
}
/**
 * List of archives
 * @param string $output Archives list
 */
function mysiteapp_list_archive($output){
	if(defined("MYSITEAPP_RUNNING")){
		$output = mysiteapp_html_data_to_xml($output, 'archive');
	}
	return $output;

}

/**
 * Helper function to translate from HTML to XML
 * @param string $str HTML list
 * @param string $parent_node	XML output
 */
function mysiteapp_html_data_to_xml($str, $parent_node) {
	preg_match_all('/href=["\'](.*?)["\'](.*?)>(.*?)<\/a>/',$str,$result);
	$total = count($result[1]);
	$toreturn = null;
	for ($i=0; $i<$total; $i++) {
		$toreturn .= sprintf(
				"\t<%s>\n\t\t<title><![CDATA[%s]]></title>\n\t\t<permalink><![CDATA[%s]]></permalink>\n\t</%s>\n",
				$parent_node,
				$result[3][$i],
				$result[1][$i],
				$parent_node
			);
	}
	return $toreturn;
}

/**
 * Pages list
 * @param string $output HTML pages list
 * @return string XML output
 */
function mysiteapp_list_pages($output){
	if(defined("MYSITEAPP_RUNNING")){
		$output = mysiteapp_html_data_to_xml($output, 'page');
	}
	return $output;

}
/**
 * Links list
 * @param string $output HTML Links list
 * @return string XML output
 */
function mysiteapp_list_links($output){
	if(defined("MYSITEAPP_RUNNING")){
		$output = mysiteapp_html_data_to_xml($output, 'link');
	}
	return $output;
}
/**
 * Next links
 * @param string $thelist Next list
 */
function mysiteapp_navigation($thelist){
	if(defined("MYSITEAPP_RUNNING")){
		$thelist = mysiteapp_html_data_to_xml($thelist, 'navigation');
	}
	return $thelist;
}

/**
 * Prints user data of the logged in user
 * @param WP_User $user	The logged in user
 */
function mysiteapp_print_userdata($user){
	if(defined("MYSITEAPP_RUNNING")){
		set_query_var('mysiteapp_user', $user);
		get_template_part('user');
		exit();
	}
}

/**
 * Prints multiple errors
 * @param mixed $wp_error	WP error
 */
function mysiteapp_print_error($wp_error){
	?><mysiteapp result="false">
	<?php foreach ($wp_error->get_error_codes() as $code): ?>
		<error><![CDATA[<?php echo $code ?>]]></error>
	<?php endforeach; ?>
	</mysiteapp><?php
	exit();
}

/**
 * Login hook
 * Performs login with username and password
 * @param mixed $user	User object
 * @param string $username	Username
 * @param string $password	Password
 */
function mysiteapp_login($user, $username, $password){
	if(defined("MYSITEAPP_RUNNING")){
		$user = wp_authenticate_username_password($user, $username, $password);
		if(is_wp_error($user)){
			mysiteapp_print_error($user);
		} else{
			mysiteapp_print_userdata($user);
		}
	}
}

/**
 * Gracefully shows an XML error
 * Performs as an error handler
 * @param string $message	The message
 * @param string $title	Title
 * @param mixed $args	Arguments
 */
function mysiteapp_error_handler($message, $title = '', $args = array()) {
	?><mysiteapp result="false">
	<error><![CDATA[<?php echo $message ?>]]></error>
	</mysiteapp>
	<?php
	die();
}
/**
 * Redirects to UppSite's error handler
 * @param string $message Error message
 */
function mysiteapp_call_error($message) {
	if(defined("MYSITEAPP_RUNNING")){
		return 'mysiteapp_error_handler';
	}
}
/**
 * Helper function to extract url from a string
 * @param string $str	The extracted URL
 */
function mysiteapp_extract_url($str) {
	if ($str) {
		$regex = "((https?|ftp)\:\/\/)?"; // SCHEME
		$regex .= "([a-zA-Z0-9+!*(),;?&=\$_.-]+(\:[a-zA-Z0-9+!*(),;?&=\$_.-]+)?@)?"; // User and Pass
		$regex .= "([a-zA-Z0-9-.]*)\.([a-z]{2,3})"; // Host or IP
		$regex .= "(\:[0-9]{2,5})?"; // Port
		$regex .= "(\/([a-zA-Z0-9+\$_-]\.?)+)*\/?"; // Path
		$regex .= "(\?[a-zA-Z+&\$_.-][a-zA-Z0-9;:@&%=+\/\$_.-]*)?"; // GET Query
		$regex .= "(#[a-zA-Z_.-][a-zA-Z0-9+\$_.-]*)?"; // Anchor

		preg_match('/'.$regex.'/', $str, $matches);
		if ($matches[0]) {
			return $matches[0];
		}
	}
	return null;
}

/**
 * Prints an array in XML format
 * @param array $arr
 */
function mysiteapp_print_xml($arr) {
	$result = MysiteappXmlParser::array_to_xml($arr, "mysiteapp");
	MysiteappXmlParser::print_xml($result);
}

/**
 * Helper function for posting from a mobile app
 */
function mysiteapp_post_new() {
	global $msap;
	global $post_ID, $form_action, $post, $user_ID;
	if ($msap->is_device() ) {
		if (!$post) {
			$post = get_default_post_to_edit( 'post', true );
			$post_ID = $post->ID;
		}
		$arr = array(
				'user'=>array('ID'=>$user_ID),
				'postedit'=>array()
			);
			
		if ( 0 == $post_ID ) {
			$form_action = 'post';
		} else {
			$form_action = 'editpost';
		}
		$arr['postedit'] = array('wpnonce'=>wp_create_nonce( 0 == $post_ID ? 'add-post' : 'update-post_' .  $post_ID ),
				'user_ID'=>(int) $user_ID,
				'original_post_status'=>esc_attr($post->post_status),
				'action'=>esc_attr($form_action),
				'originalaction'=>esc_attr($form_action),
				'post_type'=>esc_attr($post->post_type),
				'post_author'=>esc_attr( $post->post_author ),
				'referredby'=>esc_url(stripslashes(wp_get_referer())),
				'hidden_post_status'=>'',
				'hidden_post_password'=>'',
				'hidden_post_sticky'=>'',
				'autosavenonce'=>wp_create_nonce( 'autosave'),
				'closedpostboxesnonce'=>wp_create_nonce( 'closedpostboxes'),
				'getpermalinknonce'=>wp_create_nonce( 'getpermalink'),
				'samplepermalinknonce'=>wp_create_nonce( 'samplepermalink'),
				'meta_box_order_nonce'=>wp_create_nonce( 'meta-box-order'),
				'categories'=>array(),
			);
		if ( 0 == $post_ID ) {
			$arr['postedit']['temp_ID'] = esc_attr($temp_ID);
			$autosave = false;
		} else {
			$arr['postedit']['post_ID'] = esc_attr($post_ID);
		}
		mysiteapp_print_xml($arr);
		exit();
	}
}
/**
 * After post is being saved
 * @param int $post_id	The newly / updated post_id
 */
function mysiteapp_post_new_process($post_id) {
	global $msap;
	if ($msap->is_device() ) {
		$the_post = wp_is_post_revision($post_id);
		$arr = array(
				'user' => array('ID'=>$user_ID),
				'postedit' => array(
					'success'=>true,
					'post_ID'=>$post_id,
					'is_revision' => var_export(wp_is_post_revision($post_id), true),
					'permalink' => get_permalink($post_id)
				),
			);
		mysiteapp_print_xml($arr);
		exit();
	}
}

/**
 * Mobile logout
 */
function mysiteapp_logout() {
	global $msap;
	global $user_ID, $user;
	if ($msap->is_device() ) {
		$arr = array(
				'user'=>array('ID'=>$user_ID),
				'logout'=>array('success'=>(bool) $user_ID)
			);
		mysiteapp_print_xml($arr);
		exit();
	}
}

/**
 * Cleans the author name of the comment
 * @param int $comment_ID	Comment id
 * @return string	Stripped author name
 */
function mysiteapp_comment_author($comment_ID = 0) 
{
	$author = html_entity_decode($comment_ID) ;
	$stripped = strip_tags($author);
	echo $stripped;
}

/**
 * Displays comments
 */
function mysiteapp_comment_form() {
	ob_start();
	do_action('comment_form');
	$dump = ob_get_clean();
	if (preg_match_all('/name="([a-zA-Z0-9\_]+)" value="([a-zA-Z0-9\_\'&@#]+)"/', $dump, $matches)) {
		$total = count($matches[1]);
		for ($i=0; $i<$total; $i++) {
			echo "<".$matches[1][$i]."><![CDATA[".$matches[2][$i]."]]></".$matches[1][$i].">\n";
		}
	}
}
/**
 * Converts a date from WP format to unix format
 * @param string $datetime Date string (e.g. 2008-02-07 12:19:32)  
 */
function mysiteapp_convert_datetime($datetime) {
  $values = split(" ", $datetime);

  $dates = split("-", $values[0]);
  $times = split(":", $values[1]);

  $newdate = mktime($times[0], $times[1], $times[2], $dates[1], $dates[2], $dates[0]);

  return $newdate;
  
}
/**
 * Sign a message with the API secret
 * @param string $message	The message
 */
function mysiteapp_sign_message($message){
	$options = get_option('uppsite_options');
	$str = $options['uppsite_secret'].$message;
	return md5($str);
}

/**
 * Check if needs to search for a new application links
 * @return boolean Should ask UppSite server if there is a mobile app?
 */
function mysiteapp_is_need_new_link(){
	$last_check = get_option('uppsite_lastupdate_link');
	if (!$last_check) {
		// If not checked, check anyways.
		return true;
	}
	$week = 60*60*24*7;
	// Should update once in a week
	return mktime() > $week+$last_check;

}

/**
 * admin_init action
 * Setup parameters when admin enters.
 */
function mysiteapp_admin_init() {
	$require_options_update = false;
	$options = get_option('uppsite_options');
	
	if (!isset($options['uppsite_plugin_version'])) {
		$options['uppsite_plugin_version'] = MYSITEAPP_PLUGIN_VERSION;
		$require_options_update = true;
		
		// legacy fix
		$options['option_popup'] = 'Yes';
		$options['option_popup_time'] = 'Everytime';
	} elseif ($options['uppsite_plugin_version']!=MYSITEAPP_PLUGIN_VERSION) {
		$options['uppsite_plugin_version'] = MYSITEAPP_PLUGIN_VERSION;
		$require_options_update = true;
	}
	
	if ($require_options_update)
		update_option('uppsite_options', $options);
	
	mysiteapp_get_app_links();
}

/**
 * Retrives a list of application keys for the current website
 * and updates the database.
 */
function mysiteapp_get_app_links(){
	if(!mysiteapp_is_need_new_link()) {
		return;
	}
	
	$options = get_option('uppsite_options');

	if ( empty( $options['uppsite_key'] ) )
		return;

	$hash = mysiteapp_sign_message($options['uppsite_key']);
	$get = '?api_key='.$options['uppsite_key'].'&hash='.$hash;
	
	$response = wp_remote_get(MYSITEAPP_APP_DOWNLOAD_URL.$get);
	$data = json_decode($response['body'],true);
	if($data){
		// Iterate over the mobile platforms
		foreach($data as $key=>$value){
			update_option('uppsite_link_'.$key, $data[$key]['id']);
		}
		// Set updated in this time
		update_option('uppsite_lastupdate_link', mktime());
	}
}

/**
 * Returns the current version of the installed plugin.
 * @return	float	MySiteApp plugin version
 */
function mysiteapp_get_plugin_version() {
	return MYSITEAPP_PLUGIN_VERSION;
}

/**
 * Checks if there is a need to display the message for the user
 * @param int $last_time	Unix time of the last display
 * @return boolean
 */
function mysiteapp_is_user_need_link($last_time){
	$options = get_option('uppsite_options');
	$date_arr = array("Everytime"=>1, "Every Hour"=>60*60,"Every Day"=>60*60*24,"Every Week"=>60*60*24*7,"Every Month"=>60*60*24*30);
	$time_to_wait = $date_arr[$options['option_popup_time']];
	return mktime() > $time_to_wait + $last_time;
}

/**
 * Search for native application
 * Enter description here ...
 */
function mysiteapp_set_javascript_link(){
	global $mysiteapp_download_link;
	
	$options = get_option('uppsite_options');
	if ($options['option_popup'] == 'No') {
		return;
	}

	if (isset($_COOKIE['uppsite_last_link']) && is_numeric($_COOKIE['uppsite_last_link'])){
		if (!mysiteapp_is_user_need_link($_COOKIE['uppsite_last_link'])) 
			return;
	}
	
	$url_id = NULL;
	if (stristr($_SERVER['HTTP_USER_AGENT'],'iphone') || strstr($_SERVER['HTTP_USER_AGENT'],'iphone') ) {
		$url_id = get_option('uppsite_link_iphone');
	} elseif( stristr($_SERVER['HTTP_USER_AGENT'],'android') ) {
		$url_id = get_option('uppsite_link_android');
	}
	
	if ($url_id){		
		$mysiteapp_download_link = MYSITEAPP_APP_CLICK_URL.'?id='.$url_id;
		// Set cookie for 30 days
		setcookie('uppsite_last_link', ''.time(),time()+60*60*24*30,"/");
	}
	
}


/**
 * Informs the user he has a native app he can download
 * and suggests him with a link.
 */
function mysiteapp_show_link(){
	global $mysiteapp_download_link;
	
	if (is_home() && $mysiteapp_download_link):
	?><script type='text/javascript'>
		if (confirm('This website has a native app for your phone! Would you like to download it now?')) { 
			window.location.href='{$mysiteapp_download_link}';
		}
	</script><?php
	endif;
}

/**
 * Returns a picture of facebook user
 * @param string $fb_id Facebook user id
 * @return string	URL to the image
 */
function mysiteapp_get_pic_from_fb_id($fb_id){
	return 'http://graph.facebook.com/'.$fb_id.'/picture?type=small';
}

/**
 * Tries to fetch picture from facebook profile
 * @param string $fb_profile	Profile link
 * @return string	URL to the image
 */
function mysiteapp_get_pic_from_fb_profile($fb_profile){
	if(stripos($fb_profile,'facebook') === FALSE) {
			return false;
	}
	$user_id = basename($fb_profile);
	
	return mysiteapp_get_pic_from_fb_id($user_id);

}


/**
 * Prints a member object for a comment
 */
function mysiteapp_get_member_for_comment(){
	$disq = true;
	$need_g_avatar = true;
	$res = '';
	$user = array();
   
    $user['author'] = get_comment_author();
	$user['link'] = get_comment_author_url();
	
	$options = get_option('uppsite_options');
	
	// add facebook pic to user / disqus avatar
	if (isset($options['disqus'])){
		$user['avatar'] = mysiteapp_get_pic_from_fb_profile($user['link']);
		if ($user['avatar']) {
		$need_g_avatar = false;
		}
	}
	if ($need_g_avatar){
		if(function_exists('get_avatar') && function_exists('htmlspecialchars_decode')){
			$user['avatar']  = htmlspecialchars_decode(mysiteapp_extract_url(get_avatar(get_comment_author_email())));
		}
	}?>
<member>
	<name><![CDATA[<?php echo $user['author'] ?>]]></name>
	<member_link><![CDATA[<?php echo $user['link'] ?>]]></member_link>
	<avatar><![CDATA[<?php echo $user['avatar'] ?>]]></avatar>
</member><?php
}

/**
 * Returns a single comment from Facebook
 * @param array $fb_comment	Comment parameters
 */
function mysiteapp_print_single_facebook_comment($fb_comment){
	$avatar_url = mysiteapp_get_pic_from_fb_id($fb_comment['from']['id']);
?><comment ID="<?php echo $fb_comment['id'] ?>" post_id="<?php echo get_the_ID() ?>" isApproved="true">
	<permalink><![CDATA[<?php echo get_permalink() ?>]]></permalink>
	<time><![CDATA[<?php echo $fb_comment['created_time'] ?>]]></time>
	<unix_time><![CDATA[<?php echo strtotime($fb_comment['created_time']) ?>]]></unix_time>
	<member>
		<name><![CDATA[<?php echo $fb_comment['from']['name'] ?>]]></name>
		<member_link><![CDATA[]]></member_link>
		<avatar><![CDATA[<?php echo $avatar_url ?>]]></avatar>
	</member>
	<text><![CDATA[<?php echo $fb_comment['message'] ?>]]> </text>
</comment><?php
}

/**
 * Comment using facebook
 * @param int $comment_counter How many comments
 */
function mysiteapp_print_facebook_comments(&$comment_counter){
	$permalink = get_permalink();
	$comments_url = MYSITEAPP_FACEBOOK_COMMENTS_URL.$permalink;
	$res = '';
	$comment_counter = 0;
	
	//fetch comments from facebook.com
	$comment_json = wp_remote_get($comments_url);
	$avatar_url = htmlspecialchars_decode(mysiteapp_extract_url(get_avatar(0)));

	//check if comments exist
	if($comment_json){
		$comments_arr = json_decode($comment_json['body'],true);
		//check if comments exist
		if ($comments_arr == NULL || !array_key_exists($permalink,$comments_arr)) {
			return;
		}
		$comments_list = $comments_arr[$permalink]['data'];
		foreach($comments_list as $comment){
			$res .= mysiteapp_print_single_facebook_comment($comment,$avatar_url);
			//inner comment
			if (key_exists('comments',$comment)){
				foreach($comment['comments']['data'] as $inner_comment){					
				
					$res .= mysiteapp_print_single_facebook_comment($inner_comment);
					$comment_counter++;
				}
			}
			$comment_counter++;
		}
	}
	return $res;
}


/**
 * Comment using Facebook
 */
function mysiteapp_comment_to_facebook(){
	$options = get_option('uppsite_options');
	$val = (get_query_var('msa_facebook_comment_page') ? get_query_var('msa_facebook_comment_page') : NULL );
	if ($val) {
		if (isset($options['fbcomment']) && !isset($_POST['comment'])) {
		 	print mysiteapp_facebook_comments_page();
		 	exit;
		}
	}
}

/**
 * Comment using disqus
 * * Currently not working! *
 * 
 * @param string $location
 * @param string $comment
 */
function mysiteapp_comment_to_disq($location, $comment=NULL){
	global $msap;
	if ($msap->is_device()){
	$shortname  = strtolower(get_option('disqus_forum_url'));
	$disq_thread_url = '.disqus.com/thread/';
	$options = get_option('uppsite_options');
		if ($comment==NULL)
			$comment = $location;
	
	if(isset($options['disqus']) && strlen($shortname)>1){
		$post_details = get_post($comment->comment_post_ID, ARRAY_A);
		$fixed_title = str_replace(' ', '_', $post_details['post_title']);
		$fixed_title = strtolower($fixed_title);
		$str = 'author_name='.$comment->comment_author.'&author_email='.$comment->comment_author_email.'&subscribe=0&message='.$comment->comment_content;
		$post_data = array('body' =>$str);
		$url = 'http://'.$shortname.$disq_thread_url.$fixed_title.'/post_create/';
		$result = wp_remote_post($url,$post_data);
	}
}
	return $location;
}

/**
 * If surfing from mobile, turn the 'more' to 3 dots.
 * @param string $more	Current more text
 */
function mysiteapp_fix_content_more($more){
	global $msap;
	if ($msap->is_device()) {
		return '(...)';
	}
	return $more;
}

/**
 * Returns the layout of the posts, as the mobile application
 * wishes to display it.
 * 
 * @return string	Enum: full / ffull_rexcerpt / ffull_rtitle / title / excerpt
 */
function mysiteapp_get_posts_layout() {
	return get_query_var('posts_list_view');
}

/**
 * Tells whether there is a need to display the post content.
 * Will display the content in these situations:
 * - No post layout defined
 * - In post page ('full')
 * - First post & in 'First full, Rest title' / 'First full, Rest excerpt'
 * 
 * @param int $iterator	Number of the post (zero-based)
 * @param string $posts_list_view	The posts layout
 */
function mysiteapp_should_show_post_content($iterator = 0, $posts_layout = null) {
	if ($posts_layout == null)
		$posts_layout = mysiteapp_get_posts_layout();
	if (
			empty($posts_layout) || // Not set
			$posts_layout == 'full' || // Full post
			( $iterator == 0 && ($posts_layout == 'ffull_rexcerpt' || $posts_layout == 'ffull_rtitle')) // First post of "First Full, rest X"
		) {
		return true;
	}
	return false;
}

/**
 * Should the plugin hide the posts?
 * 
 * @return boolean
 */
function mysiteapp_should_hide_posts() {
	return get_query_var('posts_hide') == '1';
}
/**
 * Should the plugin hide the sidebar?
 * 
 * @return boolean
 */
function mysiteapp_should_hide_sidebar() {
	return get_query_var('sidebar_hide') == '1';
}

/**
 * query_vars filter
 * 
 * Adds more query string variables that will be available for the plugin
 * (These are requested by the mobile applications)
 * 
 * @param array $public_query_vars	Array of query string keys
 * @return array	Appended list of keys	
 */
function mysiteapp_query_vars($public_query_vars) {
	return array_merge(
		$public_query_vars,
		array(
			'sidebar_hide',
			'posts_hide',
			'posts_list_view',
			'msa_facebook_comment_page'
		)
	);
}


/**
 * Fix Facebook's social button which corrupts the view in mobile
 * @param string $content	The content
 */
function mysiteapp_fix_content_fb_social($content){
	global $msap;
	$fixed_content =  $content;
	if ($msap->is_device()){
		$fixed_content = preg_replace('/<p class=\"FacebookLikeButton\">.*?<\/p>/','',$content);				
		$fixed_content = preg_replace('/<iframe id=\"basic_facebook_social_plugins_likebutton\" .*?<\/iframe>/','',$fixed_content);				
	}
    return $fixed_content;
}

/**
 * Calls the specific function while discarding any output in the process
 * @param string $func	Function name
 * @return mixed	The function return value (if any)
 */
function mysiteapp_clean_output($func) {
	ob_start();
	$ret = call_user_func($func);
	ob_end_clean();
	return $ret;
}

/** Init hook **/
add_action('init', 'mysiteapp_set_javascript_link');
/** Header hook - shows link to native app if present **/
add_action('wp_head', 'mysiteapp_show_link');
/** After amin menu initializes **/
add_filter('wp_die_handler','mysiteapp_call_error');
/** List of categories **/
add_filter('the_category','mysiteapp_list_cat');
/** List of tags **/
add_filter('the_tags','mysiteapp_list_tags');
/** List of categories **/
add_filter('wp_list_categories','mysiteapp_list_cat');
/** Archive list **/
add_filter('get_archives_link','mysiteapp_list_archive');
/** Pages list **/
add_filter('wp_list_pages','mysiteapp_list_pages');
/** Links list **/
add_filter('wp_list_bookmarks','mysiteapp_list_links');
/** Tags **/
if ( function_exists('wp_tag_cloud') )
	add_filter('wp_tag_cloud','mysiteapp_list_tags');
/** Next links **/
add_filter('next_posts_link','mysiteapp_navigation');
/** Login hook for mobile **/
add_filter('authenticate', 'mysiteapp_login', 2, 3);
/** Logout hook */
add_action('wp_logout', 'mysiteapp_logout', 30);
/** Author of comment **/
add_action('comment_author', 'mysiteapp_comment_author');
/** Getting post-new.php params **/
add_action('load-post-new.php', 'mysiteapp_post_new');
/** Actual save */
add_action('save_post', 'mysiteapp_post_new_process');
/** Entrance to admin panel **/
add_action('admin_init','mysiteapp_admin_init',10);
/** MySiteApp query string variables **/
add_filter('query_vars', 'mysiteapp_query_vars');
/** Disqus **/
//add_filter('comment_post_redirect','mysiteapp_comment_to_disq',10,2);
/** Comment using facebook (set the template)  **/
add_action('template_redirect','mysiteapp_comment_to_facebook',10);
/** Fixing the "more..." for mobile **/
add_filter('the_content_more_link','mysiteapp_fix_content_more',10,1);
/** Content filter - fix facebook social **/
add_filter('the_content','mysiteapp_fix_content_fb_social',20,1);



endif; /*if (!defined('MYSITEAPP_AGENT')):*/
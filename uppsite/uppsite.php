<?php
/*
 Plugin Name: UppSite - Go Mobile
 Plugin URI: http://www.uppsite.com/learnmore/
 Description: Uppsite is a fully automated plugin to transform your blog into native smartphone apps. <strong>**** DISABLING THIS PLUGIN WILL PREVENT YOUR APP USERS FROM USING THE APPS! ****</strong>
 Author: UppSite
 Version: 4.0.3
 Author URI: https://www.uppsite.com
 */

require_once( dirname(__FILE__) . '/fbcomments_page.inc.php' );

if (!defined('MYSITEAPP_AGENT')):

/** Plugin version **/
define('MYSITEAPP_PLUGIN_VERSION', '4.0.3');

/** Theme name in cookie **/
define('MYSITEAPP_WEBAPP_PREF_THEME', 'uppsite_theme_select');
/** Theme save time in cookie **/
define('MYSITEAPP_WEBAPP_PREF_TIME', 'uppsite_theme_time');

/** UppSite's data option key */
define('MYSITEAPP_OPTIONS_DATA', 'uppsite_data');
/** UppSite's admin prefs */
define('MYSITEAPP_OPTIONS_OPTS', 'uppsite_options');
/** UppSite's prefs option key */
define('MYSITEAPP_OPTIONS_PREFS', 'uppsite_prefs');

/** User-Agent for mobile requests **/
define('MYSITEAPP_AGENT','MySiteApp');
/** Helper for the different enviornments (VIP / Standalone) */
require_once( dirname(__FILE__) . '/env_helper.php' );
/** Template root */
define('MYSITEAPP_TEMPLATE_ROOT', mysiteapp_get_template_root() );
/** Template for mobile requests **/
define('MYSITEAPP_TEMPLATE_APP', MYSITEAPP_TEMPLATE_ROOT.'/mysiteapp');
/** Template for web app **/
define('MYSITEAPP_TEMPLATE_WEBAPP', MYSITEAPP_TEMPLATE_ROOT.'/webapp');
/** Template for the mobile landing page **/
define('MYSITEAPP_TEMPLATE_LANDING', MYSITEAPP_TEMPLATE_ROOT.'/landing');
/** API url **/
define('MYSITEAPP_WEBSERVICES_URL', 'http://api.uppsite.com');
/** Push services url **/
define('MYSITEAPP_PUSHSERVICE', MYSITEAPP_WEBSERVICES_URL.'/push/notification.php');
/** URL for report generator **/
define('MYSITEAPP_APP_DOWNLOAD_SETTINGS', MYSITEAPP_WEBSERVICES_URL.'/settings/options_response.php');
/** URL for fetching native app link **/
define('MYSITEAPP_APP_NATIVE_URL', MYSITEAPP_WEBSERVICES_URL.'/getapplink.php');
/** URL for fetching API key & secret **/
define('MYSITEAPP_AUTOKEY_URL', MYSITEAPP_WEBSERVICES_URL.'/autokeys.php');
/** URL for fetching app preferences **/
define('MYSITEAPP_PREFERENCES_URL', MYSITEAPP_WEBSERVICES_URL.'/preferences.php');
/** URL for the minisite upon plugin installation */
define('MYSITEAPP_WEBAPP_MINISITE', MYSITEAPP_WEBSERVICES_URL.'/webapp/minisite.php?website=');
/** URL for resrouces */
define('MYSITEAPP_WEBAPP_RESOURCES', 'http://static.uppsite.com/v3/webapp');
/** Facebook comments url **/
define('MYSITEAPP_FACEBOOK_COMMENTS_URL','http://graph.facebook.com/comments/?ids=');
/** Video width **/
define('MYSITEAPP_VIDEO_WIDTH', 270);
/** One day in seconds */
define('MYSITEAPP_ONE_DAY', 86400); // 60*60*24

/** Number of posts that will contain content if the display mode is "first full, rest ..." */
define('MYSITEAPP_BUFFER_POSTS_COUNT', 5);

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

/**
 * Tells whether the webapp should be enabled (according to activation and display mode)
 * @return bool Webapp should be enabled
 */
function mysiteapp_should_show_webapp() {
    $options = get_option(MYSITEAPP_OPTIONS_OPTS);
    return isset($options['activated']) && $options['activated'] && isset($options['webapp_mode']) &&
        ($options['webapp_mode'] == "all" || $options['webapp_mode'] == "webapp_only");
}

/**
 * @return string   Native app url (or null if no such app exists)
 */
function uppsite_get_native_link() {
    $options = get_option(MYSITEAPP_OPTIONS_DATA);
    return isset($options['native_url']) ? $options['native_url'] : null;
}

/**
 * Tells whether the landing page ("selection page") should be enabled (according to activation and display mode)
 * @return bool Landing page should be enabled
 */
function mysiteapp_should_show_landing() {
    $options = get_option(MYSITEAPP_OPTIONS_OPTS);
    $showLanding = isset($options['activated']) && $options['activated'] && isset($options['webapp_mode']) &&
        ($options['webapp_mode'] == "all" || $options['webapp_mode'] == "landing_only");
    if ($showLanding && $options['webapp_mode'] == "landing_only") {
        $showLanding = $showLanding && !is_null(uppsite_get_native_link());
    }
    return $showLanding;
}

/**
 * Helper class which provides functions to detect mobile  
 */
class MySiteAppPlugin {
    /**
     * Is coming from mobile browser
     * @var boolean
     */
    var $is_mobile = false;
    /**
     * Is using MySiteApp's User-Agent
     * (Probably mobile app)
     * @var boolean
     */
    var $is_app = false;

    /**
     * The hooked template
     * @var string
     */
    var $new_template = null;

    /**
     * List of mobile user agents
     * @var array
     */
    var $_mobile_ua = array(
        "WebTV",
        "AvantGo",
        "Blazer",
        "PalmOS",
        "lynx",
        "Go.Web",
        "Elaine",
        "ProxiNet",
        "ChaiFarer",
        "Digital Paths",
        "UP.Browser",
        "Mazingo",
        "iPhone",
        "iPod",
        "Mobile",
        "T68",
        "Syncalot",
        "Danger",
        "Symbian",
        "Symbian OS",
        "SymbianOS",
        "Maemo",
        "Nokia",
        "Xiino",
        "AU-MIC",
        "EPOC",
        "Wireless",
        "Handheld",
        "Smartphone",
        "SAMSUNG",
        "J2ME",
        "MIDP",
        "MIDP-2.0",
        "320x240",
        "240x320",
        "Blackberry8700",
        "Opera Mini",
        "NetFront",
        "BlackBerry",
        "PSP",
        "Android"
    );
    
    /**
     * Constructor
     */
    function MySiteAppPlugin() {
        /** Admin panel options **/
        if (is_admin()) {
            require_once( dirname(__FILE__) . '/uppsite_options.php' );
        } else {
            $this->detect_user_agent();

            // Hooking the templates loading, if addressed from a native app
            if ($this->is_mobile || $this->is_app) {
                if (function_exists('add_theme_support')) {
                    // Add functionality of post thumbnails
                    add_theme_support( 'post-thumbnails');
                }
            }
        }
    }

    /**
     * Detects the user agent of the visitor, and marks how the plugin
     * should handle the user in the current run.
     */
    function detect_user_agent() {
        if (strpos($_SERVER['HTTP_USER_AGENT'], MYSITEAPP_AGENT) !== false) {
            // Mobile (from our applications)
            $this->is_app = true;
            $this->new_template = MYSITEAPP_TEMPLATE_APP;
        } elseif (mysiteapp_should_show_landing() || mysiteapp_should_show_webapp()) {
            if (preg_match('/('.implode('|', $this->_mobile_ua).')/i', $_SERVER['HTTP_USER_AGENT'])) {
                // Mobile user (from some browser)
                $this->is_mobile = true;
                $this->new_template = $this->get_webapp_template();
            }
        }
    }

    /**
     * Decide which template to show when coming from mobile.
     * If no choice was previously saved, a landing page is displayed.
     * @return string The template name that should be displayed.
     */
    function get_webapp_template() {
        $ret = mysiteapp_should_show_landing() ? "landing" : ( mysiteapp_should_show_webapp() ? "webapp" : "normal" );
        if (isset($_COOKIE[MYSITEAPP_WEBAPP_PREF_THEME]) && isset($_COOKIE[MYSITEAPP_WEBAPP_PREF_TIME])) {
            $ret = $_COOKIE[MYSITEAPP_WEBAPP_PREF_THEME];
            $saveTime = $_COOKIE[MYSITEAPP_WEBAPP_PREF_TIME];
            // Renew the saving time of the cookie
            setcookie(MYSITEAPP_WEBAPP_PREF_THEME, $ret, time() + $saveTime);
        }
        switch ($ret) {
            case "webapp":
                if (mysiteapp_should_show_webapp()) {
                    return MYSITEAPP_TEMPLATE_WEBAPP;
                }
                break;
            case "landing":
                if (mysiteapp_should_show_landing()) {
                    return MYSITEAPP_TEMPLATE_LANDING;
                }
                break;
        }
        // Normal mode - no webapp
        $this->is_mobile = false; // Disable the webapp and landing
        return null;
    }

    /**
     * @return bool Tells whether need to use custom theme for this request (app/webapp/landing) or not
     */
    function has_custom_theme() {
        return !is_null($this->new_template);
    }
}

/**
 * Helper class to print MySiteApp XML
 */
class MysiteappXmlParser {
    /**
     * The main function for converting to an XML document.
     * Pass in a multi dimensional array and this recursively loops through and builds up an XML document.
     *
     * @param array $data
     * @param string $rootNodeName - what you want the root node to be - defaults to data.
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
            // if there is another array found recursively call this function
            if (is_array($value)) {
                $node = $xml->addChild($key);
                // recursive call.
                self::array_to_xml($value, $key, $node);
            } else  {
                // add single node.
                if (is_string($value)) {
                    $value = htmlspecialchars($value);
                    $xml->addChild($key,$value);
                } else {
                    $xml->addAttribute($key,$value);
                }
            }
        }
        // pass back as string. or simple xml object if you want!
        return $xml->asXML();
    }

    public static function print_xml($parsed_xml) {
        header("Content-type: text/xml");
        print $parsed_xml;
    }
}

// Create a global instance of MySiteAppPlugin
global $msap;
$msap = new MySiteAppPlugin();

/**
 * Filter template/stylesheet name, and return the right template if running from mobile / app.
 * @param $newValue Value of 'template'/'stylesheet' from db
 * @return App/Mobile template if required, else the template name from db.
 */
function mysiteapp_filter_template($newValue) {
    global $msap;
    return $msap->has_custom_theme() ? $msap->new_template : $newValue;
}
add_filter('option_template', 'mysiteapp_filter_template'); // Filter 'get_option(template)'
add_filter('option_stylesheet', 'mysiteapp_filter_template'); // Filter 'get_option(stylesheet)'

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
 * @return string Content with YouTube links fixed.
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
 * @return string Content with fixed YouTube objects
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
 * @return string    Logout url
 */
function mysiteapp_logout_url_wrapper() {
    if (function_exists('wp_logout_url')) {
        return wp_logout_url();
    }
    // Create the URL ourselves
    $logout_url = site_url('wp-login.php') . "?action=logout";
    if (function_exists('wp_create_nonce')) {
        // Create nonce only if can
        // @since WP 2.0.3
        $logout_url .= "&amp;_wpnonce=" . wp_create_nonce('log-out');
    } 
    return $logout_url;
}

/**
 * Fix youtube embed videos, to show on mobile
 * @param string $subject    Text to search for youtube links
 * @return array    Matches
 */
function mysiteapp_fix_videos(&$subject) {
    $matches = preg_replace_callback("/(?P<part1><object[^>]*width=['\"])(?P<objectWidth>\d+)(?P<part2>['\"].*?height=['\"])(?P<objectHeight>\d+)(?P<part3>['\"].*?value=['\"](?P<url1>[^\"]+)['|\"].*?<\/object>)/ms", "mysiteapp_fix_helper", $subject);
    return $matches;
}

/**
 * Prints the post according to the layout
 *
 * @param int $iterator    Post number in the loop
 * @param string $posts_layout    Post layout
 */
function mysiteapp_print_post($iterator = 0, $posts_layout = 'full') {
    global $msap;
    set_query_var('mysiteapp_should_show_post', mysiteapp_should_show_post_content($iterator, $posts_layout));
    if ($msap->is_app) {
        get_template_part('post');
    }
}

/**
 * Helper function for converting html lists to xml
 * @param string $thelist  Output of the list
 * @param string $nodeName List type (category / tag / archive)
 * @return string   A XML-formatted list
 */
function mysiteapp_list($thelist, $nodeName) {
    global $msap;
    if ($msap->is_app) {
        preg_match_all('/href=["\'](.*?)["\'](.*?)>(.*?)<\/a>/', $thelist, $result);
        $total = count($result[1]);
        $thelist = "";
        for ($i=0; $i<$total; $i++) {
            $thelist .= sprintf(
                "\t<%s>\n\t\t<title><![CDATA[%s]]></title>\n\t\t<permalink><![CDATA[%s]]></permalink>\n\t</%s>\n",
                $nodeName,
                $result[3][$i],
                $result[1][$i],
                $nodeName
            );
        }
    }
    return $thelist;
}

/**
 * Lists the categories
 * @param string $thelist Category list
 * @return string    XML List of categories
 */
function mysiteapp_list_cat($thelist){
    return mysiteapp_list($thelist, 'category');
}

/**
 * List of tags
 * @param string $thelist Tags list
 * @return string    XML containing the tags
 */
function mysiteapp_list_tags($thelist){
    return mysiteapp_list($thelist, 'tag');
}
/**
 * List of archives
 * @param string $thelist Archives list
 * @return string Returns the list of archives as XML, if required.
 */
function mysiteapp_list_archive($thelist){
    return mysiteapp_list($thelist, 'archive');
}

/**
 * Pages list
 * @param string $thelist HTML pages list
 * @return string XML output
 */
function mysiteapp_list_pages($thelist){
    return mysiteapp_list($thelist, 'page');
}
/**
 * Links list
 * @param string $thelist HTML Links list
 * @return string XML output
 */
function mysiteapp_list_links($thelist){
    return mysiteapp_list($thelist, 'link');
}
/**
 * Next links
 * @param string $thelist Next list
 * @return string The list of navigation links in XML, if needed
 */
function mysiteapp_navigation($thelist){
    return mysiteapp_list($thelist, 'navigation');
}

/**
 * Prints multiple errors
 * @param mixed $wp_error    WP error
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
 * Performs login with username and password, and prints the userdata to the screen if login was successful
 * @param mixed $user    User object
 * @param string $username    Username
 * @param string $password    Password
 */
function mysiteapp_login($user, $username, $password){
    global $msap;
    if ($msap->is_app) {
        $user = wp_authenticate_username_password($user, $username, $password);
        if (is_wp_error($user)) {
            mysiteapp_print_error($user);
        } else {
            set_query_var('mysiteapp_user', $user);
            get_template_part('user');
        }
        exit();
    }
}

/**
 * Gracefully shows an XML error
 * Performs as an error handler
 * @param string $message    The message
 * @param string $title    Title
 * @param mixed $args    Arguments
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
 * @param string $function Die handling function
 */
function mysiteapp_call_error( $function ) {
    global $msap;
    if($msap->is_app){
        return 'mysiteapp_error_handler';
    }
    return $function;
}
/**
 * Extracts the src url form an html tag.
 * @param $html The HTML content
 * @return string|null The url if found, or null
 */
function uppsite_extract_src_url($html) {
    if (preg_match("/src=[\"']([\s\S]+?)[\"']/", $html, $match)) {
        return $match[1];
    }
    return null;
}

/**
 * Extracts the thumbnail url of the post by iterating
 * over popular plugins that provide the thumbnail image url
 * @note This function should be called inside the post loop.
 */
function mysiteapp_extract_thumbnail() {
    $thumb_url = null;
    
    if (function_exists('has_post_thumbnail') && has_post_thumbnail()) {
        // Built-in function
        $thumb_url = get_the_post_thumbnail();
    }
    if (empty($thumb_url) && function_exists('the_attached_image')) {
        // The Attached Image plugin
        $temp_thumb = the_attached_image('img_size=thumb&echo=false');
        if (!empty($temp_thumb)) {
            $thumb_url = $temp_thumb;
        }
    }
    if (empty($thumb_url) && function_exists('get_the_image')) {
        // Get The Image plugin
        $temp_thumb = get_the_image(array('size' => 'thumbnail', 'echo' => false, 'link_to_post' => false));
        if (!empty($temp_thumb)) {
            $thumb_url = $temp_thumb;
        }
    }
    
    if (!empty($thumb_url)) {
        $thumb_url = uppsite_extract_src_url($thumb_url);
    }
    return $thumb_url;
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
    global $temp_ID, $post_ID, $form_action, $post, $user_ID;
    if ($msap->is_app) {
        if (!$post) {
            remove_action('save_post', 'mysiteapp_post_new_process');
            $post = get_default_post_to_edit( 'post', true );
            add_action('save_post', 'mysiteapp_post_new_process');
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
        $arr['postedit'] = array(
            'wpnonce' => wp_create_nonce( 0 == $post_ID ? 'add-post' : 'update-post_' .  $post_ID ),
            'user_ID' => (int)$user_ID,
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
        } else {
            $arr['postedit']['post_ID'] = esc_attr($post_ID);
        }
        mysiteapp_print_xml($arr);
        exit();
    }
}
/**
 * After post is being saved
 * @param int $post_id    The newly / updated post_id
 */
function mysiteapp_post_new_process($post_id) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;

    if ( wp_is_post_revision( $post_id ) )
        return;

    global $msap;
    if ($msap->is_app) {
        $the_post = wp_is_post_revision($post_id);
        $arr = array(
                'user' => array('ID' => get_current_user_id()),
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
    global $user_ID;
    if ($msap->is_app) {
        $arr = array(
            'user'=>array('ID'=>$user_ID),
            'logout'=>array('success'=> !empty($user_ID))
        );
        mysiteapp_print_xml($arr);
        exit();
    }
}

/**
 * Cleans the author name of the comment
 * @param int $comment_ID    Comment id
 * @return string    Stripped author name
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
 * Sign a message with the API secret
 * @param string $message    The message
 */
function mysiteapp_sign_message($message){
    $options = get_option(MYSITEAPP_OPTIONS_DATA);
    $str = $options['uppsite_secret'].$message;
    return md5($str);
}

/**
 * Check if needs to search for a new application links
 * @return boolean Should ask UppSite server if there is a mobile app?
 */
function mysiteapp_is_need_new_link(){
    $dataOptions = get_option(MYSITEAPP_OPTIONS_DATA);
    $lastCheck = isset($dataOptions['last_native_url_check']) ? $dataOptions['last_native_url_check'] : 0;
    // Should update once in a day
    return time() > $lastCheck + MYSITEAPP_ONE_DAY;
}

/**
* @return bool Should perform preferences update?
 */
function mysiteapp_needs_prefs_update() {
    $dataOptions = get_option(MYSITEAPP_OPTIONS_DATA);
    $lastCheck = isset($dataOptions['prefs_update']) ? $dataOptions['prefs_update'] : 0;
    // Should update once in 12 hours
    return time() > $lastCheck + (MYSITEAPP_ONE_DAY / 2);
}

/**
 * Fetch and set the preferences from UppSite server
 * @param boolean $forceUpdate  Force fetching prefs or not.
 */
function mysiteapp_prefs_init($forceUpdate = false) {
    // Init keys
    $dataOptions = get_option(MYSITEAPP_OPTIONS_DATA);
    if ($dataOptions === false || !isset($dataOptions['uppsite_key'])) {
        $uppData = wp_remote_post(MYSITEAPP_AUTOKEY_URL,
            array(
                'body' => 'pingback=' . get_bloginfo('pingback_url'),
                'timeout' => 5
            )
        );
        if (!is_wp_error($uppData)) {
            $data = json_decode($uppData['body'], true);
            if (isset($data['key'])) {
                $dataOptions = array(
                    'appId' => $data['appId'],
                    'uppsite_key' => $data['key'],
                    'uppsite_secret' => $data['secret'],
                    'prefs_update' => 0,
                    'last_native_url_check' => 0
                );
                update_option(MYSITEAPP_OPTIONS_DATA, $dataOptions);

                $opts = get_option(MYSITEAPP_OPTIONS_OPTS);
                if (!is_array($opts)) {
                    $opts = array();
                }
                $opts['activated'] = $data['activated'];
                if ($opts['activated']) {
                    $opts['webapp_mode'] = "all";
                    $opts['visited_minisite'] = true;
                }
                update_option(MYSITEAPP_OPTIONS_OPTS, $opts);
            }
        }
    }

    if ($dataOptions === false) {
        // Still no data options
        return;
    }

    $prefsOptions = get_option(MYSITEAPP_OPTIONS_PREFS);
    if (empty($prefsOptions) || $forceUpdate) {
        $uppPrefs = wp_remote_post(MYSITEAPP_PREFERENCES_URL,
            array(
                'body' => 'os_id=4&json=1&key=' . $dataOptions['uppsite_key'],
                'timeout' => 5
            )
        );
        if (!is_wp_error($uppPrefs)) {
            $prefsOptions = json_decode($uppPrefs['body'], true);
            $dataOptions['app_id'] = $prefsOptions['preferences']['id'];
            update_option(MYSITEAPP_OPTIONS_PREFS, $prefsOptions['preferences']);
            $dataOptions['prefs_update'] = time();
            update_option(MYSITEAPP_OPTIONS_DATA, $dataOptions);
        }
        // Update webapp info, if needed (only when 'activated' isn't set).
        $opts = get_option(MYSITEAPP_OPTIONS_OPTS);
        if (!is_array($opts)) {
            $opts = array();
        }
        if (!isset($opts['activated'])) {
            $uppData = wp_remote_post(MYSITEAPP_AUTOKEY_URL,
                array(
                    'body' => 'meta_only=1&pingback=' . get_bloginfo('pingback_url'),
                    'timeout' => 5
                )
            );
            if (!is_wp_error($uppData)) {
                $data = json_decode($uppData['body'], true);
                if (isset($data['activated'])) {
                    $opts['activated'] = $data['activated'];
                    if ($opts['activated']) {
                        $opts['webapp_mode'] = "all";
                        $opts['visited_minisite'] = true;
                        update_option(MYSITEAPP_OPTIONS_OPTS, $opts);
                    }
                }
            }
        }
    }
}

/**
 * admin_init action
 * Setup parameters when admin enters.
 */
function mysiteapp_admin_init() {
    $forcePrefsUpdate = mysiteapp_needs_prefs_update();
    $options = get_option(MYSITEAPP_OPTIONS_OPTS);
    
    if (!isset($options['uppsite_plugin_version']) ||
        $options['uppsite_plugin_version'] != MYSITEAPP_PLUGIN_VERSION) {
        $options['uppsite_plugin_version'] = MYSITEAPP_PLUGIN_VERSION;
        update_option(MYSITEAPP_OPTIONS_OPTS, $options);
        $forcePrefsUpdate = true;
    }

    mysiteapp_prefs_init($forcePrefsUpdate);
    
    mysiteapp_get_app_links();

    $options = get_option(MYSITEAPP_OPTIONS_OPTS); // Options might change in mysiteapp_prefs_init()
    if (!isset($options['minisite_shown'])) {
    	$options['minisite_shown'] = isset($options['visited_minisite']);
    	update_option(MYSITEAPP_OPTIONS_OPTS, $options);

    	if (!$options['minisite_shown']) {
            // Only redirect if need to show the minisite
            wp_safe_redirect(menu_page_url('uppsite-settings', false));
            exit;
        }
    }
}

/**
 * Retrives a list of application keys for the current website
 * and updates the database.
 */
function mysiteapp_get_app_links(){
    if (!mysiteapp_is_need_new_link()) {
        return false;
    }
    
    $options = get_option(MYSITEAPP_OPTIONS_DATA);

    if (empty($options['uppsite_key']))
        return false;

    $hash = mysiteapp_sign_message($options['uppsite_key']);
    $get = '?api_key='.$options['uppsite_key'].'&hash='.$hash;
    
    $response = wp_remote_get(MYSITEAPP_APP_NATIVE_URL.$get);
    if (is_wp_error($response)) {
        return false;
    }

    $data = json_decode($response['body'],true);
    if ($data) {
        $options['native_url'] = $data['url'];
        // Set updated in this time
        $options['last_native_url_check'] = time();
        update_option(MYSITEAPP_OPTIONS_DATA, $options);
    }
}

/**
 * Returns the current version of the installed plugin.
 * @return    float    MySiteApp plugin version
 */
function mysiteapp_get_plugin_version() {
    return MYSITEAPP_PLUGIN_VERSION;
}

/**
 * Returns a picture of facebook user
 * @param string $fb_id Facebook user id
 * @return string    URL to the image
 */
function mysiteapp_get_pic_from_fb_id($fb_id){
    return 'http://graph.facebook.com/'.$fb_id.'/picture?type=small';
}

/**
 * Tries to fetch picture from facebook profile
 * @param string $fb_profile    Profile link
 * @return string    URL to the image
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
    $need_g_avatar = true;
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
            $user['avatar']  = htmlspecialchars_decode(uppsite_extract_src_url(get_avatar(get_comment_author_email())));
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
 * @param array $fb_comment    Comment parameters
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
    
    // Fetch comments from facebook.com
    $comment_json = wp_remote_get($comments_url);
    $avatar_url = htmlspecialchars_decode(uppsite_extract_src_url(get_avatar(0)));

    // Check if comments exist
    if($comment_json){
        $comments_arr = json_decode($comment_json['body'],true);
        //check if comments exist
        if ($comments_arr == NULL||
            !array_key_exists($permalink,$comments_arr) ||
            !array_key_exists('data',$comments_arr[$permalink])) {
            return;
        }

        $comments_list = $comments_arr[$permalink]['data'];
        foreach($comments_list as $comment){
            $res .= mysiteapp_print_single_facebook_comment($comment,$avatar_url);
            //inner comment
            if (array_key_exists('comments', $comment)){
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
    $val = isset($_REQUEST['msa_facebook_comment_page']) ? $_REQUEST['msa_facebook_comment_page'] : NULL;
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
    if ($msap->is_app) {
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
 * @param string $more    Current more text
 */
function mysiteapp_fix_content_more($more){
    global $msap;
    if ($msap->is_app) {
        return '(...)';
    }
    return $more;
}

/**
 * Returns the layout of the posts, as the mobile application
 * wishes to display it.
 * 
 * @return string    Enum: full / ffull_rexcerpt / ffull_rtitle / title / excerpt
 */
function mysiteapp_get_posts_layout() {
    $posts_list_view = isset($_REQUEST['posts_list_view']) ? esc_html(stripslashes($_REQUEST['posts_list_view'])) : "";
    // Validate value
    switch ($posts_list_view) {
        case "full":
        case "ffull_rexcerpt":
        case "ffull_rtitle":
        case "title":
        case "excerpt":
            return $posts_list_view;
    }
    return "";
}

/**
 * Tells whether there is a need to display the post content.
 * Will display the content in these situations:
 * - No post layout defined
 * - In post page ('full')
 * - First post & in 'First full, Rest title' / 'First full, Rest excerpt'
 * 
 * @param int $iterator    Number of the post (zero-based)
 * @param string $posts_list_view    The posts layout
 */
function mysiteapp_should_show_post_content($iterator = 0, $posts_layout = null) {
    if ($posts_layout == null)
        $posts_layout = mysiteapp_get_posts_layout();
    if (
            empty($posts_layout) || // Not set
            $posts_layout == 'full' || // Full post
            ( $iterator < MYSITEAPP_BUFFER_POSTS_COUNT && ($posts_layout == 'ffull_rexcerpt' || $posts_layout == 'ffull_rtitle')) // First post of "First Full, rest X"
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
    return isset($_REQUEST['posts_hide']) && $_REQUEST['posts_hide'] == '1';
}
/**
 * Should the plugin hide the sidebar?
 * 
 * @return boolean
 */
function mysiteapp_should_hide_sidebar() {
    return isset($_REQUEST['sidebar_hide']) && $_REQUEST['sidebar_hide'] == '1';
}

/**
 * Calls the specific function while discarding any output in the process
 * @param string $func    Function name
 * @return mixed    The function return value (if any)
 */
function mysiteapp_clean_output($func) {
    ob_start();
    $ret = call_user_func($func);
    ob_end_clean();
    return $ret;
}

/**
 * Checks if a theme selection has made ("landing page").
 * Sets the theme in a cookie, and redirects back to the referer page
 */
function mysiteapp_set_webapp_theme(/*&$wp*/) {
    $templateType = isset($_REQUEST['msa_theme_select']) ? esc_html(stripslashes($_REQUEST['msa_theme_select'])) : "";
    $templateSaveForever = isset($_REQUEST['msa_theme_save_forever']) ? esc_html(stripslashes($_REQUEST['msa_theme_save_forever'])) : "";
    if (empty($templateType)) {
        return;
    }

    // Validate templateType
    if (!in_array($templateType, array("webapp", "normal"))) {
        return;
    }

    $cookieTime = $templateSaveForever ? 60*60*24*7 : 60*60; // "Forever" = 7 days, else = 1 hour
    setcookie(MYSITEAPP_WEBAPP_PREF_THEME, $templateType, time() + $cookieTime);
    // Set the cookie saving time, to renew on plugin init.
    setcookie(MYSITEAPP_WEBAPP_PREF_TIME, $cookieTime, time() + 60*60*24*30);

    // Refresh the page that will now load with the correct theme.
    $cleanUrl = remove_query_arg(array("msa_theme_select","msa_theme_save_forever"));
    wp_safe_redirect($cleanUrl);
    exit;
}

/**
 * Active the webapp functionality when UppSite notifies the app is "Published"
 * (this was made so if the user didn't like the app but forgot to deactivate the plugin,
 *  he will not see the webapp)
 * @note API Key & Secret already present at this stage, as they are filled upon plugin activation. Just in case there
 *       is a outgoing communication problem with this server ('fopen doesn't allow remote hosts'), we are setting
 *       the API key & secret here too.
 */
function mysiteapp_remote_activation() {
    $query_var = isset($_REQUEST['msa_remote_activation']) ? $_REQUEST['msa_remote_activation'] : "";
    if (empty($query_var)) {
        return;
    }
    $decoded = json_decode(base64_decode($query_var), true);
    /**
     * If API Secret is present, the message will be signed by it.
     * If not, the message is signed by the pingback_url.
     */
    $dataOpts = get_option(MYSITEAPP_OPTIONS_DATA);

    $signKey = 1;
    $signVal = get_bloginfo('pingback_url');
    if (isset($dataOpts['uppsite_secret'])) {
        $signKey = 2;
        $signVal = $dataOpts['uppsite_secret'];
    }
    $signVal = md5($signVal);
    if (md5($decoded['data'].$decoded['secret' . $signKey]) != $decoded['verify' . $signKey]
        || $decoded['secret' . $signKey] != $signVal) {
        // Not signed
        return;
    }
    $data = json_decode($decoded['data'], true);

    // Allow only some keys, and into specific tables.
    $opts = get_option(MYSITEAPP_OPTIONS_OPTS);
    foreach ($data as $key=>$val) {
        switch ($key) {
            case "app_id":
            case "uppsite_key":
            case "uppsite_secret":
            case "prefs_update":
            case "last_native_url_check":
                $dataOpts[$key] = $val;
                break;
            case "activated":
            case "webapp_mode":
            case "visited_minisite":
                $opts[$key] = $val;
                break;
        }
    }
    update_option(MYSITEAPP_OPTIONS_DATA ,$dataOpts);
    update_option(MYSITEAPP_OPTIONS_OPTS, $opts);
}

/**
 * @return string JSON-encoded string with ad details for the webapp
 */
function mysiteapp_get_ads() {
    $prefs = get_option(MYSITEAPP_OPTIONS_PREFS);
    if ($prefs === false ||
        $prefs['ad_display'] == false || $prefs['ad_display'] == "false") {
        return "{active: false}";
    }
    $ret = array(
        "active" => true,
        "html" => $prefs['ads']
    );
    if (isset($prefs['matomy_site_id']) && isset($prefs['matomy_zone_id'])) {
        $ret['matomy_site_id'] = $prefs['matomy_site_id'];
        $ret['matomy_zone_id'] = $prefs['matomy_zone_id'];
    }
    return json_encode($ret);
}
/**
 * @return bool Has visited the site through the minisite? (Updated via remote)
 */
function mysiteapp_visited_minisite() {
    $opts = get_option(MYSITEAPP_OPTIONS_OPTS);
    print $opts != null && isset($opts['visited_minisite']) ? 'true' : 'false';
    exit;
}

/**
 * Converts a date from WP format to unix format
 * @param string $datetime Date string (e.g. 2008-02-07 12:19:32)
 */
function mysiteapp_convert_datetime($datetime) {
    $values = explode(" ", $datetime);

    $dates = explode("-", $values[0]);
    $times = explode(":", $values[1]);

    return mktime($times[0], $times[1], $times[2], $dates[1], $dates[2], $dates[0]);
}

/**
 * @return bool Tells whether a push notification can be sent.
 */
function mysiteapp_can_send_push() {
    $dataOpts = get_option(MYSITEAPP_OPTIONS_DATA);
    return isset($dataOpts['uppsite_key']) && isset($dataOpts['uppsite_secret']);
}

/**
 * Sends notification to UppSite's server in order to send push notification to clients.
 * @param int $post_id  Post id
 * @param null $post_details (optional) Post details
 */
function mysiteapp_send_push($post_id, $post_details = NULL) {
    if (!mysiteapp_can_send_push()) { return; }

    if (is_null($post_details)) {
        // Fill post details
        $post_details = get_post($post_id, ARRAY_A);
    }

    $dataOpts = get_option(MYSITEAPP_OPTIONS_DATA);
    $data = array();
    $data['title'] = $post_details['post_title'];
    $data['post_id'] = $post_details['ID'];
    $data['utime'] = mysiteapp_convert_datetime($post_details['post_date']);
    $data['api_key'] = $dataOpts['uppsite_key'];

    $json_str = json_encode($data);
    $hash = mysiteapp_sign_message($json_str);

    wp_remote_post(MYSITEAPP_PUSHSERVICE, array(
        'body' => 'data='.$json_str.'&hash='.$hash,
        'timeout' => 5,
    ));
}

/**
 * Sends push notification, if post is published
 * @param $post_id Post id
 */
function mysiteapp_new_post_push($post_id) {
    if ($_POST['post_status'] != 'publish') { return; }
    if ( (isset($_POST['original_post_status']) && $_POST['original_post_status'] != $_POST['post_status']) || // Post status changed
        (isset($_POST['_status']) && $_POST['_status'] != $_POST['post_status']) ) { // Another way of changing the post status
        mysiteapp_send_push($post_id);
    }
}

/**
 * Sends a push notification for a future post
 * @param $post_id Post id
 */
function mysiteapp_future_post_push($post_id) {
    $post_details = get_post($post_id, ARRAY_A);
    if ($post_details['post_status'] != 'publish') { return; }

    if (!$_POST &&
        false == (isset($post_details['sticky']) && $post_details['sticky'] == 'sticky')) {
        // Send only if not a sticky post
        mysiteapp_send_push($post_id, $post_details);
    }
}

/** Webapp theme selection **/
add_action('wp', 'mysiteapp_set_webapp_theme');
/** Webapp activation */
add_action('init', 'mysiteapp_remote_activation');
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
/** First plugin activate/Entrance to admin panel **/
add_action('admin_init','mysiteapp_admin_init');
/** Disqus **/
//add_filter('comment_post_redirect','mysiteapp_comment_to_disq',10,2);
/** Comment using facebook (set the template)  **/
add_action('template_redirect','mysiteapp_comment_to_facebook', 10);
/** Fixing the "more..." for mobile **/
add_filter('the_content_more_link','mysiteapp_fix_content_more', 10, 1);
/** Ajax request for checking the minisite option */
add_action('wp_ajax_uppsite_visited_minisite', 'mysiteapp_visited_minisite');

/** Push notification upon new post */
add_action('publish_post','mysiteapp_new_post_push', 10, 1);
add_action('publish_future_post','mysiteapp_future_post_push', 10, 1);



endif; /*if (!defined('MYSITEAPP_AGENT')):*/

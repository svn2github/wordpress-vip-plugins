<?php
/*
Plugin Name: Echo
Plugin URI: http://js-kit.com/
Description: Echo is a fully-featured commenting system with powerful pre- and post- moderation capabilities, automatic spam blocking, RSS feeds, threading, pagination and sorting of comments, and much more. This plugin enables seamless integration with Echo by exporting existing WordPress comments into Echo and instantly synchronizing new comments.
Author: Echo team <support@js-kit.com>
Version: 2.6.3-wpcom-vip
Author URI: http://js-kit.com/

WPCOM VIP Mods
-get_wpsite_url() modified to work with domain mapping
-plugin debug logic removed because it wrote to the file system
-plugin activation items removed because we don't "activate" plugins
-code to set memory and time limits removed

This version won't work well in WordPress.org environments.
For local development and testing please use
http://wordpress.org/extend/plugins/echo/
*/

include_once(ABSPATH . WPINC . '/class-IXR.php');
include_once(dirname(__FILE__) . '/wp-compat.php');

class EchoPlugin {
    var $version = '2.6.3';
    var $debug;
    var $echo_site_url;

    function EchoPlugin($echo_domain = "js-kit.com", $debug = false) {
        $this->__construct($echo_domain, $debug);
    }

    function __construct($echo_domain = "js-kit.com", $debug = false) {
        $this->debug = $debug ? true : false;
        $this->echo_site_url = 'http://' . $echo_domain;
        $this->setup_hooks();
    }

    function get_version() {
        return $this->version;
    }

    # Authentication [[[
    function authenticate($args) {
        $user_login = $args[1];
        $user_pass =  $args[2];

        if ($user_login == "authKey") {
            $echo_auth_key = get_option("jskit-authKey");
            if (strlen($echo_auth_key) > 0 && $echo_auth_key == $user_pass) {
                return true;
            }
        }

        $error = new IXR_Error(403, "Authentication using auth key failed");
        return $error;
    }
    # ]]]

    # XML-RPC call handlers [[[
    function set_status($args){
        $auth_result = $this->authenticate($args);
        if ($auth_result !== true) {
            return $auth_result;
        }

        $comment_id = $args[0];
        $status = $args[3];
        $rez = false;

        switch ($status) {
        case 'A':
            $rez = wp_set_comment_status($comment_id, 'approve');
            break;

        case 'S':
            $rez = wp_set_comment_status($comment_id, 'spam');
            break;

        case 'D':
            if (get_comment($comment_id)) {
                $rez = wp_delete_comment($comment_id);
            } else {
                $rez = true;
            }
            break;
        }

        $call_result = $rez ? 1 : -1 ;

        return $call_result;
    }

    function new_comment($args) {
        $auth_result = $this->authenticate($args);
        if ($auth_result !== true) {
            return $auth_result;
        }

        $comment = $args[0];

        # Retrieve destination post ID from the comment's path.
        # We assume that the comment path for WP synchronized comments should look
        # like /blog/p=123 or just /p=123.
        $comment_post_id = $this->get_comment_post_id($comment);
        if ($comment_post_id == 0) {
            return -2;
        }

        $comment['post_ID'] = $comment_post_id;

        # Check for duplicates
        $duplicate_comment_id = $this->get_duplicate_comment($comment);
        if ($duplicate_comment_id) {
            return $duplicate_comment_id;
        }

        # Prepare comment data for insertion
        $commentdata = $this->prepare_new_comment($comment);

        # Insert comment into database
        $comment_id = wp_insert_comment($commentdata);

        if (!$comment_id) {
            return -1;
        }

        # Check data integrity
        $inserted_comment = get_comment($comment_id);
        if (!$inserted_comment) {
            return -1;
        }

        # Set comment's status
        $sParam = $args;
        $sParam[0] = $comment_id;
        $sParam[3] = $comment['status'];
        $rez = $this->set_status($sParam);

        $call_result = $rez == -1 ? -1 : $comment_id;

        return $call_result;
    }

    function validate_auth($args) {
        $auth_result = $this->authenticate($args);
        if ($auth_result !== true) {
            return $auth_result;
        }

        return 1;
    }

    function get_comments($args) {
        $auth_result = $this->authenticate($args);
        if ($auth_result !== true) {
            return $auth_result;
        }

        $limit_offset = 0;
        $limit_count = 0;
        if (isset($args[3]) && is_numeric($args[3])) {
            $limit_offset = abs(intval($args[3]));
        }
        if (isset($args[4]) && is_numeric($args[4]) && intval($args[4]) > 0) {
            $limit_count = abs(intval($args[4]));
        }

        $Comments = get_comments(array(
            'status' => 'approve', 'orderby' => 'comment_id', 'order' => 'ASC',
            'number' => $limit_count, 'offset' => $limit_offset
        ));

        foreach ($Comments as $key => $elem) {
            $Comments[$key]->comment_date = new IXR_Date(mysql2date("Ymd\TH:i:s", $elem->comment_date));
            $Comments[$key]->comment_date_gmt = new IXR_Date(mysql2date("Ymd\TH:i:s", $elem->comment_date_gmt));
            $Comments[$key]->post_uniq = $this->get_post_uniq_value($Comments[$key]->comment_post_ID);
            $Comments[$key]->post_permalink = get_permalink($Comments[$key]->comment_post_ID);
        }

        $Cmts = array();
        $blog_charset = get_option('blog_charset');
        $required_fields = array(
            "comment_id", "comment_post_id", "comment_content",
            "comment_approved", "comment_author", "comment_author_email",
            "comment_author_ip", "comment_date", "comment_date_gmt",
            "post_uniq", "post_permalink"
        );
        foreach ($Comments as $key => $Comment) {
            $Cmt = array();
            foreach ($Comment as $attribute => $value) {
                $canonical_attr_name = strtolower($attribute);
                if (in_array($canonical_attr_name, $required_fields)) {
                    $Cmt[$canonical_attr_name] = $value;
                    if (gettype($value) == "string") {
                        $Cmt[$canonical_attr_name] = $this->convert_charset($value, $blog_charset, "UTF-8");
                    }
                }
            }
            $Cmts[$key] = $Cmt;
        }

        return $Cmts;
    }

    function get_comments_count($args) {
        $auth_result = $this->authenticate($args);
        if ($auth_result !== true) {
            return $auth_result;
        }

        $wp_comments_stats =  wp_count_comments();
        $comments_count = $wp_comments_stats->approved;

        return $comments_count;
    }

    function plugin_info($args) {
        $plugin_info = array(
            "echo_wp_plugin_version" => $this->version
        );

        return $plugin_info;
    }

    function plugin_ping($args) {
        $result = "pong";

        return $result;
    }

    function set_auth_key($authKey) {
        if (!get_option('jskit-authKey') && preg_match('/^[0-9a-f]{32}$/i', $authKey)) {
            update_option('jskit-authKey', $authKey);
        }

        return 1;
    }
    # ]]]

    # Utility functions [[[
    function attach_xmlrpc_methods($methods) {
        $methods['wp.JSKitPluginInfo'] = array(&$this, 'plugin_info');
        $methods['wp.JSKitPluginPing'] = array(&$this, 'plugin_ping');
        $methods['wp.getComments'] = array(&$this, 'get_comments');
        $methods['wp.getCommentsCount'] = array(&$this, 'get_comments_count');
        $methods['wp.newComment'] = array(&$this, 'new_comment');
        $methods['wp.validateAuth'] = array(&$this, 'validate_auth');
        $methods['wp.setStatus'] = array(&$this, 'set_status');
        $methods['wp.setAuthKey'] = array(&$this, 'set_auth_key');

        return $methods;
    }

    function get_duplicate_comment($comment) {
        $all_in_post = get_approved_comments($comment['post_ID']);
        foreach($all_in_post as $value) {
            if ($comment['author'] == $value->comment_author && $comment['text'] == $value->comment_content) {

                return $value->comment_ID;
            }
        }

        return NULL;
    }

    function get_comment_post_id($comment) {
        if (!isset($comment['path'])) {
            return 0;
        }

        $comment_post_id = 0;
        if (preg_match('/p=([0-9]+)$/', $comment['path'], $matches)) {
            $comment_post_id = intval($matches[1]);
        } else {
            $comment_post_id = url_to_postid($comment['path']);
        }

        return $comment_post_id;
    }

    function prepare_new_comment($comment) {
        $comment_post_ID = $comment['post_ID'];
        $comment_author = $comment['author'];
        $comment_author_email = $comment['email'];
        $comment_author_IP = $comment['IP'];
        $comment_content = $comment['text'];
        $comment_date_gmt = gmdate('Y-m-d H:i:s', (int)$comment['TS']);
        $comment_date = gmdate('Y-m-d H:i:s', (int)$comment['TS'] + get_option('gmt_offset') * 3600);


        $commentdata = compact('comment_post_ID', 'comment_author',
            'comment_author_email', 'comment_author_IP',
            'comment_content', 'comment_date', 'comment_date_gmt');

        $blog_charset = get_option('blog_charset');
        foreach ($commentdata as $key => $elem){
            $commentdata[$key] = $this->convert_charset($elem, 'UTF-8', $blog_charset);
        }

        $commentdata['comment_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $commentdata['comment_approved'] = 0;
        $commentdata = wp_filter_comment($commentdata);

        return $commentdata;
    }

    function xmlize_utf8($str, $utf8) {
        if (!$utf8) {
            return $str;
        }
        # XML only allows TAB, NL and LF chars
        # out of control characters set
        $search = range(chr(0), chr(31));
        foreach(array(9, 10, 13) as $i)
            unset($search[$i]);

        return str_replace($search, "", $str);
    }

    function convert_charset($str, $from, $to) {
        if (($to == 'UTF-8' && seems_utf8($str) == false) || $from == 'UTF-8') {
            $res = "";
            if (function_exists("iconv")) {
                $res = iconv($from, $to, $str);
            } elseif (function_exists("mb_convert_encoding")) {
                $res = mb_convert_encoding($str, $to, $from);
            } else {
                $res = utf8_encode($str);
            }
            return $this->xmlize_utf8($res, $to == 'UTF-8');
        }

        return $this->xmlize_utf8($str, $from == 'UTF-8');
    }

    function dialog_message($message) {
        return sprintf('<div class="updated fade"><p><strong>%s</strong></p></div>', $message);
    }

    function check_auth_key() {
        if (!get_option('jskit-authKey') && !$_GET['install']) {
            echo $this->dialog_message(
                'You have activated Echo plug-in, but have not performed import which ' .
                'also establishes two way connection with Echo.<br />' .
                'In order to guarantee live comments synchronization ' .
                'from Echo to WordPress database please ' .
                '<a href="edit-comments.php?page=echo&amp;install=1">run installation process</a>.'
            );
        }
    }

    function use_echo($post) {
        if (!get_option('jskit-useStartDate')) {
            return true;
        }
        return strtotime($post->post_date) > get_option('jskit-startDate');
    }
    # ]]]

    # URL helpers [[[
    function get_echo_site_url() {
        return $this->echo_site_url;
    }

    function get_wpsite_url() {
	global $current_blog;
	return 'http://' . $current_blog->primary_redirect . '/';
    }

    function get_blog_url() {
        $blog_url = get_bloginfo('url');
        if (substr($blog_url, -1, 1) != '/') {
            $blog_url .= '/';
        }

        return $blog_url;
    }

    function get_blog_url_info() {
        $blog_url = $this->get_blog_url();

        return parse_url($blog_url);
    }

    function get_blog_relative_path() {
        $blog_url_info = $this->get_blog_url_info();

        $relative_path = '/';
        if (isset($blog_url_info['path'])) {
            $relative_path = $blog_url_info['path'];
        }
        if (substr($relative_path, -1, 1) != '/') {
            $relative_path .= '/';
        }

        return $relative_path;
    }

    function get_blog_absolute_path() {
        $blog_url_info = $this->get_blog_url_info();

        return $blog_url_info['host'] . $this->get_blog_relative_path();
    }

    function get_blog_domain() {
        $blog_url_info = $this->get_blog_url_info();

        return $blog_url_info['host'];
    }

    function get_post_uniq_value($post_id) {
        static $blog_relative_path;

        if (!isset($blog_relative_path)) {
            $blog_relative_path = $this->get_blog_relative_path();
        }

        return $blog_relative_path . "p=" . $post_id;
    }

    function get_normalized_domain($host) {
        if (substr($host, 0, 4) == 'www.') {
            $host = substr($host, 4);
        }

        return $host;
    }

    function generate_query_string($args) {
        $query_string = array();
        foreach ($args as $k => $v) {
            $query_string[] = $k . '=' . urlencode($v);
        }

        return join("&", $query_string);
    }

    function get_install_link($authKey) {
        $wp_site_url = $this->get_wpsite_url();
        $wpsite_url_info = parse_url($wp_site_url);
        $host = $this->get_normalized_domain($wpsite_url_info['host']);

        $args = array(
            'mode' => 'frame',
            'site' => $host,
            'appkey' => $authKey ? $authKey : get_option('jskit-authKey'),
            'endpoint' => $wpsite_url_info['host'] .
                ($wpsite_url_info['path'] ? $wpsite_url_info['path'] : '/'),
            'return_url' => $wp_site_url . '/wp-admin/edit-comments.php?page=echo'
        );

        return $this->echo_site_url . '/settings/wp-install.cgi?' . $this->generate_query_string($args);
    }

    function get_import_link() {
        $wpsite_url_info = parse_url($this->get_wpsite_url());
        $host = $this->get_normalized_domain($wpsite_url_info['host']);

        $args = array(
            'site' => $host,
            'action' => 'launch_import',
            'appkey' => get_option('jskit-authKey'),
            'endpoint' => $wpsite_url_info['host'] .
                ($wpsite_url_info['path'] ? $wpsite_url_info['path'] : '/')
        );

        return $this->echo_site_url . "/comments/wordpress.cgi?" .
            $this->generate_query_string($args);
    }

    function get_moderation_link() {
        $wpsite_url_info = parse_url($this->get_wpsite_url());
        $host = $this->get_normalized_domain($wpsite_url_info['host']);

        $args = array(
            'site' => $host,
        );

        return $this->echo_site_url . "/moderate/?" . $this->generate_query_string($args);
    }

    # ]]]

    # URL rewriting [[[
    function trackback_link($trackback_url) {
        global $post;

        return $this->use_echo($post)
            ? $this->echo_site_url . "/trackback/" . $this->get_blog_domain() . $this->get_post_uniq_value($post->ID)
            : $trackback_url;
    }

    function rss_link($rss_url) {
        global $post;

        return $this->use_echo($post)
            ? $this->echo_site_url . "/rss/" . $this->get_blog_domain() . $this->get_post_uniq_value($post->ID)
            : $rss_url;
    }

    function url_rewrite($url) {
        if ($url ==  get_feed_link('comments_rss2')) {
            return $this->echo_site_url . "/rss/" . $this->get_blog_domain();
        }

        return $url;
    }
    # ]]]

    # Content rewriting [[[
    function add_page() {
        global $menu, $submenu;

        add_submenu_page('edit-comments.php', 'Echo', 'Echo', 10, 'echo', array(&$this, 'settings_page'));

        foreach ($menu as $k => $v) {
            if ($menu[$k][2] == 'edit-comments.php') {
                $menu[$k][2] = 'edit-comments.php?page=echo';
            }
        }
    }

    function comments_template($value) {
        global $post;

        return $this->use_echo($post)
            ? dirname(__FILE__) . '/comments.php'
            : $value;
    }

    function comments_number($output) {
        global $post;

        if (!$this->use_echo($post)) {
            return $output;
        }

        $uniq = $this->get_post_uniq_value($post->ID);
        $stream_type = get_option('jskit-streamType');

        $filter = "";
        if ($stream_type == 'comments') {
            $filter = ' include-sources="Comments"';
        } elseif ($stream_type == 'reactions') {
            $filter = ' exclude-sources="Comments"';
        }

        $number = '<span id="jskit-commentCountSpan" class="js-kit-comments-count" uniq="' . esc_attr($uniq) . '"' . $filter . '>0</span> ' .
            str_replace("%", "", __("% Comments"));

        return $number;
    }

    function header_js() {
    ?>
        <script type="text/javascript">
        if (!window.JSKitLib) JSKitLib = {vars:{}};
        JSKitLib.addLoadEvent = function(newLoadEvent) {
            var origLoadEvent = window.onload;
            if (typeof origLoadEvent == "function") {
                window.onload = function() {
                    origLoadEvent();
                    newLoadEvent();
                }
            } else {
                window.onload = newLoadEvent;
            }
        }
        </script>
    <?php
    }

    function footer_js() {
    ?>
        <script type="text/javascript">
        JSKitLib.addLoadEvent(function(){
            var span = document.getElementById("jskit-commentCountSpan");
            if (!span) {
                return;
            }

            var sc = document.createElement("script");
            sc.type ="text/javascript";
            sc.charset = 'utf-8';
            sc.src = "<?php echo esc_js($this->echo_site_url); ?>/comments-count.js";
            document.body.appendChild(sc);
        });
        </script>
    <?php
    }

    function all_js(){
        $this->header_js();
        $this->footer_js();
    }
    # ]]]

    function setup_hooks() {
        # Hooks registration [[[
        add_action('admin_menu', array(&$this, 'add_page'));
        add_action('admin_notices', array(&$this, 'check_auth_key'));
        add_action('admin_footer', array(&$this, 'footer_js'));
        add_action('admin_head', array(&$this, 'header_js'));
        add_action('get_footer', array(&$this, 'all_js'));
        add_filter('bloginfo_url', array(&$this, 'url_rewrite'));
        add_filter('comments_number', array(&$this, 'comments_number'));
        add_filter('comments_template', array(&$this, 'comments_template'));
        add_filter('post_comments_feed_link', array(&$this, 'rss_link'));
        add_filter('trackback_url', array(&$this, 'trackback_link'));
        add_filter('xmlrpc_methods', array(&$this, 'attach_xmlrpc_methods'));

        remove_filter('comment_flood_filter', 'wp_throttle_comment_flood', 10, 3);
        # ]]]
    }

    # Settings
    function generate_authkey($noupdate = false) {
        $authKey = md5(microtime(true) . $this->get_wpsite_url());
        if (!$noupdate) {
            update_option('jskit-authKey', $authKey);
        }
        return $authKey;
    }

    function assemble($template, $vars){
        foreach ($vars as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }

    function timestamp_to_array($timestamp) {
        $t = explode('-', date('m-d-Y', $timestamp));
        return array('month' => $t[0], 'day' => $t[1], 'year' => $t[2]);
    }

    function settings_page() {
        $authKey = get_option('jskit-authKey');
        if (!$authKey) {
            $authKey = $this->generate_authkey(true);
            $this->show_install($authKey);
            return;
        }
        $WARNING_DATE_CHANGED = '<br><br>You have changed the "Where to use Echo" setting. Please <a href="' . esc_attr($this->get_import_link()) . '" target="_blank">start import</a> again to be sure all your comments have been imported from Wordpress to Echo.';
        $successMessage = '';
        if ($_POST['echo-action'] == 'generate_key') {
            $this->generate_authkey();
        } elseif ($_POST['echo-action'] == 'save') {
            $streamType = $_POST['streamType'];
            if (!$streamType || !in_array($streamType, array('all', 'comments', 'reactions'))) {
                $streamType = 'all';
            }
            update_option('jskit-streamType', $streamType);
            update_option('jskit-splitStreams', $_POST['splitStreams'] ? 1 : 0);

            $oldUseStartDate = get_option('jskit-useStartDate', 0);
            $oldStartDate = get_option('jskit-startDate', 0);
            $warning = '';
            $useStartDate = $_POST['useStartDate'];
            update_option('jskit-useStartDate', $useStartDate);
            if ($useStartDate == 1) {
                $startDate = mktime(0, 0, 0, (int)$_POST['startDate-month'], (int)$_POST['startDate-day'], (int)$_POST['startDate-year']);
                update_option('jskit-startDate', $startDate);
                if ($startDate < $oldStartDate) {
                    $warning = $WARNING_DATE_CHANGED;
                }
            } else if ($oldUseStartDate == 1) {
                $warning = $WARNING_DATE_CHANGED;
            }
            $successMessage = $this->dialog_message('Settings saved.' . $warning);
        }

        $startYear = 2007;
        $dateRange = array (
            'month' => range(1, 12),
            'day' => range(1, 31),
            'year' => range($startYear, $startYear + 5)
        );

        $templateSections = array (
            'setup' => 'Setup',
            'stream_type' => 'Set the Echo display configuraton',
            'start_date' => 'Where to use Echo'
        );
        $tmplVars = array (
            'successMessage' => $successMessage,
            'moderationLink' => esc_attr($this->get_moderation_link()),
            'importLink' => esc_attr($this->get_import_link()),
            'authKey' => esc_html($authKey),
            'streamType' => esc_js(get_option('jskit-streamType', 'all')),
            'splitStreams' => get_option('jskit-splitStreams') ? 'true' : 'false',
            'useStartDate' => get_option('jskit-useStartDate') ? 'true' : 'false',
            'dateSelector' => $this->assemble_date_selector($dateRange)
        );
        ob_start();
?>
    <div class="wrap">
        <h2>Echo</h2>
        <h3>Thank you for using Echo.</h3>
        <div>
            <p>
                You can moderate comments left via Echo at
                <a href="{moderationLink}" target="_blank">{moderationLink}</a>
            </p>
        </div>
        {successMessage}
        <form method="post" action="">
            <input type="hidden" name="echo-action" value="save">
<?php
        foreach ($templateSections as $section => $title) {
?>
        <h3><?php echo esc_html($title); ?></h3>
        <?php call_user_func(array(&$this, 'template_' . $section)); ?>
<?php
        }
?>
            <div><br><input type="submit" value="Save Changes" class="button-primary"></div>
        </form>
    </div>
<?php
        $template = ob_get_clean();
        echo $this->assemble($template, $tmplVars);
    }

    function show_install($authKey) {
        $template = '<iframe src="{installUrl}" frameborder="0" width="100%" height="500"></iframe>';
        echo $this->assemble($template, array('installUrl' => esc_attr($this->get_install_link($authKey))));
    }

    function template_setup() {
?>
    Your personal Echo application key: <span style="color: red">{authKey}</span>
    <input type="button" value="Generate new" class="button" id="echo-appkey-btn"><br>
    Use this application key when running the
    <a href="{importLink}" target="_blank">WP setup wizard</a>
    <script type="text/javascript">
        document.getElementById('echo-appkey-btn').onclick = function() {
            if (confirm('After changing application key you have to enter it on Commenting Options page of Echo Dashboard otherwise synchronization will stop. Are you sure you want to change key?')) {
                this.form['echo-action'].value = 'generate_key';
                this.form.submit();
            }
        }
    </script>
<?php
    }

    function template_stream_type() {
?>
    <input type="radio" id="echo-display-all" name="streamType" value="all">
    <label for="echo-display-all"> Show both Comments and Reactions</label><br>
    <input type="checkbox" id="echo-split-streams" name="splitStreams" value="1" style="margin-left: 25px">
    <label for="echo-split-streams"> Split Comments and Reactions into two streams</label><br>
    <input type="radio" id="echo-display-comments" name="streamType" value="comments">
    <label for="echo-display-comments"> Show only Comments</label><br>
    <input type="radio" id="echo-display-reactions" name="streamType" value="reactions">
    <label for="echo-display-reactions"> Show only Reactions</label><br>
    <script type="text/javascript">
        (function() {
            var streams = ['all', 'comments', 'reactions'];
            var update = function(stream) {
                document.getElementById('echo-split-streams').disabled = (stream != 'all');
            }
            for (var i = 0; i < streams.length; i++) {
                document.getElementById('echo-display-' + streams[i]).onclick = function() {
                    /display-(.*)$/.test(this.id);
                    update(RegExp.$1);
                }
            }
            var streamType = '{streamType}';
            document.getElementById('echo-display-' + streamType).checked = true;
            document.getElementById('echo-split-streams').checked = {splitStreams};
            update(streamType);
        })();
    </script>
<?php
    }

    function template_start_date() {
?>
    <input type="radio" id="echo-startDate-off" name="useStartDate" value="0">
    <label for="echo-startDate-off"> Enable Echo for all posts</label><br>
    <input type="radio" id="echo-startDate-on" name="useStartDate" value="1">
    <label for="echo-startDate-on"> Enable Echo for posts published after</label>
    {dateSelector}
    <script type="text/javascript">
        (function() {
            var useStartDate = {useStartDate};
            document.getElementById('echo-startDate-' + (useStartDate ? 'on' : 'off')).checked = true;
            var names = ['month', 'day', 'year'];
            var disable = function(needBlock) {
                for (var i = 0; i < names.length; i++) {
                    var element = document.getElementById('echo-startDate-' + names[i]);
                    if (element) {
                        element.disabled = needBlock;
                    }
                }
            }
            document.getElementById('echo-startDate-off').onclick = function() {
                disable(true);
            }
            document.getElementById('echo-startDate-on').onclick = function() {
                disable(false);
            }
            disable(!useStartDate);
        })();
    </script>
<?php
    }

    function assemble_select($name, $values, $selected) {
        $options = array();
        foreach ($values as $value) {
            $options[] = '<option value="' . esc_attr($value) . '"' .
            ($value == $selected ? ' selected="selected"' : '') . '>' .
            esc_html($value) . '</option>';
        }
        $options = join("\n", $options);
        $template = '<select id="echo-startDate-' . esc_attr($name) .
        '" name="startDate-' . esc_attr($name) . '">' . $options . '</select>';
        return $template;
    }

    function assemble_date_selector($dateRange) {
        $date = get_option('jskit-startDate');
        $selected = $this->timestamp_to_array($date ? $date : time());
        $html = '';
        foreach ($dateRange as $name => $values) {
            $html .= $this->assemble_select($name, $values, $selected[$name]);
        }
        return $html;
    }

    function comment_form_html() {
        global $post;

        if (!comments_open()) {
            return '';
        }

        $comment_div_args = array(
            "uniq" => $this->get_post_uniq_value($post->ID),
            "permalink" => get_permalink(),
            "echo_wp_plugin_version" => $this->get_version(),
            "stream_type" => get_option('jskit-streamType'),
            "split_streams" => get_option('jskit-splitStreams') ? 'true' : 'false'
        );
        $comment_div_url = $this->get_echo_site_url() . "/tmpl/wp.cgi?" . $this->generate_query_string($comment_div_args);

        return '<a name="comments"></a>' .
               '<script type="text/javascript" id="js-kit-wordpressPluginTemplate" src="' . $comment_div_url . '"></script>';
    }
}

global $echo_plugin;
$echo_plugin = new EchoPlugin();
?>

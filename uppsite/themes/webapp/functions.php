<?php
define('UPPSITE_MAX_TITLE_LENGTH', 45);
define('UPPSITE_DEFAULT_ANALYTICS_KEY', "BDF2JD6ZXWX69Y9BZQBC");
/**
 * Helper functions for the webapp theme
 */

if (isset($_REQUEST['uppsite_request'])) {
    // Sets a constant containing the request type.
    define('UPPSITE_AJAX', sanitize_text_field($_REQUEST['uppsite_request']));

    // If using ajax, don't return the static page (if defined)
    update_option('show_on_front', 'posts');

    /** Remove redirect canonical, as it causes multiple redirects during ajax requests */
    remove_filter('template_redirect', 'redirect_canonical');
}

/**
 * Helper function to get the non-cdn url for this
 * @return string   The URL for the webapp directory
 */
function uppsite_get_webapp_dir_uri() {
    if ( function_exists( 'wpcom_vip_noncdn_uri' ) ) {
        return trailingslashit( wpcom_vip_noncdn_uri( dirname( __FILE__ ) ) );
    } else {
        return get_template_directory_uri();
    }
}

function uppsite_get_appid() {
    $data = get_option(MYSITEAPP_OPTIONS_DATA);
    return isset($data['app_id']) ? $data['app_id'] : 0;
}

/**
 * Returns the information regarding the current member (runs inside the loop)
 *
 * @return array The member information
 */
function uppsite_get_member() {
    $avatar = null;
    if (function_exists('get_the_author_meta')) {
        $avatar = get_avatar(get_the_author_meta('user_email'));
    } elseif (function_exists('get_the_author_id')) {
        $avatar = get_avatar(get_the_author_id());
    }

    return array(
        'name' => get_the_author(),
        'link' => get_the_author_link(),
        'avatar' => uppsite_extract_src_url($avatar),
    );
}

/**
 * Formats a list of HTML title-permalink pairs into an array
 *
 * @param $output The output of HTML pairs (e.g. "wp_list_categories")
 * @return array An array containing all the pairs.
 */
function uppsite_format_html_to_array($output) {
    preg_match_all('/href=("|\')(.*?)("|\')(.*?)>(.*?)<\/a>/', $output, $result);
    $array = array();

    for($i = 0; $i < count($result[0]); $i++) {
        $array[] = array(
            'title' => $result[5][$i],
            'permalink' => $result[2][$i],
        );
    }
    return $array;
}

/**
 * Returns the information regarding the comment author
 *
 * @return array
 */
function uppsite_get_comment_member(){
    $avatar = get_avatar(get_comment_author_email());

    return array(
        'name' =>  get_comment_author(),
        'avatar' => uppsite_extract_src_url($avatar),
    );
}

/**
 * Returns the comment information
 *
 * @return array
 */
function uppsite_get_comment() {
    global $comment;
    return array(
        'comment_ID' => get_comment_ID(),
        'post_id' => get_the_ID(),
        'isApproved' => $comment->comment_approved == '0' ? "false" : "true",
        'permalink' => get_permalink(),
        'comment_date' => get_comment_date( '', 0 ),
        'unix_time' => get_comment_date( 'U', 0 ),
        'comment_content' => get_comment_text( 0 ),
        'comment_author' => uppsite_get_comment_member(get_comment_ID()),
    );
}

/**
 * Returns the length of the string, using multi-byte functions where applicable
 * @param $str  String
 * @return int  String length
 */
function uppsite_strlen($str) {
    if (function_exists('mb_strlen')) {
        return mb_strlen($str);
    }
    return strlen($str);
}

/**
 * Performs a pattern match on the subject, and return the results
 * @note Trying to use multibyte functions (mb_eregi)
 * @param $pattern  Pattern
 * @param $subject  Subject
 * @return  null|array    Results
 */
function uppsite_match($pattern, $subject) {
    $ret = array();
    if (function_exists('mb_eregi')) {
        mb_eregi($pattern, $subject, $ret);
    } else {
        preg_match("/" . $pattern . "/", $subject, $ret);
    }
    return $ret;
}

/**
 * Process search & replace mechanism (aka "body_filters")
 * @param &$content string  Post content
 */
function uppsite_process_body_filters(&$content) {
    $filters = json_decode(mysiteapp_get_prefs_value('body_filter', null));
    if (count($filters) == 0) {
        return;
    }
    $tmpContent = $content;
    foreach ($filters as $filter) {
        $search = "/" . $filter[0] . "/ms"; // Set PRCE regex - with MULTILINE and DOTALL params.
        $replace = $filter[1];
        $tmpContent = preg_replace($search, $replace, $tmpContent);
    }
    $content = $tmpContent;
}

/**
 * In homepage display layout, the first query is the original query for the carousel posts.
 * Afterwards, there is a custom query (mysiteapp_set_current_query()) for the category items,
 * so this is the way to identify the carousel items.
 * @return bool Tells whether the current query is the query of the homepage carousel items
 */
function uppsite_is_homepage_carousel() {
    return $GLOBALS['wp_query'] == mysiteapp_get_current_query() && mysiteapp_get_posts_layout() == "homepage";
}

/**
 * Checks if the object should be filtered from results by checking its permalink
 * @param $obj  Object/Array of data that needs to be checked
 * @return boolean  Whether this object should be filtered or not.
 */
function uppsite_should_filter($obj) {
    $permalink = null;
    if ( is_object($obj) && isset($obj->permalink) ) {
        $permalink = $obj->permalink;
    } elseif ( is_array($obj) && array_key_exists('permalink', $obj) ) {
        $permalink = $obj['permalink'];
    } elseif ( is_string($obj) ) {
        $permalink = $obj;
    }
    $url_filters = json_decode(mysiteapp_get_prefs_value('url_filter', null), true);
    if (empty($permalink) || count($url_filters) == 0) {
        return false;
    }
    foreach ($url_filters as $filter) {
        if (uppsite_match($filter, $permalink)) {
            return true;
        }
    }
    return false;
}

/**
 * Returns the post information
 * @param bool $with_content Include the content or not?
 * @return array
 */
function uppsite_process_post($with_content = false) {
    $ret = array(
        'id' => get_the_ID(),
        'permalink' => get_permalink(),
        'title' => html_entity_decode(get_the_title(), ENT_QUOTES, 'UTF-8'),
        'member' => uppsite_get_member(),
        'excerpt' => get_the_excerpt(),
        'time' => apply_filters('the_time', get_the_time( 'm/d/y G:i' ), 'm/d/y G:i'),
        'unix_time' => apply_filters('the_time', get_the_time( 'U' ), 'U'),
        'comments_link' => get_comments_link(),
        'comments_num' => get_comments_number(),
        'comments_open' => comments_open()
    );
    $post_content = null;
    if ($with_content) {
        ob_start();
        the_content();
        $post_content = ob_get_contents();
        ob_get_clean();
    }
    $ret['thumb_url'] = mysiteapp_extract_thumbnail($post_content);

    if ($with_content) {
        uppsite_process_body_filters($post_content);
        $ret['content'] = $post_content;
    }

    // Trim the title to fit the view
    $maxChar = is_null($ret['thumb_url']) ? UPPSITE_MAX_TITLE_LENGTH + 15 : UPPSITE_MAX_TITLE_LENGTH;
    $maxChar += (isset($_GET['view']) && $_GET['view'] == "excerpt") ? 0 : 22;
    $maxChar += ($with_content) ? +10 : 0;
    if (uppsite_is_homepage_carousel()) {
        $maxChar = 66;
    }
    $orgLen = uppsite_strlen($ret['title']);
    if ($orgLen > $maxChar) {
        $matches = uppsite_match("(.{0," . $maxChar . "})\s", $ret['title']);
        $newTitle = rtrim($matches[1]);
        $newTitle .= (uppsite_strlen($newTitle) == $orgLen) ? "" : " ..."; // Adding elipssis only if string was actually trimmed (because of regex)
    }
    if (!is_null($newTitle)) {
        // Make sure the trimming didn't leave a null title.
        $ret['title'] = $newTitle;
    }
    
    return $ret;
}

/**
 * Prints/returns a posts list which returns from a list function (e.g. categories or tags)
 * @param $funcname The list function
 * @param bool $echo Print?
 * @return array
 */
function uppsite_posts_list($funcname, $echo = true) {
    $list = call_user_func($funcname, array('echo' => false));
    $tmpArr = uppsite_format_html_to_array($list);

    // Filter posts
    $arr = array();
    foreach ($tmpArr as $val) {
        if ( !uppsite_should_filter( $val ) ) {
            $arr[] = $val;
        }
    }

    if (!$echo) {
        return $arr;
    }
    print json_encode($arr);
}

/**
 * Returns the right template page according to the request
 * @param $template Template name
 * @return string Template path
 */
function uppsite_get_webapp_page($template) {
    if (!defined('UPPSITE_AJAX')) {
        return $template;
    }
    if (function_exists('uppsite_func_' . UPPSITE_AJAX)) {
        call_user_func('uppsite_func_' . UPPSITE_AJAX);
        return null;
    }
    $page = TEMPLATEPATH . "/" . UPPSITE_AJAX . "-ajax.php";
    if (!file_exists($page)) {
        $page = TEMPLATEPATH . "/index-ajax.php";
    }
    return $page;
}

/**
 * Login related functions:
 * - Details
 * - Logout
 * - Login
 * @param $url URL of redirection
 * @param $queryRedirectTo $_REQUEST['redirectTo']
 * @param $user WP_User
 * @return mixed
 */
function uppsite_redirect_login($url, $queryRedirectTo, $user) {
    if (!defined('UPPSITE_AJAX')) {
        return $url;
    }

    if (UPPSITE_AJAX == "user_details") {
        // Details
        if (is_user_logged_in()) {
            global $current_user;
            get_currentuserinfo();

            $res = array(
                'success' => true,
                'username' => $current_user->user_login,
                'userid' => $current_user->ID,
                'publish' => $current_user->has_cap('publish_posts'),
                'logged' => true
            );
        } else {
            $res = array('logged'=>false);
        }
        print json_encode($res);
    } elseif (UPPSITE_AJAX == "logout") {
        // Logout
        wp_logout();
    } else {
        // login_redirect, if
        if (isset($user->ID)) {
            print json_encode(
                array(
                    'success' => true,
                    'username' => $user->user_login,
                    'userid' => $user->ID,
                    'publish' => $user->has_cap('publish_posts')
                )
            );
        } else {
            print json_encode(array('success' => false));
        }
    }
    exit;
}

/**
 * Hook redirection after commenting
 */
function uppsite_redirect_comment() {
    print json_encode(array('success' => false));
    exit;
}

/**
 * Get the analytics key
 * @return string   The analytics key
 */
function uppsite_get_analytics_key() {
    return UPPSITE_DEFAULT_ANALYTICS_KEY;
}

/**
 * Publishes a post made through the webapp (ajax request of "create_quick_post")
 */
function uppsite_func_create_quick_post() {
    if (current_user_can('publish_posts')) {
        if (empty($_POST['post_title']) || empty($_POST['content']) || "publish" != $_POST['post_status']) {
            exit;
        }
        $post_title =  esc_html(stripslashes($_POST['post_title']));
        $post_content = esc_html( stripslashes($_POST['content']));
        $post_date = current_time('mysql');
        $post_date_gmt = current_time('mysql', 1);
        $post_status = 'publish';
        $current_user = wp_get_current_user();
        $post_author = $current_user->ID;
        $post_data = compact('post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_tags', 'post_status');
        $post_ID = wp_insert_post($post_data);
        print json_encode(array(
            'success' => !is_wp_error($post_ID) && is_numeric($post_ID),
            'post_id' => $post_ID
        ));
    }
    exit;
}

/**
 * Seeks for categories to display in homepage -
 * If a list present in prefs, display by it. If not, return the popular categories.
 * @return array    List of categories to display in homepage
 */
function uppsite_homepage_get_categories() {
    $settings = uppsite_homepage_get_settings();
    if (array_key_exists('cats_ar', $settings)) {
        return $settings['cats_ar'];
    }
    return mysiteapp_homepage_get_popular_categories();
}

/**
 * Transforms a RGB hex color to array with decimal values
 * @param $rgbHex string    String like "#000000"
 * @return array    Array(r, g, b) in decimal values.
 */
function uppsite_rgbhex2arr($rgbHex) {
    return array(
        hexdec(substr($rgbHex, 1, 2)), # R
        hexdec(substr($rgbHex, 3, 2)), # G
        hexdec(substr($rgbHex, 5, 2)) # B
    );
}

/**
 * Transforms a RGB hex to HSL
 * @param $rgbHex string    String like "#000000"
 * @return array    Array of Hue, Saturation and Lightness
 */
function uppsite_rgb2hsl($rgbHex) {
    $rgbArr = uppsite_rgbhex2arr($rgbHex);
    list($r, $g, $b) = array(
        $rgbArr[0] / 255.0,
        $rgbArr[1] / 255.0,
        $rgbArr[2] / 255.0);
    $max = max($r, $g, $b);
    $min = min($r, $g, $b);
    $d = $max - $min;

    if ($max == $min) {
        $h = 0;
        $s = 0;
    }
    else if ($max == $r)
        $h = 60 * ($g - $b) / $d;
    else if ($max == $g)
        $h = 60 * ($b - $r) / $d + 120;
    else if ($max == $b)
        $h = 60 * ($r - $g) / $d + 240;

    $l = ($max + $min) / 2;

    if ($l > 0.5)
        $s = (2 - $max - $min) != 0 ? $d / (2 - $max - $min) : 0;
    else
        $s = ($max + $min) != 0 ? $d / ($max + $min) : 0;

    while ($h > 360) $h -= 360;
    while ($h < 0) $h += 360;

    return array($h, $s * 100, $l * 100);
}

/**
 * Transforms HUE to RGB value
 * @param $p
 * @param $q
 * @param $t
 * @return float
 */
function uppsite_hue2rgb($p, $q, $t) {
    if ($t < 0)
        $t += 1;
    if ($t > 1)
        $t -= 1;

    if ($t < 1/6)
        $p = $p + ($q - $p) * 6 * $t;
    else if ($t < 1/2)
        $p = $q;
    else if ($t < 2/3)
        $p = $p + ($q - $p) * (2/3 - $t) * 6;

    return round($p * 255);
}

/**
 * Transforms HSL to RGB hex
 * @param $hsl array    HSL array
 * @return string   RGB hex
 */
function uppsite_hsl2rgb($hsl) {
    $h = $hsl[0] / 360.0;
    $s = $hsl[1] / 100.0;
    $l = $hsl[2] / 100.0;

    $q = $l < 0.5 ? $l * (1 + $s)
        : $l + $s - $l * $s;
    $p = 2 * $l - $q;

    // Zero padding the hex results.
    $red   = sprintf("%02x", uppsite_hue2rgb($p, $q, $h + 1/3));
    $green = sprintf("%02x", uppsite_hue2rgb($p, $q, $h));
    $blue  = sprintf("%02x", uppsite_hue2rgb($p, $q, $h - 1/3));
    return "#" . $red . $green . $blue;
}

/**
 * Performs "lighten" function (like in SASS) on rgb
 * @param $rgbHex string    RGB hex
 * @param $percent int  Percent of lightening
 * @return string   Lightened RGB hex
 */
function uppsite_rgb_lighten($rgbHex, $percent) {
    $hsl = uppsite_rgb2hsl($rgbHex);
    $hsl[2] = min(max(0, $percent+$hsl[2]), 100); // Set the "Lightness", restrict to 0..100
    return uppsite_hsl2rgb($hsl);
}

/**
 * Performs "darken" function (like in SASS) on rgb
 * @param $rgbHex string    RGB hex
 * @param $percent int  Percent of lightening
 * @return string   Lightened RGB hex
 */
function uppsite_rgb_darken($rgbHex, $percent) {
    return uppsite_rgb_lighten($rgbHex, -1.0 * $percent);
}

/**
 * Returns a search & replace values for the webapp colors.
 * The replacement takes place on the fly when loading the css via the microloader.
 * @return array    Array of search & replace
 */
function uppsite_get_colours() {
    $navbarColor = mysiteapp_get_prefs_value("navbar_tint_color", "#f2f2f2");
    $conceptColor = mysiteapp_get_prefs_value("application_global_color", "#1d5ba0");

    $navbarDarkColor = uppsite_rgb_darken($navbarColor, 10.3); // 31/3.0 = 10.3%

    $conceptLightColor = uppsite_rgb_lighten($conceptColor, 10);
    $conceptDarkColor = uppsite_rgb_darken($conceptColor, 10);

    $conceptRgb = uppsite_rgbhex2arr($conceptColor); // There is a place where the color is in RGBA, thus can't be in hex.

    return array(
        "#f2f2f2" => $navbarColor, // Navigation color
        "#1d5ba0" => $conceptColor, // Concept color
        "#d8d8d8" => $navbarDarkColor, // Navigation dark color
        "#2574cb" => $conceptLightColor, // Concept light color
        "#154275" => $conceptDarkColor, // Concept dark color
        "29,91,160" => implode($conceptRgb, ",")
    );
}

/** Hook every template path we modify */
add_filter('index_template', 'uppsite_get_webapp_page');
add_filter('front_page_template', 'uppsite_get_webapp_page');
add_filter('home_template', 'uppsite_get_webapp_page');
add_filter('sidebar_template', 'uppsite_get_webapp_page');
add_filter('category_template', 'uppsite_get_webapp_page');
add_filter('search_template', 'uppsite_get_webapp_page');
add_filter('tag_template', 'uppsite_get_webapp_page');
add_filter('archive_template', 'uppsite_get_webapp_page');

/** Hook login  **/
add_filter('login_redirect', 'uppsite_redirect_login', 10, 3);

/** Hook comment redirect **/
add_filter('comment_post_redirect', 'uppsite_redirect_comment', 10, 3);

/** Fix youtube iframe to flash object on iOS (to be rendered in YouTube app) */
function uppsite_fix_youtube($content) {
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    if (strpos($userAgent, "iPhone") === false &&
        strpos($userAgent, "iPad") === false &&
        strpos($userAgent, "iPod") === false) {
        return $content;
    }
    // Match the iframe pattern, to find
    if (!preg_match_all("/<iframe[^>]*src=\"[^\"]*youtube.com[^\"]*\"[^>]*>[^<]*<\/iframe>/x", $content, $matches)) {
        return $content;
    }
    foreach ($matches[0] as $iframe) {
        preg_match_all("/(src|width|height)=(?:\"|')([^\"']+)(?:\"|')/", $iframe, $fields);
        $vals = array(
            "height" => "",
            "width" => "",
            "src" => ""
        );
        $videoId = "";
        for ($i = 0; $i < count($fields[0]); $i++) {
            $key = $fields[1][$i];
            $vals[$key] = $fields[2][$i];
            if ($key == "src") {
                $vals[$key] = preg_replace("/([^\?]+)\??(.*)/", "$1", $vals[$key]);
                preg_match("/\/embed\/(.+)/", $vals[$key], $parts);
                $vals['videoId'] = $parts[1];
                $vals[$key] = str_replace("/embed/", "/watch?v=", $vals[$key]);
            }
        }
        $replacement = '<p><img class="uppsite-youtube-video" vid="' . $vals['src'] . '" src="http://i.ytimg.com/vi/' . $vals['videoId'] . '/0.jpg"/><img src="" height="10" width="10"/></p>';
        $content = str_replace($iframe, $replacement, $content);
    }
    return $content;
}
add_filter('the_content', 'uppsite_fix_youtube', 100); // Run last
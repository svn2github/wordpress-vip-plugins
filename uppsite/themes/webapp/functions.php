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
 * Try and extract the first image in the post, to be used as thumbnail image.
 * If an image is found, it is removed from the content.
 * @param &$content Post content
 * @return mixed
 */
function uppsite_extract_image_from_post_content(&$content) {
    if (!preg_match("/<img[^>]*src=\"([^\"]+)\"[^>]*>/", $content, $matches)) {
        return null;
    }
    if (strpos($matches[0], "uppsite-youtube-video") !== false) {
        // Don't extract youtube video images.
        return null;
    }
    $content = str_replace($matches[0], "", $content);
    return $matches[1];
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
 * Returns the post information
 * @param bool $with_content Include the content or not?
 * @return array
 */
function uppsite_process_post($with_content = false) {
    $thumb_url = mysiteapp_extract_thumbnail();

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
        'comments_open' => comments_open(),
		'tags' => uppsite_posts_list('get_the_tag_list', false),
		'categories' => uppsite_posts_list('wp_list_categories', false),
	);
    if ($with_content || is_null($thumb_url)) {
        ob_start();
        the_content();
        $post_content = ob_get_contents();
        ob_get_clean();
        if (is_null($thumb_url)) {
            // No thumb? try to fetch from post
            $thumb_url = uppsite_extract_image_from_post_content($post_content);
        }
    }
    $ret['thumb_url'] = $thumb_url;

    if ($with_content) {
        $ret['content'] = $post_content;
    } else {
        // Trim the title to fit the view
    	$maxChar = is_null($ret['thumb_url']) ? UPPSITE_MAX_TITLE_LENGTH + 15 : UPPSITE_MAX_TITLE_LENGTH;
    	$maxChar += (isset($_GET['view']) && $_GET['view'] == "excerpt") ? 0 : -10;
    	$orgLen = uppsite_strlen($ret['title']);
	    if ($orgLen > $maxChar) {
            $matches = uppsite_match("(.{0," . $maxChar . "})\s", $ret['title']);
	    	$ret['title'] = rtrim($matches[1]);
	    	$ret['title'] .= (uppsite_strlen($ret['title']) == $orgLen) ? "" : " ..."; // Adding elipssis only if string was actually trimmed (because of regex)
	    }
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
    $arr = uppsite_format_html_to_array($list);
    if (count($arr) == 0) {
        return;
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

function uppsite_get_pref_direction() {
	$options = get_option(MYSITEAPP_OPTIONS_PREFS);
	
	return isset($options['direction']) ? $options['direction'] : 'ltr';
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
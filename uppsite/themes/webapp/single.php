<?php
/**
 * Single page
 */
if (defined('UPPSITE_AJAX')) {
    ob_start(); // Catch anything printed to screen by plugins and etc.

    the_post();
    $single = uppsite_process_post(true);

    global $this_comments;
    comments_template();
    $single['comments'] = $this_comments;

    ob_end_clean(); // Clean the output buffer

    print json_encode(array($single));
} else {
    // Direct url, will be forwarded to the webapp with a link to the post.
    $url = home_url();
    $id = get_the_ID();
    wp_safe_redirect($url.'/#post/'.$id);
    exit;
}
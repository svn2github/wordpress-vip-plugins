<?php
if (!function_exists("esc_js")) {
    function esc_js($text) {
        return js_escape($text);
    }
}

if (!function_exists("esc_attr")) {
    function esc_attr($text) {
        return attribute_escape($text);
    }
}

if (!function_exists("esc_html")) {
    function esc_html($text) {
        return wp_specialchars($text);
    }
}

if (!function_exists("get_comments")) {
    # The function was taken from WP 2.9.2
    /**
     * Retrieve a list of comments.
     *
     * The comment list can be for the blog as a whole or for an individual post.
     *
     * The list of comment arguments are 'status', 'orderby', 'comment_date_gmt',
     * 'order', 'number', 'offset', and 'post_id'.
     *
     * @since 2.7.0
     * @uses $wpdb
     *
     * @param mixed $args Optional. Array or string of options to override defaults.
     * @return array List of comments.
     */
    function get_comments( $args = '' ) {
        global $wpdb;

        $defaults = array('status' => '', 'orderby' => 'comment_date_gmt', 'order' => 'DESC', 'number' => '', 'offset' => '', 'post_id' => 0);

        $args = wp_parse_args( $args, $defaults );
        extract( $args, EXTR_SKIP );

        // $args can be whatever, only use the args defined in defaults to compute the key
        $key = md5( serialize( compact(array_keys($defaults)) )  );
        $last_changed = wp_cache_get('last_changed', 'comment');
        if ( !$last_changed ) {
            $last_changed = time();
            wp_cache_set('last_changed', $last_changed, 'comment');
        }
        $cache_key = "get_comments:$key:$last_changed";

        if ( $cache = wp_cache_get( $cache_key, 'comment' ) ) {
            return $cache;
        }

        $post_id = absint($post_id);

        if ( 'hold' == $status )
            $approved = "comment_approved = '0'";
        elseif ( 'approve' == $status )
            $approved = "comment_approved = '1'";
        elseif ( 'spam' == $status )
            $approved = "comment_approved = 'spam'";
        elseif ( 'trash' == $status )
            $approved = "comment_approved = 'trash'";
        else
            $approved = "( comment_approved = '0' OR comment_approved = '1' )";

        $order = ( 'ASC' == $order ) ? 'ASC' : 'DESC';

        $orderby = 'comment_date_gmt';  // Hard code for now

        $number = absint($number);
        $offset = absint($offset);

        if ( !empty($number) ) {
            if ( $offset )
                $number = 'LIMIT ' . $offset . ',' . $number;
            else
                $number = 'LIMIT ' . $number;

        } else {
            $number = '';
        }

        if ( ! empty($post_id) )
            $post_where = $wpdb->prepare( 'comment_post_ID = %d AND', $post_id );
        else
            $post_where = '';

        $comments = $wpdb->get_results( "SELECT * FROM $wpdb->comments WHERE $post_where $approved ORDER BY $orderby $order $number" );
        wp_cache_add( $cache_key, $comments, 'comment' );

        return $comments;
    }
}

?>

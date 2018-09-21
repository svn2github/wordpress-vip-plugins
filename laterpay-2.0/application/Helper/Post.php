<?php

/**
 * LaterPay post helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Helper_Post
{

    /**
     * Contains the access state for all loaded posts.
     *
     * @var array
     */
    private static $access = array();

    /**
     * Set state for the particular post $id.
     *
     * @param string    $id
     * @param bool      $state
     */
    public static function set_access_state( $id, $state ) {
        self::$access[ $id ] = $state;
    }

    /**
     * Return the access state for all loaded posts.
     *
     * @return array
     */
    public static function get_access_state() {
        return self::$access;
    }

    /**
     * Return all content ids for selected post
     *
     * @param $post_id
     *
     * @return array
     */
    public static function get_content_ids( $post_id ) {
        $time_passes_list   = LaterPay_Helper_TimePass::get_time_passes_list_by_post_id( $post_id );
        $time_passes        = LaterPay_Helper_TimePass::get_tokenized_time_pass_ids( $time_passes_list );
        $subscriptions_list = LaterPay_Helper_Subscription::get_subscriptions_list_by_post_id( $post_id );
        $subscriptions      = LaterPay_Helper_Subscription::get_tokenized_ids( $subscriptions_list );
        return array_merge( array_merge( array( $post_id ), $time_passes, $subscriptions ) );
    }

    /**
     * Check, if user has access to a post.
     *
     * @param WP_Post $post
     * @param bool    $is_attachment
     * @param null    $main_post_id
     *
     * @return boolean success
     */
    public static function has_access_to_post( WP_Post $post, $is_attachment = false, $main_post_id = null ) {
        $has_access     = false;
        $client_options = LaterPay_Helper_Config::get_php_client_options();
        $token_name     = $client_options['token_name'];

        // @Todo: Fix cookie usage for WP VIP.
        $token_name = filter_input( INPUT_COOKIE, $token_name, FILTER_SANITIZE_STRING );

        if ( apply_filters( 'laterpay_access_check_enabled', true ) && isset( $token_name ) ) {

            // check, if parent post has access with time passes
            $parent_post = $is_attachment ? $main_post_id : $post->ID;
            $time_passes_list = LaterPay_Helper_TimePass::get_time_passes_list_by_post_id($parent_post);
            $time_passes = LaterPay_Helper_TimePass::get_tokenized_time_pass_ids($time_passes_list);

            foreach ($time_passes as $time_pass) {
                if (array_key_exists($time_pass, self::$access) && self::$access[$time_pass]) {
                    $has_access = true;
                }
            }

            // check, if parent post has access with subscriptions
            $subscriptions_list = LaterPay_Helper_Subscription::get_subscriptions_list_by_post_id($parent_post);
            $subscriptions = LaterPay_Helper_Subscription::get_tokenized_ids($subscriptions_list);

            foreach ($subscriptions as $subscription) {
                if (array_key_exists($subscription, self::$access) && self::$access[$subscription]) {
                    $has_access = true;
                }
            }

            // check access for the particular post
            if ( ! $has_access ) {
                if ( array_key_exists( $post->ID, self::$access ) ) {
                    $has_access = (bool)self::$access[$post->ID];
                } elseif ( LaterPay_Helper_Pricing::get_post_price( $post->ID, true ) > 0 ) {
                    $result = LaterPay_Helper_Request::laterpay_api_get_access( array_merge( array( $post->ID ), $time_passes, $subscriptions ) );

                    if ( empty( $result ) || ! array_key_exists('articles', $result) ) {

                    } else {
                        foreach ( $result['articles'] as $article_key => $article_access ) {
                            $access = (bool)$article_access['access'];
                            self::$access[$article_key] = $access;
                            if ($access) {
                                $has_access = true;
                            }
                        }
                    }
                }
            }

        }

        return apply_filters( 'laterpay_post_access', $has_access );
    }

    /**
     * Check, if gift code was purchased successfully and user has access.
     *
     * @return mixed return false if gift card is incorrect or doesn't exist, access data otherwise
     */
    public static function has_purchased_gift_card() {

        $purchased_gift_card = filter_input( INPUT_COOKIE, 'laterpay_purchased_gift_card', FILTER_SANITIZE_STRING );

        if ( isset( $purchased_gift_card ) ) {
            // get gift code and unset session variable
            $cookies = ( isset( $purchased_gift_card ) ) ? $purchased_gift_card : '';
            list( $code, $time_pass_id ) = explode( '|', $cookies );
            // create gift code token
            $code_key = '[#' . $code . ']';

            // check, if gift code was purchased successfully and user has access
            $result = LaterPay_Helper_Request::laterpay_api_get_access( array( $code_key ) );

            if ( empty( $result ) || ! array_key_exists( 'articles', $result ) ) {
                return false;
            }

            // return access data, if all is ok
            if ( array_key_exists( $code_key, $result['articles'] ) ) {
                $access = (bool) $result['articles'][ $code_key ]['access'];
                self::$access[ $code_key ] = $access;

                return array(
                    'access'  => $access,
                    'code'    => $code,
                    'pass_id' => $time_pass_id,
                );
            }
        }

        return false;
    }

    /**
     * Get the LaterPay purchase link for a post.
     *
     * @param int $post_id
     * @param int $current_post_id optional for attachments
     *
     * @return string url || empty string, if something went wrong
     */
    public static function get_laterpay_purchase_link( $post_id, $current_post_id = null ) {
        $post = get_post( $post_id );
        if ( $post === null ) {
            return '';
        }

        $config = laterpay_get_plugin_config();

        $currency       = $config->get( 'currency.code' );
        $price          = LaterPay_Helper_Pricing::get_post_price( $post->ID );
        $revenue_model  = LaterPay_Helper_Pricing::get_post_revenue_model( $post->ID );

        $client_options = LaterPay_Helper_Config::get_php_client_options();
        $client         = new LaterPay_Client(
            $client_options['cp_key'],
            $client_options['api_key'],
            $client_options['api_root'],
            $client_options['web_root'],
            $client_options['token_name']
        );

        // data to register purchase after redirect from LaterPay
        $url_params = array(
            'post_id' => $post->ID,
            'buy'     => 'true',
        );

        if ( $post->post_type === 'attachment' ) {
            $url_params['post_id']           = $current_post_id;
            $url_params['download_attached'] = $post->ID;
        }

	    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? filter_var( $_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL ) : ''; // phpcs:ignore
        $parsed_link = explode( '?', $request_uri );
        $back_url    = get_permalink( $post->ID ) . '?' . build_query( $url_params );

        // if params exists in uri
        if ( ! empty( $parsed_link[1] ) ) {
            $back_url .= '&' . $parsed_link[1];
        }

        // parameters for LaterPay purchase form
        $params = array(
            'article_id'    => $post->ID,
            'pricing'       => $currency . ( $price * 100 ),
            'url'           => $back_url,
            'title'         => $post->post_title,
            'require_login' => (int) get_option( 'laterpay_require_login', 0 ),
        );

        if ( $revenue_model === 'sis' ) {
            // Single Sale purchase
            return $client->get_buy_url( $params );
        } else {
            // Pay-per-Use purchase
            return $client->get_add_url( $params );
        }
    }

    /**
     * Prepare the purchase button.
     *
     * @wp-hook laterpay_purchase_button
     *
     * @param WP_Post  $post
     * @param null|int $current_post_id optional for attachments
     *
     * @return array
     */
    public static function the_purchase_button_args( WP_Post $post, $current_post_id = null ) {
        $config = laterpay_get_plugin_config();

        // render purchase button for administrator always in preview mode, too prevent accidental purchase by admin.
        $preview_mode = LaterPay_Helper_User::preview_post_as_visitor( $post );
        if ( current_user_can( 'administrator' ) ) {
            $preview_mode = true;
        }

        $view_args = array(
            'post_id'                   => $post->ID,
            'link'                      => LaterPay_Helper_Post::get_laterpay_purchase_link( $post->ID, $current_post_id ),
            'currency'                  => $config->get( 'currency.code' ),
            'price'                     => LaterPay_Helper_Pricing::get_post_price( $post->ID ),
            'preview_post_as_visitor'   => $preview_mode,
        );

        return $view_args;
    }

    /**
     * Add teaser to the post or update it.
     *
     * @param WP_Post $post
     * @param null $teaser teaser data
     * @param bool $need_update
     *
     * @return string $new_meta_value teaser content
     */
    public static function add_teaser_to_the_post( WP_Post $post, $teaser = null, $need_update = true ) {
        if ( $teaser ) {
            $new_meta_value = $teaser;
        } else {
            $new_meta_value = LaterPay_Helper_String::truncate(
                preg_replace( '/\s+/', ' ', strip_shortcodes( $post->post_content ) ),
                get_option( 'laterpay_teaser_content_word_count' ),
                array(
                    'html'  => true,
                    'words' => true,
                )
            );
        }

        if ( $need_update ) {
            update_post_meta( $post->ID, 'laterpay_post_teaser', $new_meta_value );
        }

        return $new_meta_value;
    }

    /**
     * Process more tag.
     *
     * @param $teaser_content
     * @param $post_id
     * @param null|string $more_link_text
     * @param bool|false $strip_teaser
     *
     * @return string $output
     */
    public static function get_the_content( $teaser_content, $post_id, $more_link_text = null, $strip_teaser = false ) {
        global $more;

        if ( null === $more_link_text ) {
            $more_link_text = __( '(more&hellip;)' );
        }

        $output = '';
        $original_teaser = $teaser_content;
        $has_teaser = false;

        if ( preg_match( '/<!--more(.*?)?-->/', $teaser_content, $matches ) ) {
            $teaser_content = explode( $matches[0], $teaser_content, 2 );
            if ( ! empty( $matches[1] ) && ! empty( $more_link_text ) ) {
                $more_link_text = wp_strip_all_tags( wp_kses_no_null( trim( $matches[1] ) ) );
            }
            $has_teaser = true;
        } else {
            $teaser_content = array( $teaser_content );
        }

        if ( false !== strpos( $original_teaser, '<!--noteaser-->' ) ) {
            $strip_teaser = true;
        }

        $teaser = $teaser_content[0];

        if ( $more && $strip_teaser && $has_teaser ) {
            $teaser = '';
        }

        $output .= $teaser;

        if ( count( $teaser_content ) > 1 ) {
            if ( $more ) {
                $output .= '<span id="more-' . intval( $post_id ) . '"></span>' . wp_kses_post( $teaser_content[1] );
            } else {
                if ( ! empty( $more_link_text ) ) {
                    $output .= '<a href="' . esc_url( get_permalink() ) . "#more-".intval( $post_id )."\" class=\"more-link\">".wp_kses_post( $more_link_text ). "</a>";
                }
                $output = force_balance_tags( $output );
            }
        }

        return $output;
    }

    /*
     * Retrieves a page given its title.
     *
     * @param string  $page_title Page title.
     * @param string  $output     Optional. Output type; OBJECT*, ARRAY_N, or ARRAY_A.
     * @param string  $post_type  Optional. Post type; default is 'page'.
     *
     * @return mixed WP_Post on success or null on failure.
     */
    public static function get_page_by_title( $page_title, $output, $post_type ) {

        if ( laterpay_check_is_vip() ) {
            $post = wpcom_vip_get_page_by_title( $page_title, $output, $post_type );
        } else {
            $post = get_page_by_title( $page_title, $output, $post_type ); // phpcs:ignore
        }

        return $post;
    }
}

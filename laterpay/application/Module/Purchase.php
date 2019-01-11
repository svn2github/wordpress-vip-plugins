<?php

/**
 * LaterPay Purchase class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Module_Purchase extends LaterPay_Core_View implements LaterPay_Core_Event_SubscriberInterface {

    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_shared_events()
     */
    public static function get_shared_events() {
        return array(
            'laterpay_is_purchasable' => array(
                array( 'is_purchasable' ),
            ),
            'laterpay_on_view_purchased_post_as_visitor' => array(
                array( 'on_view_purchased_post_as_visitor' ),
            ),
        );
    }

    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'laterpay_loaded' => array(
                array( 'buy_post', 10 ),
                array( 'set_token', 5 )
            ),
            'laterpay_purchase_button' => array(
                array( 'laterpay_on_preview_post_as_admin', 200 ),
                array( 'laterpay_on_view_purchased_post_as_visitor', 200 ),
                array( 'is_purchasable', 100 ),
                array( 'on_purchase_button' ),
                array( 'purchase_button_position', 0 ),
            ),
            'laterpay_explanatory_overlay' => array(
                array( 'laterpay_on_view_purchased_post_as_visitor', 200 ),
                array( 'is_purchasable', 100 ),
                array( 'on_explanatory_overlay' ),
            ),
            'laterpay_purchase_overlay' => array(
                array( 'laterpay_on_view_purchased_post_as_visitor', 200 ),
                array( 'is_purchasable', 100 ),
                array( 'on_purchase_overlay' ),
            ),
            'laterpay_explanatory_overlay_content' => array(
                array( 'on_explanatory_overlay_content' ),
            ),
            'laterpay_purchase_overlay_content' => array(
                array( 'on_purchase_overlay_content' ),
            ),
            'laterpay_purchase_link' => array(
                array( 'laterpay_on_preview_post_as_admin', 200 ),
                array( 'laterpay_on_view_purchased_post_as_visitor', 200 ),
                array( 'is_purchasable', 100 ),
                array( 'on_purchase_link' ),
            ),
            'laterpay_post_content' => array(
                array( 'laterpay_on_view_purchased_post_as_visitor', 200 ),
                array( 'is_purchasable', 100 ),
                array( 'modify_post_content', 5 ),
            ),
            'laterpay_check_user_access' => array(
                array( 'check_user_access' ),
            ),
        );
    }

    /**
     * Renders LaterPay purchase button
     *
     * @param LaterPay_Core_Event $event
     */
    public function on_purchase_button( LaterPay_Core_Event $event ) {
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        $current_post_id = null;
        if ( $event->has_argument( 'current_post' ) ) {
            $current_post_id = $event->get_argument( 'current_post' );
        }

        // create account links URL with passed parameters
        $client_options = LaterPay_Helper_Config::get_php_client_options();
        $client         = new LaterPay_Client(
            $client_options['cp_key'],
            $client_options['api_key'],
            $client_options['api_root'],
            $client_options['web_root'],
            $client_options['token_name']
        );

        $back_url    = get_permalink( $current_post_id ? $current_post_id : $post->ID );
        $content_ids = LaterPay_Helper_Post::get_content_ids( $post->ID );

        $view_args = array_merge( array(
                'post_id'           => $post->ID,
                'link'              => LaterPay_Helper_Post::get_laterpay_purchase_link( $post->ID, $current_post_id ),
                'currency'          => $this->config->get( 'currency.code' ),
                'price'             => LaterPay_Helper_Pricing::get_post_price( $post->ID ),
                'notification_text' => __( 'I already bought this', 'laterpay' ),
                'identify_url'      => $client->get_identify_url( $back_url, $content_ids ),
                'attributes'        => array(),
            ),
            $event->get_arguments()
        );

        $this->assign( 'laterpay', $view_args );
        $html_escaped = $this->get_text_view( 'frontend/partials/widget/purchase-button' );

        $event->set_result( $html_escaped )
            ->set_arguments( $view_args );
    }

    /**
     * Renders LaterPay explanatory overlay
     *
     * @param LaterPay_Core_Event $event
     */
    public function on_explanatory_overlay( LaterPay_Core_Event $event ) {
        $post   = $event->get_argument( 'post' );
        $teaser = $event->get_argument( 'teaser' );

        // get overlay content
        $revenue_model = LaterPay_Helper_Pricing::get_post_revenue_model( $post->ID );
        $overlay_content_event = new LaterPay_Core_Event( array( $revenue_model ) );
        $overlay_content_event->set_echo( false );
        laterpay_event_dispatcher()->dispatch( 'laterpay_explanatory_overlay_content', $overlay_content_event );

        $view_args = array(
            'teaser' => $teaser,
            'data'   => (array) $overlay_content_event->get_result(),
        );

        $this->assign( 'overlay', $view_args );
        $html_escaped = $this->get_text_view( 'frontend/partials/widget/explanatory-overlay' );

        $event->set_result( $html_escaped );
    }

    /**
     * Renders LaterPay purchase overlay
     *
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function on_purchase_overlay( LaterPay_Core_Event $event ) {
        $post = $event->get_argument( 'post' );

        // get overlay content
        $overlay_content_event = new LaterPay_Core_Event();
        $overlay_content_event->set_echo( false );
        $overlay_content_event->set_arguments( $event->get_arguments() );
        laterpay_event_dispatcher()->dispatch( 'laterpay_purchase_overlay_content', $overlay_content_event );

        $back_url    = get_permalink( $post->ID );
        $content_ids = LaterPay_Helper_Post::get_content_ids( $post->ID );
        $revenue_model = LaterPay_Helper_Pricing::get_post_revenue_model( $post->ID );

        // Get the value of purchase type.
        $post_price_behaviour = LaterPay_Helper_Pricing::get_post_price_behaviour();
        $post_price_type_one  = ( 1 === $post_price_behaviour );

        // If Individual purchase is turned off then select revenue model of timepass or subscription.
        if ( $post_price_type_one || LaterPay_Helper_Pricing::is_post_price_type_two_price_zero() ) {

            $content_data = (array) $overlay_content_event->get_result();

            // Check if timepass(es) exist.
            if ( ! empty( $content_data['timepasses'] ) ) {

                // If timepass(es) available, then select revenue model of first timepass.
                $revenue_model = $content_data['timepasses'][0]['revenue'];

            } else {

                // If timepass(es) not available, then select revenue model of subscription.
                $revenue_model = 'sub';

            }

        }

        switch ($revenue_model) {
            case 'sis':
                $submit_text = __('Buy Now', 'laterpay');
                break;
            case 'sub':
                $submit_text = __('Subscribe Now', 'laterpay');
                break;
            case 'ppu':
            default:
                $submit_text = __('Buy Now, Pay Later', 'laterpay');
                break;
        }

        // create account links URL with passed parameters
        $client_options = LaterPay_Helper_Config::get_php_client_options();
        $client         = new LaterPay_Client(
            $client_options['cp_key'],
            $client_options['api_key'],
            $client_options['api_root'],
            $client_options['web_root'],
            $client_options['token_name']
        );

        $view_args = array(
            'title'             => LaterPay_Helper_Appearance::get_current_options( 'header_title' ),
            'currency'          => $this->config->get( 'currency.code' ),
            'teaser'            => $event->get_argument( 'teaser' ),
            'overlay_content'   => $event->get_argument( 'overlay_content' ),
            'data'              => (array) $overlay_content_event->get_result(),
            'footer'            => LaterPay_Helper_Appearance::get_current_options( 'show_footer' ),
            'icons'             => $this->config->get_section( 'payment.icons' ),
            'notification_text' => __( 'I already bought this', 'laterpay' ),
            'identify_url'      => $client->get_identify_url( $back_url, $content_ids ),
            'submit_text'       => $submit_text,
            'is_preview'        => (int) $event->get_argument( 'is_preview' ),
        );

        $this->assign( 'overlay', $view_args );
        $html = $this->get_text_view( 'frontend/partials/widget/purchase-overlay' );

        $event->set_result( LaterPay_Helper_View::remove_extra_spaces( $html ) );
    }

    /**
     * Renders LaterPay purchase link
     *
     * @param LaterPay_Core_Event $event
     */
    public function on_purchase_link( LaterPay_Core_Event $event ) {
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        // get pricing data
        $currency      = $this->config->get( 'currency.code' );
        $price         = LaterPay_Helper_Pricing::get_post_price( $post->ID );
        $revenue_model = LaterPay_Helper_Pricing::get_post_revenue_model( $post->ID );

        // get purchase link
        $purchase_link = LaterPay_Helper_Post::get_laterpay_purchase_link( $post->ID );

        $view_args = array_merge(
            array(
                'post_id'       => $post->ID,
                'currency'      => $currency,
                'price'         => $price,
                'revenue_model' => $revenue_model,
                'link'          => $purchase_link,
                'attributes'    => array(),
            ),
            $event->get_arguments()
        );
        $this->assign( 'laterpay', $view_args );
        $html = $this->get_text_view( 'frontend/partials/widget/purchase-link' );

        $event->set_result( $html )
              ->set_arguments( $view_args );
    }

    /**
     * Collect content of benefits overlay.
     *
     * @param LaterPay_Core_Event $event
     * @var string                $revenue_model LaterPay revenue model applied to content
     *
     * @return void
     */
    public function on_explanatory_overlay_content( LaterPay_Core_Event $event ) {
        list( $revenue_model ) = $event->get_arguments() + array( 'sis' );
        // determine overlay title to show
        if ( $revenue_model === 'sis' ) {
            $overlay_title = __( 'Read Now', 'laterpay' );
        } else {
            $overlay_title = __( 'Read Now, Pay Later', 'laterpay' );
        }

        // get currency settings
        $currency = LaterPay_Helper_Config::get_currency_config();

        if ( $revenue_model === 'sis' ) {
            $overlay_benefits = array(
                array(
                    'title' => __( 'Buy Now', 'laterpay' ),
                    'text'  => __( 'Buy this post now with LaterPay and <br>pay with a payment method you trust.', 'laterpay' ),
                    'class' => 'lp_benefit--buy-now',
                ),
                array(
                    'title' => __( 'Read Immediately', 'laterpay' ),
                    'text'  => __( 'Immediately access your purchase. <br>You only buy this post. No subscription, no fees.', 'laterpay' ),
                    'class' => 'lp_benefit--use-immediately',
                ),
            );
        } else {
            $overlay_benefits = array(
                array(
                    'title' => __( 'Buy Now', 'laterpay' ),
                    'text'  => __( 'Just agree to pay later.<br> No upfront registration and payment.', 'laterpay' ),
                    'class' => 'lp_benefit--buy-now',
                ),
                array(
                    'title' => __( 'Read Immediately', 'laterpay' ),
                    'text'  => __( 'Access your purchase immediately.<br> You are only buying this article, not a subscription.', 'laterpay' ),
                    'class' => 'lp_benefit--use-immediately',
                ),
                array(
                    'title' => __( 'Pay Later', 'laterpay' ),
                    'text'  => sprintf( __( 'Buy with LaterPay until you reach a total of %s %s.<br> Only then do you have to register and pay.', 'laterpay' ), $currency['ppu_max'], $currency['code'] ),
                    'class' => 'lp_benefit--pay-later',
                ),
            );
        }

        $action_event = new LaterPay_Core_Event();
        $action_event->set_echo( false );
        laterpay_event_dispatcher()->dispatch( 'laterpay_purchase_button', $action_event );

        $overlay_content = array(
            'title' => $overlay_title,
            'benefits' => $overlay_benefits,
            'action_html_escaped' => (string) $action_event->get_result(),
        );

        $event->set_result( $overlay_content );
    }

    /**
     * Get article data
     *
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function on_purchase_overlay_content( LaterPay_Core_Event $event )
    {
        $data = $event->get_result();
        $post = $event->get_argument( 'post' );

        // Get the value of purchase type.
        $post_price_behaviour = LaterPay_Helper_Pricing::get_post_price_behaviour();
        $post_price_type_one  = ( 1 === $post_price_behaviour );

        if ( $post_price_type_one || LaterPay_Helper_Pricing::is_post_price_type_two_price_zero() ) {
            return;
        }

        $data['article'] = array(
            'title'        => $post->post_title,
            'price'        => LaterPay_Helper_View::format_number( LaterPay_Helper_Pricing::get_post_price( $post->ID ) ),
            'actual_price' => LaterPay_Helper_Pricing::get_post_price( $post->ID ),
            'revenue'      => LaterPay_Helper_Pricing::get_post_revenue_model( $post->ID ),
            'url'          => LaterPay_Helper_Post::get_laterpay_purchase_link( $post->ID ),
        );

        $event->set_result( $data );
    }

    /**
     * Check if user has access to the post
     *
     * @wp-hook laterpay_check_user_access
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function check_user_access( LaterPay_Core_Event $event ) {
        list( $has_access, $post_id ) = $event->get_arguments() + array( '', '' );
        $event->set_result( false );
        $event->set_echo( false );

        // get post
        if ( ! isset( $post_id ) ) {
            $post = get_post();
        } else {
            $post = get_post( $post_id );
        }

        if ( $post === null ) {
            $event->set_result( (bool) $has_access );
            return;
        }

        $user_has_unlimited_access = LaterPay_Helper_User::can( 'laterpay_has_full_access_to_content', $post );

        // user has unlimited access
        if ( $user_has_unlimited_access ) {
            $event->set_result( true );
            return;
        }

        // user has access to the post
        if ( LaterPay_Helper_Post::has_access_to_post( $post ) ) {
            $event->set_result( true );
            return;
        }

    }

    /**
     * Stops bubbling if content is not purchasable
     *
     * @param LaterPay_Core_Event $event
     */
    public function is_purchasable( LaterPay_Core_Event $event ) {
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        if ( ! LaterPay_Helper_Pricing::is_purchasable( $post->ID ) ) {
            $event->stop_propagation();
        }
    }

    /**
     * Save purchase in purchase history.
     *
     * @wp-hook template_redirect
     *
     * @return void
     */
    public function buy_post() {
	    $get_request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? filter_var( $_SERVER['REQUEST_METHOD'], FILTER_SANITIZE_STRING ) : ''; // phpcs:ignore
        $request_method = $get_request_method ? $get_request_method : '';
        $request        = new LaterPay_Core_Request();
        $buy            = $request->get_param( 'buy' );
        $pass_id        = $request->get_param( 'pass_id' );

        // return, if the request was not a redirect after a purchase
        if ( ! isset( $buy ) ) {
            return;
        }

        if ( LaterPay_Helper_Appearance::is_any_ga_tracking_enabled() ) {
            // Add cookie when the user is redirected back after a purchase.
            try {
                setcookie( 'lp_ga_purchased', 1, time() + 30, '/' );
            } catch ( Exception $e ) {
                unset( $e );
            }
        }

        $client_options  = LaterPay_Helper_Config::get_php_client_options();
        $laterpay_client = new LaterPay_Client(
            $client_options['cp_key'],
            $client_options['api_key'],
            $client_options['api_root'],
            $client_options['web_root'],
            $client_options['token_name']
        );

	    $request_url = isset( $_SERVER['REQUEST_URI'] ) ? filter_var( $_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL ) : ''; // phpcs:ignore
        $parts = wp_parse_url( $request_url ); // Todo: Add polyfill wp_parse_url for 3.5.2 WP support.

        parse_str( $parts['query'], $params );

        if ( LaterPay_Client_Signing::verify( $request->get_param( 'hmac' ), $laterpay_client->get_api_key(), $params, get_permalink(), $request_method ) ) {
            // check token
            $lptoken = $request->get_param( 'lptoken' );
            if ( $lptoken ) {
                $laterpay_client->set_token( $lptoken );
            }

            if ( $pass_id ) {
                $voucher = $request->get_param( 'voucher' );
                $pass_id = LaterPay_Helper_TimePass::get_untokenized_time_pass_id( $pass_id );

                // process vouchers
                if ( ! LaterPay_Helper_Voucher::check_voucher_code( $voucher ) ) {
                    if ( ! LaterPay_Helper_Voucher::check_voucher_code( $voucher, true ) ) {
                        // save the pre-generated gift code as valid voucher code now that the purchase is complete
                        $gift_cards = LaterPay_Helper_Voucher::get_time_pass_vouchers( $pass_id, true );
                        $gift_cards[ $voucher ] = array(
                            'price' => 0,
                            'title' => null,
                        );
                        LaterPay_Helper_Voucher::save_time_pass_vouchers( $pass_id, $gift_cards, true );
                        // set cookie to store information that gift card was purchased
                        setcookie(
                            'laterpay_purchased_gift_card',
                            $voucher . '|' . $pass_id,
                            time() + 30,
                            '/'
                        );
                        if ( laterpay_is_vip_go() ) {
                            setcookie( 'vip-go-cb', '1', time() + 30, '/' );
                        }
                    } else {
                        // update gift code statistics
                        LaterPay_Helper_Voucher::update_voucher_statistic( $pass_id, $voucher, true );
                    }
                } else {
                    // update voucher statistics
                    LaterPay_Helper_Voucher::update_voucher_statistic( $pass_id, $voucher );
                }
            } else {
                // prepare attachment URL for download
                $download_attached = $request->get_param( 'download_attached' );
                if ( $download_attached ) {
                    $post           = get_post( $download_attached );
                    $access         = LaterPay_Helper_Post::has_access_to_post( $post );
                    $attachment_url = LaterPay_Helper_File::get_encrypted_resource_url(
                        $download_attached,
                        wp_get_attachment_url( $download_attached ),
                        $access,
                        'attachment'
                    );
                    // set cookie to notify post that we need to start attachment download
                    setcookie(
                        'laterpay_download_attached',
                        $attachment_url,
                        time() + 60,
                        '/'
                    );
                }
            }

            unset( $params['post_id'],
                $params['pass_id'],
                $params['buy'],
                $params['lptoken'],
                $params['ts'],
                $params['hmac'] );

            $redirect_url = get_permalink( $request->get_param( 'post_id' ) );

            // Redirect back to Shortcode page if it was a Shortcode purchase.
            if ( ! empty( $params['parent_pid'] ) ) {
                $redirect_url = get_permalink( $request->get_param( 'parent_pid' ) );
                unset( $params['parent_pid'], $params['action'], $params['attachment_id'] );
            }

            // If permalink contains query string then build back url accordingly.
            $parsed_redirect_url = wp_parse_url( $redirect_url );

            if ( ! empty( $parsed_redirect_url['query'] ) ) {

                parse_str( $parsed_redirect_url['query'], $parsed_url_params );

                $url_args = wp_parse_args( $parsed_url_params );

                foreach( $url_args as $key => $value ) {
                    if ( isset( $params[$key] ) ) {
                        unset( $params[$key] );
                    }
                }
            }

            if ( ! empty( $params ) ) {
                $redirect_url = add_query_arg( LaterPay_Helper_Request::laterpay_encode_url_params( $params ), $redirect_url );
            }

            nocache_headers();

            wp_safe_redirect( $redirect_url );
            // exit script after redirect was set
            exit;
        }
    }

    /**
     * Set Laterpay token if it was provided after redirect and not processed by purchase functions.
     */
    public function set_token() {
	    $get_request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? filter_var( $_SERVER['REQUEST_METHOD'], FILTER_SANITIZE_STRING ) : ''; // phpcs:ignore
        $request_method = $get_request_method ? $get_request_method : '';
        $request           = new LaterPay_Core_Request();

        $client_options    = LaterPay_Helper_Config::get_php_client_options();
        $laterpay_client   = new LaterPay_Client(
            $client_options['cp_key'],
            $client_options['api_key'],
            $client_options['api_root'],
            $client_options['web_root'],
            $client_options['token_name']
        );

        // check token and set if necessary
        $lptoken = $request->get_param('lptoken');
        if ( $lptoken ) {
            if ( LaterPay_Client_Signing::verify( $request->get_param( 'hmac' ), $laterpay_client->get_api_key(), $request->get_data( 'get' ), get_permalink(), $request_method ) ) {
                // set token
                $laterpay_client->set_token($lptoken);
            }

            wp_safe_redirect( get_permalink( $request->get_param( 'post_id' ) ) );
            // exit script after redirect was set
            exit;
        }
    }

    /**
     * Modify the post content of paid posts.
     *
     * @wp-hook the_content
     *
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function modify_post_content( LaterPay_Core_Event $event ) {
        $content = $event->get_result();

        // button position
        $positioned_manually = (bool) get_option( 'laterpay_purchase_button_positioned_manually' );

        // add the purchase button as very first element of the content, if it is not positioned manually
        if ( $positioned_manually === false && get_option( 'laterpay_teaser_mode' ) !== '2' ) {
            $button_event = new LaterPay_Core_Event();
            $button_event->set_echo( false );
            laterpay_event_dispatcher()->dispatch( 'laterpay_purchase_button', $button_event );
            $content = $button_event->get_result() . $content;
        }

        $event->set_result( $content );
    }

    /**
     * @param LaterPay_Core_Event $event
     */
    public function purchase_button_position( LaterPay_Core_Event $event ) {
        $html = $event->get_result();
        // add the purchase button as very first element of the content, if it is not positioned manually
        $get_putchase_button_position = (bool) get_option( 'laterpay_purchase_button_positioned_manually' );
        if ( $get_putchase_button_position === false ) {
            $html = '<div class="lp_purchase-button-wrapper">' . $html . '</div>';
        }

        $event->set_result( $html );
    }

    /**
     * Stops event bubbling if the current post was already purchased and current user is not an admin
     *
     * @param LaterPay_Core_Event $event
     */
    public function on_view_purchased_post_as_visitor( LaterPay_Core_Event $event ) {
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        $preview_post_as_visitor = LaterPay_Helper_User::preview_post_as_visitor( $post );
        if ( $post instanceof WP_Post && LaterPay_Helper_Post::has_access_to_post( $post ) && ! $preview_post_as_visitor ) {
            $event->stop_propagation();
        }
    }
}

<?php

/**
 * LaterPay post controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Controller_Frontend_Post extends LaterPay_Controller_Base
{
    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'laterpay_post_content' => array(
                array( 'laterpay_on_plugin_is_working', 250 ),
                array( 'modify_post_content' ),
            ),
            'laterpay_posts' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'prefetch_post_access', 10 ),
            ),
            'laterpay_attachment_image_attributes' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'encrypt_image_source' ),
            ),
            'laterpay_attachment_get_url' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'encrypt_attachment_url' ),
            ),
            'laterpay_attachment_prepend' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'prepend_attachment' ),
            ),
            'laterpay_enqueue_scripts' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'add_frontend_stylesheets', 20 ),
                array( 'add_frontend_scripts' ),
            ),
            'laterpay_post_teaser' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'generate_post_teaser' ),
            ),
            'laterpay_feed_content' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'generate_feed_content' ),
            ),
            'laterpay_teaser_content_mode' => array(
                array( 'get_teaser_mode' ),
            ),
            'wp_ajax_laterpay_redeem_voucher_code' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'ajax_redeem_voucher_code' ),
            ),
            'wp_ajax_nopriv_laterpay_redeem_voucher_code' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'ajax_redeem_voucher_code' ),
            ),
            'wp_ajax_laterpay_load_files' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'ajax_load_files' ),
            ),
            'wp_ajax_nopriv_laterpay_load_files' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'ajax_load_files' ),
            ),
        );
    }

    /**
     * Ajax method to redeem voucher code.
     *
     * @wp-hook wp_ajax_laterpay_redeem_voucher_code, wp_ajax_nopriv_laterpay_redeem_voucher_code
     * @param LaterPay_Core_Event $event
     * @throws LaterPay_Core_Exception_InvalidIncomingData
     *
     * @return void
     */
    public function ajax_redeem_voucher_code( LaterPay_Core_Event $event ) {
        if ( ! isset( $_GET['action'] ) || sanitize_text_field( $_GET['action'] ) !== 'laterpay_redeem_voucher_code' ) { // phpcs:ignore
            throw new LaterPay_Core_Exception_InvalidIncomingData( 'action' );
        }

        if ( ! isset( $_GET['code'] ) ) { // phpcs:ignore
            throw new LaterPay_Core_Exception_InvalidIncomingData( 'code' );
        }

        if ( ! isset( $_GET['link'] ) ) { // phpcs:ignore
            throw new LaterPay_Core_Exception_InvalidIncomingData( 'link' );
        }

        // Check if voucher code exists and time pass or subscription is available for purchase.
        $is_gift     = true;
        $code        = sanitize_text_field( $_GET['code'] ); // phpcs:ignore
        $code_data   = LaterPay_Helper_Voucher::check_voucher_code( $code, $is_gift );
        if ( ! $code_data ) {
            $is_gift     = false;
            $can_be_used = true;
            $code_data   = LaterPay_Helper_Voucher::check_voucher_code( $code, $is_gift );
        } else {
            $can_be_used = LaterPay_Helper_Voucher::check_gift_code_usages_limit( $code );
        }

        // if gift code data exists and usage limit is not exceeded
        if ( $code_data && $can_be_used ) {
            // update gift code usage
            if ( $is_gift ) {
                LaterPay_Helper_Voucher::update_gift_code_usages( $code );
            }
            // Get new URL for this time pass / subscription.
            $pass_id    = $code_data['pass_id'];
            // prepare URL before use
            $data       = array(
                'voucher' => $code,
                'link'    => $is_gift ? home_url() : esc_url_raw( $_GET['link'] ), // phpcs:ignore
                'price'   => $code_data['price'],
            );

            $url_data = [];

            // Get new purchase URL.
            if ( 'time_pass' === $code_data['type'] ) {
                $url                 = LaterPay_Helper_TimePass::get_laterpay_purchase_link( $pass_id, $data );
                $url_data['pass_id'] = $pass_id;
                $url_data['type']    = 'time_pass';
            } else {
                $url                = LaterPay_Helper_Subscription::get_subscription_purchase_link( $pass_id, $data );
                $url_data['sub_id'] = $pass_id;
                $url_data['type']   = 'subscription';

            }

            if ( $url ) {

                $url_data['success'] = true;
                $url_data['price']   = LaterPay_Helper_View::format_number( $code_data['price'] );
                $url_data['url']     = $url;

                $event->set_result(
                    $url_data
                );
            }
            return;
        }

        $event->set_result(
            array(
                'success' => false,
            )
        );
    }

    /*
     * Encrypt image source to prevent direct access.
     *
     * @wp-hook wp_get_attachment_image_attributes
     *
     * @param LaterPay_Core_Event $event
     *
     * @var array        $attr Attributes for the image markup
     * @var WP_Post      $post Image attachment post
     * @var string|array $size Requested size
     *
     * @return mixed
     */
    public function encrypt_image_source( LaterPay_Core_Event $event ) {
        list( $attr, $post, $size ) = $event->get_arguments() + array( '', '', '' );
        $attr                           = $event->get_result();
        $caching_is_active              = (bool) $this->config->get( 'caching.compatible_mode' );
        $is_ajax_and_caching_is_active  = defined( 'DOING_AJAX' ) && DOING_AJAX && $caching_is_active;

        if ( is_admin() && ! $is_ajax_and_caching_is_active ) {
            return;
        }

        $is_purchasable = LaterPay_Helper_Pricing::is_purchasable( $post->ID );
        if ( $is_purchasable && $post->ID === get_the_ID() ) {
            $access         = LaterPay_Helper_Post::has_access_to_post( $post );
            $attr           = $event->get_result();
            $attr['src']    = LaterPay_Helper_File::get_encrypted_resource_url(
                $post->ID,
                $attr['src'],
                $access,
                'attachment'
            );
        }

        $event->set_result( $attr );
    }

    /**
     * Encrypt attachment URL to prevent direct access.
     *
     * @wp-hook wp_get_attachment_url
     *
     * @param LaterPay_Core_Event $event
     * @var string $url     URL for the given attachment
     * @var int    $post_id Attachment ID
     *
     * @return string
     */
    public function encrypt_attachment_url( LaterPay_Core_Event $event ) {
        list( $url, $post_id ) = $event->get_arguments() + array( '', '' );
        $caching_is_active              = (bool) $this->config->get( 'caching.compatible_mode' );
        $is_ajax_and_caching_is_active  = defined( 'DOING_AJAX' ) && DOING_AJAX && $caching_is_active;

        if ( is_admin() && ! $is_ajax_and_caching_is_active ) {
            return;
        }

        // get current post
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        if ( $post === null ) {
            return;
        }

        $url = $event->get_result();

        $is_purchasable = LaterPay_Helper_Pricing::is_purchasable( $post->ID );
        if ( $is_purchasable && $post->ID === $post_id ) {
            $access = LaterPay_Helper_Post::has_access_to_post( $post );

            // prevent from exec, if attachment is an image and user does not have access
            if ( ! $access && strpos( $post->post_mime_type, 'image' ) !== false ) {
                $event->set_result( '' );
                return;
            }

            // encrypt attachment URL
            $url = LaterPay_Helper_File::get_encrypted_resource_url(
                $post_id,
                $url,
                $access,
                'attachment'
            );
        }

        $event->set_result( $url );
    }

    /**
     * Prevent prepending of attachment before paid content.
     *
     * @wp-hook prepend_attachment
     *
     * @param LaterPay_Core_Event $event
     * @var string $attachment The attachment HTML output
     *
     * @return void
     */
    public function prepend_attachment( LaterPay_Core_Event $event ) {
        $attachment = $event->get_result();

        // get current post
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        if ( $post === null ) {
            return;
        }

        $is_purchasable          = LaterPay_Helper_Pricing::is_purchasable( $post->ID );
        $access                  = LaterPay_Helper_Post::has_access_to_post( $post );
        $preview_post_as_visitor = LaterPay_Helper_User::preview_post_as_visitor( $post );;
        if ( $is_purchasable && ! $access || $preview_post_as_visitor ) {
            $event->set_result( '' );
            return;
        }

        $caching_is_active              = (bool) $this->config->get( 'caching.compatible_mode' );
        $is_ajax_and_caching_is_active  = defined( 'DOING_AJAX' ) && DOING_AJAX && $caching_is_active;
        if ( $is_ajax_and_caching_is_active ) {
            $event->set_result( '' );
            return;
        }
        $event->set_result( $attachment );
    }

    /**
     * Prefetch the post access for posts in the loop.
     *
     * In archives or by using the WP_Query-Class, we can prefetch the access
     * for all posts in a single request instead of requesting every single post.
     *
     * @wp-hook the_posts
     *
     * @param LaterPay_Core_Event $event
     *
     * @return array $posts
     */
    public function prefetch_post_access( LaterPay_Core_Event $event ) {
        $posts = (array) $event->get_result();
        // prevent exec if admin
        if ( is_admin() ) {
            return;
        }

        $post_ids = array();
        // as posts can also be loaded by widgets (e.g. recent posts and popular posts), we loop through all posts
        // and bundle them in one API request to LaterPay, to avoid the overhead of multiple API requests
        foreach ( $posts as $post ) {
            // add a post_ID to the array of posts to be queried for access, if it's purchasable and not loaded already
            if ( ! array_key_exists( $post->ID, LaterPay_Helper_Post::get_access_state() ) && floatval( 0.00 ) !== LaterPay_Helper_Pricing::get_post_price( $post->ID ) ) {
                $post_ids[] = $post->ID;
            }
        }

        // check access for time passes
        $time_passes = LaterPay_Helper_TimePass::get_tokenized_time_pass_ids();

        foreach ( $time_passes as $time_pass ) {
            // add a tokenized time pass id to the array of posts to be queried for access, if it's not loaded already
            if ( ! array_key_exists( $time_pass, LaterPay_Helper_Post::get_access_state() ) ) {
                $post_ids[] = $time_pass;
            }
        }

        // check access for subscriptions
        $subscriptions = LaterPay_Helper_Subscription::get_tokenized_ids();

        foreach ( $subscriptions as $subscription ) {
            // add a tokenized subscription id to the array of posts to be queried for access, if it's not loaded already
            if ( ! array_key_exists( $subscription, LaterPay_Helper_Post::get_access_state() ) ) {
                $post_ids[] = $subscription;
            }
        }

        if ( empty( $post_ids ) ) {
            return;
        }

        $access_result = LaterPay_Helper_Request::laterpay_api_get_access( $post_ids );

        // Handle case 2, case 1 and 0 is handled by LaterPay_Helper_Request::laterpay_api_get_access().
        // Case 2 hides premium posts.

        if ( ! LaterPay_Helper_Request::isLpApiAvailability() ) {
            // Update result to hide all paid posts as API is down.
            $behavior = (int) get_option( 'laterpay_api_fallback_behavior', 0 );
            if ( 2 === $behavior ) {
                $result = array();
                foreach ( $posts as $post ) {
                    $paid = LaterPay_Helper_Pricing::get_post_price( $post->ID ) !== floatval( 0 );
                    if ( ! $paid ) {
                        $result[] = $post;
                    } else {
                        $key = array_search( $post->ID, $post_ids, true );
                        if ( $key ) {
                            unset( $post_ids[ $key ] );
                        }
                    }
                }

                $event->set_result( $result );
            }
        }

        if ( empty( $access_result ) || ! array_key_exists( 'articles', $access_result ) ) {
            return;
        }

        foreach ( $access_result['articles'] as $post_id => $state ) {
            LaterPay_Helper_Post::set_access_state( $post_id, (bool) $state['access'] );
        }
    }

    /**
     * Check, if the current page is a login page.
     *
     * @return boolean
     */
    public static function is_login_page() {
        return in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ), true );
    }

    /**
     * Check, if the current page is the cron page.
     *
     * @return boolean
     */
    public static function is_cron_page() {
        return in_array( $GLOBALS['pagenow'], array( 'wp-cron.php' ), true );
    }

    /**
     * Modify the post content of paid posts.
     *
     * Depending on the configuration, the content of paid posts is modified and several elements are added to the content:
     * If the user is an admin, a statistics pane with performance data for the current post is shown.
     * LaterPay purchase button is shown before the content.
     * Depending on the settings in the appearance tab, only the teaser content or the teaser content plus an excerpt of
     * the full content is returned for user who have not bought the post.
     * A LaterPay purchase link or a LaterPay purchase button is shown after the content.
     *
     * @wp-hook the_content
     *
     * @param LaterPay_Core_Event $event
     * @internal WP_Embed $wp_embed
     */
    public function modify_post_content( LaterPay_Core_Event $event ) {
        global $wp_embed;

        $content = $event->get_result();

        // Get the value of purchase type.
        $post_price_behaviour = (int) get_option( 'laterpay_post_price_behaviour' );

        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        if ( $post === null ) {
            $event->stop_propagation();
            return;
        }

        // check, if user has access to content (because he already bought it)
        $access = LaterPay_Helper_Post::has_access_to_post( $post );

        // caching and Ajax
        $caching_is_active = (bool) $this->config->get( 'caching.compatible_mode' );
        $is_ajax           = defined( 'DOING_AJAX' ) && DOING_AJAX;

        // check, if user has admin rights
        $user_has_unlimited_access = LaterPay_Helper_User::can( 'laterpay_has_full_access_to_content', $post );
        $preview_post_as_visitor   = LaterPay_Helper_User::preview_post_as_visitor( $post );

        // switch to 'admin' mode and load the correct content, if user can read post statistics
        if ($user_has_unlimited_access && ! $preview_post_as_visitor ) {
            $access = true;
        }

        // Global Price Value.
        $global_default_price = get_option( 'laterpay_global_price' );

        $post_price_type_one            = ( 1 === $post_price_behaviour );
        $post_price_type_two_price_zero = ( 2 === $post_price_behaviour && floatval( 0.00 ) === (float) $global_default_price );

        // Check if no individual post type is allowed.
        if ( $post_price_type_one || $post_price_type_two_price_zero ) {

            // Getting list of timepass by post id.
            $time_passes_list = LaterPay_Helper_TimePass::get_time_passes_list_by_post_id( $post->ID, null, true );

            // Getting list of subscription by post id.
            $subscriptions_list = LaterPay_Helper_Subscription::get_subscriptions_list_by_post_id( $post->ID, null, true );

            // Check if no timepass/subscription exists.
            if ( ( 0 === count( $time_passes_list ) ) && ( 0 === count( $subscriptions_list ) ) ) {

                // Give access to post.
                $access = true;
            }
        } elseif ( 0 === $post_price_behaviour ) {

            // @todo: Refactor Code.
            $post_price      = LaterPay_Helper_Pricing::get_post_price( $post->ID );
            $post_price_type = LaterPay_Helper_Pricing::get_post_price_type( $post->ID );
            $is_price_zero   = floatval( 0.00 ) === floatval(  $post_price );

            $is_global_price_type     = LaterPay_Helper_Pricing::TYPE_GLOBAL_DEFAULT_PRICE === $post_price_type;
            $is_individual_price_type = LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_PRICE === $post_price_type;
            $is_dynamic_price_type    = LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE === $post_price_type;
            $is_category_price_type   = LaterPay_Helper_Pricing::TYPE_CATEGORY_DEFAULT_PRICE === $post_price_type;

            $is_price_zero_and_type_not_global = ( $is_price_zero &&
                                                   ( $is_category_price_type || $is_dynamic_price_type ||
                                                     $is_individual_price_type ) );

            if ( ( empty( $post_price_type ) || $is_global_price_type ) || $is_price_zero_and_type_not_global ) {
                $access = true;
            }
        }

        // set necessary arguments
        $event->set_arguments(
            array(
                'post'       => $post,
                'access'     => $access,
                'is_cached'  => $caching_is_active,
                'is_ajax'    => $is_ajax,
                'is_preview' => $preview_post_as_visitor,
            )
        );

        // stop propagation
        if ( $user_has_unlimited_access && ! $preview_post_as_visitor ) {
            $event->stop_propagation();
            return;
        }

        // generate teaser
        $teaser_event = new LaterPay_Core_Event();
        $teaser_event->set_echo( false );
        laterpay_event_dispatcher()->dispatch( 'laterpay_post_teaser', $teaser_event );
        $teaser_content = $teaser_event->get_result();

        // generate overlay content
        $number_of_words = LaterPay_Helper_String::determine_number_of_words( $content );
        $overlay_content = LaterPay_Helper_String::truncate(  $content, $number_of_words , array( 'html' => true, 'words' => true ) );
        $event->set_argument( 'overlay_content', $overlay_content );

        // set teaser argument
        $event->set_argument( 'teaser', $teaser_content );
        $event->set_argument( 'content', $content );

        // get values for output states
        $teaser_mode_event = new LaterPay_Core_Event();
        $teaser_mode_event->set_echo( false );
        $teaser_mode_event->set_argument( 'post_id', $post->ID );
        laterpay_event_dispatcher()->dispatch( 'laterpay_teaser_content_mode', $teaser_mode_event );
        $teaser_mode = $teaser_mode_event->get_result();

        // return the teaser content on non-singular pages (archive, feed, tax, author, search, ...)
        if ( ! is_singular() && ! $is_ajax ) {
            // prepend hint to feed items that reading the full content requires purchasing the post
            if ( is_feed() ) {
                $feed_event = new LaterPay_Core_Event();
                $feed_event->set_echo( false );
                $feed_event->set_argument( 'post', $post );
                $feed_event->set_argument( 'teaser_content', $teaser_content );
                laterpay_event_dispatcher()->dispatch( 'laterpay_feed_content', $feed_event );
                $content = $feed_event->get_result();
            } else {
                $content = $teaser_content;
            }

            $event->set_result( $content );
            $event->stop_propagation();
            return;
        }

        if ( ! $access ) {
            // show proper teaser
            switch ($teaser_mode) {
                case '1':
                    // add excerpt of full content, covered by an overlay with a purchase button
                    $overlay_event = new LaterPay_Core_Event();
                    $overlay_event->set_echo( false );
                    $overlay_event->set_arguments( $event->get_arguments() );
                    laterpay_event_dispatcher()->dispatch( 'laterpay_explanatory_overlay', $overlay_event );
                    $content = $teaser_content . $overlay_event->get_result();
                    break;
                case '2':
                    // add excerpt of full content, covered by an overlay with a purchase button
                    $overlay_event = new LaterPay_Core_Event();
                    $overlay_event->set_echo( false );
                    $overlay_event->set_arguments( $event->get_arguments() );
                    laterpay_event_dispatcher()->dispatch( 'laterpay_purchase_overlay', $overlay_event );
                    $content = $teaser_content . $overlay_event->get_result();
                    break;
                default:
                    // add teaser content plus a purchase link after the teaser content
                    $link_event = new LaterPay_Core_Event();
                    $link_event->set_echo( false );
                    laterpay_event_dispatcher()->dispatch( 'laterpay_purchase_link', $link_event );
                    $content = $teaser_content . $link_event->get_result();
                    break;
            }
        } else {
            // encrypt files contained in premium posts
            $content = LaterPay_Helper_File::get_encrypted_content( $post->ID, $content, $access );
            $content = $wp_embed->autoembed( $content );
        }

        $event->set_result( $content );
    }

    /**
     * Load LaterPay stylesheets.
     *
     * @wp-hook wp_enqueue_scripts
     *
     * @return void
     */
    public function add_frontend_stylesheets() {

        wp_register_style(
            'laterpay-post-view',
            $this->config->css_url . 'laterpay-post-view.css',
            array(),
            $this->config->version
        );

        // always enqueue 'laterpay-post-view' to ensure that LaterPay shortcodes have styling
        wp_enqueue_style( 'laterpay-post-view' );

        // apply colors config
        LaterPay_Helper_View::apply_colors( 'laterpay-post-view' );

        // apply purchase overlay config
        LaterPay_Helper_Appearance::add_overlay_styles( 'laterpay-post-view' );
    }

    /**
     * Load LaterPay Javascript libraries.
     *
     * @wp-hook wp_enqueue_scripts
     *
     * @return void
     */
    public function add_frontend_scripts() {

        wp_register_script(
            'laterpay-post-view',
            $this->config->get( 'js_url' ) . 'laterpay-post-view.js',
            array( 'jquery' ),
            $this->config->get( 'version' ),
            true
        );
        $post = get_post();
        wp_localize_script(
            'laterpay-post-view',
            'lpVars',
            array(
                'ajaxUrl'               => admin_url( 'admin-ajax.php' ),
                'post_id'               => ! empty( $post ) ? $post->ID : false,
                'caching'               => (bool) $this->config->get( 'caching.compatible_mode' ),
                'i18n'                  => array(
                    'alert'             => __( 'In Live mode, your visitors would now see the LaterPay purchase dialog.', 'laterpay' ),
                    'validVoucher'      => __( 'Voucher code accepted.', 'laterpay' ),
                    'invalidVoucher'    => __( ' is not a valid voucher code!', 'laterpay' ),
                    'codeTooShort'      => __( 'Please enter a six-digit voucher code.', 'laterpay' ),
                    'generalAjaxError'  => __( 'An error occurred. Please try again.', 'laterpay' ),
                    'revenue'           => array(
                        'ppu'           => __( 'Buy Now, Pay Later', 'laterpay'),
                        'sis'           => __( 'Buy Now', 'laterpay' ),
                        'sub'           => __( 'Subscribe Now', 'laterpay' )
                    )
                ),
                'default_currency'      => $this->config->get( 'currency.code' ),
            )
        );

        wp_enqueue_script( 'laterpay-post-view' );
    }

    /**
     * @param LaterPay_Core_Event $event
     */
    public function generate_post_teaser( LaterPay_Core_Event $event ) {
        global $wp_embed;
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        if ( $post === null ) {
            return;
        }
        // get the teaser content
        $teaser_content = get_post_meta( $post->ID, 'laterpay_post_teaser', true );
        // generate teaser content, if it's empty
        if ( ! $teaser_content ) {
            $teaser_content = LaterPay_Helper_Post::add_teaser_to_the_post( $post );
        }

        // autoembed
        $teaser_content = $wp_embed->autoembed( $teaser_content );
        // add paragraphs to teaser content through wpautop
        $teaser_content = wpautop( $teaser_content );
        // get_the_content functionality for custom content
        $teaser_content = LaterPay_Helper_Post::get_the_content( $teaser_content, $post->ID );

        // assign all required vars to the view templates
        $view_args = array(
            'teaser_content' => $teaser_content,
        );

        $this->assign( 'laterpay', $view_args );

        if ( $event->is_echo_enabled() ) {
            $this->render( 'frontend/partials/post/teaser', null, true );
        } else {
            $html = LaterPay_Helper_View::remove_extra_spaces( $this->get_text_view( 'frontend/partials/post/teaser' ) );
            $event->set_result( $html );
        }
    }

    /**
     * @param LaterPay_Core_Event $event
     */
    public function generate_feed_content( LaterPay_Core_Event $event ) {
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }
        if ( $event->has_argument( 'teaser_content' ) ) {
            $teaser_content = $event->get_argument( 'teaser_content' );
        } else {
            $teaser_content = '';
        }
        if ( $event->has_argument( 'hint' ) ) {
            $feed_hint = $event->get_argument( 'feed_hint' );
        } else {
            $feed_hint = __( '&mdash; Visit the post to buy its full content for {price} {currency} &mdash; {teaser_content}', 'laterpay' );
        }
        $post_id = $post->ID;
        // get pricing data
        $currency   = $this->config->get( 'currency.code' );
        $price      = LaterPay_Helper_Pricing::get_post_price( $post_id );

        $html = $event->get_result();
        $html .= str_replace( array( '{price}', '{currency}', '{teaser_content}' ), array( $price, $currency, $teaser_content ), $feed_hint );
	    echo wp_kses_post( $html );
    }

    /**
     * Setup default teaser content preview mode
     *
     * @param LaterPay_Core_Event $event
     */
    public function get_teaser_mode( LaterPay_Core_Event $event ) {
        $event->set_result( get_option( 'laterpay_teaser_mode' ) );
    }

    /**
     * Ajax callback to load a file through a script to prevent direct access.
     *
     * @wp-hook wp_ajax_laterpay_load_files, wp_ajax_nopriv_laterpay_load_files
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function ajax_load_files( LaterPay_Core_Event $event ) {
        $file_helper = new LaterPay_Helper_File();
        $file_helper->load_file( $event );
    }
}

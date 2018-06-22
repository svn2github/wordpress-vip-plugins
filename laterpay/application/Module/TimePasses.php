<?php

/**
 * LaterPay TimePasses class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Module_TimePasses extends LaterPay_Core_View implements LaterPay_Core_Event_SubscriberInterface {

    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_shared_events()
     */
    public static function get_shared_events() {
        return array();
    }

    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'laterpay_post_content' => array(
                array( 'modify_post_content', 5 ),
            ),
            'laterpay_time_passes' => array(
                array( 'on_timepass_render', 20 ),
                array( 'the_time_passes_widget', 10 ),
            ),
            'laterpay_time_pass_render' => array(
                array( 'render_time_pass' ),
            ),
            'laterpay_shortcode_time_passes' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'render_time_passes_widget' ),
            ),
            'laterpay_explanatory_overlay_content' => array(
                array( 'on_explanatory_overlay_content', 5 ),
            ),
            'laterpay_purchase_overlay_content' => array(
                array( 'on_purchase_overlay_content', 8 ),
            ),
            'laterpay_purchase_button' => array(
                array( 'check_only_time_pass_purchases_allowed', 200 ),
            ),
            'laterpay_purchase_link' => array(
                array( 'check_only_time_pass_purchases_allowed', 200 ),
            ),
        );
    }

    /**
     * Check the permissions on saving the metaboxes.
     *
     * @wp-hook save_post
     *
     * @param int $post_id
     *
     * @return bool true|false
     */
    protected function has_permission( $post_id ) {
        // autosave -> do nothing
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return false;
        }

        // Ajax -> do nothing
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return false;
        }

        // no post found -> do nothing
        $post = get_post( $post_id );
        if ( $post === null ) {
            return false;
        }

        // current post type is not enabled for LaterPay -> do nothing
        $post_type = in_array( $post->post_type, $this->config->get( 'content.enabled_post_types' ), true );
        if ( ! $post_type ) {
            return false;
        }

        return true;
    }

    /**
     * Callback to render a widget with the available LaterPay time passes within the theme
     * that can be freely positioned.
     *
     * @wp-hook laterpay_time_passes
     *
     * @var string $introductory_text     additional text rendered at the top of the widget
     * @var string $call_to_action_text   additional text rendered after the time passes and before the voucher code input
     * @var int    $time_pass_id          id of one time pass to be rendered instead of all time passes
     *
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function the_time_passes_widget( LaterPay_Core_Event $event ) {
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        $is_homepage = is_front_page() && is_home();

        list( $introductory_text, $call_to_action_text, $time_pass_id ) = $event->get_arguments() + array( '', '', null );
        if ( empty( $introductory_text ) ) {
            $introductory_text = '';
        }
        if ( empty( $call_to_action_text ) ) {
            $call_to_action_text = '';
        }

        // get time passes list
        $time_passes_with_access = $this->get_time_passes_with_access();

        if ( isset( $time_pass_id ) ) {
            $check_time_pass_id = in_array( $time_pass_id, $time_passes_with_access, true );
            if ( $check_time_pass_id ) {
                return;
            }
            $time_passes_list = array( LaterPay_Helper_TimePass::get_time_pass_by_id( $time_pass_id, true ) );
        } else {
            // check, if we are on the homepage or on a post / page page
            $time_passes_list = LaterPay_Helper_TimePass::get_time_passes_list_by_post_id(
                ! $is_homepage && ! empty( $post )? $post->ID: null,
                $time_passes_with_access,
                true
            );
        }

        // get subscriptions
        $subscriptions = $event->get_argument( 'subscriptions' );

        // don't render the widget, if there are no time passes and no subsriptions
        if ( empty( $time_passes_list ) && empty( $subscriptions ) ) {
            return;
        }

        // check, if the time passes to be rendered have vouchers
        $has_vouchers = LaterPay_Helper_Voucher::passes_have_vouchers( $time_passes_list );

        $view_args = array(
            'passes_list'                    => $time_passes_list,
            'subscriptions'                  => $subscriptions,
            'has_vouchers'                   => $has_vouchers,
            'time_pass_introductory_text'    => $introductory_text,
            'time_pass_call_to_action_text'  => $call_to_action_text,
        );

        $this->assign( 'laterpay_widget', $view_args );
        $html = $event->get_result();
        $html .= LaterPay_Helper_View::remove_extra_spaces( $this->get_text_view( 'frontend/partials/widget/time-passes' ) );

        $event->set_result( $html );
    }

    /**
     * Execute before processing time pass widget
     *
     * @param LaterPay_Core_Event $event
     *
     * @return void;
     */
    public function on_timepass_render( LaterPay_Core_Event $event ) {
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        // disable if no post specified
        if ( $post === null ) {
            $event->stop_propagation();
            return;
        }

        // disable in purchase mode
        if ( get_option( 'laterpay_teaser_mode' ) === '2' ) {
            $event->stop_propagation();
            return;
        }

        $is_homepage                     = is_front_page() && is_home();
        $time_passes_positioned_manually = get_option( 'laterpay_time_passes_positioned_manually' );

        // prevent execution, if the current post is not the given post and we are not on the homepage,
        // or the action was called a second time,
        // or the post is free and we can't show the time pass widget on free posts
        if ( LaterPay_Helper_Pricing::is_purchasable() === false && ! $is_homepage ||
            did_action( 'laterpay_time_passes' ) > 1 ||
            LaterPay_Helper_Pricing::is_purchasable() === null
        ) {
            $event->stop_propagation();
            return;
        }

        // don't display widget on a search or multiposts page, if it is positioned automatically
        if ( ! is_singular() && ! $time_passes_positioned_manually ) {
            $event->stop_propagation();
            return;
        }
    }

    /**
     * Render time pass HTML.
     *
     * @param array $pass
     * @param bool $is_loop
     *
     * @return string
     */
    public function render_time_pass( $pass = array(), $is_loop = false ) {
        $defaults = array(
            'pass_id'     => 0,
            'title'       => LaterPay_Helper_TimePass::get_default_options( 'title' ),
            'description' => LaterPay_Helper_TimePass::get_description(),
            'price'       => LaterPay_Helper_TimePass::get_default_options( 'price' ),
            'url'         => '',
        );

        $laterpay_pass = array_merge( $defaults, $pass );
        if ( ! empty( $laterpay_pass['pass_id'] ) ) {
            $laterpay_pass['url'] = LaterPay_Helper_TimePass::get_laterpay_purchase_link( $laterpay_pass['pass_id'] );
        }

        $laterpay_pass['preview_post_as_visitor'] = LaterPay_Helper_User::preview_post_as_visitor( get_post() );

        $args = array(
            'standard_currency' => $this->config->get( 'currency.code' ),
        );
        $this->assign( 'laterpay',      $args );
        $this->assign( 'laterpay_pass', $laterpay_pass );

        if ( true === $is_loop ) {
            $this->render( 'backend/partials/time-pass', null, true );
        } else {
            $this->render( 'backend/partials/time-pass' );
        }
    }

    /**
     * Get time passes that have access to the current posts.
     *
     * @return array of time pass ids with access
     */
    protected function get_time_passes_with_access() {
        $access                     = LaterPay_Helper_Post::get_access_state();
        $time_passes_with_access    = array();

        // get time passes with access
        foreach ( $access as $access_key => $access_value ) {
            // if access was purchased
            if ( $access_value === true ) {
                $access_key_exploded = explode( '_', $access_key );
                // if this is time pass key - store time pass id
                if ( $access_key_exploded[0] === LaterPay_Helper_TimePass::PASS_TOKEN ) {
                    $time_passes_with_access[] = $access_key_exploded[1];
                }
            }
        }

        return $time_passes_with_access;
    }

    /**
     * Modify the post content of paid posts.
     *
     * @wp-hook the_content
     *
     * @param LaterPay_Core_Event $event
     *
     * @return string $content
     */
    public function modify_post_content( LaterPay_Core_Event $event ) {
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        if ( $post === null ) {
            return;
        }

        $timepasses_positioned_manually = get_option( 'laterpay_time_passes_positioned_manually' );
        if ( $timepasses_positioned_manually ) {
            return;
        }
        $content = $event->get_result();

        $only_time_passes_allowed = get_option( 'laterpay_only_time_pass_purchases_allowed' );

        if ( $only_time_passes_allowed ) {
            $content .= esc_html__( 'Buy a time pass to read the full content.', 'laterpay' );
        }
        $time_pass_event = new LaterPay_Core_Event();
        $time_pass_event->set_echo( false );
        laterpay_event_dispatcher()->dispatch( 'laterpay_time_passes', $time_pass_event );
        $content .= $time_pass_event->get_result();

        $event->set_result( $content );
    }

    /**
     * Render time passes widget from shortcode [laterpay_time_passes].
     *
     * The shortcode [laterpay_time_passes] accepts two optional parameters:
     * introductory_text     additional text rendered at the top of the widget
     * call_to_action_text   additional text rendered after the time passes and before the voucher code input
     *
     * You can find the ID of a time pass on the pricing page on the left side of the time pass (e.g. "Pass 3").
     * If no parameters are provided, the shortcode renders the time pass widget w/o parameters.
     *
     * Example:
     * [laterpay_time_passes]
     * or:
     * [laterpay_time_passes call_to_action_text="Get yours now!"]
     *
     * @var array $atts
     * @param LaterPay_Core_Event $event
     *
     * @return string
     */
    public function render_time_passes_widget( LaterPay_Core_Event $event ) {
        list( $atts ) = $event->get_arguments();

        $data = shortcode_atts( array(
            'id'                  => null,
            'introductory_text'   => '',
            'call_to_action_text' => '',
        ), $atts );

        if ( isset( $data['id'] ) && ! LaterPay_Helper_TimePass::get_time_pass_by_id( $data['id'], true ) ) {
            $error_message = LaterPay_Helper_View::get_error_message( __( 'Wrong time pass id or no time passes specified.', 'laterpay' ), $atts );
            $event->set_result( $error_message );
            $event->stop_propagation();
            return;
        }

        // introductory_text, call_to_action_text, time_pass_id
        $timepass_event = new LaterPay_Core_Event( array( $data['introductory_text'], $data['call_to_action_text'], $data['id'] ) );
        $timepass_event->set_echo( false );
        laterpay_event_dispatcher()->dispatch( 'laterpay_time_passes', $timepass_event );

        $html = $timepass_event->get_result();
        $event->set_result( $html );
    }

    /**
     * Collect content of benefits overlay.
     *
     * @param LaterPay_Core_Event $event
     * @var string                $revenue_model       LaterPay revenue model applied to content
     *
     * @return void
     */
    public function on_explanatory_overlay_content( LaterPay_Core_Event $event ) {
        $only_time_passes_allowed = get_option( 'laterpay_only_time_pass_purchases_allowed' );

        // determine overlay title to show
        if ( $only_time_passes_allowed ) {
            $overlay_title = __( 'Read Now', 'laterpay' );
            $overlay_benefits = array(
                array(
                    'title' => __( 'Buy Time Pass', 'laterpay' ),
                    'text'  => __( 'Buy a LaterPay time pass and pay with a payment method you trust.', 'laterpay' ),
                    'class' => 'lp_benefit--buy-now',
                ),
                array(
                    'title' => __( 'Read Immediately', 'laterpay' ),
                    'text'  => __( 'Immediately access your content. <br>A time pass is not a subscription, it expires automatically.', 'laterpay' ),
                    'class' => 'lp_benefit--use-immediately',
                ),
            );
            $overlay_content = array(
                'title'      => $overlay_title,
                'benefits'   => $overlay_benefits,
                'action_html_escaped'     => $this->get_text_view( 'frontend/partials/widget/time-passes-link' ),
            );
            $event->set_result( $overlay_content );
        }
    }

    /**
     * Get timepasses data
     *
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function on_purchase_overlay_content( LaterPay_Core_Event $event )
    {
        $data = $event->get_result();
        $post = $event->get_argument( 'post' );

        // default value
        $data['timepasses'] = array();

        $timepasses = LaterPay_Helper_TimePass::get_time_passes_list_by_post_id(
            $post->ID,
            null,
            true
        );

        // loop through timepasses
        foreach ($timepasses as $timepass) {
            $data['timepasses'][] = array(
                'id'          => (int) $timepass['pass_id'],
                'title'       => $timepass['title'],
                'description' => $timepass['description'],
                'price'       => LaterPay_Helper_View::format_number( $timepass['price'] ),
                'url'         => LaterPay_Helper_TimePass::get_laterpay_purchase_link( $timepass['pass_id'] ),
                'revenue'     => $timepass['revenue_model']
            );
        }

        $event->set_result( $data );
    }

    /**
     * Hide purchase information if only time-passes are allowed
     *
     * @param LaterPay_Core_Event $event
     */
    public function check_only_time_pass_purchases_allowed( LaterPay_Core_Event $event ) {
        $only_time_passes_allowed = get_option( 'laterpay_only_time_pass_purchases_allowed' );
        if ( $only_time_passes_allowed ) {
            $event->stop_propagation();
        }
    }
}

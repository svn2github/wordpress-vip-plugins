<?php

/**
 * LaterPay Subscriptions class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Module_Subscriptions extends LaterPay_Core_View implements LaterPay_Core_Event_SubscriberInterface {

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
            'laterpay_time_passes' => array(
                array( 'render_subscriptions_list', 15 ),
            ),
            'laterpay_purchase_overlay_content' => array(
                array( 'on_purchase_overlay_content', 6 ),
            ),
        );
    }

    /**
     * Callback to render a LaterPay subscriptions inside time pass widget.
     *
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function render_subscriptions_list( LaterPay_Core_Event $event ) {
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        // is homepage
        $is_homepage = is_front_page() && is_home();

        $view_args = array(
            'subscriptions' => LaterPay_Helper_Subscription::get_subscriptions_list_by_post_id(
                ! $is_homepage && ! empty( $post )? $post->ID: null,
                $this->get_purchased_subscriptions(),
                true
            ),
        );

        $this->assign( 'laterpay_sub', $view_args );

        // prepare subscriptions layout
        $subscriptions = LaterPay_Helper_View::remove_extra_spaces( $this->get_text_view( 'frontend/partials/widget/subscriptions' ) );

        $event->set_argument( 'subscriptions', $subscriptions );
    }

    /**
     * Render subscription HTML.
     *
     * @param array $pass
     *
     * @return string
     */
    public function render_subscription( $args = array(), $is_loop = false ) {
        $defaults = array(
            'id'          => 0,
            'title'       => LaterPay_Helper_Subscription::get_default_options( 'title' ),
            'description' => LaterPay_Helper_Subscription::get_description(),
            'price'       => LaterPay_Helper_Subscription::get_default_options( 'price' ),
            'url'         => '',
        );

        $args = array_merge( $defaults, $args );

        if ( ! empty( $args['id'] ) ) {
            $args['url'] = LaterPay_Helper_Subscription::get_subscription_purchase_link( $args['id'] );
        }

        $args['preview_post_as_visitor'] = LaterPay_Helper_User::preview_post_as_visitor( get_post() );

        $this->assign( 'laterpay_subscription', $args );
        $this->assign( 'laterpay',      array(
            'standard_currency' => $this->config->get( 'currency.code' ),
        ));

        if ( true === $is_loop ) {
            $this->render( 'backend/partials/subscription', null, true );
        } else {
            $this->render( 'backend/partials/subscription' );
        }


    }

    /**
     * Get subscriptions data
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
        $data['subscriptions'] = array();

        $subscriptions = LaterPay_Helper_Subscription::get_subscriptions_list_by_post_id(
            $post->ID,
            $this->get_purchased_subscriptions(),
            true
        );

        // loop through subscriptions
        foreach ($subscriptions as $subscription) {
            $data['subscriptions'][] = array(
                'title'       => $subscription['title'],
                'description' => $subscription['description'],
                'price'       => LaterPay_Helper_View::format_number( $subscription['price'] ),
                'url'         => LaterPay_Helper_Subscription::get_subscription_purchase_link( $subscription['id'] ),
                'revenue'     => 'sub'
            );
        }

        $event->set_result( $data );
    }

    /**
     * Get purchased subscriptions that have access to the current posts.
     *
     * @return array of time pass ids with access
     */
    protected function get_purchased_subscriptions() {
        $access                  = LaterPay_Helper_Post::get_access_state();
        $purchased_subscriptions = array();

        // get time passes with access
        foreach ( $access as $access_key => $access_value ) {
            // if access was granted
            if ( $access_value === true ) {
                $access_key_exploded = explode( '_', $access_key );
                // if this is time pass key - store time pass id
                if ( $access_key_exploded[0] === LaterPay_Helper_Subscription::TOKEN ) {
                    $purchased_subscriptions[] = $access_key_exploded[1];
                }
            }
        }

        return $purchased_subscriptions;
    }
}

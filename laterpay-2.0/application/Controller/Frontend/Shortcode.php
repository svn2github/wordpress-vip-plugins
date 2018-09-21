<?php

/**
 * LaterPay shortcode controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Controller_Frontend_Shortcode extends LaterPay_Controller_Base
{
    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'laterpay_shortcode_redeem_voucher' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'render_redeem_gift_code' ),
            ),
            'wp_ajax_laterpay_get_premium_shortcode_link' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'ajax_get_premium_shortcode_link' ),
            ),
            'wp_ajax_nopriv_laterpay_get_premium_shortcode_link' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'ajax_get_premium_shortcode_link' ),
            ),
        );
    }

    /**
     * Contains all settings for the plugin.
     *
     * @var LaterPay_Model_Config
     */
    protected $config;

    /**
     * Get premium shortcode link
     *
     * @hook wp_ajax_laterpay_get_premium_content_url, wp_ajax_nopriv_laterpay_get_premium_content_url
     * @param LaterPay_Core_Event $event
     * @throws LaterPay_Core_Exception_InvalidIncomingData
     * @throws LaterPay_Core_Exception_PostNotFound
     *
     * @return string
     */
    public function ajax_get_premium_shortcode_link( LaterPay_Core_Event $event ) {
        if ( ! isset( $_GET['action'] ) || sanitize_text_field( $_GET['action'] ) !== 'laterpay_get_premium_shortcode_link' ) { // phpcs:ignore
            throw new LaterPay_Core_Exception_InvalidIncomingData( 'action' );
        }

        if ( ! isset( $_GET['ids'] ) ) { // phpcs:ignore
            throw new LaterPay_Core_Exception_InvalidIncomingData( 'ids' );
        }

        if ( ! isset( $_GET['types'] ) ) { // phpcs:ignore
            throw new LaterPay_Core_Exception_InvalidIncomingData( 'types' );
        }

        if ( ! isset( $_GET['post_id'] ) ) { // phpcs:ignore
            throw new LaterPay_Core_Exception_InvalidIncomingData( 'post_id' );
        }

        $current_post_id = absint( $_GET['post_id'] ); // phpcs:ignore
        if ( ! get_post( $current_post_id ) ) {
            throw new LaterPay_Core_Exception_PostNotFound( $current_post_id );
        }

        $ids    = array_map( 'sanitize_text_field', $_GET['ids'] ); // phpcs:ignore
        $types  = array_map( 'sanitize_text_field', $_GET['types'] ); // phpcs:ignore
        $result = array();

        foreach ( $ids as $key => $id ) {
            $post = get_post( absint( $id ) );
            if ( ! $post ) {
                continue;
            }

            $is_purchasable = LaterPay_Helper_Pricing::is_purchasable( $id );
            $content_type   = $types[ $key ];
            $is_attachment  = $post->post_type === 'attachment';

            $access = LaterPay_Helper_Post::has_access_to_post( $post, $is_attachment, $current_post_id );

            if ( $access || ! $is_purchasable ) {
                // the user has already purchased the item
                switch ( $content_type ) {
                    case 'file':
                        $button_label = __( 'Download now', 'laterpay' );
                        break;

                    case 'video':
                    case 'gallery':
                        $button_label = __( 'Watch now', 'laterpay' );
                        break;

                    case 'music':
                    case 'audio':
                        $button_label = __( 'Listen now', 'laterpay' );
                        break;

                    default:
                        $button_label = __( 'Read now', 'laterpay' );
                        break;
                };

                if ( $is_attachment && $is_purchasable ) {
                    // render link to purchased attachment
                    $button_page_url = LaterPay_Helper_File::get_encrypted_resource_url(
                        $post->ID,
                        wp_get_attachment_url( $post->ID ),
                        $access,
                        'attachment'
                    );
                } else {
                    if ( $is_attachment ) {
                        // render link to attachment
                        $button_page_url = wp_get_attachment_url( $post->ID );
                    } else {
                        // render link to purchased post
                        $button_page_url = get_permalink( $post );
                    }
                }

                $html_button = '<a href="' . esc_url( $button_page_url ) . '" ' .
                    'class="lp_js_purchaseLink lp_purchase-button lp_purchase-button--shortcode" ' .
                    'rel="prefetch" ' .
                    'data-icon="b">' .
                    esc_html( $button_label ) .
                    '</a>';
            } else {
                // the user has not purchased the item yet
                $button_event = new LaterPay_Core_Event();
                $button_event->set_echo( false );
                $button_event->set_argument( 'post', $post );
                $button_event->set_argument( 'current_post', $current_post_id );
                $button_event->set_argument( 'attributes', array(
                    'class' => 'lp_js_doPurchase lp_purchase-button lp_purchase-link--shortcode',
                ) );
                laterpay_event_dispatcher()->dispatch( 'laterpay_purchase_button', $button_event );
                $html_button = $button_event->get_result();
                if ( empty( $html_button ) ) {
                    $view_args = array(
                        'url' => get_permalink( $post->ID ),
                    );
                    $this->assign( 'laterpay', $view_args );
                    $html_button = $this->get_text_view( 'frontend/partials/post/shortcode-purchase-link' );
                }
            }

            $result[ $id ] = $html_button;
        }

        $event->set_result(
            array(
                'success'   => true,
                'data'      => $result,
            )
        );
    }

    /**
     * Render a form to redeem a gift code for a time pass from shortcode [laterpay_redeem_voucher].
     * The shortcode renders an input and a button.
     * If the user enters his gift code and clicks the 'Redeem' button, a purchase dialog is opened,
     * where the user has to confirm the purchase of the associated time pass for a price of 0.00 Euro.
     * This step is done to ensure that this user accepts the LaterPay terms of use.
     * @param LaterPay_Core_Event $event
     *
     * @return string
     */
    public function render_redeem_gift_code( LaterPay_Core_Event $event ) {
        list( $atts) = $event->get_arguments() + array( array() );

        $data = shortcode_atts( array(
            'id' => null,
        ), $atts );

        // get a specific time pass, if an ID was provided; otherwise get all time passes
        if ( $data['id'] ) {
            $time_pass = LaterPay_Helper_TimePass::get_time_pass_by_id( $data['id'], true );
            if ( ! $time_pass ) {
                $error_message = LaterPay_Helper_View::get_error_message( __( 'Wrong time pass id.', 'laterpay' ), $atts );
                $event->set_result( $error_message );
                throw new LaterPay_Core_Exception( $error_message );
            }
        } else {
            $time_pass = array();
        }

        $view_args = array(
            'pass_data'               => $time_pass,
            'standard_currency'       => $this->config->get( 'currency.code' ),
            'preview_post_as_visitor' => LaterPay_Helper_User::preview_post_as_visitor( get_post() ),
        );
        $this->assign( 'laterpay', $view_args );

        $html = $this->get_text_view( 'frontend/partials/post/gift/gift-redeem' );

        $event->set_result( $html );
    }

    /**
     * Render gift card.
     *
     * @param array $gift_pass
     * @param bool  $show_redeem
     *
     * @return string
     */
    public function render_gift_pass( $gift_pass, $show_redeem = false, $is_loop = false ) {
        // check if gift_pass is not empty and is array
        if ( ! $gift_pass || ! is_array( $gift_pass ) ) {
            return '';
        }

        $view_args = array(
            'gift_pass'   => $gift_pass,
            'show_redeem' => $show_redeem,
        );
        $this->assign( 'laterpay_gift', $view_args );

        if ( true === $is_loop ) {
            $this->render( 'frontend/partials/post/gift/gift-pass', null, true );
        } else {
            $this->render( 'frontend/partials/post/gift/gift-pass' );
        }

    }

    /**
     * Render redeem gift card form.
     *
     * @return void
     */
    public function render_redeem_form() {
        $this->render( 'frontend/partials/post/gift/redeem-form' );
    }

    /**
     * Add voucher codes to time passes.
     *
     * @param array $time_passes list of time passes
     *
     * @return array
     */
    protected function add_free_codes_to_passes( $time_passes, $link = null ) {
        if ( is_array( $time_passes ) ) {
            foreach ( $time_passes as $id => $time_pass ) {
                // create URL with the generated voucher code
                $data = array(
                    'voucher' => LaterPay_Helper_Voucher::generate_voucher_code(),
                    'link'    => $link ? $link : get_permalink(),
                );

                $time_pass['url']   = LaterPay_Helper_TimePass::get_laterpay_purchase_link( $time_pass['pass_id'], $data, true );
                $time_passes[ $id ] = $time_pass;
            }
        }

        return $time_passes;
    }
}

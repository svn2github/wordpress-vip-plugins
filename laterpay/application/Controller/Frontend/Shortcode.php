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
            'laterpay_shortcode_premium_download' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'render_premium_download_box' ),
            ),
            'laterpay_shortcode_laterpay' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'render_premium_download_box' ),
            ),
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
     * Render a teaser box for selling additional (downloadable) content from the shortcode [laterpay_premium_download].
     * Shortcode [laterpay] is an alias for shortcode [laterpay_premium_download].
     *
     * The shortcode [laterpay_premium_download] accepts various parameters:
     * - target_post_title: the title of the page that contains the paid content
     * - target_post_id: the WordPress id of the page that contains the paid content
     * - heading_text: the text that should be displayed as heading in the teaser box;
     *   restricted to one line
     * - description_text: text that provides additional information on the paid content;
     *   restricted to a maximum of three lines
     * - content_type: choose between 'text', 'music', 'video', 'gallery', or 'file',
     *   to display the corresponding default teaser image provided by the plugin;
     *   can be overridden with a custom teaser image using the teaser_image_path attribute
     * - teaser_image_path: path to an image that should be used instead of the default LaterPay teaser image
     *
     * Basic example:
     * [laterpay_premium_download target_post_title="Event video footage"]
     * or:
     * [laterpay_premium_download target_post_id="734"]
     *
     * Advanced example:
     * [laterpay_premium_download target_post_id="734" heading_text="Video footage of concert"
     * description_text="Full HD video of the entire concept, including behind the scenes action."
     * teaser_image_path="/uploads/images/concert-video-still.jpg"]
     *
     * @var array $atts
     * @param LaterPay_Core_Event $event
     * @throws LaterPay_Core_Exception
     *
     */
    public function render_premium_download_box( LaterPay_Core_Event $event ) {

        list( $atts ) = $event->get_arguments() + array( array() );

        // provide default values for empty shortcode attributes
        $a = shortcode_atts( array(
            'target_post_id'    => '',
            'target_post_title' => '',
            'heading_text'      => esc_html__( 'Additional Premium Content', 'laterpay' ),
            'description_text'  => '',
            'content_type'      => '',
            'teaser_image_path' => '',
            // deprecated:
            'target_page_id'    => '',
            'target_page_title' => '',
        ), $atts );

        $error_reason = '';

        // get URL for target page
        $page = null;

        if ( $a['target_post_id'] !== '' ) {
            $page = get_post( absint( $a['target_post_id'] ) );
        }

        // target_post_id was provided, but didn't work
        if ( $page === null && $a['target_post_id'] !== '' ) {
            $error_reason = sprintf(
                esc_html__( 'We couldn\'t find a page for target_post_id="%s" on this site.', 'laterpay' ),
                absint( $a['target_post_id'] )
            );
        }

        if ( $page === null && $a['target_post_title'] !== '' ) {
            $page = LaterPay_Helper_Post::get_page_by_title( $a['target_post_title'], OBJECT, $this->config->get( 'content.enabled_post_types' ) );
        }

        // target_post_title was provided, but didn't work (no invalid target_post_id was provided)
        if ( $page === null && empty( $error_reason ) ) {
            $error_reason = sprintf(
                esc_html__( 'We couldn\'t find a page for target_post_title="%s" on this site.', 'laterpay' ),
                esc_html( $a['target_post_title'] )
            );
        }

        if ( $page === null ) {
            $error_message  = '<div class="lp_shortcode-error">';
            $error_message .= esc_html__( 'Problem with inserted shortcode:', 'laterpay' ) . '<br>';
            $error_message .= $error_reason;
            $error_message .= '</div>';

            $event->set_result( $error_message );
            throw new LaterPay_Core_Exception( $error_message );
        }

        $page_id = $page->ID;

        // don't render the shortcode, if the target page has a post type for which LaterPay is disabled
        if ( ! in_array( $page->post_type, $this->config->get( 'content.enabled_post_types' ), true ) ) {
            $error_reason   = esc_html__( 'LaterPay has been disabled for the post type of the target page.', 'laterpay' );
            $error_message  = '<div class="lp_shortcode-error">';
            $error_message .= esc_html__( 'Problem with inserted shortcode:', 'laterpay' ) . '<br>';
            $error_message .= $error_reason;
            $error_message .= '</div>';

            $event->set_result( $error_message );
            throw new LaterPay_Core_Exception( $error_message );
        }

        // check, if page has a custom post type
        $custom_post_types   = get_post_types( array( '_builtin' => false ) );
        $custom_types        = array_keys( $custom_post_types );
        $is_custom_post_type = ! empty( $custom_types ) && in_array( $page->post_type, $custom_types, true );

        // get the URL of the target page
        if ( $is_custom_post_type ) {
            // getting the permalink of a custom post type requires get_post_permalink instead of get_permalink
            $page_url = get_post_permalink( $page_id );
        } else {
            $page_url = get_permalink( $page_id );
        }

        $content_types = array( 'file', 'gallery', 'audio', 'video', 'text' );

        if ( empty( $a['content_type'] ) ) {
            // determine $content_type from MIME type of files attached to post
            $page_mime_type = get_post_mime_type( $page_id );
            switch ( $page_mime_type ) {
                case 'application/zip':
                case 'application/x-rar-compressed':
                case 'application/pdf':
                    $content_type = 'file';
                    break;
                case 'image/jpeg':
                case 'image/png':
                case 'image/gif':
                    $content_type = 'gallery';
                    break;
                case 'audio/vnd.wav':
                case 'audio/mpeg':
                case 'audio/mp4':
                case 'audio/ogg':
                case 'audio/aac':
                case 'audio/aacp':
                    $content_type = 'audio';
                    break;
                case 'video/mpeg':
                case 'video/mp4':
                case 'video/quicktime':
                    $content_type = 'video';
                    break;
                default:
                    $content_type = 'text';
            }
        } elseif ( in_array( $a['content_type'], $content_types, true ) ) {
            $content_type = $a['content_type'];
        } else {
            $content_type = 'text';
        }

        $image_path  = $a['teaser_image_path'];
        $heading     = $a['heading_text'];
        $description = $a['description_text'];

        // build the HTML for the teaser box
        if ( ! empty( $image_path ) ) {
            $html = '<div class="lp_js_premium-file-box lp_premium-file-box" '
                    . 'style="background-image:url(' . esc_url( $image_path ) . ')'
                    . '" data-post-id="' . esc_attr( $page_id )
                    . '" data-content-type="' . esc_attr( $content_type )
                    . '" data-page-url="' . esc_url ( $page_url )
                    . '">';
        } else {
            $html = '<div class="lp_js_premium-file-box lp_premium-file-box lp_is-' . esc_attr( $content_type )
                    . '" data-post-id="' . esc_attr( $page_id )
                    . '" data-content-type="' . esc_attr( $content_type )
                    . '" data-page-url="' . esc_url( $page_url )
                    . '">';
        }

        // create a premium box
        $html .= '<div class="lp_premium-file-box__details">';
        $html .= '<h3 class="lp_premium-file-box__title">' . esc_attr( $heading ) . '</h3>';

        if ( ! empty( $description ) ) {
            $html .= '<p class="lp_premium-file-box__text">' . esc_attr( $description ) . '</p>';
        }

        $html .= '</div>';
        $html .= '</div>';
        $event->set_result( $html );
    }

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

        if ( ! isset( $_GET['parent_pid'] ) ) { // phpcs:ignore
            throw new LaterPay_Core_Exception_InvalidIncomingData( 'parent_pid' );
        }

        $current_post_id = absint( $_GET['parent_pid'] ); // phpcs:ignore
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

<?php

/**
 * LaterPay preview mode controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Controller_Frontend_PreviewMode extends LaterPay_Controller_Base
{
    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'laterpay_post_footer' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'modify_footer' ),
            ),
            'wp_ajax_laterpay_preview_mode_visibility' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'ajax_toggle_visibility' ),
            ),
            'wp_ajax_laterpay_post_toggle_preview' => array(
                array( 'laterpay_on_plugin_is_working', 200 ),
                array( 'ajax_toggle_preview' ),
            ),
            'wp_ajax_laterpay_preview_mode_render' => array(
                array( 'ajax_render_tab_preview_mode', 200 ),
            ),
        );
    }

    /**
     * Check requirements for logging and rendering the post statistic pane via Ajax callback.
     *
     * @param WP_Post $post
     *
     * @return bool
     */
    protected function check_requirements( $post = null ) {

        if ( empty( $post ) ) {
            // check, if we're on a singular page
            if ( ! is_singular() ) {
                return false;
            }

            // check, if we have a post
            $post = get_post();
            if ( $post === null ) {
                return false;
            }
        }

        // don't collect statistics data, if the current post is not published
        if ( $post->post_status !== LaterPay_Helper_Pricing::STATUS_POST_PUBLISHED ) {
            return false;
        }

        // don't collect statistics data, if the current post_type is not an allowed post_type
        $allowed_post_types = $this->config->get( 'content.enabled_post_types' );
        if ( ! in_array( $post->post_type, $allowed_post_types, true ) ) {
            return false;
        }

        // don't collect statistics data, if the current post is not purchasable
        if ( ! LaterPay_Helper_Pricing::is_purchasable( $post->ID ) ) {
            return false;
        }

        return true;
    }

    /**
     * Callback to add the statistics placeholder to the footer.
     *
     * @wp-hook wp_footer
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function modify_footer( LaterPay_Core_Event $event ) {
        if ( ! $this->check_requirements() ) {
            return;
        }

        // don't add the preview pane placeholder to the footer, if the user is not logged in
        if ( ! LaterPay_Helper_User::can( 'laterpay_has_full_access_to_content', get_the_ID() ) ) {
            return;
        }

        echo '<div id="lp_js_previewModePlaceholder"></div>';
    }

    /**
     * Ajax callback to toggle the preview mode of the post.
     *
     * @wp-hook wp_ajax_laterpay_post_toggle_preview
     * @param LaterPay_Core_Event $event
     * @throws LaterPay_Core_Exception_FormValidation
     *
     * @return void
     */
    public function ajax_toggle_preview( LaterPay_Core_Event $event ) {
        $preview_form = new LaterPay_Form_PreviewModeForm( $_POST ); // phpcs:ignore

        if ( ! $preview_form->is_valid() ) {
            throw new LaterPay_Core_Exception_FormValidation( get_class( $preview_form ), $preview_form->get_errors() );
        }

        $error = array(
            'success' => false,
            'message' => __( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' ),
        );

        // check the admin referer
        if ( ! check_admin_referer( 'laterpay_form' ) ) {
            $error['code'] = 1;
            $event->set_result( $error );
            return;
        }

        $preview_post = $preview_form->get_field_value( 'preview_post' );

        if ( $preview_post === null ) {
            $error['code'] = 2;
            $event->set_result( $error );
            return;
        }

        // check, if we have a valid user
        $current_user = wp_get_current_user();
        if ( ! is_a( $current_user, 'WP_User' ) ) {
            $error['code'] = 3;
            $event->set_result( $error );
            return;
        }

        $result = LaterPay_Helper_User::update_user_meta(
            $current_user->ID,
            'laterpay_preview_post_as_visitor',
            $preview_post
        );

        if ( ! $result ) {
            $error['code'] = 5;
            $event->set_result( $error );
            return;
        }

        $event->set_result(
            array(
                'success' => true,
                'message' => __( 'Updated.', 'laterpay' ),
            )
        );
    }

    /**
     * Ajax callback to render the preview mode pane.
     *
     * @wp-hook wp_ajax_laterpay_post_preview_mode_render
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function ajax_render_tab_preview_mode( LaterPay_Core_Event $event ) {
        $preview_form = new LaterPay_Form_PreviewMode( $_GET ); //phpcs:ignore

        if ( ! $preview_form->is_valid() ) {
            $event->stop_propagation();
            return;
        }

        $post_id = $preview_form->get_field_value( 'post_id' );
        if ( ! LaterPay_Helper_User::can( 'laterpay_has_full_access_to_content', $post_id ) ) {
            $event->stop_propagation();
            return;
        }

        $post = get_post( $post_id );
        // assign variables
        $view_args = array(
            'diplay_preview_pane'       => LaterPay_Helper_User::display_preview_pane(),
            'hide_preview_mode_pane'    => LaterPay_Helper_User::preview_mode_pane_is_hidden(),
            'preview_post_as_visitor'   => (bool) LaterPay_Helper_User::preview_post_as_visitor( $post ),
        );
        $this->assign( 'laterpay', $view_args );

        $this->render( 'frontend/partials/post/select-preview-mode-tab' );
        die;
    }

    /**
     * Ajax callback to toggle the visibility of the statistics pane.
     *
     * @wp-hook wp_ajax_laterpay_post_statistic_visibility
     * @param LaterPay_Core_Event $event
     * @throws LaterPay_Core_Exception_FormValidation
     *
     * @return void
     */
    public function ajax_toggle_visibility( LaterPay_Core_Event $event ) {
        $preview_mode_visibility_form = new LaterPay_Form_PreviewModeVisibility( $_POST ); // phpcs:ignore

        if ( ! $preview_mode_visibility_form->is_valid() ) {
            throw new LaterPay_Core_Exception_FormValidation( get_class( $preview_mode_visibility_form ), $preview_mode_visibility_form->get_errors() );
        }

        $current_user = wp_get_current_user();
        $error = array(
            'success' => false,
            'message' => __( 'You don\'t have sufficient user capabilities to do this.', 'laterpay' ),
        );

        // check the admin referer
        if ( ! check_admin_referer( 'laterpay_form' ) ||
            ! is_a( $current_user, 'WP_User' ) ||
            ! LaterPay_Helper_User::can( 'laterpay_has_full_access_to_content', null, false )
        ) {
            $event->set_result( $error );
            return;
        }

        $result = LaterPay_Helper_User::update_user_meta(
            $current_user->ID,
            'laterpay_hide_preview_mode_pane',
            $preview_mode_visibility_form->get_field_value( 'hide_preview_mode_pane' )
        );

        if ( ! $result ) {
            $event->set_result( $error );
            return;
        }

        $event->set_result(
            array(
                'success' => true,
                'message' => __( 'Updated.', 'laterpay' ),
            )
        );
    }
}

<?php
/**
 * LaterPay Appearance class
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Module_Appearance extends LaterPay_Core_View implements LaterPay_Core_Event_SubscriberInterface {

    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_shared_events()
     */
    public static function get_shared_events() {
        return array(
            'laterpay_on_admin_view' => array(
                array( 'on_admin_view' ),
            ),
            'laterpay_on_plugin_is_active' => array(
                array( 'on_plugin_is_active' ),
            ),
            'laterpay_on_plugins_page_view' => array(
                array( 'on_plugins_page_view' ),
            ),
            'laterpay_on_plugin_is_working' => array(
                array( 'on_plugin_is_working' ),
            ),
            'laterpay_on_preview_post_as_admin' => array(
                array( 'on_preview_post_as_admin' ),
            ),
            'laterpay_on_visible_test_mode' => array(
                array( 'on_visible_test_mode' ),
            ),
            'laterpay_on_enabled_post_type' => array(
                array( 'on_enabled_post_type' ),
            ),
            'laterpay_on_ajax_user_can_activate_plugins' => array(
                array( 'on_ajax_user_can_activate_plugins' ),
            ),
        );
    }

    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'laterpay_post_content' => array(
                array( 'modify_post_content', 0 ),
                array( 'on_preview_post_as_admin', 100 ),
                array( 'on_enabled_post_type', 100 ),
            ),
            'laterpay_check_url_encrypt' => array(
                array( 'on_check_url_encrypt' ),
            ),
        );
    }

    /**
     * Stops event bubbling for admin with preview_post_as_visitor option disabled
     *
     * @param LaterPay_Core_Event $event
     */
    public function on_preview_post_as_admin( LaterPay_Core_Event $event ) {
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }

        $preview_post_as_visitor   = LaterPay_Helper_User::preview_post_as_visitor( $post );
        $user_has_unlimited_access = LaterPay_Helper_User::can( 'laterpay_has_full_access_to_content', $post );
        if ( $user_has_unlimited_access && ! $preview_post_as_visitor ) {
            $event->stop_propagation();
        }
        $event->add_argument( 'attributes', array( 'data-preview-post-as-visitor' => $preview_post_as_visitor ) );
        $event->set_argument( 'preview_post_as_visitor', $preview_post_as_visitor );
    }

    /**
     * Checks, if the current post is rendered in visible test mode
     *
     * @param LaterPay_Core_Event $event
     */
    public function on_visible_test_mode( LaterPay_Core_Event $event ) {
        $is_in_visible_test_mode = get_option( 'laterpay_is_in_visible_test_mode' )
                                   && ! $this->config->get( 'is_in_live_mode' );

        $event->set_argument( 'is_in_visible_test_mode', $is_in_visible_test_mode );
    }

    /**
     * Checks, if the current area is admin
     *
     * @param LaterPay_Core_Event $event
     */
    public function on_admin_view( LaterPay_Core_Event $event ) {
        if ( ! is_admin() ) {
            $event->stop_propagation();
        }
    }

    /**
     * Checks, if the current area is plugins manage page.
     *
     * @param LaterPay_Core_Event $event
     */
    public function on_plugins_page_view( LaterPay_Core_Event $event ) {
        if ( empty( $GLOBALS['pagenow'] ) || $GLOBALS['pagenow'] !== 'plugins.php' ) {
            $event->stop_propagation();
        }
    }

    /**
     * Checks, if the plugin is active.
     *
     * @param LaterPay_Core_Event $event
     */
    public function on_plugin_is_active( LaterPay_Core_Event $event ) {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }

        // Initialize for checking plugins status on wp.com VIP
        $plugin_active_on_classic = false;

        // Check whether we are on VIP Classic env.
        $is_classic = laterpay_check_is_vip_classic();

        // If we are on classic update status for the plugin.
        if ( $is_classic ) {

            // VIP loaded plugin names.
            global $vip_loaded_plugins;

            // Check if laterpay exists in the VIP loaded plugins.
            if ( in_array( 'plugins/laterpay', $vip_loaded_plugins, true ) ) {
                $plugin_active_on_classic = true;
            }

        }

        // continue, if plugin is active
        if ( ( ! is_plugin_active( laterpay_get_plugin_config()->get( 'plugin_base_name' ) ) && ! $is_classic ) || ( $is_classic && ! $plugin_active_on_classic ) ) {
            $event->stop_propagation();
        }
    }

    /**
     * Checks, if the plugin is working.
     *
     * @param LaterPay_Core_Event $event
     */
    public function on_plugin_is_working( LaterPay_Core_Event $event ) {
        // check, if the plugin is correctly configured and working
        if ( ! LaterPay_Helper_View::plugin_is_working() ) {
            $event->stop_propagation();
        }
    }

    /**
     * Stops bubbling if post is not in enabled post type list.
     *
     * @param LaterPay_Core_Event $event
     */
    public function on_enabled_post_type( LaterPay_Core_Event $event ) {
        if ( $event->has_argument( 'post' ) ) {
            $post = $event->get_argument( 'post' );
        } else {
            $post = get_post();
        }
        $is_enabled_post_type = in_array( $post->post_type, $this->config->get( 'content.enabled_post_types' ), true );
        if ( ! $is_enabled_post_type  ) {
            $event->stop_propagation();
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
        $content            = $event->get_result();
        $caching_is_active  = (bool) $this->config->get( 'caching.compatible_mode' );
        if ( $caching_is_active ) {
            // if caching is enabled, wrap the teaser in a div, so it can be replaced with the full content,
            // if the post is / has already been purchased
            $content = '<div id="lp_js_postContentPlaceholder">' . $content . '</div>';
        }

        if ( LaterPay_Helper_Appearance::is_any_ga_tracking_enabled() ) {
            $content .= '<input type="hidden" id="lp_ga_tracking" value="1" >';
        }

        $event->set_result( $content );
    }

    /**
     * Stops event if user can't activate plugins
     *
     * @param LaterPay_Core_Event $event
     */
    public function on_ajax_user_can_activate_plugins( LaterPay_Core_Event $event ) {
        // check for required capabilities to perform action
        if ( ! current_user_can( 'activate_plugins' ) ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'You don\'t have sufficient user capabilities to do this.', 'laterpay' )
                )
            );
            $event->stop_propagation();
        }
    }

    /**
     * @param  LaterPay_Core_Event $event
     * @return void
     */
    public function on_check_url_encrypt( LaterPay_Core_Event $event ) {
        $event->set_echo( false );
        $event->set_result( true );
    }
}

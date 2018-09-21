<?php

/**
 * LaterPay menu controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Controller_Admin_Base extends LaterPay_Controller_Base
{
    /**
     * Render the navigation for the plugin backend.
     *
     * @param string $file
     * @param string $view_dir view directory
     *
     */
    public function get_menu( $file = null, $view_dir = null ) {
        if ( empty( $file ) ) {
            $file = 'backend/partials/navigation';
        }

        $current_page_value = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );
        if ( null !== $current_page_value ) {
            $current_page = $current_page_value;
        } else {
            $current_page = LaterPay_Helper_View::$pluginPage;
        }
        $menu           = LaterPay_Helper_View::get_admin_menu();
        $plugin_page    = LaterPay_Helper_View::$pluginPage;

        $view_args      = array(
            'menu'         => $menu,
            'current_page' => $current_page,
            'plugin_page'  => $plugin_page,
        );

        $this->assign( 'laterpay', $view_args );
        $this->render( $file, $view_dir );
    }
}

<?php

/**
 * LaterPay appearance helper
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Helper_Appearance
{
    /**
     * Get default appearance options.
     *
     * @param null $key option name
     *
     * @return mixed option value | array of options
     */
    public static function get_default_options( $key = null ) {

        $defaults = array(
            'header_title'      => __( 'Read now, pay later', 'laterpay' ),
            'header_bg_color'   => '#585759',
            'main_bg_color'     => '#F4F3F4',
            'main_text_color'   => '#252221',
            'description_color' => '#69676A',
            'button_bg_color'   => '#00AAA2',
            'button_text_color' => '#FFFFFF',
            'link_main_color'   => '#01A99D',
            'link_hover_color'  => '#01766D',
            'show_footer'       => true,
            'footer_bg_color'   => '#EEEFEF',
        );

        if ( null !== $key && null !== $defaults[ $key ] ) {
                return $defaults[ $key ];
        }

        return $defaults;
    }

    /**
     * Get current appearance options.
     *
     * @param null $key
     *
     * @return mixed option value | array of options
     */
    public static function get_current_options( $key = null ) {

        $options = array(
            'header_title'      => get_option( 'laterpay_overlay_header_title', __('Read now, pay later', 'laterpay') ),
            'header_bg_color'   => get_option( 'laterpay_overlay_header_bg_color', '#585759' ),
            'main_bg_color'     => get_option( 'laterpay_overlay_main_bg_color', '#F4F3F4' ),
            'main_text_color'   => get_option( 'laterpay_overlay_main_text_color', '#252221' ),
            'description_color' => get_option( 'laterpay_overlay_description_color', '#69676A' ),
            'button_bg_color'   => get_option( 'laterpay_overlay_button_bg_color', '#00AAA2' ),
            'button_text_color' => get_option( 'laterpay_overlay_button_text_color', '#FFFFFF' ),
            'link_main_color'   => get_option( 'laterpay_overlay_link_main_color', '#01A99D' ),
            'link_hover_color'  => get_option( 'laterpay_overlay_link_hover_color', '#01766D' ),
            'show_footer'       => get_option( 'laterpay_overlay_show_footer', '1' ),
            'footer_bg_color'   => get_option( 'laterpay_overlay_footer_bg_color', '#EEEFEF' ),
        );

        if ( null !== $key && null !== $options[ $key ] ) {
                return $options[ $key ];
        }

        return $options;
    }

    /**
     * Add necessary inline styles for overlay
     *
     * @return void
     */
    public static function add_overlay_styles( $handle ) {

        $options = self::get_current_options();

        /**
         * Add CSS.
         */
         $custom_css = "
            .lp_purchase-overlay__header {
                background-color: " . esc_html( $options['header_bg_color'] ) . " !important;
            }
            .lp_purchase-overlay__form {
                background-color: " . esc_html( $options['main_bg_color'] ) . " !important;
            }
            .lp_purchase-overlay-option__title {
                color: " . esc_html( $options['main_text_color'] ) . " !important;
            }
            .lp_purchase-overlay-option__description {
                color: " . esc_html( $options['description_color'] ) . " !important;
            }
            .lp_purchase-overlay__notification {
                color: " . esc_html( $options['link_main_color'] ) . " !important;
            }
            .lp_purchase-overlay__notification a {
                color: " . esc_html( $options['link_main_color'] ) . " !important;
            }
            .lp_purchase-overlay__notification a:hover {
                color: " . esc_html( $options['link_hover_color'] ) . " !important;
            }
            .lp_purchase-overlay__submit {
                background-color: " . esc_html( $options['button_bg_color'] ) . " !important;
                color: " . esc_html( $options['button_text_color'] ) . " !important;
            }
            .lp_purchase-overlay__footer {
                background-color: " . esc_html( $options['footer_bg_color'] ) . " !important;
            }
        ";

        wp_add_inline_style( $handle, $custom_css );
    }

    /**
     * Check if any GA tracking is enabled.
     *
     * @return bool
     */
    public static function is_any_ga_tracking_enabled() {

        // Get current status of Google Analytics Settings.
        $lp_tracking_data      = get_option( 'laterpay_tracking_data' );
        $lp_user_tracking_data = get_option( 'laterpay_user_tracking_data' );

        // Check if LaterPay Tracking Setting is Enabled.
        $is_enabled_lp_tracking = ( ! empty( $lp_tracking_data['laterpay_ga_enabled_status'] ) &&
                                    1 === intval( $lp_tracking_data['laterpay_ga_enabled_status'] ) );

        // Check if Personal Tracking Setting is Enabled.
        $is_enabled_lp_user_tracking = ( ! empty( $lp_user_tracking_data['laterpay_ga_personal_enabled_status'] ) &&
                                         1 === intval( $lp_user_tracking_data['laterpay_ga_personal_enabled_status'] ) );

        $is_any_tracking_enabled = ( $is_enabled_lp_tracking || $is_enabled_lp_user_tracking );

        if ( $is_any_tracking_enabled ) {
            return true;
        }

        return false;

    }
}

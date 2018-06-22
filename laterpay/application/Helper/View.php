<?php

/**
 * LaterPay view helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Helper_View
{
    /**
     * @var string
     */
    public static $pluginPage = 'laterpay-pricing-tab';

    /**
     * Helper function to render a plugin backend navigation tab link.
     *
     * @param array $page array(
     *                      'url'   => String
     *                      'title' => String
     *                      'cap'   => String
     *                      'data'  => Array|String     // optional
     *                    )
     *
     * @return string $link
     */
    public static function get_admin_menu_link( $page ) {
        $query_args = array(
            'page' => $page['url'],
        );
        $href = admin_url( 'admin.php' );
        $href = add_query_arg( $query_args, $href );

        $data = '';
        if ( isset( $page['data'] ) ) {
            $data = wp_json_encode( $page['data'] );
        }

        /* translators: %1$s menu link, %2$s data attribute, %3$s menu title */
        $menu_link = sprintf( '<a href="%1$s" data="%2$s" class="lp_navigation-tabs__link">%3$s</a>', esc_url( $href ), esc_attr( $data ), esc_html( $page['title'] ) );
        return $menu_link;
    }

    /**
     * Get links to be rendered in the plugin backend navigation.
     *
     * @return array
     */
    public static function get_admin_menu() {
        $event = new LaterPay_Core_Event();
        $event->set_echo( false );
        laterpay_event_dispatcher()->dispatch( 'laterpay_admin_menu_data', $event );
        $menu = (array) $event->get_result();
        return $menu;
    }

    /**
     * Get date of next day.
     *
     * @param string $date
     *
     * @return string $nextDay
     */
    protected static function get_next_day( $date ) {
        $next_day = date( 'Y-m-d', mktime(
            date( 'H', strtotime( $date ) ),
            date( 'i', strtotime( $date ) ),
            date( 's', strtotime( $date ) ),
            date( 'm', strtotime( $date ) ),
            date( 'd', strtotime( $date ) ) + 1,
            date( 'Y', strtotime( $date ) )
        ) );

        return $next_day;
    }

    /**
     * Get date a given number of days prior to a given date.
     *
     * @param string $date
     * @param int    $ago number of days ago
     *
     * @return string $prior_date
     */
    protected static function get_date_days_ago( $date, $ago = 30 ) {
        $ago = absint( $ago );
        $prior_date = date( 'Y-m-d', mktime(
            date( 'H', strtotime( $date ) ),
            date( 'i', strtotime( $date ) ),
            date( 's', strtotime( $date ) ),
            date( 'm', strtotime( $date ) ),
            date( 'd', strtotime( $date ) ) - $ago,
            date( 'Y', strtotime( $date ) )
        ) );

        return $prior_date;
    }

    /**
     * Get the statistics data for the last 30 days as string, joined by a given delimiter.
     *
     * @param array  $statistic
     * @param string $type
     * @param string $delimiter
     *
     * @return string
     */
    public static function get_days_statistics_as_string( $statistic, $type = 'quantity', $delimiter = ',' ) {
        $today  = date( 'Y-m-d' );
        $date   = self::get_date_days_ago( date( $today ), 30 );

        $result = '';
        while ( $date <= $today ) {
            if ( $result !== '' ) {
                $result .= $delimiter;
            }
            if ( isset( $statistic[ $date ] ) ) {
                $result .= $statistic[ $date ][ $type ];
            } else {
                $result .= '0';
            }
            $date = self::get_next_day( $date );
        }

        return $result;
    }

    /**
     * Check, if plugin is fully functional.
     *
     * @return bool
     */
    public static function plugin_is_working() {
        $is_in_live_mode            = get_option( 'laterpay_plugin_is_in_live_mode' );
        $sandbox_api_key            = get_option( 'laterpay_sandbox_api_key' );
        $live_api_key               = get_option( 'laterpay_live_api_key' );
        $is_in_visible_test_mode    = get_option( 'laterpay_is_in_visible_test_mode' );
        if ( ! function_exists( 'wp_get_current_user' ) ) {
            include_once( ABSPATH . 'wp-includes/pluggable.php' );
        }

        // check, if plugin operates in live mode and Live API key exists
        if ( $is_in_live_mode && empty( $live_api_key ) ) {
            return false;
        }

        // check, if plugin is not in live mode and Sandbox API key exists
        if ( ! $is_in_live_mode && empty( $sandbox_api_key ) ) {
            return false;
        }

        // check, if plugin is not in live mode and is in visible test mode
        if ( ! $is_in_live_mode && $is_in_visible_test_mode ) {
            return true;
        }

        // check, if plugin is not in live mode and current user has sufficient capabilities
        if ( ! $is_in_live_mode ) {
            return false;
        }

        return true;
    }

    /**
     * Get current plugin mode.
     *
     * @return string $mode
     */
    public static function get_plugin_mode() {
        if ( get_option( 'laterpay_plugin_is_in_live_mode' ) ) {
            $mode = 'live';
        } else {
            $mode = 'test';
        }

        return $mode;
    }

    /**
     * Remove extra spaces from string.
     *
     * @param string $string
     *
     * @return string
     */
    public static function remove_extra_spaces( $string ) {
        $string = trim( preg_replace( '/>\s+</', '><', $string ) );
        $string = preg_replace( '/\n\s*\n/', '', $string );

        return $string;
    }

    /**
     * Format number based on its type.
     *
     * @param mixed   $number
     * @param bool    $is_monetary
     *
     * @return string $formatted
     */
    public static function format_number( $number, $is_monetary = true ) {
        // convert value to float if incorrect type passed
        $number = (float) $number ;

        if ( $is_monetary ) {
            // format value with 2 digits
            $formatted = number_format_i18n( $number, 2 );
        } else {
            // format count values
            if ( $number < 10000 ) {
                $formatted = number_format( $number );
            } else {
                // reduce values above 10,000 to thousands and format them with one digit
                $formatted = number_format( $number / 1000, 1 ) . __( 'k', 'laterpay' ); // k -> short for kilo (thousands)
            }
        }

        return $formatted;
    }

    /**
     * Number normalization
     *
     * @param $number
     *
     * @return float
     */
    public static function normalize( $number ) {
        global $wp_locale;

        $number = str_replace( $wp_locale->number_format['thousands_sep'], '', (string) $number );
        $number = str_replace( $wp_locale->number_format['decimal_point'], '.', $number );

        return (float) $number;
    }

    /**
     * Get error message for shortcode.
     *
     * @param string  $error_reason
     * @param array   $atts         shortcode attributes
     *
     * @return string $error_message
     */
    public static function get_error_message( $error_reason, $atts ) {
        $error_message  = '<div class="lp_shortcodeError">';
        $error_message .= esc_html__( 'Problem with inserted shortcode:', 'laterpay' ) . '<br>';
        $error_message .= wp_kses_post( $error_reason );
        $error_message .= '</div>';

        return $error_message;
    }

    /**
     * Apply custom laterpay colors.
     *
     * @param $handle string handler
     *
     * @return void
     */
    public static function apply_colors( $handle ) {
        $main_color  = get_option( 'laterpay_main_color' );
        $hover_color = get_option( 'laterpay_hover_color' );

        $custom_css = '';

        if ( $main_color ) {
            $custom_css .= "
                .lp_purchase-button, .lp_redeem-code__button, .lp_time-pass__front-side-link {
                    background-color: {$main_color} !important;
                }
                body .lp_time-pass__actions .lp_time-pass__terms {
                    color: {$main_color} !important;
                }
                .lp_bought_notification, .lp_purchase-link, .lp_redeem-code__hint {
                    color: {$main_color} !important;
                }
            ";
        }

        if ( $hover_color ) {
            $custom_css .= "
                .lp_purchase-button:hover {
                    background-color: {$hover_color} !important;
                }
                .lp_time-pass__front-side-link:hover {
                    background-color: {$hover_color} !important;
                }
                body .lp_time-pass__actions .lp_time-pass__terms:hover {
                    color: {$hover_color} !important;
                }
                .lp_bought_notification:hover, .lp_purchase-link:hover, .lp_redeem-code__hint:hover {
                    color: {$hover_color} !important;
                }
            ";
        }

        if ( $custom_css ) {
            wp_add_inline_style( $handle, $custom_css );
        }
    }
}

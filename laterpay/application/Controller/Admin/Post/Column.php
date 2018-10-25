<?php
/**
 * LaterPay post column controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */

class LaterPay_Controller_Admin_Post_Column extends LaterPay_Controller_Base
{
    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'laterpay_post_custom_column' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'add_columns_to_posts_table' ),
            ),
            'laterpay_post_custom_column_data' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'add_data_to_posts_table' ),
            ),
        );
    }

    /**
     * Add custom columns to posts table.
     *
     * @param LaterPay_Core_Event $event
     * @return array $extended_columns
     */
    public function add_columns_to_posts_table( LaterPay_Core_Event $event ) {
        list( $columns ) = $event->get_arguments() + array( array() );
        $extended_columns   = array();
        $insert_after       = 'title';

        foreach ( $columns as $key => $val ) {
            $extended_columns[ $key ] = $val;
            if ( ( string ) $key === $insert_after ) {
                $extended_columns['post_price']         = __( 'Price', 'laterpay' );
                $extended_columns['post_price_type']    = __( 'Price Type', 'laterpay' );
            }
        }
        $event->set_result( $extended_columns );
    }

    /**
     * Populate custom columns in posts table with data.
     *
     * @wp-hook manage_post_posts_custom_column
     *
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function add_data_to_posts_table( LaterPay_Core_Event $event ) {
        list( $column_name, $post_id ) = $event->get_arguments() + array( '', '' );

        switch ( $column_name ) {
            case 'post_price':
                $price              = (float) LaterPay_Helper_Pricing::get_post_price( $post_id, true );
                $localized_price    = LaterPay_Helper_View::format_number( $price );
                $currency           = $this->config->get( 'currency.code' );

                // Get current post price behaviour.
                $post_price_behaviour = LaterPay_Helper_Pricing::get_post_price_behaviour();

                // Getting list of timepass by post id.
                $time_passes_list = LaterPay_Helper_TimePass::get_time_passes_list_by_post_id( $post_id, null, true );

                // Getting list of subscription by post id.
                $subscriptions_list = LaterPay_Helper_Subscription::get_subscriptions_list_by_post_id( $post_id, null, true );

                // Global Price Value.
                $global_default_price = get_option( 'laterpay_global_price' );
                $is_global_zero       = ( floatval( 0.00 ) === (float) $global_default_price );

                $is_price_zero                        = floatval( 0.00 ) === floatval( $price );
                $post_price_type_one                  = ( 1 === $post_price_behaviour );
                $is_time_pass_subscription_count_zero = ( ( 0 === count( $time_passes_list ) ) && ( 0 === count( $subscriptions_list ) ) );
                $is_post_type_not_supported           = ( ! in_array( get_post_type( $post_id ), (array) get_option( 'laterpay_enabled_post_types' ), true ) );

                if ( 0 === $post_price_behaviour ) {
                    $post_price_type = LaterPay_Helper_Pricing::get_post_price_type( $post_id );

                    $is_global_price_type = LaterPay_Helper_Pricing::is_price_type_global( $post_price_type );

                    $is_price_zero_and_type_not_global = ( $is_price_zero && LaterPay_Helper_Pricing::is_price_type_not_global( $post_price_type ) );

                    if ( ( empty( $post_price_type ) || $is_global_price_type ) || ( $is_price_zero_and_type_not_global ) ) {
                        esc_html_e( 'FREE', 'laterpay' );
                    } else {
                        /* translators: %1$s post price, %2$s currency code */
                        printf( '<strong>%1$s</strong> <span>%2$s</span>', esc_html( $localized_price ), esc_html( $currency ) );
                    }
                } elseif ( $post_price_type_one ) {
                    echo '--';
                } elseif ( 2 === $post_price_behaviour ) {
                    if ( ( $is_global_zero && $is_time_pass_subscription_count_zero ) || $is_post_type_not_supported ) {
                        esc_html_e( 'FREE', 'laterpay' );
                    } else {
                        /* translators: %1$s post price, %2$s currency code */
                        printf( '<strong>%1$s</strong> <span>%2$s</span>', esc_html( $localized_price ), esc_html( $currency ) );
                    }
                }

                break;

            case 'post_price_type':
                $post_prices = get_post_meta( $post_id, 'laterpay_post_prices', true );
                if ( ! is_array( $post_prices ) ) {
                    $post_prices = array();
                }

                if ( array_key_exists( 'type', $post_prices ) ) {
                    // render the price type of the post, if it exists
                    switch ( $post_prices['type'] ) {
                        case LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_PRICE:
                            $revenue_model      = ( LaterPay_Helper_Pricing::get_post_revenue_model( $post_id ) === 'sis' )
                                                    ? __( 'Pay Now', 'laterpay' )
                                                    : __( 'Pay Later', 'laterpay' );
                            $post_price_type    = __( 'individual price', 'laterpay' ) . ' (' . $revenue_model . ')';
                            break;

                        case LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE:
                            $post_price_type = esc_html__( 'dynamic individual price', 'laterpay' );
                            break;

                        case LaterPay_Helper_Pricing::TYPE_CATEGORY_DEFAULT_PRICE:
                            $post_price_type = esc_html__( 'category default price', 'laterpay' );
                            break;

                        case LaterPay_Helper_Pricing::TYPE_GLOBAL_DEFAULT_PRICE:
                            $post_price_type = esc_html__( 'global default price', 'laterpay' );
                            break;

                        default:
                            $post_price_type = '&mdash;';
                    }

	                echo esc_html( $post_price_type );
                } else {
                    // label the post to use the global default price
	                esc_html_e( 'global default price', 'laterpay' );
                }
                break;
        }
    }
}

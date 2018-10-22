<?php

/**
 * LaterPay subscription helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Helper_Subscription
{

    const TOKEN = 'sub';

    /**
     * Get subscriptions default options.
     *
     * @param null $key option name
     *
     * @return mixed option value | array of options
     */
    public static function get_default_options( $key = null ) {
        $currency_config = LaterPay_Helper_Config::get_currency_config();

        $defaults = array(
            'id'              => '0',
            'duration'        => '1',
            'period'          => '3',
            'access_to'       => '0',
            'access_category' => '',
            'lp_id'           => '',
            'price'           => $currency_config['sis_min'],
            'title'           => __( '1 Month Subscription', 'laterpay' ),
            'description'     => __( '1 month access to all content on this website (cancellable anytime)', 'laterpay' ),
        );

        if ( isset( $key ) ) {
            if ( isset( $defaults[ $key ] ) ) {
                return $defaults[ $key ];
            }
        }

        return $defaults;
    }

    /**
     * Get short subscription description.
     *
     * @param  array  $subscription subscription data
     * @param  bool   $full_info need to display full info
     *
     * @return string short subscription description
     */
    public static function get_description( $subscription = array(), $full_info = false ) {
        $details  = array();
        $config   = laterpay_get_plugin_config();

        if ( ! $subscription ) {
            $subscription['duration']  = self::get_default_options( 'duration' );
            $subscription['period']    = self::get_default_options( 'period' );
            $subscription['access_to'] = self::get_default_options( 'access_to' );
        }

        $currency = $config->get( 'currency.code' );

        $details['duration'] = $subscription['duration'] . ' ' .
            LaterPay_Helper_TimePass::get_period_options( $subscription['period'], $subscription['duration'] > 1 );
        $details['access']   = __( 'access to', 'laterpay' ) . ' ' .
            LaterPay_Helper_TimePass::get_access_options( $subscription['access_to'] );

        // also display category, price, and revenue model, if full_info flag is used
        if ( $full_info ) {
            if ( $subscription['access_to'] > 0 ) {
                $category_id = $subscription['access_category'];
                $details['category'] = '"' . get_the_category_by_ID( $category_id ) . '"';
            }

            $details['price']    = __( 'for', 'laterpay' ) . ' ' .
                LaterPay_Helper_View::format_number( $subscription['price'] ) .
                ' ' . strtoupper( $currency );
            $details['cancellable']  = '(cancellable anytime)';
        }

        return implode( ' ', $details );
    }

    /**
     * Get subscriptions select options by type.
     *
     * @param string $type type of select
     *
     * @return string of options
     */
    public static function get_select_options( $type ) {
        $options_html  = '';
        $default_value = null;

        switch ( $type ) {
            case 'duration':
                $elements      = LaterPay_Helper_TimePass::get_duration_options();
                $default_value = self::get_default_options( 'duration' );
                break;

            case 'period':
                $elements      = LaterPay_Helper_TimePass::get_period_options();
                $default_value = self::get_default_options( 'period' );
                break;

            case 'access':
                $elements      = LaterPay_Helper_TimePass::get_access_options();
                $default_value = self::get_default_options( 'access_to' );
                break;

            default:
                return $options_html;
        }

        if ( $elements && is_array( $elements ) ) {
            foreach ( $elements as $id => $name ) {
                $options_html .= sprintf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $id ), esc_attr( selected( $default_value, $id, false ) ), esc_html( $name ) );
            }
        }

        return $options_html;
    }

    /**
     * Get tokenized subscription id.
     *
     * @param string $id untokenized subscription id
     *
     * @return array $result
     */
    public static function get_tokenized_id( $id ) {
        return sprintf( '%s_%s', self::TOKEN , $id );
    }

    /**
     * Get untokenized subscription id.
     *
     * @param string $tokenized_id tokenized subscription id
     *
     * @return string|null pass id
     */
    public static function get_untokenized_id( $tokenized_id ) {
        list( $prefix, $id ) = array_pad( explode( '_', $tokenized_id ), 2, null );
        if ( $prefix === self::TOKEN ) {
            return $id;
        }

        return null;
    }

    /**
     * Get all tokenized subscription ids.
     *
     * @param null $subscriptions array of subscriptions
     *
     * @return array $result
     */
    public static function get_tokenized_ids( $subscriptions = null ) {
        if ( ! isset( $subscriptions ) ) {
            $model        = LaterPay_Model_SubscriptionWP::get_instance();
            $subscriptions = $model->get_all_subscriptions();
        }

        $result = array();
        foreach ( $subscriptions as $subscription ) {
            $result[] = self::get_tokenized_id( $subscription['id'] );
        }

        return $result;
    }

    /**
     * Get all active subscriptions.
     *
     * @return array of subscriptions
     */
    public static function get_active_subscriptions() {
        $model = LaterPay_Model_SubscriptionWP::get_instance();
        return $model->get_active_subscriptions();
    }

    /**
     * Get subscription data by id.
     *
     * @param  int  $id
     * @param  bool $ignore_deleted ignore deleted time passes
     *
     * @return array
     */
    public static function get_subscription_by_id( $id = null, $ignore_deleted = false ) {
        $model = LaterPay_Model_SubscriptionWP::get_instance();

        if ( $id ) {
            return $model->get_subscription( (int) $id, $ignore_deleted );
        }

        return array();
    }

    /**
     * Get the LaterPay purchase link for a subscription
     *
     * @param int  $id               subscription id
     * @param null $data             additional data
     *
     * @return string url || empty string if something went wrong
     */
    public static function get_subscription_purchase_link( $id, $data = null ) {
        $subscription_model = LaterPay_Model_SubscriptionWP::get_instance();

        $subscription = $subscription_model->get_subscription( $id );
        if ( empty( $subscription ) ) {
            return '';
        }

        if ( ! isset( $data ) ) {
            $data = array();
        }

        $config   = laterpay_get_plugin_config();
        $currency = $config->get( 'currency.code' );
        $price    = isset( $data['price'] ) ? $data['price'] : $subscription['price'];
        $link     = isset( $data['link'] ) ? $data['link'] : get_permalink();

        $client_options = LaterPay_Helper_Config::get_php_client_options();
        $client = new LaterPay_Client(
            $client_options['cp_key'],
            $client_options['api_key'],
            $client_options['api_root'],
            $client_options['web_root'],
            $client_options['token_name']
        );

        // parameters for LaterPay purchase form
        $params = array(
            'article_id' => self::get_tokenized_id( $id ),
            'sub_id'     => self::get_tokenized_id( $id ),
            'pricing'    => $currency . ( $price * 100 ),
            'period'     => self::get_expiry_time( $subscription ),
            'url'        => $link,
            'title'      => $subscription['title'],
        );

        if ( isset( $data['voucher'] ) ) {
            $pass_title      = sprintf( '%1$s (%2$s %3$s)', $subscription['title'], 'Voucher Code ', $data['voucher'] );
            $params['title'] = $pass_title;
        }

        // Subscription purchase
        return $client->get_subscription_url( $params );
    }

    /**
     * Get all subscriptions for a given post.
     *
     * @param int    $post_id                    post id
     * @param null   $subscriptions_with_access  ids of subscriptions with access
     * @param bool   $ignore_deleted             ignore deleted subsciptions
     *
     * @return array $subscriptions
     */
    public static function get_subscriptions_list_by_post_id( $post_id, $subscriptions_with_access = null, $ignore_deleted = false ) {
        $model = LaterPay_Model_SubscriptionWP::get_instance();

        if ( $post_id !== null ) {
            // get all post categories
            $post_categories = get_the_category( $post_id );
            $post_category_ids = null;

            // get category ids
            foreach ( $post_categories as $category ) {
                $post_category_ids[] = $category->term_id;
                // get category parents and include them in the ids array as well
                $parent_id = get_category( $category->term_id )->parent;
                while ( $parent_id ) {
                    $post_category_ids[] = $parent_id;
                    $parent_id = get_category( $parent_id )->parent;
                }
            }

            // get list of subscriptions that cover this post
            $subscriptions = $model->get_subscriptions_by_category_ids( $post_category_ids, false, false, true );
        } else {
            $subscriptions = $model->get_subscriptions_by_category_ids();
        }

        // correct result, if we have purchased subscriptions
        if ( ! empty( $subscriptions_with_access ) ) {
            $subscriptions_with_access = array_map( 'absint', $subscriptions_with_access );
            // check, if user has access to the current post with subscription
            $has_access = false;
            foreach ( $subscriptions as $subscription ) {
                if ( in_array( absint( $subscription['id'] ), $subscriptions_with_access, true ) ) {
                    $has_access = true;
                    break;
                }
            }

            if ( $has_access ) {
                // categories with access (type 2)
                $covered_categories  = array(
                    'included' => array(),
                    'excluded' => null,
                );
                // excluded categories (type 1)
                $excluded_categories = array();

                // go through subscriptions with access and find covered and excluded categories
                foreach ( $subscriptions_with_access as $subscription_with_access_id ) {
                    $subscription_with_access_data = $model->get_subscription( $subscription_with_access_id );
                    $access_category            = $subscription_with_access_data['access_category'];
                    $access_type                = $subscription_with_access_data['access_to'];
                    if ( $access_type === 2 ) {
                        $covered_categories['included'][] = $access_category;
                    } else if ( $access_type === 1 ) {
                        $excluded_categories[] = $access_category;
                    } else {
                        return array();
                    }
                }

                // case: full access, except for specific categories
                if ( $excluded_categories ) {
                    foreach ( $excluded_categories as $excluded_category_id ) {
                        // search for excluded category in covered categories
                        $has_covered_category = array_search( $excluded_category_id, $covered_categories['included'], true );
                        if ( $has_covered_category !== false ) {
                            return array();
                        } else {
                            //  if more than 1 subscription with excluded category was purchased,
                            //  and if its values are not matched, then all categories are covered
                            if ( isset( $covered_categories['excluded'] ) && ( $covered_categories['excluded'] !== $excluded_category_id ) ) {
                                return array();
                            }
                            // store the only category not covered
                            $covered_categories['excluded'] = $excluded_category_id;
                        }
                    }
                }

                // get data without covered categories or only excluded
                if ( isset( $covered_categories['excluded'] ) ) {
                    $subscriptions = $model->get_subscriptions_by_category_ids( array( $covered_categories['excluded'] ) );
                } else {
                    $subscriptions = $model->get_subscriptions_by_category_ids( $covered_categories['included'], true );
                }
            }
        }

        if ( $ignore_deleted ) {
            // filter deleted subscriptions
            foreach ( $subscriptions as $key => $subscription ) {
                if ( $subscription['is_deleted'] ) {
                    unset( $subscriptions[ $key ] );
                }
            }
        }

        return $subscriptions;
    }

    /**
     * Get subscription expiry time.
     *
     * @param array $subscription
     *
     * @return $time expiry time
     */
    protected static function get_expiry_time( $subscription ) {
        switch ( $subscription['period'] ) {
            // hours
            case 0:
                $time = $subscription['duration'] * 60 * 60;
                break;

            // days
            case 1:
                $time = $subscription['duration'] * 60 * 60 * 24;
                break;

            // weeks
            case 2:
                $time = $subscription['duration'] * 60 * 60 * 24 * 7;
                break;

            // months
            case 3:
                $time = $subscription['duration'] * 60 * 60 * 24 * 31;
                break;

            // years
            case 4:
                $time = $subscription['duration'] * 60 * 60 * 24 * 365;
                break;

            default :
                $time = 0;
        }

        return $time;
    }

    /**
     * Get count of existing subscriptions.
     *
     * @param bool $ignore_deleted ignore count of deleted pass.
     *
     * @return int count of subscriptions
     */
    public static function get_subscriptions_count( $ignore_deleted = false ) {
        $model = LaterPay_Model_SubscriptionWP::get_instance();

        return $model->get_subscriptions_count( $ignore_deleted );
    }

}

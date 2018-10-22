<?php

/**
 * LaterPay vouchers helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Helper_Voucher
{
    /**
     * @const int Default length of voucher code.
     */
    const VOUCHER_CODE_LENGTH  = 6;

    /**
     * @const string Chars allowed in voucher code.
     */
    const VOUCHER_CHARS        = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * @const string Name of option to update if voucher is a gift.
     */
    const GIFT_CODES_OPTION    = 'laterpay_gift_codes';

    /**
     * @const string Name of statistic option to update if voucher is a gift.
     */
    const GIFT_STAT_OPTION     = 'laterpay_gift_statistic';

    /**
     * @const string Name of option to update if voucher is NOT a gift.
     */
    const VOUCHER_CODES_OPTION = 'laterpay_voucher_codes';

    /**
     * @const string Name of option to update for subscription voucher.
     */
    const SUBSCRIPTION_VOUCHER_CODES_OPTION = 'laterpay_subscription_voucher_codes';

    /**
     * @const string Name of statistic option to update if voucher is NOT a gift.
     */
    const VOUCHER_STAT_OPTION  = 'laterpay_voucher_statistic';

    /**
     * Generate random voucher code.
     *
     * @param int $length voucher code length
     *
     * @return string voucher code
     */
    public static function generate_voucher_code( $length = self::VOUCHER_CODE_LENGTH ) {
        $voucher_code  = '';
        $possibleChars = self::VOUCHER_CHARS;

        for ( $i = 0; $i < $length; $i++ ) {
            mt_srand();
            $rand = mt_rand( 0, strlen( $possibleChars ) - 1 );
            $voucher_code .= substr( $possibleChars, $rand, 1 );
        }

        return $voucher_code;
    }

    /**
     * Save vouchers for current time pass.
     *
     * @param int   $pass_id Time Pass Id.
     * @param array $data    Time Pass Voucher Data.
     * @param bool  $is_gift Is Voucher a Gift.
     *
     * @return void
     */
    public static function save_time_pass_vouchers( $pass_id, $data, $is_gift = false ) {
        $vouchers    = self::get_all_time_pass_vouchers( $is_gift );
        $option_name = $is_gift ? self::GIFT_CODES_OPTION : self::VOUCHER_CODES_OPTION;

        if ( ! $data ) {
            unset( $vouchers[ $pass_id ] );
        } else if ( is_array( $data ) ) {
            $vouchers[ $pass_id ] = $data;
        }

        // save new voucher data
        update_option( $option_name, $vouchers );
        // actualize voucher statistic
        self::actualize_voucher_statistic( $is_gift );
    }

    /**
     * Save vouchers for current subscription.
     *
     * @param int   $sub_id Subscription Id.
     * @param array $data   Subscription Voucher Data.
     *
     * @return void
     */
    public static function save_subscription_vouchers( $sub_id, $data ) {
        $vouchers    = self::get_all_subscription_vouchers();
        $option_name = self::SUBSCRIPTION_VOUCHER_CODES_OPTION;

        if ( ! $data ) {
            unset( $vouchers[ $sub_id ] );
        } else if ( is_array( $data ) ) {
            $vouchers[ $sub_id ] = $data;
        }

        // save new voucher data
        update_option( $option_name, $vouchers );
    }

    /**
     * Get voucher codes of current time pass.
     *
     * @param int  $pass_id Time Pass Id.
     * @param bool $is_gift Is Voucher a Gift.
     *
     * @return array
     */
    public static function get_time_pass_vouchers( $pass_id, $is_gift = false ) {
        $vouchers = self::get_all_time_pass_vouchers( $is_gift );
        if ( ! isset( $vouchers[ $pass_id ] ) ) {
            return array();
        }

        return $vouchers[ $pass_id ];
    }

    /**
     * Get voucher codes of subscription.
     *
     * @param int $sub_id Subscription Id.
     *
     * @return array
     */
    public static function get_subscription_vouchers( $sub_id ) {
        $vouchers = self::get_all_subscription_vouchers();
        if ( ! isset( $vouchers[ $sub_id ] ) ) {
            return array();
        }

        return $vouchers[ $sub_id ];
    }

    /**
     * Get all time pass vouchers.
     *
     * @param bool $is_gift Is Voucher a Gift.
     *
     * @return array of vouchers
     */
    public static function get_all_time_pass_vouchers( $is_gift = false ) {
        $option_name = $is_gift ? self::GIFT_CODES_OPTION : self::VOUCHER_CODES_OPTION;

        $vouchers    = get_option( $option_name );
        if ( ! $vouchers || ! is_array( $vouchers ) ) {
            update_option( $option_name, '' );
            $vouchers = array();
        }

        // format prices
        foreach ( $vouchers as $time_pass_id => $time_pass_voucher ) {
            foreach ( $time_pass_voucher as $code => $data ) {
                $vouchers[ $time_pass_id ][ $code ]['price'] = LaterPay_Helper_View::format_number( $data['price'] );
            }
        }

        return $vouchers;
    }

    /**
     * Get all subscription vouchers.
     *
     * @return array of vouchers
     */
    public static function get_all_subscription_vouchers() {
        $option_name = self::SUBSCRIPTION_VOUCHER_CODES_OPTION;

        $vouchers = get_option( $option_name );
        if ( ! $vouchers || ! is_array( $vouchers ) ) {
            update_option( $option_name, '' );
            $vouchers = array();
        }

        // format prices
        foreach ( $vouchers as $subscription_id => $subscription_voucher ) {
            foreach ( $subscription_voucher as $code => $data ) {
                $vouchers[ $subscription_id ][ $code ]['price'] = LaterPay_Helper_View::format_number( $data['price'] );
            }
        }

        return $vouchers;
    }

    /**
     * Delete time pass voucher code.
     *
     * @param int    $pass_id Time Pass Id.
     * @param string $code    Time Pass Voucher Data.
     * @param bool   $is_gift Is Voucher a Gift.
     *
     * @return void
     */
    public static function delete_time_pass_voucher_code( $pass_id, $code = null, $is_gift = false ) {
        $pass_vouchers = self::get_time_pass_vouchers( $pass_id, $is_gift );
        if ( $pass_vouchers && is_array( $pass_vouchers ) ) {
            if ( $code ) {
                unset( $pass_vouchers[ $code ] );
            } else {
                $pass_vouchers = array();
            }
        }

        self::save_time_pass_vouchers( $pass_id, $pass_vouchers, $is_gift );
    }

    /**
     * Delete subscription voucher code.
     *
     * @param int    $sub_id Subscription Id.
     * @param string $code   Subscription Voucher Data.
     *
     * @return void
     */
    public static function delete_subscription_voucher_code( $sub_id, $code = null ) {
        $sub_vouchers = self::get_subscription_vouchers( $sub_id );
        if ( $sub_vouchers && is_array( $sub_vouchers ) ) {
            if ( $code ) {
                unset( $sub_vouchers[ $code ] );
            } else {
                $sub_vouchers = array();
            }
        }

        self::save_subscription_vouchers( $sub_id, $sub_vouchers );
    }

    /**
     * Check, if voucher code exists and return pass_id and new price.
     *
     * @param string $code
     * @param bool   $is_gift
     *
     * @return mixed $voucher_data
     */
    public static function check_voucher_code( $code, $is_gift = false ) {
        $all_passes              = [];
        $time_pass_vouchers      = self::get_all_time_pass_vouchers( $is_gift );
        $all_passes['time_pass'] = $time_pass_vouchers;

        if ( ! $is_gift ) {
            $subscription_vouchers      = self::get_all_subscription_vouchers();
            $all_passes['subscription'] = $subscription_vouchers;
        }

        foreach ( $all_passes as $key => $vouchers ){
            // search code
            foreach ( $vouchers as $pass_id => $pass_vouchers ) {
                foreach ( $pass_vouchers as $voucher_code => $voucher_data ) {
                    if ( $code === $voucher_code ) {
                        $data = array(
                            'pass_id' => $pass_id,
                            'code'    => $voucher_code,
                            'price'   => number_format( LaterPay_Helper_View::normalize( $voucher_data['price'] ), 2 ),
                            'title'   => $voucher_data['title'],
                            'type'    => $key,
                        );

                        return $data;
                    }
                }
            }

        }

        return null;
    }

    /**
     * Check, if given time passes have vouchers.
     *
     * @param array $time_passes array of time passes
     * @param bool  $is_gift
     *
     * @return bool $has_vouchers
     */
    public static function passes_have_vouchers( $time_passes, $is_gift = false ) {
        $has_vouchers = false;

        if ( $time_passes && is_array( $time_passes ) ) {
            foreach ( $time_passes as $time_pass ) {
                if ( self::get_time_pass_vouchers( $time_pass['pass_id'], $is_gift ) ) {
                    $has_vouchers = true;
                    break;
                }
            }
        }

        return $has_vouchers;
    }

    /**
     * Check if given subscriptions have vouchers.
     *
     * @param array $subscriptions Array of subscriptions.
     *
     * @return bool $has_vouchers
     */
    public static function subscriptions_have_vouchers( $subscriptions ) {
        $has_vouchers = false;

        if ( $subscriptions && is_array( $subscriptions ) ) {
            foreach ( $subscriptions as $subscription ) {
                if ( self::get_subscription_vouchers( $subscription['id'] ) ) {
                    $has_vouchers = true;
                    break;
                }
            }
        }

        return $has_vouchers;
    }


    /**
     * Actualize voucher statistic.
     *
     * @param bool $is_gift
     *
     * @return void
     */
    public static function actualize_voucher_statistic( $is_gift = false ) {
        $vouchers    = self::get_time_pass_vouchers( $is_gift );
        $statistic   = self::get_all_vouchers_statistic( $is_gift );
        $result      = $statistic;
        $option_name = $is_gift ? self::GIFT_STAT_OPTION : self::VOUCHER_STAT_OPTION;

        foreach ( $statistic as $pass_id => $statistic_data ) {
            if ( ! isset( $vouchers[ $pass_id ] ) ) {
                unset( $result[ $pass_id ] );
            } else {
                foreach ( $statistic_data as $code => $usages ) {
                    if ( ! isset( $vouchers[ $pass_id ][ $code ] ) ) {
                        unset( $result[ $pass_id ][ $code ] );
                    }
                }
            }
        }

        // update voucher statistics
        update_option( $option_name, $result );
    }

    /**
     * Update voucher statistic.
     *
     * @param int    $pass_id time pass id
     * @param string $code    voucher code
     * @param bool   $is_gift
     *
     * @return bool success or error
     */
    public static function update_voucher_statistic( $pass_id, $code, $is_gift = false ) {
        $pass_vouchers = self::get_time_pass_vouchers( $pass_id, $is_gift );
        $option_name   = $is_gift ? self::GIFT_STAT_OPTION : self::VOUCHER_STAT_OPTION;

        // check, if such a voucher exists
        if ( $pass_vouchers && isset( $pass_vouchers[ $code ] ) ) {
            // get all voucher statistics for this pass
            $voucher_statistic_data = self::get_time_pass_vouchers_statistic( $pass_id, $is_gift );
            // check, if statistic is empty
            if ( $voucher_statistic_data ) {
                // increment counter by 1, if statistic exists
                $voucher_statistic_data[ $code ] += 1;
            } else {
                // create new data array, if statistic is empty
                $voucher_statistic_data[ $code ] = 1;
            }

            $statistic           = self::get_all_vouchers_statistic( $is_gift );
            $statistic[ $pass_id ] = $voucher_statistic_data;

            update_option( $option_name, $statistic );
            return true;
        }

        return false;
    }

    /**
     * Get time pass voucher statistic by time pass id.
     *
     * @param  int $pass_id time pass id
     * @param  bool $is_gift
     *
     * @return array $statistic
     */
    public static function get_time_pass_vouchers_statistic( $pass_id, $is_gift = false ) {
        $statistic = self::get_all_vouchers_statistic( $is_gift );

        if ( isset( $statistic[ $pass_id ] ) ) {
            return $statistic[ $pass_id ];
        }

        return array();
    }

    /**
     * Get statistics for all vouchers.
     *
     * @param bool $is_gift
     *
     * @return array $statistic
     */
    public static function get_all_vouchers_statistic( $is_gift = false ) {
        $option_name = $is_gift ? self::GIFT_STAT_OPTION : self::VOUCHER_STAT_OPTION;
        $statistic   = get_option( $option_name );
        if ( ! $statistic || ! is_array( $statistic ) ) {
            update_option( $option_name, '' );

            return array();
        }

        return $statistic;
    }

    /**
     * Get gift code usages count
     *
     * @param $code
     *
     * @return null
     */
    public static function get_gift_code_usages_count( $code ) {
        $usages = get_option( 'laterpay_gift_codes_usages' );
        return $usages && isset( $usages[ $code ] ) ? $usages[ $code ] : 0;
    }

    /**
     * Update gift code usages
     *
     * @param $code
     *
     * @return void
     */
    public static function update_gift_code_usages( $code ) {
        $usages = get_option( 'laterpay_gift_codes_usages' );
        if ( ! $usages ) {
            $usages = array();
        }
        isset( $usages[ $code ] ) ? $usages[ $code ] += 1 : $usages[ $code ] = 1;
        update_option( 'laterpay_gift_codes_usages', $usages );
    }

    /**
     * Check if gift code usages exceed limits
     *
     * @param $code
     *
     * @return bool
     */
    public static function check_gift_code_usages_limit( $code ) {
        $limit  = get_option( 'laterpay_maximum_redemptions_per_gift_code' );
        $usages = self::get_gift_code_usages_count( $code );
        if ( ( $usages + 1 ) <= $limit ) {
            return true;
        }
        return false;
    }
}

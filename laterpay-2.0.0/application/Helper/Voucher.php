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
     * Save vouchers for current pass.
     *
     * @param int   $pass_id
     * @param array $data
     * @param bool  $is_gift
     *
     * @return void
     */
    public static function save_pass_vouchers( $pass_id, $data, $is_gift = false ) {
        $vouchers     = self::get_all_vouchers( $is_gift );
        $option_name  = $is_gift ? self::GIFT_CODES_OPTION : self::VOUCHER_CODES_OPTION;

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
     * Get voucher codes of current time pass.
     *
     * @param int  $pass_id
     * @param bool $is_gift
     *
     * @return array
     */
    public static function get_time_pass_vouchers( $pass_id, $is_gift = false ) {
        $vouchers = self::get_all_vouchers( $is_gift );
        if ( ! isset( $vouchers[ $pass_id ] ) ) {
            return array();
        }

        return $vouchers[ $pass_id ];
    }

    /**
     * Get all vouchers.
     *
     * @param bool $is_gift
     *
     * @return array of vouchers
     */
    public static function get_all_vouchers( $is_gift = false ) {
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
     * Delete voucher code.
     *
     * @param int       $pass_id
     * @param string    $code
     * @param bool      $is_gift
     *
     * @return void
     */
    public static function delete_voucher_code( $pass_id, $code = null, $is_gift = false ) {
        $pass_vouchers = self::get_time_pass_vouchers( $pass_id, $is_gift );
        if ( $pass_vouchers && is_array( $pass_vouchers ) ) {
            if ( $code ) {
                unset( $pass_vouchers[ $code ] );
            } else {
                $pass_vouchers = array();
            }
        }

        self::save_pass_vouchers( $pass_id, $pass_vouchers, $is_gift );
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
        $vouchers = self::get_all_vouchers( $is_gift );

        // search code
        foreach ( $vouchers as $pass_id => $pass_vouchers ) {
            foreach ( $pass_vouchers as $voucher_code => $voucher_data ) {
                if ( $code === $voucher_code ) {
                    $data = array(
                        'pass_id' => $pass_id,
                        'code'    => $voucher_code,
                        'price'   => number_format( LaterPay_Helper_View::normalize( $voucher_data['price'] ), 2 ),
                        'title'   => $voucher_data['title'],
                    );

                    return $data;
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
     * Actualize voucher statistic.
     *
     * @param bool $is_gift
     *
     * @return void
     */
    public static function actualize_voucher_statistic( $is_gift = false ) {
        $vouchers    = self::get_all_vouchers( $is_gift );
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

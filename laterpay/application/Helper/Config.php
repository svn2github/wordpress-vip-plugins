<?php

/**
 * LaterPay config helper.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Helper_Config {
    private static $options = array();

    private static $regional_settings = array(
        'eu' => array(
            'api' => array(
                'sandbox_backend_api_url' => 'https://api.sandbox.laterpaytest.net',
                'sandbox_dialog_api_url'  => 'https://web.sandbox.laterpaytest.net',
                'live_backend_api_url'    => 'https://api.laterpay.net',
                'live_dialog_api_url'     => 'https://web.laterpay.net',
                'merchant_backend_url'    => 'https://merchant.laterpay.net/',
                'token_name'              => 'token',
                'sandbox_merchant_id'     => '984df2b86250447793241a',
                'sandbox_api_key'         => '57791c777baa4cea94c4ec074184e06d',
            ),
            'currency' => array(
                'code'                    => 'EUR',
                'dynamic_start'           => 13,
                'dynamic_end'             => 18,
                'default_price'           => 0.29,
                'limits' => array(
                    'default' => array(
                        'ppu_min'         => 0.05,
                        'ppu_only_limit'  => 1.48,
                        'ppu_max'         => 5.00,
                        'sis_min'         => 1.49,
                        'sis_only_limit'  => 5.01,
                        'sis_max'         => 149.99
                    ),
                    'pro' => array(
                        'ppu_min'         => 0.05,
                        'ppu_only_limit'  => 49.98,
                        'ppu_max'         => 250.00,
                        'sis_min'         => 49.99,
                        'sis_only_limit'  => 250.01,
                        'sis_max'         => 1000.00
                    )
                )
            ),
            'payment' => array(
                'icons' => array(
                    'sepa',
                    'visa',
                    'mastercard',
                    'paypal'
                )
            )
        ),
        'us' => array(
            'api' => array(
                'sandbox_backend_api_url' => 'https://api.sandbox.uselaterpaytest.com',
                'sandbox_dialog_api_url'  => 'https://web.sandbox.uselaterpaytest.com',
                'live_backend_api_url'    => 'https://api.uselaterpay.com',
                'live_dialog_api_url'     => 'https://web.uselaterpay.com',
                'merchant_backend_url'    => 'https://web.uselaterpay.com/merchant',
                'token_name'              => 'token',
                'sandbox_merchant_id'     => 'xswcBCpR6Vk6jTPw8si7KN',
                'sandbox_api_key'         => '22627fa7cbce45d394a8718fd9727731',
            ),
            'currency' => array(
                'code'                    => 'USD',
                'dynamic_start'           => 13,
                'dynamic_end'             => 18,
                'default_price'           => 0.29,
                'limits' => array(
                    'default' => array(
                        'ppu_min'         => 0.05,
                        'ppu_only_limit'  => 1.98,
                        'ppu_max'         => 5.00,
                        'sis_min'         => 1.99,
                        'sis_only_limit'  => 5.01,
                        'sis_max'         => 149.99,
                    ),
                    'pro' => array(
                        'ppu_min'         => 0.05,
                        'ppu_only_limit'  => 1.98,
                        'ppu_max'         => 5.00,
                        'sis_min'         => 1.99,
                        'sis_only_limit'  => 5.01,
                        'sis_max'         => 149.99,
                    )
                )
            ),
            'payment' => array(
                'icons' => array(
                    'visa',
                    'mastercard',
                    'visa-debit',
                    'americanexpress',
                    'discovercard'
                )
            )
        )
    );

    /**
     * Get regional settings
     *
     * @return array
     */
    public static function get_regional_settings() {
        $region = get_option( 'laterpay_region', 'us' );

        // region correction
        if ( ! isset( self::$regional_settings[ $region ] ) ) {
            update_option( 'laterpay_region', 'us' );
            $region = 'us';
        }

        return self::build_settings_list(self::$regional_settings[ $region ]);
    }

    /**
     * Build settings list
     *
     * @return array
     */
    protected static function build_settings_list( $settings, $prefix = '' ) {
        $list = array();

        foreach ( $settings as $key => $value ) {
            $setting_name = $prefix . $key;

            if ( is_array( $value ) ) {
                $list = array_merge( $list, self::build_settings_list( $value, $setting_name . '.' ) );
                continue;
            }

            $list[$setting_name] = $value;
        }

        return $list;
    }

    /**
     * Get settings section for current region
     *
     * @param $section
     *
     * @return array|null
     */
    public static function get_settings_section( $section ) {
        // get regional settings
        $region = get_option( 'laterpay_region', 'us' );

        return isset( self::$regional_settings[ $region ][ $section ] ) ? self::$regional_settings[ $region ][ $section ] : null;
    }

    /**
     * Get currency config
     *
     * @return array
     */
    public static function get_currency_config() {
        $config = laterpay_get_plugin_config();
        $limits_section = 'currency.limits';
        $plan = get_option( 'laterpay_pro_merchant', 0 ) ? 'pro' : 'default';

        // get limits
        $currency_limits  = $config->get_section( $limits_section . '.' . $plan );
        $currency_general = array(
            'code'          => $config->get( 'currency.code' ),
            'dynamic_start' => $config->get( 'currency.dynamic_start' ),
            'dynamic_end'   => $config->get( 'currency.dynamic_end' ),
            'default_price' => $config->get( 'currency.default_price' )
        );

        // process limits keys
        foreach ( $currency_limits as $key => $val ) {
            $key_components = explode( '.', $key );
            $simple_key = end( $key_components );
            $currency_limits[ $simple_key ] = $val;
            unset( $currency_limits[ $key ] );
        }

        return array_merge( $currency_limits, $currency_general );
    }

    /**
     * Get options for LaterPay PHP client.
     *
     * @return array
     */
    public static function get_php_client_options() {
        $config = laterpay_get_plugin_config();

        if ( empty( self::$options ) ) {
            if ( get_option( 'laterpay_plugin_is_in_live_mode' ) ) {
                self::$options['cp_key']   = get_option( 'laterpay_live_merchant_id' );
                self::$options['api_key']  = get_option( 'laterpay_live_api_key' );
                self::$options['api_root'] = $config->get( 'api.live_backend_api_url' );
                self::$options['web_root'] = $config->get( 'api.live_dialog_api_url' );
            } else {
                self::$options['cp_key']   = get_option( 'laterpay_sandbox_merchant_id' );
                self::$options['api_key']  = get_option( 'laterpay_sandbox_api_key' );
                self::$options['api_root'] = $config->get( 'api.sandbox_backend_api_url' );
                self::$options['web_root'] = $config->get( 'api.sandbox_dialog_api_url' );
            }

            self::$options['token_name'] = $config->get( 'api.token_name' );
        }

        return self::$options;
    }

    /**
     * Get actual sandbox creds
     *
     * @return array $creds
     */
    public static function prepare_sandbox_creds() {
        $regional_settings   = self::get_regional_settings();
        $creds_match_default = false;

        $cp_key  = get_option( 'laterpay_sandbox_merchant_id' );
        $api_key = get_option( 'laterpay_sandbox_api_key' );

        // detect if sandbox creds were modified
        if ( $cp_key && $api_key ) {
            foreach ( self::$regional_settings as $settings ) {
                if ( $settings['api'][ 'sandbox_merchant_id' ] === $cp_key &&
                     $settings['api'][ 'sandbox_api_key' ] === $api_key ) {
                    $creds_match_default = true;
                    break;
                }
            }
        } else {
            $creds_match_default = true;
        }

        if ( $creds_match_default ) {
            $cp_key  = $regional_settings[ 'api.sandbox_merchant_id' ];
            $api_key = $regional_settings[ 'api.sandbox_api_key' ];

            update_option( 'laterpay_sandbox_merchant_id', $cp_key );
            update_option( 'laterpay_sandbox_api_key', $api_key );
        }

        return array(
            'cp_key'  => $cp_key,
            'api_key' => $api_key,
        );
    }
}

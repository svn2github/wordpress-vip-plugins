<?php

/**
 * LaterPay dynamic pricing data form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Form_DynamicPricingData extends LaterPay_Form_Abstract
{

    /**
     * Implementation of abstract method
     *
     * @return void
     */
    public function init() {
        $currency = LaterPay_Helper_Config::get_currency_config();

        $this->set_field(
            'action',
            array(
                'validators' => array(
                    'is_string',
                    'cmp' => array(
                        array(
                            'eq' => 'laterpay_get_dynamic_pricing_data',
                        ),
                    ),
                ),
            )
        );

        $this->set_field(
            'post_id',
            array(
                'validators' => array(
                    'is_int',
                    'post_exist',
                ),
                'filters' => array(
                    'to_int',
                ),
            )
        );

        $this->set_field(
            'post_price',
            array(
                'validators' => array(
                    'is_float',
                    'cmp' => array(
                        array(
                            'lte' => $currency['sis_max'],
                            'gte' => $currency['ppu_min'],
                        ),
                        array(
                            'eq' => 0.00,
                        ),
                    ),
                ),
                'filters' => array(
                    'delocalize',
                    'format_num' => array(
                        'decimals'      => 2,
                        'dec_sep'       => '.',
                        'thousands_sep' => ''
                    ),
                    'to_float'
                ),
            )
        );
    }
}


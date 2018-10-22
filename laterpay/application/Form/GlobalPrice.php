<?php

/**
 * LaterPay global price class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Form_GlobalPrice extends LaterPay_Form_Abstract
{

    /**
     * Implementation of abstract method.
     *
     * @return void
     */
    public function init() {
        $currency = LaterPay_Helper_Config::get_currency_config();

        $this->set_field(
            'form',
            array(
                'validators' => array(
                    'is_string',
                    'cmp' => array(
                        array(
                            'eq' => 'global_price_form',
                        ),
                    ),
                ),
            )
        );

        $this->set_field(
            'action',
            array(
                'validators' => array(
                    'is_string',
                    'cmp' => array(
                        array(
                            'eq' => 'laterpay_pricing',
                        ),
                    ),
                ),
            )
        );

        $this->set_field(
            '_wpnonce',
            array(
                'validators' => array(
                    'is_string',
                    'cmp' => array(
                        array(
                            'ne' => null,
                        ),
                    ),
                ),
            )
        );

        $this->set_field(
            'laterpay_global_price_revenue_model',
            array(
                'validators' => array(
                    'is_string',
                    'in_array' => array( 'ppu', 'sis' ),
                    'depends' => array(
                        array(
                            'field' => 'laterpay_global_price',
                            'value' => 'sis',
                            'conditions' => array(
                                'cmp' => array(
                                    array(
                                        'lte' => $currency['sis_max'],
                                        'gte' => $currency['sis_min'],
                                    ),
                                ),
                            ),
                        ),
                        array(
                            'field' => 'laterpay_global_price',
                            'value' => 'ppu',
                            'conditions' => array(
                                'cmp' => array(
                                    array(
                                        'lte' => $currency['ppu_max'],
                                        'gte' => $currency['ppu_min'],
                                    ),
                                    array(
                                         'eq' => 0.00,
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'filters' => array(
                    'to_string'
                ),
            )
        );

        $this->set_field(
            'laterpay_global_price',
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

        $this->set_field(
            'lp_post_price_behaviour',
            array(
                'filters' => array(
                    'to_int'
                ),
            )
        );
    }
}

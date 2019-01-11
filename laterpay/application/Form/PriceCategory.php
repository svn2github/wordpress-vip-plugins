<?php

/**
 * LaterPay category price form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Form_PriceCategory extends LaterPay_Form_Abstract
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
                            'like' => 'price_category_form',
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
            'category_id',
            array(
                'filters' => array(
                    'to_string',
                ),
                'can_be_null' => true,
            )
        );

        $this->set_field(
            'category_name',
            array(
                'filters' => array(
                    'to_string',
                ),
                'can_be_null' => true,
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
            'laterpay_category_price_revenue_model',
            array(
                'validators' => array(
                    'is_string',
                    'in_array' => array( 'ppu', 'sis' ),
                    'depends' => array(
                        array(
                            'field' => 'price',
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
                            'field' => 'price',
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
                    'to_string',
                ),
                'not_strict_name' => true,
            )
        );

        $this->set_field(
            'category',
            array(
                'filters' => array(
                    'to_string',
                )
            )
        );

        $this->set_field(
            'price',
            array(
                'validators' => array(
                    'is_float',
                    'cmp' => array(
                        array(
                            'lte' => $currency['sis_max'],
                            'gte' => $currency['ppu_min'],
                        ),
                        array(
                            'eq'  => 0.00,
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


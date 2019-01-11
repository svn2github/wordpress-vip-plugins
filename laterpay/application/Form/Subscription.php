<?php

/**
 * LaterPay subscription save form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Form_Subscription extends LaterPay_Form_Abstract
{

    /**
     * Implementation of abstract method.
     *
     * @return void
     */
    public function init() {
        $currency = LaterPay_Helper_Config::get_currency_config();

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
            'id',
            array(
                'validators' => array(
                    'is_int',
                ),
                'filters' => array(
                    'to_int',
                    'unslash',
                )
            )
        );

        $this->set_field(
            'duration',
            array(
                'validators' => array(
                    'is_int',
                ),
                'filters' => array(
                    'to_int',
                    'unslash',
                )
            )
        );

        $this->set_field(
            'period',
            array(
                'validators' => array(
                    'is_int',
                    'in_array' => array_keys( LaterPay_Helper_TimePass::get_period_options() ),
                    'depends' => array(
                        array(
                            'field' => 'duration',
                            'value' => array( 0, 1, 2 ),
                            'conditions' => array(
                                'cmp' => array(
                                    array(
                                        'lte' => 24,
                                        'gte' => 1,
                                    ),
                                ),
                            ),
                        ),
                        array(
                            'field' => 'duration',
                            'value' => 3,
                            'conditions' => array(
                                'cmp' => array(
                                    array(
                                        'lte' => 12,
                                        'gte' => 1,
                                    ),
                                ),
                            ),
                        ),
                        array(
                            'field' => 'duration',
                            'value' => 4,
                            'conditions' => array(
                                'cmp' => array(
                                    array(
                                        'eq' => 1,
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'filters'    => array(
                    'to_int',
                    'unslash',
                ),
                'can_be_null' => false,
            )
        );

        $this->set_field(
            'access_to',
            array(
                'validators' => array(
                    'is_int',
                    'in_array' => array_keys( LaterPay_Helper_TimePass::get_access_options() ),
                ),
                'filters'    => array(
                    'to_int',
                    'unslash',
                ),
                'can_be_null' => false,
            )
        );

        $this->set_field(
            'access_category',
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
                            'gte' => $currency['sis_min'],
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
            'title',
            array(
                'validators' => array(
                    'is_string',
                ),
                'filters' => array(
                    'to_string',
                    'unslash',
                )
            )
        );

        $this->set_field(
            'description',
            array(
                'validators' => array(
                    'is_string',
                ),
                'filters' => array(
                    'to_string',
                    'unslash',
                )
            )
        );

        // Add validators for voucher.
        $this->set_field(
            'voucher_code',
            array(
                'validators' => array(
                    'is_array',
                ),
                'can_be_null' => true,
            )
        );

        $this->set_field(
            'voucher_price',
            array(
                'validators' => array(
                    'is_array',
                ),
                'can_be_null' => true,
            )
        );

        $this->set_field(
            'voucher_title',
            array(
                'validators' => array(
                    'is_array',
                ),
                'can_be_null' => true,
            )
        );
    }
}

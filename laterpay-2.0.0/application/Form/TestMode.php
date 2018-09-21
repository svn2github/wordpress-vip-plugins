<?php

/**
 * LaterPay test mode form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Form_TestMode extends LaterPay_Form_Abstract
{

    /**
     * Implementation of abstract method.
     *
     * @return void
     */
    public function init() {
        $this->set_field(
            'form',
            array(
                'validators' => array(
                    'is_string',
                    'cmp' => array(
                        array(
                            'eq' => 'laterpay_test_mode',
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
                            'eq' => 'laterpay_account',
                        ),
                    ),
                ),
            )
        );

        $this->set_field(
            'invalid_credentials',
            array(
                'validators' => array(
                    'is_int',
                    'in_array' => array( 0, 1 ),
                ),
                'filters' => array(
                    'to_int',
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
            'plugin_is_in_visible_test_mode',
            array(
                'validators' => array(
                    'is_int',
                    'in_array' => array( 0, 1 ),
                ),
                'filters' => array(
                    'to_int',
                ),
            )
        );
    }
}


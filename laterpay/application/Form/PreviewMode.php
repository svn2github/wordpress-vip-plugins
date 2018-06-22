<?php

/**
 * LaterPay post preview mode class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Form_PreviewMode extends LaterPay_Form_Abstract
{
    /**
     * Implementation of abstract method
     *
     * @return void
     */
    public function init() {
        $this->set_field(
            'action',
            array(
                'validators' => array(
                    'is_string',
                    'cmp' => array(
                        array(
                            'eq' => 'laterpay_preview_mode_render',
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
    }
}

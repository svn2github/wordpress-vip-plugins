<?php

/**
 * LaterPay plugin mode form class for saving post data without pricing parameters.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Form_PostWithoutPricing extends LaterPay_Form_Abstract
{

    /**
     * Implementation of abstract method.
     *
     * @return void
     */
    public function init() {
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
            'laterpay_teaser_content_box_nonce',
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
            'laterpay_post_teaser',
            array(
                'validators' => array(
                    'is_string',
                ),
                'filters'    => array(
                    'to_string',
                )
            )
        );

        $this->set_field(
            'post_default_category',
            array(
                'validators' => array(
                    'is_int',
                ),
                'filters' => array(
                    'unslash',
                    'to_int',
                ),
                'can_be_null' => true,
            )
        );
    }
}


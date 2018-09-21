<?php

/**
 * LaterPay form validation exception.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Core_Exception_FormValidation extends LaterPay_Core_Exception
{
    /**
     * @param string $form
     * @param array $errors
     */
    public function __construct( $form, $errors = array() ) {
        $this->setContext( $errors );
        $message = sprintf( __( 'Form "%s" validation failed.', 'laterpay' ), $form );
        parent::__construct( $message );
    }
}

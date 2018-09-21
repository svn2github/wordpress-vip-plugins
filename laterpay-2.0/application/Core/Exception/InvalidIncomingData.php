<?php

/**
 * LaterPay invalid incomming data exception.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Core_Exception_InvalidIncomingData extends LaterPay_Core_Exception
{
    public function __construct( $param = '', $message = '' ) {
        if ( ! $message ) {
            $message = sprintf( __( '"%s" param missed or has incorrect value', 'laterpay' ), $param );
        }
        parent::__construct( $message );
    }
}

<?php

/**
 * LaterPay post not found exception.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Core_Exception_PostNotFound extends LaterPay_Core_Exception
{
    public function __construct( $post_id = '', $message = '' ) {
        if ( ! $message ) {
            $message = sprintf( __( 'Post with id "%s" not exist', 'laterpay' ), $post_id );
        }
        parent::__construct( $message );
    }
}

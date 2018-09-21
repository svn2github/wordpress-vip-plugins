<?php

/**
 * LaterPay core exception.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Core_Exception extends Exception
{
    /**
     * Context
     * @var
     */
    protected $context;

    /**
     * Get context
     * @return mixed
     */
    public function getContext() {
        return $this->context;
    }

    /**
     * Set context
     * @param array $data
     * @return void
     */
    public function setContext( array $data = array() ) {
        $this->context = $data;
    }
}

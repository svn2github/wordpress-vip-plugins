<?php

/**
 * LaterPay Event Subscriber Interface.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
interface LaterPay_Core_Event_SubscriberInterface {
    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     */
    public static function get_subscribed_events();

    /**
     * Returns an array of shared event names this subscriber wants to distribute.
     *
     * For instance:
     *  * array( 'eventName' => array( 'methodName' ) )
     *
     * @return array The shared event names to distribute
     */
    public static function get_shared_events();
}

<?php

class LaterPay_Wrapper
{

    /**
     * Returns the JSON representation of a value.
     * Uses wp_json_encode if in WP else uses json_encode.
     *
     * @param mixed $data    Variable (usually an array or object) to encode as JSON.
     * @param int   $options Optional. Options to be passed to json_encode(). Default 0.
     * @param int   $depth   Optional. Maximum depth to walk through $data. Must be
     *                       greater than 0. Default 512.
     * @return string|false The JSON encoded string, or false if it cannot be encoded.
     */
    public static function laterpay_json_encode( $data, $options = 0, $depth = 512 ) {

        if ( function_exists( 'wp_json_encode' ) ) {
            $result = wp_json_encode( $data, $options, $depth );
        } else {
            // This won't be used in a WordPress environment.
            $result = json_encode( $data, $options, $depth ); // phpcs:ignore
        }
        return $result;
    }

    /**
     * Parse a URL and return its components
     * Uses wp_parse_url if in WP else uses parse_url.
     *
     * @param string $url       The URL to parse.
     * @param int    $component The specific component to retrieve. Use one of the PHP
     *                          predefined constants to specify which one.
     *                          Defaults to -1 (= return all parts as an array).
     *                          greater than 0. Default 512.
     * @return mixed False on parse failure; Array of URL components on success;
     *               When a specific component has been requested: null if the component
     *               doesn't exist in the given URL; a string or - in the case of
     *               PHP_URL_PORT - integer when it does. See parse_url()'s return values.
     */
    public static function laterpay_parse_url( $url, $component = -1 ) {

        if ( function_exists( 'wp_parse_url' ) ) {
            $result = wp_parse_url( $url, $component );
        } else {
            // This won't be used in a WordPress environment.
            $result = parse_url( $url, $component ); // phpcs:ignore
        }
        return $result;
    }

    /**
     * Retrieve the raw response from the HTTP request using the GET method.
     *
     * @see wp_remote_request() For more information on the response array format.
     * @see WP_Http::request() For default arguments information.
     *
     * @param string $url            Site URL to retrieve.
     * @param array  $args           Optional. Request arguments. Default empty array.
     * @param string $fallback_value Optional. Set a fallback value to be returned if the external request fails.
     * @param int    $threshold      Optional. The number of fails required before subsequent requests automatically return the fallback value. Defaults to 3, with a maximum of 10.
     * @param int    $timeout        Optional. Number of seconds before the request times out. Valid values 1-3; defaults to 1.
     * @param int    $retry          Optional. Number of seconds before resetting the fail counter and the number of seconds to delay making new requests after the fail threshold is reached. Defaults to 20, with a minimum of 10.
     *
     * @return WP_Error|array The response or WP_Error on failure.
     */
    public static function laterpay_remote_get( $url, $args = array(), $fallback_value='', $threshold=3, $timeout=1, $retry=20 ) {

        if ( laterpay_check_is_vip() ) {
            $response = vip_safe_wp_remote_get( $url, $fallback_value, $threshold, $timeout, $retry, $args );
        } else {
            $response = wp_remote_get( $url, $args ); // phpcs:ignore
        }

        return $response;
    }

    /**
     * Get User Agent For Client API Request.
     *
     * @return string
     */
    public static function get_user_agent() {
        if ( function_exists( 'get_option' ) ) {
            $plugin_version = get_option( 'laterpay_plugin_version' );
            $header_message = sprintf( 'LaterPay WP Plugin - v%s', $plugin_version );
        } else {
            $header_message = 'LaterPay Client - PHP - v0.3';
        }
        return $header_message;
    }
}

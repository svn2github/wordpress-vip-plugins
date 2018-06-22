<?php

/**
 * Native HTTP transport.
 */
class LaterPay_Http_Transport_Wp extends LaterPay_Http_Transport_Abstract implements LaterPay_Http_Transport
{

    /**
     * {@inheritdoc }
     */
    public function request( $url, $headers = array(), $data = array(), $options = array() ) {
        if ( isset( $options['type'] ) ) {
            $type = $options['type'];
        } else {
            $type = LaterPay_Http_Client::GET;
        }
        if ( isset( $options['timeout'] ) ) {
            $timeout = $options['timeout'];
        } else {
            $timeout = 30;
        }
        if ( in_array( $type, array( LaterPay_Http_Client::HEAD, LaterPay_Http_Client::GET, LaterPay_Http_Client::DELETE ), true ) & ! empty( $data ) ) {
            $url = self::format_get( $url, $data );
            $data = '';
        } elseif ( ! empty( $data ) && ! is_string( $data ) ) {
            $data = http_build_query( $data, null, '&' );
        }
        switch ( $type ) {
            case LaterPay_Http_Client::POST:
            case LaterPay_Http_Client::PUT:
            case LaterPay_Http_Client::PATCH:
                $raw_response = wp_remote_post(
                    $url,
                    array(
                        'headers'   => $headers,
                        'body'      => $data,
                        'timeout'   => $timeout,
                    )
                );
                $response = wp_remote_retrieve_body( $raw_response );
                break;
            case LaterPay_Http_Client::HEAD:
                $raw_response = wp_remote_head(
                    $url,
                    array(
                        'headers'   => $headers,
                        'timeout'   => $timeout,
                    )
                );
                $response = wp_remote_retrieve_body( $raw_response );
                break;
            default:
                $raw_response = LaterPay_Wrapper::laterpay_remote_get( $url, array(
                    'headers' => $headers,
                    'timeout' => $timeout,
                ), '', 3, 3 );

                $response = wp_remote_retrieve_body( $raw_response );
                break;
        }

        $response_code = wp_remote_retrieve_response_code( $raw_response );

        if ( empty( $response_code ) ) {
            throw new Exception(
                wp_remote_retrieve_response_message( $raw_response )
            );
        }

        return $response;
    }

    /**
     * {@inheritdoc }
     */
    public static function test() {
        return function_exists( 'wp_remote_get' ) && function_exists( 'wp_remote_post' );
    }
}

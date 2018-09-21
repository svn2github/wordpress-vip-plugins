<?php

/**
 * Native HTTP transport.
 */
class LaterPay_Http_Transport_Native extends LaterPay_Http_Transport_Abstract implements LaterPay_Http_Transport
{

    /**
     * {@inheritdoc }
     */
    public function request( $url, $headers = array(), $data = array(), $options = array() ) {
        $stream_options = array(
            'http' => array(),
        );

        if ( isset( $options['type'] ) ) {
            $type = $options['type'];
        } else {
            $type = LaterPay_Http_Client::GET;
        }
        if ( in_array( $type, array( LaterPay_Http_Client::HEAD, LaterPay_Http_Client::GET, LaterPay_Http_Client::DELETE ) ) & ! empty( $data ) ) {
            $url = self::format_get( $url, $data );
            $data = '';
        } elseif ( ! empty( $data ) && ! is_string( $data ) ) {
            $data = http_build_query( $data, null, '&' );
        }
        switch ( $type ) {
            case LaterPay_Http_Client::POST:
            case LaterPay_Http_Client::PUT:
            case LaterPay_Http_Client::PATCH:
                $stream_options['http']['content'] = $data;
                break;
        }
        if ( ! empty( $headers ) ) {
            $parsed_headers = implode( "\r\n", LaterPay_Http_Client::flatten( $headers ) ) . "\r\n";
            $stream_options['http']['header'] = $parsed_headers;
        }
        $stream_options['http']['method'] = $type;
        $context  = stream_context_create( $stream_options );
        $response = file_get_contents( $url, null, $context );

        if ( $response === false ) {
            throw new Exception(
                'Could not resolve host.'
            );
        }

        return $response;
    }

    /**
     * {@inheritdoc }
     */
    public static function test() {
        return function_exists( 'file_get_contents' ) && ini_get( 'allow_url_fopen' );
    }

}

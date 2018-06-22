<?php

/**
 * Native HTTP transport.
 */
class LaterPay_Http_Transport_Curl extends LaterPay_Http_Transport_Abstract implements LaterPay_Http_Transport
{

    /**
     * {@inheritdoc }
     */
    public function request( $url, $headers = array(), $data = array(), $options = array() ) {
        if ( isset( $options['timeout'] ) ) {
            $timeout = $options['timeout'];
        } else {
            $timeout = 30;
        }
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
        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
        switch ( $type ) {
            case LaterPay_Http_Client::POST:
            case LaterPay_Http_Client::PUT:
            case LaterPay_Http_Client::PATCH:
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
                break;
        }

        $response      = (string) curl_exec( $ch );
        $error_message = curl_error( $ch );
        curl_close( $ch );

        if ( ! empty( $error_message ) ) {
            throw new Exception(
                $error_message
            );
        }

        return $response;
    }

    /**
     * {@inheritdoc }
     */
    public static function test() {
        return extension_loaded( 'curl' );
    }

}

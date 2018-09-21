<?php

/**
 * LaterPay core response.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Core_Response extends LaterPay_Core_Entity
{

    public function _construct() {
        parent::_construct();
        $this->set_data( 'headers', array() );
        $this->set_data( 'body', '' );
        $this->set_data( 'http_response_code', 200 ); // HTTP response code to use in headers
    }

    /**
     * Normalize header name.
     *
     * Normalizes a header name to X-Capitalized-Names.
     *
     * @param string $name
     *
     * @return string
     */
    protected function _normalize_header( $name ) {
        $filtered = str_replace( array( '-', '_' ), ' ', (string) $name );
        $filtered = ucwords( strtolower( $filtered ) );
        $filtered = str_replace( ' ', '-', $filtered );

        return $filtered;
    }

    /**
     * Set a header.
     *
     * Replaces any headers already defined with that $name, if $replace is true.
     *
     * @param  string  $name
     * @param  string  $value
     * @param  boolean $replace
     *
     * @return LaterPay_Core_Response
     */
    public function set_header( $name, $value, $replace = false ) {
        $name       = $this->_normalize_header( $name );
        $value      = (string) $value;
        $headers    = $this->get_data_set_default( 'headers', array() );

        if ( $replace ) {
            foreach ( $headers as $key => $header ) {
                if ( $name === $header['name'] ) {
                    unset( $headers[ $key ] );
                }
            }
        }

        $headers[] = array(
            'name'      => $name,
            'value'     => $value,
            'replace'   => $replace,
        );
        $this->set_data( 'headers', $headers );

        return $this;
    }

    /**
     * Send all headers. Sends all specified headers.
     *
     * @return  LaterPay_Core_Response
     */
    public function send_headers() {
        if ( headers_sent() ) {
            return $this;
        }

        $httpCodeSent = false;

        foreach ( $this->get_data_set_default( 'headers', array() ) as $header ) {
            if ( ! $httpCodeSent ) {
                header( $header['name'] . ': ' . $header['value'], $header['replace'], $this->get_data( 'http_response_code' ) );
                $httpCodeSent = true;
            } else {
                header( $header['name'] . ': ' . $header['value'], $header['replace'] );
            }
        }

        if ( ! $httpCodeSent ) {
            header( 'HTTP/1.1 ' . $this->get_data( 'http_response_code' ) );
            $httpCodeSent = true;
        }

        return $this;
    }

    /**
     * Set HTTP response code to use with headers.
     *
     * @param int $code
     *
     * @return LaterPay_Core_Response
     */
    public function set_http_response_code( $code ) {
        if ( ! is_int( $code ) || ( 100 > $code ) || ( 599 < $code ) ) {
            $code = 500;

            return $this;
        }

        $this->set_data( 'http_response_code', $code );

        return $this;
    }

    /**
     * Echo the body segments.
     *
     * @return void
     */
    public function output_body() {
        $body = $this->get_data( 'body' );

        if ( is_array( $body ) ) {
            $body = implode( '', $body );
        }

        // We've cannot escape body here since we HAVE to echo the body as it is.
        // It can contains "body" part of a request. It could contain JSON/HTML or file response.
        // Even if it contains HTML, It is meant to output unescaped.
        echo $body; // phpcs:ignore
    }

    /**
     * Send the response with headers and body.
     *
     * @return void
     */
    public function send_response() {
        $this->send_headers();
        $this->output_body();
    }
}

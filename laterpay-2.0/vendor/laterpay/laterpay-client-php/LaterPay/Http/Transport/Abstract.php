<?php

abstract class LaterPay_Http_Transport_Abstract
{

    /**
     * Format a URL given GET data.
     *
     * @param string                $url
     * @param array|object|string   $data   data to build query using, see {@see http://php.net/http_build_query}
     *
     * @return string URL with data
     */
    protected static function format_get( $url, $data ) {
        if ( ! empty( $data ) ) {
            $url_parts =  LaterPay_Wrapper::laterpay_parse_url( $url );
            if ( empty( $url_parts['query'] ) ) {
                $query = $url_parts['query'] = '';
            } else {
                $query = $url_parts['query'];
            }
            if ( is_string( $data ) ) {
                $query .= '&' . $data;
            } else {
                $query .= '&' . http_build_query( $data, null, '&' );
            }
            $query = trim( $query, '&' );

            if ( empty( $url_parts['query'] ) ) {
                $url .= '?' . $query;
            } else {
                $url = str_replace( $url_parts['query'], $query, $url );
            }
        }

        return $url;
    }
}

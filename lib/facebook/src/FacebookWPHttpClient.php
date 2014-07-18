<?php

class FacebookWPHttpClient implements Facebook\FacebookHttpable {
    /**
     * Array of headers to be sent
     * 
     * @var array
     */
    protected $_headers = array();

    /**
     * The WP HTTP API response
     * 
     * @var array|WP_Error Array of results including HTTP headers or WP_Error if the request failed.
     */
    protected $_response;

    /**
     * The headers we want to send with the request
     *
      * @param string $key
     * @param string $value
     */
    public function addRequestHeader( $key, $value ) {
        $this->_headers[ $key ] = $value;
    }
    
    /**
     * The headers returned in the response
     *
     * @return array
     */
    public function getResponseHeaders() {
        if ( is_wp_error( $this->_response ) ) {
            return null;
        }

        return $this->_response['headers'];
    }
    
    /**
     * The HTTP status response code
     *
     * @return int
     */
    public function getResponseHttpStatusCode() {
        if ( is_wp_error( $this->_response ) ) {
            return null;
        }

        return $this->_response['response']['code'];
    }
    
    /**
     * The error message returned from the client
     *
     * @return string
     */
    public function getErrorMessage() {
        if ( is_wp_error( $this->_response ) ) {
            return $this->_response->get_error_message();
        }

        return null;
    }
    
    /**
     * The error code returned by the client
     *
     * @return int
     */
    public function getErrorCode() {
        if ( is_wp_error( $this->_response ) ) {
            return null;
        }
    }
    
    /**
     * Sends a request to the server
     *
     * @param string $url The endpoint to send the request to
     * @param string $method The request method
     * @param array  $parameters The key value pairs to be sent in the body
     *
     * @return string|boolean Raw response from the server or false on fail
     */
    public function send( $url, $method = 'GET', $parameters = array() ) {
        $args = array(
            'method'    => $method,
            'headers'   => $this->_headers,
            'body'      => $parameters
        );

        $this->_response = wp_remote_request( $url, $args );

        if ( is_wp_error( $this->_response ) ) {
            return false;
        }

        return $this->_response['body'];
    }
}

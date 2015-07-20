<?php
namespace UrbanAirship;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\NullHandler;

class WpAirship extends Airship {

    public $mock_response;

    /**
     * Send an authenticated request to the Urban Airship API. The request is
     * authenticated with the key and secret.
     *
     * @param string  $method      REST method for request
     * @param mixed   $body        Body of request, optional
     * @param string  $uri         URI for this request
     * @param string  $contentType Content type for the request, optional
     * @param int     $version     version # for API, optional, default is 3
     * @param mixed   $request     Request object for this operation (PushRequest, etc)
     *   optional
     * @return \Httpful\associative|string
     * @throws AirshipException
     */
    public function request( $method, $body, $uri, $contentType=null, $version=3, $request=null ) {
        // As a result of replacing Httpful/Request with WP HTTP,
        // We need to map WP_HTTP response object to Httpful/Request properties as a shim,
        // Action is not necessary but looks a bit cleaner
        add_action( 'http_api_debug', array( $this, 'set_mock_response_object' ), 10, 5 );

        $headers = array(
            'Authorization' => 'Basic ' . base64_encode( "{$this->key}:{$this->secret}" ),
            "Accept" => sprintf( self::VERSION_STRING, $version )
        ) ;

        if ( !is_null( $contentType ) ) {
            $headers["Content-type"] = $contentType;
        }
        $request = new \WP_Http;

        /**
         * Logger is disabled in production, so this won't do nothing unless WP_DEBUG is enabled
         *
         * @var [type]
         */
        $logger = UALog::getLogger();
        $logger->debug( "Making request", array(
                "method" => $method,
                "uri" => $uri,
                "headers" => $headers,
                "body" => $body ) );

        // Make a request (fires http_api_debug action that sets object property $mock_response)
        $response = $request->request( $uri,  array( 'method' => $method, 'body' => $body, 'headers' => $headers ) );

        // Check the response for wp_error (that's what WP HTTP throws when there was an issue with request)
        if ( is_wp_error( $response ) )
            return $response;

        // Check for "successful" WP HTTP request and see if UA returns any non-2xx response code
        if ( 300 <= $response['response']['code'] )
            throw AirshipException::fromResponse( $this->mock_response );

        $logger->debug( "Received response", array(
                "status" => $this->mock_response->code,
                "headers" => $this->mock_response->raw_headers,
                "body" => $this->mock_response->raw_body ) );

        // Return mock response object for any components of UA library that make requests
        return $this->mock_response;
    }

    /**
     * Action callback that maps WP_HTTP response object to Httpful/Request properties as a shim
     *
     * @param [type]  $response WP_HTTP repsonse
     * @param [type]  $context  always 'response'
     * @param [type]  $class    [description]
     * @param [type]  $args     [description]
     * @param [type]  $url      [description]
     */
    public function set_mock_response_object( $response, $context, $class, $args, $url  ) {
        $this->mock_response = new \stdClass;
        $this->mock_response->raw_body = is_wp_error( $response ) ? $response->get_error_message() : $response['body'];
        $this->mock_response->body = is_wp_error( $response ) ? $response->get_error_message() : json_decode( $response['body'] );
        $this->mock_response->code = is_wp_error( $response ) ? $response->get_error_code() : $response['response']['code'];
        $this->mock_response->headers = is_wp_error( $response ) ? array() : $response['headers'];
        $this->mock_response->raw_headers =  is_wp_error( $response ) ? array() : json_encode( $response['headers'] );

        return $this;
    }
}
/**
 * Disable Request Logging if WP_DEBUG is not enabled
 */
if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
    UALog::setLogHandlers( array( new NullHandler ) );
}

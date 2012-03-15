<?php
/**
 * WordPress Daylife API.
 *
 * @package Daylife
 * @subpackage API
 * @version 1.0
 * @author Pete Mall
 * @license GPL
 */
class WP_Daylife_API {
	var $access_key;
	var $shared_secret;
	var $source_filter_id;
	var $url;

	const protocol = 'jsonrest';
	const version = '4.10';

	public function __construct( $args ) {
		$this->access_key = $args['access_key'];
		$this->shared_secret = $args['shared_secret'];
		$this->source_filter_id = $args['source_filter_id'];
		$this->url = trailingslashit( $args['api_endpoint'] ) . self::protocol . '/publicapi/' . self::version . '/';
	}

	private function request( $call, $args = array() ) {
		$args['accesskey'] = $this->access_key;
		$args['signature'] = isset( $args['query'] ) ? $this->signature( $args['query'] ) : $this->signature( $args['content'] );
		$url = preg_match("~^(http)s?://~i", $this->url ) ? $this->url . $call : 'http://' . $this->url . $call;

		foreach ( $args as &$arg )
			$arg = rawurlencode( $arg );

		$response = wp_remote_get( add_query_arg( $args, $url ) );
		if ( 200 != wp_remote_retrieve_response_code( $response ) )
			return false;

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	public function search_getRelatedImages( $args = array() ) {
		$defaults = array(
			'source_filter_id' => $this->source_filter_id,
			'offset'           => 0,
			'limit'            => 8,
			'sort'             => 'relevance'
		);
		$response = $this->request( 'search_getRelatedImages', wp_parse_args( $args, $defaults ) );
		if ( $response )
		 	return $response->response->payload->image;

		return false;
	}

	public function content_getRelatedImages( $args ) {
		$defaults = array(
			'source_filter_id' => $this->source_filter_id,
			'offset'           => 0,
			'limit'            => 8,
			'sort'             => 'relevance'
		);
		$response = $this->request( 'content_getRelatedImages', wp_parse_args( $args, $defaults ) );
		if ( $response )
	 		return $response->response->payload->image;

		return false;
	}

	private function signature( $param ) {
		return md5( $this->access_key . $this->shared_secret . $param );
	}
}
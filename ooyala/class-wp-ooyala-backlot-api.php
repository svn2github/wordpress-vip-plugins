<?php
/**
 * WordPress Class for interfacing with the Ooyola Backlot API v2
 *
 * @package Ooyola
 * @subpackage API
 */
class WP_Ooyala_Backlot {
	var $partner_code;
	var $api_key;
	var $api_secret;

	public function __construct( $args ) {
		$this->partner_code = $args['partner_code'];
		$this->api_key = $args['api_key'];
		$this->api_secret = $args['api_secret'];
	}

	private function sign_request( $request, $params ) {
		$defaults = array(
			'api_key' => $this->api_key,
			'expires' => time() + 900,
		);
		$params = wp_parse_args( $params, $defaults );

		$signature = $this->api_secret . $request['method'] . $request['path'];
		ksort( $params ); 
		foreach ( $params as $key => $val )
			$signature .= $key . '=' . $val;

		$signature .= empty( $request['body'] ) ? '' : $request['body'];

		$signature = hash( 'sha256', $signature, true );
	    $signature = preg_replace( '#=+$#', '', trim( base64_encode( $signature ) ) );

		return $signature;
	}

	public function query( $params, $request = array(), $return = false ) {
		$default_request = array(
			'method' => 'GET',
			'path'   => '/v2/assets'
		);
		$default_params = array(
			'api_key' => $this->api_key,
			'expires' => time() + 900,
			'where'   => "status='live'",
			'limit'   => 8,
			'orderby' => 'created_at descending'
		);
		$params = wp_parse_args( $params, $default_params );
		$request = wp_parse_args( $request, $default_request );

		$params['signature'] = $this->sign_request( $request, $params );
		foreach ( $params as &$param )
			$param = rawurlencode( $param );

		$url = add_query_arg( $params, 'https://api.ooyala.com' . $request['path'] );

		$response = wp_remote_get( $url, array( 'timeout' => apply_filters( 'ooyala_http_request_timeout', 10 ) ) );

		if ( $return )
			return $response;
		if ( 200 == wp_remote_retrieve_response_code( $response ) )
			$this->render_popup( wp_remote_retrieve_body( $response ) );
	}

	private function render_popup( $response ) {
		$videos = json_decode( $response );

		if ( empty( $videos->items ) ) {
			_e( 'No videos found.', 'ooyalavideo' );
			return;
		}

		$output = '';
		if ( !empty( $videos->next_page ) ) {
			parse_str( urldecode( $videos->next_page ) );
			$next = '<a href="#' . $page_token . '" class="next page-numbers ooyala-paging">Next &raquo;</a>';
			$output .= '<div class="tablenav"><div class="tablenav-pages">' . $next . '</div></div>';
		}

		$output .= '<div id="ooyala-items">';
		foreach ( $videos->items as $video ) {
			$output .= '
			<div id="ooyala-item-' . esc_attr( $video->embed_code ) . '" class="ooyala-item">
				<div class="item-title"><a href="#" title="' . esc_attr( $video->embed_code ) .'" class="use-shortcode">' . esc_attr( $video->name ) .'</a></div>
				<div class="photo">
					<a href="#" title="' . esc_attr( $video->embed_code ) .'" class="use-shortcode"><img src="' . esc_url( $video->preview_image_url ) . '"></a>';

			if ( current_theme_supports( 'post-thumbnails' ) )
				$output .= '	<p><a href="#" class="use-featured">Use as featured image</a></p>';

			$output.='
				</div>
			</div>';
		}
		$output.='</div><div style="clear:both;"></div>';
		echo $output;
	}

	static function get_promo_thumbnail( $xml ) {
		
		$results = simplexml_load_string( $xml );
		if ( !$results )
			return new WP_Error( 'noresults', __( 'Malformed XML' , 'ooyalavideo'));

		return $results->promoThumbnail;
	}
}
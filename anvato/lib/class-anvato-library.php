<?php

/**
 * Get data about videos using the Anvato API.
 *
 * @package
 */
class Anvato_Library {

	/**
	 * printf()-friendly string for querying the Anvato API.
	 *
	 * The arguments are the MCP URL, a timestamp, a unique signature, the
	 * public key, and optional parameters.
	 *
	 * @see $this->build_request_url().
	 *
	 * @var string.
	 */
	private $api_request_url = '%s/api?ts=%d&sgn=%s&id=%s&%s';

	/**
	 * Allowed API calls
	 */
	private $api_methods = array(
		'categories' => 'list_categories',
		'live' => 'list_embeddable_channels',
		'playlist' => 'list_playlists',
		'vod' => 'list_videos',
	);
	
	/**
	 * The value of the plugin settings on instantiation.
	 *
	 * @var array.
	 */
	private $general_settings;

	/**
	 * The value of the stations settings.
	 *
	 * @var array.
	 */
	private $selected_station;

	/**
	 * The body of the XML request to send to the API.
	 *
	 * @todo Possibly convert to a printf()-friendly string for substituting
	 *     "list_groups" for "list_videos.""
	 *
	 * @var string.
	 */
	private $xml_body = "<?xml version=\"1.0\" encoding=\"utf-8\"?><request><type>%API_METHOD%</type><params></params></request>";

	/**
	 * Instance of this class.
	 *
	 * @var object.
	 */
	protected static $instance = null;

	/**
	 * Initialize the class.
	 */
	private function __construct() {
		$this->general_settings = Anvato_Settings()->get_mcp_options();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	/**
	 * Check whether the settings required for using the API are set.
	 *
	 * @return boolean.
	 */
	public function has_required_settings() {
		return !( empty( $this->general_settings ) ||
			false !== array_search( '', array( $this->general_settings['mcp']['url'],
				$this->selected_station['public_key'], $this->selected_station['private_key'] ), true
			)
		);
	}
	
	public function get_sel_station() {
		if(empty($this->selected_station)){
			return null;
		}
		
		return $this->selected_station;
	}
	

	/**
	 * Create the unique signature for a request.
	 *
	 * @see  $this->build_request_url() for detail about the timestamp.
	 *
	 * @param  int $time UNIX timestamp of the request.
	 * @return string.
	 */
	private function build_request_signature( $time ) {
		return base64_encode(
			hash_hmac( 'sha256', $this->xml_body . $time, $this->selected_station['private_key'], true )
		);
	}

	/**
	 * Set up the filtering conditions to use as part of a search of the library.
	 *
	 * @param array $args {
	 * 		@type string $lk video title search keyword.
	 * 		@type string $exp_date Used for video search, if set result includes videos that expire later than this date.
	 * 		@type int $page_no page offset, starting with 1.
	 * 		@type int $category_id MCP API filter for video list. Only videos with this category id will be returned.
	 * 		@type int $video_id MCP API filter for video list. Only video with this video id will be returned.
	 * 		@type int #program_id MCP API filter for videos in a program. Only videos with this program id will be returned
	 * 		@type bool $published_only MCP API filter for video list. Only published videos will be returned.
	 * }
	 * @return array.
	 */
	private function build_request_params( $args = array() ) {
		$params = array();
		
		if ( isset( $args['lk'] ) ) {
			$params['filter_by'][] = 'name';
			$params['filter_cond'][] = 'lk';
			$params['filter_value'][] = rawurlencode( sanitize_text_field( $args['lk'] ) );
		}
		
		if ( isset( $args['exp_date'] ) ) {
			$params['filter_by'][] = 'exp_date';
			$params['filter_cond'][] = 'ge';
			$params['filter_value'][] = rawurlencode( sanitize_text_field( $args['exp_date'] ) );
		}
		
		if ( isset( $args['page_no'] ) ) {
			$params['page_no'] = (int) $args['page_no'];
		}
		
		if ( isset( $args['category_id'] ) ) {
			$params['filter_by'][] = 'category_id';
			$params['filter_cond'][] = 'eq';
			$params['filter_value'][] = rawurlencode( sanitize_text_field( $args['category_id'] ) );
		}

		if ( isset( $args['video_id'] ) ) {
			$params['filter_by'][] = 'video_id';
			$params['filter_cond'][] = 'eq';
			$params['filter_value'][] = rawurlencode( sanitize_text_field( $args['video_id'] ) );
		}

		if ( isset( $args['program_id'] ) ) {
			$params['filter_by'][] = 'program_id';
			$params['filter_cond'][] = 'eq';
			$params['filter_value'][] = rawurlencode( sanitize_text_field( $args['program_id'] ) );
		}

		if ( isset( $args['published_only'] ) && $args['published_only'] ) {
			$params['filter_by'][] = 'published';
			$params['filter_cond'][] = 'eq';
			$params['filter_value'][] = 'true';
		}

		return $params;
	}

	/**
	 * Construct the URL to send to the API.
	 *
	 * @see  $this->api_request_url for detail about the URL.
	 * @see  $this->build_request_parameters() for allowed search parameters.
	 *
	 * @param array $params Search parameters.
	 * @param int $time The UNIX timestamp of the request. Passed to the
	 *     function because the same timestamp is needed more than once.
	 * @return string The URL after formatting with sprintf().
	 */
	private function build_request_url( $params = array(), $time ) {
		return sprintf(
			$this->api_request_url, 
			esc_url( $this->general_settings['mcp']['url'] ), $time, 
			rawurlencode( $this->build_request_signature( $time ) ), 
			$this->selected_station['public_key'], build_query( $params )
		);
	}

	/**
	 * Check whether the Anvato API reported an unsuccessful request.
	 *
	 * @param array $response The response array from wp_remote_get().
	 * @return boolean.
	 */
	private function is_api_error( $response ) {
		$xml = simplexml_load_string( wp_remote_retrieve_body( $response ) );

		if ( is_object( $xml ) ) {
			return 'failure' === $xml->result;
		} else {
			return true;
		}
	}

	/**
	 * Get the error message from the Anvato API during an unsuccessful request.
	 *
	 * @param array $response The response array from wp_remote_get().
	 * @return string The message.
	 */
	private function get_api_error( $response ) {
		$xml = simplexml_load_string( wp_remote_retrieve_body( $response ) );

		if ( is_object( $xml ) && !empty( $xml->comment ) ) {
			return sprintf( __( '"%s"', ANVATO_DOMAIN_SLUG ), esc_html( $xml->comment ) );
		} else {
			// Intentionally uncapitalized.
			return __( 'no error message provided', ANVATO_DOMAIN_SLUG );
		}
	}

	/**
	 * Request data from the Anvato API.
	 *
	 * @uses  vip_safe_wp_remote_get() if available.
	 * @see  $this->build_request_parameters() for allowed search parameters.
	 *
	 * @param array $params Search parameters.
	 * @return string|WP_Error String of XML of success, or WP_Error on failure.
	 */
	private function request( $params ) {
		if ( !$this->has_required_settings() ) {
			return new WP_Error(
				'missing_required_settings', 
				__( 'The MCP URL, Public Key, and Private Key settings are required.', ANVATO_DOMAIN_SLUG )
			);
		}

		$url = $this->build_request_url($params, time());
		$args = array('body' => $this->xml_body, 'timeout' => 10);
		$response = wp_remote_post( esc_url_raw( $url ), $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}
		
		if ( wp_remote_retrieve_response_code($response) === 200 ) {
			if ( $this->is_api_error( $response ) ) {
				return new WP_Error(
					'api_error', 
					sprintf( 
						__( '%s Please check your configuration parameters on Settings page.', ANVATO_DOMAIN_SLUG ), 
						$this->get_api_error( $response )
					)
				);
			}

			return $response;
		} 
		
		return new WP_Error(
			'request_unsuccessful', 
			__( 'There was an error contacting Anvato.', ANVATO_DOMAIN_SLUG )
		);
	}

	/**
	 * Search the library for videos.
	 *
	 * @see  $this->build_request_parameters() for allowed search parameters.
	 *
	 * @param array $args Search parameters.
	 * @param string $output_type Desired output type, 'xml' for raw API output.
	 * 
	 * @return array|WP_Error Array with SimpleXMLElements of any videos found, or WP_Error on failure.
	 */
	public function search( $args = array(), $output_type = 'clean' ) {
		if ( empty( $args['station'] ) ) {
			return new WP_Error(
				'missing_required_settings', 
				__( 'Please select station.', ANVATO_DOMAIN_SLUG )
			);
		}

		if ( !isset( $this->api_methods[$args['type']] ) || empty( $this->api_methods[$args['type']] ) ) {
			return new WP_Error(
				'missing_required_settings', 
				__( 'Unknow API call.', ANVATO_DOMAIN_SLUG )
			);
		}
  
		$api_method = $this->api_methods[$args['type']];
		
		foreach ( $this->general_settings['owners'] as $ow_item ) {
			if ( $args['station'] === $ow_item['id'] ) {
				$this->selected_station = $ow_item;
				break;
			}
		}

		if($api_method === 'list_videos') {
			$args['published_only'] = true;
		}

		$this->xml_body = str_replace( "%API_METHOD%", $api_method, $this->xml_body );

		$response = $this->request($this->build_request_params($args));
		if ( is_wp_error( $response ) ) {
			return $response;
		}
				
		$xml = simplexml_load_string( wp_remote_retrieve_body( $response ) );
		if ( !is_object( $xml ) ) {
			return new WP_Error(
				'parse_error', 
				__( 'There was an error processing the search results.', ANVATO_DOMAIN_SLUG )
			);
		}
		
		if ( $output_type === 'xml' ) {
			return $xml->params;
		}
		
		switch ( $api_method ) {

			case 'list_categories':
				return $xml->params->category_list->xpath("//category");

			case 'list_embeddable_channels':
				return $xml->params->channel_list->xpath("//channel");

			case 'list_playlists':
				return $xml->params->video_list->xpath("//playlist");

			case 'list_videos':
				return $xml->params->video_list->xpath("//video");

		}
	}

}

/**
 * Helper function to use the class instance.
 *
 * @return object.
 */
function Anvato_Library() {
	return Anvato_Library::get_instance();
}

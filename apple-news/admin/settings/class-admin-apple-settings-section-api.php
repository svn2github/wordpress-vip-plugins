<?php

/**
 * Describes a WordPress setting section
 *
 * @since 0.6.0
 */
class Admin_Apple_Settings_Section_API extends Admin_Apple_Settings_Section {

	/**
	 * Slug of the API settings section.
	 *
	 * @var string
	 * @access protected
	 */
	protected $slug = 'api-options';

	/**
	 * Constructor.
	 *
	 * @param string $page
	 */
	function __construct( $page ) {
		// Set the name
		$this->name =  __( 'API Settings', 'apple-news' );

		// Add the settings
		$this->settings = array(
			'api_key' => array(
				'label'   => __( 'API Key', 'apple-news' ),
				'type'    => 'string',
			),
			'api_secret' => array(
				'label'   => __( 'API Secret', 'apple-news' ),
				'type'    => 'password',
			),
			'api_channel' => array(
				'label'   => __( 'API Channel', 'apple-news' ),
				'type'    => 'string',
			),
			'api_autosync' => array(
				'label'   => __( 'Automatically publish to Apple News', 'apple-news' ),
				'type'    => array( 'yes', 'no' ),
			),
			'api_autosync_update' => array(
				'label'   => __( 'Automatically update Apple News', 'apple-news' ),
				'type'    => array( 'yes', 'no' ),
			),
		);

		// Add the groups
		$this->groups = array(
			'apple_news' => array(
				'label'       => __( 'Apple News API', 'apple-news' ),
				'description' => __( 'All of these settings are required for publishing to Apple News', 'apple-news' ),
				'settings'    => array( 'api_key', 'api_secret', 'api_channel', 'api_autosync', 'api_autosync_update' ),
			),
		);

		parent::__construct( $page );
	}

	/**
	 * Gets section info.
	 *
	 * @return string
	 * @access public
	 */
	public function get_section_info() {
		return sprintf(
			'%s <a target="_blank" href="https://developer.apple.com/news-publisher/">%s</a> %s.',
			__( 'Enter your Apple News credentials below. See', 'apple-news' ),
			__( 'the Apple News documentation', 'apple-news' ),
			__( 'for detailed information', 'apple-news' )
		);
	}

}

<?php

/**
 * Describes a WordPress setting section
 *
 * @since 0.6.0
 */
class Admin_Apple_Settings_Section_Advanced extends Admin_Apple_Settings_Section {

	/**
	 * Slug of the advanced settings section.
	 *
	 * @var string
	 * @access protected
	 */
	protected $slug = 'advanced-options';

	/**
	 * Constructor.
	 *
	 * @param string $page
	 */
	function __construct( $page ) {
		// Set the name
		$this->name =  __( 'Advanced Settings', 'apple-news' );

		// Add the settings
		$this->settings = array(
			'body_line_height' => array(
				'label'    => __( 'Body Line Height', 'apple-news' ),
				'type'     => 'float',
				'sanitize' => 'floatval',
			),
			'pullquote_line_height' => array(
				'label'   => __( 'Pull quote Line Height', 'apple-news' ),
				'type'    => 'float',
				'sanitize' => 'floatval',
			),
			'header_line_height' => array(
				'label'   => __( 'Heading Line Height', 'apple-news' ),
				'type'    => 'float',
				'sanitize' => 'floatval',
			),
			'component_alerts' => array(
				'label'   => __( 'Component Alerts', 'apple-news' ),
				'type'    => array( 'none', 'warn', 'fail' ),
				'description' => __( 'If a post has a component that is unsupported by Apple News, choose "none" to generate no alert, "warn" to provide an admin warning notice, or "fail" to generate a notice and stop publishing.', 'apple-news' ),
			),
		);

		// Add the groups
		$this->groups = array(
			'line_heights' => array(
				'label'       => __( 'Line Heights', 'apple-news' ),
				'settings'    => array( 'body_line_height', 'pullquote_line_height', 'header_line_height' ),
			),
			'alerts' => array(
				'label'       => __( 'Alerts', 'apple-news' ),
				'settings'    => array( 'component_alerts' ),
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
		return __( 'Delete values to restore defaults.', 'apple-news' );
	}
}

<?php

/**
 * Describes a WordPress setting section
 *
 * @since 0.6.0
 */
class Admin_Apple_Settings_Section_Developer_Tools extends Admin_Apple_Settings_Section {

	/**
	 * Slug of the developer tools section.
	 *
	 * @var string
	 * @access protected
	 */
	protected $slug = 'developer-tools';

	/**
	 * Constructor.
	 *
	 * @param string $page
	 */
	function __construct( $page ) {
		// Set the name
		$this->name =  __( 'Developer Tools', 'apple-news' );

		// Add the settings
		$this->settings = array(
			'apple_news_enable_debugging' => array(
				'label'   => __( 'Enable Debugging', 'apple-news' ),
				'type'    => array( 'no', 'yes' ),
			),
			'apple_news_admin_email' => array(
				'label'    		=> __( 'Administrator Email', 'apple-news' ),
				'type'     		=> 'text',
			),
		);

		// Add the groups
		$this->groups = array(
			'debugging_settings' => array(
				'label'       => __( 'Debugging Settings', 'apple-news' ),
				'settings'    => array( 'apple_news_enable_debugging', 'apple_news_admin_email' ),
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
		return __( 'If debugging is enabled, emails will be sent to an administrator for every publish, update or delete action with a detailed API response.', 'apple-news' );
	}
}

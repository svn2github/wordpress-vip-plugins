<?php
/**
 * Base plugin class with core plugin information and shared functionality
 * between frontend and backend plugin classes.
 *
 * @author  Federico Ramirez
 * @since   0.2.0
 */
class Apple_News {

	/**
	 * Plugin slug.
	 *
	 * @var string
	 * @access protected
	 */
	protected $plugin_slug = 'apple_news';

	/**
	 * Plugin domain.
	 *
	 * @var string
	 * @access protected
	 */
	protected $plugin_domain = 'apple-news';

	/**
	 * Option name for settings.
	 *
	 * @var string
	 * @access public
	 */
	public static $option_name = 'apple_news_settings';

	/**
	 * Plugin version.
	 *
	 * @var string
	 * @access protected
	 */
	protected $version = '1.1.4';

	/**
	 * Extracts the filename for bundling an asset.
	 * This functionality is used in a number of classes that do not have a common ancestor.
	 *
	 * @var string
	 * @access protected
	 */
	public static function get_filename( $path ) {
		// Remove any URL parameters.
		// This is important for sites using WordPress VIP or Jetpack Photon.
		$url_parts = parse_url( $path );
		if ( empty( $url_parts['path'] ) ) {
			return '';
		}

		// Get the filename
		$filename = basename( $url_parts['path'] );

		// Remove any spaces and return the filename
		return str_replace( ' ', '', $filename );
	}

	/**
	 * Attempt to migrate settings from an older version of this plugin
	 *
	 * @param Settings $settings
	 */
	public function migrate_settings( $settings ) {
		$migrated_settings = array();

		// For each potential value, see if the WordPress option exists.
		// If so, migrate its value into the new array format.
		// If it doesn't exist, just use the default value.
		foreach ( $settings->all() as $key => $default ) {
			$value = get_option( $key, $default );
			$migrated_settings[ $key ] = $value;
		}

		// Store these settings
		update_option( self::$option_name, $migrated_settings, 'no' );

		// Delete the options to clean up
		array_map( 'delete_option', array_keys( $migrated_settings ) );

		return $migrated_settings;
	}
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Abstract class to define/implement base methods for all module classes
 *
 * @since      1.0.0
 * @package    StackCommerce_WP
 * @subpackage StackCommerce_WP/includes
 */
abstract class StackCommerce_WP_Module {

	private static $instances = array();

	/**
	 * Render a template
	 *
	 * Allows parent/child themes to override the markup by placing the a file named basename( $default_template_path ) in their root folder,
	 * and also allows plugins or themes to override the markup by a filter. Themes might prefer that method if they place their templates
	 * in sub-directories to avoid cluttering the root folder. In both cases, the theme/plugin will have access to the variables so they can
	 * fully customize the output.
	 *
	 * @param  string $default_template_path The path to the template, relative to the plugin's `views` folder
	 * @param  array  $variables             An array of variables to pass into the template's scope, indexed with the variable name so that it can be extract()-ed
	 * @param  string $require               'once' to use require_once() | 'always' to use require()
	 * @return string
	*/
	protected static function render_template( $default_template_path = false, $variables = array(), $require = 'once' ) {
		do_action( 'stackcommerce_wp_render_template_pre', $default_template_path, $variables );

		$template_path = locate_template( basename( $default_template_path ) );

		if ( ! $template_path ) {
			$template_path = dirname( __DIR__ ) . '/views/' . $default_template_path;
		}

		$template_path = apply_filters( 'stackcommerce_wp_template_path', $template_path );

		if ( is_file( $template_path ) ) {
			// @codingStandardsIgnoreLine
			extract( $variables );
			ob_start();

			if ( 'always' === $require ) {
				require( $template_path );
			} else {
				require_once( $template_path );
			}

			$template_content = apply_filters( 'stackcommerce_wp_template_content', ob_get_clean(), $default_template_path, $template_path, $variables );
		} else {
			$template_content = '';
		}

		do_action( 'stackcommerce_wp_render_template_post', $default_template_path, $variables, $template_path, $template_content );

		return $template_content;
	}
}

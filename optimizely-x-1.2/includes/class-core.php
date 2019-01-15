<?php
/**
 * Optimizely X: Core class
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

namespace Optimizely_X;

/**
 * The core plugin class. Keeps track of the plugin version and slug, as well as
 * registering action and filter hooks used by the plugin.
 *
 * @since 1.0.0
 */
class Core {

	use Singleton;

	/**
	 * The current version of the plugin.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	const VERSION = '1.2.3';

	/**
	 * Initialize the objects that control the plugin's functionality.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function setup() {
		Admin::instance();
		AJAX_Config::instance();
		AJAX_Metabox::instance();
		AJAX_Results::instance();
		Frontend::instance();
		I18N::instance();
	}
}

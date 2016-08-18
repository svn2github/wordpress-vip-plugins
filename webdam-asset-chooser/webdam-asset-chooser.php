<?php
/**
 Plugin Name: WebDAM Asset Chooser
 Plugin URI: http://webdam.com/
 Description: Import WebDAM assets into WordPress.
 Version: 1.2.1
 Author: WebDAM, PMC, Amit Gupta, James Mehorter
 Author URI: http://webdam.com/
*/

namespace Webdam;

define( 'WEBDAM_PLUGIN_VERSION', '1.2.1' );
define( 'WEBDAM_PLUGIN_DIR', __DIR__ );
define( 'WEBDAM_PLUGIN_SLUG', 'webdam-asset-chooser' );
define( 'WEBDAM_PLUGIN_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );

require_once __DIR__ . '/includes/class-core.php';

require_once __DIR__ . '/includes/helpers.php';

$settings = get_option( 'webdam_settings' );

// Only load up the API class if API usage
// has been enabled in Settings > WebDAM
if ( ! empty( $settings['enable_api'] ) ) {
	require_once __DIR__ . '/includes/class-api.php';
}

require_once __DIR__ . '/includes/class-admin-settings.php';
require_once __DIR__ . '/includes/class-asset-chooser.php';

// EOF
<?php
/*
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Description: Sell digital content with LaterPay. It allows super easy and fast payments from as little as 5 cent up to 149.99 Euro at a 15% fee and no fixed costs.
 * Author: LaterPay GmbH, Mihail Turalenka and Aliaksandr Vahura
 * Version: 2.4.1
 * Author URI: https://laterpay.net/
 * Textdomain: laterpay
 * Domain Path: /languages
 */

// Kick-off.
// Initialize plugin on `init` hook instead of `plugins_loaded`, so that it works when plugin is loaded via theme.
// The priority is set to '1' so that it executes before,
// CPT registration for ( time pass and subscription ) which is on same hook.
add_action( 'init', 'laterpay_init', 1 );


if ( ! laterpay_check_is_vip() ) {
    register_activation_hook( __FILE__, 'laterpay_activate' );
    register_deactivation_hook( __FILE__, 'laterpay_deactivate' );
}

/**
 * Callback for starting the plugin.
 *
 * @wp-hook plugins_loaded
 *
 * @return void
 */
function laterpay_init() {
    laterpay_before_start();

    if ( laterpay_check_is_vip() && is_admin()) {
        if( false !== get_option( 'laterpay_plugin_version' ) ) {
            laterpay_activate();
        }
    }

    $config   = laterpay_get_plugin_config();
    $laterpay = new LaterPay_Core_Bootstrap( $config );

    try {
        $laterpay->run();
    } catch ( Exception $e ) {
        unset( $e );
    }
}

/**
 * Callback for activating the plugin.
 *
 * @wp-hook register_activation_hook
 *
 * @return void
 */
function laterpay_activate() {
    laterpay_before_start();
    $config     = laterpay_get_plugin_config();
    $laterpay   = new LaterPay_Core_Bootstrap( $config );

    laterpay_event_dispatcher()->dispatch( 'laterpay_activate_before' );
    $laterpay->activate();
    laterpay_event_dispatcher()->dispatch( 'laterpay_activate_after' );
}

/**
 * Callback for deactivating the plugin.
 *
 * @wp-hook register_deactivation_hook
 *
 * @return void
 */
function laterpay_deactivate() {
    laterpay_before_start();
    $config     = laterpay_get_plugin_config();
    $laterpay   = new LaterPay_Core_Bootstrap( $config );

    laterpay_event_dispatcher()->dispatch( 'laterpay_deactivate_before' );
    $laterpay->deactivate();
    laterpay_event_dispatcher()->dispatch( 'laterpay_deactivate_after' );
}

/**
 * Get the plugin settings.
 *
 * @return LaterPay_Model_Config
 */
function laterpay_get_plugin_config() {
    // check, if the config is in cache -> don't load it again.
    $config = wp_cache_get( 'config', 'laterpay' );
    if ( is_a( $config, 'LaterPay_Model_Config' ) ) {
        return $config;
    }

    $config = new LaterPay_Model_Config();

    // plugin default settings for paths and directories
    $config->set( 'plugin_dir_path',    plugin_dir_path( __FILE__ ) );
    $config->set( 'plugin_file_path',   __FILE__ );
    $config->set( 'plugin_base_name',   plugin_basename( __FILE__ ) );
    $config->set( 'plugin_url',         plugins_url( '/', __FILE__ ) );
    $config->set( 'view_dir',           plugin_dir_path( __FILE__ ) . 'views/' );
    $config->set( 'cache_dir',          plugin_dir_path( __FILE__ ) . 'cache/' );

    $upload_dir = wp_upload_dir();
    $config->set( 'log_dir',            $upload_dir['basedir'] . '/laterpay_log/' );
    $config->set( 'log_url',            $upload_dir['baseurl'] . '/laterpay_log/' );

    $plugin_url = $config->get( 'plugin_url' );
    $config->set( 'css_url',            $plugin_url . 'built_assets/css/' );
    $config->set( 'js_url',             $plugin_url . 'built_assets/js/' );
    $config->set( 'image_url',          $plugin_url . 'built_assets/img/' );

    // plugin modes
    $config->set( 'is_in_live_mode',    (bool) get_option( 'laterpay_plugin_is_in_live_mode', false ) );

    $config->set( 'script_debug_mode',  defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );

    // plugin headers
    $plugin_headers = get_file_data(
        __FILE__,
        array(
            'plugin_name'       => 'Plugin Name',
            'plugin_uri'        => 'Plugin URI',
            'description'       => 'Description',
            'author'            => 'Author',
            'version'           => 'Version',
            'author_uri'        => 'Author URI',
            'textdomain'        => 'Textdomain',
            'text_domain_path'  => 'Domain Path',
        )
    );
    $config->import( $plugin_headers );

    /**
     * LaterPay API endpoints and API default settings depends from region.
     */
    $config->import( LaterPay_Helper_Config::get_regional_settings() );

    /**
     * Use page caching compatible mode.
     *
     * Set this to true, if you are using a caching solution like WP Super Cache that caches entire HTML pages;
     * In compatibility mode the plugin renders paid posts without the actual content so they can be cached as static
     * files and then uses an Ajax request to load either the preview content or the full content,
     * depending on the current visitor
     *
     * @var boolean $caching_compatible_mode
     *
     * @return boolean $caching_compatible_mode
     */
    $config->set( 'caching.compatible_mode', get_option( 'laterpay_caching_compatibility' ) );

    $enabled_post_types = get_option( 'laterpay_enabled_post_types' );

    // content preview settings
    $content_settings = array(
        'content.auto_generated_teaser_content_word_count'  => get_option( 'laterpay_teaser_content_word_count' ),
        'content.preview_percentage_of_content'             => get_option( 'laterpay_preview_excerpt_percentage_of_content' ),
        'content.preview_word_count_min'                    => get_option( 'laterpay_preview_excerpt_word_count_min' ),
        'content.preview_word_count_max'                    => get_option( 'laterpay_preview_excerpt_word_count_max' ),
        'content.enabled_post_types'                        => $enabled_post_types ? $enabled_post_types : array(),
    );
    $config->import( $content_settings );

    // cache the config
    wp_cache_set( 'config', $config, 'laterpay' );

    return $config;
}

/**
 * Run before plugins_loaded, activate_laterpay, and deactivate_laterpay, to register our autoload paths.
 *
 * @return void
 */
function laterpay_before_start() {
    try {
        $dir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;

        // clean plugin cache to prevent persistent caching
        laterpay_clean_plugin_cache();

        if ( ! class_exists( 'LaterPay_Autoloader' ) ) {
            require_once( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'laterpay-load.php' );
        }

        LaterPay_AutoLoader::register_namespace( $dir . 'application', 'LaterPay' );
        LaterPay_AutoLoader::register_directory( $dir . 'vendor' . DIRECTORY_SEPARATOR . 'laterpay' . DIRECTORY_SEPARATOR . 'laterpay-client-php' );
    } catch ( Exception $e ) {
        unset( $e );
        // deactivate laterpay plugin
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}

/**
 * Clear plugin cache.
 *
 * @return void
 */
function laterpay_clean_plugin_cache() {
    wp_cache_delete( 'config', 'laterpay' );
}

/**
 * Alias for the LaterPay Event Dispatcher
 *
 * @return LaterPay_Core_Event_Dispatcher
 */
function laterpay_event_dispatcher() {
    return LaterPay_Core_Event_Dispatcher::get_dispatcher();
}

/**
 * Check if current environment is `VIP` or not.
 *
 * @return bool returns true if current site is available on VIP, otherwise false.
 */
function laterpay_check_is_vip_classic() {
    if ( defined( 'IS_WPCOM' ) && IS_WPCOM ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Check if current environment is `VIP-GO` or not.
 *
 * @return bool returns true if current site is available on VIP-GO, otherwise false
 */
function laterpay_check_is_vip() {
    if ( defined( 'LATERPAY_IS_VIP_DEBUG' ) && LATERPAY_IS_VIP_DEBUG ) { // Setting WPCOM_IS_VIP_ENV in local won't work.
        return true;
    }
    if ( defined( 'WPCOM_IS_VIP_ENV' ) && true === WPCOM_IS_VIP_ENV ) {
        return true;
    } else {
        return false;
    }
}

/**
 * Check if env is vip go.
 *
 * @return bool
 */
function laterpay_is_vip_go() {
    return ( laterpay_check_is_vip() && ( ! laterpay_check_is_vip_classic() ) );
}

/**
 * Checks whether the migration is completed or not.
 *
 * @return bool
 */
function laterpay_is_migration_complete(){
    return get_option( 'laterpay_data_migrated_to_cpt' ) !== false ;
}

/**
 * Laterpay display attributes.
 *
 * @param array $args arguments.
 * @param array $whitelisted_keys default params.
 */
function laterpay_whitelisted_attributes( $args, $whitelisted_keys ) {
    $whitelisted_keys = array_flip( $whitelisted_keys );
    $new_args = array_intersect_key( $args, $whitelisted_keys );
    foreach ( $new_args as $key => $value ) {
        echo $key . '="' . esc_attr( $value ) . '" '; // phpcs:ignore
    }
}

<?php

/**
 * LaterPay core view.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Core_View
{
    /**
     * Contains all settings for the plugin.
     *
     * @var LaterPay_Model_Config
     */
    protected $config;

    /**
     * Variables for substitution in templates.
     *
     * @var array
     */
    protected $variables = array();

    /**
     * @param LaterPay_Model_Config $config
     */
    public function __construct( $config = null ) {
        $this->config = ( $config && $config instanceof LaterPay_Model_Config ) ? $config : laterpay_get_plugin_config();
        // assign the config to the views
        $this->assign( 'config', $this->config );
        $this->initialize();
    }

    /**
     * Refresh config
     *
     * @return void
     */
    protected function refresh_config() {
        laterpay_clean_plugin_cache();

        // set new config and update assignation
        $this->config = laterpay_get_plugin_config();
        $this->assign( 'config', $this->config );
    }

    /**
     * Function which will be called on constructor and can be overwritten by child class.
     *
     * @return void
     */
    protected function initialize() {}

    /**
     * Load all assets on boot-up.
     *
     * @return void
     */
    public function load_assets() {}

    /**
     * Render HTML file.
     *
     * @param string $file file to get HTML string
     * @param string $view_dir view directory
     *
     * @return void
     */
    public function render( $file, $view_dir = null, $is_loop = false ) {
        foreach ( $this->variables as $key => $value ) {
            ${$key} = $value;
        }

        $view_dir  = isset( $view_dir ) ? $view_dir : $this->config->get( 'view_dir' );
        $view_file = $view_dir . $file . '.php';
        if ( ! file_exists( $view_file ) ) {
            return;
        }

        if ( true === $is_loop ) {
            include $view_file; // phpcs:ignore
        } else {
            include_once $view_file; // phpcs:ignore
        }

    }

    /**
     * Assign variable for substitution in templates.
     *
     * @param string $variable name variable to assign
     * @param mixed  $value    value variable for assign
     *
     * @return void
     */
    public function assign( $variable, $value ) {
        $this->variables[ $variable ] = $value;
    }

    /**
     * Get HTML from file.
     *
     * @param string $file file to get HTML string
     * @param string $view_dir  view directory
     *
     * @return string $html html output as string
     */
    public function get_text_view( $file, $view_dir = null ) {
        foreach ( $this->variables as $key => $value ) {
            ${$key} = $value;
        }

        $view_dir  = isset( $view_dir ) ? $view_dir : $this->config->get( 'view_dir' );
        $view_file = $view_dir . $file . '.php';
        if ( ! file_exists( $view_file ) ) {
            return '';
        }

        ob_start();
        include $view_file;      // phpcs:ignore
        $thread = ob_get_contents();
        ob_end_clean();
        $html = $thread;

        $this->init_assignments();

        return $html;
    }

    protected function init_assignments() {
        $this->variables = array();
        // assign the config to the views
        $this->assign( 'config', $this->config );
    }
}


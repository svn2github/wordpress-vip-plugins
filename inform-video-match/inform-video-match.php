<?php

/**
 *  Inform Video Match
 *
 *  Plugin Name: Inform Video Match
 *  Plugin URI: http://control.newsinc.com/
 *  Description: The Inform Video Match plugin enables you to easily embed videos from the Inform video library into your WordPress post.
 *  Version: 1.3.2
 *  Author: Inform, Inc. <wordpress@inform.com>
 *  Author URL: http://www.newsinc.com/
 *  Text Domain: inform-plugin
 *  Domain Path: /languages
 *  License: GPL-2.0+
 *
 * @author    Inform Inc. and its contributors
 * @license   GPL-2.0+
 * @copyright 2015, Inform, Inc.
 */

 // If this file is called directly, abort.
 if (!defined('WPINC')) {
     die;
 }

 /**
  * Defining constants for plugin (namespaced with NDN).
  */
 function ndn_define_constants()
 {
    // Assets Directory for CSS, JS & Images
    preg_match('/\/wp-content.+$/', dirname(__FILE__), $matches);
    $ndn_plugin_assets = site_url().$matches[0];
    define('NDN_PLUGIN_DIR', $ndn_plugin_assets);
 }

  /**
   * Also maintains the unique identifier of this plugin as well as the current
   * version of the plugin.
   */
  class NDN_Plugin
  {
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @var NDN_Plugin_Loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @var string The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @var string The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     */
    public function __construct()
    {
        $this->plugin_name = 'ndn_plugin';
        $this->version = '1.3.2';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - NDN_Plugin_Loader. Orchestrates the hooks of the plugin.
     * - NDN_Plugin_i18n. Defines internationalization functionality.
     * - NDN_Plugin_Admin. Defines all hooks for the admin area.
     * - NDN_Plugin_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     */
    private function load_dependencies()
    {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(__FILE__).'includes/ndn_plugin_loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(__FILE__).'includes/ndn_plugin_i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(__FILE__).'includes/ndn_plugin_admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(__FILE__).'includes/ndn_plugin_public.php';

        $this->loader = new NDN_Plugin_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the NDN_Plugin_i18n class in order to set the domain and to register the hook
     * with WordPress.
     */
    private function set_locale()
    {
        $plugin_i18n = new NDN_Plugin_i18n();
        $plugin_i18n->set_domain( 'ndn_admin' );

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new NDN_Plugin_Admin($this->get_plugin_name(), $this->get_version());

        if (!$plugin_admin::$initiated) {
            $plugin_admin::$initiated = true;

            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'post_page_enqueue_stylesheet');
            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
            $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'post_page_enqueue_scripts');

            $this->loader->add_action('admin_menu', $plugin_admin, 'create_plugin_login');
            $this->loader->add_action('admin_menu', $plugin_admin, 'create_plugin_menu');
            $this->loader->add_action('admin_menu', $plugin_admin, 'register_custom_modal_page');
            $this->loader->add_action('admin_menu', $plugin_admin, 'register_search_results_page');

            $this->loader->add_action('admin_notices', $plugin_admin, 'notify_user_for_credentials');
            $this->loader->add_action('admin_notices', $plugin_admin, 'notify_user_for_configuration');

            $this->loader->add_action('init', $plugin_admin, 'allow_additional_img_attributes');
            $this->loader->add_filter('tiny_mce_before_init', $plugin_admin, 'allow_tinymce_additional_img_attributes');

            $this->loader->add_action('init', $plugin_admin, 'submit_client_information');
            $this->loader->add_action('init', $plugin_admin, 'save_plugin_settings');
            $this->loader->add_action('init', $plugin_admin, 'submit_search_query');

            // Media Button
            $this->loader->add_action('media_buttons', $plugin_admin, 'add_media_button_wizard', 12);

            // AJAX requests
            $this->loader->add_action('wp_ajax_set_featured_image', $plugin_admin, 'set_featured_image');
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     */
    private function define_public_hooks()
    {
        $plugin_public = new NDN_Plugin_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('wp_head', $plugin_public, 'ndn_plugin_hook_embed');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return string The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return NDN_Plugin_Loader Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return string The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
  }

 /**
  * Begins execution of the plugin.
  *
  * Since everything within the plugin is registered via hooks,
  * then kicking off the plugin from this point in the file does
  * not affect the page life cycle.
  */
 function run_ndn_plugin()
 {
     $plugin = new NDN_Plugin();
     $plugin->run();
 }

// Run the plugin
 run_ndn_plugin();
 ndn_define_constants();

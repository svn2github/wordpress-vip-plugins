<?php
/**
 *  Inform Video Match
 *
 *  Plugin Name: Inform Video Match
 *  Plugin URI: http://control.newsinc.com/
 *  Description: The Inform Video Match plugin enables you to easily embed videos from the Inform video library into your WordPress post.
 *  Version: 1.5.2
 *  Author: Inform, Inc. <wordpress@inform.com>
 *  Author URL: http://www.inform.com/
 *  Text Domain: inform-plugin
 *  Domain Path: /languages
 *  License: GPL-2.0+
 *
 * @author    Inform, Inc. and its contributors
 * @license   GPL-2.0+
 * @copyright 2015, Inform, Inc.
 */
function load_custom_wp_admin_style() {
  wp_register_style( 'custom_wp_admin_css', NDN_PLUGIN_DIR . 'css/jquery-ui.css', false, '1.0.0' );
  wp_enqueue_style( 'custom_wp_admin_css' );
  wp_enqueue_script('jquery-ui-datepicker');
}
add_action( 'admin_enqueue_scripts', 'load_custom_wp_admin_style' );

// If this file is called directly, abort.
if (!defined('WPINC')) {
   wp_die();
}

if ( !function_exists('wpcom_is_vip') ) {
  define( 'NOT_VIP', true );
} else if ( wpcom_is_vip() ) {
  define( 'NOT_VIP', false );
} else {
  define( 'NOT_VIP', true );
}

if ( NOT_VIP ) {
  /**
   * The code that runs during plugin activation.
   */
  function activate_ndn_plugin()
  {
    // Nothing to activate here
  }
  /**
   * The code that runs during plugin deactivation.
   */
  function deactivate_ndn_plugin()
  {
      // Delete user Client information
     delete_option('ndn_client_id');
     delete_option('ndn_client_secret');
     // Delete Tokens
     delete_option('ndn_access_token');
     delete_option('ndn_refresh_token');
     // Settings Options
     delete_option('ndn_default_tracking_group');
     delete_option('ndn_default_div_class');
     delete_option('ndn_default_site_section');
     delete_option('ndn_default_width');
     delete_option('ndn_default_video_position');
     delete_option('ndn_default_start_behavior');

     // Search Options and Results
     delete_option('ndn_search_query');
     delete_option('ndn_search_results');
  }

  register_activation_hook(__FILE__, 'activate_ndn_plugin');
  register_deactivation_hook(__FILE__, 'deactivate_ndn_plugin');

  
}



/**
* Defining constants for plugin (namespaced with NDN).
*/
function ndn_define_constants()
{
  if ( NOT_VIP ) {
    // Assets Directory for CSS, JS & Images
    define( 'NDN_PLUGIN_DIR', plugin_dir_url( __FILE__ ) );
  } else {
    preg_match( '/\/wp-content.+$/', dirname(__FILE__), $matches );
    $ndn_plugin_assets = site_url().$matches[0];
    define( 'NDN_PLUGIN_DIR', $ndn_plugin_assets );
  }
  define( 'NDN_OAUTH_API', 'https://oauth.newsinc.com' );
  define( 'NDN_SEARCH_API', 'https://public-search-api.newsinc.com' );
  
}




// Add Shortcode
function embed_text_shortcode( $atts , $content = null ) {

  return '<script type="text/javascript">var _informq = _informq || []; _informq.push(["embed"]);</script>';

}
add_shortcode( 'embedScript', 'embed_text_shortcode' );




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
      $this->plugin_name = 'inform_plugin';
      $this->version = '1.5.2';

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
      $plugin_i18n->set_domain($this->get_plugin_name());

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

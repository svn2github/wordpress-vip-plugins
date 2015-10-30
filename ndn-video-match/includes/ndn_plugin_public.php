<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks for
 * enqueuing the public-specific stylesheet and JavaScript.
 *
 * @author      Inform, Inc. <wordpress@inform.com>
 */
class NDN_Plugin_Public
{
    /**
     * The ID of this plugin.
     *
     * @var string The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @var string The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $plugin_name The name of the plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, NDN_PLUGIN_DIR.'/css/ndn_plugin_public.css', array(), $this->version, 'all');
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, NDN_PLUGIN_DIR.'/js/ndn_plugin_public.js', array('jquery'), $this->version, false);
    }

    /**
     * Adds NDN's embed.js code in the head tag of the public pages HTML of any wordpress page.
     *
     * @return string Script tag for pointing to the embed.js source
     */
    public function ndn_plugin_hook_embed()
    {
        echo '<script type="text/javascript" src="//launch.newsinc.com/js/embed.js" id="_nw2e-js"></script>';
    }
}

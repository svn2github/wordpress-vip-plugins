<?php
/*
 * Security check
 * Exit if file accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
class PlaybuzzOptions {
    private $options_name = 'playbuzz';
    private $options_default = array(
        // General
        'key'               => 'default',
        'pb_user'           => '',
        'pb_channel_id'     => '',
        // items
        'jshead'			=> '1',
        'info'              => '1',
        'shares'            => '1',
        'comments'          => '0',
        'recommend'         => '1',
        'margin-top'        => '0',
        'embeddedon'        => 'content',
        'locale'            => 'en-US',
        //experiment-mode
        'experiment-mode'   => '0',
        // Recommendations
        'active'            => 'false',
        'show'              => 'footer',
        'view'              => 'large_images',
        'items'             => '3',
        'links'             => 'https://www.playbuzz.com',
        'section-page'		=> '',
        // Tags
        'tags-mix'          => '1',
        'tags-fun'          => '',
        'tags-pop'          => '',
        'tags-geek'         => '',
        'tags-sports'       => '',
        'tags-editors-pick' => '',
        'more-tags'         => '',
    );
    public function __construct() {
        //set default options case no options set
        //should run one time.
        if ( ! $this -> get_options() ) {
            $this -> set_default_options();
        }
    }
    /**
     * get options
     * @return mixed
     */
    public function get_options() {
        return get_option( $this -> options_name );
    }
    /**
     * set default options
     */
    function set_default_options() {
        // Set Default values
        $options_default = $this -> options_default;
        $options_name = $this -> options_name;
        // Set API Key
        if ( 'default' == $options_default['key'] ) {
            // Extract host domain
            $domain = wp_parse_url( home_url(), PHP_URL_HOST );
            // Remove "www." from the domain
            $api = str_replace( 'www.', '', $domain );
            // Set API
            $options_default['key'] = $api;
        }
        // Update options on database
        update_option( $options_name, $options_default );
    }
}
new PlaybuzzOptions();

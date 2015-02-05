<?php

//Disallow direct access to this file
if (!defined('LFAPPS__PLUGIN_PATH'))
    die('Bye');

if (!class_exists('LFAPPS_Blog_Admin')) {

    class LFAPPS_Blog_Admin {

        private static $initiated = false;

        public static function init() {
            if (!self::$initiated) {
                self::$initiated = true;
                self::init_hooks();
            }
        }

        /**
         * Initialise WP hooks
         */
        private static function init_hooks() {
            add_action('admin_menu', array('LFAPPS_Blog_Admin', 'init_admin_menu'));
            add_action('admin_enqueue_scripts', array('LFAPPS_Blog_Admin', 'load_resources'));
        }

        /**
         * Initialise admin menu items
         */
        public static function init_admin_menu() {
            add_submenu_page('livefyre_apps', 'LiveBlog', 'LiveBlog', "manage_options", 'livefyre_apps_blog', array('LFAPPS_Blog_Admin', 'menu_blog'));
        }

        /**
         * Add assets required by Livefyre Apps Admin section
         */
        public static function load_resources() {
            if ( get_option('liveyfre_domain_name', '' ) == '' || get_option( 'liveyfre_domain_name') == 'livefyre.com' ) {
                $source_url = 'http://zor.livefyre.com/wjs/v3.0/javascripts/livefyre.js';    
            }
            else {
                $source_url = 'http://zor.'
                    . ( 1 == get_option('livefyre_apps-livefyre_environment', '0' ) ?  "livefyre.com" : 't402.livefyre.com' )
                    . '/wjs/v3.0/javascripts/livefyre.js';
            }
            wp_enqueue_script( 'livefyre-js', esc_url( $source_url ) );
        }

        /**
         * Run LiveBlog page
         */
        public static function menu_blog() {
            
            LFAPPS_View::render('general', array(), 'blog');
        }
    }

}
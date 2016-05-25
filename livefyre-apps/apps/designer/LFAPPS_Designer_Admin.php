<?php

//Disallow direct access to this file
if (!defined('LFAPPS__PLUGIN_PATH'))
    die('Bye');

if (!class_exists('LFAPPS_Designer_Admin')) {
    class LFAPPS_Designer_Admin {

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
            add_action('admin_menu', array('LFAPPS_Designer_Admin', 'init_admin_menu'));
        }

        /**
         * Initialise admin menu items
         */
        public static function init_admin_menu() {
            add_submenu_page('livefyre_apps', 'Visualization Apps', 'Visualization Apps', "manage_options", 'livefyre_apps_designer', array('LFAPPS_Designer_Admin', 'menu_designer'));
        }

        /**
         * Run LiveBlog page
         */
        public static function menu_designer() {            
            LFAPPS_View::render('general', array(), 'designer');
        }
    }
}
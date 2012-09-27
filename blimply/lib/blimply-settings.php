<?php
class Blimply_Settings {

    private $settings_api;

    function __construct() {
        $this->settings_api = WeDevs_Settings_API::getInstance();

        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {
        add_options_page( __( 'Blimply Settings', 'blimply' ), __( 'Blimply Settings', 'blimply' ), 'manage_options', 'blimply_settings', array( $this, 'plugin_page' ) );
        add_options_page( __( 'Urban Airship Tags', 'blimply' ), __( 'Urban Airship Tags', 'blimply' ), 'manage_options', 'edit-tags.php?taxonomy=blimply_tags' );
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id' => 'urban_airship',
                'title' => __( 'Urban Airship Settings', 'blimply' )
            ),

        );
        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $settings_fields = array(
            'urban_airship' => array(
                array(
                    'name' => BLIMPLY_PREFIX . '_name',
                    'label' => __( 'Urban Airship Application Slug!', 'blimply' ),
                    'desc' => __( 'Text input description', 'wedevs' ),
                    'type' => 'text',
                    'default' => 'Title'
                ),
                array(
                    'name' => BLIMPLY_PREFIX . '_name',
                    'label' => __( 'Urban Airship Application Slug!', 'blimply' ),
                    'desc' => __( 'Something like my-test-app.', 'blimply' ),
                    'type' => 'text',
                    'std' => __( 'my-blimply', 'blimply' ),
                    'class'=> 'nohtml'
                ),
                array(
                    'name' => BLIMPLY_PREFIX . '_app_key',
                    'label'=> __( 'Application API Key', 'blimply' ),
                    'desc'=> __( '22 character long app key( like SYk74m98TOiUhHHHHb5l_Q.', 'blimply' ),
                    'type'=> 'text',
                    'std' => __( 'my-blimply', 'blimply' ),
                    'class'=> 'nohtml'
                ),
                array(
                    'name' => BLIMPLY_PREFIX . '_app_secret',
                    'label'=> __( 'Application Master Secret', 'blimply' ),
                    'desc'=> __( '22 character long app master secret( like SYk74m98TOiUhHHHHb5l_Q.', 'blimply' ),
                    'type'=> 'text',
                    'std' => __( 'my-blimply', 'blimply' ),
                    'class'=> 'nohtml'
                ),
            ),
        );

        return $settings_fields;
    }

    function plugin_page() {
        echo '<div class="wrap">';
        settings_errors();

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo '</div>';
    }

    /**
     * Get all the pages
     *
     * @return array page names with key value pairs
     */
    function get_pages() {
        $pages = get_pages();
        $pages_options = array();
        if ( $pages ) {
            foreach ( $pages as $page ) {
                $pages_options[$page->ID] = $page->post_title;
            }
        }

        return $pages_options;
    }

}

$settings = new Blimply_Settings;

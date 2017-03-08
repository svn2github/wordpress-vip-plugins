<?php

class Qmerce_Settings
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_enqueue_scripts', array( $this, 'addApesterSettingsStyles'  ) );
        add_action( 'admin_menu', array( $this, 'addPluginPage' ) );
        add_action( 'admin_init', array( $this, 'pageInit' ) );
    }
    
    public function addApesterSettingsStyles() {
        wp_register_style( 'apester_settings_page_css', plugins_url( '/public/css/apester_settings.css', QMERCE__PLUGIN_FILE ) );
        wp_enqueue_style( 'apester_settings_page_css' );
    }

    /*
     * Get option name
     */
    public function get_option_name() {
        return 'qmerce-settings-admin';
    }

    /**
     * Add options page
     */
    public function addPluginPage()
    {
        // This page will be under "Settings"
        add_options_page(
            'Apester Settings',
            'Apester Settings',
            'manage_options',
            'qmerce-settings-admin',
            array( $this, 'createAdminPage' )
        );
    }

    /**
     * Options page callback
     */
    public function createAdminPage()
    {
        // Set class property
        $this->options = get_option( 'qmerce-settings-admin' );

        include( QMERCE_PLUGIN_DIR . 'views/settings.tpl.php' );
    }

    /**
     * Register and add settings
     */
    public function pageInit()
    {
        register_setting(
            'qmerce-settings-fields', // Option group
            'qmerce-settings-admin', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Authorization Settings', // Title
            array( $this, 'printSectionInfo' ), // Callback
            'qmerce-settings-admin' // Page
        );

        add_settings_field(
            'auth_token',
            'Apester authorization token',
            array( $this, 'authTokenCallback' ),
            'qmerce-settings-admin',
            'setting_section_id'
        );

        add_settings_field(
            'helper_info',
            'Where do I find my token?',
            array( $this, 'printHelperInfo' ),
            'qmerce-settings-admin',
            'setting_section_id'
        );

        add_settings_field(
            'post_types',
            'Post types for admin box',
            array( $this, 'postTypesCb' ),
            'qmerce-settings-admin',
            'setting_section_id'
        );

        add_settings_field(
            'automation_post_types',
            'Post Types with automated Apester interactive widget below the main content',
            array( $this, 'automationPostTypeCb' ),
            'qmerce-settings-admin',
            'setting_section_id'
        );
    }

    /**
     * Retrieves available post types
     * @return array
     */
    private function getPostTypes()
    {
        return get_post_types( array( 'show_in_menu' => true ), 'objects' );
    }

    /**
     * Callback for the postTypes settings field
     */
    public function postTypesCb() {
        $post_types = $this->getPostTypes();
        if ( empty( $this->options['post_types'] ) || ! is_array( $this->options['post_types'] ) ) {
            $this->options['post_types'] = array();
        }
        foreach ( $post_types as $post_type ) {
            $checked = '';

            if ( in_array( $post_type->name, $this->options['post_types'] ) ) {
                $checked = 'checked';
            }

            printf(
                '<input type="checkbox" name="qmerce-settings-admin[post_types][]" value="%s" %s /> %s ',
                esc_attr( $post_type->name ),
                $checked,
                esc_html( $post_type->label )
            );
        }
    }

    public function automationPostTypeCb()
    {
        $postTypes = $this->getPostTypes();

        foreach($postTypes as $postType) {
            $checked = '';

            if ( in_array( $postType->name, $this->getAutomationPostTypes() ) ) {
                $checked = 'checked';
            }

            printf(
                '<input type="checkbox" name="qmerce-settings-admin[automation_post_types][]" value="%s" ' . $checked . '/> ' . $postType->label . ' ',
                $postType->name
            );
        }
    }

    private function getAutomationPostTypes() {
        if ( ! empty( $this->options['automation_post_types'] ) ) {
            return $this->options['automation_post_types'];
        }

        return array();
    }

    /**
     * Validates Apester authToken
     * @param string $value
     * @return bool
     */
    private function validateToken($value)
    {
        return (bool) preg_match( '/^[0-9a-fA-F]{24}$/', $value );
    }

    /**
     * Preserve old values - DEPRECATED
     * @return array
     */
    protected function preserveValue()
    {
        add_settings_error( 'qmerce-settings-admin', 500, 'Given authorization token is not valid' );
        $qmerceSettings = get_option( 'qmerce-settings-admin' );

        return array( 'auth_token' => $qmerceSettings['auth_token'] );
    }

    /**
     * Retrieves the names of all available post types in array
     * @return array
     */
    private function getPostTypesNames() {
        return wp_list_pluck($this->getPostTypes(), 'name');
    }

    /**
     * Determines if submitted post types are valid
     * @param array $postTypes
     * @return bool
     */
    private function isPostTypesValid($postTypes)
    {
        $availablePostTypes = $this->getPostTypesNames();

        foreach ( $postTypes as $postType ) {
            if ( !in_array( $postType, $availablePostTypes ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $postTypes
     * @return array
     */
    private function sanitizePostTypes($postTypes)
    {
        if ( is_array( $postTypes ) && $this->isPostTypesValid( $postTypes ) ) {
            return $postTypes;
        }

        return array();
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     * @return array|string
     */
    public function sanitize( $input ) {
        $new_input = array();

        if ( isset( $input['post_types'] ) ) {
            $new_input['post_types'] = $this->sanitizePostTypes( $input['post_types'] );
        }

        if ( isset( $input['automation_post_types'] ) ) {
            $new_input['automation_post_types'] = $this->sanitizePostTypes( $input['automation_post_types'] );
        }

        // Delete the unused user-id value.
        delete_option( 'qmerce-user-id' );
        
        $tokens = $input['auth_token'];
        if (isset( $tokens )) {
            $tokens = is_array( $tokens ) ? $tokens : array( $tokens );
            $new_input['auth_token'] = array();
        }
        foreach ( $tokens as $token ) {
            if ( trim($token) === '' || ! $this->validateToken( $token ) ) {
                continue;
            }

            $new_input['auth_token'][] = sanitize_text_field( $token );
        }

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function printSectionInfo()
    {
        print 'Enter your settings below:';
    }

    /**
     * Print the helper text.
     */
    public function printHelperInfo() {
        printf(
            'Get a token at <a href="%s" target="_blank">Apester.com</a> (you can find it in your <a href="%s" target="_blank">user settings</a>.)',
            esc_url( APESTER_EDITOR_BASEURL . '/register' ),
            esc_url( APESTER_EDITOR_BASEURL . '/user/settings' )
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function authTokenCallback() {
        printf(
            '<input type="text" id="auth_token" name="qmerce-settings-admin[auth_token]" value="%s" size="28" />',
             isset( $this->options['auth_token'] ) ? esc_attr( $this->options['auth_token'] ) : ''
        );
    }
}

$qmerce_settings_page = new Qmerce_Settings();

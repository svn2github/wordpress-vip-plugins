<?php

class Qmerce_Settings
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;
    
    // not using const to support PHP < 5.6
    private $allowed_positions = array('bottom', 'top', 'middle');

    const MAX_TAGS = 6;
    const MAX_TAG_LENGTH = 20;

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
            'Apester',
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
                '<li>' .
                '<input id="cbx-' . $postType->name . '" type="checkbox" name="qmerce-settings-admin[automation_post_types][]" value="%s" ' . $checked . '/> <label for="cbx-' . $postType->name . '">' . $postType->label . ' </label> </li> ',
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
     * Update the tokens list with the playlist enabled state of each token
     * @param $current_apester_tokens - all tokens available at the server
     * @param $playlist_tokens - the list of playlist enabled tokens from the client UI
     *
     * @return array - the updated state of the channel tokens list
     */
    private function sanitizePlaylistTokens($current_apester_tokens, $playlist_tokens)
    {
        // in case the array is not passed just ignore that and return the value as it was before the current function call
        if (!isset( $playlist_tokens )) {
            return $current_apester_tokens;
        }

        $new_tokens = array();

        foreach ( $playlist_tokens as $playlist_token => $isTokenChecked ) {
            // only update if the token exists in the tokens list
            if (isset($current_apester_tokens) && array_key_exists($playlist_token, $current_apester_tokens)) {
                // the value passed for the  'isPlaylistEnabled' property can only be either '0' or '1'
                $new_tokens[$playlist_token]['isPlaylistEnabled'] = ($isTokenChecked == '1' || $isTokenChecked == '0') ? $isTokenChecked : '0';
            }
        }

        return $new_tokens;
    }

    private function sanitizeTags($tags) {
        $sanitizedTags = array();

        if ( ! empty( $tags ) && is_array( $tags ) ) {
            foreach ( $tags as $tag ) {
                $trimmedTag = trim( $tag );

                if ( count($sanitizedTags) == self::MAX_TAGS
                     || $trimmedTag === ''
                     || in_array($trimmedTag, $sanitizedTags)
                     || strlen($trimmedTag) > self::MAX_TAG_LENGTH ) {
                    continue;
                }

                $sanitizedTags[] = sanitize_text_field( $trimmedTag );
            }
        }

        return $sanitizedTags;
    }

    public function sanitizePlaylistPosition( $playlistPosition ) {
        if ( ! isset($playlistPosition) || ! in_array($playlistPosition, $this->allowed_positions) ){
            return 'bottom';
        }

        return $playlistPosition;
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     * @return array|string
     */
    public function sanitize( $input ) {
        $new_input = array();
        $apester_options = get_option( 'qmerce-settings-admin' );
        $apester_tokens = $apester_options['apester_tokens'];

        if ( isset( $input['post_types'] ) ) {
            $new_input['post_types'] = $this->sanitizePostTypes( $input['post_types'] );
        }

        if ( isset( $input['automation_post_types'] ) ) {
            $new_input['automation_post_types'] = $this->sanitizePostTypes( $input['automation_post_types'] );
        }

        // Delete the unused user-id value.
        delete_option( 'qmerce-user-id' );

        if ( isset( $input['apester_tags'] ) ) {
            $new_input['apester_tags'] = $this->sanitizeTags( $input['apester_tags'] );
        }

        if ( isset( $input['context'] ) ) {
            $new_input['context'] = sanitize_text_field( $input['context'] );
        }

        if ( isset( $input['fallback'] ) ) {
            $new_input['fallback'] = sanitize_text_field( $input['fallback'] );
        }

        $new_input['playlist_position'] = $this->sanitizePlaylistPosition($input['playlist_position']);

        // cache the full token data for later
        $manipulatedPlaylistTokens = $this->sanitizePlaylistTokens($apester_tokens, $input['playlist_enabled_tokens']);

        // init the full token data so we can check for token existence
        $new_input['apester_tokens'] = array();

        $tokens = $input['auth_token'];
        if (isset( $tokens )) {
            $tokens = is_array( $tokens ) ? $tokens : array( $tokens );
            $new_input['auth_token'] = array();
        }

        foreach ( $tokens as $token ) {
            if ( trim($token) === '' || ! $this->validateToken( $token ) ) {
                continue;
            }

            // we keep the old token list updated in case the plugin will be downgraded in the future
            $new_input['auth_token'][] = sanitize_text_field( $token );

            // convert '<' OR '>' into thier respective html entities
            $sanitizedToken = sanitize_text_field( $token );
            
            // if the token already exists use the existing data of it
            if (isset($apester_tokens) && array_key_exists($sanitizedToken, $apester_tokens) ) {
                $new_input['apester_tokens'][$sanitizedToken] = $manipulatedPlaylistTokens[$sanitizedToken];
            }
            // if the token is new - add it to the new list
            else {
                $new_input['apester_tokens'][$sanitizedToken] = array(
                    'isPlaylistEnabled' => '0'
                );
            }
        }
        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function printSectionInfo() {
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

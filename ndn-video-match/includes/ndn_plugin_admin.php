<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks for
 * enqueuing the admin-specific stylesheet and JavaScript.
 *
 * @author     Inform, Inc. <wordpress@inform.com>
 */
class NDN_Plugin_Admin
{
    /**
     * The ID of this plugin.
     * @var string The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     * @var string The current version of this plugin.
     */
    private $version;

    /**
     * Initialized Wordpress Hooks.
     * @var bool
     */
    public static $initiated = false;

    /**
     * User has token. Changes state of default settings page.
     * @var bool
     */
    public static $has_token = false;

    /**
     * User has configured settings properly
     * Specifically, filled out tracking group id.
     * @var bool
     */
    public static $configured = false;

    /**
     * Login form class/id names for each input.
     * @var array
     */
    public static $login_form_options = array(
        'ndn_username' => 'ndn-plugin-login-username',
        'ndn_password' => 'ndn-plugin-login-password',
        'ndn_company_name' => 'ndn-plugin-login-company-name',
        'ndn_contact_name' => 'ndn-plugin-login-contact-name',
        'ndn_contact_email' => 'ndn-plugin-login-contact-email'
    );

    /**
     * Class/ID names for each form element.
     * @var array
     */
    public static $settings_form_options = array(
        'ndn_default_tracking_group' => 'ndn-plugin-default-tracking-group',
        'ndn_default_div_class' => 'ndn-plugin-default-div-class',
        'ndn_default_site_section' => 'ndn-plugin-default-site-section',
        'ndn_default_width' => 'ndn-plugin-default-width',
        'ndn_default_responsive' => 'ndn-default-responsive',
        'ndn_default_video_position' => 'ndn-default-video-position',
        'ndn_default_start_behavior' => 'ndn-plugin-default-start-behavior',
        'ndn_default_featured_image' => 'ndn-plugin-default-featured-image'
    );

    /**
     * Video configuration settings for NDN embedded videos
     * @var array
     */
    public static $custom_form_options = array(
        'ndn_responsive' => 'ndn-responsive',
        'ndn_video_width' => 'ndn-video-width',
        'ndn_video_start_behavior' => 'ndn-video-start-behavior',
        'ndn_video_position' => 'ndn-video-position',
        'ndn_featured_image' => 'ndn-featured-image'
    );

    /**
     * Start behavior options.
     * @var array
     */
    public static $start_behavior_options = array(
        'click_to_play' => array(
            'name' => 'Click to play',
            'value' => '2',
        ),
        'autoplay' => array(
            'name' => 'Autoplay',
            'value' => '1',
        ),
    );

    /**
     * Video position options.
     * @var array
     */
    public static $video_position_options = array(
        'left' => array(
            'name' => 'Left',
            'value' => 'left',
        ),
        'center' => array(
            'name' => 'Center',
            'value' => 'center',
        ),
        'right' => array(
            'name' => 'Right',
            'value' => 'right',
        ),
    );

    /**
     * Search Query String.
     * @var string
     */
    public static $search_query;

    /**
     * Array of search results.
     * @var array
     */
    public static $search_results;

    /**
     * NDN Plugin Shortcode.
     */
    const shortcode = 'inform';
    const NDN_OAUTH_API = 'https://oauth.newsinc.com';
    const NDN_SEARCH_API = 'https://public-search-api.newsinc.com';

    /**
     * Initialize the class and set its properties.
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct( $plugin_name, $version )
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        self::check_user_token();
        self::check_user_configuration();
        self::$search_query = get_option( 'ndn_search_query' ); // TODO delete if not on search results page
        self::$search_results = get_option( 'ndn_search_results' );
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles()
    {
        wp_enqueue_style( $this->plugin_name, NDN_PLUGIN_DIR . '/css/ndn_plugin_admin.css', $this->version, 'all' );
    }


    /**
     * Register the stylesheets for admin post pages
     */
    public function post_page_enqueue_stylesheet($hook) {
        if ( $hook != 'post.php' ) {
            return;
        }
        wp_enqueue_style( $this->plugin_name, NDN_PLUGIN_DIR . '/css/ndn_plugin_admin_custom_modal.css', $this->version, 'all' );
        wp_enqueue_style( $this->plugin_name, NDN_PLUGIN_DIR . '/css/ndn_plugin_admin_login.css', $this->version, 'all' );
        wp_enqueue_style( $this->plugin_name, NDN_PLUGIN_DIR . '/css/ndn_plugin_admin_search_results.css', $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts()
    {
        global $post;
        wp_enqueue_media();
        wp_enqueue_script( $this->plugin_name, NDN_PLUGIN_DIR . '/js/ndn_plugin_admin.js', array( 'jquery' ), $this->version, false );
        if ( $post ) {
          wp_localize_script( $this->plugin_name, 'NDNAjax', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'postID' => $post->ID ) );
        }    }

    /**
     * Register the JavaScript for the admin post pages
     */
    public function post_page_enqueue_scripts($hook)
    {
        if ( $hook != 'post.php' ) {
            return;
        }
        wp_enqueue_script( $this->plugin_name, NDN_PLUGIN_DIR . '/js/ndn_plugin_admin_custom_modal.js', array( 'jquery' ), $this->version, false );
        wp_enqueue_script( $this->plugin_name, NDN_PLUGIN_DIR . '/js/ndn_plugin_admin_search_results.js', array( 'jquery' ), $this->version, false );
    }

    /**
     * Create Plugin Setting page and Menu item on Admin Page.
     */
    public function create_plugin_menu()
    {
        add_menu_page(
            'Inform Video Match Settings',
            'Inform Video',
            'manage_options',
            'inform-plugin-settings',
            array( $this, 'create_plugin_menu_display' ),
            NDN_PLUGIN_DIR . '/assets/informIcon_17x17.png',
            '76'
        );
    }

    /**
     * Create Menu Display.
     */
    public static function create_plugin_menu_display()
    {
        if ( !current_user_can( 'edit_posts' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        require plugin_dir_path( dirname( __FILE__ ) ) . 'partials/ndn_plugin_admin_settings_page.php';
    }

    /**
     * Register Search Modal, hidden.
     */
    public function register_custom_modal_page()
    {
        $page = add_submenu_page(
            'options.php', // Sets it to be underneath no submenu
            'Inform Video Match',
            'Inform Video Match',
            'edit_posts',
            'inform-video-search?',
            array( $this, 'show_search_modal' )
        );

    }

    /**
     * Show Search Modal Page.
     */
    public function show_search_modal()
    {
        if ( !current_user_can( 'edit_posts' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        require plugin_dir_path( dirname( __FILE__ ) ) . 'partials/ndn_plugin_admin_search_modal.php';
    }

    /**
     * Register Search Results, hidden.
     */
    public function register_search_results_page()
    {
        add_submenu_page(
            'options.php', // Sets it to be underneath no submenu
            'Inform Video Match',
            'Inform Video Match',
            'edit_posts',
            'inform-video-search-results?',
            array( $this, 'show_search_results' )
        );
    }

    /**
     * Show Search Results Page.
     */
    public function show_search_results()
    {
        if ( !current_user_can( 'edit_posts' ) ) {
            wp_die(__( 'You do not have sufficient permissions to access this page.' ) );
        }
        require plugin_dir_path( dirname( __FILE__) ) . 'partials/ndn_plugin_admin_search_results.php';
    }

    /**
     * Create Plugin Login Display.
     */
    public function create_plugin_login()
    {
        add_submenu_page(
            'options.php',
            'Inform Login Page',
            'Inform Login Page',
            'manage_options',
            'inform-plugin-login?',
            array( $this, 'create_plugin_login_display' )
        );
    }

    /**
     * Create Menu Display.
     */
    public static function create_plugin_login_display()
    {
        if ( !current_user_can( 'edit_posts' ) ) {
            wp_die(__( 'You do not have sufficient permissions to access this page.' ) );
        }
        require plugin_dir_path( dirname( __FILE__ ) ) . 'partials/ndn_plugin_admin_login_page.php';
    }

    /**
     * Submit initial form of client information.
     */
    public function submit_client_information()
    {
        if (isset( $_POST['returning-login-submission']) && '1' == $_POST['returning-login-submission']) {
            $args = array(
                'username' => sanitize_text_field( $_POST['username'] ),
                'password' => sanitize_text_field( $_POST['password'] ),
                'first_time_login' => false
            );

            $redirect_location = admin_url( 'admin.php?page=inform-plugin-settings' );
            $error_redirect_location = admin_url( 'admin.php?page=inform-plugin-settings' );

            // After login success, go back to settings page
            $this->login_user( $args, $redirect_location,  $error_redirect_location);
        } elseif ( isset( $_POST['login-submission'] ) && '1' == $_POST['login-submission'] ) {
            $args = array(
                'username' => sanitize_text_field( $_POST['username'] ),
                'password' => sanitize_text_field( $_POST['password'] ),
                'name' => sanitize_text_field( $_POST['contact_name'] ),
                'company_name' => sanitize_text_field( $_POST['company_name'] ),
                'contact_name' => sanitize_text_field( $_POST['contact_name'] ),
                'contact_email' => sanitize_text_field( $_POST['contact_email'] ),
                'first_time_login' => true
            );

            $redirect_location = admin_url( 'admin.php?page=inform-plugin-settings' );
            $error_redirect_location = admin_url( 'admin.php?page=inform-plugin-settings' );

            // After login success, go back to settings page
            $this->login_user( $args, $redirect_location,  $error_redirect_location);
        } elseif (isset( $_POST['redirect-login-submission']) && '1' == $_POST['redirect-login-submission']) {
            $args = array(
                'username' => sanitize_text_field( $_POST['username'] ),
                'password' => sanitize_text_field( $_POST['password'] ),
                'first_time_login' => false
            );

            $redirect_location = admin_url( 'admin.php?page=inform-video-search%3F&iframe=true' );
            $error_redirect_location = admin_url( 'admin.php?page=inform-plugin-login%3F&iframe=true' );
            // After login success, go back to search page
            $this->login_user( $args, $redirect_location,  $error_redirect_location);
        }
    }

    /**
     * Logs in user and logic for error handling.
     * @param string $username                user's username
     * @param string $password                user's password
     * @param string $redirect_location       admin page where the redirect will occur
     * @param string $error_redirect_location admin page where error redirect should occur
     */
    private function login_user( $args, $redirect_location, $error_redirect_location )
    {
        if ( isset( $args['username'] ) && isset( $args['password'] ) ) {
            $username = $args['username'];
            $password = $args['password'];
            // If client id and secret are obtained, do not create a new client. Obtain tokens
            $client_id = get_option( 'ndn_client_id' );
            $client_secret = get_option( 'ndn_client_secret' );

            // Upon submission of username and password, fetch client details for client_id and client_secret
            if ( !$client_id && !$client_secret ) {
                if ( $args['first_time_login'] ) {
                    $create_client_response = $this->create_oauth_client( $username, $password, $args['name'], $args['company_name'], $args['contact_name'], $args['contact_email'] );
                } else {
                    $create_client_response = $this->get_oauth_client( $username, $password );
                }

                if ( $create_client_response ) {
                    $this->set_client_attrs( $create_client_response );
                } else {
                    // If client response does not come back, redirect the user back to the error redirect location
                    $error_message = 'Server Error';
                    $location = $error_redirect_location;
                    $status = '302';
                    wp_safe_redirect( esc_url_raw( $location ), $status );
                    exit;
                }
            }

            // Pass along username and password to create oauth token.
            $create_token_response = $this->create_oauth_token( $username, $password );

            // Set returned token
            if ( $create_token_response ) {
                 $this->set_access_token( $create_token_response );
                 $this->set_refresh_token( $create_token_response );
                 $location = $redirect_location;
                 $status = '302';
                 wp_safe_redirect( esc_url_raw( $location ), $status );
                 exit;
            } else {
                 // No token returned
                 $error_message = 'Server Error';
                 $location = $error_redirect_location;
                 $status = '302';
                 wp_safe_redirect( esc_url_raw( $location ), $status );
                 exit;
            }
        } else {
            // No username or password set
            $error_message = 'Invalid Username or Password Input';
            $location = $error_redirect_location;
            $status = '302';
            wp_safe_redirect( esc_url_raw( $location ), $status );
            exit;
        }
    }

    /**
     * Create OAuth client.
     *
     * @param string $username      Username of the user
     * @param string $password      Password of the user
     * @param string $name          User's name
     * @param string $company_name  Company Name
     * @param string $contact_name  Contact Name
     * @param string $contact_email Contact Email
     * @return object Response from OAuth Client API
     */
    private function create_oauth_client( $username, $password, $name = '', $company_name = '', $contact_name = '', $contact_email = '' )
    {
        // Construct headers
        $headers = array(
            'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
            'content-type' => 'application/json'
        );
        // Construct post data
        $data = array(
            'name' => $name,
            'company_name' => $company_name,
            'contact_name' => $contact_name,
            'contact_email' => $contact_email,
        );
        // Turn it into JSON
        $json_data = json_encode( $data );

        $wp_post_url = self::NDN_OAUTH_API . '/v1/oauth2/client';

        $wp_post_args = array(
            'method' => 'POST',
        	'headers' => $headers,
        	'body' => $json_data
        );

        $response = wp_safe_remote_post( $wp_post_url, $wp_post_args );

        // If client already exists, get that client

        if ( is_array( $response ) ) {
            if ( $response['response'] && $response['response']['code'] == '422' ) {
                return $this->get_oauth_client( $username, $password );
            } else {
                return $response;
            }
        } else {
            return $response;
        }
    }

    /**
     * Get Client details
     *
     * @param string $username      Username of the user
     * @param string $password      Password of the user
     */
    private function get_oauth_client( $username, $password )
    {
      // Construct headers
      $headers = array(
          'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
          'content-type' => 'application/json'
      );

      $wp_post_url = self::NDN_OAUTH_API . '/v1/oauth2/client';

      $wp_post_args = array(
        'method' => 'GET',
        'headers' => $headers
      );

      $response = wp_safe_remote_post( $wp_post_url, $wp_post_args );

      // If there is a response, return it. Otherwise, return false.
        if ( !is_wp_error( $response ) && is_array( $response ) ) {
            if ( array_key_exists( 'body', $response ) ) {
              return $response;
            } else {
              return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Sets client attributes (id & secret) from API response.
     * @param string $response Client information in JSON format
     */
    private function set_client_attrs( $response )
    {
        $client_response = json_decode( $response['body'], $assoc = true );

        // Check if client_response is not null
        if ( is_array( $client_response ) ) {
            if ( array_key_exists( 'client', $client_response ) ) {
                $this->client_id = $client_response['client']['client_id'];
                $this->client_secret = $client_response['client']['client_secret'];
            } else {
                $this->client_id = $client_response['client_id'];
                $this->client_secret = $client_response['client_secret'];
            }

            self::save_option( 'ndn_client_id', $this->client_id );
            self::save_option( 'ndn_client_secret', $this->client_secret );
        }
    }

    /**
     * Create OAuth Token.
     *
     * @param string $username Username of the user
     * @param string $password Password of the user
     * @return object OAuth Response, Token Object
     */
    private function create_oauth_token( $username, $password )
    {
        // Take Client_ID and Client_Secret from inputted value from user
        $client_id = get_option( 'ndn_client_id' );
        $client_secret = get_option( 'ndn_client_secret' );

        // Construct headers
        $headers = array(
            'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
            'content-type' => 'application/x-www-form-urlencoded'
        );

        // Construct post data
        //
        // Create raw array of name value pairs
        $data = array(
            'grant_type' => 'password',
            'username' => $username,
            'password' => $password,
        );

        // Create url encoded string
        $post_data = '';

        foreach ( $data as $k => $v) {
            $post_data .= $k.'='.$v.'&';
        }
        rtrim( $post_data, '&' );

        $wp_post_url = self::NDN_OAUTH_API . '/v1/oauth2/token';
        $wp_post_args = array(
            'method' => 'POST',
        	'headers' => $headers,
        	'body' => $post_data
        );

        $response = wp_safe_remote_post( $wp_post_url, $wp_post_args );

        return $response;
    }

    /**
     * Set Access Token from Response.
     * @param array $response API JSON string from API
     */
    private function set_access_token( $response )
    {
        if ( $response ) {
            $token_response = json_decode( $response['body'], $assoc = true );

            self::save_option( 'ndn_access_token', $token_response['access_token'] );
        }
    }

    /**
     * Set Refresh Token from Response
     * @param array $response API JSON string from API
     */
    private function set_refresh_token( $response )
    {
        if ( $response ) {
            $token_response = json_decode( $response['body'], $assoc = true );

            self::save_option( 'ndn_refresh_token', $token_response['refresh_token'] );
        }
    }

    /**
     * Refresh OAuth Token.
     * @return object OAuth Response, Token Object
     */
    private function refresh_oauth_token()
    {
        // Fetch parameters for post data
        $refresh_token = get_option( 'ndn_refresh_token' );
        $client_id = get_option( 'ndn_client_id' );
        $client_secret = get_option( 'ndn_client_secret' );

        // Construct headers
        $headers = array(
            'content-type' => 'application/x-www-form-urlencoded'
        );

        // Construct post data
        //
        // Create raw array of name value pairs
        $data = array( 'grant_type' => 'refresh_token',
              'client_id' => $client_id,
              'client_secret' => $client_secret,
              'refresh_token' => $refresh_token, );

        // Create url encoded string
        $post_data = '';
        foreach ( $data as $k => $v) {
            $post_data .= $k.'='.urlencode( $v).'&';
        }
        rtrim( $post_data, '&' );

        $wp_post_url = self::NDN_OAUTH_API . '/v1/oauth2/token';
        $wp_post_args = array(
            'method' => 'POST',
        	'headers' => $headers,
        	'body' => $post_data
        );

        $response = wp_safe_remote_post( $wp_post_url, $wp_post_args );

        // Set oauth_token to the response from API
        $this->set_access_token( $response );
        $this->set_refresh_token( $response );

        return $response;
    }

    /**
     * Checks the user token.
     * @return [type] [description]
     */
    public static function check_user_token()
    {
        if (get_option( 'ndn_client_id' ) && get_option( 'ndn_client_secret' ) && get_option( 'ndn_refresh_token' ) ) {
            self::$has_token = true;
        } else {
            self::$has_token = false;
        }
    }

    /**
     * Notify the user if the API credentials have not been entered.
     */
    public function notify_user_for_credentials()
    {
        $url = admin_url( 'admin.php?page=inform-plugin-settings' );
        if (self::$has_token || !current_user_can( 'edit_posts' ) ) {
            return;
        }
        ?>
        <div class="update-nag">
            <?php
            if ( current_user_can( 'manage_options' ) ) {
                echo wp_kses_post(sprintf(__( 'Your tracking group needs to be entered in <a href="%s" title="Inform Video Match Settings" class="ndn-notify-credentials">Inform Video Match Settings</a>.', 'Inform' ), esc_url( $url ) ));
            } else {
                echo wp_kses_post(sprintf(__( 'Please contact your administrator to activate the Inform Video Match plugin.', 'Inform' ), esc_url( $url) ));
            }
            ?>
        </div>
        <?php

    }

    /**
     * Notify the user if the tracking group has not been entered.
     * @action admin_notices
     */
    public function notify_user_for_configuration()
    {
        $url = admin_url( 'admin.php?page=inform-plugin-settings' );
        if (self::$configured || !self::$has_token || !current_user_can( 'edit_posts' ) ) {
            return;
        }
        ?>
        <div class="update-nag">
            <?php echo wp_kses_post(sprintf(__( 'Your tracking group needs to be entered in <a href="%s" title="Inform Video Match Settings" class="ndn-notify-settings">Inform Video Match Settings</a>.', 'NDN' ), esc_url( $url) ));
            ?>
        </div>
        <?php

    }

    /**
     * Saves the plugin settings from the form.
     */
    public static function save_plugin_settings()
    {
        if (isset( $_POST['ndn-save-settings'] ) && '1' == $_POST['ndn-save-settings'] ) {
            foreach (self::$settings_form_options as $option_name => $option) {
                if ( $_POST[$option] ) {
                    self::save_option( $option_name, sanitize_text_field( $_POST[$option] ) );
                } elseif ( $_POST[$option] == '' ) {
                    delete_option( $option_name );
                }
            }
            self::check_user_configuration();
        }
    }

    /**
     * Set if user is configured.
     */
    public static function check_user_configuration()
    {
        if (get_option( 'ndn_default_tracking_group' ) ) {
            self::$configured = true;
        } else {
            self::$configured = false;
        }
    }

    /**
     * Create Media Button on Admin Posts Page.
     */
    public static function add_media_button_wizard()
    {
        // Add Thickbox Support
        if (function_exists( 'add_thickbox' ) ) {
            add_thickbox();
        } else {
            wp_enqueue_style( 'thickbox' );
            wp_enqueue_script( 'thickbox' );
        }

        $classes = 'button ndn-plugin-wiz-button';

        if ( !self::$has_token || !self::$configured ) {
            $classes .= ' disabled';
            $href = '#';
        } else {
            $classes .= ' thickbox';
            $href = 'admin.php?page=inform-video-search%3F&amp;iframe&amp;TB_iframe=true';
        }

        // Provide media wizard button for a admin posts editor view.
        ?>
          <a href="<?php echo esc_attr( $href ); ?>"
            onclick="return false;"
            class="<?php echo esc_attr( $classes ); ?>"
            id="ndn-plugin-wiz-button"
            title="Inform Video Match"
            analytics-category="WPSearch"
            analytics-label="SearchInitiate">
            <span></span>
            &nbsp;Inform Video
          </a>
        <?php

    }

    /**
     * Submit Search Query
     * On submission of search query, validates for search form POST request, gets access token, then runs API call
     * On return of API call, gives back error page or partial page of search results.
     * @return redirects to search results page
     */
    public function submit_search_query()
    {
        if (isset( $_POST['search-action']) && '1' == $_POST['search-action']) {
            // Refresh access token
            $this->refresh_oauth_token();

            $access_token = get_option( 'ndn_access_token' );
            $query = sanitize_text_field( $_POST['query'] );

            self::save_option( 'ndn_search_query', $query );


            $response = $this->run_text_search( $access_token, $query );
            if ( $response ) {
                $decoded_response = json_decode( $response['body'] );
                if ( !get_option( 'ndn_refresh_token' ) ) {
                    $redirect_location = admin_url( 'admin.php?page=inform-plugin-login%3F&iframe=true' );
                    wp_safe_redirect( esc_url_raw( $redirect_location ) );
                    exit;
                } elseif ( !$decoded_response->response ) {
                    echo '<p>No Results Found. Go back and try searching again.</p>';
                } else {
                    // Saving data in cache, set to expire in 10 minutes
                    $cache_key = 'ndn_query_' . $query;
                    wp_cache_add( $cache_key, $response, '', 600 );
                    // Sort Videos by Recency
                    $videos = array();
                    $response_videos = $decoded_response->response->videos;
                    foreach ( $response_videos as $key => $row) {
                        $videos[$key] = $row->publish_date;
                    }
                    array_multisort( $videos, SORT_DESC, $response_videos );
                    // Set search_results as response
                    self::save_option( 'ndn_search_results', $response_videos ); // Recency

                    $redirect_location = admin_url( 'admin.php?page=inform-video-search-results%3F&iframe=true' ) ;
                    wp_safe_redirect( esc_url_raw( $redirect_location ) );
                    exit;
                }
            } else {
                $redirect_location = 'admin.php?page=inform-plugin-login%3F&iframe=true';
                wp_safe_redirect( esc_url_raw( $redirect_location ) );
                exit;
            }
        }
    }

    /**
     * Text Search API with '/content/search'.
     *
     * @param string $access_token  Access token to access API
     * @param string $search_string Search query
     * @return object Search results object
     */
    private function run_text_search( $access_token, $search_string )
    {
        $cache_key = 'ndn_query_' . $search_string;
        $response = wp_cache_get( $cache_key );
        // If there is no response in cache
        if ( $response == false ) {
            $headers = array(
              'Authorization' => sprintf( 'Bearer %s', $access_token )
            );
            $query_data = array( 'text' => $search_string );
            $query_string = http_build_query( $query_data );

            $wp_get_url = sprintf( self::NDN_SEARCH_API . '/content/search/v1/text?%s', $query_string );

            $wp_get_args = array(
              'headers' => $headers
            );

            $response = vip_safe_wp_remote_get( $wp_get_url, '', 10, 3, 20, $wp_get_args );

            if ( is_array( $response ) ) {
                if ( array_key_exists( 'response', $response ) ) {
                    $info = $response['response'];

                    if ( $info['code'] == '401' ) {
                        // Token is stale. Need user to re-authorize the API
                        return false;
                    }
                // If API gives back error message
                } else if ( array_key_exists ( 'errors', $response) ) {
                    return false;
                }
            } else if ( is_wp_error( $response ) ) {
                return false;
            }
        }
        return $response;
    }

    /**
     * Saves Option in wp_option.
     *
     * @param string       $option_name name of the option (be sure to namespace)
     * @param string/array $value       data to be stored in wp_option
     */
    private static function save_option( $option_name, $value )
    {
        if (get_option( $option_name) !== false) {
            // The option already exists, so we just update it.
            update_option( $option_name, $value );
        } else {
            // The option hasn't been added yet. We'll add it with $autoload set to 'no'.
            $deprecated = null;
            $autoload = 'yes';
            add_option( $option_name, $value, $deprecated, $autoload );
        }
    }

    /**
     * Allow additional image attributes on <img> tags on Wordpress text editor.
     */
    public function allow_additional_img_attributes()
    {
        global $allowedposttags;

        $tags = array( 'img' );
        $new_attributes = array(
            'ndn-config-video-id' => array(),
            'ndn-video-element-class' => array(),
            'ndn-config-widget-id' => array(),
            'ndn-tracking-group' => array(),
            'ndn-site-section-id' => array(),
            'ndn-video-width' => array(),
            'ndn-video-height' => array(),
            'ndn-responsive' => array()
        );

        foreach ( $tags as $tag) {
            if (isset( $allowedposttags[ $tag ]) && is_array( $allowedposttags[ $tag ]) ) {
                $allowedposttags[ $tag ] = array_merge( $allowedposttags[ $tag ], $new_attributes);
            }
        }
    }

    /**
     * Allow additional image attributes on images on Wordpress TinyMCE Visual Editor.
     *
     * @param array $options options for valid element and its attributes
     * @return array Options array
     */
    public function allow_tinymce_additional_img_attributes( $options )
    {
        if ( !isset( $options['extended_valid_elements']) ) {
            $options['extended_valid_elements'] = '';
        } else {
            $options['extended_valid_elements'] .= ',';
        }

        if ( !isset( $options['custom_elements']) ) {
            $options['custom_elements'] = '';
        } else {
            $options['custom_elements'] .= ',';
        }
        $options['extended_valid_elements'] .= 'img[ndn-config-video-id|ndn-video-element-id|ndn-config-widget-id|ndn-tracking-group|ndn-site-section-id|ndn-video-width|ndn-video-height|ndn-responsive|class|src|border|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|style]';
        $options['custom_elements']         .= 'img[ndn-config-video-id|ndn-video-element-id|ndn-config-widget-id|ndn-tracking-group|ndn-site-section-id|ndn-video-width|ndn-video-height|ndn-responsive|class|src|border|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|style]';

        return $options;
    }

    /**
     * Set featured image from $url
     */
    public function set_featured_image()
    {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-includes/pluggable.php');

        check_ajax_referer( 'ndn-ajax-nonce', 'security' );

        $url = sanitize_text_field( $_POST['url'] );
        $post_id = sanitize_text_field( $_POST['postID'] );
        $desc = sanitize_text_field( $_POST['description'] );

        // Sideload image, returns image src
        $image_id = $this->sideload_featured_image($url, $post_id, $desc);
        if ( is_wp_error( $image_id ) ) {
            wp_die( 0 );
        } else {
            // Set featured image
            set_post_thumbnail( $post_id, $image_id );

            $return = _wp_post_thumbnail_html( $image_id, $post_id );
            wp_die( $return );
        }
    }

    /**
     * Re-written media_sideload_image function to return attachment ID instead of html
     * @param  string $file    url of the file
     * @param  int    $post_id post id of the post being edited
     * @param  string $desc    description of the image, for alt tags
     * @return int             attachment id of image
     */
    function sideload_featured_image( $file, $post_id, $desc = null ) {
        if ( ! empty( $file ) ) {

    		// Set variables for storage, fix file filename for query strings.
    		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
    		$file_array = array();
    		$file_array['name'] = basename( $matches[0] );

    		// Download file to temp location.
    		$file_array['tmp_name'] = download_url( $file );

    		// If error storing temporarily, return the error.
    		if ( is_wp_error( $file_array['tmp_name'] ) ) {
    			return $file_array['tmp_name'];
    		}

    		// Do the validation and storage stuff.
    		$id = media_handle_sideload( $file_array, $post_id, $desc );

    		// If error storing permanently, unlink.
    		if ( is_wp_error( $id ) ) {
    			@unlink( $file_array['tmp_name'] );
    			return $id;
    		}

    		$src = wp_get_attachment_url( $id );
    	}

    	// Finally, check to make sure the file has been saved, then return the id.
    	if ( ! empty( $src ) ) {
    		return $id;
    	} else {
    		return new WP_Error( 'image_sideload_failed' );
    	}
    }
}

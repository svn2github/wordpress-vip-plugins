<?php
/*
Plugin Name: CoSchedule
Description: Plan, organize, and execute every content marketing project in one place with CoSchedule, an all-in-one content marketing editorial calendar solution.
Version: 3.2.2
Author: CoSchedule
Author URI: http://coschedule.com/
Plugin URI: http://coschedule.com/
*/

// Check for existing class
if ( ! class_exists( 'tm_coschedule' ) ) {

    // include the Http Class
    if ( ! class_exists( 'WP_Http' ) ) {
        /** @noinspection PhpIncludeInspection */
        include_once( ABSPATH . WPINC . '/class-http.php' );
    }

    /** @noinspection PhpUndefinedClassInspection */
    class TM_CoSchedule {
        private $api = "https://api.coschedule.com";
        private $webhooks_url = "https://webhooks.coschedule.com";
        private $app = "https://app.coschedule.com";
        private $assets = "https://assets.coschedule.com";
        private $version = "3.2.2";
        private $build;
        private $connected = false;
        private $token = false;
        private $calendar_id = false;
        private $wordpress_site_id = false;
        private $synced_build;
        private $current_user_id = false;
        private $is_wp_vip = false;
        private $base64_decode_disabled;
        private $use_wp_json_encode;

        /**
         * Class constructor: initializes class variables and adds actions and filters.
         */
        public function __construct() {
            $this->TM_CoSchedule();
        }

        public function TM_CoSchedule() {
            register_activation_hook( __FILE__, array( $this, 'activation' ) );
            register_deactivation_hook( __FILE__, array( $this, 'deactivation' ) );

            // Load variables
            $this->build                  = intval( "82" );
            $this->token                  = get_option( 'tm_coschedule_token' );
            $this->calendar_id            = get_option( 'tm_coschedule_calendar_id' );
            $this->wordpress_site_id      = get_option( 'tm_coschedule_wordpress_site_id' );
            $this->synced_build           = get_option( 'tm_coschedule_synced_build' );
            $this->is_wp_vip              = ( defined( 'WPCOM_IS_VIP_ENV' ) && ( true === WPCOM_IS_VIP_ENV ) );
            $this->base64_decode_disabled = in_array( 'base64_decode', explode( ',', str_replace( ' ', '', ini_get( 'disable_functions' ) ) ), true );
            $this->use_wp_json_encode     = function_exists( 'wp_json_encode' );

            // Check if connected to api
            if ( ! empty( $this->token ) && ! empty( $this->wordpress_site_id ) ) {
                $this->connected = true;
            }

            // Register global hooks
            $this->register_global_hooks();

            // Register admin only hooks
            if ( is_admin() ) {
                $this->register_admin_hooks();
            }

            // Sync build number
            if ( $this->should_save_build() ) {
                $this->save_build_callback();
            }
        }

        /**
         * Handles activation tasks, such as registering the uninstall hook.
         */
        public function activation() {
            register_uninstall_hook( __FILE__, array( 'tm_coschedule', 'uninstall' ) );

            // Set redirection to true
            add_option( 'tm_coschedule_activation_redirect', true );
        }

        /**
         * Checks to see if the plugin was just activated to redirect them to settings
         */
        public function activation_redirect() {
            if ( get_option( 'tm_coschedule_activation_redirect', false ) ) {
                // Redirect to settings page
                if ( delete_option( 'tm_coschedule_activation_redirect' ) ) {
                    // If the plugin is being network activated on a multisite install
                    if ( is_multisite() && is_network_admin() ) {
                        $redirect_url = network_admin_url( 'plugins.php' );
                    } else {
                        $redirect_url = 'admin.php?page=tm_coschedule_calendar';
                    }

                    if ( wp_safe_redirect( $redirect_url ) ) {
                        // NOTE: call to exit after wp_redirect is per WP Codex doc:
                        //       http://codex.wordpress.org/Function_Reference/wp_redirect#Usage
                        exit;
                    }
                }
            }
        }

        /**
         * Handles deactivation tasks, such as deleting plugin options.
         */
        public function deactivation() {
            delete_option( 'tm_coschedule_token' );
            delete_option( 'tm_coschedule_id' );
            delete_option( 'tm_coschedule_calendar_id' );
            delete_option( 'tm_coschedule_wordpress_site_id' );
            delete_option( 'tm_coschedule_activation_redirect' );
            delete_option( 'tm_coschedule_custom_post_types_list' );
            delete_option( 'tm_coschedule_synced_build' );
        }

        /**
         * Handles uninstallation tasks, such as deleting plugin options.
         */
        public function uninstall() {
            delete_option( 'tm_coschedule_token' );
            delete_option( 'tm_coschedule_id' );
            delete_option( 'tm_coschedule_calendar_id' );
            delete_option( 'tm_coschedule_wordpress_site_id' );
            delete_option( 'tm_coschedule_activation_redirect' );
            delete_option( 'tm_coschedule_custom_post_types_list' );
            delete_option( 'tm_coschedule_synced_build' );
        }

        /**
         * Registers global hooks, these are added to both the admin and front-end.
         */
        public function register_global_hooks() {
            add_action( 'init', array( $this, "set_current_user" ) );

            // Called whenever a post is created/updated/deleted
            add_action( 'load-post.php', array( $this, "edit_post_callback" ) );
            add_action( 'save_post', array( $this, "save_post_callback" ) );
            add_action( 'delete_post', array( $this, "delete_post_callback" ) );

            // Called whenever a post is created/updated/deleted
            add_action( 'create_category', array( $this, "save_category_callback" ) );
            add_action( 'edited_category', array( $this, "save_category_callback" ) ); // NOTE: edited_ prefix is critical as we want committed value //
            add_action( 'delete_category', array( $this, "delete_category_callback" ) );

            // Called whenever a user/author is created/updated/deleted
            add_action( 'user_register', array( $this, "new_user_callback" ) );
            add_action( 'profile_update', array( $this, "save_user_callback" ), 10, 2 );
            add_action( 'delete_user', array( $this, "delete_user_callback" ) );

            // Called whenever timezone is updated
            add_action( 'update_option_timezone_string', array( $this, "save_timezone_callback" ) );
            add_action( 'update_option_gmt_offset', array( $this, "save_timezone_callback" ) );

            // Called whenever timezone is updated
            add_action( 'update_option_blogname', array( $this, "save_blogname_callback" ) );

            // work around 'missed schedule draft' condition //
            add_action( 'wp_insert_post_data', array( $this, 'conditionally_update_post_date_on_publish' ), 1, 2 );

            // Custom Slug Fix, replace data
            add_filter( 'wp_insert_post_data', array( $this, 'fix_custom_slug_after' ), 20 );

            // Open Graph Previews
            add_filter( 'posts_results', array( $this, 'set_post_visibility' ), 10, 2 );
        }

        /**
         * Registers admin only hooks.
         */
        public function register_admin_hooks() {
            // Add meta box setup actions to post edit screen
            add_action( 'load-post.php', array( $this, "meta_box_action" ) );
            add_action( 'load-post-new.php', array( $this, "meta_box_action" ) );

            if ( true !== $this->is_wp_vip ) {
                // Ajax: Trigger cron - only available in non-WP-VIP environments
                add_action( 'wp_ajax_tm_aj_trigger_cron', array( $this, 'tm_aj_trigger_cron' ) );
                add_action( 'wp_ajax_nopriv_tm_aj_trigger_cron', array( $this, 'tm_aj_trigger_cron' ) );
            }

            // Ajax: Get blog info
            add_action( 'wp_ajax_tm_aj_get_bloginfo', array( $this, 'tm_aj_get_bloginfo' ) );
            add_action( 'wp_ajax_nopriv_tm_aj_get_bloginfo', array( $this, 'tm_aj_get_bloginfo' ) );

            // Ajax: Set token
            add_action( 'wp_ajax_tm_aj_set_token', array( $this, 'tm_aj_set_token' ) );

            // Ajax: Check token
            add_action( 'wp_ajax_tm_aj_check_token', array( $this, 'tm_aj_check_token' ) );
            add_action( 'wp_ajax_nopriv_tm_aj_check_token', array( $this, 'tm_aj_check_token' ) );

            // Ajax: Set custom post types
            add_action( 'wp_ajax_tm_aj_set_custom_post_types', array( $this, 'tm_aj_set_custom_post_types' ) );
            add_action( 'wp_ajax_nopriv_tm_aj_set_custom_post_types', array( $this, 'tm_aj_set_custom_post_types' ) );

            // Ajax: The main entry point (when plugin_build > 38)
            add_action( 'wp_ajax_tm_aj_action', array( $this, 'tm_aj_action' ) );
            add_action( 'wp_ajax_nopriv_tm_aj_action', array( $this, 'tm_aj_action' ) );

            // Ajax: Deactivation
            add_action( 'wp_ajax_tm_aj_deactivation', array( $this, 'tm_aj_deactivation' ) );
            add_action( 'wp_ajax_nopriv_tm_aj_deactivation', array( $this, 'tm_aj_deactivation' ) );

            // Add Sidebar Links
            add_action( 'admin_menu', array( $this, 'add_menu' ) );
            add_action( 'admin_menu', array( $this, 'add_submenu' ) );
            add_action( 'admin_menu', array( $this, 'admin_submenu_new_window_items' ) );
            add_action( 'admin_menu', array( $this, 'admin_submenu_new_window_items_jquery' ) );

            // Add settings link to plugins listing page
            add_filter( 'plugin_action_links', array( $this, 'plugin_settings_link' ), 2, 2 );

            // Add check for activation redirection
            add_action( 'admin_init', array( $this, 'activation_redirect' ) );
        }

        /**
         * Add calendar and settings link to the admin menu
         */
        public function add_menu() {
            add_menu_page( 'CoSchedule', 'CoSchedule', 'edit_posts', 'tm_coschedule_calendar', array( $this, 'plugin_calendar_page' ),
                $this->assets . '/plugin/img/icon.png',
                '50.505' );
        }

        /**
         * Add calendar submenu links to admin menu.
         */
        public function add_submenu() {
            if ( true === $this->connected ) {
                add_submenu_page( 'tm_coschedule_calendar', 'Open In Web App', 'Open In Web App', 'edit_posts', 'tm_coschedule_new_window', array( $this, 'plugin_calendar_page' ) );
            }
        }

        /**
         * Add submenu item(s) that open in new window
         */
        public function admin_submenu_new_window_items() {
            global $submenu;

            if ( ! array_key_exists('tm_coschedule_calendar', $submenu) ) {
                $submenu['tm_coschedule_calendar'] = array(); // WPCS: override ok.
            }

            // Replace the value of the originally registered "placeholder" submenu item
            if ( true === $this->connected && $submenu['tm_coschedule_calendar'][1] && $submenu['tm_coschedule_calendar'][1][0] === "Open In Web App") {
                $url = $this->app . '/#/calendar/' . $this->calendar_id . '/schedule';
                $submenu['tm_coschedule_calendar'][1] = array( '<span class="cos-submenu-new-window">Open In Web App</span>', 'edit_posts', esc_url( $url ) ); // WPCS: override ok.
            }
        }

        /**
         * Enqueue script for opening submenu links in new window
         */
        public function admin_submenu_new_window_items_jquery() {
            $cache_bust = rawurlencode( $this->get_cache_bust() );
            $url        = $this->assets . '/plugin/js/cos-plugin-new-window.js?cb=' . $cache_bust;
            wp_enqueue_script( 'cos_js_plugin_new_window', $url, false, null, true );
        }

        /**
         * Admin: Add settings link to plugin management page
         *
         * @param $actions
         * @param $file
         *
         * @return mixed
         */
        public function plugin_settings_link( $actions, $file ) {
            if ( false !== strpos( $file, 'tm-scheduler' ) ) {
                $url                 = "admin.php?page=tm_coschedule_settings";
                $actions['settings'] = '<a href="' . esc_url( $url ) . '">Settings</a>';
            }

            return $actions;
        }

        /**
         * Settings page scripts
         */
        public function plugin_settings_scripts() {
            $cache_bust = rawurlencode( $this->get_cache_bust() );
            wp_enqueue_style( 'cos_css', $this->assets . '/plugin/css/cos-plugin-setup.css?cb=' . $cache_bust );
            wp_enqueue_script( 'cos_js_config', $this->assets . '/config.js?cb=' . $cache_bust, false, null, true );
            wp_enqueue_script( 'cos_js_plugin', $this->assets . '/plugin/js/cos-plugin-setup.js?cb=' . $cache_bust, false, null, true );
        }

        /**
         * Calendar page menu callback
         */
        public function plugin_calendar_page() {
            if ( ! current_user_can( 'edit_posts' ) ) {
                wp_die( esc_html( __( 'You do not have sufficient permissions to access this page.' ) ) );
            }

            // Check if connected
            if ( true === $this->connected ) {
                include( plugin_dir_path( __FILE__ ) . 'frame.php' );
            } else {
                $this->plugin_settings_scripts();
                include( plugin_dir_path( __FILE__ ) . 'plugin_setup.php' );
            }
        }

        /**
         * Checks if the meta box should be included on the page based on post type
         */
        public function meta_box_enabled() {
            $post_type = $this->get_current_post_type();

            return $this->is_synchronizable_post_type( $post_type, true );
        }

        /**
         * Adds action to insert a meta box
         */
        public function meta_box_action() {
            add_action( 'add_meta_boxes', array( $this, "meta_box_setup" ) );
        }

        /**
         * Sets up the meta box to be inserted
         */
        public function meta_box_setup() {
            if ( true === $this->meta_box_enabled() && true === $this->connected ) {
                $this->metabox_iframe_styles();
                $this->metabox_iframe_scripts();

                $post_type = $this->get_current_post_type();
                add_meta_box(
                    'tm-scheduler',                         // Unique ID
                    'CoSchedule',                           // Title
                    array( &$this, 'meta_box_insert' ),     // Callback function
                    $post_type,                             // Admin page (or post type)
                    'normal',                               // Context
                    'default'                               // Priority
                );
            }
        }

        /**
         * Metabox iframe styles
         */
        public function metabox_iframe_styles() {
            $cache_bust = rawurlencode( $this->get_cache_bust() );
            $url        = $this->assets . '/plugin/css/cos-metabox.css?cb=' . $cache_bust;
            wp_enqueue_style( 'cos_metabox_css', $url );
        }

        /**
         * Metabox iframe scripts
         */
        public function metabox_iframe_scripts() {
            $cache_bust       = rawurlencode( $this->get_cache_bust() );
            $resizer_url      = $this->assets . '/plugin/js/cos-iframe-resizer.js?cb=' . $cache_bust;
            $resizer_exec_url = $this->assets . '/plugin/js/cos-iframe-resizer-exec.js?cb=' . $cache_bust;
            wp_enqueue_script( 'cos_js_iframe_resizer', $resizer_url, false, null, true );
            wp_enqueue_script( 'cos_js_iframe_resizer_exec', $resizer_exec_url, false, null, true );
        }

        /**
         * Inserts the meta box
         *
         * @param $post
         */
        public function meta_box_insert( $post ) {
            $calendar_id       = get_option( 'tm_coschedule_calendar_id' );
            $wordpress_site_id = get_option( 'tm_coschedule_wordpress_site_id' );
            $query_params      = array(
                "calendarID"      => rawurlencode( $calendar_id ),
                "wordpressSiteID" => rawurlencode( $wordpress_site_id ),
                "postID"          => rawurlencode( $post->ID ),
                "build"           => rawurlencode( $this->build ),
                "userID"          => rawurlencode( $this->current_user_id ),
                "isMetabox"       => rawurlencode( 'true' )
            );
            $url               = untrailingslashit( $this->app ) . "/#/authenticate";
            // NOTE: calling add_query_arg(...) with empty string to avoid it relocating the hash location of above $url
            $url .= add_query_arg( $query_params, '' );
            ?>
            <!--suppress HtmlUnknownAttribute -->
            <iframe name="cos-metabox" id="CoSmetabox" frameborder="0" border="0" scrolling="no" src="<?php echo esc_url( $url ); ?>" width="100%"></iframe>
            <?php
        }

        /**
         * Ajax: Secure using token
         *
         * @param string $token
         *
         * @return bool
         */
        public function valid_token( $token = '' ) {
            if ( ! empty( $token ) ) {
                if ( true === $this->connected ) {
                    if ( $this->token === $token ) {
                        $validate = true;
                    } else {
                        $validate = "Invalid token";
                    }
                } else {
                    $validate = "Not connected to api";
                }
            } else {
                $validate = "Token required";
            }

            if ( true === $validate ) {
                return true;
            } else {
                $error = array(
                    'error' => $validate
                );
                $this->respond_json_and_die( $error );

                return false;
            }
        }

        /**
         * Ajax: "Near Realtime Assist"
         * Triggers internal cron at the scheduled time of publication for a particular post
         *
         * @param $data_args
         */
        public function tm_aj_trigger_cron( $data_args ) {
            $response = array();
            try {
                if ( isset( $_GET['token'] ) ) {
                    $token = sanitize_text_field( $_GET['token'] );
                } elseif ( isset( $data_args['token'] ) ) {
                    $token = $data_args['token'];
                }
                $this->sanitize_param( $token );

                // only proceed if valid token
                if ( true === $this->valid_token( $token ) ) {

                    if ( is_array( $_GET ) && array_key_exists( 'post_id', $_GET ) && isset( $_GET['post_id'] )) {
                        $post_id = sanitize_text_field( $_GET['post_id'] );
                    } elseif ( is_array( $data_args ) && array_key_exists( 'post_id', $data_args ) ) {
                        $post_id = $data_args['post_id'];
                    }
                    $this->sanitize_param( $post_id );

                    // purge any post caches
                    $cache_flush_result = $this->cache_flush( $post_id );

                    if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
                        // wp_cron is disabled
                        $wp_cron_response = 'disabled';
                    } else {
                        // wp_cron can be executed, do it
                        $wp_cron_response = ( ( wp_cron() !== null ) ? true : false );
                    }

                    // if indication is wp_cron not run or disabled, force the issue //
                    if ( false === $wp_cron_response || 'disabled' === $wp_cron_response ) {
                        $publish_missed_schedule_posts_result = $this->publish_missed_schedule_posts( $post_id );
                    } else {
                        $publish_missed_schedule_posts_result = false;
                    }

                    // report the findings
                    $response['wp_cron_was_run']                      = $wp_cron_response;
                    $response['cache_flush_result']                   = $cache_flush_result;
                    $response['publish_missed_schedule_posts_result'] = $publish_missed_schedule_posts_result;
                    $response['server_time']                          = time();
                    $response['server_date']                          = date( 'c' );
                    $response['gmt_offset']                           = get_option( 'gmt_offset' );
                    $response['tz_abbrev']                            = date( 'T' );
                }
            } catch ( Exception $e ) {
                $response['error'] = $e->getMessage();
            }

            $this->respond_json_and_die( $this->array_decode_entities( $response ) );
        }

        /**
         * Adapted from nice example found here: http://theme.fm/2011/10/how-to-upload-media-via-url-programmatically-in-wordpress-2657/
         * See also: https://codex.wordpress.org/Function_Reference/media_handle_sideload
         *
         * @param $data_args
         */
        public function tm_aj_sideload_url( $data_args ) {
            try {

                if ( isset( $data_args['url'] ) ) {
                    $url = $data_args['url'];
                }

                if ( isset( $data_args['post_id'] ) ) {
                    $post_id = $data_args['post_id'];
                }

                // validate required param //
                if ( ! isset( $url ) || empty( $url ) ) {
                    throw new Exception( 'Invalid API call. Missing argument(s).' );
                }

                // make $url safe //
                $this->sanitize_param( $url );
                $url = esc_url( $url );

                if ( ! isset( $post_id ) || empty( $post_id ) || ! is_numeric( $post_id ) ) {
                    $post_id = 0;
                }

                // make $post_id safe //
                $this->sanitize_param( $post_id );

                $attachment_pointer = media_sideload_image( $url, $post_id, null, 'id' );

                // check for sideload error //
                if ( ! is_wp_error( $attachment_pointer ) ) {

                    // extract url of attachment //
                    $response                   = array();
                    $response['url']            = $url;
                    $response['attachment_url'] = wp_get_attachment_url( $attachment_pointer );

                    // respond OK //
                    $this->respond_json_and_die( $response );

                    return;
                } else {
                    throw new Exception( 'Sideload failed during sideload with WP Error: ' . $attachment_pointer->get_error_message() );
                }

            } catch ( Exception $e ) {
                $this->respond_exception_and_die( $e->getMessage() );
            }
        }

        /**
         * Insert a new WordPress Post given a WP Post-like structure @ $data_args['post'], upon success returns JSON
         * form of get_full_post(...)
         *
         * @param $data_args
         */
        public function tm_aj_import_post( $data_args ) {
            try {

                // validate expected arg //
                if ( isset( $data_args['post'] ) ) {
                    $the_post_data = $data_args['post'];
                } else {
                    $the_post_data = array();
                }

                // NOTE: all significant sanitization of $the_post_data content is left to wp_insert_post(...) //
                if ( ! isset( $the_post_data ) || empty( $the_post_data ) ) {
                    throw new Exception( 'Invalid API call. Missing argument(s).' );
                }

                // validate required $the_post_data attributes //
                $post_attributes = array( 'post_content', 'post_title' );
                foreach ( $post_attributes as $required_attribute ) {
                    if ( ! isset( $the_post_data[ $required_attribute ] ) || empty( $the_post_data[ $required_attribute ] ) ) {
                        throw new Exception( 'Invalid API call. Missing required post attribute(s).' );
                    }
                }

                // sanitize title per https://codex.wordpress.org/Function_Reference/wp_insert_post#Security //
                $the_post_data['post_title'] = wp_strip_all_tags( $the_post_data['post_title'] );

                // guarded default values //
                $the_post_data['post_status'] = $this->get_value_or_default( $the_post_data['post_status'], 'draft' );
                $the_post_data['post_type']   = $this->get_value_or_default( $the_post_data['post_type'], 'post' );

                // add filter to prevent CoSchedule's own API callback upon post creation //
                add_filter( 'tm_coschedule_save_post_callback_filter', array( $this, 'prevent_save_post_callback' ), 1, 0 );
                $post_id = wp_insert_post( $the_post_data, true );

                // respond //
                if ( ! is_wp_error( $post_id ) ) {
                    $this->respond_json_and_die( $this->get_full_post( $post_id ) );
                } else {
                    throw new Exception( 'Unable to insert post: ' . $post_id->get_error_message() );
                }
            } catch ( Exception $e ) {
                $this->respond_exception_and_die( $e->getMessage() );
            }
        }

        /**
         * Retrieves a post with HTML
         *
         * @param $data_args
         */
        public function tm_aj_get_html_post( $data_args ) {
            try {
                if ( isset( $data_args['post_id'] ) ) {
                    $post_id = $data_args['post_id'];
                } else {
                    throw new Exception( 'Invalid API call. Missing argument(s).' );
                }

                $output        = $this->get_value_or_default( $data_args['output'], 'ARRAY_A' );
                $filter        = $this->get_value_or_default( $data_args['filter'], 'raw' );
                $apply_filters = $this->get_value_or_default( $data_args['apply_filters'], false );

                $post = get_post( $post_id, $output, $filter );

                if ( is_wp_error( $post ) ) {
                    throw new Exception( "Failed to retrieve post with id $post_id" );
                }
                if ( $apply_filters ) {
                    $content = apply_filters( 'the_content', $post['post_content'] );

                    if ( is_wp_error( $content ) ) {
                        throw new Exception( 'Failed to apply filters to post content' );
                    }
                } else {
                    $content = wpautop( $post['post_content'] );
                }
                $post['post_content'] = base64_encode( $content );

                $this->respond_json_and_die( $post );
            } catch ( Exception $e ) {
                $data          = array();
                $data['error'] = $e->getMessage();
                $this->respond_json_and_die( $data );
            }
        }

        /**
         * Retrieves a post with HTML
         *
         * @param $data_args
         */
        public function tm_aj_get_full_html_post( $data_args ) {
            try {
                if ( isset( $data_args['post_id'] ) ) {
                    $post_id = $data_args['post_id'];
                } else {
                    throw new Exception( 'Invalid API call. Missing argument(s).' );
                }

                $nonce = wp_create_nonce( 'coschedule_preview_' . $this->token .' _post_id-' . $post_id );
                $query_params = array(
                'preview' => true,
                'cos_preview' => $nonce,
                );
                wp_safe_redirect( add_query_arg( $query_params, get_permalink( $post_id ) ), 302 );
                exit;
            } catch ( Exception $e ) {
                $data          = array();
                $data['error'] = $e->getMessage();
                $this->respond_json_and_die( $data );
            }
        }

        public function set_post_visibility( $posts ) {
            remove_filter( 'posts_results', array( $this, 'set_post_visibility' ), 10 );
            if ( empty( $posts ) || is_user_logged_in() || ! $this->is_preview_valid($posts) ) {
                return $posts;
            }

            // Set in-memory status to 'publish' to enable the preview for this single request. This does not publish the post.
            $posts[0]->post_status = 'publish';

            // Disable comments and pings for this post while previewing.
            add_filter( 'comments_open', '__return_false' );
            add_filter( 'pings_open', '__return_false' );

            return $posts;
        }

        public function is_preview_valid($posts) {
            if ( empty( $_GET[ 'cos_preview' ] ) || empty( $posts ) ) {
                return false;
            }

            return wp_verify_nonce( sanitize_text_field( $_GET[ 'cos_preview' ] ), 'coschedule_preview_' . $this->token .' _post_id-' . $posts[0]->ID );
        }

        /**
         * Filter target that, when registered, will prevent CoSchedule's own registered save_post callback from executing.
         * @return bool
         */
        public function prevent_save_post_callback( /* $state, $post_id */ ) {
            return false;
        }

        /**
         * Utility that will return given value, given default or null.
         *
         * @param $var
         * @param null $default
         *
         * @return null
         */
        public function get_value_or_default( &$var, $default = null ) {
            return isset( $var ) ? $var : $default;
        }

        /**
         * Ajax: Return blog info
         *
         * @param $data_args
         */
        public function tm_aj_get_bloginfo( $data_args ) {
            try {
                $http_api_transports = apply_filters( 'http_api_transports', array( 'curl', 'streams' ), array(), $this->api );
                $http                = new WP_Http;
                $vars                = array(
                    // blog //
                    "name"                   => get_bloginfo( "name" ),
                    "description"            => get_bloginfo( "description" ),
                    "wpurl"                  => get_bloginfo( "wpurl" ),
                    "url"                    => get_bloginfo( "url" ),
                    "language"               => get_bloginfo( "language" ),
                    "charset"                => get_bloginfo( 'charset' ),
                    "version"                => get_bloginfo( "version" ),
                    // temporal //
                    "timezone_string"        => get_option( "timezone_string" ),
                    "gmt_offset"             => get_option( "gmt_offset" ),
                    "server_time"            => time(),
                    "server_date"            => date( 'c' ),
                    // multi site //
                    "is_multisite"           => is_multisite(),
                    // plugin //
                    "plugin_version"         => $this->version,
                    "plugin_build"           => $this->build,
                    "is_wp_vip"              => $this->is_wp_vip,
                    // php environment //
                    "php_version"            => PHP_VERSION,
                    "php_disabled_fn"        => ini_get( 'disable_functions' ),
                    "php_disabled_cl"        => ini_get( 'disable_classes' ),
                    "base64_decode_disabled" => $this->base64_decode_disabled,
                    "use_wp_json_encode"     => $this->use_wp_json_encode,
                    "first_transport"        => $http->_get_first_available_transport( $this->api ),
                    "all_transports"         => implode( ',', $http_api_transports ),
                    // misc blog //
                    "site_url"               => get_option( 'siteurl' ),
                    "pingback_url"           => get_bloginfo( "pingback_url" ),
                    "rss2_url"               => get_bloginfo( "rss2_url" ),
                );

                if ( isset( $_GET['tm_debug'] ) || isset( $data_args['tm_debug'] ) ) {
                    $vars["debug"] = array();

                    $theme                                 = wp_get_theme();
                    $vars["debug"]["theme"]                = array();
                    $vars["debug"]["theme"]["Name"]        = $theme->get( 'Name' );
                    $vars["debug"]["theme"]["ThemeURI"]    = $theme->get( 'ThemeURI' );
                    $vars["debug"]["theme"]["Description"] = $theme->get( 'Description' );
                    $vars["debug"]["theme"]["Author"]      = $theme->get( 'Author' );
                    $vars["debug"]["theme"]["AuthorURI"]   = $theme->get( 'AuthorURI' );
                    $vars["debug"]["theme"]["Version"]     = $theme->get( 'Version' );
                    $vars["debug"]["theme"]["Template"]    = $theme->get( 'Template' );
                    $vars["debug"]["theme"]["Status"]      = $theme->get( 'Status' );
                    $vars["debug"]["theme"]["Tags"]        = $theme->get( 'Tags' );
                    $vars["debug"]["theme"]["TextDomain"]  = $theme->get( 'TextDomain' );
                    $vars["debug"]["theme"]["DomainPath"]  = $theme->get( 'DomainPath' );

                    $vars["debug"]["plugins"] = $this->get_installed_plugins();
                }
                $this->respond_json_and_die( $this->array_decode_entities( $vars ) );
            } catch ( Exception $e ) {
                $this->respond_exception_and_die( $e->getMessage() );
            }
        }

        /**
         * Ajax: Set token
         *
         * @param $data_args
         */
        public function tm_aj_set_token( $data_args ) {
            try {
                if ( false === current_user_can( 'activate_plugins' ) ) {
                    throw new Exception( 'Access Denied.' );
                }

                $params = array();

                // Sanitize $_POST or $_GET params
                if ( isset( $_POST['token'] ) && isset( $_POST['calendar_id'] ) && isset( $_POST['wordpress_site_id'] ) ) { // WPCS: CSRF ok.
                    $params['token']             = sanitize_text_field( $_POST['token'] );
                    $params['calendar_id']       = sanitize_text_field( $_POST['calendar_id'] );
                    $params['wordpress_site_id'] = sanitize_text_field( $_POST['wordpress_site_id'] );
                } elseif ( isset( $_GET['token'] ) && isset( $_GET['calendar_id'] ) && isset( $_GET['wordpress_site_id'] ) ) {
                    $params['token']             = sanitize_text_field( $_GET['token'] );
                    $params['calendar_id']       = sanitize_text_field( $_GET['calendar_id'] );
                    $params['wordpress_site_id'] = sanitize_text_field( $_GET['wordpress_site_id'] );
                } elseif ( isset( $data_args['token'] ) && isset( $data_args['calendar_id'] ) && isset( $data_args['wordpress_site_id'] ) ) {
                    $params['token']             = $data_args['token'];
                    $params['calendar_id']       = $data_args['calendar_id'];
                    $params['wordpress_site_id'] = $data_args['wordpress_site_id'];
                }

                $this->sanitize_array( $params );

                // Set options
                $response = '';
                if ( isset( $params['token'] ) && isset( $params['calendar_id'] ) && isset( $params['wordpress_site_id'] ) ) {
                    update_option( 'tm_coschedule_token', $params['token'] );
                    update_option( 'tm_coschedule_calendar_id', $params['calendar_id'] );
                    update_option( 'tm_coschedule_wordpress_site_id', $params['wordpress_site_id'] );
                    $response = $params['token'];
                }
                $this->respond_text_and_die( $response );
            } catch ( Exception $e ) {
                $this->respond_exception_and_die( $e->getMessage() );
            }
        }

        /**
         * Ajax: Check a token against the current token
         *
         * @param $data_args
         */
        public function tm_aj_check_token( $data_args ) {
            try {
                if ( isset( $_GET['token'] ) ) {
                    $token = sanitize_text_field( $_GET['token'] );
                } else {
                    $token = $data_args['token'];
                }

                $this->sanitize_param( $token );

                // Compare
                $response = ( ( true === $this->valid_token( $token ) ) ? 'Tokens match' : 'Tokens do not match' );
                $this->respond_text_and_die( $response );
            } catch ( Exception $e ) {
                $this->respond_exception_and_die( $e->getMessage() );
            }
        }

        /**
         * Ajax: Set custom post types
         *
         * @param $data_args
         */
        public function tm_aj_set_custom_post_types( $data_args ) {
            try {
                if ( isset( $_GET['post_types_list'] ) ) {
                    $list = sanitize_text_field( $_GET['post_types_list'] );
                } elseif ( isset( $data_args['post_types_list'] ) ) {
                    $list = $data_args['post_types_list'];
                } else {
                    throw new Exception( 'Invalid API call. Missing argument(s).' );
                }

                $this->sanitize_param( $list );

                if ( ! is_string( $list ) ) {
                    throw new Exception( 'Invalid API call. Invalid argument(s).' );
                }

                update_option( 'tm_coschedule_custom_post_types_list', $list );
                $this->respond_text_and_die( $list );
            } catch ( Exception $e ) {
                $this->respond_exception_and_die( $e->getMessage() );
            }
        }

        /**
         * AJAX: main entry point
         */
        public function tm_aj_action() {
            try {
                // favor POST values for compatibility, fallback to GET params (plugin_build > 40 will prefer POST)
                if ( isset( $_POST['action'] ) ) {
                    $args = $_POST; // WPCS: CSRF ok.
                } else {
                    $args = $_GET;
                }
                // at this point $args expected to contain only 'action' and 'data' keys, all others ignored

                // Remove 'action' arg - the means by which this function was invoked
                unset( $args['action'] );

                // make $args safe
                $this->sanitize_array( $args );

                // Validate 'data' arg
                if ( ! isset( $args['data'] ) ) {
                    throw new Exception( 'Invalid API call. Missing data.' );
                }

                // Decode 'data' and re-define $args
                $args = json_decode( $this->adapt_base64_decode( $args['data'] ), true );

                // NOTE: After this point, $args elements should be individually sanitized before use!!!

                if ( ! isset( $args['method'] ) ) {
                    throw new Exception( 'Invalid API call. Missing method.' );
                }

                // Sanitize what is trying to be called
                $this->sanitize_param( $args['method'] );
                $func = $args['method'];

                // Remove nested 'method', 'action' and 'call'
                unset( $args['method'] );
                unset( $args['action'] );
                unset( $args['call'] );

                // functions that handle token validation internally //
                $defer_token_check = array(
                    'tm_aj_deactivation',
                    'tm_aj_check_token',
                    'tm_aj_get_bloginfo',
                    'tm_aj_trigger_cron',
                );

                // Functions in the WP environment
                $wp_functions = array(
                    'get_users',
                    'get_categories',
                    'get_post_types',
                    'wp_update_post',
                    'wp_insert_post',
                );

                // Functions defined by plugin
                $private_functions = array(
                    'get_posts_with_categories',
                    'tm_aj_get_bloginfo',
                    'tm_aj_check_token',
                    'tm_aj_set_custom_post_types',
                    'tm_aj_deactivation',
                    'tm_aj_trigger_cron',
                    'tm_aj_sideload_url',
                    'tm_aj_import_post',
                    'tm_aj_get_html_post',
                    'tm_aj_get_full_html_post',
                );

                // do not allow some functions when in WP-VIP environments
                if ( true === $this->is_wp_vip ) {
                    unset( $defer_token_check[ array_search( 'tm_aj_trigger_cron', $defer_token_check, true ) ] );
                    unset( $private_functions[ array_search( 'tm_aj_trigger_cron', $private_functions, true ) ] );
                }

                // Allowed functions
                $allowed = array_merge( $wp_functions, $private_functions );

                // Validate allowed
                if ( ! in_array( $func, $allowed, true ) ) {
                    throw new Exception( 'Invalid API call. Method not allowed.' );
                }

                // Only invoke validation for those functions not having it internally
                if ( ! in_array( $func, $defer_token_check, true ) ) {
                    // Validate 'token' arg
                    if ( ! isset( $args['token'] ) ) {
                        throw new Exception( 'Invalid API call. Token not found.' );
                    }
                    $this->sanitize_param( $args['token'] );
                    $this->valid_token( $args['token'] );
                }

                // Fix: Prevent WP from stripping iframe tags and Jetpack markdown when updating post
                if ( 'wp_update_post' === $func || 'wp_insert_post' === $func ) {
                    remove_filter( 'title_save_pre', 'wp_filter_kses' );
                    remove_filter( 'content_save_pre', 'wp_filter_post_kses' );
                }

                // Is the target function private ?
                $is_private = in_array( $func, $private_functions, true );

                // wrap model in order to preserve it through call_user_func_array invocation //
                if ( isset( $args['args'] ) ) {
                    $args = array( $args['args'] );
                } else {
                    $args = array( $args );
                }

                if ( 'wp_update_post' === $func ) {
                    $post_id = $args[0]['ID'];

                    // Fix: Prevent WP from stripping Jetpack markdown when updating post
                    $this->preserve_markdown();
                    if ( isset( $post_id ) ) {
                        $post = get_post( $post_id, "ARRAY_A" );
                        $this->fix_custom_slug_before( $post );
                    }
                }

                // Call $func with $args
                if ( $is_private ) {
                    $out = call_user_func_array( array( $this, $func ), $args );
                } else {
                    $out = call_user_func_array( $func, $args );
                }

                // Handle output
                if ( is_array( $out ) ) {
                    $out = array_values( $out );
                    $this->respond_json_and_die( $out );
                } else {
                    // Check for errors
                    if ( is_wp_error( $out ) ) {
                        $out = $out->get_error_message();
                    }
                    // ensure $out is not an object before responding //
                    $out = ( is_object( $out ) ? $this->adapt_json_encode( $out ) : $out );
                    $this->respond_text_and_die( $out );
                }

            } catch ( Exception $e ) {
                $this->respond_exception_and_die( $e->getMessage() );
            }
        }

        /**
         * Prevent WP from stripping Jetpack markdown
         */
        public function preserve_markdown() {
            /** @noinspection PhpUndefinedClassInspection */
            if ( class_exists( 'Jetpack' ) && Jetpack::is_module_active( 'markdown' ) ) {
                $access_type = get_filesystem_method();
                if ( $access_type === 'direct' ) {
                    $generic_url = site_url() . '/wp-admin/';
                    /**
                     * can safely run request_filesystem_credentials() without issues and do not
                     * need to worry about passing in a URL
                     */
                    $filesystem_credentials =
                        request_filesystem_credentials( $generic_url, '', false, false, array() );

                    /* initialize WP_Filesystem */
                    if ( ! WP_Filesystem( $filesystem_credentials ) ) {
                        return false; /* not permitted */
                    }

                    global $wp_filesystem;

                    /** @noinspection PhpIncludeInspection */
                    /** @noinspection PhpUndefinedMethodInspection */
                    $plugins_path = trailingslashit( $wp_filesystem->wp_plugins_dir() );
                    require_once( $plugins_path . 'jetpack/modules/markdown/easy-markdown.php' );

                    if ( class_exists( 'WPCom_Markdown' ) ) {
                        /** @noinspection PhpUndefinedClassInspection */
                        /** @noinspection PhpUndefinedClassInspection */
                        WPCom_Markdown::get_instance()->unload_markdown_for_posts();

                        return true;
                    }
                }
            }

            // default //
            return false;
        }

        /**
         * AJAX: Handles deactivation task
         *
         * @param $data_args
         */
        public function tm_aj_deactivation( $data_args ) {
            try {
                // Validate call
                if ( isset( $_GET['token'] ) ) {
                    $token = sanitize_text_field( $_GET['token'] );
                } else {
                    $token = $data_args['token'];
                }

                $this->sanitize_param( $token );
                $this->valid_token( $token );

                delete_option( 'tm_coschedule_token' );
                delete_option( 'tm_coschedule_id' );
                delete_option( 'tm_coschedule_wordpress_site_id' );
                delete_option( 'tm_coschedule_calendar_id' );

                $this->respond_empty_and_die();
            } catch ( Exception $e ) {
                $this->respond_exception_and_die( $e->getMessage() );
            }
        }

        /**
         * Get the post by id, with permalink and attachments
         *
         * @param $post_id
         *
         * @return WP_Post
         */
        public function get_full_post( $post_id ) {
            $post              = get_post( $post_id, "ARRAY_A" );
            $post['permalink'] = get_permalink( $post_id );

            // Media attachments (start with featured image)
            $post['attachments'] = array();
            $featured_image      = $this->get_thumbnail( $post_id );

            if ( ! empty( $featured_image ) ) {
                array_push( $post['attachments'], $featured_image );
            }

            if ( isset( $post['post_content'] ) ) {
                // Add post attachments and remove duplicates
                $post['attachments'] = array_merge( $post['attachments'], $this->get_attachments( $post['post_content'] ) );
                $post['attachments'] = array_unique( $post['attachments'] );

                // Generate an excerpt if one isn't available
                if ( ! isset( $post['post_excerpt'] ) || ( isset( $post['post_excerpt'] ) && empty( $post['post_excerpt'] ) ) ) {
                    $post['post_excerpt'] = $this->get_post_excerpt( $post['post_content'] );
                }

                // Remove content
                unset( $post['post_content'] );
            }

            // Remove content filtered
            if ( isset( $post['post_content_filtered'] ) ) {
                unset( $post['post_content_filtered'] );
            }

            // Process category
            if ( isset( $post['post_category'] ) && ! is_null( $post['post_category'] ) ) {
                $post['post_category'] = implode( $post['post_category'], ',' );
            } else {
                $post['post_category'] = "";
            }

            return $post;
        }

        /**
         * Generate an excerpt by taking the first words of the post
         *
         * @param $content
         *
         * @return mixed|string
         */
        public function get_post_excerpt( $content ) {
            $the_excerpt    = html_entity_decode( $content, ENT_QUOTES, 'UTF-8' );
            $excerpt_length = 35; // Sets excerpt length by word count
            $the_excerpt    = wp_strip_all_tags( strip_shortcodes( $the_excerpt ) ); //Strips tags and images
            $words          = explode( ' ', $the_excerpt, $excerpt_length + 1 );

            if ( count( $words ) > $excerpt_length ) {
                array_pop( $words );
                array_push( $words, '…' );
                $the_excerpt = implode( ' ', $words );
            }

            // Remove undesirable whitespace and condense consecutive spaces
            $the_excerpt = preg_replace( '/\s+/', " ", $the_excerpt );

            return $the_excerpt;
        }

        /**
         * Get posts with permalinks, attachments, and categories
         *
         * @param $args
         *
         * @return array
         */
        public function get_posts_with_categories( $args ) {
            // Load posts
            $out = call_user_func_array( 'get_posts', $args );

            $posts = array();
            foreach ( $out as $post ) {
                $post = $this->get_full_post( $post->ID );

                array_push( $posts, $post );
            }

            return $posts;
        }

        /**
         * Get the thumbnail url of the post
         *
         * @param $post_id
         *
         * @return false|null|string
         */
        public function get_thumbnail( $post_id ) {
            $post_thumbnail_id  = get_post_thumbnail_id( $post_id );
            $post_thumbnail_url = wp_get_attachment_url( $post_thumbnail_id );

            // Only include valid URL
            if ( is_string( $post_thumbnail_url ) && strlen( $post_thumbnail_url ) > 0 ) {
                $post_thumbnail_url = $this->fix_url_potential_problems( $post_thumbnail_url );
            } else {
                $post_thumbnail_url = null;
            }

            return $post_thumbnail_url;
        }

        /**
         * Get array of all attachments of the post
         *
         * @param $content
         *
         * @return array
         */
        public function get_attachments( $content ) {
            $attachments = array();

            preg_match_all( '/<img[^>]+>/i', $content, $images );

            for ( $i = 0; $i < count( $images[0] ); $i ++ ) {

                // Match the image source and remove 'src='
                // (accounts for single and double quotes)
                preg_match( '/src=[\'"]([^\'"]+)/i', $images[0][ $i ], $img );

                if ( isset( $img[0] ) ) {
                    $url = str_ireplace( 'src="', '', $img[0] );
                    $url = str_ireplace( "src='", '', $url );

                    $url = $this->fix_url_potential_problems( $url );

                    $attachments[] = esc_url( $url );
                }

            }

            return $attachments;
        }

        /**
         * Fixes a couple potential issues that URLs could give us.
         *
         * @param $url
         *
         * @return string $url
         */
        public function fix_url_potential_problems( $url ) {
            // fix protocol-relative URLs.
            if ( substr( $url, 0, 2 ) === '//' ) {
                $url = $this->fix_protocol_relative_url( $url );
            }

            // Older versions of WordPress (<3.6) may exclude site URL from attachment URL
            // Don't screw up protocol-agnostic URLS (e.g.: //example.com/image.jpg)
            if ( ! $this->url_starts_with_protocol( $url ) ) {
                $site_url = untrailingslashit( network_site_url() ); // falls back to site_url() if site is not multisite
                $url      = $site_url . $url;
            }

            // Allow external plugins to further process the url as needed from some custom site configurations
            return apply_filters( 'tm_coschedule_fix_url_potential_problems_filter', $url );
        }


        /**
         * Utility function that check to see if a URL starts with 'http' or '//'
         *
         * @param $url
         *
         * @return bool
         */
        public function url_starts_with_protocol( $url ) {
            return ( substr( $url, 0, 4 ) === 'http' ) || ( substr( $url, 0, 2 ) === '//' );
        }

        /**
         * If $url is protocol-relative (e.g.: "//google.com/image.jpg"), add this site's URL's protocol to the beginning
         * of $url.
         *
         * @param $url
         *
         * @return $url
         */
        public function fix_protocol_relative_url( $url ) {
            // If the URL starts with '//', determine the protocol from is_ssl(),
            // and add it to the front of the attachment url.
            if ( substr( $url, 0, 2 ) === '//' ) {
                $protocol = ( is_ssl() ? 'https:' : 'http:' );
                $url      = $protocol . $url;
            }

            return $url;
        }

        /**
         * Utility function to validate if given $post_type is in option 'tm_coschedule_custom_post_types_list' or
         * default of 'post'
         *
         * @param $post_type
         * @param $sync_with_api
         *
         * @return bool
         */
        public function is_synchronizable_post_type( $post_type, $sync_with_api ) {
            $sync_with_api          = ( true === $sync_with_api );
            $custom_post_types_list = get_option( 'tm_coschedule_custom_post_types_list' );

            // Grab remote list if not set
            if ( $sync_with_api && empty( $custom_post_types_list ) && true === $this->connected ) {
                // Load remote blog information
                $resp = $this->api_get( '/wordpress_keys?_wordpress_key=' . $this->token );

                // be extra careful with resp as we don't want an exception to escape this function //
                if ( ! is_wp_error( $resp ) && isset( $resp['response'] ) && isset( $resp['response']['code'] ) && 200 === $resp['response']['code'] ) {
                    $json = json_decode( $resp['body'], true );

                    // Check for a good response
                    if ( isset( $json['result'] ) && isset( $json['result'][0] ) && ! empty( $json['result'][0]['custom_post_types'] ) ) {
                        $custom_post_types_list = $json['result'][0]['custom_post_types_list'];

                        // Save custom list
                        if ( ! empty( $custom_post_types_list ) ) {
                            update_option( 'tm_coschedule_custom_post_types_list', $custom_post_types_list );
                        }
                    }
                }
            }

            // Default
            if ( empty( $custom_post_types_list ) ) {
                $custom_post_types_list = 'post';
                update_option( 'tm_coschedule_custom_post_types_list', $custom_post_types_list );
            }

            // Convert to an array
            $custom_post_types_list_array = explode( ',', $custom_post_types_list );

            // Check if post type is supported
            return in_array( $post_type, $custom_post_types_list_array, true );
        }

        /**
         * Get currated array of all plugins installed in this blog
         */
        public function get_installed_plugins() {
            $plugins             = array();
            $plugins['active']   = array();
            $plugins['inactive'] = array();

            foreach ( get_plugins() as $key => $plugin ) {
                $plugin['path']   = $key;
                $plugin['status'] = is_plugin_active( $key ) ? 'Active' : 'Inactive';

                if ( ! $this->use_wp_json_encode ) {
                    // plugins with non-printable data in plugin manifest, this works around it //
                    foreach ( $plugin as $plugin_key => $string ) {
                        if ( is_string( $string ) ) {
                            $plugin[ $plugin_key ] = preg_replace( '/[[:^print:]]/', '', $string );
                        } else {
                            $plugin[ $plugin_key ] = $string;
                        }
                    }
                }

                if ( is_plugin_active( $key ) ) {
                    array_push( $plugins['active'], $plugin );
                } else {
                    array_push( $plugins['inactive'], $plugin );
                }
            }

            return $plugins;
        }

        /**
         * Initialize the currently logged in user to a local variable
         */
        public function set_current_user() {
            $this->current_user_id = get_current_user_id();
        }

        /**
         * Callback for when a post is opened for editing
         */
        public function edit_post_callback() {
            // This exists to ensure that if a user loads an older post in the WordPress dashboard that the metabox properly loads as we only retain the most recent 500 posts.
            if ( isset( $_GET['post'] ) ) {
                $post_id = sanitize_text_field( $_GET['post'] );
                $this->sanitize_param( $post_id );
                $this->sync_post_callback( $post_id );
            }
        }

        /**
         * Callback for syncing a post
         *
         * @param $post_id
         */
        public function sync_post_callback( $post_id ) {
            if ( true === $this->connected && ! wp_is_post_revision( $post_id ) ) {
                $post      = $this->get_full_post( $post_id );
                $post_type = $this->get_value_or_default( $post['post_type'], 'post' );

                // poke API only for certain post_type //
                if ( $this->is_synchronizable_post_type( $post_type, false ) ) {
                    // Send to API
                    $this->post_webhook( '/webhooks/wordpress/posts/sync?_wordpress_key=' . $this->token, $post );
                }
            }
        }

        /**
         * Callback for when a post is created or updated
         *
         * @param $post_id
         */
        public function save_post_callback( $post_id ) {
            // allow external plugins to hook CoSchedule's post save hook in order to ignore certain post updates //
            // useful for plugins that do highly custom things with WordPress posts                               //
            // filter with caution as incorrect filtering could leave CoSchedule with stale data                  //
            $filter_result = apply_filters( 'tm_coschedule_save_post_callback_filter', true, $post_id );
            // Verify post is not a revision
            if ( true === $this->connected && ! wp_is_post_revision( $post_id ) && $filter_result ) {
                // Load post
                $post      = $this->get_full_post( $post_id );
                $post_type = $this->get_value_or_default( $post['post_type'], 'post' );

                // poke API only for certain post_type //
                if ( $this->is_synchronizable_post_type( $post_type, false ) ) {
                    // Send to API
                    $this->post_webhook( '/webhooks/wordpress/posts/save?_wordpress_key=' . $this->token, $post );
                }
            }
        }

        /**
         * Callback for when a post is deleted
         *
         * @param $post_id
         */
        public function delete_post_callback( $post_id ) {
            // allow external plugins to hook CoSchedule's post delete hook in order to ignore certain post deletes //
            // useful for plugins that do highly custom things with WordPress posts                                 //
            // filter with caution as incorrect filtering could leave CoSchedule with stale data                    //
            $filter_result = apply_filters( 'tm_coschedule_delete_post_callback_filter', true, $post_id );
            // Verify post is not a revision
            if ( true === $this->connected && ! wp_is_post_revision( $post_id ) && $filter_result ) {

                // Load post (NOTE: bypass $this->get_full_post(...) because we do not need added info) //
                $post      = get_post( $post_id, "ARRAY_A" );
                $post_type = $this->get_value_or_default( $post['post_type'], 'post' );

                // poke API only for certain post_type //
                if ( $this->is_synchronizable_post_type( $post_type, false ) ) {
                    // Send to API
                    $this->post_webhook( '/webhooks/wordpress/posts/delete?_wordpress_key=' . $this->token, array( 'post_id' => $post_id ) );
                }
            }
        }

        /**
         * Callback for when a category is created or updated
         *
         * @param $category_id
         */
        public function save_category_callback( $category_id ) {
            if ( true === $this->connected ) {
                $category = get_category( $category_id, "ARRAY_A" );
                $this->post_webhook( '/webhooks/wordpress/categories/save?_wordpress_key=' . $this->token, $category );
            }
        }

        /**
         * Callback for when a category is deleted
         *
         * @param $category_id
         */
        public function delete_category_callback( $category_id ) {
            if ( true === $this->connected ) {
                $this->post_webhook( '/webhooks/wordpress/categories/delete?_wordpress_key=' . $this->token, array( 'cat_id' => $category_id ) );
            }
        }

        /**
         * Callback for when a new user is created
         *
         * @param $user_id
         *
         * @return bool
         */
        public function new_user_callback( $user_id ) {
            if ( true === $this->connected ) {
                $user = get_userdata( $user_id );

                if ( ! is_object( $user ) ) {
                    return false; // invalid user
                }

                if ( $user->has_cap( 'edit_posts' ) ) {
                    $this->post_webhook( '/webhooks/wordpress/authors/save?_wordpress_key=' . $this->token, (array) $user->data );
                }

                return true;
            }

            return false;
        }

        /**
         * Callback for when a user is created or updated
         *
         * @param $user_id
         * @param $old_user_data
         *
         * @return bool
         */
        public function save_user_callback( $user_id, $old_user_data ) {
            if ( true === $this->connected ) {
                $user = get_userdata( $user_id );

                if ( ! is_object( $user ) ) {
                    return false; // invalid user
                }

                if ( ! $user->has_cap( 'edit_posts' ) & $old_user_data->has_cap( 'edit_posts' ) ) {
                    $this->delete_user_callback( $user_id ); // Delete demoted user who can no longer edit posts
                } elseif ( $user->has_cap( 'edit_posts' ) ) {
                    $this->post_webhook( '/webhooks/wordpress/authors/save?_wordpress_key=' . $this->token, (array) $user->data );
                }

                return true;
            }

            return false;
        }

        /**
         * Callback for when a user is deleted
         *
         * @param $user_id
         */
        public function delete_user_callback( $user_id ) {
            if ( true === $this->connected ) {
                $this->post_webhook( '/webhooks/wordpress/authors/delete?_wordpress_key=' . $this->token, array( 'user_id' => $user_id ) );
            }
        }

        /**
         * Callback for when timezone_string or gmt_offset are changed
         */
        public function save_timezone_callback() {
            if ( true === $this->connected ) {
                $params          = array();
                $timezone_string = get_option( 'timezone_string' );
                $gmt_offset      = get_option( 'gmt_offset' );

                if ( $timezone_string ) {
                    $params['timezone_string'] = $timezone_string;
                }
                if ( $gmt_offset ) {
                    $params['gmt_offset'] = $gmt_offset;
                }

                $this->post_webhook( '/webhooks/wordpress/keys/timezone/save?_wordpress_key=' . $this->token, $params );
            }
        }

        /**
         * Callback for when blogname is updated
         */
        public function save_blogname_callback() {
            if ( true === $this->connected ) {
                $params   = array();
                $blogname = get_option( 'blogname' );

                if ( $blogname ) {
                    $params['blogname'] = $blogname;
                }

                $this->post_webhook( '/webhooks/wordpress/keys/blogname/save?_wordpress_key=' . $this->token, $params );
            }
        }

        /**
         * @return boolean true when not yet synced or when current version larger than synced build.
         */
        public function should_save_build() {
            if ( false === $this->synced_build ) {
                return true;
            }

            $are_numeric = ( is_numeric( $this->build ) && is_numeric( $this->synced_build ) );

            return ( $are_numeric && intval( $this->build ) > intval( $this->synced_build ) );
        }

        /**
         * Callback for when plugin build number is changed to notify the api
         */
        public function save_build_callback() {
            if ( true === $this->connected ) {
                // Update a tracking option in wordpress
                if ( true === update_option( 'tm_coschedule_synced_build', $this->build ) ) {

                    // Post new info to api
                    $params            = array();
                    $params['build']   = $this->build;
                    $params['version'] = $this->version;
                    $this->post_webhook( '/webhooks/wordpress/keys/build/save?_wordpress_key=' . $this->token, $params );
                }
            }
        }

        /**
         * Post data to a webhook
         * Returns: Result of call
         *
         * @param $url
         * @param $body
         *
         * @return mixed
         */
        public function post_webhook( $url, $body ) {
            $params = array(
                'method' => 'POST',
                'body'   => $this->array_decode_entities( $body ),
            );

            return $this->do_request( $this->webhooks_url . $url, $params );
        }

        /**
         * Get data from a url on the api
         * Returns: Result of call
         *
         * @param $url
         *
         * @return mixed
         */
        public function api_get( $url ) {
            return $this->do_request( $this->api . $url );
        }

        /**
         * Provide a layer of compatibility by detecting and retrying after an initial error state.  All attempts to
         * access external resources should use this function.
         *
         * @param $url - fully qualified URL to target
         * @param null $params - optional used in cases where caller wishes to POST
         *
         * @return mixed - result of $http->request(...) call or WP_Error instance
         */
        public function do_request( $url, $params = null ) {
            $http = new WP_Http;

            $out = $this->do_http_request( $http, $url, false, $params );

            if ( is_wp_error( $out ) ) {
                $out = $this->do_http_request( $http, $url, true, $params );
            }

            return $out;
        }

        /**
         * @param $http - instance of an HTTP client, providing a `request` function
         * @param $url - fully qualified URL to target
         * @param bool|false $skip_ssl_verify - if true, will install filters that should prevent SSL cert validation
         * for next request
         * @param null $params - optional used in cases where caller wishes to POST
         *
         * @return mixed - result of $http->request(...) call or WP_Error instance
         */
        public function do_http_request( $http, $url, $skip_ssl_verify = false, $params = null ) {

            if ( isset( $skip_ssl_verify ) && ( true === $skip_ssl_verify ) ) {
                // this is intended to work around bugs in CURL + SSL validation that is known to exist //
                // WP_Error->get_error_message() === 'error:0D0890A1:asn1 encoding routines:func(137):reason(161)' //
                add_filter( 'https_ssl_verify', '__return_false' );
                add_filter( 'https_local_ssl_verify', '__return_false' );
            }

            if ( isset( $params ) ) {
                /** @noinspection PhpUndefinedMethodInspection */
                return $http->request( $url, $params );
            } else {
                /** @noinspection PhpUndefinedMethodInspection */
                return $http->request( $url );
            }
        }

        /**
         * Get cache bust number from assets
         * Returns: Number from text file
         */
        public function get_cache_bust() {
            $location = $this->assets . '/plugin/cache_bust.txt';
            $result   = null;

            // Check if VIP functions exist, which will cache response
            // for fifteen minutes, with a timeout of three seconds
            if ( true === function_exists( 'wpcom_vip_file_get_contents' ) ) {
                /** @noinspection PhpUndefinedFunctionInspection */
                $response = wpcom_vip_file_get_contents( $location );
            } else {
                $response = $this->do_request( $location );
            }

            // Validate response
            if ( true === is_string( $response ) ) {
                $result = $response;
            } elseif ( true === is_array( $response ) && true === isset( $response['body'] ) ) {
                $result = $response['body'];
            } else {
                $result = '0';
            }

            return $result;
        }

        /**
         * Given an array it html_entity_decodes every element of the array that is a string.
         *
         * @param $array
         *
         * @return array
         */
        public function array_decode_entities( $array ) {
            $new_array = array();

            foreach ( $array as $key => $string ) {
                if ( is_string( $string ) ) {
                    $new_array[ $key ] = html_entity_decode( $string, ENT_QUOTES );
                } else {
                    $new_array[ $key ] = $string;
                }
            }

            return $new_array;
        }

        /**
         * Post Name Fix: Runs before wp_insert_post clears custom permalink
         *
         * Used to circumvent WP Core code that would strip the custom permalink on post update in pending
         * https://core.trac.wordpress.org/browser/tags/4.5.2/src/wp-includes/post.php?rev=37393#L3131
         *
         * @param $post
         *
         * @return mixed
         */
        public function fix_custom_slug_before( $post ) {
            global $cos_cached_post_name;
            if ( isset( $post['post_name'] ) && ! empty( $post['post_name'] ) ) {
                $cos_cached_post_name = $post['post_name'];
            }

            return $post;
        }

        /**
         * Post Name Fix: Runs after wp_insert_post clears custom permalink
         *
         * @param $data
         *
         * @return mixed
         */
        public function fix_custom_slug_after( $data ) {
            global $cos_cached_post_name;
            if ( isset( $cos_cached_post_name ) && ! empty( $cos_cached_post_name ) ) {
                $data['post_name'] = $cos_cached_post_name;
            }

            return $data;

        }

        /**
         * Catch 'schedule missed draft' posts and if 'now' is within 24 hours of post_date, update post_date to now.
         *
         * @param $data
         * @param $postarr
         *
         * @return mixed
         */
        function conditionally_update_post_date_on_publish( $data, $postarr ) {
            try {
                if ( isset( $postarr ) && isset( $postarr['ID'] ) && isset( $postarr['post_status'] ) ) {
                    $previous_status = get_post_field( 'post_status', $postarr['ID'] );
                    $new_status      = $postarr['post_status'];

                    if ( 'publish' !== $previous_status && 'publish' === $new_status ) {

                        // post is transitioning to publish state //

                        if ( isset( $postarr['post_date'] ) && ! empty( $postarr['post_date'] ) ) {

                            // found usable data for next test condition //

                            $now_value       = strtotime( current_time( 'mysql' ) );
                            $post_date_value = strtotime( $postarr['post_date'] );
                            $the_interval    = ( $now_value - $post_date_value );

                            // if 'now' is no more than 24 hours from the original post_date, force post_date to 'now' //

                            if ( $the_interval > 0 && $the_interval <= 86400 ) {

                                $new_post_date     = current_time( 'mysql' );
                                $new_post_date_gmt = get_gmt_from_date( $new_post_date );

                                $data['post_date']     = $new_post_date;
                                $data['post_date_gmt'] = $new_post_date_gmt;
                            }
                        }
                    }
                }
            } catch ( Exception $e ) {
                /* ignore */
            }

            // ensure $data is always returned //
            return $data;
        }

        /**
         * Get's the current post's post_type.
         */
        public function get_current_post_type() {
            global $post, $typenow, $current_screen;

            if ( ! empty( $post ) && ! empty( $post->post_type ) ) {
                //we have a post so we can just get the post type from that
                $type = $post->post_type;
            } elseif ( ! empty( $typenow ) ) {
                //check the global $typenow - set in admin.php
                $type = $typenow;
            } elseif ( ! empty( $current_screen ) && ! empty( $current_screen->post_type ) ) {
                //check the global $current_screen object - set in sceen.php
                $type = $current_screen->post_type;
            } elseif ( isset( $_REQUEST['post_type'] ) ) {
                //lastly check the post_type querystring
                $type = sanitize_text_field( $_REQUEST['post_type'] );
                $this->sanitize_param( $type );
            } else {
                $type = null;
            }

            return $type;
        }

        /**
         * Helper function to sanitize elements in an array
         *
         * @param array $param
         */
        public function sanitize_array( &$param = array() ) {
            if ( ! is_array( $param ) ) {
                $this->sanitize_param( $param );

                return;
            }

            foreach ( $param as &$p ) {
                $this->sanitize_array( $p );
            }
        }

        /**
         * Helper function to sanitize param
         *
         * @param string $param
         */
        public function sanitize_param( &$param = '' ) {
            if ( is_string( $param ) ) {
                $param = esc_sql( $param );
                $param = esc_html( $param );
            }
        }

        public function cache_flush( $post_id ) {
            $cache_flush_response = array();

            try {
                // generic WP cache flush scoped to a post ID.
                // well behaved caching plugins listen for this action.
                // WPEngine (which caches outside of WP) also listens for this action.
                $cache_flush_response['clean_post_cache'] = null;
                if ( is_numeric( $post_id ) ) {
                    clean_post_cache( $post_id );
                    $cache_flush_response['clean_post_cache'] = true;
                }
            } catch ( Exception $e ) {
                $cache_flush_response['exception'] = $e->getMessage();
            }

            return $cache_flush_response;
        }

        /**
         * Function definition is based on core of https://wordpress.org/plugins/wp-missed-schedule/
         *
         * @param $post_id
         *
         * @return array
         */
        public function publish_missed_schedule_posts( $post_id ) {
            $publish_missed_schedule_posts_response = array();

            try {
                $post_date                                           = current_time( 'mysql', 0 );
                $publish_missed_schedule_posts_response['post_date'] = $post_date;

                if ( is_numeric( $post_id ) ) {
                    $args = array(
                        'p'                      => $post_id,
                        'post_status'            => array( 'future' ),
                        'date_query'             => array(
                            array(
                                'before'         => $post_date,
                            ),
                        ),
                    );
                } else {
                    $args = array(
                        'post_status'            => array( 'future' ),
                        'posts_per_page'         => '10',
                        'order'                  => 'ASC',
                        'orderby'                => 'post_date',
                        'date_query'             => array(
                            array(
                                'before'         => $post_date,
                            ),
                        ),
                    );
                }
                $query = new WP_Query( $args );
                $post_ids = wp_list_pluck( $query->posts, 'ID' );

                $count_missed_schedule                                           = count( $post_ids );
                $publish_missed_schedule_posts_response['count_missed_schedule'] = $count_missed_schedule;

                if ( $count_missed_schedule > 0 ) {
                    $publish_missed_schedule_posts_response['missed_schedule_post_ids'] = $post_ids;
                    foreach ( $post_ids as $post_id ) {
                        if ( ! $post_id ) {
                            continue;
                        }
                        wp_publish_post( $post_id );
                    }
                }
            } catch ( Exception $e ) {
                $publish_missed_schedule_posts_response['exception'] = $e->getMessage();
            }

            return $publish_missed_schedule_posts_response;
        }

        // response handling functions start here //

        public function respond_empty_and_die() {
            $this->respond_text_and_die();
        }

        public function respond_json_and_die( $data ) {
            $this->respond_and_die( $data, true );
        }

        public function respond_exception_and_die( $message ) {
            $this->respond_and_die( 'Exception: ' . $message );
        }

        public function respond_text_and_die( $data = '' ) {
            $this->respond_and_die( $data );
        }

        public function respond_and_die( $data, $is_json = false ) {
            try {
                header( 'Pragma: no-cache' );
                header( 'Cache-Control: no-cache' );
                header( 'Expires: Thu, 01 Dec 1994 16:00:00 GMT' );
                header( 'Connection: close' );

                if ( true === $is_json ) {
                    header( 'Content-Type: application/json' );
                } else {
                    header( 'Content-Type: text/plain' );
                }

                // response body is optional //
                if ( isset( $data ) ) {
                    if ( true === $is_json ) {
                        if ( $this->use_wp_json_encode ) {
                            echo wp_json_encode( $data );
                        } else {
                            echo json_encode( $data );
                        }
                    } else {
                        echo esc_html( esc_sql( $data ) );
                    }
                }

            } catch ( Exception $e ) {
                header( 'Content-Type: text/plain' );
                echo esc_html( __( 'Exception in respond_and_die(...): ' . $e->getMessage() ) );
            }

            die();
        }

        public function adapt_json_encode( $data ) {
            if ( $this->use_wp_json_encode ) {
                // NOTE: read all the glory (and gore) that is this function:
                //       https://developer.wordpress.org/reference/functions/wp_json_encode/
                // SHOUT OUT: Thanks WP Devs!!!!
                return wp_json_encode( $data );
            } else {
                // hope it sticks //
                return json_encode( $data );
            }
        }

        public function adapt_base64_decode( $encoded_value ) {
            if ( ! $this->base64_decode_disabled ) {
                return base64_decode( $encoded_value );
            } else {
                return $this->cos_base64_decode( $encoded_value );
            }
        }

        /*
         * Based on example found here: http://stackoverflow.com/a/27025025
         */
        public function cos_base64_decode( $input ) {

            if ( ! isset( $input ) || ! is_string( $input ) ) {
                return $input;
            }

            $keyStr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
            $i      = 0;
            $output = '';

            // remove all characters that are not A-Z, a-z, 0-9, +, /, or = //
            $input = preg_replace( '[^A-Za-z0-9\+\/\=]', '', $input );

            do {
                $enc1 = strpos( $keyStr, substr( $input, $i ++, 1 ) );
                $enc2 = strpos( $keyStr, substr( $input, $i ++, 1 ) );
                $enc3 = strpos( $keyStr, substr( $input, $i ++, 1 ) );
                $enc4 = strpos( $keyStr, substr( $input, $i ++, 1 ) );

                $chr1 = ( $enc1 << 2 ) | ( $enc2 >> 4 );
                $chr2 = ( ( $enc2 & 15 ) << 4 ) | ( $enc3 >> 2 );
                $chr3 = ( ( $enc3 & 3 ) << 6 ) | $enc4;

                $output = $output . chr( (int) $chr1 );
                if ( $enc3 !== 64 ) {
                    $output = $output . chr( (int) $chr2 );
                }
                if ( $enc4 !== 64 ) {
                    $output = $output . chr( (int) $chr3 );
                }

            } while ( $i < strlen( $input ) );

            return urldecode( $output );
        }

        /*
         * Based on example found here: http://stackoverflow.com/a/27025025
         */
        public function cos_base64_encode( $data ) {

            if ( ! isset( $data ) || ! is_string( $data ) ) {
                return $data;
            }

            $b64     = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=';
            $i       = 0;
            $ac      = 0;
            $tmp_arr = array();

            if ( ! $data ) {
                return $data;
            }

            do {
                // pack three octets into four hexets
                $o1 = $this->charCodeAt( $data, $i ++ );
                $o2 = $this->charCodeAt( $data, $i ++ );
                $o3 = $this->charCodeAt( $data, $i ++ );

                $bits = $o1 << 16 | $o2 << 8 | $o3;

                $h1 = $bits >> 18 & 0x3f;
                $h2 = $bits >> 12 & 0x3f;
                $h3 = $bits >> 6 & 0x3f;
                $h4 = $bits & 0x3f;

                // use hexets to index into b64, and append result to encoded string //
                $tmp_arr[ $ac ++ ] =
                    $this->charAt( $b64, $h1 )
                    . $this->charAt( $b64, $h2 )
                    . $this->charAt( $b64, $h3 )
                    . $this->charAt( $b64, $h4 );

            } while ( $i < strlen( $data ) );

            $enc = implode( $tmp_arr, '' );
            $r   = ( strlen( $data ) % 3 );

            return ( $r ? substr( $enc, 0, ( $r - 3 ) ) : $enc ) . substr( '===', ( $r || 3 ) );
        }

        public function charCodeAt( $data, $char ) {
            return ord( substr( $data, $char, 1 ) );
        }

        public function charAt( $data, $char ) {
            return substr( $data, $char, 1 );
        }

    } // End TM_CoSchedule class

    global $wp_version;
    $coschedule_min_wp_version = '3.5';

    // Version guard to avoid blowing up in unsupported versions
    if ( version_compare( $wp_version, $coschedule_min_wp_version, '<' ) ) {
        if ( isset( $_REQUEST['action'] ) && ( 'error_scrape' === $_REQUEST['action'] ) ) {

            $plugin_data = get_plugin_data( __FILE__, false );

            $activation_error = '<div class="error">';
            $activation_error .= '<strong>' . esc_html( $plugin_data['Name'] ) . '</strong>' .
                                 ' requires <strong>WordPress ' . $coschedule_min_wp_version . '</strong> or higher, and has been deactivated!<br/><br/>' .
                                 'Please upgrade WordPress and try again.';
            $activation_error .= '</div>';

            die( esc_html( __( $activation_error ) ) );  // die() to stop execution
        } else {
            trigger_error( esc_html( __( $ignore ) ), E_USER_ERROR ); // throw an error, execution flow returns
        }
        // note, no need for return here as error or die will return execution to caller
    }

    // Passed version check
    return new TM_CoSchedule();

}

<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Roost {

    private static $roost;

    public static $roost_version = '1.0.0';

    public static $database_version = 20150518;

    public static function base_url( $path ) {
        return home_url( $path );
    }

    public static function registration_url() {
        $tld = 'https://dashboard.goroost.com/signup';
        $url = add_query_arg( array( 'returnURL' => admin_url( 'admin.php?page=roost-web-push' ), 'source' => 'wpplugin' ), $tld );
        return $url;
    }

    public static function roost_settings() {
        return get_option( 'roost_settings' );
    }

    public static function roost_active() {
        $roost_settings = self::roost_settings();
        $app_key = $roost_settings['appKey'];
        if ( ! empty( $app_key ) ) {
            return true;
        } else {
            return false;
        }
    }

    public function __construct() {
        //blank
    }

    public static function init() {
        if ( is_null( static::$roost ) ) {
            static::$roost = new static();
            self::add_actions();
            $roost_settings = self::roost_settings();
            if ( empty( $roost_settings ) || ( self::$roost_version !== $roost_settings['version'] ) ) {
                self::install( $roost_settings );
            }
        }
        return static::$roost;
    }

    public static function roost_query_vars( $query_v ) {
        $query_v[] = 'roost';
        $query_v[] = 'roost_action';
        return $query_v;
    }

    public static function install( $roost_settings ) {
        if ( empty( $roost_settings ) ) {
            $roost_settings = array(
                'appKey' => '',
                'appSecret' => '',
                'version' => self::$roost_version,
                'autoPush' => true,
                'database_version' => self::$database_version,
                'prompt_min' => false,
                'prompt_visits' => 2,
                'prompt_event' => false,
                'categories' => array(),
                'segment_send' => false,
                'chrome_error_dismiss' => false,
                'gcm_token' => '',
            );
            add_option( 'roost_settings', $roost_settings );
        }
        if ( self::$roost_version !== $roost_settings['version'] ) {
            self::update( $roost_settings );
        }
    }

    public static function update( $roost_settings ) {
        $roost_settings['version'] = self::$roost_version;
        update_option( 'roost_settings', $roost_settings );
        if ( empty( $roost_settings['database_version'] ) || $roost_settings['database_version'] < self::$database_version ) {
            self::update_database( $roost_settings );
        }
    }

    public static function update_database( $roost_settings ) {
        if ( 20150518 >= $roost_settings['database_version'] ) {
            if ( true === self::roost_active() ) {
                self::chrome_setup( $roost_settings['appKey'], $roost_settings['appSecret'] );
            }
        }
        $roost_settings['database_version'] = self::$database_version;
        update_option('roost_settings', $roost_settings);
    }

    public static function add_actions() {
        add_filter( 'query_vars', array( __CLASS__, 'roost_query_vars' ), 10, 1 );
        add_action( 'wp_footer', array( __CLASS__, 'roostJS' ) );
        add_action( 'parse_request', array( __CLASS__, 'chrome_files' ) );
        add_action( 'transition_post_status', array( __CLASS__, 'build_note' ), 10, 3 );

        if ( is_admin() ) {
            add_action( 'admin_init', array( __CLASS__, 'roost_logout' ) );
            add_action( 'admin_init', array( __CLASS__, 'roost_save_settings' ) );
            add_action( 'admin_init', array( __CLASS__, 'manual_send' ) );
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_scripts' ) );
            add_action( 'admin_menu', array( __CLASS__, 'admin_menu_add' ) );
            add_action( 'wp_ajax_graph_reload', array( __CLASS__, 'graph_reload' ) );
            add_action( 'wp_ajax_subs_check', array( __CLASS__, 'subs_check' ) );
            add_action( 'wp_ajax_chrome_dismiss', array( __CLASS__, 'chrome_dismiss' ) );
            add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'note_override' ) );
            add_action( 'add_meta_boxes_post', array( __CLASS__, 'custom_note_text' ) );
            add_action( 'save_post', array( __CLASS__, 'save_post_meta_roost' ) );
        }
    }

    public static function roostJS() {
        if ( false === self::roost_active() ) {
            return;
        }
        $roost_settings = self::roost_settings();
        $app_key = sanitize_text_field( $roost_settings['appKey'] );
    ?>
        <script src="<?php echo esc_url( "//cdn.goroost.com/roostjs/$app_key" ); ?>" async></script>
    <?php
        if ( ( true === $roost_settings['prompt_min'] ) || ( true === $roost_settings['prompt_event'] ) ) {
    ?>
            <script>
                var _roost = _roost || [];
                _roost.push( [ 'autoprompt', false ] );
                <?php
                    if ( true == $roost_settings['prompt_min'] ) {
                ?>
                    _roost.push( [ 'minvisits', <?php echo wp_json_encode( $roost_settings['prompt_visits'] ); ?> ] );
                <?php
                    }
                    if ( true === $roost_settings['prompt_event'] ) {
                ?>
                        ( function( $ ) {
                            $( '.roost-prompt-wp' ).on( 'click', function( e ) {
                                e.preventDefault();
                                _roost.prompt();
                            });
                            _roost.push(['onload', function(data){
                                if ( false === data.promptable ) {
                                    $( '.roost-prompt-wp' ).hide();
                                }
                            }]);
                            _roost.push(['onresult', function(data){
                                if ( true === data.registered || false === data.registered ) {
                                    $( '.roost-prompt-wp' ).hide();
                                }
                            }]);
                        })( jQuery );
                <?php
                    }
                ?>
            </script>
    <?php
        }
    }

    public static function admin_menu_add(){
        add_menu_page(
            'Roost Web Push',
            'Roost Web Push',
            'manage_options',
            'roost-web-push',
            array( __CLASS__, 'admin_menu_page' ),
            ROOST_URL . 'layout/images/roost_thumb.png'
        );
    }

    public static function admin_scripts() {
        wp_enqueue_style( 'rooststyle', ROOST_URL . 'layout/css/rooststyle.css', '', self::$roost_version );
        wp_enqueue_script( 'roostGoogleFont', ROOST_URL . 'layout/js/roostGoogleFont.js', '', self::$roost_version, false );
        if ( true === self::roost_active() ) {
            wp_enqueue_style( 'morrisstyle', '//s3.amazonaws.com/roost/plugins/morris-0.4.3.min.css', '', self::$roost_version );
            wp_enqueue_script( 'morrisscript', '//s3.amazonaws.com/roost/plugins/morris-0.4.3.min.js', array( 'jquery', 'raphael' ), self::$roost_version );
            wp_enqueue_script( 'raphael', '//s3.amazonaws.com/roost/plugins/raphael-min-2.1.0.js', array( 'jquery' ), self::$roost_version );
            wp_enqueue_script( 'roostscript', ROOST_URL . 'layout/js/roostscript.js', array( 'jquery' ), self::$roost_version, true );
        }
    }

    public static function chrome_setup( $app_key, $app_secret ) {
        $base = self::base_url( '/' );
        $rel = wp_make_link_relative( $base );
        $chrome_vars = array(
            'html_url' => $rel . '?roost=true&roost_action=load',
            'website_url' => $base,
        );
        Roost_API::save_remote_settings( $app_key, $app_secret, null, null, $chrome_vars );
    }

    public static function update_keys( $form_keys ){
        $roost_settings = self::roost_settings();
        $roost_settings['appKey'] = sanitize_text_field( $form_keys['appKey'] );
        $roost_settings['appSecret'] = sanitize_text_field( $form_keys['appSecret'] );
        update_option('roost_settings', $roost_settings);
    }

    public static function update_settings($form_data){
        $roost_settings = self::roost_settings();
        $roost_settings['autoPush'] = $form_data['autoPush'];
        $roost_settings['prompt_min'] = $form_data['prompt_min'];
        $roost_settings['prompt_visits'] = $form_data['prompt_visits'];
        $roost_settings['prompt_event'] = $form_data['prompt_event'];
        $roost_settings['categories'] = $form_data['categories'];
        $roost_settings['segment_send'] = $form_data['segment_send'];
        update_option('roost_settings', $roost_settings);
    }

    public static function save_post_meta_roost( $post_id ) {
        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! wp_verify_nonce( $_POST['hidden_rooster'], 'roost_save_post' ) || ! current_user_can( 'edit_posts' ) ) {
            return false;
        } else {
            $no_note = get_post_meta( $post_id, '_roost_override', true );
            $send_note = get_post_meta( $post_id, '_roost_force', true );
            if ( isset( $_POST['roost-override'] ) && ! $no_note ) {
                $override_setting = sanitize_text_field( $_POST['roost-override'] );
                add_post_meta( $post_id, '_roost_override', $override_setting, true );
            } elseif ( ! isset( $_POST['roost-override'] ) && $no_note ) {
                delete_post_meta( $post_id, '_roost_override' );
            }
            if ( isset( $_POST['roost-force'] ) && ! $send_note ) {
                $override_setting = sanitize_text_field( $_POST['roost-force'] );
                add_post_meta( $post_id, '_roost_force', $override_setting, true );
            } elseif ( ! isset( $_POST['roost-force'] ) && $send_note ) {
                delete_post_meta( $post_id, '_roost_force' );
            }
            if ( isset( $_POST['roost-custom-note-text'] ) ) {
                update_post_meta( $post_id, '_roost_custom_note_text', sanitize_text_field( $_POST['roost-custom-note-text'] ) );
            }
        }
    }

    public static function filter_string( $string ) {
        $string = str_replace( '&#8220;', '&quot;', $string );
        $string = str_replace( '&#8221;', '&quot;', $string );
        $string = str_replace( '&#8216;', '&#39;', $string );
        $string = str_replace( '&#8217;', '&#39;', $string );
        $string = str_replace( '&#8211;', '-', $string );
        $string = str_replace( '&#8212;', '-', $string );
        $string = str_replace( '&#8242;', '&#39;', $string );
        $string = str_replace( '&#8230;', '...', $string );
        $string = str_replace( '&prime;', '&#39;', $string );
        return html_entity_decode( $string, ENT_QUOTES );
    }

    public static function build_note( $new_status, $old_status, $post ) {
        if ( false === self::roost_active() ) {
            return;
        }
        if ( empty( $post ) ) {
            return;
        }
        if ( ! current_user_can( 'publish_posts' ) && ! DOING_CRON  ) {
            return;
        }

        $roost_settings = self::roost_settings();
        $app_key = $roost_settings['appKey'];
        $app_secret = $roost_settings['appSecret'];
        $post_id = $post->ID;
        $post_type = get_post_type( $post );

        if ( 'post' !== $post_type ) {
            return;
        }

        if ( 'publish' === $new_status && 'publish' === $old_status ) {
            if ( isset( $_POST['roost-force-update'] ) ) {
                $send_note = true;
            }
        }

        if ( $new_status !== $old_status || ! empty( $send_note ) ) {
            if ( 'publish' === $new_status ) {
                $categories = get_the_category( $post_id );
                $auto_push = $roost_settings['autoPush'];
                $non_roost_categories = $roost_settings['categories'];
                $segment_send = $roost_settings['segment_send'];
                $segments = null;
                $image_url = null;

                if ( ( 'publish' === $new_status && 'future' === $old_status ) || ! wp_verify_nonce( $_POST['hidden_rooster'], 'roost_save_post' ) ) {
                    $override = get_post_meta( $post_id, '_roost_override', true );
                    $send_note = get_post_meta( $post_id, '_roost_force', true );
                    $custom_headline = get_post_meta( $post_id, '_roost_custom_note_text', true );
                } else {
                    if ( isset( $_POST['roost-override'] ) ) {
                        $override = sanitize_text_field( $_POST['roost-override'] );
                    }
                    if ( isset( $_POST['roost-force'] ) ) {
                        $send_note = sanitize_text_field( $_POST['roost-force'] );
                    }
                    if ( isset( $_POST['roost-custom-note-text'] ) && ! empty( $_POST['roost-custom-note-text'] ) ) {
                        $custom_headline = sanitize_text_field( $_POST['roost-custom-note-text'] );
                    }
                }
                if ( ( true === $auto_push || ! empty( $send_note ) ) ) {
                    if ( empty( $override ) ) {
                        if ( empty( $send_note ) ) {
                            foreach ( $categories as $cat ) {
                                $cats[] = $cat->cat_ID;
                            }
                            $show_stopper_categories = array_intersect( $non_roost_categories, $cats );
                            if ( count( $show_stopper_categories ) ) {
                                return;
                            }
                        }
                        if ( true === $segment_send ) {
                            foreach ( $categories as $cat ) {
                                if ( 1 == $cat->cat_ID ) {
                                    continue;
                                }
                                $segments[] = $cat->name;
                            }
                        }
                        if ( ! empty( $custom_headline ) ) {
                            $alert = stripslashes( $custom_headline );
                        } else {
                            $alert = get_the_title( $post_id );
                        }
                        $url = get_permalink( $post_id );
                        if ( has_post_thumbnail( $post_id ) ) {
                            $raw_image = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ) );
                            $image_url = $raw_image[0];
                        }
                        Roost_API::send_notification( $alert, $url, $image_url, $app_key, $app_secret, null, $segments );
                    }
                }
            }
        }
    }

    public static function note_override() {
        if ( false === self::roost_active() ) {
            return;
        }
        $roost_settings = self::roost_settings();
        global $post;
        if ( 'post' === $post->post_type ) {
            $auto_push = $roost_settings['autoPush'];
            printf('<div class="misc-pub-section misc-pub-section-last" id="roost-post-checkboxes">');
            if ( 'publish' === $post->post_status ) {
                printf('<label><input type="checkbox" value="1" id="roost-forced-checkbox" name="roost-force-update" style="margin: -3px 9px 0 1px;" />');
                echo 'Send Roost notification on update</label>';
            } else {
                $pid = get_the_ID();
                if ( true === $auto_push ) {
                    printf( '<label><input type="checkbox" value="1" id="roost-override-checkbox" name="roost-override" style="margin: -3px 9px 0 1px;" %s />', checked( get_post_meta( $pid, '_roost_override', true ), 1, false ) );
                    echo 'Do NOT send Roost notification</label>';
                } else {
                    printf( '<label><input type="checkbox" value="1" id="roost-forced-checkbox" name="roost-force" style="margin: -3px 9px 0 1px;" %s />', checked( get_post_meta( $pid, '_roost_force', true ), 1, false ) );
                    echo 'Send Roost notification</label>';
                }
            }
            wp_nonce_field( 'roost_save_post', 'hidden_rooster' );
            echo '</div>';
        }
    }

    public static function custom_note_text( $post ) {
        if ( false === self::roost_active() ) {
            return;
        }
        add_meta_box(
            'roost_meta',
            'Roost Web Push - Custom Notification Headline',
            array( __CLASS__, 'roost_custom_headline_content' ),
            'post',
            'normal',
            'high'
        );
    }

    public static function roost_custom_headline_content( $post ) {
        $custom_note_text = get_post_meta( $post->ID, '_roost_custom_note_text', true );
        ?>
        <div id="roost-custom-note">
            <input type="text" id="roost-custom-note-text" placeholder="Enter Custom Headline for your Notification" name="roost-custom-note-text" value="<?php echo ! empty( $custom_note_text ) ? esc_attr( $custom_note_text ) : ''; ?>" />
            <span id="roost-custom-note-text-description" >When using a custom headline, this text will be used in place of the default blog post title for your push notification. ( Leave this blank to default to post title. )</span>
        </div>
    <?php
    }

    public static function complete_login( $logged_in, $site ) {
        if ( ! empty( $logged_in ) ) {
            if ( true === $logged_in['success'] ) {
                if ( count( $logged_in['apps'] ) > 1 ){
                    $roost_sites = $logged_in['apps'];
                    return $roost_sites;
                } else {
                    $form_keys = array(
                        'appKey' => $logged_in['apps'][0]['key'],
                        'appSecret' => $logged_in['apps'][0]['secret'],
                    );
                }
            }
        } elseif ( ! empty( $site ) ) {
            $site_key = $site[0];
            $site_secret = $site[1];
            $form_keys = array(
                'appKey' => $site_key,
                'appSecret' => $site_secret,
            );
        }

        $response = array();

        if ( ! empty( $form_keys ) ) {
            self::update_keys( $form_keys );
            $response['status'] = true;
            $response['firstTime'] = true;
            $response['server_settings'] = Roost_API::get_server_settings( $form_keys['appKey'], $form_keys['appSecret'] );
            $response['stats'] = Roost_API::get_stats( $form_keys['appKey'], $form_keys['appSecret'] );
            self::chrome_setup( $form_keys['appKey'], $form_keys['appSecret'] );
            self::admin_scripts();
        } else {
            $response['status'] = 'Please check your Email or Username and Password.';
            $response['stats'] = null;
            $response['server_settings'] = null;
        }
        return $response;
    }

    public static function graph_reload() {
        $roost_settings = self::roost_settings();
        $app_key = $roost_settings['appKey'];
        $app_secret = $roost_settings['appSecret'];
        $type = esc_attr( $_POST['type'] );
        $range = esc_attr( $_POST['range'] );
        $value = esc_attr( $_POST['value'] );
        $time_offset = esc_attr( $_POST['offset'] );
        $roost_graph_data = Roost_API::get_graph_data( $app_key, $app_secret, $type, $range, $value, $time_offset );
        wp_send_json( $roost_graph_data );
    }

    public static function subs_check() {
        $roost_settings = self::roost_settings();
        $app_key = $roost_settings['appKey'];
        $app_secret = $roost_settings['appSecret'];
        $roost_stats = Roost_API::get_stats( $app_key, $app_secret );
        wp_send_json( $roost_stats['registrations'] );
    }

    public static function chrome_dismiss() {
        $roost_settings = self::roost_settings();
        $roost_settings['chrome_error_dismiss'] = true;
        update_option('roost_settings', $roost_settings);
        die();
    }

    public static function roost_logout() {
        if ( isset( $_POST['clearkey'] ) ) {
            $roost_settings = self::roost_settings();
            $roost_settings['appKey'] = '';
            $roost_settings['appSecret'] = '';
            $roost_settings['chrome_error_dismiss'] = false;
            $roost_settings['gcm_token'] = '';
            update_option('roost_settings', $roost_settings);
            wp_dequeue_script( 'roostscript' );
            $status = 'Roost has been disconnected.';
            $status = urlencode( $status );
            wp_redirect( esc_url_raw( admin_url( 'admin.php?page=roost-web-push' ) . '&status=' . $status ) );
            exit;
        }
    }

    public static function chrome_files( $query ) {
        if ( array_key_exists( 'roost', $query->query_vars ) && ( 'true' === $query->query_vars['roost'] ) ) {
            $roost_settings = self::roost_settings();
            $app_key = $roost_settings['appKey'];
            $app_secret = $roost_settings['appSecret'];
            $gcm_token = $roost_settings['gcm_token'];
            $site_base_path = wp_make_link_relative( self::base_url( '/' ) );

            if ( empty( $gcm_token ) ) {
                $roost_server_settings = Roost_API::get_server_settings( $app_key, $app_secret );
                $gcm_token = $roost_server_settings['gcmProjectID'];
                $roost_settings['gcm_token'] = sanitize_text_field( $gcm_token );
                update_option('roost_settings', $roost_settings);
            }

            $roost_action = $query->query_vars['roost_action'];

            if ( 'load' === $roost_action ) {
                header( 'Content-Type: text/html' );
                include dirname( plugin_dir_path( __FILE__ ) ) . '/includes/chrome/roost.php';
                exit;
            }

            if ( 'manifest' === $roost_action ) {
                header( 'Content-Type: application/javascript' );
                include dirname( plugin_dir_path( __FILE__ ) ) . '/includes/chrome/roost_manifest.php';
                exit;
            }

            if ( 'worker' === $roost_action ) {
                header( 'Content-Type: application/javascript' );
                include dirname( plugin_dir_path( __FILE__ ) ) . '/includes/chrome/roost_worker.php';
                exit;
            }
        }
    }

    public static function roost_save_settings() {
        if ( isset( $_POST['savesettings'] ) ) {
            $roost_settings = self::roost_settings();
            $app_key = $roost_settings['appKey'];
            $app_secret = $roost_settings['appSecret'];

            $autoPush = false;
            $prompt_min = false;
            $prompt_visits = 2;
            $prompt_event = false;
            $non_roost_categories = array();
            $segment_send = false;

            if ( isset( $_POST['autoPush'] ) ) {
                $autoPush = true;
            }
            if ( isset( $_POST['roost-prompt-min'] ) ) {
                $prompt_min = true;
            }
            if ( isset( $_POST['roost-prompt-visits'] ) ) {
                if ( '0' === $_POST['roost-prompt-visits'] || '1' === $_POST['roost-prompt-visits'] ) {
                    $prompt_visits = 2;
                } else {
                    $prompt_visits = sanitize_text_field( $_POST['roost-prompt-visits'] );
                }
            }
            if ( isset( $_POST['roost-prompt-event'] ) ) {
                $prompt_event = true;
            }
            if ( isset( $_POST['roost-categories'] ) ) {
                $non_roost_categories = array_map( sanitize_text_field, $_POST['roost-categories'] );
            }
            if ( isset( $_POST['roost-segment-send'] ) ) {
                $segment_send = true;
            }

            $form_data = array(
                'autoPush' => $autoPush,
                'prompt_min' => $prompt_min,
                'prompt_visits' => $prompt_visits,
                'prompt_event' => $prompt_event,
                'categories' => $non_roost_categories,
                'segment_send' => $segment_send,
            );

            self::update_settings( $form_data );
            $status = 'Settings Saved.';
            $status = urlencode( $status );
            wp_redirect( esc_url_raw( admin_url( 'admin.php?page=roost-web-push' ) . '&status=' . $status ) );
            exit;
        }
    }

    public static function manual_send() {
        if ( isset( $_POST['manualtext'] ) ) {
            $manual_text = sanitize_text_field( $_POST['manualtext'] );
            $manual_link = esc_url( $_POST['manuallink'] );
            $manual_text = stripslashes( $manual_text );
            if ( '' == $manual_text || '' == $manual_link ) {
                $status = 'Your message or link can not be blank.';
            } else {
                $roost_settings = self::roost_settings();
                $app_key = $roost_settings['appKey'];
                $app_secret = $roost_settings['appSecret'];
                $msg_status = Roost_API::send_notification( $manual_text, $manual_link, null, $app_key, $app_secret, null, null );
                if ( true === $msg_status['success'] ) {
                    $status = 'Message Sent.';
                } else {
                    $status = 'Message failed. Please make sure you have a valid URL.';
                }
            }
            $status = urlencode( $status );
            wp_redirect( esc_url_raw( admin_url( 'admin.php?page=roost-web-push' ) . '&status=' . $status ) );
            exit;
        }
    }

    public static function admin_menu_page() {
        $roost_settings = self::roost_settings();
        $app_key = $roost_settings['appKey'];
        $app_secret = $roost_settings['appSecret'];
        $chrome_error_dismiss = $roost_settings['chrome_error_dismiss'];
        $cat_args = array(
            'hide_empty' => 0,
            'order' => 'ASC'
        );
        $cats = get_categories( $cat_args );

        if ( true === self::roost_active() ) {
            $roost_active_key = true;
        } else {
            $roost_active_key = false;
        }

        if ( true === self::roost_active() && empty( $roost_server_settings ) ) {
            $roost_server_settings = Roost_API::get_server_settings( $app_key, $app_secret );
            $roost_stats = Roost_API::get_stats( $app_key, $app_secret );
        }

        if ( false === self::roost_active() && isset( $_GET['roost_token'] ) ) {
            $roost_token = $_GET['roost_token'];
            $roost_token = urldecode($roost_token);
            $logged_in = Roost_API::login( null, null, $roost_token );
            $response = self::complete_login( $logged_in, null );
            $first_time = $response['firstTime'];
            $roost_server_settings = $response['server_settings'];
            $roost_stats = $response['stats'];
            $roost_active_key = true;
        }

        if ( isset( $_POST['roostlogin'] ) ) {
            $roost_user = $_POST['roostuserlogin'];
            $roost_pass = $_POST['roostpasslogin'];
            $logged_in = Roost_API::login( $roost_user, $roost_pass, null );
            $response = self::complete_login( $logged_in, null );
            if ( empty( $response['status'] ) ) {
                $roost_sites = $response;
            } else {
                if ( ! empty( $response['firstTime'] ) ) {
                    $first_time = $response['firstTime'];
                    $roost_server_settings = $response['server_settings'];
                    $roost_stats = $response['stats'];
                    $roost_active_key = true;
                } else {
                    $status = $response['status'];
                }
            }
        }

        if ( isset( $_POST['roostconfigselect'] ) ) {
            $selected_site = sanitize_text_field( $_POST['roostsites'] );
            $site = explode( '|', $selected_site );
            $response = self::complete_login( null, $site );
            $first_time = $response['firstTime'];
            $roost_server_settings = $response['server_settings'];
            $roost_stats = $response['stats'];
            $roost_active_key = true;
        }
        if ( isset( $_GET['status'] ) ) {
            $status = urldecode( $_GET['status'] );
        }

        require_once( dirname( plugin_dir_path( __FILE__ ) ) . '/layout/admin.php' );
    }
}

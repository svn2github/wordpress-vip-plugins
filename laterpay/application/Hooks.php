<?php

class LaterPay_Hooks {
    private static $wp_action_prefix    = 'wp_action_';
    private static $wp_filter_prefix    = 'wp_filter_';
    private static $wp_shortcode_prefix = 'wp_shcode_';
    private static $lp_filter_suffix    = '_filter';
    private static $lp_filter_args_suffix = '_arguments';
    private static $instance            = null;
    private static $lp_actions          = array();
    private static $lp_shortcodes       = array();

    /**
     * Singleton to get only one event dispatcher
     *
     * @return LaterPay_Hooks
     */
    public static function get_instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Magic method to process WordPress actions/filters.
     *
     * @param string    $name Method name.
     * @param array     $args Method arguments.
     * @return mixed
     */
    public function __call( $name, $args ) {
        $method = substr( $name, 0, 10 );
        $action = substr( $name, 10 );
        $result = null;

        try {
            switch ( $method ) {
                case self::$wp_action_prefix:
                    $this->run_wp_action( $action, $args );
                    break;
                case self::$wp_filter_prefix:
                    $result = $this->run_wp_filter( $action, $args );
                    break;
                case self::$wp_shortcode_prefix:
                    $result = $this->run_wp_shortcode( $action, $args );
                    break;
                default:
                    throw new RuntimeException( sprintf( 'Method "%s" is not found within LaterPay_Core_Event_Dispatcher class.', $name ) );
            }
        } catch ( Exception $e ) {
            unset( $e );
        }

        return $result;
    }

    /**
     * Registers WordPress hooks to trigger internal plugin events.
     */
    public function init() {
        add_filter( 'the_content',                      array( $this, self::$wp_filter_prefix . 'laterpay_post_content' ) );
        add_filter( 'get_post_metadata',                array( $this, self::$wp_filter_prefix . 'laterpay_post_metadata' ), 10, 4 );
        add_filter( 'the_posts',                        array( $this, self::$wp_filter_prefix . 'laterpay_posts' ) );

        add_filter( 'terms_clauses',                    array( $this, self::$wp_filter_prefix . 'laterpay_terms_clauses' ) );
        add_filter( 'date_query_valid_columns',         array( $this, self::$wp_filter_prefix . 'laterpay_date_query_valid_columns' ) );

        add_filter( 'wp_get_attachment_image_attributes', array( $this, self::$wp_filter_prefix . 'laterpay_attachment_image_attributes' ), 10, 3 );
        add_filter( 'wp_get_attachment_url',            array( $this, self::$wp_filter_prefix . 'laterpay_attachment_get_url' ), 10, 2 );
        add_filter( 'prepend_attachment',               array( $this, self::$wp_filter_prefix . 'laterpay_attachment_prepend' ) );

        foreach ( laterpay_get_plugin_config()->get( 'content.enabled_post_types' ) as $post_type ) {
            add_filter( 'manage_' . $post_type . '_posts_columns',         array( $this, self::$wp_filter_prefix . 'laterpay_post_custom_column' ) );
            add_action( 'manage_' . $post_type . '_posts_custom_column',   array( $this, self::$wp_action_prefix . 'laterpay_post_custom_column_data' ), 10, 2 );
        }

        add_action( 'template_redirect',                array( $this, self::$wp_action_prefix . 'laterpay_loaded' ) );
        add_action( 'wp_footer',                        array( $this, self::$wp_action_prefix . 'laterpay_post_footer' ) );
        add_action( 'wp_enqueue_scripts',               array( $this, self::$wp_action_prefix . 'laterpay_enqueue_scripts' ) );

        add_action( 'admin_init',                       array( $this, self::$wp_action_prefix . 'laterpay_admin_init' ) );
        add_action( 'admin_head',                       array( $this, self::$wp_action_prefix . 'laterpay_admin_head' ) );
        add_action( 'admin_menu',                       array( $this, self::$wp_action_prefix . 'laterpay_admin_menu' ) );
        add_action( 'admin_notices',                    array( $this, self::$wp_action_prefix . 'laterpay_admin_notices' ) );
        add_action( 'admin_footer',                     array( $this, self::$wp_action_prefix . 'laterpay_admin_footer' ), 1000 );
        add_action( 'admin_enqueue_scripts',            array( $this, self::$wp_action_prefix . 'laterpay_admin_enqueue_scripts' ) );
        add_action( 'admin_bar_menu',                   array( $this, self::$wp_action_prefix . 'laterpay_admin_bar_menu' ), 1000 );
        add_action( 'admin_print_footer_scripts',       array( $this, self::$wp_action_prefix . 'laterpay_admin_footer_scripts' ) );
        add_action( 'admin_print_styles-post.php',      array( $this, self::$wp_action_prefix . 'laterpay_admin_enqueue_styles_post_edit' ) );
        add_action( 'admin_print_styles-post-new.php',  array( $this, self::$wp_action_prefix . 'laterpay_admin_enqueue_styles_post_new' ) );

        add_action( 'load-post.php',                    array( $this, self::$wp_action_prefix . 'laterpay_post_edit' ) );
        add_action( 'load-post-new.php',                array( $this, self::$wp_action_prefix . 'laterpay_post_new' ) );
        add_action( 'add_meta_boxes',                   array( $this, self::$wp_action_prefix . 'laterpay_meta_boxes' ) );
        add_action( 'save_post',                        array( $this, self::$wp_action_prefix . 'laterpay_post_save' ) );
        add_action( 'edit_attachment',                  array( $this, self::$wp_action_prefix . 'laterpay_attachment_edit' ) );
        add_action( 'transition_post_status',           array( $this, self::$wp_action_prefix . 'laterpay_transition_post_status' ), 10, 3 );
        add_action( 'init',                             array( $this, self::$wp_action_prefix . 'laterpay_register_subscription_cpt' ), 10, 1 );

        // cache helper to purge the cache on update_option()
        $options = array(
            'laterpay_global_price',
            'laterpay_global_price_revenue_model',
            'laterpay_enabled_post_types',
            'laterpay_teaser_mode',
            'laterpay_plugin_is_in_live_mode',
        );
        foreach ( $options as $option_name ) {
            add_action( 'update_option_' . $option_name, array( $this, self::$wp_action_prefix . 'laterpay_option_update' ) );
        }

        add_action( 'init', array( $this, self::$wp_action_prefix . 'laterpay_register_passes_cpt' ) );
    }

    /**
     * Allows to register dynamically WordPress actions.
     *
     * @param string        $name Wordpress hook name.
     * @param string|null   $event_name LaterPay internal event name.
     */
    public static function add_wp_action( $name, $event_name = null) {
        if ( empty( $event_name ) ) {
            $event_name = 'laterpay_' . $name;
        }
        add_action( $name, array( self::get_instance(), self::$wp_action_prefix . $event_name ) );

    }

    /**
     * Registers LaterPay event in WordPress actions pool.
     *
     * @param string $event_name Event name.
     */
    public static function register_laterpay_action( $event_name ) {
        $check_event_name = in_array( $event_name, self::$lp_actions, true );
        if ( ! $check_event_name ) {
            self::add_wp_action( $event_name, $event_name );
            self::$lp_actions[] = $event_name;
        }
    }

    /**
     * Registers LaterPay event in WordPress shortcode pool.
     *
     * @param string $event_name Event name.
     */
    public static function register_laterpay_shortcode( $event_name ) {
        $check_event_name = in_array( $event_name, self::$lp_shortcodes, true );
        if ( ! $check_event_name ) {
            if ( strpos( $event_name, 'laterpay_shortcode_' ) !== false ) {
                $name = substr( $event_name, 19 );
            }
            self::add_wp_shortcode( $name, $event_name );
            self::$lp_shortcodes[] = $event_name;
        }
    }

    /**
     * Allows to register dynamic WordPress filters.
     *
     * @param string        $name Wordpress hook name.
     * @param string|null   $event_name LaterPay internal event name.
     */
    public static function add_wp_filter( $name, $event_name = null) {
        if ( empty( $event_name ) ) {
            $event_name = 'laterpay_' . $name;
        }
        add_filter( $name, array( self::get_instance(), self::$wp_filter_prefix . $event_name ) );

    }

    /**
     * Allows to register WordPress shortcodes.
     *
     * @param string        $name Wordpress hook name.
     * @param string|null   $event_name LaterPay internal event name.
     */
    public static function add_wp_shortcode( $name, $event_name = null) {
        if ( empty( $event_name ) ) {
            $event_name = 'laterpay_' . $name;
        }
        add_shortcode( $name, array( self::get_instance(), self::$wp_shortcode_prefix . $event_name ) );

    }

    /**
     * Triggered by WordPress for registered actions.
     *
     * @param string    $action Action name.
     * @param array     $args Action arguments.
     * @return array|string
     */
    protected function run_wp_action( $action, $args = array() ) {
        // argument can have value == null, so 'isset' function is not suitable
        $default = array_key_exists( 0, $args ) ? $args[0]: '';
        try {
            $event = new LaterPay_Core_Event( $args );
            if ( strpos( $action, 'wp_ajax' ) !== false ) {
                $event->set_ajax( true );
            }
            laterpay_event_dispatcher()->dispatch( $action, $event );
            $result = $event->get_result();
        } catch ( Exception $e ) {
            unset( $e );
            $result = $default;
        }
        return $result;
    }

    /**
     * Triggered by WordPress for registered filters.
     *
     * @param string    $event_name Event name.
     * @param array     $args Filter arguments. first argument is filtered value.
     * @return array|string Filtered result
     */
    protected function run_wp_filter( $event_name, $args = array() ) {
        // argument can have value == null, so 'isset' function is not suitable
        $default = array_key_exists( 0, $args ) ? $args[0]: '';
        try {
            $event = new LaterPay_Core_Event( $args );
            $event->set_result( $default );
            $event->set_echo( false );

            laterpay_event_dispatcher()->dispatch( $event_name, $event );

            $result = $event->get_result();
        } catch ( Exception $e ) {
            unset( $e );
            $result = $default;
        }
        return $result;
    }

    /**
     * Triggered by WordPress for registered shortcode.
     *
     * @param string    $event_name Event name.
     * @param array     $args Shortcode arguments.
     * @return mixed Filtered result
     */
    protected function run_wp_shortcode( $event_name, $args = array() ) {
        $event = new LaterPay_Core_Event( $args );
        $event->set_echo( false );
        laterpay_event_dispatcher()->dispatch( $event_name, $event );

        return $event->get_result();
    }

    /**
     * Applies filters to triggered by LaterPay events.
     *
     * @param string        $action Action name.
     * @param array         $value Value to filter.
     * @return string|array
     */
    public static function apply_filters( $action, $value ) {
        return apply_filters( $action . self::$lp_filter_suffix, $value );
    }

    /**
     * Applies filters to triggered by LaterPay events.
     *
     * @param string        $action Action name.
     * @param array         $value Value to filter.
     * @return string|array
     */
    public static function apply_arguments_filters( $action, $value ) {
        return apply_filters( $action . self::$lp_filter_args_suffix, $value );
    }

    /**
     * Late load event for other plugins to remove / add own actions to the LaterPay plugin.
     *
     * @return void
     */
    public function laterpay_ready() {
        /**
         * Late loading event for LaterPay.
         *
         * @param LaterPay_Core_Bootstrap $this
         */
        do_action( 'laterpay_ready', $this );
    }

    /**
     * Remove hooks related to WP_Query.
     */
    public function remove_wp_query_hooks() {
        remove_filter( 'the_posts', array( $this, self::$wp_filter_prefix . 'laterpay_posts' ) );
    }

    /**
     * Adds hooks related to WP_Query.
     */
    public function add_wp_query_hooks() {
        add_filter( 'the_posts', array( $this, self::$wp_filter_prefix . 'laterpay_posts' ) );
    }

}

<?php

/**
 * LaterPay settings controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Controller_Admin_Settings extends LaterPay_Controller_Base
{
    private $has_custom_roles = false;

    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'laterpay_admin_init' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_plugin_is_active', 200 ),
                array( 'init_laterpay_advanced_settings' ),
            ),
            'laterpay_admin_menu' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'add_laterpay_advanced_settings_page' ),
            ),
        );
    }

    /**
     * @see LaterPay_Core_View::load_assets
     */
    public function load_assets()
    {
        parent::load_assets();

        // register and enqueue stylesheet
        wp_register_style(
            'laterpay-options',
            $this->config->css_url . 'laterpay-options.css',
            array(),
            $this->config->version
        );
        wp_enqueue_style('laterpay-options');

        // Add thickbox to display modal.
        add_thickbox();

        // load page-specific JS
        wp_register_script(
            'laterpay-backend-options',
            $this->config->js_url . '/laterpay-backend-options.js',
            array( 'jquery' ),
            $this->config->version,
            true
        );
        wp_enqueue_script( 'laterpay-backend-options' );

        // Localize string to be used in script.
        wp_localize_script(
            'laterpay-backend-options',
            'lpVars',
            array(
                'modal' => array(
                    'id'    => 'lp_ga_modal_id',
                    'title' => esc_html__( 'Disable Tracking', 'laterpay' )
                ),
                'i18n'  => array(
                    'alertEmptyCode' => esc_html__( 'Please enter UA-ID to enable Personal Analytics!', 'laterpay' ),
                    'invalidCode'    => esc_html__( 'Please enter valid UA-ID code!', 'laterpay' ),
                )
            )
        );
    }

    /**
     * Add LaterPay advanced settings to the settings menu.
     *
     * @return void
     */
    public function add_laterpay_advanced_settings_page() {
        add_options_page(
            __( 'LaterPay Advanced Settings', 'laterpay' ),
            'LaterPay',
            'manage_options',
            'laterpay',
            array( $this, 'render_advanced_settings_page' )
        );
    }

    /**
     * Render the settings page for all LaterPay advanced settings.
     *
     * @return void
     */
    public function render_advanced_settings_page() {
        $this->load_assets();
        // pass variables to template
        $view_args = array(
            'settings_title' => __( 'LaterPay Advanced Settings', 'laterpay' ),
        );

        $this->assign( 'laterpay', $view_args );

        // render view template for options page
        $this->render( 'backend/options' );
    }

    /**
     * Configure content of LaterPay advanced settings page.
     *
     * @return void
     */
    public function init_laterpay_advanced_settings() {
        // add sections with fields
        $this->add_colors_settings();
        $this->add_ga_tracking_settings();
        $this->add_caching_settings();
        $this->add_enabled_post_types_settings();
        $this->add_revenue_settings();
        $this->add_gift_codes_settings();
        $this->add_teaser_content_settings();
        $this->add_preview_excerpt_settings();
        $this->add_unlimited_access_settings();
        $this->add_laterpay_api_settings();
        $this->add_laterpay_pro_merchant();
    }

    public function add_colors_settings() {
        add_settings_section(
            'laterpay_colors',
            __( 'LaterPay Colors', 'laterpay' ),
            array( $this, 'get_colors_section_description' ),
            'laterpay'
        );

        add_settings_field(
            'laterpay_main_color',
            __( 'Main Color', 'laterpay' ),
            array( $this, 'get_input_field_markup' ),
            'laterpay',
            'laterpay_colors',
            array(
                'label' => __( 'Main color for clickable elements. (Default: #01a99d)' ),
                'name'  => 'laterpay_main_color',
            )
        );

        register_setting( 'laterpay', 'laterpay_main_color' );

        add_settings_field(
            'laterpay_hover_color',
            __( 'Hover Color', 'laterpay' ),
            array( $this, 'get_input_field_markup' ),
            'laterpay',
            'laterpay_colors',
            array(
                'label' => __( 'Hover color for clickable elements. (Default: #01766e)' ),
                'name'  => 'laterpay_hover_color',
            )
        );

        register_setting( 'laterpay', 'laterpay_hover_color' );
    }

    /**
     * Get colors section description
     *
     * @return void
     */
    public function get_colors_section_description() {
        echo '<p>';
        esc_html_e( 'You can customize the colors of clickable LaterPay elements.', 'laterpay' );
        echo '</p>';
    }


    /**
     * Add caching section and fields.
     *
     * @return void
     */
    public function add_caching_settings() {
        add_settings_section(
            'laterpay_caching',
            __( 'Caching Compatibility Mode', 'laterpay' ),
            array( $this, 'get_caching_section_description' ),
            'laterpay'
        );

        add_settings_field(
            'laterpay_caching_compatibility',
            __( 'Caching Compatibility', 'laterpay' ),
            array( $this, 'get_input_field_markup' ),
            'laterpay',
            'laterpay_caching',
            array(
                'name'  => 'laterpay_caching_compatibility',
                'value' => 1,
                'type' => 'checkbox',
                'label' => __( 'I am using a caching plugin (e.g. WP Super Cache or Cachify)', 'laterpay' ),
            )
        );

        register_setting( 'laterpay', 'laterpay_caching_compatibility' );
    }

    /**
     * Render the hint text for the caching section.
     *
     * @return void
     */
    public function get_caching_section_description() {
        echo '<p>';
        esc_html_e( 'You MUST enable caching compatiblity mode, if you are using a caching solution
           that caches entire HTML pages.', 'laterpay' );
        echo  '<br/>';
        esc_html_e( 'In caching compatibility mode the plugin works
           like this:', 'laterpay' );
        echo  '<br/>';
        esc_html_e( 'It renders paid posts only with the teaser content. This allows to cache
           them as static files without risking to leak the paid content.', 'laterpay' );
        echo  '<br/>';
        esc_html_e( 'When someone visits
           the page, it makes an Ajax request to determine, if the visitor has already bought the post
           and replaces the teaser with the full content, if required.', 'laterpay' );
        echo '</p>';
    }

    /**
     * Add activated post types section and fields.
     *
     * @return void
     */
    public function add_enabled_post_types_settings() {
        add_settings_section(
            'laterpay_post_types',
            __( 'LaterPay-enabled Post Types', 'laterpay' ),
            array( $this, 'get_enabled_post_types_section_description' ),
            'laterpay'
        );

        add_settings_field(
            'laterpay_enabled_post_types',
            __( 'Enabled Post Types', 'laterpay' ),
            array( $this, 'get_enabled_post_types_markup' ),
            'laterpay',
            'laterpay_post_types'
        );

        register_setting( 'laterpay', 'laterpay_enabled_post_types' );
    }

    /**
     * Render the hint text for the enabled post types section.
     *
     * @return void
     */
    public function get_enabled_post_types_section_description() {
        echo '<p>';
        esc_html_e( 'Please choose, which standard and custom post types should be sellable with LaterPay.',
            'laterpay' );
        echo '</p>';
    }

    /**
     * Render the hint text for the enabled post types section.
     *
     * @return void
     */
    public function get_time_passes_section_description() {
        echo '<p>';
        esc_html_e( 'Please choose, if you want to show the time passes widget on free posts, or only on paid posts.',
            'laterpay' );
        echo '</p>';
    }

    /**
     *  Add revenue settings section
     */
    public function add_revenue_settings() {
        add_settings_section(
            'laterpay_revenue_section',
            __( 'Require login', 'laterpay' ),
            array( $this, 'get_revenue_section_description' ),
            'laterpay'
        );

        add_settings_field(
            'laterpay_require_login',
            __( 'Require login', 'laterpay' ),
            array( $this, 'get_input_field_markup' ),
            'laterpay',
            'laterpay_revenue_section',
            array(
                'name'  => 'laterpay_require_login',
                'value' => 1,
                'type'  => 'checkbox',
                'label' => __( 'Require the user to log in to LaterPay before a "Pay Later" purchase.', 'laterpay' ),
            )
        );

        register_setting( 'laterpay', 'laterpay_require_login' );
    }

    /**
     * Render the hint text for the enabled post types section.
     *
     * @return void
     */
    public function get_revenue_section_description() {
        echo '<p>';
        esc_html_e( 'Please choose if you want to require a login for "Pay Later" purchases.','laterpay' );
        echo '</p>';
    }

    /**
     * Add gift codes section and fields.
     *
     * @return void
     */
    public function add_gift_codes_settings() {
        add_settings_section(
            'laterpay_gift_codes',
            __( 'Gift Codes Limit', 'laterpay' ),
            array( $this, 'get_gift_codes_section_description' ),
            'laterpay'
        );

        add_settings_field(
            'laterpay_maximum_redemptions_per_gift_code',
            __( 'Times Redeemable', 'laterpay' ),
            array( $this, 'get_input_field_markup' ),
            'laterpay',
            'laterpay_gift_codes',
            array(
                'name'  => 'laterpay_maximum_redemptions_per_gift_code',
                'class' => 'lp_number-input',
            )
        );

        register_setting( 'laterpay', 'laterpay_maximum_redemptions_per_gift_code', array( $this, 'sanitize_maximum_redemptions_per_gift_code_input' ) );
    }

    /**
     * Render the hint text for the gift codes section.
     *
     * @return void
     */
    public function get_gift_codes_section_description() {
        echo '<p>';
        esc_html_e( 'Specify, how many times a gift code can be redeemed for the associated time pass.','laterpay' );
        echo '</p>';
    }

    /**
     * Sanitize maximum redemptions per gift code.
     *
     * @param $input
     *
     * @return int
     */
    public function sanitize_maximum_redemptions_per_gift_code_input( $input ) {
        $error = '';
        $input = absint( $input );

        if ( $input < 1 ) {
            $input = 1;
            $error = 'Please enter a valid limit ( 1 or greater )';
        }

        if ( ! empty( $error ) ) {
            add_settings_error(
                'laterpay',
                'gift_code_redeem_error',
                $error,
                'error'
            );
        }

        return $input;
    }

    /**
     * Add teaser content section and fields.
     *
     * @return void
     */
    public function add_teaser_content_settings() {
        add_settings_section(
            'laterpay_teaser_content',
            __( 'Automatically Generated Teaser Content', 'laterpay' ),
            array( $this, 'get_teaser_content_section_description' ),
            'laterpay'
        );

        add_settings_field(
            'laterpay_teaser_content_word_count',
            __( 'Teaser Content Word Count', 'laterpay' ),
            array( $this, 'get_input_field_markup' ),
            'laterpay',
            'laterpay_teaser_content',
            array(
                'name'          => 'laterpay_teaser_content_word_count',
                'class'         => 'lp_number-input',
                'appended_text' => __( 'Number of words extracted from paid posts as teaser content.', 'laterpay' ),
            )
        );

        register_setting( 'laterpay', 'laterpay_teaser_content_word_count', 'absint' );
    }

    /**
     * Render the hint text for the teaser content section.
     *
     * @return void
     */
    public function get_teaser_content_section_description() {
        echo '<p>';
        esc_html_e( 'The LaterPay WordPress plugin automatically generates teaser content for every paid post
            without teaser content.', 'laterpay' );
        echo '<br/>';
        esc_html_e( 'While technically possible, setting this parameter to zero is
            HIGHLY DISCOURAGED.', 'laterpay' );
        echo '<br/>';
        esc_html_e( 'If you really, really want to have NO teaser content for a post,
            enter one space into the teaser content editor for that post.', 'laterpay' );
        echo '</p>';
    }

    /**
     * Add preview excerpt section and fields.
     *
     * @return void
     */
    public function add_preview_excerpt_settings() {
        add_settings_section(
            'laterpay_preview_excerpt',
            __( 'Content Preview under Overlay', 'laterpay' ),
            array( $this, 'get_preview_excerpt_section_description' ),
            'laterpay'
        );

        add_settings_field(
            'laterpay_preview_excerpt_percentage_of_content',
            __( 'Percentage of Post Content', 'laterpay' ),
            array( $this, 'get_input_field_markup' ),
            'laterpay',
            'laterpay_preview_excerpt',
            array(
                'name'          => 'laterpay_preview_excerpt_percentage_of_content',
                'class'         => 'lp_number-input',
                'appended_text' => __( 'Percentage of content to be extracted;
                                      20 means "extract 20% of the total number of words of the post".', 'laterpay' ),
            )
        );

        add_settings_field(
            'laterpay_preview_excerpt_word_count_min',
            __( 'Minimum Number of Words', 'laterpay' ),
            array( $this, 'get_input_field_markup' ),
            'laterpay',
            'laterpay_preview_excerpt',
            array(
                'name'          => 'laterpay_preview_excerpt_word_count_min',
                'class'         => 'lp_number-input',
                'appended_text' => __( 'Applied if number of words as percentage of the total number of words is less
                                      than this value.', 'laterpay' ),
            )
        );

        add_settings_field(
            'laterpay_preview_excerpt_word_count_max',
            __( 'Maximum Number of Words', 'laterpay' ),
            array( $this, 'get_input_field_markup' ),
            'laterpay',
            'laterpay_preview_excerpt',
            array(
                'name'          => 'laterpay_preview_excerpt_word_count_max',
                'class'         => 'lp_number-input',
                'appended_text' => __( 'Applied if number of words as percentage of the total number of words exceeds
                                      this value.', 'laterpay' ),
            )
        );

        register_setting( 'laterpay', 'laterpay_preview_excerpt_percentage_of_content', 'absint' );
        register_setting( 'laterpay', 'laterpay_preview_excerpt_word_count_min', 'absint' );
        register_setting( 'laterpay', 'laterpay_preview_excerpt_word_count_max', 'absint' );
    }

    /**
     * Render the hint text for the preview excerpt section.
     *
     * @return void
     */
    public function get_preview_excerpt_section_description() {
        echo '<p>';
        esc_html_e( 'In the appearance tab, you can choose to preview your paid posts with the teaser content plus
            an excerpt of the full content, covered by a semi-transparent overlay.', 'laterpay' );
        echo '<br/>';
        esc_html_e( 'The following three parameters give you fine-grained control over the length of this excerpt.', 'laterpay' );
        echo '<br/>';
        esc_html_e( 'These settings do not affect the teaser content in any way.', 'laterpay' );
        echo '</p>';
    }

    /**
     * Add unlimited access section and fields.
     *
     * @return void
     */
    public function add_unlimited_access_settings() {
        global $wp_roles;
        $custom_roles  = array();

        $default_roles = array(
            'administrator',
            'editor',
            'contributor',
            'author',
            'subscriber',
        );

        $categories    = array(
            'none' => 'none',
            'all'  => 'all',
        );

        $args          = array(
            'hide_empty' => false,
            'taxonomy'   => 'category',
        );

        // get custom roles
        foreach ( $wp_roles->roles as $role => $role_data ) {
            if ( ! in_array( $role, $default_roles, true ) ) {
                $this->has_custom_roles = true;
                $custom_roles[ $role ] = $role_data['name'];
            }
        }

        // get categories and add them to the array
        $wp_categories = get_categories( $args );
        foreach ( $wp_categories as $category ) {
            $categories[ $category->term_id ] = $category->name;
        }

        add_settings_section(
            'laterpay_unlimited_access',
            __( 'Unlimited Access to Paid Content', 'laterpay' ),
            array( $this, 'get_unlimited_access_section_description' ),
            'laterpay'
        );

        register_setting( 'laterpay', 'laterpay_unlimited_access', array( $this, 'validate_unlimited_access' ) );

        // add options for each custom role
        foreach ( $custom_roles as $role => $name ) {
            add_settings_field(
                $role,
                $name,
                array( $this, 'get_unlimited_access_markup' ),
                'laterpay',
                'laterpay_unlimited_access',
                array(
                    'role'       => $role,
                    'categories' => $categories,
                )
            );
        }

    }

    /**
     * Render the hint text for the unlimited access section.
     *
     * @return void
     */
    public function get_unlimited_access_section_description() {
        echo '<p>';
        esc_html_e( 'You can give logged-in users unlimited access to specific categories depending on their user role.', 'laterpay' );
        echo '<br/>';
        esc_html_e( 'This feature can be useful e.g. for giving free access to existing subscribers.', 'laterpay' );
        echo '<br/>';
        esc_html_e( 'We recommend the plugin \'User Role Editor\' for adding custom roles to WordPress.', 'laterpay' );
        echo '</p>';

        if ( $this->has_custom_roles ) {
            // show header
            echo '<table class="form-table"><tr><th>';
            esc_html_e( 'User Role', 'laterpay' );
            echo '</th><td>';
            esc_html_e( 'Unlimited Access to Categories', 'laterpay' );
            echo'</td></tr></table>';
        } else {
            // tell the user that he needs to have at least one custom role defined
            echo '<h4>';
            esc_html_e( 'Please add a custom role first.', 'laterpay' );
            echo '</h4>';
        }
    }

    /**
     * Generic method to render input fields.
     *
     * @param array $field array of field params
     *
     * @return void
     */
    public function get_input_field_markup( $field = null ) {

        if ( $field && isset( $field['name'] ) ) {
            $option_value = get_option( $field['name'] );
            $field_value  = isset( $field['value'] ) ? $field['value'] : get_option( $field['name'], '' );
            $type         = isset( $field['type'] ) ? $field['type'] : 'text';
            $classes      = isset( $field['classes'] ) ? $field['classes'] : array();

            // clean 'class' data
            if ( ! is_array( $classes ) ) {
                $classes = array($classes);
            }
            $classes = array_unique( $classes );

            if ( $type === 'text' ) {
                $classes[] = 'regular-text';
            }

            if ( isset( $field['label'] ) ) {
                echo '<label>';
            }

            echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $field_value ) . '"';

            // add id, if set
            if ( isset( $field['id'] ) ) {
                echo ' id="' . esc_attr( $field['id'] ). '"';
            }

            if ( isset( $field['label'] ) ) {
                echo ' style="margin-right:5px;"';
            }

            // add classes, if set
            if ( ! empty( $classes ) ) {
                echo ' class="' . esc_attr( implode( ' ', $classes ) ) . '"';
            }

            // add checked property, if set
            if ( 'checkbox' === $type ) {
                echo $option_value ? ' checked' : '';
            }

            // add disabled property, if set
            if ( isset( $field['disabled'] ) && $field['disabled'] ) {
                echo ' disabled';
            }

            // add onclick support
            if ( isset( $field['onclick'] ) && $field['onclick'] ) {
                // already using esc_js in add_laterpay_pro_merchant()
                echo ' onclick="' . esc_attr( $field['onclick'] ) . '"';
            }

            echo '>';

            if ( isset( $field['appended_text'] ) ) {
                echo '<dfn class="lp_appended-text">' . esc_html( $field['appended_text'] ) . '</dfn>';
            }
            if ( isset( $field['label'] ) ) {
                echo esc_html( $field['label'] );
                echo '</label>';
            }
        }
    }

    /**
     * Generic method to render select fields.
     *
     * @param array $field array of field params
     *
     * @return void
     */
    public function get_select_field_markup( $field = null ) {

        if ( $field && isset( $field['name'] ) ) {
            $field_value  = isset( $field['value'] ) ? $field['value'] : get_option( $field['name'] );
            $options      = isset( $field['options'] ) ? (array) $field['options'] : array();
            $classes      = isset( $field['class'] ) ? $field['class'] : array();
            if ( ! is_array( $classes ) ) {
                $classes = array($classes);
            }

            if ( isset( $field['label'] ) ) {
                echo '<label>';
            }
            // remove duplicated classes
            $classes = array_unique( $classes );

            echo '<select name="' . esc_attr( $field['name'] ) . '"';

            if ( isset( $field['id'] ) ) {
                echo ' id="' . esc_attr( $field['id'] ) . '"';
            }

            if ( isset( $field['disabled'] ) && $field['disabled'] ) {
                echo ' disabled';
            }

            if ( ! empty( $classes ) ) {
                echo ' class="' . esc_attr( implode( ' ', $classes ) ) . '"';
            }

            echo '>';

            foreach ( $options as $option ) {
                if ( ! is_array( $option ) ) {
                    $option_value = $option_text = $option;
                } else {
                    $option_value   = isset( $option['value'] ) ? $option['value'] : '';
                    $option_text    = isset( $option['text'] ) ? $option['text'] : '';
                }
                $selected = '';
                if ( absint( $field_value ) === absint( $option_value ) ) {
                    $selected = 'selected';
                }
                echo '<option value="' . esc_attr( $option_value ) .  '" ' . esc_attr( $selected ) . '>' . esc_html( $option_text ) . '</option>';
            }

            echo '</select>';
            if ( isset( $field['appended_text'] ) ) {
                echo '<dfn class="lp_appended-text">' . esc_html( $field['appended_text'] ) . '</dfn>';
            }
            if ( isset( $field['label'] ) ) {
                echo esc_html( $field['label'] );
                echo '</label>';
            }
        }
    }

    /**
     * Render the inputs for the unlimited access section.
     *
     * @param array $field array of field parameters
     *
     * @return void
     */
    public function get_unlimited_access_markup( $field = null ) {
        $role       = isset( $field['role'] ) ? $field['role'] : null;
        $categories = isset( $field['categories'] ) ? $field['categories'] : array();
        $unlimited  = get_option( 'laterpay_unlimited_access' ) ? get_option( 'laterpay_unlimited_access' ) : array();

        $count = 1;

        if ( $role ) {
            foreach ( $categories as $id => $name ) {
                $need_default   = ! isset( $unlimited[ $role ] ) || ! $unlimited[ $role ];
                $is_none_or_all = in_array( $id, array( 'none', 'all' ), true );
                $is_selected    = ! $need_default ? in_array( (string) $id, $unlimited[ $role ], true ) : false;

                echo '<input type="checkbox" ';
                echo 'id="lp_category--' . esc_attr( $role . $count ) . '"';
                echo 'class="lp_category-access-input';
                echo $is_none_or_all ? ' lp_global-access" ' : '" ';
                echo 'name="laterpay_unlimited_access[' . esc_attr( $role ) . '][]"';
                echo 'value="' . esc_attr( $id ) . '" ';

                if( $is_selected || ( $need_default && $id === 'none' ) ) {
                    echo 'checked';
                }

                echo '>';
                echo '<label class="lp_category-access-label';
                echo $is_none_or_all ? ' lp_global-access" ' : '" ';
                echo 'for="lp_category--' . esc_attr( $role . $count ) . '">';
                echo esc_html__( $name, 'laterpay' );
                echo '</label>';

                $count += 1;
            }
        }
    }

    /**
     * Validate unlimited access inputs before saving.
     *
     * @param $input
     *
     * @return array $valid array of valid values
     */
    public function validate_unlimited_access( $input ) {
        $valid      = array();
        $args       = array(
            'hide_empty' => false,
            'taxonomy'   => 'category',
            'parent'     => 0,
        );

        // get only 1st level categories
        $categories = get_categories( $args );

        if ( $input && is_array( $input ) ) {
            foreach ( $input as $role => $data ) {
                // check, if selected categories cover entire blog
                $covered = 1;
                foreach ( $categories as $category ) {
                    if ( ! in_array( ( string ) $category->term_id, $data, true ) ) {
                        $covered = 0;
                        break;
                    }
                }

                // set option 'all' for this role, if entire blog is covered
                if ( $covered ) {
                    $valid[ $role ] = array( 'all' );
                    continue;
                }

                // filter values, if entire blog is not covered
                if ( in_array( 'all', $data, true ) && in_array( 'none', $data, true ) && count( $data ) === 2 ) {
                    // unset option 'all', if option 'all' and option 'none' are selected at the same time
                    unset( $data[ array_search( 'all', $data, true ) ] );
                } elseif ( count( $data ) > 1 ) {
                    // unset option 'all', if at least one category is selected
                    if ( array_search( 'all', $data, true ) !== false ) {
                        foreach ( $data as $key => $option ) {
                            if ( ! in_array( $option, array( 'none', 'all' ), true ) ) {
                                unset( $data[ $key ] );
                            }
                        }
                    }

                    // unset all categories, if option 'none' is selected
                    if ( array_search( 'none', $data, true ) !== false ) {
                        foreach ( $data as $key => $option ) {
                            if ( ! in_array( $option, array( 'none', 'all' ), true ) ) {
                                unset( $data[ $key ] );
                            }
                        }
                    }
                }

                $valid[ $role ] = array_values( $data );
            }
        }

        return $valid;
    }

    /**
     * Render the inputs for the enabled post types section.
     *
     * @return void
     */
    public function get_enabled_post_types_markup() {
        $hidden_post_types = array(
            'nav_menu_item',
            'revision',
            'custom_css',
            'customize_changeset',
            'lp_passes',
            'lp_subscription',
            'oembed_cache',
        );

        $all_post_types     = get_post_types( array( ), 'objects' );
        $enabled_post_types = get_option( 'laterpay_enabled_post_types' );

        echo '<ul class="post_types">';
        foreach ( $all_post_types as $slug => $post_type ) {
            if (in_array($slug, $hidden_post_types, true)) {
                continue;
            }
            echo '<li><label title="' . esc_attr( $post_type->labels->name ) . '">';
            echo '<input type="checkbox" name="laterpay_enabled_post_types[]" value="' . esc_attr( $slug ) . '" ';
            if ( is_array( $enabled_post_types ) && in_array( $slug, $enabled_post_types, true ) ) {
                echo 'checked';
            }
            echo '>';
            echo '<span>' . esc_html( $post_type->labels->name ) . '</span>';
            echo '</label></li>';
        }
        echo '</ul>';
    }

    /**
     * Add LaterPay API settings section and fields.
     *
     * @return void
     */
    public function add_laterpay_api_settings() {
        add_settings_section(
            'laterpay_api_settings',
            __( 'LaterPay API Settings', 'laterpay' ),
            array( $this, 'get_laterpay_api_description' ),
            'laterpay'
        );

        $value      = absint( get_option( 'laterpay_api_fallback_behavior' ) );
        $options    = self::get_laterpay_api_options();
        add_settings_field(
            'laterpay_api_fallback_behavior',
            __( 'Fallback Behavior', 'laterpay' ),
            array( $this, 'get_select_field_markup' ),
            'laterpay',
            'laterpay_api_settings',
            array(
                'name'          => 'laterpay_api_fallback_behavior',
                'value'         => $value,
                'options'       => $options,
                'id'            => 'lp_js_laterpayApiFallbackSelect',
                'appended_text' => isset( $options[ $value ] ) ? $options[ $value ]['description'] : '',
            )
        );

        register_setting( 'laterpay', 'laterpay_api_fallback_behavior' );

        add_settings_field(
            'laterpay_api_enabled_on_homepage',
            __( 'Enabled on home page', 'laterpay' ),
            array( $this, 'get_input_field_markup' ),
            'laterpay',
            'laterpay_api_settings',
            array(
                'name'  => 'laterpay_api_enabled_on_homepage',
                'value' => 1,
                'type'  => 'checkbox',
                'label' => __( 'I want to enable requests to LaterPay API on home page', 'laterpay' ),
            )
        );

        register_setting( 'laterpay', 'laterpay_api_enabled_on_homepage' );
    }

    /**
     * Render the hint text for the LaterPay API section.
     *
     * @return void
     */
    public function get_laterpay_api_description() {
        echo '<p>';
        esc_html_e( 'Define fallback behavior in case LaterPay API is not responding and option to disallow plugin to contact LaterPay API on homepage', 'laterpay' );
        echo '</p>';
    }

    /**
     * Get LaterPay API options array.
     *
     * @return string description
     */
    public static function get_laterpay_api_options() {
        return array(
            array(
                'value'         => '0',
                'text'          => __( 'Do nothing', 'laterpay' ),
                'description'   => __( 'No user can access premium content while the LaterPay API is not responding.', 'laterpay' ),
            ),
            array(
                'value'         => '1',
                'text'          => __( 'Give full access', 'laterpay' ),
                'description'   => __( 'All users have full access to premium content in order to not disappoint paying users.', 'laterpay' ),
            ),
            array(
                'value'         => '2',
                'text'          => __( 'Hide premium content', 'laterpay' ),
                'description'   => __( 'Premium content is hidden from users. Direct access would be blocked.', 'laterpay' ),
            ),
        );
    }

    /**
     * Add LaterPay Pro merchant settings
     *
     * @return void
     */
    public function add_laterpay_pro_merchant() {
        add_settings_section(
            'laterpay_pro_merchant',
            __( 'LaterPay Pro Merchant', 'laterpay' ),
            array( $this, 'get_laterpay_pro_merchant_description' ),
            'laterpay'
        );

        $confirm_message = __( 'Only choose this option, if you have a LaterPay Pro merchant account. Otherwise, selling content with LaterPay might not work anymore.If you have questions about LaterPay Pro, please contact sales@laterpay.net. Are you sure that you want to choose this option?', 'laterpay' );

        add_settings_field(
            'laterpay_pro_merchant',
            __( 'LaterPay Pro Merchant', 'laterpay' ),
            array( $this, 'get_input_field_markup' ),
            'laterpay',
            'laterpay_pro_merchant',
            array(
                'name'    => 'laterpay_pro_merchant',
                'value'   => 1,
                'type'    => 'checkbox',
                'label'   => __( 'I have a LaterPay Pro merchant account.', 'laterpay' ),
                'onclick' => "if (this.checked) return confirm('" . esc_js( "{$confirm_message}" ) . "'); else return true;"
            )
        );

        register_setting( 'laterpay', 'laterpay_pro_merchant' );
    }

    /**
     * Render the hint text for the LaterPay Pro Merchant section.
     *
     * @return void
     */
    public function get_laterpay_pro_merchant_description() {
        echo '<p>';
        esc_html_e( 'Please choose, if you have a LaterPay Pro merchant account.', 'laterpay' );
        echo '</p>';
    }

    /**
     * Method to render ga fields.
     *
     * @param array $fields array of field params.
     *
     * @return void
     */
    public function get_ga_field_markup( $fields = null ) {

        if ( ! empty( $fields ) && is_array( $fields ) ) {
            foreach ( $fields as $field ) {
                if ( $field && isset( $field['parent_name'] ) ) {
                    $option_value = get_option( $field['parent_name'] );
                    $field_value  = isset( $option_value[$field['name']] ) ? $option_value[$field['name']] : '';
                    $type         = isset( $field['type'] ) ? $field['type'] : 'text';
                    $classes      = isset( $field['classes'] ) ? $field['classes'] : array();

                    // clean 'class' data.
                    if ( ! is_array( $classes ) ) {
                        $classes = array($classes);
                    }
                    $classes = array_unique( $classes );

                    // add class if type is text.
                    if ( 'text' === $type ) {
                        $classes[] = 'regular-text';
                    }

                    // add label if set.
                    if ( isset( $field['label'] ) ) {
                        echo '<label>';
                    }

                    echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $field['parent_name'] . '[' . $field['name'] . ']' ) . '" value="' . esc_attr( $field_value ) . '"';

                    // add id, if set.
                    if ( isset( $field['id'] ) ) {
                        echo ' id="' . esc_attr( $field['id'] ). '"';
                    }

                    if ( isset( $field['label'] ) ) {
                        echo ' style="margin-right:5px;"';
                    }

                    // add classes, if set.
                    if ( ! empty( $classes ) ) {
                        echo ' class="' . esc_attr( implode( ' ', $classes ) ) . '"';
                    }


                    // add checked property, if set.
                    if ( 'checkbox' === $type ) {
                        echo $field_value ? ' checked' : '';
                    }

                    // add disabled property, if set.
                    if ( isset( $field['disabled'] ) && $field['disabled'] ) {
                        echo ' disabled';
                    }

                    // add disabled property, if set.
                    if ( isset( $field['readonly'] ) && $field['readonly'] ) {
                        echo ' readonly';
                    }

                    // add onclick support.
                    if ( isset( $field['onclick'] ) && $field['onclick'] ) {
                        echo ' onclick="' . esc_attr( $field['onclick'] ) . '"';
                    }

                    echo '>';

                    // Display Auto Detected Text.
                    if ( 'text' === $type ) {
                        if ( isset( $field['show_notice'] ) && 1 === $field['show_notice'] ) {
                            echo '<span style="font-style: italic;font-weight: 600;">(' . esc_html__( 'auto detected', 'laterpay' ) . ')</span>';
                        }

                        // Update option and remove value.
                        $this->update_auto_detection_value( 0 );
                    }

                    // add extra text if set.
                    if ( isset( $field['appended_text'] ) ) {
                        echo '<dfn class="lp_appended-text">' . esc_html( $field['appended_text'] ) . '</dfn>';
                    }

                    if ( isset( $field['label'] ) ) {
                        echo esc_html( $field['label'] );
                        echo '</label>';
                    }

                    // add support for modal.
                    if ( isset( $field['modal'] ) ) {
                        echo '<div id="' . esc_attr( $field['modal']['id'] ) . '" style="display:none;">';
                        echo '<p>' . wp_kses( $field['modal']['message'], [ 'br' => [] ] ) . '</p>';
                        echo '<button class="lp_js_disableTracking button button-primary lp_mt- lp_mb-">' .
                             esc_html( $field['modal']['saveText'] ) . '</button>';
                        echo '<button type="button" class="button button-secondary lp_mt- lp_mb- lp_js_ga_cancel">' . esc_html( $field['modal']['cancelText'] ) . '</button>';
                        echo '</div>';
                    }
                }
            }
        }
    }

    /**
     * Add Google Analytics Tracking Section.
     *
     * @return void
     */
    public function add_ga_tracking_settings() {
        add_settings_section(
            'laterpay_ga_tracking',
            esc_html__( 'Google Analytics Tracking', 'laterpay' ),
            array( $this, 'get_ga_tracking_section_description' ),
            'laterpay'
        );

        $user_tracking = $this->get_ga_tracking_value();

        // Get Value of Auto Detected Text if set.
        if ( ! empty( $user_tracking['auto_detected'] ) && 1 === (int) $user_tracking['auto_detected'] ) {
            $show_notice = 1;
        } else {
            $show_notice = 0;
        }

        // Add Personal GA Section.
        add_settings_field(
            'laterpay_user_tracking_data',
            esc_html__( 'Your Personal Google Analytics:', 'laterpay' ),
            array( $this, 'get_ga_field_markup' ),
            'laterpay',
            'laterpay_ga_tracking',
            array(
                array(
                    'name'        => 'laterpay_ga_personal_enabled_status',
                    'value'       => 1,
                    'type'        => 'checkbox',
                    'parent_name' => 'laterpay_user_tracking_data',
                ),
                array(
                    'name'        => 'laterpay_ga_personal_ua_id',
                    'type'        => 'text',
                    'classes'     => ['lp_ga-input'],
                    'parent_name' => 'laterpay_user_tracking_data',
                    'show_notice' => $show_notice,
                )
            )
        );

        register_setting( 'laterpay', 'laterpay_user_tracking_data' );

        // Add LaterPay GA Section.
        add_settings_field(
            'laterpay_tracking_data',
            __( 'LaterPay Google Analytics:', 'laterpay' ),
            array( $this, 'get_ga_field_markup' ),
            'laterpay',
            'laterpay_ga_tracking',
            array(
                array(
                    'name'        => 'laterpay_ga_enabled_status',
                    'value'       => 1,
                    'type'        => 'checkbox',
                    'parent_name' => 'laterpay_tracking_data',
                    'modal'       => array(
                        'id'         => 'lp_ga_modal_id',
                        'message'    => sprintf( '%1$s <br/><br/> %2$s',
                            esc_html__( 'LaterPay collects this information to improve our products and
                                        services and also so that you can determine the effectiveness of your pricing
                                        strategy using our Merchant Analytics dashboard.', 'laterpay' ),
                            esc_html__( 'Are you sure you would like to disable this feature?', 'laterpay' ) ),
                        'saveText'   => esc_html__( 'Yes, Disable Tracking', 'laterpay' ),
                        'cancelText' => esc_html__( 'Cancel', 'laterpay' ),
                    ),
                    ),
                array(
                    'name'        => 'laterpay_ga_ua_id',
                    'type'        => 'text',
                    'classes'     => ['lp_ga-input'],
                    'readonly'    => true,
                    'parent_name' => 'laterpay_tracking_data',
                )
            )
        );

        register_setting( 'laterpay', 'laterpay_tracking_data' );

    }

    /**
     * Get Google Analytics Track Section Description.
     *
     * @return void
     */
    public function get_ga_tracking_section_description() {
        echo '<p>';
        printf( '%1$s <br/> %2$s <a href="%3$s" target="_blank">%4$s</a> %5$s',
            esc_html__( 'LaterPay is not in the business of selling data. This tracking information is for your benefit
            so that you can determine the effectiveness of ','laterpay' ),
            esc_html__( 'your pricing strategy. To view your analytics, log in to your LaterPay account at', 'laterpay'),
            esc_url( 'https://www.laterpay.net/' ),
            'laterpay.net',
            esc_html__( 'to view your Merchant Analytics dashboard.', 'laterpay' )
        );
        echo '</p>';

        echo '<table class="form-table"><tr><th></th> <td>';
        esc_html_e( 'Enabled', 'laterpay' );
        echo '</td><td width="79%">';
        esc_html_e( 'Google Analytics "UA-ID"', 'laterpay' );
        echo'</td></tr></table>';
    }

    /**
     * Get User Tracking Data.
     *
     * @return array
     */
    public function get_ga_tracking_value() {
        return get_option( 'laterpay_user_tracking_data', array() );
    }

    /**
     * Update option value fro User Tracking Data.
     *
     * @param int $status Status to set for auto detected property.
     *
     * @return void
     */
    public function update_auto_detection_value( $status ) {

        $user_tracking = $this->get_ga_tracking_value();

        if ( 0 === $status ) {
            unset( $user_tracking['auto_detected'] );
            update_option( 'laterpay_user_tracking_data', $user_tracking );
        }
    }

}

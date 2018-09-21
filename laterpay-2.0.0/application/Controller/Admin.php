<?php

/**
 * LaterPay admin controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Controller_Admin extends LaterPay_Controller_Base
{

    const ADMIN_MENU_POINTER            = 'lpwpp01';
    const POST_PRICE_BOX_POINTER        = 'lpwpp02';
    const POST_TEASER_CONTENT_POINTER   = 'lpwpp03';

    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'laterpay_admin_head' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_plugin_is_active', 200 ),
                array( 'add_html5shiv_to_admin_head' ),
            ),
            'laterpay_admin_menu' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_plugin_is_active', 200 ),
                array( 'add_to_admin_panel' ),
            ),
            'laterpay_admin_menu_data' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_plugin_is_active', 200 ),
                array( 'get_admin_menu' ),
            ),
            'laterpay_admin_footer_scripts' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_plugin_is_active', 200 ),
                array( 'modify_footer' ),
            ),
            'laterpay_post_edit' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_plugin_is_active', 200 ),
                array( 'help_wp_edit_post' ),
            ),
            'laterpay_post_new' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_plugin_is_active', 200 ),
                array( 'help_wp_add_post' ),
            ),
            'laterpay_admin_enqueue_scripts' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_plugin_is_active', 200 ),
                array( 'add_plugin_admin_assets' ),
                array( 'add_admin_pointers_script' ),
            ),
        );
    }

    /**
     * Show plugin in administrator panel.
     *
     * @return void
     */
    public function add_to_admin_panel() {
        if( get_option( 'laterpay_plugin_is_in_live_mode' ) ) {
            $plugin_page = LaterPay_Helper_View::$pluginPage;
        } else {
            $plugin_page = 'laterpay-account-tab';
        }
        add_menu_page(
            __( 'LaterPay Plugin Settings', 'laterpay' ),
            'LaterPay',
            'moderate_comments', // allow Super Admin, Admin, and Editor to view the settings page
            $plugin_page,
            array( $this, 'run' ),
            'dashicons-laterpay-logo',
            81
        );

        $menu = LaterPay_Helper_View::get_admin_menu();
        foreach ( $menu as $name => $page ) {
            $slug    = $page['url'];
            $page_id = add_submenu_page(
                $plugin_page,
                $page['title'] . ' | ' . __( 'LaterPay Plugin Settings', 'laterpay' ),
                $page['title'],
                $page['cap'],
                $slug,
                isset( $page['run'] ) ? $page['run'] : array( $this, 'run_' . $name )
            );
            if ( isset( $page['submenu'] ) ) {
                $sub_page   = $page['submenu'];
                add_submenu_page(
                    $sub_page['name'],
                    $sub_page['title'] . ' | ' . __( 'LaterPay Plugin Settings', 'laterpay' ),
                    $sub_page['title'],
                    $page['cap'],
                    $sub_page['url'],
                    array( $this, 'run_' . $sub_page['name'] )
                );
            }
            LaterPay_Hooks::add_wp_action( 'load-' . $page_id, 'laterpay_load_' . $page_id );
            $help_action = isset( $page['help'] ) ? $page['help'] : array( $this, 'help_' . $name );
            laterpay_event_dispatcher()->add_listener( 'laterpay_load_' . $page_id, $help_action );
        }
    }

    /**
     *
     * @param string $name
     * @param mixed  $args
     *
     * @return void
     */
    public function __call( $name, $args ) {
        if ( substr( $name, 0, 4 ) === 'run_' ) {
            return $this->run( strtolower( substr( $name, 4 ) ) );
        } elseif ( substr( $name, 0, 5 ) === 'help_' ) {
            return $this->help( strtolower( substr( $name, 5 ) ) );
        }
    }

    /**
     * @see LaterPay_Core_View::load_assets()
     */
    public function load_assets() {
        parent::load_assets();

        // load LaterPay-specific CSS
        wp_register_style(
            'laterpay-backend',
            $this->config->get( 'css_url' ) . 'laterpay-backend.css',
            array(),
            $this->config->get( 'version' )
        );
        wp_register_style(
            'open-sans',
            '//fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,300,400,600&subset=latin,latin-ext'
        );
        wp_register_style(
            'laterpay-select2',
            $this->config->get( 'css_url' ) . 'vendor/select2.min.css',
            array(),
            $this->config->get( 'version' )
        );
        wp_enqueue_style( 'open-sans' );
        wp_enqueue_style( 'laterpay-select2' );
        wp_enqueue_style( 'laterpay-backend' );

        // load LaterPay-specific JS
        wp_register_script(
            'laterpay-velocity',
            $this->config->get( 'js_url' ) . 'vendor/velocity.min.js',
            array(),
            $this->config->get( 'version' ),
            true
        );
        wp_register_script(
            'laterpay-backend',
            $this->config->get( 'js_url' ) . 'laterpay-backend.js',
            array( 'jquery', 'laterpay-velocity' ),
            $this->config->get( 'version' ),
            true
        );
        wp_enqueue_script( 'laterpay-velocity' );
        wp_enqueue_script( 'laterpay-backend' );

    }

    /**
     * Add html5shim to the admin_head() for Internet Explorer < 9.
     *
     * @wp-hook admin_head
     * @param LaterPay_Core_Event $event
     * @return void
     */
    public function add_html5shiv_to_admin_head( LaterPay_Core_Event $event ) {
        $view_args = array(
            'scripts' => array(
                '//html5shim.googlecode.com/svn/trunk/html5.js',
            ),
        );
        $this->assign( 'laterpay', $view_args );

	    $this->render( 'backend/partials/html5shiv' );
    }

    /**
     * Constructor for class LaterPayController, processes the tabs in the plugin backend.
     *
     * @param string $tab
     *
     * @return void
     */
    public function run( $tab = '' ) {
        $this->load_assets();

        if ( '' === $tab ) {
            $tab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
        }

        switch ( $tab ) {
            case 'pricing':
                $pricing_controller = new LaterPay_Controller_Admin_Pricing( $this->config );
                $pricing_controller->render_page();
                break;

            // render appearance tab
            case 'appearance':
                $appearance_controller = new LaterPay_Controller_Admin_Appearance( $this->config );
                $appearance_controller->render_page();
                break;

            // render account tab
            case 'account':
                $account_controller = new LaterPay_Controller_Admin_Account( $this->config );
                $account_controller->render_page();
                break;
        }
    }

    /**
     * Render contextual help, depending on the current page.
     *
     * @param string $tab
     *
     * @return void
     */
    public function help( $tab = '' ) {
        switch ( $tab ) {
            case 'wp_edit_post':
            case 'wp_add_post':
                $this->render_add_edit_post_page_help();
                break;

            case 'pricing':
                $this->render_pricing_tab_help();
                break;

            case 'appearance':
                $this->render_appearance_tab_help();
                break;

            case 'account':
                $this->render_account_tab_help();
                break;

            default:
                break;
        }
    }

    /**
     * Add contextual help for add / edit post page.
     *
     * @return void
     */
    protected function render_add_edit_post_page_help() {
        $screen = get_current_screen();
        $screen->add_help_tab( array(
            'id'      => 'laterpay_add_edit_post_page_help',
            'title'   => __( 'LaterPay', 'laterpay' ),
            'content' => __( '
            <p>
                <strong>Setting Prices</strong><br>
                You can set an individual price for each post.<br>
                Possible prices are either 0.00 (free) or any value between 0.05 (inclusive) and 149.99 (inclusive).<br>
                If you set an individual price, category default prices you might have set for the post\'s category(s)
                won\'t apply anymore, unless you make the post use a category default price.
            </p>
            <p>
                <strong>Dynamic Pricing Options</strong><br>
                You can define dynamic price settings for each post to adjust prices automatically over time.<br>
                <br>
                For example, you could sell a "breaking news" post for 0.49 (high interest within the first 24 hours)
                and automatically reduce the price to 0.05 on the second day.
            </p>
            <p>
                <strong>Teaser</strong><br>
                The teaser should give your visitors a first impression of the content you want to sell.<br>
                You don\'t have to provide a teaser for every single post on your site:<br>
                by default, the LaterPay plugin uses the first 60 words of each post as teaser content.
                <br>
                Nevertheless, we highly recommend manually creating the teaser for each post, to increase your sales.
            </p>
            <p>
                <strong>Pay Later</strong><br>
                If you choose to sell your content as <strong>Pay Later</strong>, a user pays the purchased content <strong>later</strong>. The purchase is added to his LaterPay invoice and he has to log in to LaterPay and pay, once his invoice has reached 5.00 (EUR or USD).<br>
                LaterPay <strong>recommends</strong> Pay Later for all prices up to 5.00 as they deliver the <strong>best purchase experience</strong> for your users.<br>
                PPU is possible for prices between (including) <strong>0.05</strong> and (including) <strong>5.00</strong>.
            </p>
            <p>
                <strong>Pay Now</strong><br>
                If you sell your content as <strong>Pay Now</strong>, a user has to <strong>log in</strong> to LaterPay and <strong>pay</strong> for your content <strong>immediately</strong>.<br>
                Pay Now are especially suitable for higher-value content and / or content that immediately occasions costs (e. g. license fees for a video stream).<br>
                Pay Now are possible for prices between (including) <strong>1.49 € (in Europe) / $ 1.99 (in the U.S.)</strong> and (including) <strong>149.99</strong>.
            </p>', 'laterpay'
            ),
        ) );
    }

    /**
     * Add contextual help for pricing tab.
     *
     * @return  void
     */
    protected function render_pricing_tab_help() {
        $screen = get_current_screen();
        $screen->add_help_tab( array(
            'id'      => 'laterpay_pricing_tab_help_global_default_price',
            'title'   => __( 'Global Default Price', 'laterpay' ),
            'content' => __( '
            <p>
                The global default price is used for all posts, for which no
                category default price or individual price has been set.<br>
                Accordingly, setting the global default price to 0.00 makes
                all articles free, for which no category default price or
                individual price has been set.
            </p>', 'laterpay'
            ),
        ) );
        $screen->add_help_tab( array(
            'id'      => 'laterpay_pricing_tab_help_category_default_price',
            'title'   => __( 'Category Default Prices', 'laterpay' ),
            'content' => __( '
            <p>
                A category default price is applied to all posts in a given
                category that don\'t have an individual price.<br>
                A category default price overwrites the global default price.<br>
                If a post belongs to multiple categories, you can choose on
                the add / edit post page, which category default price should
                be effective.<br>
                For example, if you have set a global default price of 0.15,
                but a post belongs to a category with a category default price
                of 0.30, that post will sell for 0.30.
            </p>', 'laterpay'
            ),
        ) );
        $screen->add_help_tab( array(
            'id'      => 'laterpay_pricing_tab_help_currency',
            'title'   => __( 'Currency', 'laterpay' ),
            'content' => __( '
            <p>
                The plugin supports two currencies, depending on the region of your LaterPay merchant account: EUR (€) for European merchant accounts, USD ($) for a U.S. merchant account.<br>
                Changing the standard currency will not convert the prices you have set.
                Only the currency code next to the price is changed.<br>
                For example, if your global default price is 0.10 EUR and you change the default currency to USD, the global default
                price will be 0.10 USD.<br>
            </p>', 'laterpay'
            ),
        ) );
        $screen->add_help_tab( array(
            'id'      => 'laterpay_pricing_tab_help_time_passes',
            'title'   => __( 'Time Passes', 'laterpay' ),
            'content' => __( '
            <p>
                <strong>Validity of Time Passes</strong><br>
                With time passes, you can offer your users <strong>time-limited</strong> access to your content. You can define, which content a time pass should cover and for which period of time it should be valid. A time pass can be valid for <strong>all LaterPay content</strong>
            </p>
            <ul>
                <li>on your <strong>entire website</strong>,</li>
                <li>in one <strong>specific category</strong>, or</li>
                <li>on your entire website <strong>except from a specific category</strong>.</li>
            </ul>
            <p>
                The <strong>validity period</strong> of a time pass starts with the <strong>purchase</strong> and is defined for a <strong>continuous</strong> use – i.e. it doesn\'t matter, if a user is on your website during the entire validity period. After a time pass has expired, the access to the covered content is automatically refused. Please note: Access to pages which are <strong>still open</strong> when a pass expires will be refused only after <strong>reloading</strong> the respective page. <strong>Any files</strong> (images, documents, presentations...), that were downloaded during the validity period, can still be used after the access has expired – but the user will <strong>not</strong> be able to <strong>download them </strong> without purchasing again.
            </p>
            <p>
                <strong>Deleting Time Passes</strong><br>
                If you <strong>delete</strong> a time pass, users who have bought this time pass <strong>will still have access</strong> to the covered content. Deleted time passes <strong>can\'t be restored</strong>.
            </p>
            <p>
                <strong>Time Passes and Individual Sales</strong><br>
                When a user purchases a time pass, he has access to all the content covered by this pass during the validity period. Of course, you can still sell your content individually.<br>
                Example: A user has already purchased the post "New York – a Travel Report" for 0.29. Now he purchases a Week Pass for the category "Travel Reports" for 0.99. The category also contains the "New York" post. For one week, he can now read all posts in the category "Travel Reports" for a fixed price of 0.99. After this week, the access expires automatically. During the validity period, the user will not see any LaterPay purchase buttons for posts in the category "Travel Reports". After the pass has expired, the user will still have access to the post he had previously purchased individually.
            </p>', 'laterpay'
            ),
        ) );
        $screen->add_help_tab( array(
            'id'      => 'laterpay_pricing_tab_help_time_pass_vouchers',
            'title'   => __( 'Time Pass Vouchers', 'laterpay' ),
            'content' => __( '
            <p>
                You can create any number of voucher codes for each time pass. A voucher code allows one (or multiple) user(s) to purchase a time pass for a reduced price. A user can enter a voucher code right <strong>below the time passes</strong> by clicking <strong>"I have a voucher"</strong>. If the entered code is a valid voucher code, the price of the respective time pass will be reduced.<br>
                A voucher code can be used <strong>any number of times</strong> and is <strong>not linked</strong> to a specific user. If you want to invalidate a time pass voucher code, you can simply delete it.<br>
                <strong>Deleting</strong> a voucher code will <strong>not affect</strong> the validity of time passes which have already been purchased using this voucher code.
            </p>
            <p>
            Follow these steps to create a voucher code:
            </p>
            <ul>
                <li>Click the "Edit" icon next to the time pass for which you want to create a voucher code.</strong>,</li>
                <li>Enter a price next to \'Offer this time pass at a reduced price of\'. If you enter a price of \'0.00\', anyone with this voucher code can purchase the respective time pass for 0.00.<br>
                    If you enter a price of e.g. \'0.20\', entering this voucher code will change the price of the respective time pass to 0.20.</li>
                <li>Click the \'Save\' button.</li>
            </ul>', 'laterpay'
            ),
        ) );
    }

    /**
     * Add contextual help for appearance tab.
     *
     * @return  void
     */
    protected function render_appearance_tab_help() {
        $screen = get_current_screen();
        $screen->add_help_tab( array(
            'id'      => 'laterpay_appearance_tab_help_preview_mode',
            'title'   => __( 'Preview Mode', 'laterpay' ),
            'content' => __( '
            <p>
                The preview mode defines, how teaser content is shown to your
                visitors.<br>
                You can choose between two preview modes:
            </p>
            <ul>
                <li>
                    <strong>Teaser only</strong> &ndash; This mode shows only
                    the teaser with an unobtrusive purchase link below.
                </li>
                <li>
                    <strong>Teaser + overlay</strong> &ndash; This mode shows
                    the teaser and an excerpt of the full content under a
                    semi-transparent overlay that briefly explains LaterPay.<br>
                    The plugin never loads the entire content before a user has
                    purchased it.
                </li>
            </ul>', 'laterpay'
            ),
        ) );
        $screen->add_help_tab( array(
            'id'      => 'laterpay_appearance_tab_help_purchase_button_position',
            'title'   => __( 'Purchase Button Position', 'laterpay' ),
            'content' => __( '
            <p>
                You can choose, if the LaterPay purchase button is positioned at its default or a custom position:
            </p>
            <ul>
                <li>
                    <strong>Default position</strong> &ndash; The LaterPay purchase button is displayed at the top on the right below the title.
                </li>
                <li>
                    <strong>Custom position</strong> &ndash; You can position the LaterPay purchase button yourself by using the stated WordPress action.
                </li>
            </ul>', 'laterpay'
            ),
        ) );
        $screen->add_help_tab( array(
            'id'      => 'laterpay_appearance_tab_help_time_pass_position',
            'title'   => __( 'Time Pass Position', 'laterpay' ),
            'content' => __( '
            <p>
                You can choose, if time passes are positioned at their default or a custom position:
            </p>
            <ul>
                <li>
                    <strong>Default position</strong> &ndash; Time passes are displayed right below each paid article.<br>
                    If you want to display time passes also for free posts, you can choose \'I want to display the time passes widget on free and paid posts\' in the plugin\'s advanced settings (Settings > LaterPay).
                </li>
                <li>
                    <strong>Custom position</strong> &ndash; You can position time passes yourself by using the stated WordPress action.
                </li>
            </ul>', 'laterpay'
            ),
        ) );
    }

    /**
     * Add contextual help for account tab.
     *
     * @return void
     */
    protected function render_account_tab_help() {
        $screen = get_current_screen();
        $screen->add_help_tab( array(
            'id'      => 'laterpay_account_tab_help_api_credentials',
            'title'   => __( 'API Credentials', 'laterpay' ),
            'content' => __( '
            <p>
                To access the LaterPay API, you need LaterPay API credentials,
                consisting of
            </p>
            <ul>
                <li><strong>Merchant ID</strong> (a 22-character string) and</li>
                <li><strong>API Key</strong> (a 32-character string).</li>
            </ul>
            <p>
                LaterPay runs two completely separated API environments that
                need <strong>different API credentials:</strong>
            </p>
            <ul>
                <li>
                    The <strong>Sandbox</strong> environment for testing and
                    development use.<br>
                    In this environment you can play around with LaterPay
                    without fear, as your transactions will only be simulated
                    and not actually be processed.<br>
                    LaterPay guarantees no particular service level of
                    availability for this environment.
                </li>
                <li>
                    The <strong>Live</strong> environment for production use.<br>
                    In this environment all transactions will be actually
                    processed and credited to your LaterPay merchant account.<br>
                    The LaterPay SLA for availability and response time apply.
                </li>
            </ul>
            <p>
                The LaterPay plugin comes with a set of <strong>public Sandbox
                credentials</strong> to allow immediate testing use.
            </p>
            <p>
                If you want to switch to <strong>Live mode</strong> and sell
                content, you need your individual <strong>Live API credentials.
                </strong><br>
                Due to legal reasons, we can email you those credentials only
                once we have received a <strong>signed merchant contract</strong>
                including <strong>all necessary identification documents</strong>.<br>
                <a href="https://www.laterpay.net/how-to-become-a-content-provider" target="blank">Visit our website to read more about how to become a content provider.</a>
            </p>', 'laterpay'
            ),
        ) );
        $screen->add_help_tab( array(
            'id'      => 'laterpay_account_tab_help_plugin_mode',
            'title'   => __( 'Plugin Mode', 'laterpay' ),
            'content' => __( '
            <p>You can run the LaterPay plugin in three modes:</p>
            <ul>
                <li>
                    <strong>Invisible Test Mode</strong> &ndash; This test mode lets you
                    test your plugin configuration.<br>
                    While providing the full plugin functionality, payments are
                    only simulated and not actually processed.<br>
                    The plugin will <em>only</em> be visible to admin users,
                    not to visitors.<br>
                    This is the <strong>default</strong> setting after activating the plugin for the first time.
                </li>
                <li>
                    <strong>Visible Test Mode</strong> &ndash; The plugin will be <strong>visible</strong> to regular visitors and users,<br>
                    but payments will still only be simulated and not actually processed.
                </li>
                <li>
                    <strong>Live Mode</strong> &ndash; In live mode, the plugin
                    is publicly visible and manages access to paid content.<br>
                    All payments are actually processed.
                </li>
            </ul>
            <p>
                Using the LaterPay plugin usually requires some adjustments of
                your theme.<br>
                Therefore, we recommend installing, configuring, and testing
                the LaterPay plugin on a test system before activating it on
                your production system.
            </p>', 'laterpay'
            ),
        ) );
    }

    /**
     * Add WordPress pointers to pages.
     *
     * @param LaterPay_Core_Event $event
     * @return void
     */
    public function modify_footer( LaterPay_Core_Event $event ) {
        $pointers = LaterPay_Controller_Admin::get_pointers_to_be_shown();

        // don't render the partial, if there are no pointers to be shown
        if ( empty( $pointers ) ) {
            return;
        }

        // assign pointers
        $view_args = array(
            'pointers' => $pointers,
        );

        $this->assign( 'laterpay', $view_args );
        $this->render( 'backend/partials/pointer-scripts', null, true );
    }

    /**
     * Load LaterPay stylesheet with LaterPay vector logo on all pages where the admin menu is visible.
     *
     * @return void
     */
    public function add_plugin_admin_assets() {
        wp_register_style(
            'laterpay-admin',
            $this->config->css_url . 'laterpay-admin.css',
            array(),
            $this->config->version
        );

        wp_enqueue_style( 'laterpay-admin' );

        // apply colors config
        LaterPay_Helper_View::apply_colors( 'laterpay-admin' );
    }

    /**
     * Hint at the newly installed plugin using WordPress pointers.
     *
     * @return void
     */
    public function add_admin_pointers_script() {
        $pointers = LaterPay_Controller_Admin::get_pointers_to_be_shown();

        // don't enqueue the assets, if there are no pointers to be shown
        if ( empty( $pointers ) ) {
            return;
        }

        wp_enqueue_script( 'wp-pointer' );
        wp_enqueue_style( 'wp-pointer' );
    }

    /**
     * Return the pointers that have not been shown yet.
     *
     * @return array $pointers
     */
    public function get_pointers_to_be_shown() {
        $dismissed_pointers = explode( ',', (string) LaterPay_Helper_User::get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
        $pointers = array();

        if ( ! in_array( LaterPay_Controller_Admin::ADMIN_MENU_POINTER, $dismissed_pointers, true ) ) {
            $pointers[] = LaterPay_Controller_Admin::ADMIN_MENU_POINTER;
        }
        // add pointers to LaterPay features on add / edit post page
        if ( ! in_array( LaterPay_Controller_Admin::POST_PRICE_BOX_POINTER, $dismissed_pointers, true ) ) {
            $pointers[] = LaterPay_Controller_Admin::POST_PRICE_BOX_POINTER;
        }
        if ( ! in_array( LaterPay_Controller_Admin::POST_TEASER_CONTENT_POINTER, $dismissed_pointers, true ) ) {
            $pointers[] = LaterPay_Controller_Admin::POST_TEASER_CONTENT_POINTER;
        }

        return $pointers;
    }

    /**
     * Return all pointer constants from current class.
     *
     * @return array $pointers
     */
    public static function get_all_pointers() {
        $reflection         = new ReflectionClass( __CLASS__ );
        $class_constants    = $reflection->getConstants();
        $pointers           = array();

        if ( $class_constants ) {
            foreach ( array_keys( $class_constants ) as $key_value ) {
                if ( strpos( $key_value, 'POINTER' ) !== false ) {
                    $pointers[] = $class_constants[ $key_value ];
                }
            }
        }

        return $pointers;
    }


    /**
     * Get links to be rendered in the plugin backend navigation.
     *
     * @param LaterPay_Core_Event $event
     */
    public function get_admin_menu( LaterPay_Core_Event $event ) {
        $menu = (array) $event->get_result();

        // @link http://codex.wordpress.org/Roles_and_Capabilities#Capability_vs._Role_Table
        // cap "activate_plugins"   => Super Admin, Admin
        // cap "moderate_comments"  => Super Admin, Admin, Editor

        $menu['pricing'] = array(
            'url'   => 'laterpay-pricing-tab',
            'title' => __( 'Pricing', 'laterpay' ),
            'cap'   => 'activate_plugins',
        );

        $menu['appearance'] = array(
            'url'   => 'laterpay-appearance-tab',
            'title' => __( 'Appearance', 'laterpay' ),
            'cap'   => 'activate_plugins',
        );

        $menu['account'] = array(
            'url'   => 'laterpay-account-tab',
            'title' => __( 'Account', 'laterpay' ),
            'cap'   => 'activate_plugins',
        );

        if ( ! get_option( 'laterpay_plugin_is_in_live_mode' ) ) {
            $menu = array_reverse( $menu );
        }

        $event->set_result( $menu );
    }
}

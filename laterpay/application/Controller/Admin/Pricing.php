<?php

/**
 * LaterPay pricing controller.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Controller_Admin_Pricing extends LaterPay_Controller_Admin_Base
{
    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'wp_ajax_laterpay_pricing' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'process_ajax_requests' ),
                array( 'laterpay_on_ajax_user_can_activate_plugins', 200 ),
            ),
            'wp_ajax_laterpay_get_category_prices' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'process_ajax_requests' ),
                array( 'laterpay_on_ajax_user_can_activate_plugins', 200 ),
            ),
            'wp_ajax_laterpay_enabled_post_types' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'process_ajax_requests' ),
                array( 'laterpay_on_ajax_user_can_activate_plugins', 200 ),
            ),
            'laterpay_register_subscription_cpt' => array(
                array( 'register_subscription_cpt' )
            ),
            'laterpay_register_passes_cpt' => array(
                array( 'register_passes_cpt' ),
            ),
        );
    }

    /**
     * Registers 'Subscription' CPT.
     *
     * @param LaterPay_Core_Event $event
     */
    public function register_subscription_cpt( LaterPay_Core_Event $event ) {

        register_post_type( 'lp_subscription',
            array(
                'labels'      => array(
                    'name'          => __( 'Subscriptions', 'laterpay' ),
                    'singular_name' => __( 'Subscription', 'laterpay' ),
                )
            )
        );
    }
    /**
     * @see LaterPay_Core_View::load_assets()
     */
    public function load_assets() {
        parent::load_assets();

        // Get data for GA.
        $merchant_key      = LaterPay_Controller_Admin::get_merchant_id_for_ga();
        $data_for_localize = [
            'categories_count'    => absint( LaterPay_Model_CategoryPriceWP::get_instance()->get_categories_with_defined_price_count() ),
            'time_passes_count'   => absint( LaterPay_Helper_TimePass::get_time_passes_count( true ) ),
            'subscriptions_count' => absint( LaterPay_Helper_Subscription::get_subscriptions_count( true ) ),
            'lp_current_version'  => esc_html( get_option( 'laterpay_plugin_version' ) ),
            'lp_plugin_status'    => LaterPay_Helper_View::is_plugin_in_live_mode() ? 'LIVE' : 'TEST',
            'site_url'            => get_site_url(),
        ];

        LaterPay_Controller_Admin::register_common_scripts( 'pricing', $data_for_localize );

        // load page-specific JS
        wp_register_script(
            'laterpay-select2',
            $this->config->get( 'js_url' ) . 'vendor/select2.min.js',
            array( 'jquery' ),
            $this->config->get( 'version' ),
            true
        );
        wp_register_script(
            'laterpay-backend-pricing',
            $this->config->get( 'js_url' ) . 'laterpay-backend-pricing.js',
            array( 'jquery', 'laterpay-select2', 'laterpay-common' ),
            $this->config->get( 'version' ),
            true
        );
        wp_enqueue_script( 'laterpay-select2' );
        wp_enqueue_script( 'laterpay-backend-pricing' );

        // translations
        $i18n = array(
            // bulk price editor
            'after'                     => __( 'After', 'laterpay' ),
            'make'                      => __( 'Make', 'laterpay' ),
            'free'                      => __( 'free', 'laterpay' ),
            'to'                        => __( 'to', 'laterpay' ),
            'by'                        => __( 'by', 'laterpay' ),
            'toGlobalDefaultPrice'      => __( 'to global default price of', 'laterpay' ),
            'toCategoryDefaultPrice'    => __( 'to category default price of', 'laterpay' ),
            'updatePrices'              => __( 'Update Prices', 'laterpay' ),
            'delete'                    => __( 'Delete', 'laterpay' ),
            // time pass editor
            'confirmDeleteTimepass'     => __( 'Are you sure?', 'laterpay' ),
            'confirmDeleteSubscription' => __( 'Do you really want to discontinue this subscription? If you delete it, it will continue to renew for users who have an active subscription until the user cancels it. Existing subscribers will still have access to the content in their subscription. New users won\'t be able to buy the subscription anymore. Do you want to delete this subscription?', 'laterpay' ),
            'voucherText'               => __( 'reduces the price to', 'laterpay' ),
            'payLaterToolTip'           => esc_html__( 'Pay Later allows users to gain access now by committing to pay once their invoice reaches $5 or 5€; it is available for posts with pricing between 0.05 and 5.00', 'laterpay' ),
            'payNowToolTip'             => esc_html__( 'Pay Now requires users pay for purchased content immediately; available for posts with pricing above $1.99 or 1.49€', 'laterpay' ),
            'subVoucherMinimum'         => esc_html__( 'Subscriptions, like other Pay Now content, must have pricing above $1.99 or 1.49€', 'laterpay' ),
            'subVoucherMaximumPrice'    => esc_html__( 'The voucher price must be less than or equal to the subscription price.', 'laterpay' ),
        );

        // pass localized strings and variables to script
        // time pass with vouchers
        $time_passes_model   = LaterPay_Model_TimePassWP::get_instance();
        $time_passes_list    = $time_passes_model->get_active_time_passes();
        $vouchers_list       = LaterPay_Helper_Voucher::get_all_time_pass_vouchers();
        $vouchers_statistic  = LaterPay_Helper_Voucher::get_all_vouchers_statistic();

        // subscriptions and its vouchers if any.
        $subscriptions_model = LaterPay_Model_SubscriptionWP::get_instance();
        $subscriptions_list  = $subscriptions_model->get_active_subscriptions();
        $sub_vouchers_list   = LaterPay_Helper_Voucher::get_all_subscription_vouchers();

        wp_localize_script(
            'laterpay-backend-pricing',
            'lpVars',
            array(
                'locale'                => get_locale(),
                'i18n'                  => $i18n,
                'currency'              => wp_json_encode( LaterPay_Helper_Config::get_currency_config() ),
                'globalDefaultPrice'    => LaterPay_Helper_View::format_number( get_option( 'laterpay_global_price' ) ),
                'inCategoryLabel'       => __( 'All posts in category', 'laterpay' ),
                'time_passes_list'      => $this->get_time_passes_json( $time_passes_list ),
                'subscriptions_list'    => $this->get_subscriptions_json( $subscriptions_list ),
                'vouchers_list'         => wp_json_encode( $vouchers_list ),
                'sub_vouchers_list'     => wp_json_encode( $sub_vouchers_list ),
                'vouchers_statistic'    => wp_json_encode( $vouchers_statistic ),
                'l10n_print_after'      => 'lpVars.currency = JSON.parse(lpVars.currency);
                                            lpVars.time_passes_list = JSON.parse(lpVars.time_passes_list);
                                            lpVars.subscriptions_list = JSON.parse(lpVars.subscriptions_list);
                                            lpVars.vouchers_list = JSON.parse(lpVars.vouchers_list);
                                            lpVars.sub_vouchers_list = JSON.parse(lpVars.sub_vouchers_list);
                                            lpVars.vouchers_statistic = JSON.parse(lpVars.vouchers_statistic);',
                'gaData'                => array(
                    'sandbox_merchant_id' => ( ! empty( $merchant_key ) ) ? $merchant_key : '',
                ),
            )
        );
    }

    /**
     * @see LaterPay_Core_View::render_page
     */
    public function render_page() {
        $this->load_assets();
        $category_price_model          = LaterPay_Model_CategoryPriceWP::get_instance();
        $categories_with_defined_price = $category_price_model->get_categories_with_defined_price();

        $grouped_data     = [];
        $final_categories = [];

        // Go through all categories with prices and create a group based on identifier.
        foreach ( $categories_with_defined_price as $category ) {
            $grouped_data[$category->identifier][] = $category;
        }

        // Convert grouped assoc array to indexed array.
        $grouped_data = array_values( $grouped_data );

        // Loop through grouped data and create data for display.
        foreach ( $grouped_data as $key => $category_group ) {

            // Get data from array of objects using array_map as array_column introduced use of objects in PHP7.
            $cat_ids      = self::get_category_group_data( 'id', $category_group );
            $cat_titles   = self::get_category_group_data( 'category_name', $category_group );
            $category_ids = self::get_category_group_data( 'category_id', $category_group );

            $category                 = new stdClass();
            $category->id             = implode( ',', $cat_ids );
            $category->category_name  = implode( ',', $cat_titles );
            $category->category_id    = implode( ',', $category_ids );
            $category->category_price = ( ! empty( $category_group[0]->category_price ) ) ? $category_group[0]->category_price : '' ;
            $category->revenue_model  = ( ! empty( $category_group[0]->revenue_model ) ) ? $category_group[0]->revenue_model : '';
            $category->identifier     = ( ! empty( $category_group[0]->identifier ) ) ? $category_group[0]->identifier : '';

            $final_categories[$key] = $category;
        }

        // time passes and vouchers data
        $time_passes_model  = LaterPay_Model_TimePassWP::get_instance();
        $time_passes_list   = $time_passes_model->get_active_time_passes();
        $vouchers_list      = LaterPay_Helper_Voucher::get_all_time_pass_vouchers();
        $sub_vouchers_list  = LaterPay_Helper_Voucher::get_all_subscription_vouchers();
        $vouchers_statistic = LaterPay_Helper_Voucher::get_all_vouchers_statistic();

        // subscriptions data
        $subscriptions_model = LaterPay_Model_SubscriptionWP::get_instance();
        $subscriptions_list  = $subscriptions_model->get_active_subscriptions();

        // Get all post types.
        $all_post_types = get_post_types( array( ), 'objects' );

        // Get already enabled post type data.
        $enabled_post_types = get_option( 'laterpay_enabled_post_types' );

        $view_args = array(
            'pricing_obj'                        => $this,
            'admin_menu'                         => LaterPay_Helper_View::get_admin_menu(),
            'categories_with_defined_price'      => $final_categories,
            'currency'                           => LaterPay_Helper_Config::get_currency_config(),
            'plugin_is_in_live_mode'             => $this->config->get( 'is_in_live_mode' ),
            'global_default_price'               => get_option( 'laterpay_global_price' ),
            'global_default_price_revenue_model' => get_option( 'laterpay_global_price_revenue_model' ),
            'passes_list'                        => $time_passes_list,
            'vouchers_list'                      => $vouchers_list,
            'sub_vouchers_list'                  => $sub_vouchers_list,
            'vouchers_statistic'                 => $vouchers_statistic,
            'subscriptions_list'                 => $subscriptions_list,
            'hidden_post_types'                  => self::get_hidden_post_types(),
            'all_post_types'                     => $all_post_types,
            'enabled_post_types'                 => $enabled_post_types,
        );

        $this->assign( 'laterpay', $view_args );
        $this->render( 'backend/pricing' );
    }

    /**
     * Process Ajax requests from pricing tab.
     *
     * @param LaterPay_Core_Event $event
     * @throws LaterPay_Core_Exception_InvalidIncomingData
     *
     * @return void
     */
    public function process_ajax_requests( LaterPay_Core_Event $event ) {
        $event->set_result(
            array(
                'success' => false,
                'message' => __( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' ),
            )
        );

        $retrieved_nonce = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );

        if ( ! isset( $_POST['form'] ) && ! wp_verify_nonce( $retrieved_nonce, 'laterpay_form' ) ) { // WPCS: input var ok.
            // invalid request
            throw new LaterPay_Core_Exception_InvalidIncomingData( 'form' );
        }

        // save changes in submitted form
        $submitted_form_value = filter_input( INPUT_POST, 'form', FILTER_SANITIZE_STRING );
        switch ( $submitted_form_value ) {
            case 'global_price_form':
                $this->update_global_default_price( $event );
                break;

            case 'price_category_form':
                $this->set_category_default_price( $event );
                break;

            case 'price_category_form_delete':
                $this->delete_category_default_price( $event );
                break;

            case 'laterpay_get_category_prices':
                $category_ids = filter_input( INPUT_POST, 'category_ids', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
                if ( null === $category_ids || ! is_array( $category_ids ) ) {
                    $category_ids = array();
                }

                $categories = array_map( 'absint', $category_ids );

                $category_price_model = LaterPay_Model_CategoryPriceWP::get_instance();

                $event->set_result( array(
                    'success'               => true,
                    'prices'                => $this->get_category_prices( $categories ),
                    'no_category_price_set' => ( empty( $category_price_model->get_categories_with_defined_price() ) ) ? true : false,
                ));
                break;

            case 'time_pass_form_save':
                $this->time_pass_save( $event );
                break;

            case 'time_pass_delete':
                $this->time_pass_delete( $event );
                break;

            case 'subscription_form_save':
                $this->subscription_form_save( $event );
                break;

            case 'subscription_delete':
                $this->subscription_delete( $event );
                break;

            case 'generate_voucher_code':
                $this->generate_voucher_code( $event );
                break;

            case 'laterpay_get_categories_with_price':

                //Get preselected category data for display.
                $post_terms = filter_input( INPUT_POST, 'terms', FILTER_SANITIZE_STRING );

                if ( ! empty( $post_terms ) ) {
                    // return categories
                    $args = array(
                        'hide_empty' => false,
                        'taxonomy'   => 'category',
                    );

                    if ( null !== $post_terms && ! empty( $post_terms ) ) {
                        $search_categories    = explode( ',', $post_terms );
                        $sanitized_categories = array_map( 'absint', $search_categories );
                        $args['include']      = $sanitized_categories;
                    }

                    $categories = new WP_Term_Query( $args );

                    $event->set_result( array(
                        'success'    => true,
                        'categories' => $categories->terms,
                    ));
                } else {

                    //Get input for search.
                    $post_term = filter_input( INPUT_POST, 'term', FILTER_SANITIZE_STRING );

                    if ( null === $post_term ) {
                        throw new LaterPay_Core_Exception_InvalidIncomingData( 'term' );
                    }

                    // return categories that match a given search term
                    $category_price_model = LaterPay_Model_CategoryPriceWP::get_instance();
                    $args                 = array();

                    if ( ! empty( $post_term ) ) {
                        $args['name__like'] = $post_term;
                    }

                    $event->set_result( array(
                        'success'    => true,
                        'categories' => $category_price_model->get_categories_without_price_by_term( $args ),
                    ));
                }
                break;

            case 'laterpay_get_categories':
                // return categories
                $args = array(
                    'hide_empty' => false,
                    'taxonomy'     => 'category',
                );

                $post_term = filter_input( INPUT_POST, 'terms', FILTER_SANITIZE_STRING );

                if ( null !== $post_term && ! empty( $post_term ) ) {
                    $search_categories    = explode( ',', $post_term );
                    $sanitized_categories = array_map( 'absint', $search_categories );
                    $args['include']      = $sanitized_categories;
                }

                $categories = new WP_Term_Query( $args );

                $event->set_result( array(
                    'success'    => true,
                    'categories' => $categories->terms,
                ));
                break;

            case 'update_enabled_post_types':
                $this->update_enabled_post_types( $event );
                break;

            default:
                break;
        }
    }

    /**
     * Update the global price.
     * The global price is applied to every posts by default, if
     * - it is > 0 and
     * - there isn't a more specific price for a given post.
     * @param LaterPay_Core_Event $event
     * @throws LaterPay_Core_Exception_FormValidation
     *
     * @return void
     */
    protected function update_global_default_price( LaterPay_Core_Event $event ) {
        $global_price_form = new LaterPay_Form_GlobalPrice();

        if ( ! $global_price_form->is_valid( $_POST ) ) { // phpcs:ignore
            $event->set_result(
                array(
                    'success'       => false,
                    'price'         => get_option( 'laterpay_global_price' ),
                    'revenue_model' => get_option( 'laterpay_global_price_revenue_model' ),
                    'message'       => __( 'An error occurred. Incorrect data provided.', 'laterpay' ),
                )
            );
            throw new LaterPay_Core_Exception_FormValidation( get_class( $global_price_form ), $global_price_form->get_errors() );
        }

        $delocalized_global_price   = $global_price_form->get_field_value( 'laterpay_global_price' );
        $global_price_revenue_model = $global_price_form->get_field_value( 'laterpay_global_price_revenue_model' );
        $localized_global_price     = LaterPay_Helper_View::format_number( $delocalized_global_price );
        $lp_post_price_behaviour    = $global_price_form->get_field_value( 'lp_post_price_behaviour' );

        update_option( 'laterpay_global_price', $delocalized_global_price );
        update_option( 'laterpay_global_price_revenue_model', $global_price_revenue_model );
        update_option( 'laterpay_post_price_behaviour', $lp_post_price_behaviour );

        if ( 0 === $lp_post_price_behaviour ) {
            $message = esc_html__( 'All articles will be free by default; Time Passes & Subscriptions will only be
            displayed if an Individual Article Price greater than 0.00 is manually set on the Post page.', 'laterpay' );
        } elseif ( 1 === $lp_post_price_behaviour ) {
            $message = esc_html__( 'Only Time Passes & Subscriptions will be displayed in the purchase dialog.', 'laterpay' );
        } elseif ( 2 === $lp_post_price_behaviour ) {
            $message = sprintf(
                esc_html__( 'The global default price for all posts is %s %s now.', 'laterpay' ),
                $localized_global_price,
                $this->config->get( 'currency.code' )
            );
        }

        $event->set_result(
            array(
                'success'              => true,
                'price'                => number_format( $delocalized_global_price, 2, '.', '' ),
                'localized_price'      => esc_html( $localized_global_price ),
                'revenue_model'        => esc_html( $global_price_revenue_model ),
                'revenue_model_label'  => esc_html( LaterPay_Helper_Pricing::get_revenue_label( $global_price_revenue_model ) ),
                'post_price_behaviour' => intval( $lp_post_price_behaviour ),
                'message'              => esc_html( $message ),
            )
        );
    }

    /**
     * Set the category price, if a given category does not have a category price yet.
     * @param LaterPay_Core_Event $event
     * @throws LaterPay_Core_Exception_FormValidation
     *
     * @return void
     */
    protected function set_category_default_price( LaterPay_Core_Event $event ) {
        $price_category_form = new LaterPay_Form_PriceCategory();
        if ( ! $price_category_form->is_valid( $_POST ) ) { // phpcs:ignore
            $errors = $price_category_form->get_errors();
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'An error occurred. Incorrect data provided.', 'laterpay' )
                )
            );
            throw new LaterPay_Core_Exception_FormValidation( get_class( $price_category_form ), array( $errors['name'], $errors['value'] ) );
        }

        $post_category_id = $price_category_form->get_field_value( 'category_id' );
        $category         = $price_category_form->get_field_value( 'category' );

        $args = array(
            'hide_empty' => false,
            'taxonomy'   => 'category',
        );

        if ( null !== $category && ! empty( $category ) ) {
            $search_categories    = explode( ',', $category );
            $sanitized_categories = array_map( 'absint', $search_categories );
            $args['include']      = $sanitized_categories;
        }

        $categories_data = new WP_Term_Query( $args );

        $categories                   = $categories_data->terms;
        $category_price_revenue_model = $price_category_form->get_field_value( 'laterpay_category_price_revenue_model' );
        $updated_post_ids             = null;

        if ( ! $categories ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' ),
                )
            );
            return;
        }

        $cat_ids   = array_column( $categories, 'term_id' );
        $cat_names = array_column( $categories, 'name' );

        $identifier_string = implode( ',', $cat_ids );
        $category_names    = implode( ',', $cat_names );

        // If the category price is being edited remove existing data.
        if ( ! empty( $post_category_id ) ) {
            $category_ids = explode( ',', $post_category_id );
            self::delete_category_price_by_id( $category_ids );
        }

        foreach ( $categories as $term ) {

            $category_id                = $term->term_id;
            $category_price_model       = LaterPay_Model_CategoryPriceWP::get_instance();
            $delocalized_category_price = $price_category_form->get_field_value( 'price' );

            if ( empty( $category_id ) ) {
                $event->set_result(
                    array(
                        'success' => false,
                        'message' => __( 'There is no such category on this website.', 'laterpay' ),
                    )
                );
                return;
            }

            $category_price_model->set_category_price(
                $category_id,
                $delocalized_category_price,
                $category_price_revenue_model,
                0,
                $identifier_string
            );

        }

        $localized_category_price = LaterPay_Helper_View::format_number( $delocalized_category_price );
        $currency                 = $this->config->get( 'currency.code' );

        $event->set_result(
            array(
                'success'             => true,
                'category'            => $category_names,
                'price'               => number_format( $delocalized_category_price, 2, '.', '' ),
                'localized_price'     => $localized_category_price,
                'currency'            => $currency,
                'category_id'         => $identifier_string,
                'revenue_model'       => $category_price_revenue_model,
                'revenue_model_label' => LaterPay_Helper_Pricing::get_revenue_label( $category_price_revenue_model ),
                'updated_post_ids'    => $updated_post_ids,
                'message'             => sprintf(
                    esc_html__( 'All posts in category %s have a default price of %s %s now.', 'laterpay' ),
                    $category_names,
                    $localized_category_price,
                    $currency
                ),
            )
        );
    }

    /**
     * Delete the category price for a given category.
     *
     * @param LaterPay_Core_Event $event
     *
     * @throws LaterPay_Core_Exception_FormValidation
     *
     * @return void
     */
    protected function delete_category_default_price( LaterPay_Core_Event $event ) {
        $price_category_delete_form = new LaterPay_Form_PriceCategory();
        $event->set_result(
            array(
                'success' => false,
                'message' => __( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' ),
            )
        );

        if ( ! $price_category_delete_form->is_valid( $_POST ) ) { // phpcs:ignore
            throw new LaterPay_Core_Exception_FormValidation( get_class( $price_category_delete_form ), $price_category_delete_form->get_errors() );
        }

        // Get category ids for deletion.
        $category_ids = explode( ',', $price_category_delete_form->get_field_value( 'category_id' ) );

        // Delete categories.
        $success = self::delete_category_price_by_id( $category_ids );

        if ( ! $success ) {
            return;
        }

        $event->set_result(
            array(
                'success' => true,
                'message' => sprintf(
                    __( 'The default price for category %s was deleted.', 'laterpay' ),
                    $price_category_delete_form->get_field_value( 'category_name' )
                ),
            )
        );
    }

    /**
     * Process Ajax requests for prices of applied categories.
     *
     * @param array $category_ids
     *
     * @return array
     */
    protected function get_category_prices( $category_ids ) {
        return LaterPay_Helper_Pricing::get_category_price_data_by_category_ids( $category_ids );
    }

    /**
     * Render time pass HTML.
     *
     * @param array $args timepass display arguments
     * @param bool $echo  should echo.
     *
     * @return string|void
     */
    public function render_time_pass( $args = array(), $echo = false ) {
        $defaults = LaterPay_Helper_TimePass::get_default_options();
        $args     = array_merge( $defaults, $args );

        $this->assign( 'laterpay_pass', $args );
        $this->assign( 'laterpay',      array(
            'standard_currency' => $this->config->get( 'currency.code' ),
        ));

        if ( $echo ) {
            $this->render( 'backend/partials/time-pass', null, true );
        } else {
            return $this->get_text_view( 'backend/partials/time-pass' );
        }
    }

    /**
     * Save time pass
     *
     * @param LaterPay_Core_Event $event
     * @throws LaterPay_Core_Exception_FormValidation
     *
     * @return void
     */
    protected function time_pass_save( LaterPay_Core_Event $event ) {
        $save_time_pass_form = new LaterPay_Form_Pass( $_POST ); // phpcs:ignore
        $time_pass_model     = LaterPay_Model_TimePassWP::get_instance();

        $event->set_result(
            array(
                'success' => false,
                'errors'  => $save_time_pass_form->get_errors(),
                'message' => __( 'An error occurred when trying to save the time pass. Please try again.', 'laterpay' ),
            )
        );

        if ( ! $save_time_pass_form->is_valid() ) {
            throw new LaterPay_Core_Exception_FormValidation( get_class( $save_time_pass_form ), $save_time_pass_form->get_errors() );
        }

        $data = $save_time_pass_form->get_form_values( true, null, array( 'voucher_code', 'voucher_price', 'voucher_title' ) );

        // check and set revenue model
        if ( ! isset( $data['revenue_model'] ) ) {
            $data['revenue_model'] = 'ppu';
        }

        // ensure valid revenue model
        $data['revenue_model'] = LaterPay_Helper_Pricing::ensure_valid_revenue_model( $data['revenue_model'], $data['price'] );

        // update time pass data or create new time pass
        $data    = $time_pass_model->update_time_pass( $data );
        $pass_id = $data['pass_id'];

        // default vouchers data
        $vouchers_data = array();

        // set vouchers data
        $voucher_codes = $save_time_pass_form->get_field_value( 'voucher_code' );
        if ( $voucher_codes && is_array( $voucher_codes ) ) {
            $voucher_prices = $save_time_pass_form->get_field_value( 'voucher_price' );
            $voucher_titles = $save_time_pass_form->get_field_value( 'voucher_title' );
            foreach ( $voucher_codes as $idx => $code ) {
                // normalize prices and format with 2 digits in form
                $voucher_price           = isset( $voucher_prices[ $idx ] ) ? $voucher_prices[ $idx ] : 0;
                $formatted_voucher_price = number_format( LaterPay_Helper_View::normalize( $voucher_price ), 2, '.', '' );

                if ( floatval( $formatted_voucher_price ) > floatval( $data['price'] ) ) {
                    $formatted_voucher_price = $data['price'];
                }

                $vouchers_data[ $code ] = array(
                    'price' => $formatted_voucher_price,
                    'title' => isset( $voucher_titles[ $idx ] ) ? $voucher_titles[ $idx ] : '',
                );
            }
        }

        // save vouchers for this pass
        LaterPay_Helper_Voucher::save_time_pass_vouchers( $pass_id, $vouchers_data );

        $data['category_name']   = $data['access_category'];
        $hmtl_data               = $data;
        $data['price']           = number_format( $data['price'], 2, '.', '' );
        $data['localized_price'] = LaterPay_Helper_View::format_number( $data['price'] );
        $vouchers                = LaterPay_Helper_Voucher::get_time_pass_vouchers( $pass_id );

        $event->set_result(
            array(
                'success'  => true,
                'data'     => $data,
                'vouchers' => $vouchers,
                'html'     => $this->render_time_pass( $hmtl_data ),
                'message'  => __( 'Pass saved.', 'laterpay' ),
            )
        );
    }

    /**
     * Remove time pass by pass_id.
     *
     * @return void
     */
    protected function time_pass_delete( LaterPay_Core_Event $event ) {
        $time_id = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_STRING );
        if ( null !== $time_id ) {
            $time_pass_id    = sanitize_text_field( $time_id );
            $time_pass_model = LaterPay_Model_TimePassWP::get_instance();

            // remove time pass
            $time_pass_model->delete_time_pass_by_id( $time_pass_id );

            // remove vouchers
            LaterPay_Helper_Voucher::delete_time_pass_voucher_code( $time_pass_id );

            $event->set_result(
                array(
                    'success'             => true,
                    'message'             => esc_html__( 'Time pass deleted.', 'laterpay' ),
                )
            );
        } else {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'The selected pass was deleted already.', 'laterpay' ),
                )
            );
        }
    }

    /**
     * Render time pass HTML.
     *
     * @param array $args   arguments.
     * @param bool $echo    should echo.
     *
     * @return string|void
     */
    public function render_subscription( $args = array(), $echo = false ) {
        $defaults = LaterPay_Helper_Subscription::get_default_options();
        $args     = array_merge( $defaults, $args );

        $this->assign( 'laterpay_subscription', $args );
        $this->assign( 'laterpay',      array(
            'standard_currency' => $this->config->get( 'currency.code' ),
        ));

        if ( $echo ) {
            $this->render( 'backend/partials/subscription', null, true );
        } else {
            return $this->get_text_view( 'backend/partials/subscription' );
        }
    }

    /**
     * Save subscription
     *
     * @param LaterPay_Core_Event $event
     */
    protected function subscription_form_save( LaterPay_Core_Event $event ) {
        $save_subscription_form = new LaterPay_Form_Subscription( $_POST ); // phpcs:ignore
        $subscription_model     = LaterPay_Model_SubscriptionWP::get_instance();

        $event->set_result(
            array(
                'success' => false,
                'errors'  => $save_subscription_form->get_errors(),
                'message' => __( 'An error occurred when trying to save the subscription. Please try again.', 'laterpay' ),
            )
        );

        if ( ! $save_subscription_form->is_valid() ) {
            throw new LaterPay_Core_Exception_FormValidation( get_class( $save_subscription_form ), $save_subscription_form->get_errors() );
        }

        $data = $save_subscription_form->get_form_values();

        // update subscription data or create new subscriptions
        $data   = $subscription_model->update_subscription( $data );
        $sub_id = $data['id'];

        $data['category_name']   = $data['access_category'];
        $hmtl_data               = $data;
        $data['price']           = number_format( $data['price'], 2, '.', '' );
        $data['localized_price'] = LaterPay_Helper_View::format_number( $data['price'] );

        // Default vouchers data.
        $vouchers_data = array();

        // Set vouchers data.
        $voucher_codes = $save_subscription_form->get_field_value( 'voucher_code' );
        if ( $voucher_codes && is_array( $voucher_codes ) ) {
            $voucher_prices = $save_subscription_form->get_field_value( 'voucher_price' );
            $voucher_titles = $save_subscription_form->get_field_value( 'voucher_title' );
            foreach ( $voucher_codes as $idx => $code ) {
                // normalize prices and format with 2 digits in form
                $voucher_price           = isset( $voucher_prices[ $idx ] ) ? $voucher_prices[ $idx ] : 0;
                $formatted_voucher_price = number_format( LaterPay_Helper_View::normalize( $voucher_price ), 2, '.', '' );

                if ( floatval( $formatted_voucher_price ) > floatval( $data['price'] ) ) {
                    $formatted_voucher_price = $data['price'];
                }

                $vouchers_data[ $code ] = array(
                    'price' => $formatted_voucher_price,
                    'title' => isset( $voucher_titles[ $idx ] ) ? $voucher_titles[ $idx ] : '',
                );
            }
        }

        // Save vouchers for this subscription.
        LaterPay_Helper_Voucher::save_subscription_vouchers( $sub_id, $vouchers_data );
        $vouchers = LaterPay_Helper_Voucher::get_subscription_vouchers( $sub_id );

        $event->set_result(
            array(
                'success'  => true,
                'data'     => $data,
                'vouchers' => $vouchers,
                'html'     => $this->render_subscription( $hmtl_data ),
                'message'  => __( 'Subscription saved.', 'laterpay' ),
            )
        );
    }

    /**
     * Remove subscription by id.
     *
     * @param LaterPay_Core_Event $event
     */
    protected function subscription_delete( LaterPay_Core_Event $event ) {
        $subscription_id = filter_input( INPUT_POST, 'id', FILTER_SANITIZE_STRING );
        if ( null !== $subscription_id ) {
            $sub_id             = sanitize_text_field( $subscription_id );
            $subscription_model = LaterPay_Model_SubscriptionWP::get_instance();

            // Remove subscription.
            $subscription_model->delete_subscription_by_id( $sub_id );

            // Remove vouchers.
            LaterPay_Helper_Voucher::delete_subscription_voucher_code( $sub_id );

            $event->set_result(
                array(
                    'success'             => true,
                    'message'             => esc_html__( 'Subscription deleted.', 'laterpay' ),
                )
            );
        } else {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'The selected subscription was deleted already.', 'laterpay' ),
                )
            );
        }
    }

    /**
     * Get JSON array of time passes list with defaults.
     *
     * @return mixed
     */
    private function get_time_passes_json( $time_passes_list = array() ) {
        $time_passes_array = array( 0 => LaterPay_Helper_TimePass::get_default_options() );

        foreach ( $time_passes_list as $time_pass ) {
            if ( isset( $time_pass['access_category'] ) && $time_pass['access_category'] ) {
                $time_pass['category_name'] = $time_pass['access_category'];
            }
            $time_passes_array[ $time_pass['pass_id'] ] = $time_pass;
        }

        return wp_json_encode( $time_passes_array );
    }

    /**
     * Get JSON array of subscriptions list with defaults.
     *
     * @return mixed
     */
    private function get_subscriptions_json( $subscriptions_list = array() ) {
        $subscriptions_array = array( 0 => LaterPay_Helper_Subscription::get_default_options() );

        foreach ( $subscriptions_list as $subscription ) {
            if ( isset( $subscription['access_category'] ) && $subscription['access_category'] ) {
                $subscription['category_name'] = $subscription['access_category'];
            }
            $subscriptions_array[ $subscription['id'] ] = $subscription;
        }

        return wp_json_encode( $subscriptions_array );
    }

    /**
     * Get generated voucher code.
     * @param LaterPay_Core_Event $event
     * @throws LaterPay_Core_Exception_InvalidIncomingData
     *
     * @return void
     */
    private function generate_voucher_code( LaterPay_Core_Event $event ) {
        $currency = LaterPay_Helper_Config::get_currency_config();

        $event->set_result(
            array(
                'success' => false,
                'message' => __( 'Incorrect voucher price.', 'laterpay' ),
            )
        );

        $voucher_price = filter_input( INPUT_POST, 'price', FILTER_SANITIZE_STRING );

        if ( null === $voucher_price ) {
            throw new LaterPay_Core_Exception_InvalidIncomingData( 'price' );
        }

        $price = sanitize_text_field( $voucher_price );
        //validates price given for time pass before creating voucher.
        if ( ! ( $price >= $currency['ppu_min'] && $price <= $currency['sis_max'] ) && floatval( 0 ) !== floatval( $price ) ) {
            return;
        }

        // generate voucher code
        $event->set_result(
            array(
                'success' => true,
                'code'    => LaterPay_Helper_Voucher::generate_voucher_code(),
            )
        );
    }

    /**
     * Register laterpay passes custom post type.
     *
     * @param LaterPay_Core_Event $event
     */
    public function register_passes_cpt( LaterPay_Core_Event $event ) {

        $args = array(
            'labels'     => array(
                'name'          => __( 'Passes', 'laterpay' ),
                'singular_name' => __( 'Pass', 'laterpay' ),
            ),
        );

        $result = register_post_type( LaterPay_Model_TimePassWP::$timepass_post_type, $args );

        if ( is_wp_error( $result ) ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'Laterpay Passes Post type Registration issue.', 'laterpay' ),
                )
            );
        }
    }

    /**
     * Update enabled post types for LaterPay functionality.
     *
     * @param LaterPay_Core_Event $event
     *
     * @throws LaterPay_Core_Exception_FormValidation
     *
     * @return void
     */
    protected function update_enabled_post_types( LaterPay_Core_Event $event ) {
        $enabled_post_type = new LaterPay_Form_EnabledPostType();

        $event->set_result(
            array(
                'success' => false,
                'message' => esc_html__( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' ),
            )
        );

        if ( ! $enabled_post_type->is_valid( $_POST ) ) { // phpcs:ignore
            throw new LaterPay_Core_Exception_FormValidation( get_class( $enabled_post_type ), $enabled_post_type->get_errors() );
        }

        $enabled_post_types = $enabled_post_type->get_field_value( 'laterpay_enabled_post_types' );

        // Get all post types.
        $all_post_types = array_keys( get_post_types( array( ), 'objects' ) );

        // Get allowed post types.
        $allowed_post_types = array_diff( $all_post_types, self::get_hidden_post_types() );

        // If received data is not null, use valid values from received data.
        if ( null !== $enabled_post_types ) {
            $enabled_post_types = array_intersect( $allowed_post_types, $enabled_post_types );
        } else {
            $enabled_post_types = '';
        }

        // Update option for enabled post types.
        $is_updated = update_option( 'laterpay_enabled_post_types', $enabled_post_types );

        if ( ! $is_updated ) {

            // Display different error if option was not updated.
            $event->set_result(
                array(
                    'success' => false,
                    'message' => esc_html__( 'Unable to update LaterPay Enabled Post Type(s).', 'laterpay' ),
                )
            );

            return;

        }

        // Set success message.
        $event->set_result(
            array(
                'success' => true,
                'message' => esc_html__( 'Successfully Updated LaterPay Enabled Post Type(s).', 'laterpay' ),
            )
        );
    }

    public static function get_hidden_post_types() {
        // Post Types to be hidden.
        return [
            'nav_menu_item',
            'revision',
            'custom_css',
            'customize_changeset',
            'lp_passes',
            'lp_subscription',
            'oembed_cache',
        ];
    }

    /**
     * Delete pricing from given term ids.
     *
     * @param $category_ids array Array of term ids.
     *
     * @return bool
     */
    private static function delete_category_price_by_id( $category_ids ) {

        // Delete the category_price.
        $category_price_model = LaterPay_Model_CategoryPriceWP::get_instance();
        $success              = false;

        foreach ( $category_ids as $category_id ) {
            $success = $category_price_model->delete_prices_by_category_id( $category_id );
        }

        return $success;
    }

    /**
     * Get array of field passed from Category Data.
     *
     * @param $field         string Field to be returned from category.
     * @param $category_data array  Category Data.
     *
     * @return array
     */
    private static function get_category_group_data( $field, $category_data ) {

        return array_map( function( $cat ) use ( $field ) {
            return $cat->$field;
        }, $category_data );

    }
}

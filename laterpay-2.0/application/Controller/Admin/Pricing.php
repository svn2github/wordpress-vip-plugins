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
            array( 'jquery', 'laterpay-select2' ),
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
            'timesRedeemed'             => __( 'times redeemed.', 'laterpay' ),
            'payLaterToolTip'           => esc_html__( 'Pay Later allows users to gain access now by committing to pay once their invoice reaches $5 or 5€; it is available for posts with pricing between 0.05 and 5.00', 'laterpay' ),
            'payNowToolTip'             => esc_html__( 'Pay Now requires users pay for purchased content immediately; available for posts with pricing above $1.99 or 1.49€', 'laterpay' ),
        );

        // pass localized strings and variables to script
        // time pass with vouchers
        $time_passes_model   = LaterPay_Model_TimePassWP::get_instance();
        $time_passes_list    = $time_passes_model->get_active_time_passes();
        $vouchers_list       = LaterPay_Helper_Voucher::get_all_vouchers();
        $vouchers_statistic  = LaterPay_Helper_Voucher::get_all_vouchers_statistic();

        // subscriptions
        $subscriptions_model = LaterPay_Model_SubscriptionWP::get_instance();
        $subscriptions_list  = $subscriptions_model->get_active_subscriptions();

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
                'vouchers_statistic'    => wp_json_encode( $vouchers_statistic ),
                'l10n_print_after'      => 'lpVars.currency = JSON.parse(lpVars.currency);
                                            lpVars.time_passes_list = JSON.parse(lpVars.time_passes_list);
                                            lpVars.subscriptions_list = JSON.parse(lpVars.subscriptions_list);
                                            lpVars.vouchers_list = JSON.parse(lpVars.vouchers_list);
                                            lpVars.vouchers_statistic = JSON.parse(lpVars.vouchers_statistic);',
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

        // time passes and vouchers data
        $time_passes_model  = LaterPay_Model_TimePassWP::get_instance();
        $time_passes_list   = $time_passes_model->get_active_time_passes();
        $vouchers_list      = LaterPay_Helper_Voucher::get_all_vouchers();
        $vouchers_statistic = LaterPay_Helper_Voucher::get_all_vouchers_statistic();

        // subscriptions data
        $subscriptions_model = LaterPay_Model_SubscriptionWP::get_instance();
        $subscriptions_list  = $subscriptions_model->get_active_subscriptions();

        $view_args = array(
            'pricing_obj'                        => $this,
            'admin_menu'                         => LaterPay_Helper_View::get_admin_menu(),
            'categories_with_defined_price'      => $categories_with_defined_price,
            'currency'                           => LaterPay_Helper_Config::get_currency_config(),
            'plugin_is_in_live_mode'             => $this->config->get( 'is_in_live_mode' ),
            'global_default_price'               => get_option( 'laterpay_global_price' ),
            'global_default_price_revenue_model' => get_option( 'laterpay_global_price_revenue_model' ),
            'passes_list'                        => $time_passes_list,
            'vouchers_list'                      => $vouchers_list,
            'vouchers_statistic'                 => $vouchers_statistic,
            'subscriptions_list'                 => $subscriptions_list,
            'only_time_pass_purchases_allowed'   => get_option( 'laterpay_only_time_pass_purchases_allowed' ),
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

                $categories   = array_map( 'absint', $category_ids );

                $event->set_result( array(
                    'success' => true,
                    'prices'  => $this->get_category_prices( $categories ),
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
                break;

            case 'laterpay_get_categories':
                // return categories
                $args = array(
                    'hide_empty' => false,
                );
                $post_term = filter_input( INPUT_POST, 'term', FILTER_SANITIZE_STRING );
                if ( null !== $post_term && ! empty( $post_term ) ) {
                    $args['name__like'] = $post_term;
                }

                $event->set_result( array(
                    'success'    => true,
                    'categories' => get_categories( $args ),
                ));
                break;

            case 'change_purchase_mode_form':
                $this->change_purchase_mode( $event );
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

        update_option( 'laterpay_global_price', $delocalized_global_price );
        update_option( 'laterpay_global_price_revenue_model', $global_price_revenue_model );

        $message = sprintf(
            esc_html__( 'The global default price for all posts is %s %s now.', 'laterpay' ),
            $localized_global_price,
            $this->config->get( 'currency.code' )
        );

        $event->set_result(
            array(
                'success'             => true,
                'price'               => number_format( $delocalized_global_price, 2, '.', '' ),
                'localized_price'     => $localized_global_price,
                'revenue_model'       => $global_price_revenue_model,
                'revenue_model_label' => LaterPay_Helper_Pricing::get_revenue_label( $global_price_revenue_model ),
                'message'             => $message,
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

        $post_category_id               = $price_category_form->get_field_value( 'category_id' );
        $category                       = $price_category_form->get_field_value( 'category' );
        $term                           = get_term_by( 'name', $category, 'category' );
        $category_price_revenue_model   = $price_category_form->get_field_value( 'laterpay_category_price_revenue_model' );
        $updated_post_ids               = null;

        if ( ! $term ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'An error occurred when trying to save your settings. Please try again.', 'laterpay' ),
                )
            );
            return;
        }

        $category_id                = $term->term_id;
        $category_price_model       = LaterPay_Model_CategoryPriceWP::get_instance();
        $category_price_id          = $category_price_model->get_price_id_by_category_id( $post_category_id );
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

        if ( ! $post_category_id ) {
            $category_price_model->set_category_price(
                $category_id,
                $delocalized_category_price,
                $category_price_revenue_model
            );
        } else {
            $category_price_model->set_category_price(
                $category_id,
                $delocalized_category_price,
                $category_price_revenue_model,
                $category_price_id
            );
        }

        $localized_category_price = LaterPay_Helper_View::format_number( $delocalized_category_price );
        $currency                 = $this->config->get( 'currency.code' );

        $event->set_result(
            array(
                'success'             => true,
                'category'            => $category,
                'price'               => number_format( $delocalized_category_price, 2, '.', '' ),
                'localized_price'     => $localized_category_price,
                'currency'            => $currency,
                'category_id'         => $category_id,
                'revenue_model'       => $category_price_revenue_model,
                'revenue_model_label' => LaterPay_Helper_Pricing::get_revenue_label( $category_price_revenue_model ),
                'updated_post_ids'    => $updated_post_ids,
                'message'             => sprintf(
                    __( 'All posts in category %s have a default price of %s %s now.', 'laterpay' ),
                    $category,
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

        $category_id = $price_category_delete_form->get_field_value( 'category_id' );

        // delete the category_price
        $category_price_model = LaterPay_Model_CategoryPriceWP::get_instance();
        $success              = $category_price_model->delete_prices_by_category_id( $category_id );

        if ( ! $success ) {
            return;
        }

        $event->set_result(
            array(
                'success' => true,
                'message' => sprintf(
                    __( 'The default price for category %s was deleted.', 'laterpay' ),
                    $price_category_delete_form->get_field_value( 'category' )
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
                $voucher_price = isset( $voucher_prices[ $idx ] ) ? $voucher_prices[ $idx ] : 0;
                $vouchers_data[ $code ] = array(
                    'price' => number_format( LaterPay_Helper_View::normalize( $voucher_price ), 2, '.', '' ),
                    'title' => isset( $voucher_titles[ $idx ] ) ? $voucher_titles[ $idx ] : '',
                );
            }
        }

        // save vouchers for this pass
        LaterPay_Helper_Voucher::save_pass_vouchers( $pass_id, $vouchers_data );

        $data['category_name']   = get_the_category_by_ID( $data['access_category'] );
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
            LaterPay_Helper_Voucher::delete_voucher_code( $time_pass_id );

            if ( ! LaterPay_Helper_TimePass::get_time_passes_count( true ) && ! LaterPay_Helper_Subscription::get_subscriptions_count( true ) ) {

                update_option( 'laterpay_only_time_pass_purchases_allowed', 0 );
            }

            $event->set_result(
                array(
                    'success'             => true,
                    'purchase_mode_value' => get_option( 'laterpay_only_time_pass_purchases_allowed' ),
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
        $data = $subscription_model->update_subscription( $data );

        $data['category_name']   = get_the_category_by_ID( $data['access_category'] );
        $hmtl_data               = $data;
        $data['price']           = number_format( $data['price'], 2, '.', '' );
        $data['localized_price'] = LaterPay_Helper_View::format_number( $data['price'] );

        $event->set_result(
            array(
                'success'  => true,
                'data'     => $data,
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

            // remove subscription
            $subscription_model->delete_subscription_by_id( $sub_id );

            if ( ! LaterPay_Helper_TimePass::get_time_passes_count( true ) && ! LaterPay_Helper_Subscription::get_subscriptions_count( true ) ) {

                update_option( 'laterpay_only_time_pass_purchases_allowed', 0 );
            }

            $event->set_result(
                array(
                    'success'             => true,
                    'purchase_mode_value' => get_option( 'laterpay_only_time_pass_purchases_allowed' ),
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
     * @return array
     */
    private function get_time_passes_json( $time_passes_list = array() ) {
        $time_passes_array = array( 0 => LaterPay_Helper_TimePass::get_default_options() );

        foreach ( $time_passes_list as $time_pass ) {
            if ( isset( $time_pass['access_category'] ) && $time_pass['access_category'] ) {
                $time_pass['category_name'] = get_the_category_by_ID( $time_pass['access_category'] );
            }
            $time_passes_array[ $time_pass['pass_id'] ] = $time_pass;
        }

        return wp_json_encode( $time_passes_array );
    }

    /**
     * Get JSON array of subscriptions list with defaults.
     *
     * @return array
     */
    private function get_subscriptions_json( $subscriptions_list = array() ) {
        $subscriptions_array = array( 0 => LaterPay_Helper_Subscription::get_default_options() );

        foreach ( $subscriptions_list as $subscription ) {
            if ( isset( $subscription['access_category'] ) && $subscription['access_category'] ) {
                $subscription['category_name'] = get_the_category_by_ID( $subscription['access_category'] );
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
     * Switch plugin between allowing
     * (1) individual purchases and time pass purchases, or
     * (2) time pass purchases only.
     * Do nothing and render an error message, if no time pass is defined when trying to switch to time pass only mode.
     *
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    private function change_purchase_mode( LaterPay_Core_Event $event ) {

        $time_pass_purchase_mode = filter_input( INPUT_POST, 'only_time_pass_purchase_mode', FILTER_SANITIZE_STRING );

        if ( null !== $time_pass_purchase_mode ) {
            $only_time_pass = 1; // allow time pass purchases or subscription only
        } else {
            $only_time_pass = 0; // allow individual, time pass and subscription purchases
        }

        if ( 1 === $only_time_pass && ! LaterPay_Helper_TimePass::get_time_passes_count( true ) && ! LaterPay_Helper_Subscription::get_subscriptions_count( true ) ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => esc_html__( 'You have to create a time pass or subscription, before you can disable individual purchases.', 'laterpay' ),
                )
            );
            return;
        }

        update_option( 'laterpay_only_time_pass_purchases_allowed', $only_time_pass );

        $event->set_result(
            array(
                'success' => true,
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
}

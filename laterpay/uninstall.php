<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    // exit, if uninstall was not called from WordPress
    exit;
}

if ( defined( 'WPCOM_IS_VIP_ENV' ) && true === WPCOM_IS_VIP_ENV ) {
    return;
}

global $wpdb;

// Check for non-VIP env.

$table_terms_price   = $wpdb->prefix . 'laterpay_terms_price';
$table_history       = $wpdb->prefix . 'laterpay_payment_history';
$table_post_views    = $wpdb->prefix . 'laterpay_post_views';
$table_time_passes   = $wpdb->prefix . 'laterpay_passes';
$table_subscriptions = $wpdb->prefix . 'laterpay_subscriptions';

// remove custom tables
$sql = "
DROP TABLE IF EXISTS
    $table_terms_price,
    $table_history,
    $table_post_views,
    $table_time_passes,
    $table_subscriptions
;
";
$wpdb->query( $sql );

$table_postmeta      = $wpdb->postmeta;
$table_usermeta      = $wpdb->usermeta;

// remove pricing and voting data from wp_postmeta table
delete_post_meta_by_key( 'laterpay_post_prices' );
delete_post_meta_by_key( 'laterpay_post_teaser' );
delete_post_meta_by_key( 'laterpay_rating' );
delete_post_meta_by_key( 'laterpay_users_voted' );

// remove user settings from wp_usermeta table
$sql = "
    DELETE FROM
        $table_usermeta
    WHERE
        meta_key IN (
            'laterpay_preview_post_as_visitor',
            'laterpay_hide_preview_mode_pane'
        )
    ;
";
$wpdb->query( $sql );

// remove global settings from wp_options table
delete_option( 'laterpay_live_backend_api_url' );
delete_option( 'laterpay_live_dialog_api_url' );
delete_option( 'laterpay_api_merchant_backend_url' );
delete_option( 'laterpay_sandbox_backend_api_url' );
delete_option( 'laterpay_sandbox_dialog_api_url' );

delete_option( 'laterpay_sandbox_api_key' );
delete_option( 'laterpay_sandbox_merchant_id' );
delete_option( 'laterpay_live_api_key' );
delete_option( 'laterpay_live_merchant_id' );
delete_option( 'laterpay_plugin_is_in_live_mode' );
delete_option( 'laterpay_is_in_visible_test_mode' );

delete_option( 'laterpay_enabled_post_types' );

delete_option( 'laterpay_currency' );
delete_option( 'laterpay_global_price' );
delete_option( 'laterpay_global_price_revenue_model' );

delete_option( 'laterpay_access_logging_enabled' );

delete_option( 'laterpay_caching_compatibility' );

delete_option( 'laterpay_teaser_mode' );

delete_option( 'laterpay_teaser_content_word_count' );

delete_option( 'laterpay_preview_excerpt_percentage_of_content' );
delete_option( 'laterpay_preview_excerpt_word_count_min' );
delete_option( 'laterpay_preview_excerpt_word_count_max' );

delete_option( 'laterpay_unlimited_access' );

delete_option( 'laterpay_voucher_codes' );
delete_option( 'laterpay_gift_codes' );
delete_option( 'laterpay_voucher_statistic' );
delete_option( 'laterpay_gift_statistic' );
delete_option( 'laterpay_gift_codes_usages' );
delete_option( 'laterpay_debugger_enabled' );
delete_option( 'laterpay_debugger_addresses' );

delete_option( 'laterpay_purchase_button_positioned_manually' );
delete_option( 'laterpay_time_passes_positioned_manually' );

delete_option( 'laterpay_only_time_pass_purchases_allowed' );

delete_option( 'laterpay_maximum_redemptions_per_gift_code' );

delete_option( 'laterpay_api_fallback_behavior' );
delete_option( 'laterpay_api_enabled_on_homepage' );

delete_option( 'laterpay_main_color' );
delete_option( 'laterpay_hover_color' );
delete_option( 'laterpay_require_login' );
delete_option( 'laterpay_region' );
delete_option( 'laterpay_plugin_version' );
delete_option( 'laterpay_pro_merchant' );

// register LaterPay autoloader
$dir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;

if ( ! class_exists( 'LaterPay_Autoloader' ) ) {
    require_once( $dir . 'laterpay-load.php' );
}

LaterPay_AutoLoader::register_namespace( $dir . 'application', 'LaterPay' );

// remove custom capabilities
LaterPay_Helper_User::remove_custom_capabilities();

// remove all dismissed LaterPay pointers
// delete_user_meta can't remove these pointers without damaging other data
$pointers = LaterPay_Controller_Admin::get_all_pointers();

if ( ! empty( $pointers ) && is_array( $pointers ) ) {
    $replace_string = 'meta_value';

    foreach ( $pointers as $pointer ) {
        // we need to use prefix ',' before pointer names to remove them properly from string
        $replace_string = "REPLACE($replace_string, ',$pointer', '')";
    }

    $sql = "
        UPDATE
            $table_usermeta
        SET
            meta_value = $replace_string
        WHERE
            meta_key = 'dismissed_wp_pointers'
        ;
    ";

    $wpdb->query( $sql );
}

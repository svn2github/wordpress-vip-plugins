<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?>

<?php

$period = LaterPay_Helper_TimePass::get_period_options( $laterpay_subscription['period'] );
if ( $laterpay_subscription['duration'] > 1 ) {
    $period = LaterPay_Helper_TimePass::get_period_options( $laterpay_subscription['period'], true );
}

$price = LaterPay_Helper_View::format_number( $laterpay_subscription['price'] );

$access_type = LaterPay_Helper_TimePass::get_access_options( $laterpay_subscription['access_to'] );
$access_dest = __( 'on this website', 'laterpay' );
$category = get_category( $laterpay_subscription['access_category'] );
if ( ! is_wp_error( $category ) && ! empty( $category ) && 0 !== intval( $laterpay_subscription['access_to'] ) ) {
    $access_dest = $category->name;
}
?>

<div class="lp_js_subscription lp_time-pass lp_time-pass-<?php echo esc_attr( $laterpay_subscription['id'] ); ?>" data-sub-id="<?php echo esc_attr( $laterpay_subscription['id'] ); ?>">

    <section class="lp_time-pass__front">
        <h4 class="lp_js_subscriptionPreviewTitle lp_time-pass__title"><?php echo esc_html( $laterpay_subscription['title'] ); ?></h4>
        <p class="lp_js_subscriptionPreviewDescription lp_time-pass__description"><?php echo esc_html( $laterpay_subscription['description'] ); ?></p>
        <div class="lp_time-pass__actions">
            <a href="#" class="lp_js_doPurchase lp_js_purchaseLink lp_purchase-button"
               title="<?php esc_attr_e( 'Buy now with LaterPay', 'laterpay' ); ?>" data-icon="b"
               data-laterpay="<?php echo ( isset( $laterpay_subscription['url'] ) ? esc_url( $laterpay_subscription['url'] ) : '' ); ?>"
               data-preview-as-visitor="<?php echo ( isset( $laterpay_subscription['preview_post_as_visitor'] ) ? esc_attr( $laterpay_subscription['preview_post_as_visitor'] ) : '' ); ?>"><?php printf( '%s<small class="lp_purchase-link__currency">%s</small>', esc_html( LaterPay_Helper_View::format_number( $laterpay_subscription['price'] ) ), esc_html( $laterpay['standard_currency'] ) ); ?></a>
            <a href="#" class="lp_js_flipSubscription lp_time-pass__terms"><?php esc_html_e( 'Terms', 'laterpay' ); ?></a>
        </div>
    </section>

    <section class="lp_time-pass__back">
        <a href="#" class="lp_js_flipSubscription lp_time-pass__front-side-link"><?php esc_html_e( 'Back', 'laterpay' ); ?></a>
        <table class="lp_time-pass__conditions">
            <tbody>
            <tr>
                <th class="lp_time-pass__condition-title"><?php esc_html_e( 'Validity', 'laterpay' ); ?></th>
                <td class="lp_time-pass__condition-value">
                    <span class="lp_js_subscriptionPreviewValidity"><?php echo esc_html( $laterpay_subscription['duration'] . ' ' . $period ); ?></span>
                </td>
            </tr>
            <tr>
                <th class="lp_time-pass__condition-title"><?php esc_html_e( 'Access to', 'laterpay' ); ?></th>
                <td class="lp_time-pass__condition-value">
                    <span class="lp_js_subscriptionPreviewAccess"><?php echo esc_html( $access_type . ' ' . $access_dest ); ?></span>
                </td>
            </tr>
            <tr>
                <th class="lp_time-pass__condition-title"><?php esc_html_e( 'Renewal', 'laterpay' ); ?></th>
                <td class="lp_time-pass__condition-value">
                    <span class="lp_js_subscriptionPreviewRenewal"><?php printf( esc_html__( 'After %s %s', 'laterpay' ), esc_html( $laterpay_subscription['duration'] ), esc_html( $period ) ); ?></span>
                </td>
            </tr>
            <tr>
                <th class="lp_time-pass__condition-title"><?php esc_html_e( 'Price', 'laterpay' ); ?></th>
                <td class="lp_time-pass__condition-value">
                    <span class="lp_js_subscriptionPreviewPrice"><?php echo esc_html( $price . ' ' . $laterpay['standard_currency'] ); ?></span>
                </td>
            </tr>
            <tr>
                <th class="lp_time-pass__condition-title"><?php esc_html_e( 'Cancellation', 'laterpay' ); ?></th>
                <td class="lp_time-pass__condition-value">
                    <?php esc_html_e( 'Cancellable anytime', 'laterpay' ); ?>
                </td>
            </tr>
            </tbody>
        </table>
    </section>

</div>

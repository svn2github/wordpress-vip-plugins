<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?>

<?php
    $title = sprintf(
        '%s<small class="lp_purchase-link__currency">%s</small>',
        LaterPay_Helper_View::format_number( $laterpay_pass['price'] ),
        $laterpay['standard_currency']
    );

    $period = LaterPay_Helper_TimePass::get_period_options( $laterpay_pass['period'] );
    if ( $laterpay_pass['duration'] > 1 ) {
        $period = LaterPay_Helper_TimePass::get_period_options( $laterpay_pass['period'], true );
    }

    $price = LaterPay_Helper_View::format_number( $laterpay_pass['price'] );

    $access_type = LaterPay_Helper_TimePass::get_access_options( $laterpay_pass['access_to'] );
    $access_dest = __( 'on this website', 'laterpay' );
    $category = get_category( $laterpay_pass['access_category'] );
    if ( ! is_wp_error( $category ) && ! empty( $category ) && 0 !== $laterpay_pass['access_to'] ) {
        $access_dest = $category->name;
    }
?>

<div class="lp_js_timePass lp_time-pass lp_time-pass-<?php echo esc_attr( $laterpay_pass['pass_id'] ); ?>" data-pass-id="<?php echo esc_attr( $laterpay_pass['pass_id'] ); ?>">

    <section class="lp_time-pass__front">
        <h4 class="lp_js_timePassPreviewTitle lp_time-pass__title"><?php echo esc_html( $laterpay_pass['title'] ); ?></h4>
        <p class="lp_js_timePassPreviewDescription lp_time-pass__description"><?php echo esc_html( $laterpay_pass['description'] ); ?></p>
        <div class="lp_time-pass__actions">
            <a href="#" class="lp_js_doPurchase lp_js_purchaseLink lp_purchase-button"
               title="<?php esc_attr_e( 'Buy now with LaterPay', 'laterpay' ); ?>" data-icon="b"
               data-laterpay="<?php echo ( isset( $laterpay_pass['url'] ) ? esc_url( $laterpay_pass['url'] ) : '' ); ?>"
               data-preview-as-visitor="<?php echo ( isset( $laterpay_pass['preview_post_as_visitor'] ) ? esc_attr( $laterpay_pass['preview_post_as_visitor'] ) : '' ); ?>"><?php
                /* translators: %1$s formatted price, %2$s currency tpye */
                printf( '%1$s<small class="lp_purchase-link__currency">%2$s</small>', esc_html( LaterPay_Helper_View::format_number( $laterpay_pass['price'] ) ), esc_html( $laterpay['standard_currency'] ) ); ?>
            </a>
            <a href="#" class="lp_js_flipTimePass lp_time-pass__terms"><?php esc_html_e( 'Terms', 'laterpay' ); ?></a>
        </div>
    </section>

    <section class="lp_time-pass__back">
        <a href="#" class="lp_js_flipTimePass lp_time-pass__front-side-link"><?php esc_html_e( 'Back', 'laterpay' ); ?></a>
        <table class="lp_time-pass__conditions">
            <tbody>
                <tr>
                    <th class="lp_time-pass__condition-title"><?php esc_html_e( 'Validity', 'laterpay' ); ?></th>
                    <td class="lp_time-pass__condition-value">
                        <span class="lp_js_timePassPreviewValidity"><?php echo esc_html( $laterpay_pass['duration'] . ' ' . $period ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th class="lp_time-pass__condition-title"><?php esc_html_e( 'Access to', 'laterpay' ); ?></th>
                    <td class="lp_time-pass__condition-value">
                        <span class="lp_js_timePassPreviewAccess"><?php echo esc_html( $access_type . ' ' . $access_dest ); ?></span>
                    </td>
                </tr>
                <tr>
                    <th class="lp_time-pass__condition-title"><?php esc_html_e( 'Renewal', 'laterpay' ); ?></th>
                    <td class="lp_time-pass__condition-value">
                        <?php esc_html_e( 'No automatic renewal', 'laterpay' ); ?>
                    </td>
                </tr>
                <tr>
                    <th class="lp_time-pass__condition-title"><?php esc_html_e( 'Price', 'laterpay' ); ?></th>
                    <td class="lp_time-pass__condition-value">
                        <span class="lp_js_timePassPreviewPrice"><?php echo esc_html( $price . ' ' . $laterpay['standard_currency'] ); ?></span>
                    </td>
                </tr>
            </tbody>
        </table>
    </section>

</div>

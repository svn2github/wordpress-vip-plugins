<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?>

<?php
    $gift_pass = $laterpay_gift['gift_pass'];

    $title = sprintf(
        /* translators: %1$s formatted price, %2$s currency tpye */
        '%1$s<small class="lp_purchase-link__currency">%2$s</small>',
        LaterPay_Helper_View::format_number( $gift_pass['price'] ),
        $laterpay['standard_currency']
    );

    $period = LaterPay_Helper_TimePass::get_period_options( $gift_pass['period'] );
    if ( $gift_pass['duration'] > 1 ) {
        $period = LaterPay_Helper_TimePass::get_period_options( $gift_pass['period'], true );
    }

    $price = LaterPay_Helper_View::format_number( $gift_pass['price'] );

    $access_type = LaterPay_Helper_TimePass::get_access_options( $gift_pass['access_to'] );
    $access_dest = __( 'on this website', 'laterpay' );
    $category    = get_category( $gift_pass['access_category'] );

    if ( 0 !== absint( $gift_pass['access_to'] ) ) {
        $access_dest = $category->name;
    }
?>

<div class="lp_js_giftCard lp_gift-card lp_gift-card-<?php echo esc_attr( $gift_pass['pass_id'] ); ?>">
    <h4 class="lp_gift-card__title"><?php echo esc_html( $gift_pass['title'] ); ?></h4>
    <p class="lp_gift-card__description"><?php echo wp_kses_post( $gift_pass['description'] ); ?></p>
    <table class="lp_gift-card___conditions">
        <tr>
            <th class="lp_gift-card___conditions-title"><?php esc_html_e( 'Validity', 'laterpay' ); ?></th>
            <td class="lp_gift-card___conditions-value">
                <?php echo esc_html( $gift_pass['duration'] . ' ' . $period ); ?>
            </td>
        </tr>
        <tr>
            <th class="lp_gift-card___conditions-title"><?php esc_html_e( 'Access to', 'laterpay' ); ?></th>
            <td class="lp_gift-card___conditions-value">
                <?php echo esc_html( $access_type . ' ' . $access_dest ); ?>
            </td>
        </tr>
        <tr>
            <th class="lp_gift-card___conditions-title"><?php esc_html_e( 'Renewal', 'laterpay'  ); ?></th>
            <td class="lp_gift-card___conditions-value">
                <?php esc_html_e( 'No automatic renewal', 'laterpay' ); ?>
            </td>
        </tr>
    </table>
    <?php if ( $laterpay_gift['show_redeem'] ) : ?>
        <?php
            $this->render_redeem_form();
        ?>
    <?php else : ?>
        <div class="lp_js_giftCardActionsPlaceholder_<?php echo esc_attr( $gift_pass['pass_id'] ); ?>"></div>
    <?php endif; ?>
</div>

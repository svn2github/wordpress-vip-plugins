<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?>

<?php
    $pass  = $laterpay['pass'];
    $title = sprintf(
        '%s<small class="lp_purchase-link__currency">%s</small>',
        LaterPay_Helper_View::format_number( $pass['price'] ),
        $laterpay['standard_currency']
    );
?>
<div class="lp_gift-card__actions">
    <?php if ( $laterpay['has_access'] ) : ?>
        <?php esc_html_e( 'Gift Code', 'laterpay' ); ?>
        <span class="lp_voucher__code"><?php echo esc_html( $laterpay['gift_code'] ); ?></span><br>
        <!--
        <?php esc_html_e( 'Redeem at', 'laterpay' ); ?>
        <a href="<?php echo esc_url( $laterpay['landing_page'] ); ?>"><?php echo esc_html( $laterpay['landing_page'] ); ?></a>
        -->
    <?php else : ?>
        <a href="#" class="lp_js_doPurchase lp_purchase-button" title="<?php esc_attr_e( 'Buy now with LaterPay', 'laterpay' ); ?>"
           data-icon="b" data-laterpay="<?php echo esc_attr( $pass['url'] ); ?>"
           data-preview-as-visitor="<?php echo esc_attr( $laterpay['preview_post_as_visitor'] ); ?>">
            <?php
                echo wp_kses( $title,
                    [
                        'small' =>
                            [
                                'class' => [],
                            ],
                    ]
                );
            ?>
        </a>
    <?php endif; ?>
</div>

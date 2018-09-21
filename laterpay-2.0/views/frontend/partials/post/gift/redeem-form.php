<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?>

<div id="lp_js_giftCardWrapper" class="lp_js_giftCodeWrapper lp_js_dataDeferExecution lp_redeem-code__wrapper lp_clearfix">
    <input type="text" name="gift_code" class="lp_js_giftCardCodeInput lp_redeem-code__value" maxlength="6">
    <p class="lp_redeem-code__input-hint"><?php esc_html_e( 'Code', 'laterpay' ); ?></p>
    <a href="#" class="lp_js_giftCardRedeemButton lp_redeem-code__button lp_button"><?php esc_html_e( 'Redeem', 'laterpay' ); ?></a>
</div>

<a href="#" id="fakebtn" class="lp_js_doPurchase" style="display:none;" data-laterpay="" data-preview-as-visitor="<?php echo esc_attr( $laterpay['preview_post_as_visitor'] ); ?>"></a>

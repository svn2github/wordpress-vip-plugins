<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?>

<div class="lp_paid-content">
    <div class="lp_full-content">
        <?php echo wp_kses_post( $overlay['overlay_content'] ); ?>
        <br>
        <?php esc_html_e( 'Thanks for reading this short excerpt from the paid post! Fancy buying it to read all of it?', 'laterpay' ); ?>
    </div>

    <?php
        $overlay_data = $overlay['data'];
        $input_id = 1;
    ?>
    <div class="lp_js_purchaseOverlay lp_purchase-overlay">
        <div class="lp_purchase-overlay__wrapper">
            <div class="lp_purchase-overlay__form">
                <section class="lp_purchase-overlay__header">
                    <?php echo esc_html( $overlay['title'] ); ?>
                </section>
                <section class="lp_purchase-overlay__body">
                    <div class="lp_purchase-overlay__settings">
                        <?php if ( isset( $overlay_data['article'] ) && floatval( 0.00 ) !== floatval( $overlay_data['article']['price'] ) ) : ?>
                        <div class="lp_purchase-overlay-option<?php if ( empty( $overlay_data['subscriptions']) && empty( $overlay_data['timepasses'] )):?> lp_purchase-overlay-option-single<?php endif;?>"
                             data-revenue="<?php echo esc_attr( $overlay_data['article']['revenue'] );?>">
                            <div class="lp_purchase-overlay-option__button">
                                <input id="lp_purchaseOverlayOptionInput<?php echo esc_attr( $input_id ); ?>" type="radio"
                                       class="lp_purchase-overlay-option__input" value="<?php echo esc_url( $overlay_data['article']['url'] ); ?>"
                                       name="lp_purchase-overlay-option" checked>
                                <label for="lp_purchaseOverlayOptionInput<?php echo esc_attr( $input_id++ ); ?>" class="lp_purchase-overlay-option__label"></label>
                            </div>
                            <div class="lp_purchase-overlay-option__name">
                                <div class="lp_purchase-overlay-option__title">
                                    <?php esc_html_e( 'This article', 'laterpay' ); ?>
                                </div>
                                <div class="lp_purchase-overlay-option__description">
                                    <?php echo esc_html( $overlay_data['article']['title'] ); ?>
                                </div>
                            </div>
                            <div class="lp_purchase-overlay-option__cost">
                                <div class="lp_purchase-overlay-option__price">
                                    <?php echo esc_html( $overlay_data['article']['price'] ); ?>
                                </div>
                                <div class="lp_purchase-overlay-option__currency">
                                    <?php echo esc_html( $overlay['currency'] ); ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ( isset( $overlay_data['timepasses'] ) ) : ?>
                            <?php
                            $individual_timepass = ( !isset( $overlay_data['article'] ) && empty( $overlay_data['subscriptions'] ) && count( $overlay_data['timepasses'] ) === 1 );
                            $tp_index            = 0; ?>
                            <?php foreach ( $overlay_data['timepasses'] as $timepass ) : ?>
                                <div class="lp_purchase-overlay-option <?php if ( $individual_timepass ): ?> lp_purchase-overlay-option-single<?php endif; ?> lp_js_timePass"
                                     data-pass-id="<?php echo esc_attr( $timepass['id'] ); ?>"
                                     data-revenue="<?php echo esc_attr( $timepass['revenue'] );?>">
                                    <div class="lp_purchase-overlay-option__button">
                                        <input id="lp_purchaseOverlayOptionInput<?php echo esc_attr( $input_id ); ?>" type="radio"
                                               class="lp_purchase-overlay-option__input" value="<?php echo esc_url( $timepass['url'] ); ?>"
                                               name="lp_purchase-overlay-option" <?php if ( $individual_timepass || ( 0 === $tp_index && !isset( $overlay_data['article'] ) ) ): ?> checked <?php endif; ?> >
                                        <label for="lp_purchaseOverlayOptionInput<?php echo esc_html( $input_id++ ); ?>" class="lp_purchase-overlay-option__label"></label>
                                    </div>
                                    <div class="lp_purchase-overlay-option__name">
                                        <div class="lp_purchase-overlay-option__title">
                                            <?php echo esc_html( $timepass['title'] ); ?>
                                        </div>
                                        <div class="lp_purchase-overlay-option__description">
                                            <?php echo wp_kses_post( $timepass['description'] ); ?>
                                        </div>
                                    </div>
                                    <div class="lp_purchase-overlay-option__cost">
                                        <div class="lp_purchase-overlay-option__price lp_js_timePassPrice">
                                            <?php echo esc_html( $timepass['price'] ); ?>
                                        </div>
                                        <div class="lp_purchase-overlay-option__currency">
                                            <?php echo esc_html( $overlay['currency'] ); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php $tp_index++; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if ( isset( $overlay_data['subscriptions'] ) ) : ?>
                            <?php
                            $only_subscription       = ( ! isset( $overlay_data['article'] ) && empty( $overlay_data['timepasses'] ) );
                            $individual_subscription = ( $only_subscription  && count( $overlay_data['subscriptions'] ) === 1 );
                            $sp_index                = 0;
                            ?>
                            <?php foreach ( $overlay_data['subscriptions'] as $subscription ) : ?>
                                <div class="lp_purchase-overlay-option <?php if ( $individual_subscription ): ?> lp_purchase-overlay-option-single<?php endif; ?> lp_js_subscription" data-sub-id="<?php echo esc_attr( $subscription['id'] ); ?>" data-revenue="<?php echo esc_attr( $subscription['revenue'] );?>">
                                    <div class="lp_purchase-overlay-option__button">
                                        <input id="lp_purchaseOverlayOptionInput<?php echo esc_attr( $input_id ); ?>" type="radio"
                                               class="lp_purchase-overlay-option__input" value="<?php echo esc_url( $subscription['url'] ); ?>" name="lp_purchase-overlay-option" <?php if ( $individual_subscription || ( 0 === $sp_index && $only_subscription ) ): ?> checked <?php endif; ?>>
                                        <label for="lp_purchaseOverlayOptionInput<?php echo esc_attr( $input_id++ ); ?>" class="lp_purchase-overlay-option__label"></label>
                                    </div>
                                    <div class="lp_purchase-overlay-option__name">
                                        <div class="lp_purchase-overlay-option__title">
                                            <?php echo esc_html( $subscription['title'] ); ?>
                                        </div>
                                        <div class="lp_purchase-overlay-option__description">
                                            <?php echo wp_kses_post( $subscription['description'] ); ?>
                                        </div>
                                    </div>
                                    <div class="lp_purchase-overlay-option__cost">
                                        <div class="lp_purchase-overlay-option__price">
                                            <?php echo esc_html( $subscription['price'] ); ?>
                                        </div>
                                        <div class="lp_purchase-overlay-option__currency">
                                            <?php echo esc_html( $overlay['currency'] ); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php $sp_index++; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="lp_purchase-overlay__voucher lp_hidden">
                        <div>
                            <input type="text" class="lp_purchase-overlay__voucher-input lp_js_voucherCodeInput" placeholder="<?php esc_attr_e('Enter Voucher Code', 'laterpay' ); ?>">
                        </div>
                            <div class="lp_purchase-overlay__message-container lp_js_purchaseOverlayMessageContainer"></div>
                    </div>
                    <div class="lp_purchase-overlay__buttons">
                        <div>
                            <a class="lp_js_overlayPurchase lp_purchase-overlay__submit" data-purchase-action="buy"
                               data-preview-post-as-visitor="<?php echo esc_attr( $overlay['is_preview'] ); ?>" href="#">
                                <span data-icon="b"></span>
                                <span data-buy-label="true" class="lp_purchase-overlay__submit-text"><?php echo esc_html( $overlay['submit_text'] ); ?></span>
                                <span data-voucher-label="true" class="lp_hidden"><?php esc_html_e( 'Redeem Voucher Code', 'laterpay' ); ?></span>
                            </a>
                        </div>
                        <div class="lp_purchase-overlay__notification">
                            <div class="lp_js_notificationButtons">
                                <a class="lp_bought_notification" href="<?php echo esc_url( $overlay['identify_url'] ); ?>"><?php echo esc_html( $overlay['notification_text'] ); ?></a> | <a href="#" class="lp_js_redeemVoucher"><?php esc_html_e( 'Redeem voucher', 'laterpay' ); ?></a>
                            </div>
                            <div class="lp_js_notificationCancel lp_hidden">
                                <a href="#" class="lp_js_voucherCancel"><?php esc_html_e( 'Cancel', 'laterpay' ); ?></a>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="lp_purchase-overlay__footer" <?php if ( $overlay['footer'] !== '1' ) { echo 'style="display:none;"'; } ?>>
                    <ul class="lp_purchase-overlay-payments-list">
                        <?php foreach ( $overlay['icons'] as $icon ) : ?>
                            <li class="lp_purchase-overlay-payments-item">
                                <i class="lp_purchase-overlay-icon lp_purchase-overlay-icon-<?php echo esc_attr( $icon ); ?>"></i>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </div>
            <div class="lp_purchase-overlay__copy">
                <?php esc_html_e( 'Powered by', 'laterpay' ); ?>
                <span data-icon="a"></span>
            </div>
        </div>
    </div>
</div>

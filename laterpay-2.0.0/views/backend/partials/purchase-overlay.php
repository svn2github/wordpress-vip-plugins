<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?>

<div class="lp_js_purchaseOverlay lp_purchase-overlay">
    <div class="lp_purchase-overlay__wrapper">
        <div class="lp_purchase-overlay__form">
            <section class="lp_purchase-overlay__header">
                <?php echo esc_html( $overlay['header_title'] ); ?>
            </section>
            <section class="lp_purchase-overlay__body">
                <div class="lp_purchase-overlay__settings">
                    <div class="lp_purchase-overlay-option">
                        <div class="lp_purchase-overlay-option__button">
                            <input id="lp_purchaseOverlayOptionInput1" type="radio" class="lp_purchase-overlay-option__input" name="lp_purchase-overlay-option" value="1" checked disabled>
                            <label for="lp_purchaseOverlayOptionInput1" class="lp_purchase-overlay-option__label"></label>
                        </div>
                        <div class="lp_purchase-overlay-option__name">
                            <div class="lp_purchase-overlay-option__title">
                                <?php esc_html_e( 'This article', 'laterpay' ); ?>
                            </div>
                            <div class="lp_purchase-overlay-option__description">
                                <?php esc_html_e( 'An Amazing Article', 'laterpay' ); ?>
                            </div>
                        </div>
                        <div class="lp_purchase-overlay-option__cost">
                            <div class="lp_purchase-overlay-option__price">0.19</div>
                            <div class="lp_purchase-overlay-option__currency"><?php echo esc_html( $overlay['currency'] ); ?></div>
                        </div>
                    </div>
                    <div class="lp_purchase-overlay-option">
                        <div class="lp_purchase-overlay-option__button">
                            <input id="lp_purchaseOverlayOptionInput2" type="radio" class="lp_purchase-overlay-option__input" name="lp_purchase-overlay-option" value="2" disabled>
                            <label for="lp_purchaseOverlayOptionInput2" class="lp_purchase-overlay-option__label"></label>
                        </div>
                        <div class="lp_purchase-overlay-option__name">
                            <div class="lp_purchase-overlay-option__title">
                                <?php esc_html_e( 'Week Pass', 'laterpay' ); ?>
                            </div>
                            <div class="lp_purchase-overlay-option__description">
                                <?php esc_html_e( '7 days access to all paid content on this website (no subscription)', 'laterpay' ); ?>
                            </div>
                        </div>
                        <div class="lp_purchase-overlay-option__cost">
                            <div class="lp_purchase-overlay-option__price">1.49</div>
                            <div class="lp_purchase-overlay-option__currency"><?php echo esc_html( $overlay['currency'] ); ?></div>
                        </div>
                    </div>
                    <div class="lp_purchase-overlay-option">
                        <div class="lp_purchase-overlay-option__button">
                            <input id="lp_purchaseOverlayOptionInput3" type="radio" class="lp_purchase-overlay-option__input" name="lp_purchase-overlay-option" value="3" disabled>
                            <label for="lp_purchaseOverlayOptionInput3" class="lp_purchase-overlay-option__label"></label>
                        </div>
                        <div class="lp_purchase-overlay-option__name">
                            <div class="lp_purchase-overlay-option__title">
                                <?php esc_html_e( 'Month subscription', 'laterpay' ); ?>
                            </div>
                            <div class="lp_purchase-overlay-option__description">
                                <?php esc_html_e( '30 days access to all paid content (cancellable anytime)', 'laterpay' ); ?>
                            </div>
                        </div>
                        <div class="lp_purchase-overlay-option__cost">
                            <div class="lp_purchase-overlay-option__price">149.49</div>
                            <div class="lp_purchase-overlay-option__currency"><?php echo esc_html( $overlay['currency'] ); ?></div>
                        </div>
                    </div>
                </div>
                <div class="lp_purchase-overlay__buttons">
                    <a class="lp_purchase-overlay__submit" href="#"><span data-icon="b"></span><?php esc_html_e( 'Buy now, pay later', 'laterpay' ); ?></a>
                    <div class="lp_purchase-overlay__notification">
                        <a href="#"><?php esc_html_e( 'I already bought this', 'laterpay' ); ?></a> | <a href="#"><?php esc_html_e( 'Redeem voucher', 'laterpay' ); ?></a>
                    </div>
                </div>
            </section>
            <section class="lp_purchase-overlay__footer" <?php if ( $overlay['show_footer'] !== '1' ) echo 'style="display:none;"'; ?>>
                <ul class="lp_purchase-overlay-payments-list">
                    <?php foreach ( $overlay['icons'] as $icon ) : ?>
                    <li class="lp_purchase-overlay-payments-item">
                        <i class="lp_purchase-overlay-icon lp_purchase-overlay-icon-<?php echo esc_html( $icon ); ?>"></i>
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

<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?>

<script>
    var lpVars = window.lpVars || {};
    lpVars.postId = <?php echo esc_html( $laterpay['post_id'] ); ?>;
    lpVars.limits = <?php echo wp_json_encode( $laterpay['price_ranges'] ); ?>;
</script>
<div class="lp_clearfix">
    <?php if ( ! get_option( 'laterpay_plugin_is_in_live_mode' ) ) : ?>
    <div class="lp_tooltip" data-tooltip="<?php echo esc_attr( __( 'Click here to finish your account set up', 'laterpay' ) ); ?>">
            <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'laterpay-account-tab' ), admin_url( 'admin.php' ) ) ); ?>"
               class="lp_plugin-mode-indicator"
               data-icon="h">
                <h2 class="lp_plugin-mode-indicator__title"><?php esc_html_e( 'Test mode', 'laterpay' ); ?></h2>
                <span class="lp_plugin-mode-indicator__text">
                    <?php
                    /* translators: %1$s info text1, %2$s info text2*/
                    printf( '%1$s<i> %2$s</i>', esc_html__( 'Earn money in', 'laterpay' ), esc_html__( 'live mode', 'laterpay' ) );
                    ?>
                </span>
            </a>
    </div>
    <?php endif; ?>
    <?php if( ! get_option( 'laterpay_is_in_visible_test_mode' ) ): ?>
    <p class="account_setup_warning" data-icon="n">
        <?php
        printf( '%1s <a href="%2$s">%3$s</a> %4$s',
            esc_html__( 'Your LaterPay Plugin is currently invisible to viewers. Click', 'laterpay' ),
            esc_url( add_query_arg( array( 'page' => 'laterpay-account-tab' ), admin_url( 'admin.php' ) ) ),
            esc_html__( 'here', 'laterpay' ),
            esc_html__( 'to toggle visibility.', 'laterpay' ) );
        ?>
    </p>
    <?php endif; ?>
    <div class="lp_layout lp_mt+ lp_mb+">
        <div id="lp_js_postPriceRevenueModel" class="lp_layout__item lp_3/8">
            <label class="lp_badge lp_badge--revenue-model lp_tooltip
                    <?php if ( $laterpay['post_revenue_model'] === 'ppu' ) { echo 'lp_is-selected'; } ?>
                    <?php if ( in_array( $laterpay['post_price_type'], array( LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_PRICE, LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ), true ) ) : ?>
                        <?php if ( $laterpay['price'] > $laterpay['currency']['ppu_max'] ) { echo 'lp_is-disabled'; } ?>
                    <?php else : ?>
                        <?php if ( $laterpay['post_revenue_model'] !== 'ppu' || $laterpay['price'] > $laterpay['currency']['ppu_max'] ) { echo 'lp_is-disabled'; } ?>
                    <?php endif; ?>"
                    data-tooltip="<?php echo esc_attr( __( 'Pay Later allows users to gain access now by committing to pay once their invoice reaches $5 or 5€; it is available for posts with pricing between 0.05 and 5.00', 'laterpay' ) ); ?>">
                <input type="radio"
                    name="post_revenue_model"
                    value="ppu"
                    <?php if ( $laterpay['post_revenue_model'] === 'ppu' ) { echo 'checked'; } ?>><?php esc_html_e( 'Pay Later', 'laterpay' ); ?>
            </label>
            <label class="lp_badge lp_badge--revenue-model lp_tooltip lp_mt-
                    <?php if ( $laterpay['post_revenue_model'] === 'sis' ) { echo 'lp_is-selected'; } ?>
                    <?php if ( in_array( $laterpay['post_price_type'], array( LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_PRICE, LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ), true ) ) : ?>
                        <?php if ( $laterpay['price'] < $laterpay['currency']['sis_min'] ) { echo 'lp_is-disabled'; } ?>
                    <?php else : ?>
                        <?php if ( $laterpay['post_revenue_model'] !== 'sis' ) { echo 'lp_is-disabled'; } ?>
                    <?php endif; ?>"
                    data-tooltip="<?php echo esc_attr( __( 'Pay Now requires users pay for purchased content immediately; available for posts with pricing above $1.99 or 1.49€', 'laterpay' ) ); ?>">
                <input type="radio"
                    name="post_revenue_model"
                    value="sis"
                    <?php if ( $laterpay['post_revenue_model'] === 'sis' ) { echo 'checked'; } ?>><?php esc_html_e( 'Pay Now', 'laterpay' ); ?>
            </label>
        </div><!-- layout works with display:inline-block; comments are there to suppress spaces
     --><div class="lp_layout__item lp_7/16">
            <input type="text"
                    id="lp_js_postPriceInput"
                    class="lp_post-price-input lp_input lp_ml-"
                    name="post-price"
                    value="<?php echo esc_attr( LaterPay_Helper_View::format_number( $laterpay['price'] ) ); ?>"
                    placeholder="<?php esc_attr_e( '0.00', 'laterpay' ); ?>"
                    <?php if ( $laterpay['post_price_type'] !== LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_PRICE ) { echo 'disabled'; } ?>>
        </div><!-- layout works with display:inline-block; comments are there to suppress spaces
     --><div class="lp_layout__item lp_3/16">
            <div class="lp_currency"><?php esc_html_e( $laterpay['currency']['code'] ); ?></div>
        </div>
    </div>

    <input type="hidden" name="post_price_type" id="lp_js_postPriceTypeInput" value="<?php echo esc_attr( $laterpay['post_price_type'] ); ?>">
</div>


<div id="lp_js_priceType" class="lp_price-type<?php if ( in_array( $laterpay['post_price_type'], array( LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE, LaterPay_Helper_Pricing::TYPE_CATEGORY_DEFAULT_PRICE ), true ) ) { echo ' lp_is-expanded'; } ?>">
    <ul id="lp_js_priceTypeButtonGroup" class="lp_price-type__list lp_clearfix">
        <li class="lp_price-type__item <?php if ( in_array( $laterpay['post_price_type'], array( LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_PRICE, LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ), true ) ) { echo 'lp_is-selected'; } ?>">
            <a href="#"
                id="lp_js_useIndividualPrice"
                class="lp_js_priceTypeButton lp_price-type__link"><?php esc_html_e( 'Individual Price', 'laterpay' ); ?></a>
        </li>
        <li class="lp_price-type__item <?php if ( $laterpay['post_price_type'] === LaterPay_Helper_Pricing::TYPE_CATEGORY_DEFAULT_PRICE ) { echo 'lp_is-selected'; } ?><?php if ( empty( $laterpay['category_prices'] ) ) { echo ' lp_is-disabled lp_tooltip'; } ?>"
            <?php if ( $laterpay['no_category_price_set'] ) { printf( '%1$s="%2$s"', 'data-tooltip',  esc_html__( 'It looks like you have not set up a Category Default Price. Go to the LaterPay > Pricing page to set up Category Default Prices.', 'laterpay' ) ); } elseif ( empty( $laterpay['category_prices'] ) ) { printf( '%1$s="%2$s"', 'data-tooltip',  esc_html__( 'Please select a category from the "Categories" panel below to enable Category Default Pricing.', 'laterpay' ) ); } ?>>
            <a href="#"
                id="lp_js_useCategoryDefaultPrice"
                class="lp_js_priceTypeButton lp_price-type__link"><?php esc_html_e( 'Category Default Price', 'laterpay' ); ?></a>
        </li>
        <li class="lp_price-type__item <?php if ( $laterpay['post_price_type'] === LaterPay_Helper_Pricing::TYPE_GLOBAL_DEFAULT_PRICE ) { echo 'lp_is-selected'; } ?><?php if ( ! empty( $laterpay['category_prices'] ) ) { echo ' lp_is-disabled'; } ?>">
            <a href="#"
                id="lp_js_useGlobalDefaultPrice"
                class="lp_js_priceTypeButton lp_price-type__link"
                data-price="<?php echo esc_attr( LaterPay_Helper_View::format_number( $laterpay['global_default_price'] ) ); ?>"
                data-revenue-model="<?php echo esc_attr( $laterpay['global_default_price_revenue_model'] ); ?>"><?php printf( "%s <br> %s", esc_html__( 'Global', 'laterpay' ), esc_html__('Default Price', 'laterpay' ) ); ?></a>
        </li>
    </ul>

    <div id="lp_js_priceTypeDetails" class="lp_price-type__details">
        <div id="lp_js_priceTypeDetailsIndividualPrice" class="lp_js_useIndividualPrice lp_js_priceTypeDetailsSection lp_price-type__details-item"<?php if ( $laterpay['post_price_type'] !== LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ) { echo ' style="display:none;"'; } ?>>
            <input type="hidden" name="start_price">
            <input type="hidden" name="end_price">
            <input type="hidden" name="change_start_price_after_days">
            <input type="hidden" name="transitional_period_end_after_days">
            <input type="hidden" name="reach_end_price_after_days">

            <div id="lp_js_dynamicPricingWidgetContainer" class="lp_dynamic-pricing"></div>
        </div>
        <div id="lp_js_priceTypeDetailsCategoryDefaultPrice"
            class="lp_price-type__details-item lp_useCategoryDefaultPrice lp_js_priceTypeDetailsSection"<?php if ( $laterpay['post_price_type'] !== LaterPay_Helper_Pricing::TYPE_CATEGORY_DEFAULT_PRICE ) { echo ' style="display:none;"'; } ?>>
             <input type="hidden" name="post_default_category" id="lp_js_postDefaultCategoryInput" value="<?php echo esc_attr( $laterpay['post_default_category'] ); ?>">
             <ul class="lp_js_priceTypeDetailsCategoryDefaultPriceList lp_price-type-categorized__list">
                <?php if ( is_array( $laterpay['category_prices'] ) ) : ?>
                    <?php foreach ( $laterpay['category_prices'] as $category ) : ?>
                        <li data-category="<?php echo esc_attr( $category['category_id'] ); ?>" class="lp_js_priceTypeDetailsCategoryDefaultPriceItem lp_price-type-categorized__item<?php if ( $category['category_id'] === $laterpay['post_default_category'] ) : echo ' lp_is-selectedCategory'; endif; ?>">
                            <a href="#"
                                data-price="<?php echo esc_attr( LaterPay_Helper_View::format_number( $category['category_price'] ) ); ?>"
                                data-revenue-model="<?php echo esc_attr( $category['revenue_model'] ); ?>">
                                <span><?php esc_html_e( LaterPay_Helper_View::format_number( $category['category_price'] ) ); ?> <?php esc_html_e( $laterpay['currency']['code'] ); ?></span><?php esc_html_e( $category['category_name'] ); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

    </div>
</div>

<?php if ( $laterpay['post_price_type'] === LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ) : ?>
    <?php if ( $laterpay['post_status'] !== LaterPay_Helper_Pricing::STATUS_POST_PUBLISHED ) : ?>
        <dfn><?php printf( "%s <strong>%s</strong>%s<strong>%s</strong>%s", esc_html__( "The dynamic pricing will", 'laterpay' ), esc_html__( "start", "laterpay" ), esc_html__(", once you have", "laterpay" ), esc_html__( "published", "laterpay" ), esc_html__( "this post.", "laterpay" ) ); ?></dfn>
    <?php else : ?>
        <a href="#"
            id="lp_js_resetDynamicPricingStartDate"
            class="lp_dynamic-pricing-reset"
            data-icon="p"><?php esc_html_e( 'Restart dynamic pricing', 'laterpay' ); ?>
        </a>
    <?php endif; ?>
    <a href="#"
        id="lp_js_toggleDynamicPricing"
        class="lp_dynamic-pricing-toggle lp_is-withDynamicPricing"
        data-icon="e"><?php esc_html_e( 'Remove dynamic pricing', 'laterpay' ); ?>
    </a>
<?php else : ?>
    <a href="#"
        id="lp_js_toggleDynamicPricing"
        class="lp_dynamic-pricing-toggle"
        data-icon="c"
        <?php if ( substr( $laterpay['post_price_type'], 0, 16 ) !== LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_PRICE ) { echo 'style="display:none;"'; } ?>><?php esc_html_e( 'Add dynamic pricing', 'laterpay' ); ?>
    </a>
<?php endif; ?>

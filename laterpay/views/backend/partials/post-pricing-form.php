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
<?php
// Get the value of purchase type
$post_price_behaviour = LaterPay_Helper_Pricing::get_post_price_behaviour();

$individual_selected_class = '';
$type_one_disabled_class   = '';
$category_selected_class   = '';
$category_disabled_class   = '';
$global_selected_class     = '';
$global_disabled_class     = '';

if ( in_array( $laterpay['post_price_type'], array( LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_PRICE, LaterPay_Helper_Pricing::TYPE_INDIVIDUAL_DYNAMIC_PRICE ), true )
     && ( 1 !== $post_price_behaviour ) ) {
    $individual_selected_class = 'lp_is-selected';
}

if ( 1 === $post_price_behaviour ) {
    $type_one_disabled_class = 'lp_is-disabled lp_tooltip';
}

if ( $laterpay['post_price_type'] === LaterPay_Helper_Pricing::TYPE_CATEGORY_DEFAULT_PRICE ) {
    $category_selected_class = 'lp_is-selected';
}

if ( empty( $laterpay['category_prices'] ) || 1 === $post_price_behaviour ) {
    $category_disabled_class = 'lp_is-disabled lp_tooltip';
}

if ( $laterpay['post_price_type'] === LaterPay_Helper_Pricing::TYPE_GLOBAL_DEFAULT_PRICE ) {
    $global_selected_class = 'lp_is-selected';
}

if ( ! empty( $laterpay['category_prices'] ) ) {
    $global_disabled_class = ' lp_is-disabled';
}

$is_in_live_mode        = (bool) get_option( 'laterpay_plugin_is_in_live_mode' );
$is_visible_to_visitors = (bool) get_option( 'laterpay_is_in_visible_test_mode' );
?>
<div class="lp_clearfix lp_postMetaBox">
    <?php if ( ! $is_in_live_mode ) : ?>
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
    <?php if( ! $is_in_live_mode && ! $is_visible_to_visitors ): ?>
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
    <div class="lp_js_postEditGlobalBehaviour">
        <?php
        if ( 0 === $post_price_behaviour ) {
            ?>
            <p class="lp_postEditTypeZero"><?php esc_html_e( 'FREE', 'laterpay' ); ?></p>
            <span class="lp_postEditTypeZero">
                <?php
                    esc_html_e( 'All articles are free by default; Time Passes & Subscriptions will only be displayed if an Individual Article Price greater than 0.00 is manually set by selecting “Individual Price” below.', 'laterpay' );
                ?>
            </span>
            <?php
        } elseif ( 1 === $post_price_behaviour ) {
            ?>
            <p class="lp_postEditTypeOne"><?php esc_html_e( 'Posts cannot be purchased individually', 'laterpay' ); ?></p>
            <span class="lp_postEditTypeOne">
                <?php
                    printf(
                        '%1$s <br/><br/> %2$s<a href="%3$s">%4$s</a>',
                        esc_html__( 'Only Time Passes & Subscriptions will be displayed in the purchase dialog.', 'laterpay' ),
                        esc_html__( 'If you would like to allow articles to be purchased individually,', 'laterpay' ),
                        esc_url( add_query_arg( array( 'page' => 'laterpay-pricing-tab' ), admin_url( 'admin.php' ) ) ),
                        esc_html__( 'click here to adjust your Global Default Price.', 'laterpay' )
                    );
                ?>
            </span>
            <?php
        }
        ?>
    </div>
    <div class="lp_layout lp_mt+ lp_mb+" style="display:<?php echo ( 1 === $post_price_behaviour || ( 0 === $post_price_behaviour && ! empty( $global_selected_class ) ) ) ? 'none': 'block'; ?>" id="lp_js_priceEditSection">
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
        <li class="lp_price-type__item <?php echo esc_attr( $individual_selected_class . ' ' . $type_one_disabled_class ); ?>" data-tooltip="<?php echo esc_attr__( 'To allow articles to be purchased individually, visit the LaterPay Pricing tab & adjust your Global Default Price.', 'laterpay' ); ?>" >
            <a href="#"
                id="lp_js_useIndividualPrice"
                class="lp_js_priceTypeButton lp_price-type__link"><?php esc_html_e( 'Individual Price', 'laterpay' ); ?></a>
        </li>
        <li class="lp_price-type__item <?php echo esc_attr( $category_selected_class . ' ' .$category_disabled_class ); ?>"
            <?php if ( $laterpay['no_category_price_set'] && ( 1 !== $post_price_behaviour ) ) { printf( '%1$s="%2$s"', 'data-tooltip',  esc_html__( 'It looks like you have not set up a Category Default Price. Go to the LaterPay > Pricing page to set up Category Default Prices.', 'laterpay' ) ); } elseif ( empty( $laterpay['category_prices'] ) && ( 1 !== $post_price_behaviour ) ) { printf( '%1$s="%2$s"', 'data-tooltip',  esc_html__( 'Please select a category from the "Categories" panel below to enable Category Default Pricing.', 'laterpay' ) ); } ?>
            <?php if ( 1 === $post_price_behaviour ) { printf( '%1$s="%2$s"', 'data-tooltip',  esc_html__( 'To allow articles to be purchased individually, visit the LaterPay Pricing tab & adjust your Global Default Price.', 'laterpay' ) ); } ?>>
            <a href="#"
                id="lp_js_useCategoryDefaultPrice"
                class="lp_js_priceTypeButton lp_price-type__link"><?php esc_html_e( 'Category Default Price', 'laterpay' ); ?></a>
        </li>
        <li class="lp_price-type__item <?php echo esc_attr( $global_selected_class . ' ' . $global_disabled_class ); ?>">
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

<?php if ( 1 !== $post_price_behaviour ): ?>
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
<?php endif; ?>
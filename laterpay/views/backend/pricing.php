<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?>

<div class="lp_page wp-core-ui">

    <div id="lp_js_flashMessage" class="lp_flash-message" style="display:none;">
        <p></p>
    </div>

    <div class="lp_navigation">
        <?php if ( ! $laterpay['plugin_is_in_live_mode'] ) : ?>
            <a href="<?php echo esc_url( add_query_arg( array( 'page' => $laterpay['admin_menu']['account']['url'] ), admin_url( 'admin.php' ) ) ); ?>"
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
        <?php endif; ?>
        <?php
        // laterpay[pricing_obj] is instance of LaterPay_Controller_Admin_Pricing
        $laterpay['pricing_obj']->get_menu();
        $selected_option = ( int ) get_option( 'laterpay_post_price_behaviour', 2 );
        $hasCategories   = ( count( $laterpay['categories_with_defined_price'] ) > 0 );
        $isGlobalTypeOne = ( 1 === $selected_option );
        ?>
    </div>

    <div class="lp_pagewrap">

        <div class="lp_js_hideInTimePassOnlyMode lp_layout lp_mb++">
            <div class="lp_price-section lp_layout__item lp_1/2 lp_pdr">
                <h2><?php esc_html_e( 'Global Default Price', 'laterpay' ); ?></h2>

                <form id="lp_js_globalDefaultPriceForm" method="post" action="" class="lp_price-settings">
                    <input type="hidden" name="form"    value="global_price_form">
                    <input type="hidden" name="action"  value="laterpay_pricing">
                    <input type="hidden" name="revenue_model" class="lp_js_globalRevenueModel" value="<?php echo esc_attr( $laterpay['global_default_price_revenue_model'] ); ?>" disabled>
                    <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_form' ); } ?>

                    <div id="lp_js_globalDefaultPriceShowElements" class="lp_greybox lp_price-panel">
                        <div id="lp_js_globalPriceOptionTwo" style="display:<?php echo ( 2 === $selected_option ) ? 'block' : 'none'; ?>">
                            <?php
                            esc_html_e( 'Every post costs', 'laterpay' );
                            ?>
                            <span id="lp_js_globalDefaultPriceDisplay" class="lp_price-settings__value-text" data-price="<?php echo esc_attr( $laterpay['global_default_price'] ); ?>">
                                <?php echo esc_html( LaterPay_Helper_View::format_number( $laterpay['global_default_price'] ) ); ?>
                            </span>
                            <span class="lp_js_currency lp_currency">
                                <?php echo esc_html( $laterpay['currency']['code'] ); ?>
                            </span>
                            <span id="lp_js_globalDefaultPriceRevenueModelDisplay" class="lp_badge" data-revenue="<?php echo esc_attr( $laterpay['global_default_price_revenue_model'] ); ?>">
                                <?php echo esc_html( LaterPay_Helper_Pricing::get_revenue_label( $laterpay['global_default_price_revenue_model'] ) ); ?>
                            </span>
                        </div>
                        <span id="lp_js_globalPriceOptionOne" style="display:<?php echo ( 1 === $selected_option ) ? 'block' : 'none'; ?>">
                            <?php
                            printf( '%1$s <br/> %2$s',
                                esc_html__( 'Posts cannot be purchased individually;', 'laterpay' ),
                                esc_html__( 'only Time Passes & Subscriptions will be displayed.', 'laterpay' )
                            );
                            ?>
                        </span>
                        <span id="lp_js_globalPriceOptionZero" style="display:<?php echo ( 0 === $selected_option ) ? 'block' : 'none'; ?>">
                            <?php
                            printf( '<b>%1$s</b> %2$s <br/> %3$s',
                                esc_html__( 'Every post is FREE', 'laterpay' ),
                                esc_html__( 'unless they match a Category Default Price', 'laterpay' ),
                                esc_html__( 'or have an Individual Article Price set on the Post page.', 'laterpay' )
                            );
                            ?>
                        </span>

                        <div class="lp_price-panel__buttons">
                            <a href="#" id="lp_js_editGlobalDefaultPrice" class="lp_edit-link--bold lp_change-link lp_rounded--right" <?php echo ( 0 === $selected_option || 1 === $selected_option ) ? 'style="padding: 21px;"' : ''; ?> data-icon="d"></a>
                        </div>
                    </div>

                    <div id="lp_js_globalDefaultPriceEditElements" class="lp_greybox--outline lp_mb-" style="display:none;">
                        <table class="lp_table--form" width="100%">
                            <thead>
                                <tr>
                                    <th colspan="2">
                                        <?php esc_html_e( 'Edit Global Default Price', 'laterpay' ); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <td colspan="3">
                                        <label class="lp_js_postPriceLabel">
                                            <input type="radio" class="lp_js_postPriceDisplayOption" value="0" <?php checked( $selected_option, 0 ); ?> name="lp_post_price_behaviour" id="lp_make_post_free">
                                            <?php esc_html_e( 'FREE unless price is set on post page or by category', 'laterpay' ); ?>
                                        </label>
                                        <p class="lp_tooltip lp_tooltip_p" data-tooltip="<?php echo esc_attr( 'All articles will be free by default; Time Passes & Subscriptions will only be displayed if the article matches a Category Default Price or has an Individual Article Price set on the Post page.', true ) ?>">
                                            <span data-icon="m" class="lp_js_postPriceSpan"></span>
                                        </p>
                                        <br/>
                                        <label class="lp_js_postPriceLabel">
                                            <input type="radio" class="lp_js_postPriceDisplayOption" value="1" <?php checked( $selected_option, 1 ); ?> name="lp_post_price_behaviour" id="lp_disable_individual_purchase">
                                            <?php esc_html_e( 'Posts cannot be purchased individually', 'laterpay' ); ?>
                                        </label>
                                        <p class="lp_tooltip lp_tooltip_p" data-tooltip="<?php echo esc_attr( 'Only Time Passes & Subscriptions will be displayed in the purchase dialog.', true ) ?>">
                                            <span data-icon="m" class="lp_js_postPriceSpan"></span>
                                        </p>
                                        <br/>
                                        <label class="lp_js_postPriceLabel">
                                            <input type="radio" class="lp_js_postPriceDisplayOption" value="2" <?php checked( $selected_option, 2 ); ?> name="lp_post_price_behaviour" id="lp_set_individual_price">
                                            <?php esc_html_e( 'Set individual article default price', 'laterpay' ); ?>
                                        </label>
                                        <input type="hidden" value="<?php echo esc_attr( $selected_option ); ?>" name="lp_current_post_price_val">
                                    </td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="lp_js_globalPriceSection" style="<?php echo ( 0 === $selected_option || 1 === $selected_option ) ? 'display:none' : '' ?>" >
                                    <th>
                                        <?php esc_html_e( 'Price', 'laterpay' ); ?>
                                    </th>
                                    <td>
                                        <input  type="text"
                                                id="lp_js_globalDefaultPriceInput"
                                                class="lp_js_priceInput lp_input lp_number-input"
                                                name="laterpay_global_price"
                                                value="<?php echo esc_attr( number_format( $laterpay['global_default_price'], 2, '.', '' ) ); ?>"
                                                placeholder="<?php echo esc_attr( LaterPay_Helper_View::format_number( 0 ) ); ?>">
                                        <span class="lp_js_currency lp_currency"><?php echo esc_html( $laterpay['currency']['code'] ); ?></span>
                                    </td>
                                </tr>
                                <tr id="lp_js_globalRevenueSection" style="<?php echo ( 0 === $selected_option || 1 === $selected_option ) ? 'display:none' : '' ?>">
                                    <th>
                                        <?php esc_html_e( 'Revenue Model', 'laterpay' ); ?>
                                    </th>
                                    <td>
                                        <div class="lp_js_revenueModel lp_button-group">
                                            <label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
                                                <?php if ( $laterpay['global_default_price_revenue_model'] === 'ppu' || ! $laterpay['global_default_price_revenue_model'] ) { echo 'lp_is-selected'; } ?>
                                                <?php if ( $laterpay['global_default_price'] > $laterpay['currency']['ppu_max'] ) { echo 'lp_is-disabled lp_tooltip'; } ?>" <?php if ( $laterpay['global_default_price'] > $laterpay['currency']['ppu_max'] ) { printf( '%1$s="%2$s"', 'data-tooltip',  esc_html__( 'Pay Later allows users to gain access now by committing to pay once their invoice reaches $5 or 5€; it is available for posts with pricing between 0.05 and 5.00', 'laterpay' ) ); } ?>>
                                                <input type="radio" name="laterpay_global_price_revenue_model" class="lp_js_revenueModelInput" value="ppu" <?php if ( $laterpay['global_default_price_revenue_model'] === 'ppu' || ( ! $laterpay['global_default_price_revenue_model'] && $laterpay['global_default_price'] < $laterpay['currency']['ppu_max'] ) ) { echo ' checked'; } ?>><?php esc_html_e( 'Pay Later', 'laterpay' ); ?>
                                            </label><!--
                                            --><label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
                                                <?php if ( $laterpay['global_default_price_revenue_model'] === 'sis' ) { echo 'lp_is-selected'; } ?>
                                                <?php if ( $laterpay['global_default_price'] < $laterpay['currency']['sis_min'] ) { echo 'lp_is-disabled lp_tooltip'; } ?>" <?php if ( $laterpay['global_default_price'] < $laterpay['currency']['sis_min'] ) { printf( '%1$s="%2$s"', 'data-tooltip',  esc_html__( 'Pay Now requires users pay for purchased content immediately; available for posts with pricing above $1.99 or 1.49€', 'laterpay' ) ); } ?>>
                                                <input type="radio" name="laterpay_global_price_revenue_model" class="lp_js_revenueModelInput" value="sis" <?php if ( $laterpay['global_default_price_revenue_model'] === 'sis' ) { echo ' checked'; } ?>><?php esc_html_e( 'Pay Now', 'laterpay' ); ?>
                                            </label>
                                        </div>
                                    </td>
                                    <td rowspan="2" class="lp_revenu_td_width">
                                        <div class="lp_show_revenue_info">
                                            <?php
                                            printf(
                                                '<b>%1$s</b>%2$s <a href="%3$s">%4$s</a>',
                                                esc_html__( 'TIP:', 'laterpay' ),
                                                esc_html__( ' "Pay Later" is LaterPay\'s patented revenue model which allows your customers to purchase content with a single click, dramatically reducing their barriers to entry. Once they have purchased $5 or 5€ worth of content, they will be asked to settle their invoice.', 'laterpay' ),
                                                esc_url( 'https://support.laterpay.net/hc/en-us/articles/201251457-What-is-LaterPay-' ),
                                                esc_html__( ' Click here to learn more.', 'laterpay' )
                                            );
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td colspan="3" id="lp_js_formButtons">
                                        <a href="#" id="lp_js_saveGlobalDefaultPrice" class="button button-primary"><?php esc_html_e( 'Save', 'laterpay' ); ?></a>
                                        <a href="#" id="lp_js_cancelEditingGlobalDefaultPrice" class="lp_inline-block lp_pd--05-1"><?php esc_html_e( 'Cancel', 'laterpay' ); ?></a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div><!--
         --><div class="lp_price-section lp_layout__item lp_1/2 lp_pdr">
                <h2>
                    <?php esc_html_e( 'Category Default Prices', 'laterpay' ); ?>
                    <div class="lp_js_categoryButtonContainer <?php echo ( 1 === $selected_option ) ? 'lp_tooltip' : ''; ?>" data-tooltip="<?php echo esc_attr__( 'To allow articles to be purchased individually, adjust your Global Default Price.', 'laterpay' ); ?>">
                        <a href="#" id="lp_js_addCategoryDefaultPrice" class="button button-primary lp_heading-button" <?php echo ( 1 === $selected_option ) ? 'disabled=disabled' : ''; ?> data-icon="c">
                            <?php esc_html_e( 'Create', 'laterpay' ); ?>
                        </a>
                    </div>
                </h2>

                <div class="lp_js_categoryPanelWarning" style="display:<?php echo ( true === $hasCategories && true === $isGlobalTypeOne ) ? 'block' : 'none'; ?>">
                    <p data-icon="n">
                        <?php
                        printf(
                            '%1$s <br/> %2$s',
                            esc_html__( 'Only Time Passes & Subscriptions will be displayed in the purchase dialog.', 'laterpay' ),
                            esc_html__( 'To allow articles to be purchased individually, adjust your Global Default Price.', 'laterpay' )
                        );
                        ?>
                    </p>
                </div>

                <div id="lp_js_categoryDefaultPriceList">
                    <?php foreach ( $laterpay['categories_with_defined_price'] as $category ) : ?>
                        <?php $category_price         = $category->category_price; ?>
                        <?php $category_revenue_model = $category->revenue_model; ?>

                        <form method="post" class="lp_js_categoryDefaultPriceForm lp_category-price-form">
                            <input type="hidden" name="form"        value="price_category_form">
                            <input type="hidden" name="action"      value="laterpay_pricing">
                            <input type="hidden" name="category_id" class="lp_js_categoryDefaultPriceCategoryId" value="<?php echo esc_attr( $category->category_id ); ?>">
                            <input type="hidden" name="revenue_model" class="lp_js_categoryRevenueModel" value="<?php echo esc_attr( $category_revenue_model ); ?>" disabled>
                            <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_form' ); } ?>

                            <div class="lp_js_categoryDefaultPriceShowElements lp_greybox lp_mb- lp_price-panel">
                                <?php esc_html_e( 'Every post in', 'laterpay' ); ?>
                                <span class="lp_js_categoryDefaultPriceCategoryTitle lp_inline-block">
                                    <?php echo esc_html( $category->category_name ); ?>
                                </span>
                                <?php esc_html_e( 'costs', 'laterpay' ); ?>
                                <span class="lp_js_categoryDefaultPriceDisplay lp_category-price" data-price="<?php echo esc_attr( $category_price ); ?>">
                                    <?php echo esc_html( LaterPay_Helper_View::format_number( $category_price ) ); ?>
                                </span>
                                <span class="lp_js_currency lp_currency">
                                    <?php echo esc_html( $laterpay['currency']['code'] ); ?>
                                </span>
                                <span class="lp_js_revenueModelLabelDisplay lp_badge" data-revenue="<?php echo esc_attr( $category_revenue_model ); ?>">
                                    <?php echo esc_html( LaterPay_Helper_Pricing::get_revenue_label( $category_revenue_model ) ); ?>
                                </span>
                                <div class="lp_price-panel__buttons">
                                    <a href="#" class="lp_js_deleteCategoryDefaultPrice lp_edit-link--bold lp_delete-link lp_rounded--right" data-icon="g"></a>
                                    <a href="#" class="lp_js_editCategoryDefaultPrice lp_edit-link--bold lp_change-link" data-icon="d"></a>
                                </div>
                            </div>

                            <div class="lp_js_categoryDefaultPriceEditElements lp_greybox--outline lp_mb-" style="display:none;">
                                <table class="lp_table--form">
                                    <thead>
                                        <tr>
                                            <th colspan="2">
                                                <?php esc_html_e( 'Edit Category Default Price', 'laterpay' ); ?>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th>
                                                <?php esc_html_e( 'Category', 'laterpay' ); ?>
                                            </th>
                                            <td>
                                                <input type="hidden" name="category" value="<?php echo esc_attr( $category->category_name ); ?>" class="lp_js_selectCategory">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <?php esc_html_e( 'Price', 'laterpay' ); ?>
                                            </th>
                                            <td>
                                                <input  type="text"
                                                        name="price"
                                                        class="lp_js_priceInput lp_js_categoryDefaultPriceInput lp_input lp_number-input"
                                                        value="<?php echo esc_attr( number_format( $category->category_price , 2, '.', '' ) ); ?>"
                                                        placeholder="<?php echo esc_attr( LaterPay_Helper_View::format_number( 0 ) ); ?>">
                                                <span class="lp_js_currency lp_currency"><?php echo esc_html( $laterpay['currency']['code'] ); ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <?php esc_html_e( 'Revenue Model', 'laterpay' ); ?>
                                            </th>
                                            <td>
                                                <div class="lp_js_revenueModel lp_button-group">
                                                    <label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
                                                            <?php if ( 'ppu' === strval( $category_revenue_model ) || ( ! $category_revenue_model && $category_price <= $laterpay['currency']['ppu_max'] ) ) { echo 'lp_is-selected'; } ?>
                                                            <?php if ( $category_price > $laterpay['currency']['ppu_max'] ) { echo 'lp_is-disabled'; } ?>">
                                                        <input type="radio" name="laterpay_category_price_revenue_model_<?php echo esc_attr( $category->category_id ); ?>" class="lp_js_revenueModelInput" value="ppu" <?php if ( $category_revenue_model === 'ppu' || ( ! $category_revenue_model && $category_price <= $laterpay['currency']['ppu_max'] ) ) { echo ' checked'; } ?>><?php esc_html_e( 'Pay Later', 'laterpay' ); ?>
                                                    </label><!--
                                                    --><label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
                                                            <?php if ( 'sis' === strval( $category_revenue_model ) || ( ! $category_revenue_model && $category_price > $laterpay['currency']['ppu_max'] ) ) { echo 'lp_is-selected'; } ?>
                                                            <?php if ( $category_price < $laterpay['currency']['sis_min'] ) { echo 'lp_is-disabled'; } ?>">
                                                        <input type="radio" name="laterpay_category_price_revenue_model_<?php echo esc_attr( $category->category_id ); ?>" class="lp_js_revenueModelInput" value="sis" <?php if ( $category_revenue_model === 'sis' || ( ! $category_revenue_model && $category_price > $laterpay['currency']['ppu_max'] ) ) { echo ' checked'; } ?>><?php esc_html_e( 'Pay Now', 'laterpay' ); ?>
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td>&nbsp;</td>
                                            <td>
                                                <a href="#" class="lp_js_saveCategoryDefaultPrice button button-primary"><?php esc_html_e( 'Save', 'laterpay' ); ?></a>
                                                <a href="#" class="lp_js_cancelEditingCategoryDefaultPrice lp_inline-block lp_pd--05-1"><?php esc_html_e( 'Cancel', 'laterpay' ); ?></a>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </form>
                    <?php endforeach; ?>

                    <div class="lp_js_emptyState lp_empty-state"<?php if ( ! empty( $laterpay['categories_with_defined_price'] ) ) { echo ' style="display:none;"'; } ?>>
                        <h2>
                            <?php esc_html_e( 'Set prices by category', 'laterpay' ); ?>
                        </h2>
                        <p>
                            <?php
                            /* translators: %1$s info text, %2$s link to wordpress categories codex page, %3$s link text*/
                            printf( '%1$s<br/><a href="%2$s" target="_blank">%3$s</a>',
                                esc_html__( 'Not familiar with WordPress categories?', 'laterpay' ),
                                esc_url( 'https://codex.wordpress.org/Posts_Categories_Screen' ),
                                esc_html__( 'Click here to learn more.', 'laterpay' )
                            );
                            ?>
                        </p>
                        <p>
                            <?php
                            /* translators: %1$s info text1, %2$s info text2*/
                            printf( '%1$s<br>%2$s', esc_html__( 'Category default prices are convenient for selling different categories of content at different standard prices.', 'laterpay' ), esc_html__( 'Individual prices can be set when editing a post.', 'laterpay' ) );
                            ?>
                        </p>
                        <p>
                            <?php esc_html_e( 'Click the "Create" button to set a default price for a category.', 'laterpay' ); ?>
                        </p>
                    </div>
                </div>

                <form method="post" id="lp_js_categoryDefaultPriceTemplate" class="lp_js_categoryDefaultPriceForm lp_category-price-form lp_is-unsaved lp_price-panel" style="display:none;">
                    <input type="hidden" name="form"        value="price_category_form">
                    <input type="hidden" name="action"      value="laterpay_pricing">
                    <input type="hidden" name="category_id" value="" class="lp_js_categoryDefaultPriceCategoryId">
                    <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_form' ); } ?>

                    <div class="lp_js_categoryDefaultPriceShowElements lp_greybox lp_mb-" style="display:none;">
                        <?php esc_html_e( 'Every post in', 'laterpay' ); ?>
                        <span class="lp_js_categoryDefaultPriceCategoryTitle lp_inline-block">
                        </span>
                        <?php esc_html_e( 'costs', 'laterpay' ); ?>
                        <span class="lp_js_categoryDefaultPriceDisplay lp_category-price">
                        </span>
                        <span class="lp_js_currency lp_currency">
                            <?php echo esc_html( $laterpay['currency']['code'] ); ?>
                        </span>
                        <span class="lp_js_revenueModelLabelDisplay lp_badge">
                        </span>
                        <div class="lp_price-panel__buttons">
                            <a href="#" class="lp_js_deleteCategoryDefaultPrice lp_edit-link--bold lp_delete-link lp_rounded--right" data-icon="g"></a>
                            <a href="#" class="lp_js_editCategoryDefaultPrice lp_edit-link--bold lp_change-link" data-icon="d"></a>
                        </div>
                    </div>

                    <div class="lp_js_categoryDefaultPriceEditElements lp_greybox--outline lp_mb-">
                        <table class="lp_table--form">
                            <thead>
                                <tr>
                                    <th colspan="2">
                                        <?php esc_html_e( 'Add a Category Default Price', 'laterpay' ); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <th>
                                        <?php esc_html_e( 'Category', 'laterpay' ); ?>
                                    </th>
                                    <td>
                                        <input type="hidden" name="category" value="" class="lp_js_selectCategory">
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <?php esc_html_e( 'Price', 'laterpay' ); ?>
                                    </th>
                                    <td>
                                        <input  type="text"
                                                name="price"
                                                class="lp_js_priceInput lp_js_categoryDefaultPriceInput lp_input lp_number-input"
                                                value="<?php echo esc_attr( number_format( $laterpay['global_default_price'], 2, '.', '' ) ); ?>"
                                                placeholder="<?php echo esc_attr( LaterPay_Helper_View::format_number( 0 ) ); ?>">
                                        <span class="lp_js_currency lp_currency"><?php echo esc_html( $laterpay['currency']['code'] ); ?></span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <?php esc_html_e( 'Revenue Model', 'laterpay' ); ?>
                                    </th>
                                    <td>
                                        <div class="lp_js_revenueModel lp_button-group">
                                            <label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
                                                    <?php if ( 'ppu' === strval( $laterpay['global_default_price_revenue_model'] ) || ( ! $laterpay['global_default_price_revenue_model'] && $laterpay['global_default_price'] < $laterpay['currency']['ppu_max'] ) ) { echo 'lp_is-selected'; } ?>
                                                    <?php if ( $laterpay['global_default_price'] > $laterpay['currency']['ppu_max'] ) { echo 'lp_is-disabled'; } ?>">
                                                <input type="radio" name="laterpay_category_price_revenue_model" class="lp_js_revenueModelInput" value="ppu"<?php if ( $laterpay['global_default_price_revenue_model'] === 'ppu' || ( ! $laterpay['global_default_price_revenue_model'] && $laterpay['global_default_price'] < $laterpay['currency']['ppu_max'] ) ) { echo ' checked'; } ?>><?php esc_html_e( 'Pay Later', 'laterpay' ); ?>
                                            </label><!--
                                            --><label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
                                                    <?php if ( 'sis' === strval( $laterpay['global_default_price_revenue_model'] ) ) { echo 'lp_is-selected'; } ?>
                                                    <?php if ( $laterpay['global_default_price'] < $laterpay['currency']['sis_min'] ) { echo 'lp_is-disabled'; } ?>">
                                                <input type="radio" name="laterpay_category_price_revenue_model" class="lp_js_revenueModelInput" value="sis"<?php if ( $laterpay['global_default_price_revenue_model'] === 'sis' ) { echo ' checked'; } ?>><?php esc_html_e( 'Pay Now', 'laterpay' ); ?>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>
                                        <a href="#" class="lp_js_saveCategoryDefaultPrice button button-primary"><?php esc_html_e( 'Save', 'laterpay' ); ?></a>
                                        <a href="#" class="lp_js_cancelEditingCategoryDefaultPrice lp_inline-block lp_pd--05-1"><?php esc_html_e( 'Cancel', 'laterpay' ); ?></a>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </form>
            </div>
        </div>

        <div class="lp_layout lp_mt+ lp_mb++">
            <div id="lp_time-passes" class="lp_time-passes__list lp_layout__item lp_1/2 lp_pdr">
                <h2>
                    <?php esc_html_e( 'Time Passes', 'laterpay' ); ?>
                    <a href="#" id="lp_js_addTimePass" class="button button-primary lp_heading-button" data-icon="c">
                        <?php esc_html_e( 'Create', 'laterpay' ); ?>
                    </a>
                </h2>

                <?php foreach ( $laterpay['passes_list'] as $pass ) : ?>
                    <div class="lp_js_timePassWrapper lp_time-passes__item lp_clearfix" data-pass-id="<?php echo esc_attr( $pass['pass_id'] ); ?>">
                        <div class="lp_time-pass__id-wrapper">
                            <?php esc_html_e( 'Pass', 'laterpay' ); ?>
                            <span class="lp_js_timePassId lp_time-pass__id"><?php echo esc_html( $pass['pass_id'] ); ?></span>
                        </div>
                        <div class="lp_js_timePassPreview lp_left">
                            <?php $this->render_time_pass( $pass, true ); ?>
                        </div>

                        <div class="lp_js_timePassEditorContainer lp_time-pass-editor"></div>

                        <a href="#" class="lp_js_saveTimePass button button-primary lp_mt- lp_mb- lp_hidden"><?php esc_html_e( 'Save', 'laterpay' ); ?></a>
                        <a href="#" class="lp_js_cancelEditingTimePass lp_inline-block lp_pd- lp_hidden"><?php esc_html_e( 'Cancel', 'laterpay' ); ?></a>
                        <a href="#" class="lp_js_editTimePass lp_edit-link--bold lp_rounded--topright lp_inline-block" data-icon="d"></a>
                        <a href="#" class="lp_js_deleteTimePass lp_edit-link--bold lp_inline-block" data-icon="g"></a>

                        <div class="lp_js_voucherList lp_vouchers">
                            <?php if ( isset( $laterpay['vouchers_list'][ $pass['pass_id'] ] ) ) : ?>
                                <?php foreach ( $laterpay['vouchers_list'][ $pass['pass_id'] ] as $voucher_code => $voucher_data ) : ?>
                                    <div class="lp_js_voucher lp_voucher">
                                        <?php if ( $voucher_data['title'] ) : ?>
                                        <span class="lp_voucher__title"><b> <?php echo esc_html( $voucher_data['title'] ); ?></b></span>
                                        <?php endif; ?>
                                        <div>
                                        <span class="lp_voucher__code"><?php echo esc_html( $voucher_code ); ?></span>
                                        <span class="lp_voucher__code-infos">
                                            <?php esc_html_e( 'reduces the price to', 'laterpay' ); ?>
                                            <?php echo esc_html( $voucher_data['price'] . ' ' . $laterpay['currency']['code'] ); ?>.
                                        </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div id="lp_js_timePassTemplate"
                    class="lp_js_timePassWrapper lp_time-passes__item lp_clearfix lp_hidden"
                    data-pass-id="0">
                    <div class="lp_time-pass__id-wrapper" style="display:none;">
                        <?php esc_html_e( 'Pass', 'laterpay' ); ?>
                        <span class="lp_js_timePassId lp_time-pass__id">x</span>
                    </div>

                    <div class="lp_js_timePassPreview lp_left">
                        <?php $this->render_time_pass( array(), true ); ?>
                    </div>

                    <div class="lp_js_timePassEditorContainer lp_time-pass-editor">
                        <form class="lp_js_timePassEditorForm lp_hidden lp_1 lp_mb" method="post">
                            <input type="hidden" name="form"    value="time_pass_form_save">
                            <input type="hidden" name="action"  value="laterpay_pricing">
                            <input type="hidden" name="pass_id" value="0" id="lp_js_timePassEditorHiddenPassId">
                            <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_form' ); } ?>

                            <table class="lp_time-pass-editor__column lp_1">
                                <tr>
                                    <td>
                                        <?php esc_html_e( 'The pass is valid for ', 'laterpay' ); ?>
                                    </td>
                                    <td>
                                        <select name="duration" class="lp_js_switchTimePassDuration lp_input">

                                            <?php echo wp_kses( LaterPay_Helper_TimePass::get_select_options( 'duration' ), array(
                                                'option' => array(
                                                    'selected' => array(),
                                                    'value'    => array(),
                                                )
                                            ) ); ?>
                                        </select>
                                        <select name="period" class="lp_js_switchTimePassPeriod lp_input">
                                            <?php echo wp_kses( LaterPay_Helper_TimePass::get_select_options( 'period' ), array(
                                                'option' => array(
                                                    'selected' => array(),
                                                    'value'    => array(),
                                                )
                                            ) ); ?>
                                        </select>
                                        <?php esc_html_e( 'and grants', 'laterpay' ); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php esc_html_e( 'access to', 'laterpay' ); ?>
                                    </td>
                                    <td>
                                        <select name="access_to" class="lp_js_switchTimePassScope lp_input lp_1">
                                            <?php echo wp_kses( LaterPay_Helper_TimePass::get_select_options( 'access' ), array(
                                                'option' => array(
                                                    'selected' => array(),
                                                    'value'    => array(),
                                                )
                                            ) ); ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="lp_js_timePassCategoryWrapper">
                                    <td>
                                    </td>
                                    <td>
                                        <input type="hidden" name="category_name"   value="" class="lp_js_switchTimePassScopeCategory">
                                        <input type="hidden" name="access_category" value="" class="lp_js_timePassCategoryId">
                                    </td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e( 'This pass costs', 'laterpay' ); ?></td>
                                    <td>
                                        <input type="text"
                                            class="lp_js_timePassPriceInput lp_input lp_number-input"
                                            name="price"
                                            value="<?php echo esc_attr( LaterPay_Helper_View::format_number( LaterPay_Helper_TimePass::get_default_options( 'price' ) ) ); ?>"
                                            maxlength="6">
                                        <?php echo esc_html( $laterpay['currency']['code'] ); ?>
                                        <?php esc_html_e( 'and the user has to', 'laterpay' ); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                        <div class="lp_js_revenueModel lp_button-group">
                                            <label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
                                                            <?php if ( LaterPay_Helper_TimePass::get_default_options( 'revenue_model' ) === 'ppu' ) { echo 'lp_is-selected'; } ?>
                                                            <?php if ( LaterPay_Helper_TimePass::get_default_options( 'price' ) > $laterpay['currency']['ppu_max'] ) { echo 'lp_is-disabled'; } ?>">
                                                <input type="radio" name="revenue_model" class="lp_js_timePassRevenueModelInput" value="ppu"<?php if ( 'ppu' === strval( LaterPay_Helper_TimePass::get_default_options( 'revenue_model' ) ) ) { echo ' checked'; } ?>><?php esc_html_e( 'Pay Later', 'laterpay' ); ?>
                                            </label><!--
                                            --><label class="lp_js_revenueModelLabel lp_button-group__button lp_1/2
                                                            <?php if ( LaterPay_Helper_TimePass::get_default_options( 'revenue_model' ) === 'sis' ) { echo 'lp_is-selected'; } ?>
                                                            <?php if ( LaterPay_Helper_TimePass::get_default_options( 'price' ) < $laterpay['currency']['sis_min'] ) { echo 'lp_is-disabled'; } ?>">
                                                <input type="radio" name="revenue_model" class="lp_js_timePassRevenueModelInput" value="sis"<?php if ( 'sis' === strval( LaterPay_Helper_TimePass::get_default_options( 'revenue_model' ) ) ) { echo ' checked'; } ?>><?php esc_html_e( 'Pay Now', 'laterpay' ); ?>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php esc_html_e( 'Title', 'laterpay' ); ?>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="title"
                                               class="lp_js_timePassTitleInput lp_input lp_1"
                                               value="<?php echo esc_attr( LaterPay_Helper_TimePass::get_default_options( 'title' ) ); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="lp_rowspan-label">
                                        <?php esc_html_e( 'Description', 'laterpay' ); ?>
                                    </td>
                                    <td rowspan="2">
                                        <textarea
                                            class="lp_js_timePassDescriptionTextarea lp_timePass_description-input lp_input lp_1"
                                            name="description">
                                            <?php echo esc_textarea( LaterPay_Helper_TimePass::get_description() ); ?>
                                        </textarea>
                                    </td>
                                </tr>
                            </table>

                            <div class="lp_js_voucherEditor lp_mt-">
                                <?php esc_html_e( 'Offer this time pass at a reduced price of', 'laterpay' ); ?>
                                <input type="text"
                                       name="voucher_price_temp"
                                       class="lp_js_voucherPriceInput lp_input lp_number-input"
                                       value="<?php echo esc_attr( LaterPay_Helper_View::format_number( LaterPay_Helper_TimePass::get_default_options( 'price' ) ) ); ?>"
                                       maxlength="6">
                                <span><?php echo esc_html( $laterpay['currency']['code'] ); ?></span>
                                <span class="lp_js_voucher_msg" data-icon="n"><?php printf( '%1$s<br/>%2$s', esc_html__( 'The voucher price must be less than or equal to the ', 'laterpay'), esc_html__( 'time pass price.', 'laterpay' )  ); ?></span>
                                <a href="#" class="lp_js_generateVoucherCode lp_edit-link lp_add-link" data-icon="c">
                                    <?php esc_html_e( 'Generate voucher code', 'laterpay' ); ?>
                                </a>

                                <div class="lp_js_voucherPlaceholder"></div>
                            </div>

                        </form>
                    </div>

                    <a href="#" class="lp_js_saveTimePass button button-primary lp_mt- lp_mb-"><?php esc_html_e( 'Save', 'laterpay' ); ?></a>
                    <a href="#" class="lp_js_cancelEditingTimePass lp_inline-block lp_pd-"><?php esc_html_e( 'Cancel', 'laterpay' ); ?></a>

                    <a href="#" class="lp_js_editTimePass lp_edit-link--bold lp_rounded--topright lp_inline-block lp_hidden" data-icon="d"></a><br>
                    <a href="#" class="lp_js_deleteTimePass lp_edit-link--bold lp_inline-block lp_hidden" data-icon="g"></a>

                    <div class="lp_js_voucherList lp_vouchers"></div>
                </div>

                <div class="lp_js_emptyState lp_empty-state"<?php if ( ! empty( $laterpay['passes_list'] ) ) { echo ' style="display:none;"'; } ?>>
                    <h2>
                        <?php esc_html_e( 'Sell bundles of content', 'laterpay' ); ?>
                    </h2>
                    <p>
                        <?php esc_html_e( 'With Time Passes you can sell time-limited access to a category or your entire site. Time Passes do not renew automatically.', 'laterpay' ); ?>
                    </p>
                    <p>
                        <?php esc_html_e( 'Click the "Create" button to add a Time Pass.', 'laterpay' ); ?>
                    </p>
                </div>
            </div><!--
         --><div id="lp_subscriptions" class="lp_subscriptions__list lp_layout__item lp_1/2 lp_pdr">
                <h2>
                    <?php esc_html_e( 'Subscriptions', 'laterpay' ); ?>
                    <a href="#" id="lp_js_addSubscription" class="button button-primary lp_heading-button" data-icon="c">
                        <?php esc_html_e( 'Create', 'laterpay' ); ?>
                    </a>
                </h2>

                <?php foreach ( $laterpay['subscriptions_list'] as $subscription ) : ?>
                    <div class="lp_js_subscriptionWrapper lp_subscriptions__item lp_clearfix" data-sub-id="<?php echo esc_attr( $subscription['id'] ); ?>">
                        <div class="lp_subscription__id-wrapper">
                            <?php esc_html_e( 'Sub', 'laterpay' ); ?>
                            <span class="lp_js_subscriptionId lp_subscription__id"><?php echo esc_html( $subscription['id'] ); ?></span>
                        </div>
                        <div class="lp_js_subscriptionPreview lp_left">
                            <?php $this->render_subscription( $subscription, true ); ?>
                        </div>

                        <div class="lp_js_subscriptionEditorContainer lp_subscription-editor"></div>

                        <a href="#" class="lp_js_saveSubscription button button-primary lp_mt- lp_mb- lp_hidden"><?php esc_html_e( 'Save', 'laterpay' ); ?></a>
                        <a href="#" class="lp_js_cancelEditingSubscription lp_inline-block lp_pd- lp_hidden"><?php esc_html_e( 'Cancel', 'laterpay' ); ?></a>
                        <a href="#" class="lp_js_editSubscription lp_edit-link--bold lp_rounded--topright lp_inline-block" data-icon="d"></a>
                        <a href="#" class="lp_js_deleteSubscription lp_edit-link--bold lp_inline-block" data-icon="g"></a>

                        <div class="lp_js_voucherList lp_vouchers">
                            <?php if ( isset( $laterpay['sub_vouchers_list'][ $subscription['id'] ] ) ) : ?>
                                <?php foreach ( $laterpay['sub_vouchers_list'][ $subscription['id'] ] as $voucher_code => $voucher_data ) : ?>
                                    <div class="lp_js_voucher lp_voucher">
                                        <?php if ( $voucher_data['title'] ) : ?>
                                            <span class="lp_voucher__title"><b> <?php echo esc_html( $voucher_data['title'] ); ?></b></span>
                                        <?php endif; ?>
                                        <div>
                                            <span class="lp_voucher__code"><?php echo esc_html( $voucher_code ); ?></span>
                                            <span class="lp_voucher__code-infos">
                                            <?php esc_html_e( 'reduces the price to', 'laterpay' ); ?>
                                                <?php echo esc_html( $voucher_data['price'] . ' ' . $laterpay['currency']['code'] ); ?>.
                                        </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div id="lp_js_subscriptionTemplate"
                     class="lp_js_subscriptionWrapper lp_subscriptions__item lp_greybox lp_clearfix lp_hidden"
                     data-sub-id="0">
                    <div class="lp_subscription__id-wrapper" style="display:none;">
                        <?php esc_html_e( 'Sub', 'laterpay' ); ?>
                        <span class="lp_js_subscriptionId lp_subscription__id">x</span>
                    </div>

                    <div class="lp_js_subscriptionPreview lp_left">
                        <?php $this->render_subscription( array(), true ); ?>
                    </div>

                    <div class="lp_js_subscriptionEditorContainer lp_subscription-editor">
                        <form class="lp_js_subscriptionEditorForm lp_hidden lp_1 lp_mb" method="post">
                            <input type="hidden" name="form"    value="subscription_form_save">
                            <input type="hidden" name="action"  value="laterpay_pricing">
                            <input type="hidden" name="id"      value="0" id="lp_js_subscriptionEditorHiddenSubcriptionId">
                            <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_form' ); } ?>

                            <table class="lp_subscription-editor__column lp_1">
                                <tr>
                                    <td>
                                        <?php esc_html_e( 'The subscription costs', 'laterpay' ); ?>
                                    </td>
                                    <td>
                                        <input type="text"
                                               class="lp_js_subscriptionPriceInput lp_input lp_number-input"
                                               name="price"
                                               value="<?php echo esc_attr( LaterPay_Helper_View::format_number( LaterPay_Helper_TimePass::get_default_options( 'price' ) ) ); ?>"
                                               maxlength="6">
                                        <?php echo esc_html( $laterpay['currency']['code'] ); ?>
                                        <?php esc_html_e( ', grants ', 'laterpay' ); ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php esc_html_e( 'access to', 'laterpay' ); ?>
                                    </td>
                                    <td>
                                        <select name="access_to" class="lp_js_switchSubscriptionScope lp_input lp_1">
                                            <?php echo wp_kses( LaterPay_Helper_TimePass::get_select_options( 'access' ), array(
                                                'option' => array(
                                                    'selected' => array(),
                                                    'value'    => array(),
                                                )
                                            ) ); ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="lp_js_subscriptionCategoryWrapper">
                                    <td>
                                    </td>
                                    <td>
                                        <input type="hidden" name="category_name"   value="" class="lp_js_switchSubscriptionScopeCategory">
                                        <input type="hidden" name="access_category" value="" class="lp_js_subscriptionCategoryId">
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php esc_html_e( 'and renews every', 'laterpay' ); ?>
                                    </td>
                                    <td>
                                        <select name="duration" class="lp_js_switchSubscriptionDuration lp_input">
                                            <?php echo wp_kses( LaterPay_Helper_TimePass::get_select_options( 'duration' ), array(
                                                'option' => array(
                                                    'selected' => array(),
                                                    'value'    => array(),
                                                )
                                            ) ); ?>
                                        </select>
                                        <select name="period" class="lp_js_switchSubscriptionPeriod lp_input">
                                            <?php echo wp_kses( LaterPay_Helper_TimePass::get_select_options( 'period' ), array(
                                                'option' => array(
                                                    'selected' => array(),
                                                    'value'    => array(),
                                                )
                                            ) ); ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php esc_html_e( 'Title', 'laterpay' ); ?>
                                    </td>
                                    <td>
                                        <input type="text"
                                               name="title"
                                               class="lp_js_subscriptionTitleInput lp_input lp_1"
                                               value="<?php echo esc_attr( LaterPay_Helper_TimePass::get_default_options( 'title' ) ); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="lp_rowspan-label">
                                        <?php esc_html_e( 'Description', 'laterpay' ); ?>
                                    </td>
                                    <td rowspan="2">
                                        <textarea
                                            class="lp_js_subscriptionDescriptionTextarea lp_subscription_description-input lp_input lp_1"
                                            name="description">
                                            <?php echo esc_textarea( LaterPay_Helper_TimePass::get_description() ); ?>
                                        </textarea>
                                    </td>
                                </tr>
                            </table>

                            <div class="lp_js_voucherEditor lp_mt-">
                                <?php esc_html_e( 'Offer this subscription at a reduced price of', 'laterpay' ); ?>
                                <input type="text"
                                       name="voucher_price_temp"
                                       class="lp_js_voucherPriceInput lp_input lp_number-input"
                                       value="<?php echo esc_attr( LaterPay_Helper_View::format_number( LaterPay_Helper_Subscription::get_default_options( 'price' ) ) ); ?>"
                                       maxlength="6">
                                <span><?php echo esc_html( $laterpay['currency']['code'] ); ?></span>
                                <span class="lp_js_voucher_msg" data-icon="n"><?php printf( '%1$s<br/>%2$s', esc_html__( 'The voucher price must be less than or equal to the ', 'laterpay'), esc_html__( 'subscription price.', 'laterpay' )  ); ?></span>
                                <a href="#" class="lp_js_generateVoucherCode lp_edit-link lp_add-link" data-icon="c">
                                    <?php esc_html_e( 'Generate voucher code', 'laterpay' ); ?>
                                </a>

                                <div class="lp_js_voucherPlaceholder"></div>
                            </div>

                        </form>
                    </div>

                    <a href="#" class="lp_js_saveSubscription button button-primary lp_mt- lp_mb-"><?php esc_html_e( 'Save', 'laterpay' ); ?></a>
                    <a href="#" class="lp_js_cancelEditingSubscription lp_inline-block lp_pd-"><?php esc_html_e( 'Cancel', 'laterpay' ); ?></a>

                    <a href="#" class="lp_js_editSubscription lp_edit-link--bold lp_rounded--topright lp_inline-block lp_hidden" data-icon="d"></a><br>
                    <a href="#" class="lp_js_deleteSubscription lp_edit-link--bold lp_inline-block lp_hidden" data-icon="g"></a>

                    <div class="lp_js_voucherList lp_vouchers"></div>
                </div>

                <div class="lp_js_emptyState lp_empty-state"<?php if ( ! empty( $laterpay['subscriptions_list'] ) ) { echo ' style="display:none;"'; } ?>>
                    <h2>
                        <?php esc_html_e( 'Sell subscriptions', 'laterpay' ); ?>
                    </h2>
                    <p>
                        <?php esc_html_e( 'Subscriptions work exactly like time passes, with a simple difference: They renew automatically.', 'laterpay' ); ?>
                    </p>
                    <p>
                        <?php esc_html_e( 'Click the "Create" button to add a Subscription.', 'laterpay' ); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

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
        <a  href="<?php echo esc_url( add_query_arg( array( 'page' => $laterpay['admin_menu']['account']['url'] ), admin_url( 'admin.php' ) ) ); ?>"
            id="lp_js_pluginModeIndicator"
            class="lp_plugin-mode-indicator"
            <?php if ( $laterpay['plugin_is_in_live_mode'] ) : ?>style="display:none;"<?php endif; ?>
            data-icon="h">
            <h2 class="lp_plugin-mode-indicator__title"><?php esc_html_e(  'Test mode', 'laterpay' ); ?></h2>
            <span class="lp_plugin-mode-indicator__text"><?php printf( '%1$s <i> %2$s </i>', esc_html__( 'Earn money in', 'laterpay' ), esc_html__( 'live mode', 'laterpay' ) ); ?></span>
        </a>

        <?php
        // laterpay[account_obj] is instance of LaterPay_Controller_Admin_Account
        $laterpay['account_obj']->get_menu(); ?>

    </div>


    <div class="lp_pagewrap">

        <div class="lp_greybox lp_mt lp_mr lp_mb">
            <?php esc_html_e( 'The LaterPay plugin is in', 'laterpay' ); ?><div class="lp_toggle">
                <form id="laterpay_plugin_mode" method="post">
                    <input type="hidden" name="form"    value="laterpay_plugin_mode">
                    <input type="hidden" name="action"  value="laterpay_account">
                    <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_form' ); } ?>
                    <label class="lp_toggle__label">
                        <input type="checkbox"
                                id="lp_js_togglePluginMode"
                                class="lp_toggle__input"
                                name="plugin_is_in_live_mode"
                                value="1"
                                <?php if ( $laterpay['plugin_is_in_live_mode'] ) { echo 'checked'; } ?>>
                        <span class="lp_toggle__text" data-on="LIVE" data-off="TEST"></span>
                        <span class="lp_toggle__handle"></span>
                    </label>
                </form>
            </div><?php esc_html_e( 'mode.', 'laterpay' ); ?>
            <div id="lp_js_pluginVisibilitySetting"
                class="lp_inline-block"
                <?php if ( $laterpay['plugin_is_in_live_mode'] ) { echo ' style="display:none;"'; } ?>>
                <?php esc_html_e( 'It is invisible', 'laterpay' ); ?><div class="lp_toggle">
                    <form id="laterpay_test_mode" method="post">
                        <input type="hidden" name="form"    value="laterpay_test_mode">
                        <input type="hidden" name="action"  value="laterpay_account">
                        <input type="hidden" id="lp_js_hasInvalidSandboxCredentials" name="invalid_credentials" value="0">
                        <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_form' ); } ?>
                        <label class="lp_toggle__label lp_toggle__label-pass">
                            <input type="checkbox"
                                   id="lp_js_toggleVisibilityInTestMode"
                                   class="lp_toggle__input"
                                   name="plugin_is_in_visible_test_mode"
                                   value="1"
                                <?php if ( $laterpay['plugin_is_in_visible_test_mode'] ) { echo 'checked'; } ?>>
                            <span class="lp_toggle__text" data-on="" data-off=""></span>
                            <span class="lp_toggle__handle"></span>
                        </label>
                    </form>
                </div><?php esc_html_e(  'visible to visitors.', 'laterpay' ); ?>
            </div>
        </div>

        <div id="lp_js_apiCredentialsSection" class="lp_clearfix">

            <div class="lp_api-credentials lp_api-credentials--sandbox" data-icon="h">
                <fieldset class="lp_api-credentials__fieldset">
                    <legend class="lp_api-credentials__legend"><?php esc_html_e(  'Sandbox Environment', 'laterpay' ); ?></legend>

                    <dfn class="lp_api-credentials__hint">
                        <?php esc_html_e( 'for testing with simulated payments', 'laterpay' ); ?>
                    </dfn>

                    <ul class="lp_api-credentials__list">
                        <li class="lp_api-credentials__list-item">
                            <span class="lp_iconized-input" data-icon="i"></span>
                            <form id="laterpay_sandbox_merchant_id" method="post">
                                <input type="hidden" name="form"   value="laterpay_sandbox_merchant_id">
                                <input type="hidden" name="action" value="laterpay_account">
                                <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_form' ); } ?>

                                <input type="text"
                                    id="lp_js_sandboxMerchantId"
                                    class="lp_js_validateMerchantId lp_api-credentials__input"
                                    name="laterpay_sandbox_merchant_id"
                                    value="<?php echo esc_attr( $laterpay['sandbox_merchant_id'] ); ?>"
                                    maxlength="22"
                                    required>
                                <label for="laterpay_sandbox_merchant_id"
                                       alt="<?php esc_attr_e( 'Paste Sandbox Merchant ID here', 'laterpay' ); ?>"
                                       placeholder="<?php esc_attr_e( 'Merchant ID', 'laterpay' ); ?>">
                                </label>
                            </form>
                        </li>
                        <li class="lp_api-credentials__list-item">
                            <span class="lp_iconized-input" data-icon="j"></span>
                            <form id="laterpay_sandbox_api_key" method="post">
                                <input type="hidden" name="form"   value="laterpay_sandbox_api_key">
                                <input type="hidden" name="action" value="laterpay_account">
                                <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_form' ); } ?>

                                <input type="text"
                                    id="lp_js_sandboxApiKey"
                                    class="lp_js_validateApiKey lp_api-credentials__input"
                                    name="laterpay_sandbox_api_key"
                                    value="<?php echo esc_attr( $laterpay['sandbox_api_key'] ); ?>"
                                    maxlength="32"
                                    required>
                                <label for="laterpay_sandbox_api_key"
                                       alt="<?php esc_attr_e( 'Paste Sandbox API Key here', 'laterpay' ); ?>"
                                       placeholder="<?php esc_attr_e( 'API Key', 'laterpay' ); ?>">
                                </label>
                            </form>
                        </li>
                    </ul>

                </fieldset>
            </div>

            <div id="lp_js_liveCredentials"
                class="lp_api-credentials lp_api-credentials--live<?php if ( $laterpay['plugin_is_in_live_mode'] ) { echo ' lp_is-live'; } ?>"
                data-icon="k">
                <fieldset class="lp_api-credentials__fieldset">
                    <legend class="lp_api-credentials__legend"><?php esc_html_e( 'Live Environment', 'laterpay' ); ?></legend>

                    <dfn class="lp_api-credentials__hint">
                        <?php esc_html_e( 'for processing real financial transactions', 'laterpay' ); ?>
                    </dfn>

                    <ul class="lp_api-credentials__list">
                        <li class="lp_api-credentials__list-item">
                            <span class="lp_iconized-input" data-icon="i"></span>
                            <form id="laterpay_live_merchant_id" method="post">
                                <input type="hidden" name="form"   value="laterpay_live_merchant_id">
                                <input type="hidden" name="action" value="laterpay_account">
                                <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_form' ); } ?>

                                <input type="text"
                                    id="lp_js_liveMerchantId"
                                    class="lp_js_validateMerchantId lp_api-credentials__input"
                                    name="laterpay_live_merchant_id"
                                    value="<?php echo esc_attr( $laterpay['live_merchant_id'] ); ?>"
                                    maxlength="22"
                                    required>
                                <label for="laterpay_live_merchant_id"
                                    alt="<?php esc_attr_e( 'Paste Live Merchant ID here', 'laterpay' ); ?>"
                                    placeholder="<?php esc_attr_e( 'Merchant ID', 'laterpay' ); ?>">
                                </label>
                            </form>
                        </li>
                        <li class="lp_api-credentials__list-item">
                            <span class="lp_iconized-input" data-icon="j"></span>
                            <form id="laterpay_live_api_key" method="post">
                                <input type="hidden" name="form"    value="laterpay_live_api_key">
                                <input type="hidden" name="action"  value="laterpay_account">
                                <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_form' ); } ?>

                                <input type="text"
                                    id="lp_js_liveApiKey"
                                    class="lp_js_validateApiKey lp_api-credentials__input"
                                    name="laterpay_live_api_key"
                                    value="<?php echo esc_attr( $laterpay['live_api_key'] ); ?>"
                                    maxlength="32"
                                    required>
                                <label for="laterpay_sandbox_api_key"
                                    alt="<?php esc_attr_e( 'Paste Live API Key here', 'laterpay' ); ?>"
                                    placeholder="<?php esc_attr_e( 'API Key', 'laterpay' ); ?>">
                                </label>
                            </form>
                        </li>
                        <li class="lp_api-credentials__list-item">
                            <a href="#"
                               data-href-eu="<?php echo esc_url($laterpay['credentials_url_eu']);?>"
                               data-href-us="<?php echo esc_url($laterpay['credentials_url_us']);?>"
                                id="lp_js_showMerchantContracts"
                                class="button button-primary"
                                target="_blank"
                                <?php if ( ! empty( $laterpay['live_merchant_id'] ) && ! empty( $laterpay['live_api_key'] ) ) { echo 'style="display:none";'; } ?>>
                                <?php esc_html_e( 'Request Live API Credentials', 'laterpay' ); ?>
                            </a>
                        </li>
                    </ul>
                </fieldset>
            </div>
        </div>

        <div class="lp_clearfix">
            <fieldset class="lp_fieldset">
                <legend class="lp_legend"><?php esc_html_e( 'Region and Currency', 'laterpay' ); ?></legend>

                <p class="lp_bold"><?php esc_html_e( 'Select the region for your LaterPay merchant account', 'laterpay' ); ?></p>

                <p>
                    <dfn>
                        <?php esc_html_e( "Is the selling company or person based in Europe or in the United States?", "laterpay" ); ?>
                        <br>
                        <?php esc_html_e( "If you select 'Europe', all prices will be displayed and charged in Euro (EUR), and the plugin will connect to the LaterPay Europe platform.", "laterpay" ); ?>
                        <br>
                        <?php esc_html_e( "If you select 'United States', all prices will be displayed and charged in U.S. Dollar (USD), and the plugin will connect to the LaterPay U.S. platform.", "laterpay" ); ?>
                    </dfn>
                </p>

                <form id="laterpay_region" method="post">
                    <input type="hidden" name="form"    value="laterpay_region_change">
                    <input type="hidden" name="action"  value="laterpay_account">
                    <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_form' ); } ?>
                    <select id="lp_js_apiRegionSection" name="laterpay_region" class="lp_input">
                        <option value="eu" <?php selected( $laterpay['region'], 'eu' ); ?>><?php esc_html_e( 'Europe (EUR)', 'laterpay' ); ?></option>
                        <option value="us" <?php selected( $laterpay['region'], 'us' ); ?>><?php esc_html_e( 'United States (USD)', 'laterpay' ); ?></option>
                    </select>
                </form>

                <p id="lp_js_regionNotice" <?php if ( $laterpay['region'] === 'us' ) : ?>class="hidden"<?php endif; ?>>
                    <dfn class="lp_region_notice" data-icon="n">
                        <b>
                            <?php esc_html_e( "Important:", 'laterpay' ); ?>
                        </b>
                        <?php esc_html_e( " The minimum value for \"Pay Now\" prices in the U.S. region is", "laterpay" ); ?>
                        <b>
                            <?php esc_html_e( "$1.99", "laterpay" ); ?>
                        </b>
                        <br>
                        <?php esc_html_e( "If you have already set \"Pay Now\" prices lower than 1.99, make sure to change them before you switch to the U.S. region.", "laterpay" ); ?>
                        <br>
                        <?php esc_html_e( "If you haven't done any configuration yet, you can safely switch the region without further adjustments. ", "laterpay" ); ?>
                    </dfn>
                </p>
            </fieldset>
        </div>

    </div>

</div>

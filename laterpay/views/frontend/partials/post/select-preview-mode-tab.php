<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?>

<div id="lp_js_previewModeContainer" class="lp_post-preview-mode <?php if ( true === $laterpay['hide_preview_mode_pane'] ) { echo ' lp_is-hidden'; } ?>">
    <form id="lp_js_previewModeVisibilityForm" method="post">
    <input type="hidden" name="action" value="laterpay_preview_mode_visibility">
    <input type="hidden" id="lp_js_previewModeVisibilityInput" name="hide_preview_mode_pane" value="<?php echo (int)$laterpay['hide_preview_mode_pane'];?>">
    <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_form' ); } ?>
    </form>
    <a href="#" id="lp_js_togglePreviewModeVisibility" class="lp_post-preview-mode__visibility-toggle" data-icon="l"></a>
    <h2 class="lp_post-preview-mode__title" data-icon="a"><?php esc_html_e( 'Post Preview Mode', 'laterpay' ); ?></h2>
    <div class="lp_post-preview-mode__plugin-preview-mode">
        <?php esc_html_e( 'Preview post as', 'laterpay' ); ?> <strong><?php esc_html_e( 'Admin', 'laterpay' ); ?></strong>
        <div class="lp_toggle">
            <form id="lp_js_previewModeForm" method="post">
                <input type="hidden" name="action" value="laterpay_post_toggle_preview">
                <?php if ( function_exists( 'wp_nonce_field' ) ) { wp_nonce_field( 'laterpay_form' ); } ?>
                <label class="lp_toggle__label">
                    <input type="checkbox"
                            name="preview_post_checkbox"
                            id="lp_js_togglePreviewMode"
                            class="lp_toggle__input"
                            <?php if ( true === $laterpay['preview_post_as_visitor'] ) : ?>checked<?php endif; ?>>
                    <input type="hidden"
                            name="preview_post"
                            id="lp_js_previewModeInput"
                            value="<?php if ( true === $laterpay['preview_post_as_visitor']) { echo 1; } else { echo 0; } ?>">
                    <span class="lp_toggle__text" data-on="" data-off=""></span>
                    <span class="lp_toggle__handle"></span>
                </label>
            </form>
        </div>
        <strong><?php esc_html_e( 'Visitor', 'laterpay' ); ?></strong>
    </div>
</div>

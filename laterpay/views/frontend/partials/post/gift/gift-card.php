<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?>

<div class="lp_js_giftsWrapper" data-id="<?php echo esc_attr( $laterpay['selected_pass_id'] ); ?>">
    <?php foreach ( $laterpay['passes_list'] as $gift_pass ) : ?>
        <?php
            $this->render_gift_pass( $gift_pass, false, true );
        ?>
    <?php endforeach; ?>
</div>

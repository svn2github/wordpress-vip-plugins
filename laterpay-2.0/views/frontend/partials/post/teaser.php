<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?><div class="lp_teaser-content"><?php echo wp_kses_post( LaterPay_Helper_View::remove_extra_spaces( $laterpay['teaser_content'] ) ); ?></div>

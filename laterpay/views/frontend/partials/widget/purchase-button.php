<?php
/**
 * this template is used for do_action( 'laterpay_purchase_button' );
 */

if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}

/**
 * We can't use line-breaks in this template, otherwise wpautop() would add <br> before every attribute
 */

$args = array_merge( array(
    'href'                          => '#',
    'class'                         => 'lp_js_doPurchase lp_purchase-button',
    'title'                         => __( 'Buy now with LaterPay', 'laterpay' ),
    'data-icon'                     => 'b',
    'data-laterpay'                 => esc_url( $laterpay['link'] ),
    'data-post-id'                  => $laterpay['post_id'],
    ),
    $laterpay['attributes']
);
$whitelisted_attr = array(
    'href',
    'class',
    'title',
    'data-icon',
    'data-laterpay',
    'data-post-id',
    'data-preview-post-as-visitor',
);
/* translators: %1$s formatted price, %2$s currency tpye */
$link_text = sprintf( '%1$s<small class="lp_purchase-link__currency">%2$s</small>', esc_html( LaterPay_Helper_View::format_number( $laterpay['price'] ) ), esc_html( $laterpay['currency'] ) );

if ( isset( $laterpay['link_text'] ) ) {
    $link_text = $laterpay['link_text'];
    $link_text = str_replace( array('{price}', '{currency}'), array( LaterPay_Helper_View::format_number( $laterpay['price'] ), $laterpay['currency'] ), $link_text );
}
?>

<div><a <?php laterpay_whitelisted_attributes( $args, $whitelisted_attr ); ?>><?php echo wp_kses_post( $link_text ); // phpcs:ignore ?></a></div>
<div><a class="lp_bought_notification" href="<?php echo esc_url( $laterpay['identify_url'] ); ?>"><?php echo esc_html( $laterpay['notification_text'] ); ?></a></div>

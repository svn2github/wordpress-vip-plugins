<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?>

<?php
    // plugin menu pointer
$admin_menu_pointer = in_array( LaterPay_Controller_Admin::ADMIN_MENU_POINTER, $laterpay['pointers'], true );
if ( $admin_menu_pointer ) :
    $pointer_title = __( 'Welcome to LaterPay', 'laterpay' );
    $pointer_body = __( 'Set the most appropriate settings for you.', 'laterpay' );
?>
<script>
    jQuery(document).ready(function($) {
        if (typeof(jQuery().pointer) !== 'undefined') {
            jQuery('#toplevel_page_laterpay-plugin')
            .pointer({
                content : '<?php echo "<h3>" . esc_html( $pointer_title ) . "</h3><p>" . esc_html( $pointer_body ) . "</p>"?>',
                position: {
                    edge: 'left',
                    align: 'middle'
                },
                close: function() {
                    jQuery.post( ajaxurl, {
                        pointer: '<?php echo esc_html( LaterPay_Controller_Admin::ADMIN_MENU_POINTER ); ?>',
                        action: 'dismiss-wp-pointer'
                    });
                }
            })
            .pointer('open');
        }
    });
</script>
<?php endif; ?>
<?php
    // add / edit post page - pricing box pointer
$post_price_box_pointer = in_array( LaterPay_Controller_Admin::POST_PRICE_BOX_POINTER, $laterpay['pointers'], true );
if ( $post_price_box_pointer ) :
    $pointer_title  = __( 'Set a Price for this Post', 'laterpay' );
    $pointer_body_1 = __( 'Set an', 'laterpay' );
    $pointer_body_2 = __( ' individual price ', 'laterpay' );
    $pointer_body_3 = __( 'for this post here. ', 'laterpay' );
    $pointer_body_4 = __( 'You can also apply', 'laterpay' );
    $pointer_body_5 = __( ' advanced pricing ', 'laterpay' );
    $pointer_body_6 = __( 'by defining how the price changes over time.', 'laterpay' );
?>
<script>
    jQuery(document).ready(function($) {
        if (typeof(jQuery().pointer) !== 'undefined') {
            jQuery('#lp_post-pricing')
            .pointer({
                content: '<?php printf( "<h3>%s</h3><p>%s <strong>%s</strong>.<br>%s<strong>%s</strong>%s</p>", esc_html( $pointer_title ), esc_html( $pointer_body_1 ), esc_html( $pointer_body_2 ), esc_html( $pointer_body_3 ), esc_html( $pointer_body_4 ), esc_html( $pointer_body_5 ), esc_html( $pointer_body_6 ) ); ?>',
                position: {
                    edge: 'top',
                    align: 'middle'
                },
                close: function() {
                    jQuery.post( ajaxurl, {
                        pointer: '<?php echo esc_html( LaterPay_Controller_Admin::POST_PRICE_BOX_POINTER ); ?>',
                        action: 'dismiss-wp-pointer'
                    });
                }
            })
            .pointer('open');
        }
    });
</script>
<?php endif; ?>
<?php
    // add / edit post page - teaser content pointer
$post_teaser_content_pointer = in_array( LaterPay_Controller_Admin::POST_TEASER_CONTENT_POINTER, $laterpay['pointers'], true);
if ( $post_teaser_content_pointer ) :
    $pointer_title = __( 'Add Teaser Content', 'laterpay' );
    $pointer_body  = __( 'You´ll give your users a better impression of what they´ll buy, if you preview some text, images, or video from the actual post.', 'laterpay' );
    ?>
<script>
    jQuery(document).ready(function($) {
        if (typeof(jQuery().pointer) !== 'undefined') {
            jQuery('#lp_post-teaser')
            .pointer({
                content: '<?php echo "<h3>" . esc_html( $pointer_title ) . "</h3><p>" . esc_html( $pointer_body ) . "</p>" ?>',
                position: {
                    edge: 'bottom',
                    align: 'left'
                },
                close: function() {
                    jQuery.post( ajaxurl, {
                        pointer: '<?php echo esc_html( LaterPay_Controller_Admin::POST_TEASER_CONTENT_POINTER ); ?>',
                        action: 'dismiss-wp-pointer'
                    });
                }
            })
            .pointer('open');
        }
    });
</script>
<?php endif; ?>

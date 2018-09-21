<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?>

<ul class="lp_navigation-tabs">
<?php foreach ( $laterpay['menu'] as $page ) : ?>
    <?php if ( ! current_user_can( $page['cap'] ) ) : ?>
        <?php continue; ?>
    <?php endif; ?>
        <?php
            $is_current_page    = false;
            $current_page_class = '';
        ?>
    <?php if ( $laterpay['current_page'] === $page['url'] ) : ?>
        <?php
            $is_current_page    = true;
            $current_page_class = 'lp_is-current';
        ?>
    <?php endif; ?>
    <li class="lp_navigation-tabs__item <?php echo esc_attr( $current_page_class ); ?>">
        <?php
        $allow_html = array(
            'a' => array(
                'href'  => array(),
                'class' => array(),
                'data'  => array(),
            ),
        );
        echo wp_kses( LaterPay_Helper_View::get_admin_menu_link( $page ), $allow_html );
        ?>
        <?php if ( isset( $page['submenu'] ) ) : ?>
            <ul class="lp_navigation-tabs__submenu">
                <li class="lp_navigation-tabs__item">
                    <?php echo wp_kses( LaterPay_Helper_View::get_admin_menu_link( $page['submenu'] ), $allow_html ); ?>
                </li>
            </ul>
        <?php endif; ?>
    </li>
<?php endforeach; ?>
</ul>

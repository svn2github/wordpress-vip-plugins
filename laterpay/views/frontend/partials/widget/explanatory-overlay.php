<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
?>

<div class="lp_paid-content">
    <div class="lp_full-content">
        <?php echo wp_kses_post( $overlay['teaser'] ); ?>
        <br>
        <?php esc_html_e( 'Thanks for reading this short excerpt from the paid post! Fancy buying it to read all of it?', 'laterpay' ); ?>
    </div>

    <?php $overlay_data = $overlay['data']; ?>
    <div class="lp_overlay-text">
        <div class="lp_benefits">
            <header class="lp_benefits__header">
                <h2 class="lp_benefits__title">
                    <?php echo esc_html( $overlay_data['title'] ); ?>
                </h2>
            </header>
            <ul class="lp_benefits__list">
                <?php foreach ( $overlay_data['benefits'] as $benefit ) : ?>
                    <li class="lp_benefits__list-item <?php echo esc_attr( $benefit['class'] ); ?>">
                        <h3 class="lp_benefit__title">
                            <?php echo esc_html( $benefit['title'] ); ?>
                        </h3>
                        <p class="lp_benefit__text">
                            <?php echo wp_kses( $benefit['text'], [ 'br' => [] ] ); ?>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="lp_benefits__action">
                <?php
                    // ignoring this because generated html is escaped in,
                    // views/frontend/partials/widget/purchase-button.php
                echo wp_kses( $overlay_data['action_html_escaped'], [
                    'div' => [
                        'class' => [],
                    ],
                    'a' => [
                        'href' => [],
                        'class' => [],
                        'title' => [],
                        'data-icon' => [],
                        'data-laterpay' => [],
                        'data-post-id' => [],
                        'data-preview-post-as-visitor' => [],
                    ],
                    'small' => [
                        'class' => [],
                    ],
                ] );
                ?>
            </div>
            <div class="lp_powered-by">
                powered by<span data-icon="a"></span>
            </div>
        </div>
    </div>

</div>

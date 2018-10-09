<?php
if ( get_option( 'tm_coschedule_token' ) ) {
    if ( current_user_can( 'edit_posts' ) ) {
        $url = "https://app.coschedule.com/#/authenticate?calendarID=" . rawurlencode( get_option( 'tm_coschedule_calendar_id' ) );
        $url .= "&wordpressSiteID=" . rawurlencode( get_option( 'tm_coschedule_wordpress_site_id' ) );
        $url .= "&redirect=" . $redirect . "&build=" . $this->build;
        $url .= "&userID=" . $this->current_user_id;

        $userToken = '';
        if ( isset( $_GET['tm_cos_user_token'] ) ) {
            $userToken = sanitize_text_field( $_GET['tm_cos_user_token'] );
        }

        if ( isset( $userToken ) && ! empty( $userToken ) ) {
            $url .= '&userToken=' . rawurlencode( $userToken );
        }

        ?>

        <iframe id="CoSiFrame" src="<?php echo esc_url( $url ); ?>" width="100%" style="border:none; margin-left: -20px; width: calc(100% + 20px)"></iframe>

        <script>
            jQuery(document).ready(function ($) {
                $('.update-nag').remove();
                $('#wpfooter').remove();
                $('#wpwrap').find('#footer').remove();
                $('#wpbody-content').css('paddingBottom', 0);

                $('#CoSiFrame').css('min-height', $('#wpbody').height());

                var resize = function () {
                    var p = $(window).height() - $('#wpadminbar').height() - 4;
                    $('#CoSiFrame').height(p);
                };

                resize();
                $(window).resize(function () {
                    resize();
                });
            });
        </script>
        <?php
    } else {
        include( plugin_dir_path( __FILE__ ) . '_access-denied.php' );
    }
} else {
    include( plugin_dir_path( __FILE__ ) . '_missing-token.php' );
}

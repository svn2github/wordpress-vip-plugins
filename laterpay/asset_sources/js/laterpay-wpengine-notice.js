/*global lp_i18n, wpengine_cookie_done_nonce */

(function ( $ ) {
    $( function () {

        var wpengine_notice_btn = jQuery( '#wpengn_done_btn' );
        var wpengineNoticeBox   = jQuery( '#lp_wpengine_notice' );

        wpengine_notice_btn.on( 'click', function () {

            wpengineNoticeBox.removeClass( 'notice-error' ).addClass( 'notice-info' );

            $( '<img />' )
                .attr( 'id', 'wpengine-loader' )
                .attr( 'src', '/wp-admin/images/loading.gif' )
                .appendTo( wpengineNoticeBox );
            wpengineNoticeBox.append( '  '+lp_i18n.SaveWpNoticeData );

            saveWpStatus();

        } );

        function saveWpStatus() {

            var data = {
                action  : 'laterpay_save_wpengine_status',
                security: wpengine_cookie_done_nonce,
                status  : 'true',
            };

            $.post( ajaxurl, data, function ( response ) {

                if ( $.type( response ) === 'string' ) {
                    response = JSON.parse( response );
                }

                if ( response.status === true ) {

                    wpengineNoticeBox.removeClass( 'notice-info' ).addClass( 'notice-success' );
                    wpengineNoticeBox.html( '' ).append( lp_i18n.SavedWpNoticeData );
                    wpengineNoticeBox.delay(1000).fadeOut();
                } else {

                    wpengineNoticeBox.removeClass( 'notice-info' ).addClass( 'notice-error' );
                    wpengineNoticeBox.html( '' ).append( lp_i18n.UnSavedWpNoticeData );
                    wpengineNoticeBox.delay(1000).fadeOut();
                }

            } );
        }
    } );
})( jQuery );

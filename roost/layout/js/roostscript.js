(function( $ ) {
    var $roostAdmin = $( '.roost-admin-section' );
    $roostAdmin.hide();
    $( '#roost-activity' ).show();
    $( '#roost-tabs li' ).on( 'click', function() {
        $( this ).parent().find( '.active' ).removeClass( 'active' );
        $( this ).addClass( 'active' );
        var index = $( this ).index();
        if( 0 === index ) {
            $roostAdmin.hide();
            $( '#roost-activity' ).show();
        } else if ( 1 === index ) {
            $roostAdmin.hide();
            $( '#roost-manual-push' ).show();
        } else {
            $roostAdmin.hide();
            $( '#roost-settings' ).show();
        }
    });
    var roostInput = $( '#roost-manual-note' );
    var roostCount = $( '#roost-manual-note-count-int' );
    var roostLimit = 70;
    roostInput.keyup( function() {
        var n = this.value.replace( /{.*?}/g, '' ).length;
        if ( n > ( roostLimit - 11 ) ){
            if( ! roostCount.hasClass( 'roostWarning' ) ) {
                roostCount.addClass( 'roostWarning' );
            }
        } else if ( n < roostLimit - 10 ) {
            if( roostCount.hasClass( 'roostWarning' ) ) {
                roostCount.removeClass( 'roostWarning' );
            }
        }
        roostCount.text( 0 + n );
    }).triggerHandler( 'keyup' );
    $( '#roost-advanced-settings-control' ).on('click', function (e) {
        e.preventDefault();
        if ( 'true' === $( this ).data( 'roost-advanced-settings' ) ) {
            $( this ).data( 'roost-advanced-settings', 'false' );
            $( this ).text('Show Advanced Settings');
        } else {
            $( this ).data( 'roost-advanced-settings', 'true' );
            $( this ).text('Hide Advanced Settings');
        }
        $( '#roost-advanced-settings' ).toggle();
    });
    if ( $( '#roost-push-control' ).is( ':checked' ) ) {
        $( '#roost-available-categories' ).css( 'display', 'inline-block' ).show();
    }
    $( '#roost-push-control' ).change( function () {
        $( '#roost-available-categories' ).slideToggle( 100 );
    });
    $( '#roost-prompt-min' ).change( function () {
        $( '#roost-min-visits' ).attr( 'disabled', ! this.checked );
    });
    if ( $( '#roost-prompt-event' ).is( ':checked' ) ) {
        $( '#roost-event-hints' ).css( 'display', 'inline-block' ).show();
        $( '#roost-event-hints-disclaimer' ).show();
    }
    $( '#roost-prompt-event' ).change( function () {
        $( '#roost-event-hints' ).slideToggle( 100, function(){
            $( '#roost-event-hints-disclaimer' ).toggle();
        });
    });
    $( '#roost-segment-send' ).change( function () {
        if ( $( '#roost-segment-send' ).is( ':checked' ) ) {
            var confirmSegments = confirm('Are you sure you want to do this?\n\nIf you are not explicitly assigning user segments, no notifications will be sent.');
            if ( false === confirmSegments ) {
                $( '#roost-segment-send' ).prop( 'checked', false );
                return;
            }
        }
    });
    if ( $( '#roost-use-custom-script' ).is( ':checked' ) ) {
        $( '#roost-custom-script' ).css( 'display', 'inline-block' ).show();
    }
    $( '#roost-use-custom-script' ).change( function () {
        if ( $( '#roost-use-custom-script' ).is( ':checked' ) ) {
            var confirmScript = confirm('Are you sure you want to do this?\n\nEnabling this feature will not include the normal Roost.js, and instead include what you paste in the custom script box that will be shown.');
            if ( false === confirmScript ) {
                $( '#roost-use-custom-script' ).prop( 'checked', false );
                return;
            }
        }
        $( '#roost-custom-script' ).slideToggle( 100 );
    });
})( jQuery );

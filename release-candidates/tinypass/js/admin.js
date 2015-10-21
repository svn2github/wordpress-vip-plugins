jQuery( function ( $ ) {
	$( '.tinypass-dynamic-display' ).each( function () {
		var input = $( this );
		var rel = $( input.attr( 'rel' ) );

		input.change( function () {
			if ( input.is( ':radio' ) && ! input.is( ':checked' ) ) {
				return;
			}
			var val = $( input ).val();
			if ( input.is( ':checkbox' ) && ! input.is( ':checked' ) ) {
				val = null;
			}
			var requiredVals = $( input ).attr( 'tinypass-dynamic-display' ).split( '|' );
			var show = false;
			if ( $.inArray( val, requiredVals ) >= 0 ) {
				rel.show();
			}
			else {
				rel.hide();
			}
		} ).trigger( 'change' );

	} );

	$( '.tinypass-subscription-selector' ).each( function () {
		var input = $( this );

		var tinypassSubscriptionSelectorChange = function () {
			var input = $( this );
			var rel = $( input.attr( 'rel' ) );
			if ( input.is( ':checked' ) ) {
				var checkboxes = $( ':checkbox', rel );
				var notice = $( '.tinypass-no-resources-notice', rel );
				if ( (
				     checkboxes.length == 1
				     ) && (
				     0 == notice
				     ) ) {
					checkboxes.prop( 'checked', true );
					rel.hide();
				}
				else {
					rel.show();
				}
			}
			else {
				rel.hide();
			}
		};
		input.change( function () {
			$( '.tinypass-subscription-selector' ).each( tinypassSubscriptionSelectorChange )
		} ).trigger( 'change' );
	} );
} );
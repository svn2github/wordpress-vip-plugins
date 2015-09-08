(function ( $, window, undefined ) {
	'use strict';

	$( '.share-url-button' ).click(function ( e ) {
		e.preventDefault();
		$( this ).siblings( '.apple-share-url' ).toggle();
	});

	$( '.row-actions' ).mouseenter (function () {
		$( this ).addClass( 'is-active' );
	});

	$( '.row-actions' ).mouseleave(function () {
		$( this ).removeClass( 'is-active' );
	});

})( jQuery, window );

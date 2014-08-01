jQuery( document ).ready( function( $ ) {
	$( '.column-subheading' ).hide();
	$( 'td.subheading' ).each( function() {
		$( 'td.post-title:first', $( this ).parents( 'tr' ) )
			.children(':first')
			.after($(this).html());
	} );

	function checkbox_handler() {
		$( '#subheading_before, #subheading_after' ).parent().css(
			'display',
			( $( this ).is( ':checked' ) ? '' : 'none' )
		);
	}
	$( '#subheading_append' ).click( checkbox_handler );

	checkbox_handler();
} );
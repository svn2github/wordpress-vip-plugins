(function ($) {

	$(document).ready(function () {
		$( '.select2' ).select2();
		appleNewsSettingsSortInit( '#meta-component-order-sort', 'meta_component_order' );
	});

	function appleNewsSettingsSortInit( selector, key ) {
		$( selector ).sortable( {
			'stop' : function( event, ui ) {
				appleNewsSettingsSortUpdate( $( this ), key );
			},
		} );
   	$( selector ).disableSelection();
   	appleNewsSettingsSortUpdate( $( selector ), key );
	}

	function appleNewsSettingsSortUpdate( $sortableElement, keyPrefix ) {
		// Build the key for field
		var key = keyPrefix + '[]';

		// Remove any current values
		$( 'input[name="' + key + '"]' ).remove();

		// Create a hidden form field with the values of the sortable element
		var values = $sortableElement.sortable( 'toArray' );
		if ( values.length > 0 ) {
			$.each( values.reverse(), function( index, value ) {
				$hidden = $( '<input>' )
					.attr( 'type', 'hidden' )
					.attr( 'name', key )
					.attr( 'value', value );

				$sortableElement.after( $hidden );
			} );
		}
	}

}( jQuery ) );

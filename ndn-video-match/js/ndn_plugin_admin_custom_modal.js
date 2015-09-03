( function($) {
  'use strict';

  jQuery( document ).ready( function() {
    // Google Analytics
    jQuery( '.ndn-search-query-input' ).on( 'change', ndnGAKeyEvent );
    jQuery( '.ndn-change-settings' ).on( 'click', parent.ndnGAClickEvent );
    jQuery( 'form[name="ndn-search"]' ).on( 'submit', parent.ndnGASubmitEvent );
  });

  /**
  * Attach a input change event listener for GA
   */
  function ndnGAKeyEvent() {
    var input,
      category,
      label,
      value;

    /*jshint validthis:true */
    input = $( this );
    category = input.attr( 'analytics-category' );
    label = input.attr( 'analytics-label' );
    value = input.val();

    ga('send', 'event', category, 'input', label);
  }
})( jQuery );

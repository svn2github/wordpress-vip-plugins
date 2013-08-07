(function ($) {
	"use strict";
	$(function () {

		// when a user clicks subscribe
		$("#sailthru-add-subscriber-form").submit( function( e ){

			e.preventDefault();
			var user_input = $(this).serialize();
				
			$.post(
				ajaxurl,
				user_input,
				function(data) {
					data = jQuery.parseJSON(data);
					console.log(data);
					if( data.error == true ) {
						$("#sailthru-add-subscriber-errors").html(data.message);
					} else {
						$("#sailthru-add-subscriber-form").html('Thank you for subscribing.');
					}
			  		
				}
			);				

		});


	});
}(jQuery));
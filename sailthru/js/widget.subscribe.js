(function ($) {
	"use strict";
	$(function () {
		$('#sailthru-modal').hide();
		$( "#show_shortcode" ).on( "click", function( e ) {
			e.preventDefault();
			$('#sailthru-modal').toggle();
		});

		$('#sailthru-modal .sailthru-signup-widget-close').click(function(){
			$('#sailthru-modal ').fadeOut();
		}) ;
		// when a user clicks subscribe
		$(".sailthru-add-subscriber-form").submit( function( e ){

			e.preventDefault();
			var user_input = $(this).serialize();
			var form = $(this);
			$.post(
				ajaxurl,
				user_input,
				function(data) {
					data = jQuery.parseJSON(data);
					console.log(data);
					if( data.error == true ) {
						$(".sailthru-add-subscriber-errors").html(data.message);
					} else {
						$('#sailthru-modal .sailthru-signup-widget-close').fadeIn();
						$(form).html('');
						$(form).parent().find(".success").show();
					}
				}
			);

		});


	});
}(jQuery));

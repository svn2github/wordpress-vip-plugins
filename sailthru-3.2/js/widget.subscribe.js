(function ($) {
	"use strict";
	$(function () {
		$('#sailthru-modal').hide();

		$( ".show_shortcode" ).on( "click", function( e ) {
			e.preventDefault();
			var posTop = $(this).offset().top;
			var modal = $( "#sailthru-modal");
   		 	modal.css("top", '100px');
   		 	modal.css("left", Math.max(0, (($(window).width() - $(modal).outerWidth()) / 2) + $(window).scrollLeft()) + "px");

   		 	$('.sailthru_shortcode_hidden .sailthru-signup-widget-close').show();
   		 	modal.fadeIn();

		});

		$('#sailthru-modal .sailthru-signup-widget-close').click(function(){
			$('#sailthru-modal ').fadeOut();
		}) ;

		// when a user clicks subscribe
		$(".sailthru-add-subscriber-form").submit( function( e ){

			e.preventDefault();
			var user_input = $(this).serialize();
			var form = $(this);
			$.ajax({
                url: sailthru_vars.ajaxurl,
                type: 'post',
                data: user_input,
                dataType: "json",
                xhrFields: {
                    withCredentials: true
                },
                success: function (data, status) {
                    if (data.success == false) {
                        $('#' + form.attr('id') + " .sailthru-add-subscriber-errors").html(data.message);
                    } else {
                        $('#sailthru-modal .sailthru-signup-widget-close').fadeIn();
                        $(form).html('');
                        $(form).parent().find(".success").show();
                    }
                }
            } );

		});


	});
}(jQuery));

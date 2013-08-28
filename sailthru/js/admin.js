(function ($) {
	"use strict";
	$(function () {

		// show/hide the form to add the user's API key/secret
		$("#sailthru-add-api-key").click( function(e) {
			e.preventDefault();
			$("#sailthru-add-api-key-form").toggle(600);
		});



		// validate the form for saving api keys
		$("#sailthru-add-api-key-form").submit( function(e)
		{

			var isFormValid = true;

			$("input").each( function() {

				if ($.trim($(this).val()).length == 0){

					$(this).addClass("error-highlight");
					isFormValid = false;
					e.preventDefault();

				} else{

					$(this).removeClass("error-highlight");
					isFormValid = true;
				}
			});

			return isFormValid;

		}); // end validate form submit



		// add a subscriber
		$("#sailthru-add-subscriber-form").submit( function(e) {

			e.preventDefault();

		});


		// set up form. make the email template more prominent
		$("#sailthru_setup_email_template").parents('tr').addClass('grayBorder');

		// datepicker for meta box
		$('.datepicker').datepicker({
        	dateFormat : 'mm-dd-yy'
    	});


	});
}(jQuery));

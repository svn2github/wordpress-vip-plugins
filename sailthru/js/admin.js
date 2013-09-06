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
			var sailthru_fields = new Array("sailthru_api_key", "sailthru_api_secret", "sailthru_horizon_domain");

			for (var i = 0; i < sailthru_fields.length; i++) {
				var field = '#'+sailthru_fields[i];

   				 if ($.trim($(field).val()).length == 0){
   				 	$(field).addClass("error-highlight");
					isFormValid = false;
					e.preventDefault();
   				 } else{
					$(field).removeClass("error-highlight");
					isFormValid = true;
				}
			}

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

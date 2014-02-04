//on doc ready
(function ($) {
	//namespace enclose jquery
	$(
		function () {
			//will execute on dom ready
			if (localStorage && localStorage.getItem("janrainCaptureToken")) {
				// logged in so configure link to logout (default render of link is logged out state)
				//logout links
				$('a.janrainShortcode').addClass('capture_end_session');
				//logout custom blocks
				$('div.janrainShortcode').each(
					function ()  {
						$(this).on('click',
							function () {
								janrain.capture.ui.endCaptureSession();
							});
					});
				//all logout  behaviors
				$('.janrainShortcode')
					.removeClass('capture_modal_open')
					.on('click',
						function () {
							window.location.href = $(this).data('afterlogout');
						});
				$('.janrainShortcodeLoginContent').hide();
				$('.janrainShortcodeLogoutContent').show();
			}
		});
})(jQuery);

function updateCountdown() {
	var count = sf_l10n['max'] - jQuery('#sf-text').val().length;
	if(count<0) {
		jQuery('#sf_char_count').addClass('sf-count-error');
		jQuery('#sf-post input[type=submit]').attr('disabled', 'disabled')
	} else {
		jQuery('#sf_char_count').removeClass('sf-count-error');
		jQuery('#sf-post input[type=submit]').removeAttr('disabled');
	}

    jQuery('#sf_char_count').text(count);
}

jQuery(document).ready(function($) {
    updateCountdown();
    $('#sf-text').change(updateCountdown);
    $('#sf-text').keyup(updateCountdown);

	$('.shorten-links').click(function() {
		$('#shorten-links #ajax-loading').css('visibility','visible');
		var sf_message = $('#sf-text').val();
		var sf_account = $("input:radio[name^='socialflow[account]']:checked").val();
		var data = {
			action: 'sf-shorten-msg',
			sf_account: sf_account,
			sf_message: sf_message
		};
		$.post(ajaxurl, data, function(response) {
			$('#sf-text').val(response);
			$('#shorten-links #ajax-loading').css('visibility','hidden');
			$('#sf-text').trigger('keyup');
		});
	});

	$('#title').keyup(function() {
		$('#sf-text').val( $('#title').val() );
		$('#sf-text').trigger('keyup');
	});
});

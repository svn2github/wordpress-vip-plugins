/**
 * Drive UI on the Ooyala Settings screen
 */
jQuery(function($) {
	var $ooyala = $('#ooyala');

	// Switch out the version class on the form table so we can style
	// sections
	$ooyala
		.on('change', '.ooyala-player-version', function() {
			var val = $ooyala.find('.ooyala-player-version:checked').val();

			$ooyala.attr('class', $ooyala.attr('class').replace(/ooyala-v[34]-settings/, 'ooyala-' + val + '-settings'));
		})

		// Validate JSON fields
		.on('change', '.ooyala-raw-json', function() {
			var $input = $(this)
			  , $json = $input.siblings('.ooyala-json')
			  , value = $input.val().trim()
			  , json = false
			;

			if(!value) {
				$json.val('');
			}
			else try {
				// encapsulating braces are optional
				if(!/^\s*\{[\s\S]*\}\s*$/.test(value)) {
					value = '{' + value + '}';
				}

				// eval(), with justification:
				// Yes, this could execute some arbitrary code, but this only happening in the context of
				// wp-admin, as a means to see if this is a 'plain' JS object or string of JSON.
				// This will prevent the user from inadvertantly passing arbitrary code to the shortcode,
				// which in turn would put it right into a script tag ending up on the front end.
				value = eval('(' + value + ')');

				// arrays, or primitives need not apply. allow empty objects so we can put templates
				// in that result in empty objects.
				if(typeof value == 'object' && !Array.isArray(value)) {
					json = JSON.stringify(value);

					$json.val(json);
				}
				else {
					$json.val('');
				}
			} catch(e) {
				// some error along the way...not valid JSON or JS object
				json = false;
			}

			$input.toggleClass('textarea-invalid', !!value && !json);
		})

		// Loosely validate CSS
		.on('change', '.ooyala-custom-css', function() {
			var $input = $(this)
			  , value = $input.val().trim()
			;

			$input.toggleClass('textarea-invalid', value && !value.match(new RegExp(ooyala.cssPattern)));
		})

		// Show pulse options when pulse is selected
		.on('change', 'input[name="ooyala[ad_plugin]"]', function() {
			$('.ad_plugin-pulse-options').toggle($ooyala.find('input[name="ooyala[ad_plugin]"]:checked').val() === 'pulse');
		})
	;

	// Trigger form updates
	$ooyala.find('input[name="ooyala[ad_plugin]"]').first().trigger('change');
	$ooyala.find('.ooyala-raw-json').trigger('change');

	//Delete alternate account
	$ooyala.on('click', '.delete-account', function(ev) {
		$(ev.target).closest('.alt-accounts-wrap').remove();
	});

	//Add alternate account
	var i = 0
	  , add_template = $ooyala.find('#ooyala_add_account_template').html()
	  , $altAccountsHeading = $ooyala.find('#alt-accounts-heading')
	;

	$ooyala.find('#add-account').click(function() {
		var elem = add_template.replace(/%d/g, i++);
		$altAccountsHeading.after(elem);
	});

});

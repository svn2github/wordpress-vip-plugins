/**
 * Drive UI on the Ooyala Settings screen
 */
jQuery(function($) {
	var $ooyala = $('#ooyala');

	/**
	 * Attempt to parse a string as either a JSON object or JS object literal notation.
	 * @param  {String} value  object string to parse, surrounding braces {} are optional
	 * @param {Boolean} [rawObject]  return the actual object instead of JSON string
	 * @return {Object|String|false}     object, stringified JSON of object (empty string if empty input), false if not a valid value
	 */
	function parseObject(value, rawObject) {
		value = value.trim();

		if(!value) {
			return rawObject ? {} : '';
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
				return rawObject ? value : JSON.stringify(value);
			}
			else {
				// valid syntax but does not contain an object
				return rawObject ? {} : '';
			}
		} catch(e) {
			// some error along the way...not valid JSON or JS object
			return false;
		}
	}

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
			  , value = $input.val()
			  , json = parseObject(value)
			;

			if (typeof json === 'string') {
				$json.val(json);
			}

			$input.toggleClass('textarea-invalid', !!value && !json);
		})

		// Loosely validate CSS
		.on('change', '.ooyala-custom-css', function() {
			var $input = $(this)
			  , value = $input.val().trim()
			;

			$input.toggleClass('textarea-invalid', !!value && !value.match(new RegExp(ooyala.cssPattern)));
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

	// the tab content panels
	var $tabPanels = $('.ooyala-tab-container')
	  , $tabLinks = $('.js--ooyala-tab-link')
	  , $form = $('#submit').closest('form')
	;

	// switch tab on click
	$('.ooyala-tabs').on('click', '.js--ooyala-tab-link', function(ev) {
		var $el = $(ev.target);

		ev.preventDefault();

		// this tab is already active or has validation errors
		if ($el.hasClass('active') || !$form[0].reportValidity()) {
			return;
		}

		// hide all tabs
		$tabPanels.removeClass('active');
		// show the new active tab
		$(document.getElementById($el.attr('href').substr(1))).addClass('active')
			.find('.js--ooyala-plugin-enable').change(); // trigger the enabled checkbox to set up fields properly

		// update active state of tab links
		$tabLinks.removeClass('active');
		$el.addClass('active');
	});

	// select the first tab @todo - select the first enabled plugin?
	$tabLinks.first().click();

	// enable/disable settings form based on "enable" checkbox
	$('.ooyala-analytics-wrapper').on('change', '.js--ooyala-plugin-enable', function(ev) {
		var $el = $(this)
		  , $table = $el.closest('.ooyala-tab-container').find('.ooyala-settings')
		;

		$table.toggleClass('enabled', $el.is(':checked'));

		if ($el.is(':checked')) {
			$table.find('[data-required]').attr({required: 'required', 'aria-disabled': 'true'});
		} else {
			$table.find('[data-required]').removeAttr('required aria-disabled');
		}
	});

	// toggle the advanced object editor field
	$('.ooyala-advanced-params-wrapper .js--toggle-advanced').on('click', function(ev) {
		ev.preventDefault();
		$(this).closest('.ooyala-advanced-params-wrapper').toggleClass('js--shown');
	});

	// set up the analytics object editors
	$('.js--analytics-object').each(function() {
		var $textarea = $(this)
		  , $tab = $textarea.closest('.ooyala-tab-container')
		  , $objectFields = $tab.find('[data-obj-property]')
		  , currObj
		;

		// update the object field with the currObj value
		function updateObjectField() {
			// pretty-format the JSON string
			$textarea.val(JSON.stringify(currObj, null, '  '));
		}

		// handle changes to raw object on blur
		$textarea.on('blur', function() {
			var obj = parseObject($textarea.val(), true);

			// new valid object?
			if (obj) {
				// update the accompanying fields
				$objectFields.each(function() {
					var $el = $(this);

					if ($el.attr('type') === 'radio') {
						var $els = $(document.getElementsByName($el.attr('name')));

						if (typeof obj[$el.data('obj-property')] !== 'undefined') {
							$els.filter(obj[$el.data('obj-property')] ? '[value=true]' : '[value=false]').prop('checked', true);
						} else {
							$els.prop('checked', false)
								.filter('[value=default]').prop('checked', true);
						}
					} else {
						$el.val(obj[$el.data('obj-property')]);
					}
				});

				currObj = obj;
				updateObjectField();
				$textarea.removeClass('textarea-invalid');
			} else {
				// do not change the currentObject if we have a syntax issue so it can be corrected without losing data
				$textarea.addClass('textarea-invalid');
			}
		});

		// initialize the field and the currObj variable
		$textarea.blur();

		// update raw object field on text input change
		$objectFields.filter('[type=text]').on('input', function() {
			var $el = $(this);

			// delete the property from the object if its value is blank
			if (!$el.val()) {
				delete currObj[$el.data('obj-property')];
			} else {
				currObj[$el.data('obj-property')] = $el.val();
			}

			updateObjectField();
		});

		// update raw object field on radio input change
		$objectFields.filter('[type=radio]').on('change', function() {
			var $el = $(this);

			switch ($(document.getElementsByName($el.attr('name'))).filter(':checked').val()) {
				case 'true':
					currObj[$el.data('obj-property')] = true;
					break;

				case 'false':
					currObj[$el.data('obj-property')] = false;
					break;

				default:
					delete currObj[$el.data('obj-property')];
			}

			updateObjectField();
		});
	});

	// ensure we don't submit with mal-formed JSON
	$form.on('submit', function(ev) {
		var $invalid = $('.textarea-invalid');

		if ($invalid.length) {
			alert('One or more JSON object fields contain syntax errors. Please correct these before saving.');
			ev.preventDefault();
			$invalid.first().focus();
		}
	});

});

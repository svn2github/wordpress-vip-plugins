(function($) {$(function() {

    // encapsulate all LaterPay Javascript in function laterPayBackendAccount
    function laterPayBackendAccount() {
        var $o = {
                // API credentials
                apiKeyInput                     : $('.lp_js_validateApiKey'),
                merchantIdInput                 : $('.lp_js_validateMerchantId'),
                apiCredentialsInputs            : $('.lp_js_validateApiKey, .lp_js_validateMerchantId'),
                testMerchantId                  : $('#lp_js_sandboxMerchantId'),
                testApiKey                      : $('#lp_js_sandboxApiKey'),
                liveMerchantId                  : $('#lp_js_liveMerchantId'),
                liveApiKey                      : $('#lp_js_liveApiKey'),

                // plugin mode
                pluginModeIndicator             : $('#lp_js_pluginModeIndicator'),
                pluginModeToggle                : $('#lp_js_togglePluginMode'),
                pluginVisibilitySetting         : $('#lp_js_pluginVisibilitySetting'),
                pluginVisibilityToggle          : $('#lp_js_toggleVisibilityInTestMode'),
                hasInvalidSandboxCredentials    : $('#lp_js_hasInvalidSandboxCredentials'),
                isLive                          : 'lp_is-live',
                navigation                      : $('.lp_navigation'),

                region                          : $('#lp_js_apiRegionSection'),
                regionNoticeBlock               : $('#lp_js_regionNotice'),

                showMerchantContractsButton     : $('#lp_js_showMerchantContracts'),
                apiCredentials                  : $('#lp_js_apiCredentialsSection'),
                requestSent                     : false
            },

            regionVal = $o.region.val(),

            bindEvents = function() {
                // validate and save entered LaterPay API Keys
                $o.apiKeyInput
                .bind('input', function() {
                    var $input = this;
                    setTimeout(function() {
                        validateAPIKey($input);
                    }, 50);
                });

                // validate and save entered LaterPay Merchant IDs
                $o.merchantIdInput
                .bind('input', function() {
                    var $input = this;
                    setTimeout(function() {
                        validateMerchantId($input);
                    }, 50);
                });

                // validate and save entered LaterPay Merchant IDs
                $o.region
                .change(function() {
                    changeRegion();
                });

                // switch plugin between TEST and LIVE mode
                $o.pluginModeToggle
                .change(function() {
                    togglePluginMode();
                });

                // switch plugin visibility in TEST mode
                $o.pluginVisibilityToggle
                .change(function() {
                    toggleVisibilityInTestMode();
                });

                $o.showMerchantContractsButton.bind('click', function() {
                    $(this).attr('href', $(this).data('href-'+$o.region.val()));
                    return true;
                });

                // ask user for confirmation, if he tries to leave the page without a set of valid API credentials
                window.onbeforeunload = function() {
                    preventLeavingWithoutValidCredentials();
                };
            },

            autofocusEmptyInput = function() {
                var i = 0,
                    l = $o.apiCredentialsInputs.length;

                for (; i < l; i++) {
                    if ($o.apiCredentialsInputs.eq(i).val() === '') {
                        $o.apiCredentialsInputs.eq(i).focus();
                        return;
                    }
                }
            },

            toggleVisibilityInTestMode = function() {
                if (hasNoValidCredentials()) {
                    // save information in form that credentials are invalid
                    $o.hasInvalidSandboxCredentials.val(1);

                    // switch to invisible test mode to make sure visitors don't see a broken site
                    $o.pluginVisibilityToggle.prop('checked', false);

                    // focus Merchant ID input in case the user just forgot to enter his credentials
                    $o.testMerchantId.focus();

                    // show button for loading the contracts, as the user probably has no valid live credentials yet
                    $o.showMerchantContractsButton.velocity('fadeIn', { duration: 250 });

                    // make sure Ajax request gets sent
                    $o.requestSent = false;
                } else {
                    $o.hasInvalidSandboxCredentials.val(0);
                }

                // save visibility in test mode
                makeAjaxRequest('laterpay_test_mode');
            },

            togglePluginModeIndicators = function(mode) {
                if (mode === 'live') {
                    $('#lp_js_pluginModeIndicator').velocity('fadeOut', { duration: 250 });
                    $('#lp_js_liveCredentials').addClass($o.isLive);
                } else {
                    $('#lp_js_pluginModeIndicator').velocity('fadeIn', { duration: 250 });
                    $('#lp_js_liveCredentials').removeClass($o.isLive);
                }
            },

            togglePluginMode = function() {
                var $toggle               = $o.pluginModeToggle,
                    hasSwitchedToLiveMode = $toggle.prop('checked');

                if (hasNoValidCredentials()) {
                    // restore test mode
                    $toggle.prop('checked', false);

                    // focus Merchant ID input in case the user just forgot to enter his credentials
                    $o.liveMerchantId.focus();

                    // make sure Ajax request gets sent
                    $o.requestSent = false;

                    // show additional toggle for switching between visible and invisible test mode
                    $o.pluginVisibilitySetting.velocity('fadeIn', { duration: 250, display: 'inline-block' });
                } else if (hasSwitchedToLiveMode) {
                    // hide toggle for switching between visible and invisible test mode
                    $o.pluginVisibilitySetting.velocity('fadeOut', { duration: 250 });

                    // hide button for loading the contracts, as the user obviously has valid live credentials already
                    $o.showMerchantContractsButton.velocity('fadeOut', { duration: 250 });
                } else {
                    // hide toggle for switching between visible and invisible test mode
                    $o.pluginVisibilitySetting.velocity('fadeIn', { duration: 250, display: 'inline-block' });
                }

                // save plugin mode
                makeAjaxRequest('laterpay_plugin_mode');
            },

            changeRegion = function() {
                var form_id = 'laterpay_region';

                $.post(
                    ajaxurl,
                    $('#' + form_id).serializeArray(),
                    function(data) {
                        $o.navigation.showMessage(data);

                        if ( ! data.success ) {
                            $o.region.val( regionVal );
                        } else {
                            regionVal = $o.region.val();
                            $o.testMerchantId.val( data.creds.cp_key );
                            $o.testApiKey.val( data.creds.api_key );

                            if ( regionVal !== 'us' ) {
                                $o.regionNoticeBlock.removeClass('hidden');
                            } else {
                                $o.regionNoticeBlock.addClass('hidden');
                            }
                        }
                    },
                    'json'
                );
            },

            makeAjaxRequest = function(form_id) {
                // prevent duplicate Ajax requests
                if (!$o.requestSent) {
                    $o.requestSent = true;

                    $.post(
                        ajaxurl,
                        $('#' + form_id).serializeArray(),
                        function(data) {
                            $o.navigation.showMessage(data);
                            togglePluginModeIndicators(data.mode);
                        },
                        'json'
                    )
                    .done(function() {
                        $o.requestSent = false;
                    });
                }
            },

            validateAPIKey = function(api_key_input) {
                var $input          = $(api_key_input),
                    $form           = $input.parents('form'),
                    value           = $input.val().trim(),
                    apiKeyLength    = 32;

                // trim spaces from input
                if (value.length !== $input.val().length) {
                    $input.val(value);
                }

                if (value.length === 0 || value.length === apiKeyLength) {
                    // save the value, because it's valid (empty input or string of correct length)
                    makeAjaxRequest($form.attr('id'));
                } else {
                    $o.navigation.showMessage(lpVars.i18nApiKeyInvalid, false);

                    // switch to invisible test mode to make sure visitors don't see a broken site,
                    // if we are in test mode;
                    // no action is required here, if we are in live mode, because there is another check below,
                    // if we need to switch from live to test mode
                    var currentFormId = $o.testApiKey.parents('form').attr('id');
                    if ($form.attr('id') === currentFormId) {
                        toggleVisibilityInTestMode();
                    }
                }

                // switch from live mode to test mode, if there are no valid live credentials
                if (hasNoValidCredentials()) {
                    togglePluginMode();
                }
            },

            validateMerchantId = function(merchant_id_input) {
                var $input              = $(merchant_id_input),
                    $form               = $input.parents('form'),
                    value               = $input.val().trim(),
                    merchantIdLength    = 22;

                // trim spaces from input
                if (value.length !== $input.val().length) {
                    $input.val(value);
                }

                if (value.length === 0 || value.length === merchantIdLength) {
                    // save the value, because it's valid (empty input or string of correct length)
                    makeAjaxRequest($form.attr('id'));
                } else {
                    $o.navigation.showMessage(lpVars.i18nMerchantIdInvalid, false);

                    // switch to invisible test mode to make sure visitors don't see a broken site,
                    // if we are in test mode;
                    // no action is required here, if we are in live mode, because there is another check below,
                    // if we need to switch from live to test mode
                    var currentFormId = $o.testMerchantId.parents('form').attr('id');
                    if ($form.attr('id') === currentFormId) {
                        toggleVisibilityInTestMode();
                    }
                }

                // switch from live mode to test mode, if there are no valid live credentials
                if (hasNoValidCredentials()) {
                    togglePluginMode();
                }
            },

            hasNoValidCredentials = function() {
                var invalidCreds = $o.liveApiKey.val().length !== 32 || $o.liveMerchantId.val().length !== 22;
                // plugin is in live mode, but there are no valid Live API credentials
                return $o.pluginModeToggle.prop('checked') && invalidCreds;
            },

            preventLeavingWithoutValidCredentials = function() {
                if (hasNoValidCredentials()) {
                    return lpVars.i18nPreventUnload;
                }
            },

            initializePage = function() {
                bindEvents();
                autofocusEmptyInput();
            };

        initializePage();
    }

    // initialize page
    laterPayBackendAccount();

});})(jQuery);

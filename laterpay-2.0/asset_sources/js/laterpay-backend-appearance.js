(function($) {$(function() {

    // encapsulate all LaterPay Javascript in function laterPayBackendAppearance
    function laterPayBackendAppearance() {
        var $o = {
                // appearance option
                savePurchaseForm    : $('.lp_js_savePurchaseForm'),
                cancelFormEdit      : $('.lp_js_cancelEditingPurchaseForm'),
                restoreDefaults     : $('.lp_js_restoreDefaultPurchaseForm'),
                buttonGroupButtons  : '.lp_js_buttonGroupButton',
                buttonGroupHint     : '.lp_js_buttonGroupHint',
                overlayOptions      : '.lp_js_overlayOptions',
                overlayShowFooter   : '.lp_js_overlayShowFooter',
                selected            : 'lp_is-selected',
                showHintOnTrue      : 'lp_js_showHintOnTrue',
                headerBgColor       : 'lp_js_purchaseHeaderBackgroundColor',
                headerTitle         : 'lp_js_purchaseHeaderTitle',
                purchaseBgColor     : 'lp_js_purchaseBackgroundColor',
                purchaseMainText    : 'lp_js_purchaseMainTextColor',
                purchaseDescription : 'lp_js_purchaseDescriptionTextColor',
                buttonBgColor       : 'lp_js_purchaseButtonBackgroundColor',
                buttonTextColor     : 'lp_js_purchaseButtonTextColor',
                linkMainColor       : 'lp_js_purchaseLinkMainColor',
                linkHoverColor      : 'lp_js_purchaseLinkHoverColor',
                footerBgColor       : 'lp_js_purchaseFooterBackgroundColor',
                showFooter          : 'lp_js_overlayShowFooter',

                // overlay
                overlayHeader       : '.lp_purchase-overlay__header',
                overlayForm         : '.lp_purchase-overlay__form',
                overlayOptionTitle  : '.lp_purchase-overlay-option__title',
                overlayDescription  : '.lp_purchase-overlay-option__description',
                overlayLink         : '.lp_purchase-overlay__notification',
                overlayButton       : '.lp_purchase-overlay__submit',
                overlayFooter       : '.lp_purchase-overlay__footer',

                // forms
                paidContentPreview  : $('#lp_js_paidContentPreview'),
                previewSwitch       : $('#lp_js_paidContentPreview').find('.lp_js_switchButtonGroup'),
                purchaseForm        : $('#lp_js_purchaseForm'),

                purchaseButtonForm  : $('#lp_js_purchaseButton'),
                purchaseButtonSwitch: $('#lp_js_purchaseButton').find('.lp_js_switchButtonGroup'),

                timePassesForm      : $('#lp_js_timePasses'),
                timePassesSwitch    : $('#lp_js_timePasses').find('.lp_js_switchButtonGroup')
            },

            bindEvents = function() {
                //Content Preview for Paid Posts
                $o.previewSwitch
                .click(function() {
                    previewSwitch($(this));
                });

                //Position of the LaterPay Purchase Button
                $o.purchaseButtonSwitch
                    .click(function() {
                        purchaseButtonSwitch($(this));
                    });

                //Display of LaterPay Time Passes
                $o.timePassesSwitch
                    .click(function() {
                        timePassesSwitch($(this));
                    });

                // toggle elements change
                $($o.overlayOptions)
                .change(function() {
                    updateOverlayOptions($(this));
                });

                // show/hide footer
                $($o.overlayShowFooter)
                .click(function(){
                    processFooter($(this));
                });

                // save overlay settings
                $o.savePurchaseForm
                .click(function(e){
                    e.preventDefault();
                    var $form = $(this).parents('form');

                    // set correct form name
                    $('input[name=form]', $form).val('overlay_settings');

                    saveData($form);
                });

                // restore original data
                $o.cancelFormEdit
                .click(function(e){
                    e.preventDefault();
                    resetOverlaySettings(lpVars.overlaySettings.current);
                });

                // set default settings
                $o.restoreDefaults
                .click(function(e){
                    e.preventDefault();
                    resetOverlaySettings(lpVars.overlaySettings.default);
                });
            },

            previewSwitch = function($trigger) {
                var $form = $trigger.parents('form');

                // mark clicked button as selected
                $($o.buttonGroupButtons, $form).removeClass($o.selected);
                $trigger.parent($o.buttonGroupButtons).addClass($o.selected);

                $('input[name=form]', $form).val('paid_content_preview');

                switch($('input:checked', $form).val())
                {
                    case '0':
                    case '1':
                        $o.purchaseButtonForm.fadeIn();
                        $o.timePassesForm.fadeIn();
                        $o.purchaseForm.hide();

                        $(':input', $o.purchaseForm).attr('disabled', true);

                        break;
                    case '2':
                        $o.purchaseForm.fadeIn();
                        $o.purchaseButtonForm.hide();
                        $o.timePassesForm.hide();

                        $(':input', $o.purchaseForm).attr('disabled', false);

                        break;
                    default:
                        $o.purchaseForm.hide();
                        $o.purchaseButtonForm.hide();
                        $o.timePassesForm.hide();
                        break;
                }

                saveData($form);
            },

            purchaseButtonSwitch = function($trigger) {
                var $form = $trigger.parents('form');

                // mark clicked button as selected
                $($o.buttonGroupButtons, $form).removeClass($o.selected);
                $trigger.parent($o.buttonGroupButtons).addClass($o.selected);

                switch($('input:checked', $form).val())
                {
                    case '0':
                        $form.find($o.buttonGroupHint).fadeOut();
                        break;
                    case '1':
                        $form.find($o.buttonGroupHint).fadeIn();
                        break;
                    default:
                        break;
                }

                saveData($form);
            },

            timePassesSwitch = function($trigger) {
                var $form = $trigger.parents('form');

                // mark clicked button as selected
                $($o.buttonGroupButtons, $form).removeClass($o.selected);
                $trigger.parent($o.buttonGroupButtons).addClass($o.selected);

                switch($('input:checked', $form).val())
                {
                    case '0':
                        $form.find($o.buttonGroupHint).fadeOut();
                        break;
                    case '1':
                        $form.find($o.buttonGroupHint).fadeIn();
                        break;
                    default:
                        break;
                }

                saveData($form);
            },
            updateOverlayOptions = function($trigger) {
                var style;

                // change header bg
                if ($trigger.hasClass($o.headerBgColor)) {
                    style = 'background-color: ' + $('.' + $o.headerBgColor).val() + ' !important;';
                    setStyle($o.overlayHeader, style);
                }

                // change header title
                if ($trigger.hasClass($o.headerTitle)) {
                    $($o.overlayHeader).text($('.' + $o.headerTitle).val());
                }

                // change form bg color
                if ($trigger.hasClass($o.purchaseBgColor)) {
                    style = 'background-color: ' + $('.' + $o.purchaseBgColor).val() + ' !important;';
                    setStyle($($o.overlayForm), style);
                }

                // change form text color
                if ($trigger.hasClass($o.purchaseMainText)) {
                    style = 'color: ' + $('.' + $o.purchaseMainText).val() + ' !important;';
                    setStyle($($o.overlayOptionTitle), style);
                }

                // change form description color
                if ($trigger.hasClass($o.purchaseDescription)) {
                    style = 'color: ' + $('.' + $o.purchaseDescription).val() + ' !important;';
                    setStyle($($o.overlayDescription), style);
                }

                // change button bg color
                if ($trigger.hasClass($o.buttonBgColor)) {
                    style = 'background-color: ' + $('.' + $o.buttonBgColor).val() + ' !important;';
                    setStyle($($o.overlayButton), style);
                }

                // change button text color
                if ($trigger.hasClass($o.buttonTextColor)) {
                    style = 'color: ' + $('.' + $o.buttonTextColor).val() + ' !important;';
                    setStyle($($o.overlayButton), style);
                }

                // change link main color
                if ($trigger.hasClass($o.linkMainColor)) {
                    style = 'color: ' + $('.' + $o.linkMainColor).val() + ' !important;';
                    setStyle($($o.overlayLink + ' a'), style);
                    setStyle($($o.overlayLink), style);
                }

                // change link hover color
                if ($trigger.hasClass($o.linkHoverColor)) {
                    $($o.overlayLink + ' a').hover(
                        function() {
                            style = 'color: ' + $('.' + $o.linkHoverColor).val() + ' !important;';
                            setStyle($($o.overlayLink + ' a'), style);
                        },
                        function() {
                            style = 'color: ' + $('.' + $o.linkMainColor).val() + ' !important;';
                            setStyle($($o.overlayLink + ' a'), style);
                        }
                    );
                }

                // change footer bg color
                if ($trigger.hasClass($o.footerBgColor)) {
                    style = 'background-color: ' + $('.' + $o.footerBgColor).val() + ' !important;';

                    if ($($o.overlayFooter).is(':hidden'))
                    {
                        style += 'display: none;';
                    }

                    setStyle($($o.overlayFooter), style);
                }
            },

            processFooter = function($trigger) {
                if ($trigger.is(':checked')) {
                    $($o.overlayFooter).show();
                } else {
                    $($o.overlayFooter).hide();
                }
            },

            saveData = function($form) {
                $.post(
                    ajaxurl,
                    $form.serializeArray(),
                    function(data) {
                        $('.lp_navigation').showMessage(data);
                    }
                );
            },

            setStyle = function(target, style) {
                $(target).attr('style', style);
            },

            resetOverlaySettings = function(settings) {
                $('.' + $o.headerBgColor).val(settings.header_bg_color).change();
                $('.' + $o.headerTitle).val(settings.header_title).change();
                $('.' + $o.purchaseBgColor).val(settings.main_bg_color).change();
                $('.' + $o.purchaseMainText).val(settings.main_text_color).change();
                $('.' + $o.purchaseDescription).val(settings.description_color).change();
                $('.' + $o.buttonBgColor).val(settings.button_bg_color).change();
                $('.' + $o.buttonTextColor).val(settings.button_text_color).change();
                $('.' + $o.linkMainColor).val(settings.link_main_color).change();
                $('.' + $o.linkHoverColor).val(settings.link_hover_color).change();
                $('.' + $o.footerBgColor).val(settings.footer_bg_color).change();

                if (true === settings.show_footer) {
                    $('.' + $o.showFooter).attr('checked', 'checked');
                }
                else
                {
                    $('.' + $o.showFooter).removeAttr('checked');
                }
            },

            initializePage = function() {
                bindEvents();
            };

        initializePage();
    }

    // initialize page
    laterPayBackendAppearance();

});})(jQuery);

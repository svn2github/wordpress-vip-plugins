!function(o){o(function(){function e(){var e={savePurchaseForm:o(".lp_js_savePurchaseForm"),cancelFormEdit:o(".lp_js_cancelEditingPurchaseForm"),restoreDefaults:o(".lp_js_restoreDefaultPurchaseForm"),buttonGroupButtons:".lp_js_buttonGroupButton",buttonGroupHint:".lp_js_buttonGroupHint",overlayOptions:".lp_js_overlayOptions",overlayShowFooter:".lp_js_overlayShowFooter",selected:"lp_is-selected",showHintOnTrue:"lp_js_showHintOnTrue",headerBgColor:"lp_js_purchaseHeaderBackgroundColor",headerTitle:"lp_js_purchaseHeaderTitle",purchaseBgColor:"lp_js_purchaseBackgroundColor",purchaseMainText:"lp_js_purchaseMainTextColor",purchaseDescription:"lp_js_purchaseDescriptionTextColor",buttonBgColor:"lp_js_purchaseButtonBackgroundColor",buttonTextColor:"lp_js_purchaseButtonTextColor",linkMainColor:"lp_js_purchaseLinkMainColor",linkHoverColor:"lp_js_purchaseLinkHoverColor",footerBgColor:"lp_js_purchaseFooterBackgroundColor",showFooter:"lp_js_overlayShowFooter",overlayHeader:".lp_purchase-overlay__header",overlayForm:".lp_purchase-overlay__form",overlayOptionTitle:".lp_purchase-overlay-option__title",overlayDescription:".lp_purchase-overlay-option__description",overlayLink:".lp_purchase-overlay__notification",overlayButton:".lp_purchase-overlay__submit",overlayFooter:".lp_purchase-overlay__footer",paidContentPreview:o("#lp_js_paidContentPreview"),previewSwitch:o("#lp_js_paidContentPreview").find(".lp_js_switchButtonGroup"),purchaseForm:o("#lp_js_purchaseForm"),purchaseButtonForm:o("#lp_js_purchaseButton"),purchaseButtonSwitch:o("#lp_js_purchaseButton").find(".lp_js_switchButtonGroup"),timePassesForm:o("#lp_js_timePasses"),timePassesSwitch:o("#lp_js_timePasses").find(".lp_js_switchButtonGroup")},r=function(){e.previewSwitch.click(function(){a(o(this))}),e.purchaseButtonSwitch.click(function(){t(o(this))}),e.timePassesSwitch.click(function(){n(o(this))}),o(e.overlayOptions).change(function(){l(o(this))}),o(e.overlayShowFooter).click(function(){s(o(this))}),e.savePurchaseForm.click(function(e){e.preventDefault();var r=o(this).parents("form");o("input[name=form]",r).val("overlay_settings"),i(r)}),e.cancelFormEdit.click(function(o){o.preventDefault(),p(lpVars.overlaySettings.current)}),e.restoreDefaults.click(function(o){o.preventDefault(),p(lpVars.overlaySettings["default"])})},a=function(r){var a=r.parents("form");o(e.buttonGroupButtons,a).removeClass(e.selected),r.parent(e.buttonGroupButtons).addClass(e.selected),o("input[name=form]",a).val("paid_content_preview");var t=o("input:checked",a).val();switch(t){case"0":case"1":e.purchaseButtonForm.fadeIn(),e.timePassesForm.fadeIn(),e.purchaseForm.hide(),o(":input",e.purchaseForm).attr("disabled",!0);break;case"2":e.purchaseForm.fadeIn(),e.purchaseButtonForm.hide(),e.timePassesForm.hide(),o(":input",e.purchaseForm).attr("disabled",!1);break;default:e.purchaseForm.hide(),e.purchaseButtonForm.hide(),e.timePassesForm.hide()}var n=["Purchase Link","Explanatory Overlay","Purchase Overlay"],l=lpVars.gaData.sandbox_merchant_id+" | ";lpGlobal.sendLPGAEvent("Overall Appearance","LP WP Appearance",l+n[parseInt(t)]),i(a)},t=function(r){var a=r.parents("form");o(e.buttonGroupButtons,a).removeClass(e.selected),r.parent(e.buttonGroupButtons).addClass(e.selected);var t=lpVars.gaData.sandbox_merchant_id+" | Purchase Button";switch(o("input:checked",a).val()){case"0":a.find(e.buttonGroupHint).fadeOut(),lpGlobal.sendLPGAEvent("Standard Position","LP WP Appearance",t);break;case"1":a.find(e.buttonGroupHint).fadeIn(),lpGlobal.sendLPGAEvent("Custom Position","LP WP Appearance",t)}i(a)},n=function(r){var a=r.parents("form");o(e.buttonGroupButtons,a).removeClass(e.selected),r.parent(e.buttonGroupButtons).addClass(e.selected);var t=lpVars.gaData.sandbox_merchant_id+" | Subscriptions & Time Passes";switch(o("input:checked",a).val()){case"0":a.find(e.buttonGroupHint).fadeOut(),lpGlobal.sendLPGAEvent("Standard Position","LP WP Appearance",t);break;case"1":a.find(e.buttonGroupHint).fadeIn(),lpGlobal.sendLPGAEvent("Custom Position","LP WP Appearance",t)}i(a)},l=function(r){var a;r.hasClass(e.headerBgColor)&&(a="background-color: "+o("."+e.headerBgColor).val()+" !important;",c(e.overlayHeader,a)),r.hasClass(e.headerTitle)&&o(e.overlayHeader).text(o("."+e.headerTitle).val()),r.hasClass(e.purchaseBgColor)&&(a="background-color: "+o("."+e.purchaseBgColor).val()+" !important;",c(o(e.overlayForm),a)),r.hasClass(e.purchaseMainText)&&(a="color: "+o("."+e.purchaseMainText).val()+" !important;",c(o(e.overlayOptionTitle),a)),r.hasClass(e.purchaseDescription)&&(a="color: "+o("."+e.purchaseDescription).val()+" !important;",c(o(e.overlayDescription),a)),r.hasClass(e.buttonBgColor)&&(a="background-color: "+o("."+e.buttonBgColor).val()+" !important;",c(o(e.overlayButton),a)),r.hasClass(e.buttonTextColor)&&(a="color: "+o("."+e.buttonTextColor).val()+" !important;",c(o(e.overlayButton),a)),r.hasClass(e.linkMainColor)&&(a="color: "+o("."+e.linkMainColor).val()+" !important;",c(o(e.overlayLink+" a"),a),c(o(e.overlayLink),a)),r.hasClass(e.linkHoverColor)&&o(e.overlayLink+" a").hover(function(){a="color: "+o("."+e.linkHoverColor).val()+" !important;",c(o(e.overlayLink+" a"),a)},function(){a="color: "+o("."+e.linkMainColor).val()+" !important;",c(o(e.overlayLink+" a"),a)}),r.hasClass(e.footerBgColor)&&(a="background-color: "+o("."+e.footerBgColor).val()+" !important;",o(e.overlayFooter).is(":hidden")&&(a+="display: none;"),c(o(e.overlayFooter),a))},s=function(r){r.is(":checked")?o(e.overlayFooter).show():o(e.overlayFooter).hide()},i=function(e){o.post(ajaxurl,e.serializeArray(),function(e){o(".lp_navigation").showMessage(e)})},c=function(e,r){o(e).attr("style",r)},p=function(r){o("."+e.headerBgColor).val(r.header_bg_color).change(),o("."+e.headerTitle).val(r.header_title).change(),o("."+e.purchaseBgColor).val(r.main_bg_color).change(),o("."+e.purchaseMainText).val(r.main_text_color).change(),o("."+e.purchaseDescription).val(r.description_color).change(),o("."+e.buttonBgColor).val(r.button_bg_color).change(),o("."+e.buttonTextColor).val(r.button_text_color).change(),o("."+e.linkMainColor).val(r.link_main_color).change(),o("."+e.linkHoverColor).val(r.link_hover_color).change(),o("."+e.footerBgColor).val(r.footer_bg_color).change(),!0===r.show_footer?o("."+e.showFooter).attr("checked","checked"):o("."+e.showFooter).removeAttr("checked")},u=function(){r()};u()}e()})}(jQuery);
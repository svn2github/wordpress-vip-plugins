!function(e){e(function(){function t(){var t={body:e("body"),revenueModel:".lp_js_revenueModel",revenueModelLabel:".lp_js_revenueModelLabel",revenueModelLabelDisplay:".lp_js_revenueModelLabelDisplay",revenueModelInput:".lp_js_revenueModelInput",priceInput:".lp_js_priceInput",emptyState:".lp_js_emptyState",timePassOnlyHideElements:e(".lp_js_hideInTimePassOnlyMode"),globalDefaultPriceForm:e("#lp_js_globalDefaultPriceForm"),globalDefaultPriceInput:e("#lp_js_globalDefaultPriceInput"),globalDefaultPriceDisplay:e("#lp_js_globalDefaultPriceDisplay"),globalDefaultPriceRevenueModelDisplay:e("#lp_js_globalDefaultPriceRevenueModelDisplay"),editGlobalDefaultPrice:e("#lp_js_editGlobalDefaultPrice"),cancelEditingGlobalDefaultPrice:e("#lp_js_cancelEditingGlobalDefaultPrice"),saveGlobalDefaultPrice:e("#lp_js_saveGlobalDefaultPrice"),globalDefaultPriceShowElements:e("#lp_js_globalDefaultPriceShowElements"),globalDefaultPriceEditElements:e("#lp_js_globalDefaultPriceEditElements"),categoryDefaultPrices:e("#lp_js_categoryDefaultPriceList"),addCategory:e("#lp_js_addCategoryDefaultPrice"),categoryDefaultPriceTemplate:e("#lp_js_categoryDefaultPriceTemplate"),categoryDefaultPriceForm:".lp_js_categoryDefaultPriceForm",editCategoryDefaultPrice:".lp_js_editCategoryDefaultPrice",cancelEditingCategoryDefaultPrice:".lp_js_cancelEditingCategoryDefaultPrice",saveCategoryDefaultPrice:".lp_js_saveCategoryDefaultPrice",deleteCategoryDefaultPrice:".lp_js_deleteCategoryDefaultPrice",categoryDefaultPriceShowElements:".lp_js_categoryDefaultPriceShowElements",categoryDefaultPriceEditElements:".lp_js_categoryDefaultPriceEditElements",categoryTitle:".lp_js_categoryDefaultPriceCategoryTitle",categoryDefaultPriceDisplay:".lp_js_categoryDefaultPriceDisplay",selectCategory:".lp_js_selectCategory",categoryDefaultPriceInput:".lp_js_categoryDefaultPriceInput",categoryId:".lp_js_categoryDefaultPriceCategoryId",categoryName:".lp_js_categoryDefaultPriceCategorName",globalEnabledPostTypesForm:e("#lp_js_globalEnabledPostTypesForm"),saveEnabledPostTypes:e("#lp_js_saveEnabledPostTypes"),cancelEditingEnabledPostTypes:e("#lp_js_cancelEditingEnabledPostTypes"),timepass:{editor:e("#lp_time-passes"),template:e("#lp_js_timePassTemplate"),form:".lp_js_timePassEditorForm",editorContainer:".lp_js_timePassEditorContainer",id:".lp_js_timePassId",wrapper:".lp_js_timePassWrapper",categoryWrapper:".lp_js_timePassCategoryWrapper",fields:{duration:".lp_js_switchTimePassDuration",period:".lp_js_switchTimePassPeriod",scope:".lp_js_switchTimePassScope",scopeCategory:".lp_js_switchTimePassScopeCategory",categoryId:".lp_js_timePassCategoryId",title:".lp_js_timePassTitleInput",price:".lp_js_timePassPriceInput",revenueModel:".lp_js_timePassRevenueModelInput",description:".lp_js_timePassDescriptionTextarea"},classes:{form:"lp_js_timePassForm",editorForm:"lp_js_timePassEditorForm",durationClass:"lp_js_switchTimePassDuration",titleClass:"lp_js_timePassTitleInput",priceClass:"lp_js_timePassPriceInput",descriptionClass:"lp_js_timePassDescriptionTextarea",periodClass:"lp_js_switchTimePassPeriod",scopeClass:"lp_js_switchTimePassScope",scopeCategoryClass:"lp_js_switchTimePassScopeCategory"},preview:{placeholder:".lp_js_timePassPreview",wrapper:".lp_js_timePass",title:".lp_js_timePassPreviewTitle",description:".lp_js_timePassPreviewDescription",validity:".lp_js_timePassPreviewValidity",access:".lp_js_timePassPreviewAccess",price:".lp_js_timePassPreviewPrice"},actions:{create:e("#lp_js_addTimePass"),show:".lp_js_saveTimePass, .lp_js_cancelEditingTimePass",modify:".lp_js_editTimePass, .lp_js_deleteTimePass",save:".lp_js_saveTimePass",cancel:".lp_js_cancelEditingTimePass","delete":".lp_js_deleteTimePass",edit:".lp_js_editTimePass",flip:".lp_js_flipTimePass"},ajax:{form:{"delete":"time_pass_delete"}},data:{id:"pass-id",list:lpVars.time_passes_list,vouchers:lpVars.vouchers_list,deleteConfirm:lpVars.i18n.confirmDeleteTimepass,fields:{id:"pass_id"}}},subscription:{editor:e("#lp_subscriptions"),template:e("#lp_js_subscriptionTemplate"),form:".lp_js_subscriptionEditorForm",editorContainer:".lp_js_subscriptionEditorContainer",id:".lp_js_subscriptionId",wrapper:".lp_js_subscriptionWrapper",categoryWrapper:".lp_js_subscriptionCategoryWrapper",fields:{duration:".lp_js_switchSubscriptionDuration",period:".lp_js_switchSubscriptionPeriod",scope:".lp_js_switchSubscriptionScope",scopeCategory:".lp_js_switchSubscriptionScopeCategory",categoryId:".lp_js_subscriptionCategoryId",title:".lp_js_subscriptionTitleInput",price:".lp_js_subscriptionPriceInput",description:".lp_js_subscriptionDescriptionTextarea"},classes:{form:"lp_js_subscriptionForm",editorForm:"lp_js_subscriptionEditorForm",durationClass:"lp_js_switchSubscriptionDuration",titleClass:"lp_js_subscriptionTitleInput",priceClass:"lp_js_subscriptionPriceInput",descriptionClass:"lp_js_subscriptionDescriptionTextarea",periodClass:"lp_js_switchSubscriptionPeriod",scopeClass:"lp_js_switchSubscriptionScope",scopeCategoryClass:"lp_js_switchSubscriptionScopeCategory"},preview:{placeholder:".lp_js_subscriptionPreview",wrapper:".lp_js_subscription",title:".lp_js_subscriptionPreviewTitle",description:".lp_js_subscriptionPreviewDescription",validity:".lp_js_subscriptionPreviewValidity",access:".lp_js_subscriptionPreviewAccess",price:".lp_js_subscriptionPreviewPrice",renewal:".lp_js_subscriptionPreviewRenewal"},actions:{create:e("#lp_js_addSubscription"),show:".lp_js_saveSubscription, .lp_js_cancelEditingSubscription",modify:".lp_js_editSubscription, .lp_js_deleteSubscription",save:".lp_js_saveSubscription",edit:".lp_js_editSubscription",cancel:".lp_js_cancelEditingSubscription","delete":".lp_js_deleteSubscription",flip:".lp_js_flipSubscription"},ajax:{form:{"delete":"subscription_delete"}},data:{id:"sub-id",list:lpVars.subscriptions_list,vouchers:lpVars.sub_vouchers_list,deleteConfirm:lpVars.i18n.confirmDeleteSubscription,fields:{id:"id"}}},voucherPriceInput:".lp_js_voucherPriceInput",generateVoucherCode:".lp_js_generateVoucherCode",voucherDeleteLink:".lp_js_deleteVoucher",voucherEditor:".lp_js_voucherEditor",voucherHiddenPassId:"#lp_js_timePassEditorHiddenPassId",voucherPlaceholder:".lp_js_voucherPlaceholder",voucherList:".lp_js_voucherList",voucher:".lp_js_voucher",editing:"lp_is-editing",unsaved:"lp_is-unsaved",payPerUse:"ppu",singleSale:"sis",selected:"lp_is-selected",disabled:"lp_is-disabled",hidden:"lp_hidden",navigation:e(".lp_navigation"),lp_make_post_free:e("#lp_make_post_free"),lp_disable_individual_purchase:e("#lp_disable_individual_purchase"),lp_set_inidvidual_price:e("#lp_set_individual_price"),lp_current_post_price_val:e('input[name="lp_current_post_price_val"]'),lp_global_price_section:e("#lp_js_globalPriceSection"),lp_global_revenue_section:e("#lp_js_globalRevenueSection"),lp_js_form_buttons_section:e("#lp_js_formButtons"),lp_js_globalPriceOptionZero:e("#lp_js_globalPriceOptionZero"),lp_js_globalPriceOptionOne:e("#lp_js_globalPriceOptionOne"),lp_js_globalPriceOptionTwo:e("#lp_js_globalPriceOptionTwo"),categoryButtonContainer:e("div.lp_js_categoryButtonContainer"),categoryPanelWarning:e("div.lp_js_categoryPanelWarning")},i=function(){t.body.on("change",t.revenueModelInput,function(){a(e(this).parents("form"))}),t.body.on("keyup",t.priceInput,L(function(){a(e(this).parents("form"))},1500)),t.editGlobalDefaultPrice.on("mousedown",function(){o()}).click(function(e){e.preventDefault()}),t.cancelEditingGlobalDefaultPrice.on("mousedown",function(){l()}).click(function(e){e.preventDefault()}),t.saveGlobalDefaultPrice.on("mousedown",function(){n()}).click(function(e){e.preventDefault()}),t.addCategory.on("mousedown",function(){c()}).click(function(e){e.preventDefault()}),t.body.on("click",t.editCategoryDefaultPrice,function(){var i=e(this).parents(t.categoryDefaultPriceForm);p(i)}),t.body.on("click",t.cancelEditingCategoryDefaultPrice,function(){var i=e(this).parents(t.categoryDefaultPriceForm);u(i)}),t.body.on("click",t.saveCategoryDefaultPrice,function(){var i=e(this).parents(t.categoryDefaultPriceForm),s=t.lp_current_post_price_val.val();return"1"===s?void u(i):void d(i)}),t.body.on("click",t.deleteCategoryDefaultPrice,function(){var i=e(this).parents(t.categoryDefaultPriceForm);_(i)}),t.timepass.actions.create.on("mousedown",function(){h("timepass")}).on("click",function(e){e.preventDefault()}),t.timepass.editor.on("mousedown",t.timepass.actions.edit,function(){b("timepass",e(this).parents(t.timepass.wrapper))}).on("click",t.timepass.actions.edit,function(e){e.preventDefault()}),t.timepass.editor.on("change",t.timepass.fields.revenueModel,function(){var i=e(this).parents("form");a(i,!1,e(t.timepass.fields.price,i))}),t.timepass.editor.on("change",t.timepass.fields.duration,function(){y("timepass",e(this).parents(t.timepass.wrapper),e(this))}),t.timepass.editor.on("change",t.timepass.fields.period,function(){k("timepass",e(this),e(this).parents(t.timepass.wrapper)),y("timepass",e(this).parents(t.timepass.wrapper),e(this))}),t.timepass.editor.on("change",t.timepass.fields.scope,function(){C("timepass",e(this)),y("timepass",e(this).parents(t.timepass.wrapper),e(this))}),t.timepass.editor.on("change",t.timepass.fields.scopeCategory,function(){y("timepass",e(this).parents(t.timepass.wrapper),e(this))}),t.timepass.editor.on("input",[t.timepass.fields.title,t.timepass.fields.description].join(),function(){y("timepass",e(this).parents(t.timepass.wrapper),e(this))}),t.timepass.editor.on("keyup",t.timepass.fields.price,L(function(){a(e(this).parents("form"),!1,e(this)),y("timepass",e(this).parents(t.timepass.wrapper),e(this))},1500)),t.timepass.editor.on("click",t.timepass.actions.cancel,function(i){e(t.timepass.actions.save).removeAttr("disabled"),e(t.timepass.actions.save).attr("href","#"),P("timepass",e(this).parents(t.timepass.wrapper)),i.preventDefault()}),t.timepass.editor.on("click",t.timepass.actions.save,function(i){return!e(this).is("[disabled=disabled]")&&(j("timepass",e(this).parents(t.timepass.wrapper)),void i.preventDefault())}),t.timepass.editor.on("click",t.timepass.actions["delete"],function(i){D("timepass",e(this).parents(t.timepass.wrapper)),i.preventDefault()}),t.timepass.editor.on("mousedown",t.timepass.actions.flip,function(){w("timepass",this)}).on("click",t.timepass.actions.flip,function(e){e.preventDefault()}),t.timepass.editor.on("keyup",t.voucherPriceInput,L(function(){a(e(this).parents("form"),!0,e(this))},1500)),t.timepass.editor.on("mousedown",t.generateVoucherCode,function(){I("timepass",e(this).parents(t.timepass.wrapper))}).on("click",t.generateVoucherCode,function(e){e.preventDefault()}),t.timepass.editor.on("click",t.voucherDeleteLink,function(t){V(e(this).parent()),t.preventDefault()}),t.timepass.editor.on("keyup",t.voucherPriceInput,function(i){s("timepass",e(this).parents(t.timepass.wrapper)),i.preventDefault()}),t.subscription.actions.create.on("mousedown",function(){h("subscription")}).on("click",function(e){e.preventDefault()}),t.subscription.editor.on("mousedown",t.subscription.actions.edit,function(){b("subscription",e(this).parents(t.subscription.wrapper))}).on("click",t.subscription.actions.edit,function(e){e.preventDefault()}),t.subscription.editor.on("change",t.subscription.fields.duration,function(){y("subscription",e(this).parents(t.subscription.wrapper),e(this))}),t.subscription.editor.on("change",t.subscription.fields.period,function(){k("subscription",e(this),e(this).parents(t.subscription.wrapper)),y("subscription",e(this).parents(t.subscription.wrapper),e(this))}),t.subscription.editor.on("change",t.subscription.fields.scope,function(){C("subscription",e(this)),y("subscription",e(this).parents(t.subscription.wrapper),e(this))}),t.subscription.editor.on("change",t.subscription.fields.scopeCategory,function(){y("subscription",e(this).parents(t.subscription.wrapper),e(this))}),t.subscription.editor.on("input",[t.subscription.fields.title,t.subscription.fields.description].join(),function(){y("subscription",e(this).parents(t.subscription.wrapper),e(this))}),t.subscription.editor.on("keyup",t.subscription.fields.price,L(function(){a(e(this).parents("form"),!0,e(this),!0),y("subscription",e(this).parents(t.subscription.wrapper),e(this))},1500)),t.subscription.editor.on("click",t.subscription.actions.cancel,function(i){e(t.subscription.actions.save).removeAttr("disabled"),e(t.subscription.actions.save).attr("href","#"),P("subscription",e(this).parents(t.subscription.wrapper)),i.preventDefault()}),t.subscription.editor.on("click",t.subscription.actions.save,function(i){return!e(this).is("[disabled=disabled]")&&(j("subscription",e(this).parents(t.subscription.wrapper)),void i.preventDefault())}),t.subscription.editor.on("click",t.subscription.actions["delete"],function(i){D("subscription",e(this).parents(t.subscription.wrapper)),i.preventDefault()}),t.subscription.editor.on("mousedown",t.subscription.actions.flip,function(){w("subscription",this)}).on("click",t.subscription.actions.flip,function(e){e.preventDefault()}),t.subscription.editor.on("keyup",t.voucherPriceInput,L(function(){a(e(this).parents("form"),!0,e(this),!0)},1500)),t.subscription.editor.on("mousedown",t.generateVoucherCode,function(){I("subscription",e(this).parents(t.subscription.wrapper))}).on("click",t.generateVoucherCode,function(e){e.preventDefault()}),t.subscription.editor.on("click",t.voucherDeleteLink,function(t){V(e(this).parent()),t.preventDefault()}),t.subscription.editor.on("keyup",t.voucherPriceInput,function(i){s("subscription",e(this).parents(t.subscription.wrapper)),i.preventDefault()}),t.lp_make_post_free.on("click",function(){return t.lp_global_price_section.hide(),t.lp_global_revenue_section.hide(),t.lp_js_form_buttons_section.css("float","right"),!0}),t.lp_disable_individual_purchase.on("click",function(){return t.lp_global_price_section.hide(),t.lp_global_revenue_section.hide(),t.lp_js_form_buttons_section.css("float","right"),!0}),t.lp_set_inidvidual_price.on("click",function(){return t.lp_global_price_section.show(),t.lp_global_revenue_section.show(),t.lp_js_form_buttons_section.css("float","none"),!0}),t.saveEnabledPostTypes.on("mousedown",function(){M()}).click(function(e){e.preventDefault()}),t.cancelEditingEnabledPostTypes.on("mousedown",function(){t.globalEnabledPostTypesForm.trigger("reset")}).click(function(e){e.preventDefault()})},s=function(i,s){var a=!1;if("subscription"===i&&(a=!0),a){if(s.find(t.voucherPriceInput).val()>s.find(t.subscription.fields.price).val())return s.find(".lp_js_voucher_msg").text(lpVars.i18n.subVoucherMaximumPrice),void s.find(".lp_js_voucher_msg").css("display","block");if(s.find(t.voucherPriceInput).val()<lpVars.currency.sis_min)return s.find(".lp_js_voucher_msg").text(lpVars.i18n.subVoucherMinimum),void s.find(".lp_js_voucher_msg").css("display","block");e(t.subscription.actions.save).removeAttr("disabled"),e(t.subscription.actions.save).attr("href","#")}else{if(s.find(t.voucherPriceInput).val()>s.find(t.timepass.fields.price).val())return void s.find(".lp_js_voucher_msg").css("display","block");e(t.timepass.actions.save).removeAttr("disabled"),e(t.timepass.actions.save).attr("href","#")}s.find(".lp_js_voucher_msg").hide()},a=function(t,i,s,a){var o=s?s:e(".lp_number-input",t),l=o.val();return l=l.replace(/[^0-9\,\.]/g,""),l=parseFloat(l.replace(",",".")).toFixed(2),isNaN(l)&&(l=0),l=Math.abs(l),a?l<lpVars.currency.sis_min?l=lpVars.currency.sis_min:l>lpVars.currency.sis_max&&(l=lpVars.currency.sis_max):l>lpVars.currency.sis_max?l=lpVars.currency.sis_max:l>0&&l<lpVars.currency.ppu_min&&(l=lpVars.currency.ppu_min),i||r(l,t),l=l.toFixed(2),lpVars.locale.indexOf("de_DE")!==-1&&(l=l.replace(".",",")),o.val(l),l},r=function(i,s){var a,r=t.revenueModelInput;s.hasClass(t.timepass.classes.editorForm)&&(r=t.timepass.fields.revenueModel);var o=e(r+"[value="+t.payPerUse+"]",s),l=e(r+"[value="+t.singleSale+"]",s);a=e("input:radio:checked",s).val(),0===i||i>=lpVars.currency.ppu_min&&i<=lpVars.currency.ppu_max?(o.removeProp("disabled").parent("label").removeClass(t.disabled),l.parent("label").attr("data-tooltip",lpVars.i18n.payNowToolTip),l.parent("label").addClass("lp_tooltip"),o.parent("label").removeClass("lp_tooltip")):o.prop("disabled","disabled").parent("label").addClass(t.disabled),i>=lpVars.currency.sis_min?(l.removeProp("disabled").parent("label").removeClass(t.disabled),o.parent("label").attr("data-tooltip",lpVars.i18n.payLaterToolTip),o.parent("label").addClass("lp_tooltip"),l.parent("label").removeClass("lp_tooltip")):l.prop("disabled","disabled").parent("label").addClass(t.disabled),i>lpVars.currency.ppu_max&&a===t.payPerUse?l.prop("checked","checked"):i<lpVars.currency.sis_min&&a===t.singleSale&&o.prop("checked","checked"),e("label",s).removeClass(t.selected),e(r+":checked",s).parent("label").addClass(t.selected)},o=function(){t.globalDefaultPriceShowElements.velocity("slideUp",{duration:250,easing:"ease-out"}),t.globalDefaultPriceEditElements.velocity("slideDown",{duration:250,easing:"ease-out",complete:function(){setTimeout(function(){t.globalDefaultPriceInput.focus()},50)}}),t.globalDefaultPriceForm.addClass(t.editing)},l=function(){t.globalDefaultPriceShowElements.velocity("slideDown",{duration:250,easing:"ease-out"}),t.globalDefaultPriceEditElements.velocity("slideUp",{duration:250,easing:"ease-out"}),t.globalDefaultPriceForm.removeClass(t.editing),t.globalDefaultPriceInput.val(t.globalDefaultPriceDisplay.data("price"));var i=t.globalDefaultPriceRevenueModelDisplay.data("revenue");e(t.revenueModelLabel,t.globalDefaultPriceForm).removeClass(t.selected),e(".lp_js_revenueModelInput[value="+i+"]",t.globalDefaultPriceForm).prop("checked","checked").parent("label").addClass(t.selected);var s=t.lp_current_post_price_val.val();"2"===s?(t.lp_set_inidvidual_price.attr("checked","checked"),t.lp_disable_individual_purchase.removeProp("checked"),t.lp_make_post_free.removeProp("checked"),t.lp_global_price_section.show(),t.lp_global_revenue_section.show(),t.lp_js_form_buttons_section.css("float","none")):"1"===s?(t.lp_disable_individual_purchase.attr("checked","checked"),t.lp_set_inidvidual_price.removeProp("checked"),t.lp_make_post_free.removeProp("checked"),t.lp_global_price_section.hide(),t.lp_global_revenue_section.hide(),t.lp_js_form_buttons_section.css("float","right")):"0"===s&&(t.lp_make_post_free.attr("checked","checked"),t.lp_set_inidvidual_price.removeProp("checked"),t.lp_disable_individual_purchase.removeProp("checked"),t.lp_global_price_section.hide(),t.lp_global_revenue_section.hide(),t.lp_js_form_buttons_section.css("float","right"))},n=function(){var i=a(t.globalDefaultPriceForm);t.globalDefaultPriceInput.val(i);var s,r=lpVars.gaData.sandbox_merchant_id+" | ",o="",n="LP WP Pricing",c="Save Global Default Price";e.post(ajaxurl,t.globalDefaultPriceForm.serializeArray(),function(i){i.success&&(t.globalDefaultPriceDisplay.text(i.localized_price).data("price",i.price),t.globalDefaultPriceRevenueModelDisplay.text(i.revenue_model_label).data("revenue",i.revenue_model),2===i.post_price_behaviour?(s=r+"Individual Article | "+i.revenue_model_label,o=Math.ceil(100*i.price),lpGlobal.sendLPGAEvent(c,n,s,o),t.lp_current_post_price_val.val("2"),t.lp_set_inidvidual_price.attr("checked","checked"),t.lp_disable_individual_purchase.removeProp("checked"),t.lp_make_post_free.removeProp("checked"),t.lp_global_price_section.show(),t.lp_global_revenue_section.show(),t.lp_js_form_buttons_section.css("float","none"),t.lp_js_globalPriceOptionTwo.show(),t.editGlobalDefaultPrice.css("padding","14px"),t.lp_js_globalPriceOptionOne.hide(),t.lp_js_globalPriceOptionZero.hide(),t.addCategory.removeAttr("disabled"),t.categoryButtonContainer.removeClass("lp_tooltip"),t.categoryPanelWarning.hide()):1===i.post_price_behaviour?(s=r+"Cannot Purchase Individually |",o=0,lpGlobal.sendLPGAEvent(c,n,s,o),t.lp_current_post_price_val.val("1"),t.lp_disable_individual_purchase.attr("checked","checked"),t.lp_set_inidvidual_price.removeProp("checked"),t.lp_make_post_free.removeProp("checked"),t.lp_global_price_section.hide(),t.lp_global_revenue_section.hide(),t.lp_js_form_buttons_section.css("float","right"),t.lp_js_globalPriceOptionOne.show(),t.editGlobalDefaultPrice.css("padding","21px"),t.lp_js_globalPriceOptionTwo.hide(),t.lp_js_globalPriceOptionZero.hide(),t.addCategory.attr("disabled","disabled"),t.categoryButtonContainer.addClass("lp_tooltip"),e(t.categoryDefaultPriceForm+":visible").length>0&&t.categoryPanelWarning.show()):0===i.post_price_behaviour&&(s=r+"Free |",o=0,lpGlobal.sendLPGAEvent(c,n,s,o),t.lp_current_post_price_val.val("0"),t.lp_make_post_free.attr("checked","checked"),t.lp_set_inidvidual_price.removeProp("checked"),t.lp_disable_individual_purchase.removeProp("checked"),t.lp_global_price_section.hide(),t.lp_global_revenue_section.hide(),t.lp_js_form_buttons_section.css("float","right"),t.lp_js_globalPriceOptionZero.show(),t.editGlobalDefaultPrice.css("padding","21px"),t.lp_js_globalPriceOptionTwo.hide(),t.lp_js_globalPriceOptionOne.hide(),t.addCategory.removeAttr("disabled"),t.categoryButtonContainer.removeClass("lp_tooltip"),t.categoryPanelWarning.hide())),t.navigation.showMessage(i),l()},"json")},c=function(){var i=t.lp_current_post_price_val.val();if("1"!==i){t.addCategory.velocity("fadeOut",{duration:250}),e(t.emptyState,t.categoryDefaultPrices).is(":visible")&&e(t.emptyState,t.categoryDefaultPrices).velocity("fadeOut",{duration:400});var s=t.categoryDefaultPriceTemplate.clone().removeAttr("id").insertBefore("#lp_js_categoryDefaultPriceList").velocity("slideDown",{duration:250,easing:"ease-out"});p(s)}},p=function(i){e(".lp_js_categoryDefaultPriceForm.lp_is-editing").each(function(){u(e(this),!0)}),i.addClass(t.editing),e(t.categoryDefaultPriceShowElements,i).velocity("slideUp",{duration:250,easing:"ease-out"}),t.addCategory.velocity("fadeOut",{duration:250}),e(t.categoryDefaultPriceEditElements,i).velocity("slideDown",{duration:250,easing:"ease-out",complete:function(){e(t.categoryDefaultPriceInput,i).focus()}}),m(i,t.selectCategory,"laterpay_get_categories_with_price",v,"category")},d=function(i){var s=a(i);e(t.categoryDefaultPriceInput,i).val(s),e.post(ajaxurl,i.serializeArray(),function(s){if(s.success){var a,r=lpVars.gaData.sandbox_merchant_id+" | ",o="",l="LP WP Pricing",n="Edit Category Default";a=r+s.category+" | "+s.revenue_model_label,o=Math.ceil(100*s.price),""===e(t.categoryId,i).val()&&(n="Create Category Default"),lpGlobal.sendLPGAEvent(n,l,a,o),e(t.categoryDefaultPriceDisplay,i).text(s.localized_price).data("price",s.price),e(t.revenueModelLabelDisplay,i).text(s.revenue_model_label).data("revenue",s.revenue_model),e(t.categoryDefaultPriceInput,i).val(s.price),e(t.categoryTitle,i).text(s.category),e(t.categoryId,i).val(s.category_id),e(t.categoryName,i).val(s.category),i.removeClass(t.unsaved)}u(i),t.navigation.showMessage(s)},"json")},u=function(i,s){if(i.removeClass(t.editing),i.hasClass(t.unsaved))i.velocity("slideUp",{duration:250,easing:"ease-out",complete:function(){e(this).remove(),0===e(t.categoryDefaultPriceForm+":visible").length&&e(t.emptyState,t.categoryDefaultPrices).velocity("fadeIn",{duration:400})}});else{e(t.categoryDefaultPriceEditElements,i).velocity("slideUp",{duration:250,easing:"ease-out"}),e(t.selectCategory,i).select2("destroy"),e(t.categoryDefaultPriceInput,i).val(e(t.categoryDefaultPriceDisplay,i).data("price"));var a=e(t.revenueModelLabelDisplay,i).data("revenue");e(t.revenueModelLabel,i).removeClass(t.selected),e(".lp_js_revenueModelInput[value="+a+"]",i).prop("checked","checked").parent("label").addClass(t.selected),e(t.categoryDefaultPriceShowElements,i).velocity("slideDown",{duration:250,easing:"ease-out"})}s||t.addCategory.velocity("fadeIn",{duration:250,display:"inline-block"})},_=function(i){var s=a(i,!1,e("input[name=price]",i));e("input[name=form]",i).val("price_category_form_delete");var r,o=e("input[name=category]",i).val(),l=i.find("span.lp_js_revenueModelLabelDisplay").text().trim(),n=lpVars.gaData.sandbox_merchant_id+" | ",c="",p="LP WP Pricing";r=n+o+" | "+l,c=Math.ceil(100*s),lpGlobal.sendLPGAEvent("Delete Category Default",p,r,c),e.post(ajaxurl,i.serializeArray(),function(s){s.success&&i.velocity("slideUp",{duration:250,easing:"ease-out",complete:function(){e(this).remove(),0===e(t.categoryDefaultPriceForm+":visible").length&&(e(t.emptyState,t.categoryDefaultPrices).velocity("fadeIn",{duration:400}),e(t.categoryPanelWarning).hide())}}),t.navigation.showMessage(s)},"json")},v=function(t,i){var s=e(i).parent().parent().parent();return e(".lp_js_selectCategory",s).val(t.text),e(".lp_js_categoryDefaultPriceCategoryId",s).val(t.id),t.text},f=function(i,s){var a=e(s).parents("form"),r=t.timepass;return e(a).hasClass(t.subscription.classes.editorForm)&&(r=t.subscription),i.id&&e(r.fields.categoryId,e(a)).val(i.id),e(r.fields.scopeCategory,e(a)).val(i.text),i.text},m=function(i,s,a,r,o){var l="";if("timepass"===o||"subscription"===o){var n=t.timepass;"subscription"===o&&(n=t.subscription),l=e(n.fields.categoryId,i).val(),e(n.fields.scopeCategory,i).val(l),e(s,i).on("change",function(t){e(n.fields.categoryId,i).val(t.val)})}else l=e(s,i).val();e(s,i).select2({allowClear:!0,ajax:{url:ajaxurl,data:function(e){return{form:a,term:e,action:"laterpay_pricing"}},results:function(t){var i=[];return e.each(t.categories,function(e){var s=t.categories[e];i.push({id:s.term_id,text:s.name})}),{results:i}},dataType:"json",type:"POST"},initSelection:function(t,i){"0"!==l&&e.post(ajaxurl,{form:a,terms:l,term:"",action:"laterpay_pricing"},function(t){if(void 0!==t){var s=[];e.each(t.categories,function(e){var i=t.categories[e];s.push({id:i.term_id,text:i.name})}),i(s)}})},formatResult:function(e){return e.text},formatSelection:r,multiple:!0,escapeMarkup:function(e){return e}})},h=function(i){var s=t[i];s.actions.create.velocity("fadeOut",{duration:250}),e(t.emptyState,s.editor).is(":visible")&&e(t.emptyState,s.editor).velocity("fadeOut",{duration:400}),e(s.wrapper).first().before(s.template.clone().removeAttr("id"));var a=e(s.wrapper,s.editor).first();e(s.form,a).addClass(t.unsaved),g(i,a),a.velocity("slideDown",{duration:250,easing:"ease-out",complete:function(){e(this).removeClass(t.hidden)}}).find(s.form).velocity("slideDown",{duration:250,easing:"ease-out",complete:function(){e(this).removeClass(t.hidden)}})},b=function(i,s){var a=t[i],r=e(a.form,a.template).clone();e(a.editorContainer,s).empty().append(r),g(i,s),e(a.actions.modify,s).addClass(t.hidden),e(a.actions.show,s).removeClass(t.hidden),r.removeClass(t.hidden)},g=function(i,s){var r=t[i],o=s.data(r.data.id),l=r.data.list[o],n="";if(l){if(e("input, select, textarea",s).each(function(t,i){n=e(i,s).attr("name"),""!==n&&void 0!==l[n]&&"revenue_model"!==n&&e(i,s).val(l[n])}),"timepass"===i){var c=r.data.vouchers[o];a(s.find("form"),!1,e(r.fields.price,s)),e(t.voucherPriceInput,s).val(e(r.fields.price,s).val()),e(t.revenueModelLabel,s).removeClass(t.selected);var p=e(r.fields.revenueModel+"[value="+l.revenue_model+"]",s);p.prop("checked","checked"),p.parent("label").addClass(t.selected),S(s),c instanceof Object&&e.each(c,function(e,t){E(e,t,s)})}else if("subscription"===i){var d=r.data.vouchers[o];a(s.find("form"),!0,e(r.fields.price,s),!0),e(t.voucherPriceInput,s).val(e(r.fields.price,s).val()),S(s),d instanceof Object&&e.each(d,function(e,t){E(e,t,s)})}e(r.categoryWrapper,s).hide(),m(s,r.fields.scopeCategory,"laterpay_get_categories",f,i);var u=e(r.fields.scope,s).find("option:selected");"0"!==u.val()&&e(r.categoryWrapper,s).show()}},y=function(i,s,a){var r=t[i],o=""!==a.val()?a.val():" ";if(a.hasClass(r.classes.durationClass)||a.hasClass(r.classes.periodClass)){var l=e(r.fields.duration,s).val(),n=e(r.fields.period,s).find("option:selected").text();n=parseInt(l,10)>1?n+"s":n,o=l+" "+n,e(r.preview.validity,s).text(o),"subscription"===i&&e(r.preview.renewal,s).text(lpVars.i18n.after+" "+o)}else if(a.hasClass(r.classes.scopeClass)||a.hasClass(r.classes.scopeCategoryClass)){var c=e(r.fields.scope,s).find("option:selected");o=c.text(),"0"!==c.val()&&(o+=" "+e(r.fields.scopeCategory,s).val()),e(r.preview.access,s).text(o)}else if(a.hasClass(r.classes.priceClass)){var p=e("<small />",{"class":"lp_purchase-link__currency"});p.text(lpVars.currency.code),e(".lp_js_purchaseLink",s).empty().append(o).append(p),e(r.preview.price).text(o+" "+lpVars.currency.code)}else a.hasClass(r.classes.titleClass)?e(r.preview.title,s).text(o):a.hasClass(r.classes.descriptionClass)&&e(r.preview.description,s).text(o)},P=function(i,s){var a=t[i],r=s.find(a.preview.wrapper).data(a.data.id);e(a.form,s).hasClass(t.unsaved)?s.velocity("fadeOut",{duration:250,complete:function(){e(this).remove(),0===e(a.wrapper+":visible").length&&e(t.emptyState,a.editor).velocity("fadeIn",{duration:400})}}):e(a.form,s).velocity("fadeOut",{duration:250,complete:function(){e(this).remove()}}),e(a.actions.modify,s).removeClass(t.hidden),e(a.actions.show,s).addClass(t.hidden),S(s),a.data.vouchers[r]instanceof Object&&(e.each(a.data.vouchers[r],function(e,t){T(e,t,s)}),s.find(t.voucherList).show()),a.actions.create.is(":hidden")&&a.actions.create.velocity("fadeIn",{duration:250,display:"inline-block"})},j=function(i,s){var a=t[i];e.post(ajaxurl,e(a.form,s).serializeArray(),function(r){if(r.success){var o,l,n,c,p=["Hour","Day","Week","Month","Year"],d=s.find(a.preview.wrapper).data(a.data.id),u="Time Pass",_="Pay Later",v=lpVars.gaData.sandbox_merchant_id+" | ",f="";"sis"===r.revenueModel&&(_="Pay Now"),"subscription"===i&&(u="Subscription",_="Pay Now");var m="LP WP Pricing",h="Create "+u;0!==d&&(h="Edit "+u),0===r.data.access_to?c="All":1===r.data.access_to?c="Content except "+r.data.category_name:2===r.data.access_to&&(c="Content in "+r.data.category_name),n=r.data.duration+" "+p[r.data.period],f=Object.keys(r.vouchers).length,l=Math.ceil(100*r.data.price),o=v+n+" | "+_+" | "+c+" | "+f,lpGlobal.sendLPGAEvent(h,m,o,l);var b=r.data[a.data.fields.id];a.data.vouchers[b]=r.vouchers,a.data.list[b]||(s.data(a.data.id,b),e(a.id,s).text(b).parent().velocity("fadeIn",{duration:250})),a.data.list[b]=r.data,e(a.preview.placeholder,s).empty().append(r.html),e(a.actions.show,s).addClass(t.hidden),e(a.actions.modify,s).removeClass(t.hidden),e(a.form,s).velocity("fadeOut",{duration:250,complete:function(){e(this).remove(),x(s,a,b)}}),a.actions.create.is(":hidden")&&a.actions.create.velocity("fadeIn",{duration:250,display:"inline-block"})}t.navigation.showMessage(r)},"json")},D=function(i,s){var a,r,o,l,n=t[i],c=n.data.list[s.data(n.data.id)],p=["Hour","Day","Week","Month","Year"],d="Time Pass",u="Pay Later",_=lpVars.gaData.sandbox_merchant_id+" | ",v="";"sis"===c.revenueModel&&(u="Pay Now"),"subscription"===i&&(d="Subscription",u="Pay Now");var f="LP WP Pricing",m="Delete "+d;0===parseInt(c.access_to)?l="All":1===parseInt(c.access_to)?l="Content except "+c.category_name:2===parseInt(c.access_to)&&(l="Content in "+c.category_name),o=c.duration+" "+p[c.period];var h=n.data.vouchers[s.data(n.data.id)];v="undefined"==typeof h?0:Object.keys(h).length,r=Math.ceil(100*c.price),a=_+o+" | "+u+" | "+l+" | "+v,lpGlobal.sendLPGAEvent(m,f,a,r),confirm(n.data.deleteConfirm)&&s.velocity("slideUp",{duration:250,easing:"ease-out",begin:function(){e.post(ajaxurl,{action:"laterpay_pricing",form:n.ajax.form["delete"],id:s.data(n.data.id)},function(i){i.success?(s.remove(),0===e(n.wrapper+":visible").length&&e(t.emptyState,n.editor).velocity("fadeIn",{duration:400})):e(this).stop().show(),t.navigation.showMessage(i)},"json")}})},w=function(i,s){e(s).parents(t[i].preview.wrapper).toggleClass("lp_is-flipped")},C=function(i,s){var a=t[i],r=e("option:selected",s).val();"0"===r?e(a.categoryWrapper).hide():e(a.categoryWrapper).show()},k=function(i,s,a){var r,o=t[i],l=[],n=24,c=s.val(),p=e(o.fields.duration,a).val();for("4"===c?n=1:"3"===c&&(n=12),r=1;r<=n;r++){var d=e("<option/>",{value:r});d.text(r),l.push(d)}e(o.fields.duration,a).find("option").remove().end().append(l).val(p&&p<=n?p:1)},x=function(i,s,a){S(i),s.data.vouchers[a]instanceof Object&&(e.each(s.data.vouchers[a],function(e,t){T(e,t,i)}),
i.find(t.voucherList).show())},I=function(i,s){var r=!1;if("subscription"===i&&(r=!0),a(s,!0,e(".lp_js_voucherPriceInput",s),r),r){if(s.find(t.voucherPriceInput).val()>s.find(t.subscription.fields.price).val())return e(t.subscription.actions.save).attr("disabled","disabled"),e(t.subscription.actions.save).removeAttr("href"),void s.find(".lp_js_voucher_msg").css("display","block");e(t.subscription.actions.save).removeAttr("disabled"),e(t.subscription.actions.save).attr("href","#")}else{if(s.find(t.voucherPriceInput).val()>s.find(t.timepass.fields.price).val())return e(t.timepass.actions.save).attr("disabled","disabled"),e(t.timepass.actions.save).removeAttr("href"),void s.find(".lp_js_voucher_msg").css("display","block");e(t.timepass.actions.save).removeAttr("disabled"),e(t.timepass.actions.save).attr("href","#")}s.find(".lp_js_voucher_msg").hide(),e.post(ajaxurl,{form:"generate_voucher_code",action:"laterpay_pricing",price:s.find(t.voucherPriceInput).val()},function(e){e.success?E(e.code,s.find(t.voucherPriceInput).val(),s):t.navigation.showMessage(e)},"json")},E=function(i,s,a){var r=s.price?s.price:s,o=r+" "+lpVars.currency.code,l=s.title?s.title:"",n=e("<div/>",{"class":"lp_js_voucher lp_voucher","data-code":i,style:"display:none;"}),c=e("<input/>",{type:"hidden",name:"voucher_code[]",value:i}),p=e("<input/>",{type:"hidden",name:"voucher_price[]",value:r}),d=e("<span/>",{"class":"lp_voucher__code"}).text(i),u=e("<span/>",{"class":"lp_voucher__code-infos"}).text(lpVars.i18n.voucherText+" "+o),_=e("<input/>",{"class":"lp_input__title",type:"text",name:"voucher_title[]",value:l}),v=e("<a/>",{"class":"lp_js_deleteVoucher lp_edit-link--bold","data-icon":"g"});n.empty().append(c).append(p).append(d).append(u).append(_).append(v),a.find(t.voucherPlaceholder).prepend(n).find("div").first().velocity("slideDown",{duration:250,easing:"ease-out"})},T=function(i,s,a){var r=s.title?s.title:"",o=s.price+" "+lpVars.currency.code,l=e("<div/>",{"class":"lp_js_voucher lp_voucher","data-code":i}),n=e("<span/>",{"class":"lp_voucher__title"}).append(e("<b/>").text(r)),c=e("<span/>",{"class":"lp_voucher__code"}).text(i),p=e("<span/>",{"class":"lp_voucher__code-infos"}).text(lpVars.i18n.voucherText+" "+o),d=e("<div/>").append(c).append(p);l.append(n).append(d),a.find(t.voucherList).append(l)},S=function(e){e.find(t.voucher).remove()},V=function(t){t.velocity("slideUp",{duration:250,easing:"ease-out",complete:function(){e(this).remove()}})},M=function(){var i=e("ul.post_types :checkbox:checked"),s=[];e.each(i,function(t){s.push(e(i[t]).next().text().trim())});var a=lpVars.gaData.sandbox_merchant_id+" | ";s=a+s.join(","),e.post(ajaxurl,t.globalEnabledPostTypesForm.serializeArray(),function(e){t.navigation.showMessage(e)},"json"),lpGlobal.sendLPGAEvent("LaterPay Content","LP WP Pricing",s)},L=function(e,t){var i;return function(){var s=this,a=arguments;clearTimeout(i),i=setTimeout(function(){e.apply(s,a)},t)}},O=function(){i()};O()}t()})}(jQuery);
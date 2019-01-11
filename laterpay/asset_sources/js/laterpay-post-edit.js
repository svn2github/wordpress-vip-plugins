/* globals wp,lpGlobal */

(function($) {$(function() {

    // encapsulate all LaterPay Javascript in function laterPayPostEdit
    function laterPayPostEdit() {
        var $o = {
                // post price inputs
                priceInput              : $('#lp_js_postPriceInput'),
                priceTypeInput          : $('#lp_js_postPriceTypeInput'),
                revenueModel            : $('#lp_js_postPriceRevenueModel'),
                categoryInput           : $('#lp_js_postDefaultCategoryInput'),

                // button group for choosing pricing type
                priceSection            : $('#lp_js_priceType'),
                pricingTypeButtonGroup  : $('#lp_js_priceTypeButtonGroup'),
                pricingTypeButtons      : $('.lp_js_priceTypeButton'),
                individualPriceButton   : $('#lp_js_useIndividualPrice').parent(),
                categoryPriceSelector   : '#lp_js_useCategoryDefaultPrice',
                categoryPriceButton     : $('#lp_js_useCategoryDefaultPrice').parent(),
                globalPriceButton       : $('#lp_js_useGlobalDefaultPrice').parent(),
                postEditTypeZero        : $('#lp_postEditTypeZero'),
                priceEditSection        : $('#lp_js_priceEditSection'),

                // details sections for chosen pricing type
                details                 : $('#lp_js_priceTypeDetails'),
                detailsSections         : $('.lp_js_priceTypeDetailsSection'),
                individualPriceDetails  : $('#lp_js_priceTypeDetailsIndividualPrice'),
                categoryPriceDetails    : $('#lp_js_priceTypeDetailsCategoryDefaultPrice'),
                categoriesList          : $('.lp_js_priceTypeDetailsCategoryDefaultPriceList'),
                categories              : $('.lp_js_priceTypeDetailsCategoryDefaultPriceItem'),
                dynamicPricingToggle    : $('#lp_js_toggleDynamicPricing'),
                dynamicPricingContainer : '#lp_js_dynamicPricingWidgetContainer',
                dynamicPricingResetDate : $('#lp_js_resetDynamicPricingStartDate'),

                // strings cached for better compression
                expanded                : 'lp_is-expanded',
                selected                : 'lp_is-selected',
                disabled                : 'lp_is-disabled',
                dynamicPricingApplied   : 'lp_is-withDynamicPricing',
                selectedCategory        : 'lp_is-selectedCategory',
                payPerUse               : 'ppu',
                singleSale              : 'sis'
            },

            /**
             * Category ids selected by the user from categories meta box.
             *
             * @type {Array}
             */
            categoryIds = [],

            bindEvents = function() {
                // switch pricing type
                $o.pricingTypeButtons
                .mousedown(function() {
                    switchPricingType(this);
                })
                .click(function(e) {e.preventDefault();});

                // save pricing data
                $('#post')
                .submit(function() {
                    saveDynamicPricingData();
                });

                subscribeToGutenbergUpdates();

                // validate manually entered prices
                // (function is only triggered 800ms after the keyup)
                $o.priceInput
                .keyup(
                    debounce(function() {
                        setPrice($(this).val());
                    }, 800)
                );

                // validate choice of revenue model (validating the price switches the revenue model if required)
                $('input:radio', $o.revenueModel)
                .change(function() {
                    validatePrice($o.priceInput.val());
                });

                // toggle dynamic pricing widget
                $o.dynamicPricingToggle
                .mousedown(function() {
                    toggleDynamicPricing();
                })
                .click(function(e) {e.preventDefault();});

                // reset dynamic pricing date
                $o.dynamicPricingResetDate
                .mousedown(function() {
                    resetPostDate(lpVars.postId);
                })
                .click(function(e) {e.preventDefault();});

                // update list of applicable category prices on change of categories list
                $('.categorychecklist input:checkbox')
                .on('change', function() {
                    updateApplicableCategoriesList();
                });

                // apply category default prices when selecting one of the applicable categories
                $o.categoryPriceDetails
                .on('mousedown', 'a', function() {
                    applyCategoryPrice(this);
                })
                .on('click', 'a', function(e) {e.preventDefault();});

                // Hide Pricing Section If Post Price Behaviour is Free and Global Default Price is selected.
                $o.globalPriceButton.on( 'click', 'a', function (e) {
                    e.preventDefault();

                    if ( $o.globalPriceButton.hasClass('lp_is-disabled') ) {
                        return;
                    }

                    if ( '0' === lpVars.postPriceBehaviour ) {
                        $o.priceEditSection.hide();
                        $o.postEditTypeZero.show();
                    }
                });

                // Display Price Editing Section if hidden.
                $o.individualPriceButton.on( 'click', 'a', function (e) {
                    e.preventDefault();

                    if ( $o.individualPriceButton.hasClass('lp_is-disabled') ) {
                        return;
                    }

                    $o.priceEditSection.show();

                    if ( '0' === lpVars.postPriceBehaviour ) {
                        $o.postEditTypeZero.hide();
                    }

                });

                // Display Price Editing Section if hidden.
                $($o.categoryPriceSelector).on( 'click', function (e) {
                    e.preventDefault();

                    if ( $($o.categoryPriceSelector).parent().hasClass('lp_is-disabled') ) {
                        return;
                    }

                    $o.priceEditSection.show();
                });

            },

            /**
             * Subscribe to gutenberg editor updates.
             *
             * @return {void}
             */
            subscribeToGutenbergUpdates = function() {
                var editPost, editor, categories;

                // Bail out early if gutenberg is not enabled.
                if ( ! wp.data ) {
                    return;
                }

                editPost = wp.data.select( 'core/edit-post' );
                editor = wp.data.select( 'core/editor' );
                categories = editor.getPostEdits().categories;

                wp.data.subscribe( function() {
                    if ( editPost.isSavingMetaBoxes() ) {

                        // Gutenberg does not save tinyMCE by default.
                        if ( window.tinyMCE ) {
                            window.tinyMCE.triggerSave();
                        }

                        saveDynamicPricingData();
                    }

                    // Checks if the category value has changed.
                    if ( categories !== editor.getPostEdits().categories ) {
                        categoryIds = editor.getPostEdits().categories;

                        updateApplicableCategoriesList();
                        categories = categoryIds;
                    }
                } );
            },

            switchPricingType = function(trigger) {
                var $this           = $(trigger),
                    $clickedButton  = $this.parent('li'),
                    priceType       = $this.attr('id'),
                    price,
                    revenueModel;

                if ($clickedButton.hasClass($o.disabled) || $clickedButton.hasClass($o.selected)) {
                    return;
                }

                // set state of button group
                $('.' + $o.selected, $o.pricingTypeButtonGroup).removeClass($o.selected);
                $clickedButton.addClass($o.selected);
                $o.priceSection.removeClass($o.expanded);

                // hide details sections
                $o.detailsSections.velocity('slideUp', { duration: 250 });

                // case: individual price
                if (priceType === 'lp_js_useIndividualPrice') {
                    $o.priceSection.addClass($o.expanded);
                    $o.dynamicPricingToggle.velocity('fadeIn', { duration: 250, display: 'block' });
                    $o.priceTypeInput.val('individual price');

                    // validate price to enable all applicable revenue models
                    validatePrice($o.priceInput.val());

                    // show / hide stuff
                    if ($o.dynamicPricingToggle.text() === lpVars.i18nRemoveDynamicPricing) {
                        renderDynamicPricingWidget();
                        $o.individualPriceDetails.velocity('slideDown', { duration: 250 });
                    }
                }
                // case: category default price
                else if (priceType === 'lp_js_useCategoryDefaultPrice') {
                    updateSelectedCategory();

                    // set the price and revenue model of the selected category
                    var $category   = $('.lp_is-selectedCategory a', $o.categoriesList);
                    price           = $category.attr('data-price');
                    revenueModel    = $category.attr('data-revenue-model');
                    setPrice(price);
                    setRevenueModel(revenueModel, true);

                    // show / hide stuff
                    $o.priceSection.addClass($o.expanded);
                    $o.categoryPriceDetails.velocity('slideDown', { duration: 250 });
                    $o.categories.velocity('slideDown', { duration: 250 });
                    $o.dynamicPricingToggle.velocity('fadeOut', { duration: 250 });
                    $o.priceTypeInput.val('category default price');
                }
                // case: global default price
                else if (priceType === 'lp_js_useGlobalDefaultPrice') {
                    price           = $this.attr('data-price');
                    revenueModel    = $this.attr('data-revenue-model');

                    setPrice(price);
                    setRevenueModel(revenueModel, true);

                    // show / hide stuff
                    $o.dynamicPricingToggle.velocity('fadeOut', { duration: 250 });
                    $o.priceTypeInput.val('global default price');
                }

                // disable price input for all scenarios other than static individual price
                if (
                    priceType === 'lp_js_useIndividualPrice' &&
                    !$o.dynamicPricingToggle.hasClass($o.dynamicPricingApplied)
                ) {
                    $o.priceInput.removeAttr('disabled');
                    setTimeout(function() {
                        $o.priceInput.focus();
                    }, 50);
                } else {
                    if ($o.dynamicPricingToggle.hasClass($o.dynamicPricingApplied)) {
                        disableDynamicPricing();
                    }
                    $o.priceInput.attr('disabled', 'disabled');
                }
            },

            setPrice = function(price) {
                var validatedPrice = validatePrice(price);
                $o.priceInput.val(validatedPrice);
            },

            setRevenueModel = function(revenueModel, readOnly) {
                $('label', $o.revenueModel).removeClass($o.selected);

                if (readOnly) {
                    // disable not-selected revenue model
                    $('input:radio[name=post_revenue_model]', $o.revenueModel)
                        .parent('label').addClass($o.disabled);
                }

                // enable and check selected revenue model
                $('input:radio[value=' + revenueModel + ']', $o.revenueModel)
                .prop('checked', 'checked')
                    .parent('label')
                    .removeClass($o.disabled)
                    .addClass($o.selected);
            },

            validatePrice = function(price) {
                // strip non-number characters
                price = price.toString().replace(/[^0-9\,\.]/g, '');

                // convert price to proper float value
                if (typeof price === 'string' && price.indexOf(',') > -1) {
                    price = parseFloat(price.replace(',', '.')).toFixed(2);
                } else {
                    price = parseFloat(price).toFixed(2);
                }

                // prevent non-number prices
                if (isNaN(price)) {
                    price = 0;
                }

                // prevent negative prices
                price = Math.abs(price);

                // correct prices outside the allowed range of 0.05 - 149.49
                if (price > lpVars.limits.sis_max) {
                    price = lpVars.limits.sis_max;
                } else if (price > 0 && price < lpVars.limits.ppu_min) {
                    price = lpVars.limits.ppu_min;
                }

                validateRevenueModel(price);

                // format price with two digits
                price = price.toFixed(2);

                // localize price
                if (lpVars.locale.indexOf( 'de_DE' ) !== -1) {
                    price = price.replace('.', ',');
                }

                return price;
            },

            validateRevenueModel = function(price) {
                var currentRevenueModel = $('input:radio:checked', $o.revenueModel).val(),
                    $payPerUse          = $('input:radio[value=' + $o.payPerUse + ']', $o.revenueModel),
                    $singleSale         = $('input:radio[value=' + $o.singleSale + ']', $o.revenueModel);

                if (price === 0 || (price >= lpVars.limits.ppu_min && price <= lpVars.limits.ppu_max)) {
                    // enable Pay-per-Use for 0 and all prices between 0.05 and 5.00 Euro
                    $payPerUse.parent('label').removeClass($o.disabled);
                } else {
                    // disable Pay-per-Use
                    $payPerUse.parent('label').addClass($o.disabled);
                }

                if (price >= lpVars.limits.sis_min) {
                    // enable Single Sale for prices >= 1.49 Euro
                    // (prices > 149.99 Euro are fixed by validatePrice already)
                    $singleSale.parent('label').removeClass($o.disabled);
                } else {
                    // disable Single Sale
                    $singleSale.parent('label').addClass($o.disabled);
                }

                // switch revenue model, if combination of price and revenue model is not allowed
                if (price > lpVars.limits.ppu_max && currentRevenueModel === $o.payPerUse) {
                    // Pay-per-Use purchases are not allowed for prices > 5.00 Euro
                    $singleSale.prop('checked', true);
                } else if (price < lpVars.limits.sis_min && currentRevenueModel === $o.singleSale) {
                    // Single Sale purchases are not allowed for prices < 1.49 Euro
                    $payPerUse.prop('checked', true);
                }

                // highlight current revenue model
                $('label', $o.revenueModel).removeClass($o.selected);
                $('input:radio:checked', $o.revenueModel).parent('label').addClass($o.selected);
            },

            updateSelectedCategory = function() {
                var selectedCategoryId  = $o.categoryInput.val(),
                    $firstCategory      = $o.categories.first();

                if (!$o.categories.length) {
                    $o.categoryInput.val('');
                    return;
                }

                if (
                    typeof(selectedCategoryId) !== 'undefined' &&
                    $('[data-category=' + selectedCategoryId + ']', $o.categories.parent()).length
                ) {
                    $('[data-category=' + selectedCategoryId + ']', $o.categories.parent())
                    .addClass($o.selectedCategory);
                } else {
                    // select the first category in the list, if none is selected
                    $firstCategory.addClass($o.selectedCategory);
                    $o.categoryInput.val($firstCategory.data('category'));
                }

                // also update price and revenue model, if the selected category has changed
                // in pricing mode 'category default price'
                if ($o.categoryPriceButton.hasClass($o.selected)) {
                    var $category       = $('.lp_is-selectedCategory a', $o.categoriesList),
                        price           = $category.attr('data-price'),
                        revenueModel    = $category.attr('data-revenue-model');

                    setPrice(price);
                    setRevenueModel(revenueModel, true);
                }
            },

            updateApplicableCategoriesList = function() {
                var $selectedCategories = $('#categorychecklist :checkbox:checked'),
                    l                   = $selectedCategories.length,
                    categoriesList      = [],
                    i, categoryId;

                if ( ! wp.data ) {
                    categoryIds = [];

                    for (i = 0; i < l; i++) {
                        categoryId = parseInt($selectedCategories.eq(i).val(), 10);
                        categoryIds.push(categoryId);
                    }
                }

                // make Ajax request for prices and names of categories
                $.post(
                    lpVars.ajaxUrl,
                    {
                        action          : 'laterpay_get_category_prices',
                        form            : 'laterpay_get_category_prices',
                        category_ids    : categoryIds
                    },
                    function(data) {
                        // rebuild list of categories in category default pricing tab
                        if (data.success && data.prices) {
                            data.prices.forEach(function(category) {
                                var price = parseFloat(category.category_price).toFixed(2) + ' ' + lpVars.currency;

                                var newCategory = $('<li/>',{
                                    'data-category': category.category_id,
                                    'calss': 'lp_price-type-categorized__item',
                                }).append($('<a/>',{
                                    'href': '#',
                                    'data-price': category.category_price,
                                    'data-revenue-model': category.revenue_model,
                                }).append($('<span/>').text(price)).append(category.category_name));

                                categoriesList.push(newCategory);
                            });
                            $o.categoriesList.empty().append(categoriesList);

                            if (data.prices.length) {
                                $o.categoryPriceButton.removeClass($o.disabled).removeClass($o.selected)
                                    .removeClass( 'lp_tooltip' );
                                // update cached selector
                                $o.categories = $('#lp_js_priceTypeDetailsCategoryDefaultPrice li');
                                switchPricingType($o.categoryPriceSelector);
                                $o.globalPriceButton.addClass($o.disabled).addClass( 'lp_tooltip' )
                                    .attr( 'data-tooltip', lpVars.i18nGlobalDisabled );
                            } else {
                                // disable the 'use category default price' button,
                                // if no categories with an attached default price are applied to the current post
                                $o.categoryPriceButton.addClass($o.disabled).addClass( 'lp_tooltip' )
                                    .attr( 'data-tooltip', lpVars.i18nCategoryPriceSelect );

                                if ( data.no_category_price_set === true ) {
                                    $o.categoryPriceButton.attr( 'data-tooltip', lpVars.i18nCategoryPriceNotSetup );
                                }

                                $o.globalPriceButton.removeClass($o.disabled).removeClass( 'lp_tooltip' );

                                // hide details sections
                                $o.detailsSections.velocity('fadeOut', { duration: 250 });

                                // if current pricing type is 'category default price'
                                // fall back to global default price or an individual price of 0
                                if ($o.categoryPriceButton.hasClass($o.selected)) {
                                    $('.' + $o.selected, $o.pricingTypeButtonGroup).removeClass($o.selected);
                                    $o.priceSection.removeClass($o.expanded);

                                    if ($o.globalPriceButton.hasClass($o.disabled)) {
                                        // case: fall back to individual price
                                        $o.individualPriceButton.addClass($o.selected);
                                        $o.priceTypeInput.val('individual price');
                                        $o.dynamicPricingToggle.velocity('fadeIn', { duration: 250, display: 'block' });
                                        $o.priceInput.removeAttr('disabled');
                                        setPrice(0);
                                        setRevenueModel($o.payPerUse, false);
                                    } else {
                                        // case: fall back to global default price
                                        $o.globalPriceButton.addClass($o.selected);
                                        $o.priceTypeInput.val('global default price');
                                        setPrice(lpVars.globalDefaultPrice);
                                        setRevenueModel($('a', $o.globalPriceButton).attr('data-revenue-model'), true);
                                    }
                                }
                            }
                        }
                    },
                    'json'
                );
            },

            applyCategoryPrice = function(trigger) {
                var $this           = $(trigger),
                    $category       = $this.parent(),
                    category        = $category.attr('data-category'),
                    price           = $this.attr('data-price'),
                    revenueModel    = $this.attr('data-revenue-model');

                $o.categories.removeClass($o.selectedCategory);
                $category.addClass($o.selectedCategory);
                $o.categoryInput.val(category);

                setPrice(price);
                setRevenueModel(revenueModel, true);
            },

            toggleDynamicPricing = function() {
                if ($o.dynamicPricingToggle.hasClass($o.dynamicPricingApplied)) {
                    disableDynamicPricing();
                    $o.revenueModel.velocity('fadeIn', { duration: 250, display: 'inline-block' });
                } else {
                    enableDynamicPricing();
                }
            },

            resetPostDate = function(postId) {
                $.post(
                    lpVars.ajaxUrl,
                    {
                        action          : 'laterpay_reset_post_publication_date',
                        post_id         : postId,
                    },
                    function(data) {
                        if (data.success) {
                            window.location.reload();
                        } else if (data.message) {
                            alert(data.message);
                        }
                    },
                    'json'
                );
            },

            enableDynamicPricing = function() {
                renderDynamicPricingWidget();
                $o.dynamicPricingToggle.addClass($o.dynamicPricingApplied);
                $o.priceInput.attr('disabled', 'disabled');
                $o.individualPriceDetails.velocity('slideDown', { duration: 250 });
                $o.priceTypeInput.val('individual price, dynamic');
                $o.dynamicPricingToggle.text(lpVars.i18nRemoveDynamicPricing).attr('data-icon', 'e');
            },

            disableDynamicPricing = function() {
                removeDynamicPricing();
                $o.dynamicPricingToggle.removeClass($o.dynamicPricingApplied);
                $o.individualPriceDetails.velocity('slideUp', {
                    duration: 250,
                    complete: function() {
                        $($o.dynamicPricingContainer).empty();
                    }
                });
                $o.dynamicPricingResetDate.velocity('fadeOut', { duration: 250 });
                $o.dynamicPricingToggle.text(lpVars.i18nAddDynamicPricing).attr('data-icon', 'c');
                if ($o.priceTypeInput.val() === 'individual price, dynamic') {
                    $o.priceTypeInput.val('individual price');
                    $o.priceInput.removeAttr('disabled');
                }
            },

            removeDynamicPricing = function() {
                $.post(
                    lpVars.ajaxUrl,
                    {
                        action  : 'laterpay_remove_post_dynamic_pricing',
                        post_id : lpVars.postId
                    },
                    function(data) {
                        if (data.message) {
                            alert(data.message);
                        }
                    },
                    'json'
                );
            },

            renderDynamicPricingWidget = function() {
                $.post(
                    lpVars.ajaxUrl,
                    {
                        action          : 'laterpay_get_dynamic_pricing_data',
                        post_id         : lpVars.postId,
                        post_price      : $o.priceInput.val()
                    },
                    function(data) {
                        if (data) {
                            var dynamicPricingWidget    = new DynamicPricingWidget($o.dynamicPricingContainer),
                                startPrice              = data.values[0].y,
                                endPrice                = data.values[3].y,
                                minPrice                = 0,
                                maxPrice                = 5;
                            window.dynamicPricingWidget = dynamicPricingWidget;

                            $o.priceInput.attr('disabled', 'disabled');

                            if (startPrice > lpVars.limits.ppu_max || endPrice > lpVars.limits.ppu_max) {
                                // Single Sale
                                maxPrice = lpVars.limits.sis_max;
                                minPrice = lpVars.limits.sis_only_limit;
                            } else if (startPrice >= lpVars.limits.sis_min || endPrice >= lpVars.limits.sis_min) {
                                // Pay-per-Use and Single Sale
                                maxPrice = lpVars.limits.ppu_max;
                                minPrice = lpVars.limits.sis_min;
                            } else {
                                // Pay-per-Use
                                maxPrice = lpVars.limits.ppu_only_limit;
                                minPrice = lpVars.limits.ppu_min;
                            }

                            if (data.price.pubDays > 0 && data.price.pubDays <= 30) {
                                dynamicPricingWidget.set_today(data.price.pubDays, data.price.todayPrice);
                            }

                            if (data.values.length === 4) {
                                dynamicPricingWidget
                                .set_data(data.values)
                                .setPrice(minPrice, maxPrice, lpVars.globalDefaultPrice)
                                .plot();
                            } else {
                                dynamicPricingWidget
                                .set_data(data.values)
                                .setPrice(minPrice, maxPrice, lpVars.globalDefaultPrice)
                                .interpolate('step-before')
                                .plot();
                            }
                        }
                    },
                    'json'
                );
            },

            saveDynamicPricingData = function() {

                // Get Data to be sent to GA.
                var selectedType = jQuery('#lp_js_priceTypeButtonGroup li.lp_is-selected').text().trim();
                var lpPrice = validatePrice($o.priceInput.val()) * 100;
                var eventAction = 'Pricing for Post';
                var eventCategory = 'LP WP Post';
                var commonLabel = lpVars.gaData.sandbox_merchant_id + ' | ' + lpVars.postId + ' | ';

                var categoryLabel = [];

                // Check editor type to get selected categories in post.
                if ( ! wp.data ) {
                    var selectedCategories = $('#categorychecklist :checkbox:checked');

                    // Loop through selected categories and store in an array.
                    $.each( selectedCategories, function( i ) {
                        categoryLabel.push($('#'+selectedCategories[i].id).parent().text().trim());
                    } );
                } else {
                    var selectedCategoriesGB =
                        $('div.editor-post-taxonomies__hierarchical-terms-list :checkbox:checked');

                    // Loop through checked categories and store label text in an array.
                    $.each( selectedCategoriesGB, function( i ) {
                        categoryLabel.push($(selectedCategoriesGB[i]).next( 'label' ).text().trim());
                    } );

                }

                // Send GA event with category details.
                lpGlobal.sendLPGAEvent( 'Post Published', eventCategory, commonLabel + categoryLabel.join(',') );

                // don't try to save dynamic pricing data, if pricing type is not dynamic but static
                if (!$o.dynamicPricingToggle.hasClass($o.dynamicPricingApplied)) {
                    lpGlobal.sendLPGAEvent( eventAction, eventCategory, commonLabel + selectedType, lpPrice );
                    return;
                }

                // save dynamic pricing data
                var data = window.dynamicPricingWidget.get_data();
                if (window.dynamicPricingWidget.get_data().length === 4) {
                    $('input[name=start_price]').val(data[0].y);
                    $('input[name=end_price]').val(data[3].y);
                    $('input[name=change_start_price_after_days]').val(data[1].x);
                    $('input[name=transitional_period_end_after_days]').val(data[2].x);
                    $('input[name=reach_end_price_after_days]').val(data[3].x);

                    // Send GA event with dynamic price data.
                    lpGlobal.sendLPGAEvent( eventAction, eventCategory, commonLabel + selectedType,
                        Math.round( data[3].y * 100 ) );
                    lpGlobal.sendLPGAEvent( eventAction, eventCategory, commonLabel + 'Dynamic Price',
                        Math.round( data[0].y * 100 ) );

                } else if (window.dynamicPricingWidget.get_data().length === 3) {
                    $('input[name=start_price]').val(data[0].y);
                    $('input[name=end_price]').val(data[2].y);
                    $('input[name=change_start_price_after_days]').val(data[1].x);
                    $('input[name=transitional_period_end_after_days]').val(0);
                    $('input[name=reach_end_price_after_days]').val(data[2].x);
                }

                return true;
            },

            // throttle the execution of a function by a given delay
            debounce = function(fn, delay) {
              var timer;
              return function() {
                var context = this,
                    args    = arguments;

                clearTimeout(timer);

                timer = setTimeout(function() {
                  fn.apply(context, args);
                }, delay);
              };
            },

            initializePage = function() {
                bindEvents();

                if ($o.dynamicPricingToggle.hasClass($o.dynamicPricingApplied)) {
                    renderDynamicPricingWidget();
                }
            };

        initializePage();
    }

    // initialize page
    laterPayPostEdit();

});})(jQuery);

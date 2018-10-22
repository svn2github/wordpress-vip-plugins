(function($) {$(function() {

    // encapsulate all LaterPay Javascript in function laterPayPostView
    function laterPayPostView() {
        var $o = {
                body                            : $('body'),

                // post preview mode
                previewModePlaceholder          : $('#lp_js_previewModePlaceholder'),
                previewModeContainer            : '#lp_js_previewModeContainer',
                previewModeForm                 : '#lp_js_previewModeForm',
                previewModeToggle               : '#lp_js_togglePreviewMode',
                previewModeInput                : '#lp_js_previewModeInput',

                previewModeVisibilityForm       : '#lp_js_previewModeVisibilityForm',
                previewModeVisibilityToggle     : '#lp_js_togglePreviewModeVisibility',
                previewModeVisibilityInput      : '#lp_js_previewModeVisibilityInput',

                optionContainer                 : '.lp_purchase-overlay-option',
                optionInput                     : '.lp_purchase-overlay-option__input',
                submitButtonText                : '.lp_purchase-overlay__submit-text',

                // time passes
                timePass                        : '.lp_js_timePass',
                flipTimePassLink                : '.lp_js_flipTimePass',
                timePassPreviewPrice            : '.lp_js_timePassPreviewPrice',
                voucherCodeWrapper              : '#lp_js_voucherCodeWrapper',
                voucherCodeInput                : '.lp_js_voucherCodeInput',
                voucherRedeemButton             : '.lp_js_voucherRedeemButton',
                giftCardRedeemButton            : '.lp_js_giftCardRedeemButton',
                giftCardCodeInput               : '.lp_js_giftCardCodeInput',
                giftCardWrapper                 : '#lp_js_giftCardWrapper',
                giftCardActionsPlaceholder      : '.lp_js_giftCardActionsPlaceholder',
                giftsWrapper                    : $('.lp_js_giftsWrapper'),

                // subscriptions
                subscription                    : '.lp_js_subscription',
                flipSubscriptionLink            : '.lp_js_flipSubscription',

                // placeholders for caching compatibility mode
                postContentPlaceholder          : $('#lp_js_postContentPlaceholder'),

                // purchase buttons and purchase links
                purchaseLink                    : '.lp_js_doPurchase',
                purchaseOverlay                 : '.lp_js_overlayPurchase',
                currentOverlay                  : 'input[name="lp_purchase-overlay-option"]:checked',

                // strings cached for better compression
                hidden                          : 'lp_is-hidden',
                fadingOut                       : 'lp_is-fading-out',

                // premium content
                premiumBox                      : '.lp_js_premium-file-box',

                // redeem voucher
                redeemVoucherBlock              : $('.lp_purchase-overlay__voucher'),
                notificationButtons             : $('.lp_js_notificationButtons'),
                notificationCancel              : $('.lp_js_notificationCancel'),
                voucherCancel                   : '.lp_js_voucherCancel',
                redeemVoucherButton             : '.lp_js_redeemVoucher',
                overlayMessageContainer         : '.lp_js_purchaseOverlayMessageContainer',
                overlayTimePassPrice            : '.lp_js_timePassPrice'
            },

            // Messages templates

            timePassFeedbackMessage = function (msg) {
                var message = $('<div/>', {
                    id: 'lp_js_voucherCodeFeedbackMessage',
                    class: 'lp_voucher__feedback-message',
                    style: 'display:none;'
                }).text(msg);

                return message;
            },

            purchaseOverlayFeedbackMessage = function (msg) {
                var message = $('<div/>', {
                    id: 'lp_js_voucherCodeFeedbackMessage',
                    class: 'lp_purchase-overlay__voucher-error'
                }).text(msg);

                return message;
            },

            // DOM cache

            recachePreviewModeContainer = function() {
                $o.previewModeContainer = $('#lp_js_previewModeContainer');
                $o.previewModeForm  = $('#lp_js_previewModeForm');
                $o.previewModeToggle = $('#lp_js_togglePreviewMode');
                $o.previewModeInput = $('#lp_js_previewModeInput');

                $o.previewModeVisibilityForm = $('#lp_js_previewModeVisibilityForm');
                $o.previewModeVisibilityToggle = $('#lp_js_togglePreviewModeVisibility');
                $o.previewModeVisibilityInput = $('#lp_js_previewModeVisibilityInput');
            },

            // Binding Events

            bindPreviewModeEvents = function() {
                $o.previewModeToggle.on('change', function() {
                    togglePreviewMode();
                });

                // toggle visibility of post statistics pane
                $o.previewModeVisibilityToggle
                    .on('mousedown', function() {
                        togglePreviewModeVisibility();
                    })
                    .on('click', function(e) {e.preventDefault();});
            },

            bindPurchaseEvents = function() {
                // handle clicks on purchase links in test mode
                $o.body
                    .on('mousedown', $o.purchaseLink, function() {
                        handlePurchaseInTestMode(this);
                    })
                    .on('click', $o.purchaseLink, function(e) {
                        // redirect to the laterpay side
                        e.preventDefault();
                        if ( $(this).data( 'preview-post-as-visitor' ) ) {
                            alert(lpVars.i18n.alert);
                        } else {
                            window.location.href = $(this).data('laterpay');
                        }
                    });

                $o.body
                    .on('mousedown', $o.purchaseOverlay, function() {
                        handlePurchaseInTestMode(this);
                    })
                    .on('click', $o.purchaseOverlay, function(e) {
                        // redirect to the laterpay side
                        e.preventDefault();
                        if ( $(this).data( 'preview-post-as-visitor' ) ) {
                            alert(lpVars.i18n.alert);
                        } else {
                            purchaseOverlaySubmit($(this).attr('data-purchase-action'));
                        }
                    });

                // select radio input by clicking on a container
                $o.body
                    .on('click', $o.optionContainer, function (e) {
                        e.preventDefault();
                        $(this).find($o.optionInput).attr('checked', 'checked');

                        switch( $(this).data('revenue') ) {
                            // buy now
                            case 'sis':
                                $($o.submitButtonText).text(lpVars.i18n.revenue.sis);
                                break;
                            // subscription
                            case 'sub':
                                $($o.submitButtonText).text(lpVars.i18n.revenue.sub);
                                break;
                            // pay later
                            case 'ppu':
                            /* falls through */
                            default:
                                $($o.submitButtonText).text(lpVars.i18n.revenue.ppu);
                                break;
                        }
                    });

                // show redeem voucher input
                $o.body
                    .on('click', $o.redeemVoucherButton, function (e) {
                        e.preventDefault();

                        $o.redeemVoucherBlock.removeClass('lp_hidden');
                        $o.notificationButtons.addClass('lp_hidden');
                        $o.notificationCancel.removeClass('lp_hidden');

                        $($o.purchaseOverlay).find('[data-buy-label="true"]').addClass('lp_hidden');
                        $($o.purchaseOverlay).find('[data-voucher-label="true"]').removeClass('lp_hidden');
                        $($o.purchaseOverlay).attr('data-purchase-action', 'voucher');
                    });

                // hide redeem voucher input
                $o.body
                    .on('click', $o.voucherCancel, function (e) {
                        e.preventDefault();

                        $o.redeemVoucherBlock.addClass('lp_hidden');
                        $o.notificationButtons.removeClass('lp_hidden');
                        $o.notificationCancel.addClass('lp_hidden');

                        $($o.purchaseOverlay).find('[data-buy-label="true"]').removeClass('lp_hidden');
                        $($o.purchaseOverlay).find('[data-voucher-label="true"]').addClass('lp_hidden');
                        $($o.purchaseOverlay).attr('data-purchase-action', 'buy');
                    });

                // handle clicks on time passes
                $o.body
                    .on('click', $o.flipTimePassLink, function(e) {
                        e.preventDefault();
                        flipTimePass(this);
                    });

                // handle clicks on subscription
                $o.body
                    .on('click', $o.flipSubscriptionLink, function(e) {
                        e.preventDefault();
                        flipTimePass(this);
                    });
            },

            purchaseOverlaySubmit = function (action) {
                if (action === 'buy') {
                    window.location.href = $($o.currentOverlay).val();
                }

                if (action === 'voucher') {
                    $($o.overlayMessageContainer).html('');

                    redeemVoucherCode(
                        $($o.overlayMessageContainer),
                        purchaseOverlayFeedbackMessage,
                        $o.voucherCodeInput,
                        'purchase-overlay',
                        false
                    );
                }

                return false;
            },

            bindTimePassesEvents = function() {
                // redeem voucher code
                $($o.voucherRedeemButton)
                    .on('mousedown', function() {
                        redeemVoucherCode(
                            $(this).parent(),
                            timePassFeedbackMessage,
                            $o.voucherCodeInput,
                            'time-pass',
                            false
                        );
                    })
                    .on('click', function(e) {e.preventDefault();});

                $($o.giftCardRedeemButton)
                    .on('mousedown', function() {
                        redeemVoucherCode(
                            $(this).parent(),
                            timePassFeedbackMessage,
                            $o.giftCardCodeInput,
                            'time-pass',
                            true
                        );
                    })
                    .on('click', function(e) {e.preventDefault();});
            },

            redeemVoucherCode = function($wrapper, feedbackMessageTpl, input, type, is_gift) {
                var code = $(input).val();

                if (code.length === 6) {
                    $.get(
                        lpVars.ajaxUrl,
                        {
                            action  : 'laterpay_redeem_voucher_code',
                            code    : code,
                            link    : window.location.href
                        },
                        function(r) {
                            // clear input
                            $(input).val('');

                            if (r.success) {
                                if (!is_gift) {
                                    var has_matches = false,
                                        passId,subId;

                                    if ( 'time_pass' === r.type ) {
                                        $($o.timePass).each(function() {
                                            // Check for each shown time pass,
                                            // if the request returned updated data for it.
                                            passId = $(this).data('pass-id');
                                            if (passId === r.pass_id) {
                                                has_matches = true;
                                                return false;
                                            }
                                        });
                                    }

                                    if ( 'subscription' === r.type ) {
                                        $($o.subscription).each(function() {
                                            // Check for each shown subscription,
                                            // if the request returned updated data for it.
                                            subId = $(this).data('sub-id');
                                            if (subId === r.sub_id) {
                                                has_matches = true;
                                                return false;
                                            }
                                        });
                                    }

                                    if (has_matches) {
                                        // voucher is valid for at least one displayed time pass ->
                                        // forward to purchase dialog
                                        window.location.href = r.url;
                                    } else {
                                        // voucher is invalid for all displayed time passes
                                        showVoucherCodeFeedbackMessage(
                                            code + lpVars.i18n.invalidVoucher,
                                            feedbackMessageTpl,
                                            type,
                                            $wrapper
                                        );
                                    }
                                } else {
                                    $('#fakebtn')
                                        .attr('data-laterpay', r.url)
                                        .click();
                                }
                            } else {
                                // voucher is invalid for all displayed time passes
                                showVoucherCodeFeedbackMessage(
                                    code + lpVars.i18n.invalidVoucher,
                                    feedbackMessageTpl,
                                    type,
                                    $wrapper
                                );
                            }
                        },
                        'json'
                    );
                } else {
                    // request was not sent, because voucher code is not six characters long
                    showVoucherCodeFeedbackMessage(lpVars.i18n.codeTooShort, feedbackMessageTpl, type, $wrapper);
                }
            },

            showVoucherCodeFeedbackMessage = function(message, tpl, type, $wrapper) {
                var $feedbackMessage = tpl(message);

                if (type === 'purchase-overlay') {
                    $wrapper.empty().append($feedbackMessage);
                }

                if (type === 'time-pass') {
                    $wrapper.prepend($feedbackMessage);

                    $feedbackMessage = $('#lp_js_voucherCodeFeedbackMessage', $wrapper);
                    $feedbackMessage
                        .fadeIn(250)
                        .click(function() {
                            // remove feedback message on click
                            removeVoucherCodeFeedbackMessage($feedbackMessage);
                        });

                    // automatically remove feedback message after 3 seconds
                    setTimeout(function() {
                        removeVoucherCodeFeedbackMessage($feedbackMessage);
                    }, 3000);
                }
            },

            removeVoucherCodeFeedbackMessage = function($feedbackMessage) {
                $feedbackMessage.fadeOut(250, function() {
                    $feedbackMessage.unbind().remove();
                });
            },

            loadGiftCards = function() {
                var ids     = [],
                    cards   = $o.giftsWrapper;

                // get all pass ids from wrappers
                $.each(cards, function(i) {
                    ids.push($(cards[i]).data('id'));
                });

                $.get(
                    lpVars.ajaxUrl,
                    {
                        action  : 'laterpay_get_gift_card_actions',
                        pass_id : ids,
                        link    : window.location.href
                    },
                    function(r) {
                        if (r.data) {
                            $.each(r.data, function(i) {
                                var gift    = r.data[i],
                                    $elem   = $($o.giftCardActionsPlaceholder + '_' + gift.id);

                                $elem.empty().append(gift.html);

                                // add 'buy another gift card' after gift card
                                if (gift.buy_more) {
                                    // $elem.parent().after(gift.buy_more);
                                    $(gift.buy_more)
                                        .appendTo($elem.parent())
                                        .attr('href', window.location.href);
                                }
                            });

                            // remove gift code cookie if present
                            delete_cookie('laterpay_purchased_gift_card');
                        }
                    },
                    'json'
                );
            },

            loadPremiumUrls = function() {
                var ids   = [],
                    types = [],
                    boxes = $($o.premiumBox);

                // get all pass ids from wrappers
                $.each(boxes, function(i) {
                    ids.push($(boxes[i]).data('post-id'));
                    types.push($(boxes[i]).data('content-type'));
                });

                $.get(
                    lpVars.ajaxUrl,
                    {
                        action  : 'laterpay_get_premium_shortcode_link',
                        ids     : ids,
                        types   : types,
                        post_id : lpVars.post_id
                    },
                    function(r) {
                        if (r.data) {
                            var url = null;
                            $.each(r.data, function(i) {
                                url = r.data[i];
                                $.each(boxes, function(j) {
                                    if ($(boxes[j]).data('post-id').toString() === i) {
                                        $(boxes[j]).prepend(url);
                                    }
                                });
                            });
                        }
                        initiateAttachmentDownload();
                    },
                    'json'
                );
            },

            loadPreviewModeContainer = function() {
                $.ajax( {
                  url       : lpVars.ajaxUrl,
                  method    : 'GET',
                  data      :{
                    action  : 'laterpay_preview_mode_render',
                    post_id : lpVars.post_id
                  },
                  xhrFields : {
                    withCredentials : true
                  }
                } ).done( function ( data ) {
                  if (data) {
                    $o.previewModePlaceholder.before(data).remove();
                    recachePreviewModeContainer();
                    bindPreviewModeEvents();
                  }
                } );
            },

            togglePreviewMode = function() {
                if ($o.previewModeToggle.prop('checked')) {
                    $o.previewModeInput.val(1);
                } else {
                    $o.previewModeInput.val(0);
                }

                // save the state and reload the page in the new preview mode
                $.ajax( {
                  url       : lpVars.ajaxUrl,
                  method    : 'POST',
                  data      : $o.previewModeForm.serializeArray(),
                  xhrFields : {
                    withCredentials : true
                  }
                } ).done( function () {
                  window.location.reload();
                } );
            },

            togglePreviewModeVisibility = function() {
                var doHide = $o.previewModeContainer.hasClass($o.hidden) ? '0' : '1';
                $o.previewModeVisibilityInput.val(doHide);

                // toggle the visibility
                $o.previewModeContainer.toggleClass($o.hidden);

                // save the state
                $.ajax( {
                  url       : lpVars.ajaxUrl,
                  method    : 'POST',
                  data      : $o.previewModeVisibilityForm.serializeArray(),
                  xhrFields : {
                    withCredentials : true
                  }
                } );
            },

            handlePurchaseInTestMode = function(trigger) {
                if ($(trigger).data('preview-as-visitor') && !$(trigger).data('is-in-visible-test-mode')) {
                    // show alert instead of loading LaterPay purchase dialogs
                    alert(lpVars.i18n.alert);
                }
            },

            initiateAttachmentDownload = function() {
                var url = get_cookie('laterpay_download_attached');
                // start attachment download, if requested
                if ( url ) {
                    delete_cookie('laterpay_download_attached');
                    window.location.href = url;
                }
            },

            flipTimePass = function(trigger) {
                $(trigger).parents('.lp_time-pass').toggleClass('lp_is-flipped');
            },

            delete_cookie = function( name ) {
                document.cookie = name + '=; expires=Thu, 01-Jan-70 00:00:01 GMT; path=/';
            },

            get_cookie = function(name) {
                var matches = document.cookie.match(
                    new RegExp('(?:^|; )' + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'));
                return matches ? decodeURIComponent(matches[1]) : undefined;
            },

            initializePage = function() {

                if ($o.previewModePlaceholder.length === 1) {
                    loadPreviewModeContainer();
                }

                if ($o.giftsWrapper.length >= 1) {
                    loadGiftCards();
                }

                if ($($o.premiumBox).length >= 1) {
                    loadPremiumUrls();
                }

                bindPurchaseEvents();
                bindTimePassesEvents();
            };

        initializePage();
    }

// initialize page
    laterPayPostView();

});})(jQuery);

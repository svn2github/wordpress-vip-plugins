jQuery.fn.showLoadingIndicator = function() {
    var $container = jQuery(this);

    // add a state class, indicating that the element will be showing a loading indicator after a delay
    $container.addClass('lp_is-delayed');

    setTimeout(function() {
        if ($container.hasClass('lp_is-delayed')) {
            // inject the loading indicator after a delay, if the element still has that state class
            $container.removeClass('lp_is-delayed');
            var loadingIndicator = $('<div/>', {
                class: 'lp_js_loadingIndicator lp_loading-indicator',
            });
            $container.empty().append(loadingIndicator);
        }
    }, 600);
};

jQuery.fn.removeLoadingIndicator = function() {
    var $container = jQuery(this);

    if ($container.hasClass('lp_is-delayed')) {
        // remove the state class, thus canceling adding the loading indicator
        $container.removeClass('lp_is-delayed');
    } else {
        // remove the loading indicator
        $container.find('.lp_js_loadingIndicator').remove();
    }
};

jQuery.fn.showMessage = function(message, success) {
    var $container  = jQuery(this);

	try {
		if ( jQuery.type( message ) === 'string' ) {
			message = JSON.parse( message );
		}
		success = message.success;
		message = message.message;
	} catch ( e ) {
        if (typeof message !== 'string') {
            success = message.success;
            message = message.message;
        }
    }

    var $message     = jQuery('<div class="lp_flash-message" style="display:none;"><p></p></div>'),
        messageClass = success ? 'updated' : 'error';
    $container.prepend($message);
    $message.addClass(messageClass).find('p').empty().append(message);
    if (jQuery('p:hidden', $message)) {
        $message.velocity('slideDown', { duration: 250 });
    }
    setTimeout(function() { $message.clearMessage(); }, 3000);
};

jQuery.fn.clearMessage = function() {
    jQuery(this).velocity('slideUp', { duration: 250, complete: function(message) { jQuery(message).remove(); } });
};

jQuery.noConflict();

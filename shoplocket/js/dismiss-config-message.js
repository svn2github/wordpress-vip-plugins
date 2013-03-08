function dismissShopLocketConfigMessage(wp_nonce) {

    jQuery("#message.shoplocket_config_message").hide();
    var data = {
        action: 'shoplocket_dismiss_config_message',
        nonce: wp_nonce
    };
    jQuery.post(ajaxurl, data, function(response) {
        //if (response.code == 200) {
        //
        //} else {
        //
        //}
    }, "json");
}
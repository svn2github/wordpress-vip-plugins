<?php
if($display_template) {
    echo '<div id="'. esc_attr($livefyre_element).'"></div>';
}
?>
<script type="text/javascript">
    var networkConfigChat = {
        <?php echo isset( $strings ) ? 'strings: ' . Livefyre_Apps::json_encode_wrap($strings) . ',' : ''; ?>
        network: <?php echo Livefyre_Apps::json_encode_wrap($network->getName()); ?>
    };
    var convConfigChat<?php echo Livefyre_Apps::json_encode_wrap($articleId); ?> = {
        siteId: <?php echo Livefyre_Apps::json_encode_wrap($siteId); ?>,
        articleId: <?php echo Livefyre_Apps::json_encode_wrap($articleId); ?>,
        el: <?php echo Livefyre_Apps::json_encode_wrap($livefyre_element); ?>,
        collectionMeta: <?php echo Livefyre_Apps::json_encode_wrap($collectionMetaToken); ?>,
        checksum: <?php echo Livefyre_Apps::json_encode_wrap($checksum); ?>
    };
    
    if(typeof(liveChatConfig) !== 'undefined') {
        convConfigChat<?php echo Livefyre_Apps::json_encode_wrap($articleId); ?> = Livefyre.LFAPPS.lfExtend(liveChatConfig, convConfigChat<?php echo Livefyre_Apps::json_encode_wrap($articleId); ?>);
    }

    Livefyre.require([<?php echo LFAPPS_Chat::get_package_reference(); ?>], function(ConvChat) {
        load_livefyre_auth();
        new ConvChat(networkConfigChat, [convConfigChat<?php echo Livefyre_Apps::json_encode_wrap($articleId); ?>], function(chatWidget) {
            if(typeof chatWidget !== "undefined") {
                var livechatListeners = Livefyre.LFAPPS.getAppEventListeners('livechat');
                if(livechatListeners.length > 0) {
                    for(var i=0; i<livechatListeners; i++) {
                        var livechatListener = livechatListeners[i];
                        chatWidget.on(livechatListener.eventName, livechatListener.callback);
                    }
                }
            }
        });
    });
</script>
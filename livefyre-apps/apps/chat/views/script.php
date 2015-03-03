<?php
if($display_template) {
    echo '<div id="'. esc_attr($livefyre_element).'"></div>';
}
?>
<script type="text/javascript">
    var networkConfigChat = {
        network: "<?php echo esc_js($network->getName()); ?>"
    };
    var chatStrings = <?php echo json_encode($strings); ?>;
    if (chatStrings != '') {
        networkConfigChat['strings'] = chatStrings;
    }
    var convConfigChat<?php echo esc_js($articleId); ?> = {
        siteId: "<?php echo esc_js($siteId); ?>",
        articleId: "<?php echo esc_js($articleId); ?>",
        el: "<?php echo esc_js($livefyre_element); ?>",
        collectionMeta: "<?php echo esc_js($collectionMetaToken); ?>",
        checksum: "<?php echo esc_js($checksum); ?>"
    };
    
    if(typeof(liveChatConfig) !== 'undefined') {
        convConfigChat<?php echo esc_js($articleId); ?> = lf_extend(liveChatConfig, convConfigChat<?php echo esc_js($articleId); ?>);
    }

    Livefyre.require(['<?php echo Livefyre_Apps::get_package_reference('fyre.conv'); ?>'], function(ConvChat) {
        load_livefyre_auth();
        new ConvChat(networkConfigChat, [convConfigChat<?php echo esc_js($articleId); ?>], function(chatWidget) {
        }());
    });
</script>
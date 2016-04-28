<div id="<?php echo esc_attr($livefyre_element); ?>"></div>
<script type="text/javascript">
    var networkConfigBlog = {
        <?php echo isset( $strings ) ? 'strings: ' . Livefyre_Apps::json_encode_wrap($strings) . ',' : ''; ?>
        network: <?php echo Livefyre_Apps::json_encode_wrap($network->getName()); ?>   
    };
    var convConfigBlog<?php echo Livefyre_Apps::json_encode_wrap($articleId); ?> = {
        siteId: <?php echo Livefyre_Apps::json_encode_wrap($siteId); ?>,
        articleId: <?php echo Livefyre_Apps::json_encode_wrap($articleId); ?>,
        el: <?php echo Livefyre_Apps::json_encode_wrap($livefyre_element); ?>,
        collectionMeta: <?php echo Livefyre_Apps::json_encode_wrap($collectionMetaToken); ?>,
        checksum: <?php echo Livefyre_Apps::json_encode_wrap($checksum); ?>
    };
    
    if(typeof(liveBlogConfig) !== 'undefined') {
        convConfigBlog<?php echo Livefyre_Apps::json_encode_wrap($articleId); ?> = Livefyre.LFAPPS.lfExtend(liveBlogConfig, convConfigBlog<?php echo Livefyre_Apps::json_encode_wrap($articleId); ?>);
    }

    Livefyre.require([<?php echo LFAPPS_Blog::get_package_reference(); ?>], function(ConvBlog) {
        load_livefyre_auth();
        new ConvBlog(networkConfigBlog, [convConfigBlog<?php echo Livefyre_Apps::json_encode_wrap($articleId); ?>], function(blogWidget) {  
            if(typeof blogWidget !== "undefined") {
                var liveblogListeners = Livefyre.LFAPPS.getAppEventListeners('liveblog');
                if(liveblogListeners.length > 0) {
                    for(var i=0; i<liveblogListeners.length; i++) {
                        var liveblogListener = liveblogListeners[i];
                        blogWidget.on(liveblogListener.event_name, liveblogListener.callback);
                    }
                }
            }
        });
    });
</script>
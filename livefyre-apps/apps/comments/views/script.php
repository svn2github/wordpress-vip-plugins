<?php
if ($display_template) {
    global $wp_query, $post;
    if ($parent_id = wp_is_post_revision($wp_query->post->ID)) {
        $post_id = $parent_id;
    } else {
        $post_id = $post->ID;
    }
    $url = LFAPPS_Comments_Core::$bootstrap_url_v3 . '/' . base64_encode($post_id) . '/bootstrap.html';
    $lfHttp = new LFAPPS_Http_Extension();
    $result = $lfHttp->request($url);
    $cached_html = '';
    if (isset($result['response']['code']) && $result['response']['code'] == 200) {
        $cached_html = isset($result['body']) ? $result['body'] : '';
        $cached_html = preg_replace('(<script>[\w\W]*<\/script>)', '', $cached_html);
    }
    echo '<div id="' . esc_attr($livefyre_element) . '">' . wp_kses_post($cached_html) . '</div>';
}
?>

<script type="text/javascript">
    var networkConfigComments = {
<?php echo isset($strings) ? 'strings: ' . Livefyre_Apps::json_encode_wrap($strings) . ',' : ''; ?>
        network: <?php echo Livefyre_Apps::json_encode_wrap($network->getName()); ?>
    };
    var convConfigComments<?php echo Livefyre_Apps::json_encode_wrap($articleId); ?> = {
        siteId: <?php echo Livefyre_Apps::json_encode_wrap($siteId); ?>,
        articleId: <?php echo Livefyre_Apps::json_encode_wrap($articleId); ?>,
        el: <?php echo Livefyre_Apps::json_encode_wrap($livefyre_element); ?>,
        collectionMeta: <?php echo Livefyre_Apps::json_encode_wrap($collectionMetaToken); ?>,
        checksum: <?php echo Livefyre_Apps::json_encode_wrap($checksum); ?>
    };
    if (typeof (liveCommentsConfig) !== 'undefined') {
        convConfigComments<?php echo Livefyre_Apps::json_encode_wrap($articleId); ?> = Livefyre.LFAPPS.lfExtend(liveCommentsConfig, convConfigComments<?php echo Livefyre_Apps::json_encode_wrap($articleId); ?>);
    }

    Livefyre.require([<?php echo LFAPPS_Comments::get_package_reference(); ?>], function (ConvComments) {
        load_livefyre_auth();
        new ConvComments(networkConfigComments,
                [convConfigComments<?php echo Livefyre_Apps::json_encode_wrap($articleId); ?>],
                function (commentsWidget) {
                    var livecommentsListeners = Livefyre.LFAPPS.getAppEventListeners('livecomments');
                    if (livecommentsListeners.length > 0) {
                        for (var i = 0; i<livecommentsListeners.length; i++) {
                            var livecommentsListener = livecommentsListeners[i];
                            commentsWidget.on(livecommentsListener.eventName, livecommentsListener.callback);
                        }
                    }
                }
        );
    });
</script>
<div class="lf-app-embed" data-lf-app="<?php echo esc_attr($designerAppId); ?>/tagged/published" data-lf-env="<?php echo esc_attr($env); ?>"></div>
<script>
    Livefyre.require([<?php echo LFAPPS_Designer::get_package_reference(); ?>], function (appEmbed) {
        appEmbed.loadAll().done(function(embed) {
            embed = embed[0];
            if(embed.el.onload && embed.getConfig){
                embed.el.onload(embed.getConfig());
            }
        });
    });
</script>
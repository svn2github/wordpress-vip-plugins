<?php
require_once(LFAPPS__PLUGIN_PATH . "/libs/php/LFAPPS_JWT.php");

$network_name = get_option('livefyre_apps-livefyre_domain_name', 'livefyre.com');
$delegate_auth_url = 'http://admin.' . $network_name;
$site_id = get_option('livefyre_apps-livefyre_site_id');
$article_id = get_the_ID();
$site_key = get_option('livefyre_apps-livefyre_site_key');

$collection_meta = array(
    'title'=>  get_the_title(),
    'url'=> get_permalink(get_the_ID()),
    'articleId'=>$article_id,
    'type'=>'sidenotes'
);
$jwtString = LFAPPS_JWT::encode($collection_meta, $site_key);
        
$conv_config = array(
    'siteId'=>$site_id,
    'articleId'=>$article_id,
    'collectionMeta'=>$jwtString,
    'network'=>$network_name,
    'selectors'=>get_option('livefyre_apps-livefyre_sidenotes_selectors'),
);
$strings = apply_filters( 'livefyre_custom_sidenotes_strings', '' );
$conv_config_str = json_encode($conv_config);
?>
<script type="text/javascript">
Livefyre.require(['<?php echo Livefyre_Apps::get_package_reference('sidenotes'); ?>'], function (Sidenotes) {
    load_livefyre_auth();
    var convConfigSidenotes = <?php echo $conv_config_str; ?>;
    convConfigSidenotes['network'] = "<?php echo esc_js($network_name); ?>";
    var sidenotesStrings = <?php echo json_encode($strings); ?>;
    if (sidenotesStrings != '') {
        convConfigSidenotes['strings'] = sidenotesStrings;
    }
    if(typeof(livefyreSidenotesConfig) !== 'undefined') {
        convConfigSidenotes = lf_extend(convConfigSidenotes, livefyreSidenotesConfig);
    }
    new Sidenotes(convConfigSidenotes);
});
</script>

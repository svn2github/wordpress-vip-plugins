jQuery(document).ready(function ($) {

    $("html").delegate("#sitemaps_enable", "click", sitemapCheck);
    sitemapCheck();
    function sitemapCheck() {
        if ($("#sitemaps_enable").is(":checked")) {
            $(".subSitemap").removeAttr("disabled");
        } else {
            $(".subSitemap").attr("disabled", "disabled");
        }
    }
});
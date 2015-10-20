(function (d, s, id) {
    window.siGalleryInit = function () {
        sigallery_embed.init();
    };
    var js, sijs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s);
    js.id = id;
    js.src = "http://beta.usatsimg.com/galleries/js/sigallery_embed.min.js";
    sijs.parentNode.insertBefore(js, sijs);
}(document, 'script', 'sigallery-jssdk'));
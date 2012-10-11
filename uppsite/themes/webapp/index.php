<!DOCTYPE html>
<html>
<head>
    <title><?php bloginfo("name"); ?></title>
    <style type="text/css">
        html, body {
            height: 100%;
        }
        #appLoadingIndicator {
            position: absolute;
            top: 50%;
            left: 50%;
            margin-top: -10px;
            margin-left: -70px;
            width: 155px;
            height: 48px;
            background: url(<?php echo MYSITEAPP_WEBAPP_RESOURCES ?>/uppsite_loading.gif) no-repeat;
        }
    </style>
    <script type="text/javascript">
        var UPPSITE_ROOT_URL = "<?php echo esc_js( get_template_directory_uri() ); ?>/";
        var UPPSITE_BLOG_URL = "<?php echo esc_js( home_url( '/' ) ); ?>";
        var UPPSITE_BLOG_NAME = "<?php echo esc_js( get_bloginfo("name") ); ?>";
        var UPPSITE_ADS = <?php echo mysiteapp_get_ads(); ?>;
        var UPPSITE_PLUGIN_VERSION = "<?php echo esc_js( mysiteapp_get_plugin_version() ); ?>";
        var UPPSITE_ANALYTICS_KEY = "<?php echo esc_js( uppsite_get_analytics_key() ); ?>";
        var UPPSITE_APP_ID = "<?php echo esc_js( uppsite_get_appid() ) ?>";
        var WINDOW_HEIGHT = window.inneHeight;
        var WINDOW_WIDTH = window.innerWidth;
        var PHP_USER_AGENT = "<?php echo array_key_exists("HTTP_USER_AGENT", $_SERVER) ? esc_js( $_SERVER['HTTP_USER_AGENT'] ) : "" ?>";
    </script>
    <script type="text/javascript" id="placeholder"></script>
    <script type="text/javascript" src="<?php echo MYSITEAPP_WEBSERVICES_URL . "/js/webapp_helper.js"?>"></script>
    <script type="text/javascript">(function(n){function r(a){function b(a,j){var c=a.length,b,e;for(b=0;b<c;b++){e=a[b];var d=a,J=b,k=void 0;"string"==typeof e&&(e={path:e});e.shared?(e.version=e.shared,k=e.shared+e.path):(y.href=UPPSITE_ROOT_URL+e.path,k=y.href);e.uri=k;e.key=g+"-"+k;f[k]=e;d[J]=e;e.type=j;e.index=b;e.collection=a;e.ready=!1;e.evaluated=!1}return a}var c;"string"==typeof a?(c=a,a=z(c)):c=JSON.stringify(a);var g=a.id,d=g+"-"+A+o,f={};this.key=d;this.css=b(a.css,"css");this.js=b(a.js,"js");this.assets=this.css.concat(this.js);
this.getAsset=function(a){return f[a]};this.store=function(){s(d,c)}}function v(a,b){i.write('<meta name="'+a+'" content="'+b+'">')}function p(a,b,c){var g=new XMLHttpRequest,d,c=c||B;try{g.open("GET",a,!0),g.onreadystatechange=function(){4==g.readyState&&(d=g.status,200==d||304==d||0==d?b(g.responseText):c())},g.send(null)}catch(f){c()}}function K(a,b){var c=i.createElement("iframe");m.push({iframe:c,callback:b});c.src=a+".html";c.style.cssText="width:0;height:0;border:0;position:absolute;z-index:-999;visibility:hidden";
i.body.appendChild(c)}function C(a,b,c){var g=!!a.shared;if(!g)var d=b,f=a.version,h,b=function(j){h=j.substring(0,f.length+4);h!=="/*"+f+"*/"?confirm("Requested: '"+a.uri+"' with checksum: "+f+" but received: "+h.substring(2,f.length)+"instead. Attemp to refresh the application?")&&L():d(j)};(g?K:p)(a.uri,b,c)}function D(a){var b=a.data,a=a.source.window,c,g,d,f;for(c=0,g=m.length;c<g;c++)if(d=m[c],f=d.iframe,f.contentWindow===a){d.callback(b);i.body.removeChild(f);m.splice(c,1);break}}function E(a){"undefined"!=
typeof console&&(console.error||console.log).call(console,a)}function s(a,b){try{l.setItem(a,b)}catch(c){if(c.code==c.QUOTA_EXCEEDED_ERR){var g=t.assets.map(function(a){return a.key}),d=0,f=l.length,h=!1,j;for(g.push(t.key);d<=f-1;)j=l.key(d),-1==g.indexOf(j)?(l.removeItem(j),h=!0,f--):d++;h&&s(a,b)}}}function u(a){try{return l.getItem(a)}catch(b){return null}}function L(){F||(F=!0,p(o,function(a){(new r(a)).store();n.location.reload()}))}function G(a){function b(a,b){var d=a.collection,e=a.index,
g=d.length,f;a.ready=!0;a.content=b;for(f=e-1;0<=f;f--)if(a=d[f],!a.ready||!a.evaluated)return;for(f=e;f<g;f++)if(a=d[f],a.ready)a.evaluated||c(a);else break}function c(a){a.evaluated=!0;if("js"==a.type)try{eval(a.content)}catch(b){E("Error evaluating "+a.uri+" with message: "+b)}else{var c=i.createElement("style"),e;c.type="text/css";c.textContent=a.content;"id"in a&&(c.id=a.id);"disabled"in a&&(c.disabled=a.disabled);e=document.createElement("base");e.href=a.path.replace(/\/[^\/]*$/,"/");w.appendChild(e);
w.appendChild(c);w.removeChild(e)}delete a.content;0==--f&&g()}function g(){function c(){d&&b()}function b(){var a=q.onUpdated||B;if("onSetup"in q)q.onSetup(a);else a()}function f(){l.store();e.forEach(function(a){s(a.key,a.content)});b()}var e=[],d=!1,g=!1,k=function(){g=!0},i=function(){h.swapCache();d=!0;k()},m;n.removeEventListener("message",D,!1);h.status==h.UPDATEREADY?i():h.status==h.CHECKING||h.status==h.DOWNLOADING?(h.onupdateready=i,h.onnoupdate=h.onobsolete=k):k();!1!==navigator.onLine&&
p(o,function(b){t=l=new r(b);var d;l.assets.forEach(function(b){d=a.getAsset(b.uri);(!d||b.version!==d.version)&&e.push(b)});m=e.length;0==m?g?c():k=c:e.forEach(function(b){function c(){C(b,function(a){b.content=a;0==--m&&(g?f():k=f)})}var d=a.getAsset(b.uri),e=b.path,h=b.update;!d||!h||null===u(b.key)||"delta"!=h?c():p("deltas/"+e+"/"+d.version+".json",function(a){try{var c=b,d;var e=u(b.key),h=z(a),a=[],j,i,l;if(0===h.length)d=e;else{for(i=0,l=h.length;i<l;i++)j=h[i],"number"===typeof j?a.push(e.substring(j,
j+h[++i])):a.push(j);d=a.join("")}c.content=d;0==--m&&(g?f():k=f)}catch(n){E("Malformed delta content received for "+b.uri)}},c)})})}var d=a.assets,f=d.length,l;t=a;H("message",D,!1);0==f?g():d.forEach(function(a){var c=u(a.key);null===c?C(a,function(c){s(a.key,c);b(a,c)},function(){b(a,"")}):b(a,c)})}function I(a){null!==i.readyState.match(/interactive|complete|loaded/)?G(a):H("DOMContentLoaded",function(){G(a)},!1)}var B=function(){},m=[],i=n.document,w=i.head,H=n.addEventListener,l=n.localStorage,
h=n.applicationCache,z=JSON.parse,y=i.createElement("a"),x=i.location,A=x.origin+x.pathname+x.search,o=UPPSITE_ROOT_URL+"app.json",F=!1,t;if("undefined"===typeof q)var q=n.Ext={};q.blink=function(a){var b=u(a.id+"-"+A+o);v("viewport","width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no");v("apple-mobile-web-app-capable","yes");v("apple-touch-fullscreen","yes");b?(a=new r(b),I(a)):p(o,function(b){a=new r(b);a.store();I(a)})}})(this);
;Ext.blink({"id":"3f10e2b0-76cd-11e1-92ac-cb5b28a58a56"})</script>
</head>
<body class="direction-<?php echo uppsite_get_pref_direction(); ?>">
<div id="appLoadingIndicator"></div>
</body>
</html>

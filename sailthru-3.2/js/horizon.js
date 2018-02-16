
if (tag.concierge.enabled == true) {

    (function() {
        console.log(tag.concierge);

        function loadHorizon() {
            var s = document.createElement('script');
            s.type = 'text/javascript';
            s.async = true;
            s.src = location.protocol + '//ak.sail-horizon.com/horizon/v1.js';
            var x = document.getElementsByTagName('script')[0];
            x.parentNode.insertBefore(s, x);
        }
        loadHorizon();
        var oldOnLoad = window.onload;
        window.onload = function() {
            if (typeof oldOnLoad === 'function') {
                oldOnLoad();
            }
            Sailthru.setup({
                domain: tag.options.horizon_domain,
                concierge: {
                    from: tag.concierge.from,
                    threshold: tag.concierge.threshold,
                    delay: tag.concierge.delay,
                    offsetBottom: tag.concierge.offsetBottom,
                    cssPath: tag.concierge.cssPath,
                    filter: tag.concierge.filter
                }
            });
        };
    })();

} else {
    (function() {
        function loadHorizon() {
            var s = document.createElement('script');
            s.type = 'text/javascript';
            s.async = true;
            s.src = location.protocol + '//ak.sail-horizon.com/horizon/v1.js';
            var x = document.getElementsByTagName('script')[0];
            x.parentNode.insertBefore(s, x);
        }
        loadHorizon();
        var oldOnLoad = window.onload;
        window.onload = function() {
            if (typeof oldOnLoad === 'function') {
                oldOnLoad();
            }
            Sailthru.setup({
                domain: tag.options.horizon_domain,
            });
        };
    })();
}
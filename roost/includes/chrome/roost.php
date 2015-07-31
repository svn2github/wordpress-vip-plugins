<html>
<head>
    <link rel="manifest" href="<?php echo esc_attr( $site_base_path ); ?>?roost=true&roost_action=manifest">
    <script>
        //To customize filename, change the above and below.
        var swFilename = "?roost=true&roost_action=worker";
        var init = function() {
            var hasPush = !!window.PushManager || !!navigator.push;
            var hasNotification = !!window.Notification;
            var hasServiceWorker = !!navigator.serviceWorker;
            var supportsPush = hasPush && hasNotification && hasServiceWorker;
            var logging = true;

            function loadFrame(url) {
                var i = document.createElement('iframe');
                i.style.display = 'none';
                i.src = url;
                document.body.appendChild(i);
            }

            function parseQueryString() {
                var qd = {};
                location.search.substr(1).split("&").forEach(function (item) {
                    var parts = item.split("=");
                    var k = parts[0];
                    var v = decodeURIComponent(parts[1]);
                    (k in qd) ? qd[k].push(v) : qd[k] = [v, ];
                });
                return qd;
            }

            var query = parseQueryString();
            if (logging) console.log(query);

            if (query.noteID && query.noteID.length == 1) {
                if (query.sendID && query.sendID.length == 1) {
                    window.location.replace("https://go.goroost.com/note/" + encodeURIComponent(query.noteID[0]) + "/" + encodeURIComponent(query.sendID[0]));
                    return;
                }
            }

            if (supportsPush) {
                var alreadyAnswered = Notification.permission != "default";
                var tags = query.tags;
                var host = "https://go.goroost.com";
                var alias = query.alias && query.alias.length > 0 ? query.alias[0] : null;
                var appKey = query.appKey && query.appKey.length > 0 ? query.appKey[0] : null;
                var firstTime = query.firstTime && query.firstTime.length > 0 ? query.firstTime[0] === "true" : !alreadyAnswered;
                var params = "&appKey=" + encodeURIComponent(appKey) + "&firstTime=" + encodeURIComponent(firstTime);

                if (alias) {
                    params += "&alias=" + encodeURIComponent(alias);
                }
                if (tags) {
                    for (var i = 0; i < tags.length; i++) {
                        var tag = tags[i];
                        params += "&tag=" + encodeURIComponent(tag);
                    }
                }

                if (!alreadyAnswered) {
                    //log the prompt
                    loadFrame(host + "/api/frame/register?prompt=true&appKey=" + encodeURIComponent(appKey));
                }

                //The domain that this call happens on is the one shown in the permission dialog.
                Notification.requestPermission(function (permission) {
                    if (logging) console.log('Notification.requestPermission result.', permission);
                    if (permission === "granted") {
                        var serviceworkerURL = <?php echo wp_json_encode( $site_base_path ); ?> + swFilename;
                        var scope = <?php echo wp_json_encode( $site_base_path ); ?>;

                        navigator.serviceWorker.register(serviceworkerURL, {scope: scope}).then(function (swr) {
                            if (logging) console.log("SW ready", swr);
                            //We don't want to fire two events in the case that the service worker does install early.
                            if (alreadyAnswered) {
                                swr.pushManager.subscribe({userVisible: true, userVisibleOnly: true}).then(
                                    function (pushRegistration) {
                                        if (logging) console.log("registered", pushRegistration);
                                        var regID = null;
                                        if ('subscriptionId' in pushRegistration) {
                                            regID = pushRegistration.subscriptionId;
                                        } else {
                                            //in Chrome 44+ and other SW browsers, reg ID is part of endpoint, send the whole thing and let the server figure it out.
                                            regID = pushRegistration.endpoint;
                                        }
                                        //this stays the remote host, even if "custom"
                                        loadFrame(host + "/api/frame/register?deviceToken=" + encodeURIComponent(regID) + params);
                                    }, function (error) {
                                        console.log(error);
                                    }
                                );
                            }
                        }, function onReject(err) {
                            console.log("Roost Error: Could not register service worker.  Validate correct file installed: " + serviceworkerURL, err);
                        });

                        if (!alreadyAnswered) {
                            //This is the first time, let's reload to get the worker installed
                            if (logging) console.log("Reloading internal frame to cement SW install.");
                            setTimeout(function() {
                                window.location.href = window.location.href + "&firstTime=" + encodeURIComponent(firstTime);
                            }, 200);
                        }
                    }
                });
            }
        };
        if (document.readyState === 'interactive' || document.readyState === "complete") {
            init();
        } else {
            if (document.addEventListener) {
                window.addEventListener('load', init);
            } else {
                document.attachEvent('onload', init);
            }
        }
    </script>
</head>
<body>
</body>
</html>

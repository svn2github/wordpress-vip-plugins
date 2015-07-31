"use strict";

var _roostSW = {
    version: 2,
    logging: true,
    appKey: <?php echo wp_json_encode( $app_key ); ?>,
    host: "https://go.goroost.com"
};

self.addEventListener('install', function(evt) {
    //Automatically take over the previous worker.
    evt.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function(evt) {
    if (_roostSW.logging) console.log("Activated Roost ServiceWorker version: " + _roostSW.version);
});

//Handle the push received event.
self.addEventListener('push', function(evt) {
    if (_roostSW.logging) console.log("push listener", evt);
    evt.waitUntil(self.registration.pushManager.getSubscription().then(function(subscription) {
        var regID = null;
        if ('subscriptionId' in subscription) {
            regID = subscription.subscriptionId;
        } else {
            //in Chrome 44+ and other SW browsers, reg ID is part of endpoint, send the whole thing and let the server figure it out.
            regID = subscription.endpoint;
        }
        return fetch(_roostSW.host + "/api/browser/notifications?version=" + _roostSW.version + "&appKey=" + _roostSW.appKey + "&deviceID=" + encodeURIComponent(regID)).then(function(response) {
            return response.json().then(function(json) {
                if (_roostSW.logging) console.log(json);
                var promises = [];
                for (var i = 0; i < json.notifications.length; i++) {
                    var note = json.notifications[i];
                    if (_roostSW.logging) console.log("Showing notification: " + encodeURI(note.body));
                    var url = <?php echo wp_json_encode( $site_base_path ); ?> + "?roost=true&roost_action=load&noteID=" + encodeURIComponent(note.roost_note_id) + "&sendID=" + encodeURIComponent(note.roost_send_id) + "&body=" + encodeURIComponent(note.body);
                    promises.push(showNotification(note.roost_note_id, note.title, note.body, url, _roostSW.appKey));
                }
                return Promise.all(promises);
            });
        });
    }));
});

self.addEventListener('notificationclick', function(evt) {
    if (_roostSW.logging) console.log("notificationclick listener", evt);
    evt.waitUntil(handleNotificationClick(evt));
});

function parseQueryString(queryString) {
    var qd = {};
    queryString.split("&").forEach(function (item) {
        var parts = item.split("=");
        var k = parts[0];
        var v = decodeURIComponent(parts[1]);
        (k in qd) ? qd[k].push(v) : qd[k] = [v, ]
    });
    return qd;
}

//Utility function to handle the click
function handleNotificationClick(evt) {
    if (_roostSW.logging) console.log("Notification clicked: ", evt.notification);
    evt.notification.close();
    var iconURL = evt.notification.icon;
    if (iconURL.indexOf("?") > -1) {
        var queryString = iconURL.split("?")[1];
        var query = parseQueryString(queryString);
        if (query.url && query.url.length == 1) {
            if (_roostSW.logging) console.log("Opening URL: " + query.url[0]);
            return clients.openWindow(query.url[0]);
        }
    }
    console.log("Failed to redirect to notification for iconURL: " + iconURL);
}

//Utility function to actually show the notification.
function showNotification(noteID, title, body, url, appKey) {
    var options = {
        body: body,
        tag: "roost",
        icon: _roostSW.host + '/api/browser/logo?size=100&direct=true&appKey=' + _roostSW.appKey + '&noteID='+ encodeURIComponent(noteID) + '&url=' + encodeURIComponent(url)
    };
    return self.registration.showNotification(title, options);
}

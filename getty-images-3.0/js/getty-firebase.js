(function($) {
    var getty = gettyImages;

    getty.firebase = {};

    // Initialize Firebase
  // TODO: Replace with your project's customized code snippet

  var firebaseConfig = {
        apiKey: "AIzaSyABB_5vW1NXebp7nxBGuKOy22Gb2H4MJvw",
        authDomain: "getty-images-plugin.firebaseapp.com",
        databaseURL: "https://getty-images-plugin.firebaseio.com/",
        storageBucket: "getty-images-plugin.appspot.com",
        messagingSenderId: "382695403044"
    };

  firebase.initializeApp(firebaseConfig);

  var database = firebase.database();

  getty.firebase.content = {};

  getty.firebase.get = function (key) {
        var dayInMs = 1000 * 60 * 60 * 24;
        var contentFresh = this.content[key] && new Date() - this.content[key].lastFetched < dayInMs;
        if (contentFresh) return Promise.resolve(this.content[key].data);

        return database.ref(key).once('value').then(function(response) {
            var results = response.val();
            return results;
        });
    };

})(jQuery);

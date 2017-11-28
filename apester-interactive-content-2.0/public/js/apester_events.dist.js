jQuery(function($) {
  var PROTOCOL = 'https:';

  var existingTokensSent = window.existing_tokens_sent || false;
  var wp_option_apester_tokens = window.apester_tokens || [];

  var eventDeferreds = [],
    channelsSetTpWpDeferreds = [];

  var versionsFlags = {
    tokenExists: '2.0.2',
    pluginPublishOption: '2.0.4'
  };

  /**
   * validate token on the client
   * @param token
   * @returns {boolean} isValid
   */
  var isValidToken = function(token) {
    return /^[0-9a-fA-F]{24}$/.test(token);
  };

  var convertVersionToNumber = function(versionString) {
    return +(versionString.split(".").join(""));
  };

  var sendEvent = function(eventName, token) {
    var baseUrl = PROTOCOL + '//events.apester.com/event';
    var payload = {
      event: eventName,
      properties: {
        pluginProvider: 'WordPress',
        pluginVersion: window.apester_plugin_version,
        channelToken: token,
        phpVersion: window.php_version,
        wpVersion: window.wp_version
      },
      metadata: {
        referrer: encodeURIComponent(document.referrer),
        current_url: encodeURIComponent(window.location.href),
        screen_height: window.screen.height.toString(),
        screen_width: window.screen.width.toString(),
        language: window.navigator.userLanguage || window.navigator.language
      }};
    $.ajax({
      type: 'POST',
      url: baseUrl,
      contentType: 'application/json; charset=UTF-8',
      data: JSON.stringify(payload),
      async: false
    });
  };

  var updateSingleTokenToWP = function(channelToken) {
    var baseUrl = PROTOCOL + '//users.apester.com/publisher/token/',
      urlSuffix = '/publish-settings';

    $.ajax({
      type: 'PATCH',
      url: baseUrl + channelToken + urlSuffix,
      contentType: 'application/json; charset=UTF-8',
      data: JSON.stringify({ 'type': 'wordpress' }),
      credentials: true,
      async: false
    });
  };

  var updatePublishOption = function() {
    for (var i=0; i < wp_option_apester_tokens.length; i++) {
      if (isValidToken(wp_option_apester_tokens[i])) {
        channelsSetTpWpDeferreds.push(updateSingleTokenToWP(wp_option_apester_tokens[i]));
      }
    }
  };

  var applyPublishOptionFlag = function() {
    var data = {
      action: 'apester_tokens_publish_option',
      isPublishOptionUpdated: true
    };

    $.post(ape_ajax_object.ajaxUrl, data);
  };

  var sendExistingTokens = function() {
    var eventName = 'wordpress_plugin_token_exists';

    for (var i=0; i < wp_option_apester_tokens.length; i++) {
      eventDeferreds.push( sendEvent(eventName, wp_option_apester_tokens[i]) );
    }
    existingTokensSent = true;
  };

  var applySavedTokensFlag = function() {
    var data = {
      action: 'apester_events',
      apesterTokenSent: true
    };

    $.post(ape_ajax_object.ajaxUrl, data);
  };

  var applyEventsLogic = function(currentVersion) {
    if (!ape_ajax_object.isApesterTokensSent && currentVersion >= convertVersionToNumber(versionsFlags.tokenExists)) {
      sendExistingTokens();

      // similar to q.all()
      $.when.apply($, eventDeferreds).then(function(){
        applySavedTokensFlag();
      });
    }
  };

  var applyPluginPublishOptionLogic = function(currentVersion) {
    // if version is  >= 2.0.4 & !isApesterPluginUpdatedForChannels
    if (!ape_ajax_object.isTokensPublishOptionUpdated && currentVersion >= convertVersionToNumber(versionsFlags.pluginPublishOption)) {
      updatePublishOption();

      // similar to q.all()
      $.when.apply($, channelsSetTpWpDeferreds).then(function(){
        applyPublishOptionFlag();
      });
    }
  };

  var init = function() {
    var currentVersion = convertVersionToNumber(window.apester_plugin_version);

    applyEventsLogic(currentVersion);
    applyPluginPublishOptionLogic(currentVersion);
  };

  init();
});

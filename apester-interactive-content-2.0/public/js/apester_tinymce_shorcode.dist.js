(function($) {
  tinymce.PluginManager.add( 'apester_shortcode_handler', function( editor, url ) {

    var PROTOCOL = 'https:';

    var CONST = {
      displayUrlPrefix: PROTOCOL + '//display.apester.com/interactions/',
      displayUrlSufffix: '/display',
      imagesPath:'public/img/'
    };

    // get the plugin path from outside the tinyMCE iframe
    var apesterPluginPath = window.parent.apester_plugin_path;

    /**
     * get an attribute's value from a string of html (e.g: '<div prop="value"></div>')
     * @param attr
     * @param stringToIterate
     * @returns string - the value of the attribute passed
     */
    var getAttrValueInString = function(attr, stringToIterate) {
      var result;
      var propertyRegex = new RegExp(attr + '=\"(.*?)\"','g');
      var found = propertyRegex.exec(stringToIterate);
      if ( found ) {
        result = found[1];
      }
      return result;
    };

    /**
     * returns the properties object for event sending
     * @param node - the html element that has the id/token property on it
     * @returns {{}} - object that meets the event.properties form
     */
    var getEventPropsFromElement = function(node) {
      var $content = $(node).find('.apester-media-wrapper');
      var id = $content.attr('id');
      var idComponents = id.split('-');
      var mediaType = idComponents[1];
      var mediaId = idComponents[2];

      var properties = {};
      // in case of specific interaction we use interactionId
      if (mediaType === 'media') {
        properties.interactionId = mediaId;
      }
      // in case of random playlist interaction we use channelToken
      else {
        properties.channelToken = mediaId;
      }
      return properties;
    };

    var sendEvent = function(eventDataObject) {
      var baseUrl = PROTOCOL + '//events.apester.com/event';
      var payload = {
        event: '',
        properties: {
          pluginProvider: 'WordPress',
          pluginVersion: window.apester_plugin_version,
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

      var eventData = $.extend(true, {}, payload, eventDataObject);

      $.ajax({
        type: 'POST',
        url: baseUrl,
        contentType: 'application/json; charset=UTF-8',
        data: JSON.stringify(eventData),
        async: false
      });
    };

    // replace apester shortcode with a media preview
    editor.on( 'BeforeSetContent', function( event ) {
      event.content = event.content.replace( /\[interaction([^\]]*)\]/g, function( all, attr, con ) {

        var idAttr = 'id="',
          idAttrIndex = attr.indexOf(idAttr);
        var mediaId = attr.slice(idAttrIndex + idAttr.length, -1);

        // get interactions dimensions
        $.ajax({
          url      : CONST.displayUrlPrefix + mediaId + CONST.displayUrlSufffix,
          type     : "get",
          dataType : "json",
          success  : function( data ) {
            var mediaDimensions = data.payload.data.size;

            // create a style tag inside the tinyMCE editor iframe so when user deletes a media and un-does the deletion, the media dimensions will be correct again
            var css = '#apester-media-' + mediaId + ' iframe {'
              +'height:' + mediaDimensions.height +'px;'
              +'max-width: ' + mediaDimensions.width + 'px;'
              + 'border: none;'
              +'}';

            var styleTag = document.createElement('style');
            styleTag.type = 'text/css';
            styleTag.id = 'apester-media-' + mediaId + '-css';

            var editorDocument = tinyMCE.activeEditor.dom.doc;

            if (styleTag.styleSheet) {
              styleTag.styleSheet.cssText = css;
            } else {
              styleTag.appendChild(document.createTextNode(css));
            }
            editorDocument.head.appendChild(styleTag);
          }

        });

        // create the shortcode-replacement HTML elements
        var wrapper, mediaWrapper, iframe, mediaOverlay, closeBtn;
        wrapper = $("<div>").addClass("apester-shortcode-replacement").attr("contenteditable", false);
        mediaWrapper = $("<div>").addClass("apester-media-wrapper")
          .attr({
            "id": "apester-media-" + mediaId,
            "data-mce-resize": "false",
            "data-mce-placeholder": "1"
          });
        iframe = $("<iframe>").addClass("apester-shortcode-iframe")
          .attr({
            "contenteditable": false,
            "id": "apester-media-" + mediaId,
            "data-apester-media-id": mediaId,
            "src": PROTOCOL + "//renderer.apester.com/interaction/"+ mediaId,
            "data-mce-placeholder": "1"
          });
        mediaOverlay = $("<div>").addClass("apester-media-overlay");
        closeBtn = $('<button class="ic icon-cross apester-shorcode-remove-btn">');

        // combine into final element
        mediaWrapper.append(iframe);
        wrapper.append(mediaWrapper, mediaOverlay, closeBtn);

        // this will come in place of the shortcode
        return wrapper.prop("outerHTML");

      });

      // random playlist shorcode handler
      event.content = event.content.replace( /\[apester-playlist([^\]]*)\]/g, function( all, attr, con ) {

        var idAttr = 'channelToken="',
          idAttrIndex = attr.indexOf(idAttr);
        var channelToken = attr.slice(idAttrIndex + idAttr.length, -1);
        var apesterIcon = apesterPluginPath + CONST.imagesPath + 'ape-icon.svg';

        // create the shortcode-replacement HTML elements
        var wrapper, mediaWrapper, contentWrapper, img, randomInnerText, closeBtn;
        wrapper = $("<div>").addClass("apester-shortcode-replacement-playlist").attr("contenteditable", false);
        mediaWrapper = $("<div>").addClass("apester-media-wrapper")
          .attr({
            "id": "apester-playlist-" + channelToken,
            "data-apester-playlist": channelToken,
            "data-mce-resize": "false",
            "data-mce-placeholder": "1"
          });
        contentWrapper = $("<div>").addClass("apester-content-wrapper")
          .attr({
            "data-mce-resize": "false",
            "data-mce-placeholder": "1"
          });
        img = $("<img>").addClass("apester-shortcode-iframe")
          .attr({
            "contenteditable": false,
            "src": apesterIcon,
            "id": "apester-playlist-" + channelToken,
            "data-mce-placeholder": "1"
          });
        randomInnerText = $("<div>Random Unit</div>");
        closeBtn = $('<button class="ic icon-cross apester-shorcode-remove-btn">');

        // combine into final element
        contentWrapper.append(img, randomInnerText);
        mediaWrapper.append(contentWrapper);
        wrapper.append(mediaWrapper, closeBtn);

        // this will come in place of the shortcode
        return wrapper.prop("outerHTML");

      });


      // playlist exclude handler
      event.content = event.content.replace( /\[apester-exclude-playlist([^\]]*)\]/g, function( all, attr, con ) {

        var idAttr = 'channelToken="',
          idAttrIndex = attr.indexOf(idAttr);
        var channelToken = attr.slice(idAttrIndex + idAttr.length, -1);
        var apesterIcon = apesterPluginPath + CONST.imagesPath + 'ape-icon.svg';

        // create the shortcode-replacement HTML elements
        var wrapper, mediaWrapper, contentWrapper, img, randomInnerText, closeBtn;
        wrapper = $("<div>").addClass("apester-shortcode-replacement-exclude-playlist").attr("contenteditable", false);
        mediaWrapper = $("<div>").addClass("apester-media-wrapper")
          .attr({
            "data-apester-exclude-playlist": true,
            "data-mce-resize": "false",
            "data-mce-placeholder": "1"
          });
        contentWrapper = $("<div>").addClass("apester-content-wrapper")
          .attr({
            "data-mce-resize": "false",
            "data-mce-placeholder": "1"
          });
        img = $("<img>").addClass("apester-shortcode-iframe")
          .attr({
            "contenteditable": false,
            "src": apesterIcon,
            "data-mce-placeholder": "1"
          });
        randomInnerText = $("<div>Your Apester playlist has been excluded from this article. </div>");
        closeBtn = $('<button class="apester-shorcode-remove-btn text-button">Undo</button>');

        // combine into final element
        randomInnerText.append(closeBtn);
        contentWrapper.append(img, randomInnerText);
        mediaWrapper.append(contentWrapper);
        wrapper.append(mediaWrapper);

        // this will come in place of the shortcode
        return wrapper.prop("outerHTML");

      });
    });

    editor.on('click', function(e) {
      // if the user clicked on the remove button - remove the apester media from the editor
      if ( (e.target.nodeName == 'BUTTON') && (e.target.className.indexOf('apester-shorcode-remove-btn') > -1) ) {
        var node = tinyMCE.activeEditor.selection.getNode();
        if (node.className.indexOf('apester-shortcode-replacement') > -1) {
          //make sure we keep a <p> element after we remove the embed so the ser can
          //write something else, instead of having an empty editor
          $(node).after('<p><br/></p>');

          // TODO: figure out what to send for removing exclude if at all
          if (node.className.indexOf('exclude') === -1) {
            var props = getEventPropsFromElement(node);
            sendEvent($.extend(true, {}, { event: 'wordpress_remove_interaction_clicked', properties: props }));
          }

          $(node).remove();
        }
        return;
      }
    });

    // replace the media preview with apester shortcode
    editor.on('GetContent', function( event ) {

      event.content = event.content.replace( /((<div class="apester-shortcode-replacement"[^<>]*>)(.*?)(?:<\/button><\/div>))*/g, function( match, contents ) {
        var mediaId = getAttrValueInString('data-apester-media-id', match);

        // insert the apester shortcode in place of the html element media preview
        if ( mediaId ) {
          return '<p>[interaction id="' + mediaId + '"]</p>';
        }

        return match;
      });

      // random playlist shorcode handler
      event.content = event.content.replace( /((<div class="apester-shortcode-replacement-playlist"[^<>]*>)(.*?)(?:<\/button><\/div>))*/g, function( match, contents ) {
        var mediaId = getAttrValueInString('data-apester-playlist', match);

        // insert the apester shortcode in place of the html element media preview
        if ( mediaId ) {
          return '<p>[apester-playlist channelToken="' + mediaId + '"]</p>';
        }

        return match;
      });

      // playlist exclude handler
      event.content = event.content.replace( /((<div class="apester-shortcode-replacement-exclude-playlist"[^<>]*>)(.*?)(?:<\/div><\/div><\/div><\/div>))*/g, function( match, contents ) {
        var isExclude = getAttrValueInString('data-apester-exclude-playlist', match);

        if ( isExclude ) {
          return '[apester-exclude-playlist]';
        }
        return match;
      });
    });
  });

})(jQuery);

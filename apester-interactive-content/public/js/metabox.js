(function(window, document) {
    'use strict';

    var map = {
            addInteraction: addInteraction,
            editInteraction: editInteraction,
            chooseInteraction: chooseInteraction,
            interactionCreated: closeAndEmbed
        },
        baseUrl = configuration.editorBaseUrl;

    function embedInteraction(id) {
        var content = tinymce.editors[0].getContent(),
            regex = /(\[interaction.*?\])/;

        if (content.indexOf('[interaction') !== -1) {
            content = content.replace(regex, '[interaction id="'+ id +'"]');
            tinymce.editors[0].setContent(content);
            return;
        }

        if (parseInt(tinyMCE.majorVersion) == 3) {
            tinymce.activeEditor.execCommand('mceInsertContent',false,'[interaction id="'+ id +'"]');
            return;
        }

        tinymce.editors[0].insertContent('[interaction id="'+ id +'"]');
    }

    function getSuggestionsFrame() {
        return document.getElementById('qmerce_meta_box_suggestions');
    }

    function notify() {
        var contentWindow = getSuggestionsFrame().contentWindow;

        if (!contentWindow) {
            return;
        }

        contentWindow.postMessage({ action: 'interactionCreated' }, '*');
    }

    function closeAndEmbed(id) {
        modal.close();
        embedInteraction(id);
        notify();
    }

    function chooseInteraction(id) {
        embedInteraction(id);
    }

    function editInteraction(id) {
        modal.open(baseUrl + '/#/editor/' + encodeURIComponent( id ) + '?access_token=' + encodeURIComponent( window.authToken ) );
    }

    function addInteraction() {
        modal.open(baseUrl + '/#/editor/new?access_token=' + encodeURIComponent( window.authToken ) );
    }

    function messageHandler(event) {
        var data = event.data,
            params = data.params || null;

        if (!data.action || !map[data.action]) {
            return;
        }

        map[data.action](data.id);
    }

    window.addEventListener('message', messageHandler, false);

}(window, window.document));

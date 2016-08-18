/**
 * Modal JavaScript SDK
 */
window.modal = (function (window, document, undefined) {
    'use strict';

    /**
     * Loads given CSS url to the DOM
     * @param {string} url
     */
    function loadCss(url) {
        var firstStyle = document.getElementsByTagName('link')[0],
            modalCss = document.createElement('link');

        modalCss.type = 'text/css';
        modalCss.rel = 'stylesheet';
        modalCss.media = 'all';
        modalCss.href = url;

        firstStyle.parentNode.insertBefore(modalCss, firstStyle);
    }

    /**
     * Returns the overlay object
     * @returns {null|document.element}
     */
    function getOverlay() {
        return document.getElementById('modal-overlay');
    }

    /**
     * Returns the modal object
     * @returns {null|document.element}
     */
    function getModal() {
        return document.getElementById('modal-popup');
    }


    function closeModal() {
        var modal = getModal(),
            overlay = getOverlay();

        document.body.removeChild(modal);
        document.body.removeChild(overlay);
    }

    /**
     * Adds overlay to the page
     */
    function addOverlay() {
        var overlay = document.createElement('div');

        overlay.id = 'modal-overlay';

        document.body.appendChild(overlay);
    }

    /**
     * Returns brand new iframe object
     * @param {string} src
     * @param {string} id
     * @returns {document.element}
     */
    function getIframe(id, src) {
        var iframe = document.createElement('iframe');

        iframe.src = src;
        iframe.id = id;
        iframe.scrolling = 'no';

        return iframe;
    }

    /**
     * Returns close button DOM object
     * @returns {document.element}
     */
    function getCloseButton() {
        var close = document.createElement('a');

        close.id = 'modal-close';
        close.onclick = function () {
            closeModal();
            return false;
        };

        return close;
    }

    /**
     * Opens a modal with given iframe URL
     */
    function modal(url) {
        addOverlay();

        var popup = document.createElement('div'),
            iframe = getIframe('modal-frame', url);

        popup.id = 'modal-popup';
        document.body.appendChild(popup);
        popup.appendChild(getCloseButton());

        popup.appendChild(iframe);
    }


    function messageHandler(event) {
        var data,
            params;

        data = event.data;
        params = data.params || null;

        if (data.command === 'closeModal') {
            closeModal();
        }
    }

    window.addEventListener('message', messageHandler, false);

    /**
     * Close popup on esc key press.
     * @param {event} event
     */
    document.onkeydown = function (event) {
        if (event.keyCode !== 27) {
            return;
        }

        closeModal();
    };

    /**
     * Method for dispatching events and triggering the first ready event.
     */
    (function dispatchEvent(event, data) {
        var evt = document.createEvent('CustomEvent');
        evt.initCustomEvent(event, true, false, data);

        window.dispatchEvent(evt);
    }('modal:ready', {}));

    return {

        /**
         * Opens the Modal popup
         */
        open: function (url) {
            modal(url);
        },

        /**
         * Closes the Modal popup
         */
        close: function () {
            closeModal();
        }
    };

}(window, window.document));

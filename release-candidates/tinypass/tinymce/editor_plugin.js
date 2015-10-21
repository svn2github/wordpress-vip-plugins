// Docu : http://wiki.moxiecode.com/index.php/TinyMCE:Create_plugin/3.x#Creating_your_own_plugins

(function () {

    tinymce.create('tinymce.plugins.tinypass', {
        /**
         * Initializes the plugin, this will be executed after the plugin has been created.
         * This call is done before the editor instance has finished it's initialization so use the onInit event
         * of the editor instance to intercept that event.
         *
         * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
         * @param {string} url Absolute URL to where the plugin is located.
         */
        init: function (ed, url) {

            var breakElem = document.createElement('img');
            breakElem.setAttribute('src', url + '/trans.gif');
            breakElem.style.border = '0px';
            breakElem.style.borderTop = '1px dotted #cccccc';
            breakElem.style.display = 'block';
            breakElem.style.width = '100%';
            breakElem.style.height = '12px';
            breakElem.style.marginTop = '15px';
            breakElem.style.background = '#ffffff url(' + url + '/more_bug.gif) no-repeat right top';
            breakElem.setAttribute('class', 'mceTPmore mceItemNoResize');
            breakElem.setAttribute('title', ed.getLang("wordpress.wp_more_alt"));

            // Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');
            ed.addCommand('mceTinypassBreak', function () {
                ed.execCommand("mceInsertContent", 0, breakElem.outerHTML);
            });

            // Register example button
            ed.addButton('tinypass', {
                tooltip: 'Insert Tinypass More tag',
                onclick: function () {
                    ed.execCommand('mceTinypassBreak');
                }
            });


            ed.onBeforeSetContent.add(function (f, g) {
                if (g.content) {
                    g.content = g.content.replace(/<!--tpmore(.*?)-->/g, breakElem);
                }
            });

            ed.onPostProcess.add(function (ed, obj) {
                if (obj.get) {
                    obj.content = obj.content.replace(/<img[^>]+>/g, function (i) {
                        if (i.indexOf('class="mceTPmore') !== -1) {
                            var h, j = (h = i.match(/alt="(.*?)"/)) ? h[1] : "";
                            i = "<!--tpmore " + j + "-->"
                        }
                        return i
                    })
                }
            });

        },


        /**
         * Creates control instances based in the incomming name. This method is normally not
         * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
         * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
         * method can be used to create those.
         *
         * @param {String} n Name of the control to create.
         * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
         * @return {tinymce.ui.Control} New control instance or null if no control was created.
         */
        createControl: function (n, cm) {
            return null;
        },

        /**
         * Returns information about the plugin as a name/value array.
         * The current keys are longname, author, authorurl, infourl and version.
         *
         * @return {Object} Name/value array containing information about the plugin.
         */
        getInfo: function () {
            return {
                longname: 'tinypass',
                author: 'tinypass',
                authorurl: 'http://www.tinypass.com',
                infourl: 'http://www.tinypass.com',
                version: "2.0"
            };
        }
    });

    // Register plugin
    tinymce.PluginManager.add('tinypass', tinymce.plugins.tinypass);
})();

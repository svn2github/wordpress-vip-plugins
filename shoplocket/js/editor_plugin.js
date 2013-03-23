
(function() {
    tinymce.create('tinymce.plugins.slProductItem', {

        init : function(ed, url) {
            var t = this;

            t.url = url;
            t.editor = ed;
            //t._createButtons();

            // Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('...');
            ed.addCommand('SL_ProductItem', function() {
                if ( tinymce.isIE )
                    ed.selection.moveToBookmark( ed.slProductItemBookmark );

                var el = ed.selection.getNode(),
                    productitem = sl.media.productitem,
                    frame;

                // Check if the `sl.media.productitem` API exists.
                if ( typeof sl === 'undefined' || ! sl.media || ! sl.media.productitem )
                    return;

                // Make sure we've selected a productitem node.
                if ( el.nodeName != 'IMG' || ed.dom.getAttrib(el, 'class').indexOf('slProductItem') == -1 )
                    return;

                frame = productitem.edit( '[' + ed.dom.getAttrib( el, 'title' ) + ']' );

                frame.state('productitem-edit').on( 'update', function( selection ) {
                    var shortcode = productitem.shortcode( selection ).string().slice( 1, -1 );
                    ed.dom.setAttrib( el, 'title', shortcode );
                });
            });

            ed.onInit.add(function(ed) {
                // iOS6 doesn't show the buttons properly on click, show them on 'touchstart'
                if ( 'ontouchstart' in window ) {
                    ed.dom.events.add(ed.getBody(), 'touchstart', function(e){
                        var target = e.target;

                        if ( target.nodeName == 'IMG' && ed.dom.hasClass(target, 'slProductItem') ) {
                            ed.selection.select(target);
                            ed.dom.events.cancel(e);
                            ed.plugins.shoplocket._hideButtons();
                            ed.plugins.shoplocket._showButtons(target, 'sl_productitembtns');
                        }
                    });
                }
            });

            ed.onMouseDown.add(function(ed, e) {
                if ( e.target.nodeName == 'IMG' && ed.dom.hasClass(e.target, 'slProductItem') ) {
                    ed.plugins.shoplocket._hideButtons();
                    ed.plugins.shoplocket._showButtons(e.target, 'sl_productitembtns');
                }
            });

            ed.onBeforeSetContent.add(function(ed, o) {
                o.content = t._do_productitem(o.content,t.url);
            });

            ed.onPostProcess.add(function(ed, o) {
                if (o.get)
                    o.content = t._get_productitem(o.content);
            });
        },

        _do_productitem : function(co,url) {
            return co.replace(/\[shoplocket([^\]]*)\]/g, function(a,b){
                var w = 200;
                var h = 200;
                var t = "shoplocketID";
                attrs = b.split(' ');
                for(var i=0;i<attrs.length;i++) {
                    attr = attrs[i].split("=");
                    if (attr[0]=="w") w = attr[1];
                    if (attr[0]=="h") h = attr[1];
                    if (attr[0]=="id") t = "shoplocket" + attr[1];
                }
                return '<img id="' + t + '" style="width: ' + w + 'px; height: ' + h + 'px;" src="'+url+'/../img/spacer.gif" class="shoplocketProductDiv mceItem" title="shoplocket'+tinymce.DOM.encode(b)+'" />';
            });
        },

        _get_productitem : function(co) {

            function getAttr(s, n) {
                n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
                return n ? tinymce.DOM.decode(n[1]) : '';
            };

            return co.replace(/(?:<p[^>]*>)*(<img[^>]+>)(?:<\/p>)*/g, function(a,im) {
                var cls = getAttr(im, 'class');

                if ( cls.indexOf('shoplocketProductDiv') != -1 )
                    return '<p>['+tinymce.trim(getAttr(im, 'title'))+']</p>';

                return a;
            });
        },

        _createButtons : function() {
            var t = this, ed = tinymce.activeEditor, DOM = tinymce.DOM, editButton, dellButton, isRetina;

            if ( DOM.get('sl_productitembtns') )
                return;

            isRetina = ( window.devicePixelRatio && window.devicePixelRatio > 1 ) || // WebKit, Opera
                ( window.matchMedia && window.matchMedia('(min-resolution:130dpi)').matches ); // Firefox, IE10, Opera

            DOM.add(document.body, 'div', {
                id : 'sl_productitembtns',
                style : 'display:none;'
            });

            editButton = DOM.add('sl_productitembtns', 'img', {
                src : isRetina ? t.url+'/img/edit-2x.png' : t.url+'/img/edit.png',
                id : 'sl_editproductitem',
                width : '24',
                height : '24',
                title : ed.getLang('shoplocket.editproductitem')
            });

            tinymce.dom.Event.add(editButton, 'mousedown', function(e) {
                var ed = tinymce.activeEditor;
                ed.slProductItemBookmark = ed.selection.getBookmark('simple');
                ed.execCommand("SL_ProductItem");
                ed.plugins.shoplocket._hideButtons();
            });

            dellButton = DOM.add('sl_productitembtns', 'img', {
                src : isRetina ? t.url+'/img/delete-2x.png' : t.url+'/img/delete.png',
                id : 'sl_delproductitem',
                width : '24',
                height : '24',
                title : ed.getLang('shoplocket.delproductitem')
            });

            tinymce.dom.Event.add(dellButton, 'mousedown', function(e) {
                var ed = tinymce.activeEditor, el = ed.selection.getNode();

                if ( el.nodeName == 'IMG' && ed.dom.hasClass(el, 'slProductItem') ) {
                    ed.dom.remove(el);

                    ed.execCommand('mceRepaint');
                    ed.dom.events.cancel(e);
                }

                ed.plugins.shoplocket._hideButtons();
            });
        },

        getInfo : function() {
            return {
                longname : 'ProductItem Settings',
                author : 'ShopLocket',
                authorurl : 'http://shoplocket.com',
                infourl : '',
                version : "1.0"
            };
        }
    });

    tinymce.PluginManager.add('shoplocketproduct', tinymce.plugins.slProductItem);
})();

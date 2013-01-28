// Docu : http://wiki.moxiecode.com/index.php/TinyMCE:Create_plugin/3.x#Creating_your_own_plugins

(function() {
	// Load plugin specific language pack
	//	tinymce.PluginManager.requireLangPack('TinyPass');

	tinymce.create('tinymce.plugins.TinyPass', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 * This call is done before the editor instance has finished it's initialization so use the onInit event
		 * of the editor instance to intercept that event.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init : function(ed, url) {

			var breakElem = '<img src="' + url + '/trans.gif" style="border: 0px; border-top: 1px dotted #cccccc; display: block; width: 100%; height: 12px; margin-top: 15px; background: #ffffff url(' + url + '/more_bug.gif) no-repeat right top;" class="mceTPmore mceItemNoResize" title="' + ed.getLang("wordpress.wp_more_alt") + '" />';
			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mceExample');

			ed.addCommand('mceTinyPassBreak', function() {
				ed.execCommand("mceInsertContent",0, breakElem);
			});

			// Register example button
			ed.addButton('TinyPass', {
				title : 'TinyPass.desc',
				cmd : 'mceTinyPassBreak',
				image : url + '/tpbutton.gif'
			});


			ed.onBeforeSetContent.add(function(f, g) {
				if (g.content) {
					g.content = g.content.replace(/<!--tpmore(.*?)-->/g, breakElem);
				}
			});

			ed.onPostProcess.add(function(ed, obj) {
				console.log(obj);
				if (obj.get) {
					obj.content = obj.content.replace(/<img[^>]+>/g, function(i) {
						if (i.indexOf('class="mceTPmore') !== -1) {
							var h,j = (h = i.match(/alt="(.*?)"/)) ? h[1] : "";
							i = "<!--tpmore " + j + "-->"
						}
						return i
					})
				}
			});


		// Add a node change handler, selects the button in the UI when a image is selected
		//			ed.onNodeChange.add(function(ed, cm, n) {
		//				cm.setActive('TinyPass', n.nodeName == 'IMG');
		//			});
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
		createControl : function(n, cm) {
			return null;
		},

		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @return {Object} Name/value array containing information about the plugin.
		 */
		getInfo : function() {
			return {
				longname  : 'TinyPass',
				author 	  : 'TinyPass',
				authorurl : 'http://www.tinypass.com',
				infourl   : 'http://www.tinypass.com',
				version   : "2.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('TinyPass', tinymce.plugins.TinyPass);
})();

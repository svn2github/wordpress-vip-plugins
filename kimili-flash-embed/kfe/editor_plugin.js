/* Import plugin specific language pack */
tinyMCE.importPluginLanguagePack('kfe', '');

var TinyMCE_KFEPlugin = {  
	getInfo : function() {  
		return {  
			longname : 'Kimili Flash Embed',  
			author : 'Michael Bester',  
			authorurl : 'http://kimili.com',  
			infourl : 'http://kimili.com/plugins/kml_flashembed',  
			version : "1.4.1"  
		};  
	},  
	getControlHTML : function(cn) {  
		switch (cn) {  
			case "kfe":
				var button = '<a id="mce_editor_0_kfe" href="javascript:tinyMCE.execInstanceCommand(\'{$editor_id}\',\'mcekfe\');" onclick="tinyMCE.execInstanceCommand(\'{$editor_id}\',\'mcekfe\');return false;" onmousedown="return false;" class="mceButtonNormal" target="_self"><img src="{$pluginurl}/images/flash.gif" title="Kimili Flash Embed"></a>';
				return button;
		}  
		return "";  
	},  
	execCommand : function(editor_id, element, command, user_interface, value) {  
		switch (command) {  
			case "mcekfe":  
			// Call the script
			var n = prompt("What is the absolute URL to your .SWF?");
			var h = prompt("How tall is your SWF?\n(In pixels or a percentage - i.e. 250 or 100%)");
			var w = prompt("How wide is your SWF?\n(In pixels or a percentage - i.e. 125 or 75%)");
			if (n && h && w) {
				var text = "[kml_flashembed movie=\"" + n + "\" height=\"" + h + "\" width=\"" + w + "\" /]";		
				window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, text);
				return true;	
			}
		}  
		return false;  
	}
};  
// Adds the plugin class to the list of available TinyMCE plugins  
tinyMCE.addPlugin("kfe", TinyMCE_KFEPlugin );
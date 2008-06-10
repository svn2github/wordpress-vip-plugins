tinyMCEPopup.requireLangPack();

var PageTitleDialog = {
	init : function() {
		var f = document.forms[0];

		// Get the selected contents as text and place it in the input
		var m;
        var selectedtext = tinyMCEPopup.editor.selection.getContent({format : 'raw'});
		f.pagetitle.value = (m = selectedtext.match(/<!--pagetitle:(.*?)-->/)) ? m[1] : '';
		
		if (f.pagetitle.value == '') { f.insert.value = 'Insert'; }
		else { f.insert.value = 'Update'; }
		
	},

	insert : function(ed, url) {
		// Insert the contents from the input into the document
		var pb = '<!--pagetitle:' + document.forms[0].pagetitle.value + '-->';	
			
		tinyMCEPopup.editor.execCommand('mceInsertRawHTML', 0, pb);
		tinyMCEPopup.close();
	}
};

tinyMCEPopup.onInit.add(PageTitleDialog.init, PageTitleDialog);

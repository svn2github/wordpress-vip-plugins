var TPReadOn = TPReadOn|| {
	enabled:true
};

jQuery(function () {
	function expand_readon(readOnLink, id, fetch){
		if(fetch){
			var url = jQuery(readOnLink).attr("rurl");
			jQuery.ajax({
				url: url,
				success: function(data) {
					jQuery('#' + id).html(data).slideToggle("slow");
				}	
			});
		}else {
			jQuery('#' + id).slideToggle("slow");
		}
	};

	// assume that "Read On" was not clicked
	var readOnClick = false;
	jQuery("a.readon-link").toggle(
		function(){
			if(!TPReadOn.enabled)
				return;
			// replace the hyperlink text "Read On" with "Collapse Post"
			jQuery(this).html('Collapse Post');
			// which means we've clicked on the "Read On" button
			readOnClick = true;
		},
		function () {
			jQuery(this).html(jQuery(this).attr('longdesc'));
		});

	jQuery("a.readon-link").click(function () {
		if(!TPReadOn.enabled)
				return false;

		var clickMode;
		// get the url that was clicked
		var hrefClicked = jQuery(this).attr("href");
		// determine whether "Read On" or "Collaped" was clicked
		if (readOnClick) {
			clickMode = "Read On";
		} else {
			clickMode = "Collapsed";
		}
		// and reset the "Read On" flag
		readOnClick = false;
			
		//expand(jQuery(this).parent().parent().find(".extended").attr("id"));
		expand_readon(jQuery(this), jQuery(this).attr("rid"), clickMode == 'Read On');
	});
		 
});


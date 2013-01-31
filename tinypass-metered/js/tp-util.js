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


	jQuery("body").delegate("a.readon-link", "click", function(){

		var expand = jQuery(this).html() == 'Read On';

		if(!TPReadOn.enabled && expand)
			return false;

		if(expand)
			jQuery(this).html('Collapse Post');
		else
			jQuery(this).html('Read On');

		// get the url that was clicked
		var hrefClicked = jQuery(this).attr("href");
			
		//expand(jQuery(this).parent().parent().find(".extended").attr("id"));
		expand_readon(jQuery(this), jQuery(this).attr("rid"), expand);
		return false;
	});
		 
});


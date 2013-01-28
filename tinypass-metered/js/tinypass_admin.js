var tinypass = {

	doError:function(fieldName, msg){
		jQuery("#tp-error").append("<p> &bull; " + msg + "</p>");
		jQuery('*[name*="'+fieldName+'"]').addClass("form-invalid tp-error");
	},

	clearError:function(fieldName, msg){
		jQuery(".form-invalid").removeClass("form-invalid");
		jQuery(".tp-error").removeClass("tp-error");
		jQuery("#tp-error").html("");
	},

	log:function(msg){
		if(console && console.log)
			console.log(msg);
	},
	fullHide:function(selector, scope){
		jQuery(selector).hide();
	},
	fullShow:function(selector){
		jQuery(selector).show();
	}

}
var fauxfb_connect_user = false;

function fauxfb_update_user_details() {
	fauxfb_connect_user = true;

	if (!jQuery('#fb-user').length) { 
		jQuery('.fauxfb_login_button').hide();
		jQuery('.fauxfb_hide_on_login').hide().after(
														"<span id='fb-user'>" +
														"<fb:profile-pic uid='loggedinuser' facebook-logo='true'></fb:profile-pic>" +
														"<span id='fb-msg'><strong>Hi <fb:name uid='loggedinuser' useyou='false'></fb:name>!</strong><br />You are logged in with your Facebook account. " +
														"<a href='#' onclick='FB.Connect.logoutAndRedirect( current_url ); return false;'>Logout</a>" +
														"</span></span>"
		);
	}
	// Refresh the DOM
	FB.XFBML.Host.parseDomTree();
}

function fauxfb_update_form_values() {
	if (fauxfb_connect_user) {
		profile = jQuery('#fb-user').find('.FB_ElementReady .FB_Link')[1]['href'];
		user_id = profile.substring(profile.indexOf('?id=')+4);
		name = jQuery('#fb-user').find('.FB_ElementReady .FB_Link').find('img').attr("title");
		jQuery('#url').val(profile); // FB profile URL
		jQuery('#email').val(user_id+'@facebook.com'); // Can't get a real one from FB unfortunately. This saves their user id @facebook.com
		jQuery('#author').val(name); // Gets their name from the DOM
		fauxfb_set_coockie('fauxfb_connect', 'yes');
	}
}

function fauxfb_empty_user_details() {
	jQuery('#url').val('');
	jQuery('#email').val('');
	jQuery('#author').val('');
}

function fauxfb_set_coockie(c_name,value,expiredays) {
	var exdate=new Date();
	exdate.setDate(exdate.getDate()+expiredays);
	document.cookie=c_name+ "=" +escape(value)+((expiredays==null) ? "" : ";expires="+exdate.toGMTString());
}

function fauxfb_get_coockie(c_name) {
	if (document.cookie.length>0) {
		c_start=document.cookie.indexOf(c_name + "=");
		if (c_start!=-1) {
			c_start=c_start + c_name.length+1;
			c_end=document.cookie.indexOf(";",c_start);
			if (c_end==-1) c_end=document.cookie.length;
			return unescape(document.cookie.substring(c_start,c_end));
		}
	}
	return "";
}
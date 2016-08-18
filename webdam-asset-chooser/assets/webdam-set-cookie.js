/**
 * Set a Cookie helper
 *
 * @param cname
 * @param cvalue
 * @param exdays
 */
function webdam_set_cookie(cname, cvalue, exdays) {
	var d = new Date();
	d.setTime(d.getTime() + (exdays*24*60*60*1000));
	var expires = "expires="+d.toUTCString();
	document.cookie = cname + "=" + cvalue + ";path=/;";
}

/**
 * Get URL query string values by name
 *
 * @param name
 * @returns {string}
 */
function getParameterByName(name) {
	name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
	var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
		results = regex.exec(location.search);
	return results == null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

// Set the WebDAM Cookie
webdam_set_cookie('widgetEmbedValue', getParameterByName('widgetEmbedValue'), 1);
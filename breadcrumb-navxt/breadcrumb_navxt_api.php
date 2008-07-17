<?php
/**
 * Breadcrumb NavXT - API
 *
 * Functions inside the global Namespace used by Breadcrumb NavXT
 *
 * @author John Havlik
 * @author Tom Klingenberg
 *
 * 2008-03-06:
 * FIX: bcn_get						-	Reworked the conditions for when spaces
 * 										will be preserved.
 * 2008-02-07:
 * ADD: bcn_get_option_inputvalue	-	Escape Option Values to be used inside 
 *                                  	(X)HTML Element Attribute Values.
 * FIX: bcn_get                   	- 	fixed issue solved inside wordpress main 
 *                                  	codebase in 2007-09.
 *                                  	see http://trac.wordpress.org/ticket/4781
 */

/**
 * Get Option, get_option Replacement
 *
 * @param string optionname name of the wordpress option
 */
function bcn_get_option($optionname)
{
	//Retrieve the option value
	$bcn_value = get_option($optionname);
	
	//Remove &nbsp; so that it looks correct (string problem)
	$bcn_value = str_replace("&nbsp;", " ", $bcn_value);
	
	return $bcn_value;
}

/**
 * Update Option, update_option Replacement
 * 
 * @param unknown_type $optionname
 * @param unknown_type $value
 * @see bcn_get_option
 */
function bcn_update_option($optionname, $value)
{
	$bcn_value = $value;
	
	/*
	 * Only if we have a string should we check for spaces
	 * 
	 * @note since $value is from $_POST[] this will return true ever^^
	 * 
	 * 
	 * @todo Instead of poking blindly around and inventing stupid algos:
	 * 	     enclose the whole string sothat everything is preserved automatically
	 * 		 bcn_get_option can remove the enclosure then again with ease 100% transparent  
	 */	
	if(is_string($bcn_value))
	{		
		//Preserving the front space if exists
		if(strpos($bcn_value, " ") === 0)
		{
			$bcn_value = "&nbsp;" . ltrim($bcn_value);
		}
		//Preserv the end space if exists
		$bcn_length = strlen($bcn_value) - 1;
		if($bcn_length > 0)
		{
			if(strpos($bcn_value, " ", $bcn_length - 1) === $bcn_length)
			{
				$bcn_value = rtrim($bcn_value) . "&nbsp;";
			}
		}
	}
		
	return update_option($optionname, $bcn_value);
}

/**
 * bcn_get_option_inputvalue
 *
 * Administration input complex, Escapes Option Values for the 
 * Output inside the XHTML Forms. The returned value is safe
 * for usage inside value="".
 *
 * @param  (string) optionname name of the wordpress option
 * @return (string) escaped option-value
 * @since  2008-02-07
 */
function bcn_get_option_inputvalue($optionname)
{
	//Retrieve the option value
	$bcn_value = bcn_get_option($optionname);
	
	//Convert any (x)HTML special charactors into a form that won't mess up the web form
	$bcn_value_secaped = htmlspecialchars($bcn_value);
	
	//Return the escaped value
	return $bcn_value_secaped;
}
/**
 * bcn_get
 *
 * Administration input complex, replaces the broken WordPress one
 * Based off of the suggestions and code of Tom Klingenberg
 *
 * Removes Faulty Adding Slashes and Preserves leading and trailing spaces
 *
 * Wordpress adds slashes to Request Variables by Default (before
 * removing those added by PHP) - This re-invents the wheel
 * and mimicks all the problems with magic_quotes_gpc.
 * The faulty adding slashes is done in wp-settings.php.
 * 
 * Therefore the plugin needs to unslash the slashed potential 
 * unslahsed-phpslashed data again. This is done in this function.
 *
 * @param  (string) varname name of the post variable
 * @param  (string) default deftaul value (optional)
 * @return (string) unescaped post data
 * @note   WP-Version 2.3.3, wp-settings.php #259ff
 */
function bcn_get($varname, $default = "")
{	
	//Import variable from post-request
	$bcn_value = $_POST[$varname];
	
	//If null kick out early (handle default values as well)
	if($bcn_value == "")
	{
		return $default;
	}
	
	//Only if we have a string should we check for spaces
	// >> this has been migrated to where it belongs to: bcn_update_option	
	
	//Remove by faulty-wordpress-code added slashes
	$bcn_value = stripslashes($bcn_value);
	
	//Return unslashed value
	return $bcn_value;
}
/**
 * bcn_local
 *
 * Initilizes localization domain
 */
function bcn_local()
{
	//Load breadcrumb-navxt translation
	load_plugin_textdomain($domain = 'breadcrumb_navxt', $path = PLUGINDIR . '/breadcrumb-navxt');
}

/**
 * bcn_wp_static_frontpage
 * 
 * does this wordpress installation uses a static page as frontpage
 * or the standard listing of latest posts?
 * 
 * @return bool true if wordpress uses a static frontpage
 * @since  2.1.3
 */
function bcn_wp_has_static_frontpage()
{
	/*
	 * the option is taken directly from wordpress configuraion 
	 *	 
	 * wp option: get_option('show_on_front')
	 * 
	 * @see http://codex.wordpress.org/Option_Reference
	 * 
	 * 		page_on_front
	 *  
	 * 		The ID of the page that should be displayed on the front page. 
	 * 		Requires show_on_front's value to be page.
	 * 		Data type: Integer
	 * 
	 * 		show_on_front 
	 * 
	 * 		What to show on the front page
	 * 		'posts' : Your latest posts 
	 *		'page' : A static page (see page_on_front) 
	 *		Data type: String
	 */
	
	$blog_has_static_frontpage = (bool) (get_option('show_on_front') == 'page');
	
	return $blog_has_static_frontpage;
}

/**
 * Get Wordpress Homepage
 * 
 * @return string URL of wordpress homepage
 * @since  2.1.3
 */
function bcn_wp_url_home()
{
	$url_home = get_option('home') . '/';
		
	return $url_home;
}


?>
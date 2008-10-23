<?php
/*
Plugin Name: Kimili Flash Embed
Plugin URI: http://www.kimili.com/plugins/kml_flashembed
Description: Provides a wordpress interface for Geoff Stearns' excellent standards compliant <a href="http://blog.deconcept.com/flashobject/">Flash detection and embedding JavaScript</a>. The syntax is <code>[kml_flashembed movie=&quot;filename.swf&quot; height=&quot;250&quot; width=&quot;400&quot; /]</code>.
Version: 1.4.3
Author: Michael Bester
Author URI: http://www.kimili.com
Update: http://www.kimili.com/plugins/kml_flashembed/wp
*/

/*
*
*	KIMILI FLASH EMBED
*
*	Copyright 2008 Michael Bester (http://www.kimili.com)
*	Released under the GNU General Public License (http://www.gnu.org/licenses/gpl.html)
*
*/

/***********************************************************************
*	Global Vars
************************************************************************/

$kml_request_type		= "";
$kml_flashembed_ver		= "1.4.3";
$kml_flashembed_root	= get_settings('siteurl') . '/wp-content/themes/vip/plugins/kimili-flash-embed';

/***********************************************************************
*	Load Dependencies 
************************************************************************/

if( !class_exists('buttonsnap') )
	require_once ('buttonsnap.php');


/***********************************************************************
*	Run the main function 
************************************************************************/

function kml_flashembed($content) {
	$pattern = '/(<p>[\s\n\r]*)??(([\[<]KML_(FLASH|SWF)EMBED.*\/[\]>])|([\[<]KML_(FLASH|SWF)EMBED.*[\]>][\[<]\/KML_(FLASH|SWF)EMBED[\]>]))([\s\n\r]*<\/p>)??/Umi'; 
	$result = preg_replace_callback($pattern,'kml_flashembed_parse_kfe_tags',$content);
	return $result;	
}


/***********************************************************************
*	Parse out the KFE Tags
************************************************************************/

function kml_flashembed_parse_kfe_tags($match) {
		
	$r	= "";
			
	# Clean up and untexturize tag
	$strip		= array('[KML_FLASHEMBED',
						'][/KML_FLASHEMBED]',
						'[kml_flashembed',
						'][/kml_flashembed]',
						'[KML_SWFEMBED',
						'][/KML_SWFEMBED]',
						'[kml_swfembed',
						'][/kml_swfembed]',
						'/]',
						'<KML_FLASHEMBED',
						'></KML_FLASHEMBED>',
						'<kml_flashembed',
						'></kml_flashembed>',
						'<KML_SWFEMBED',
						'></KML_SWFEMBED>',
						'<kml_swfembed',
						'></kml_swfembed>',
						'/>',
						'\n',
						'<br>',
						'<br />',
						'<p>',
						'</p>'
						);
						
	$elements	= str_replace($strip, '', $match[0]);
	
	$elements	= preg_replace("/=(\s*)\"/", "==`", $elements);
	$elements	= preg_replace("/=(\s*)&Prime;/", "==`", $elements);
	$elements	= preg_replace("/=(\s*)&prime;/", "==`", $elements);
	$elements	= preg_replace("/=(\s*)&#8221;/", "==`", $elements);
	$elements	= preg_replace("/\"(\s*)/", "`| ", $elements);
	$elements	= preg_replace("/&Prime;(\s*)/", "`|", $elements);
	$elements	= preg_replace("/&prime;(\s*)/", "`|", $elements);
	$elements	= preg_replace("/&#8221;(\s*)/", "`|", $elements);
	$elements	= preg_replace("/&#8243;(\s*)/", "`|", $elements);
	$elements	= preg_replace("/&#8216;(\s*)/", "'", $elements);
	$elements	= preg_replace("/&#8217;(\s*)/", "'", $elements);
	
	$attpairs	= preg_split('/\|/', $elements, -1, PREG_SPLIT_NO_EMPTY);
	$atts		= array();
	
	// Create an associative array of the attributes
	for ($x = 0; $x < count($attpairs); $x++) {
		
		$attpair		= explode('==', $attpairs[$x]);
		$attn			= trim(strtolower($attpair[0]));
		$attv			= preg_replace("/`/", "", trim($attpair[1]));
		$atts[$attn]	= $attv;
	}
	
	if (isset($atts['movie']) && isset($atts['height']) && isset($atts['width'])) {
		
		$atts['fversion']	= (isset($atts['fversion'])) ? $atts['fversion'] : 6;
		
		if (isset($atts['fvars'])) {
			$fvarpair_regex		= "/(?<!([$|\?]\{))\s+;\s+(?!\})/";
			$atts['fvars']		= preg_split($fvarpair_regex, $atts['fvars'], -1, PREG_SPLIT_NO_EMPTY);
		}
		
		// Convert any quasi-HTML in alttext back into tags
		$atts['alttext']		= (isset($atts['alttext'])) ? preg_replace("/{(.*?)}/i", "<$1>", $atts['alttext']) : '';
		
		// If we're not serving up a feed, generate the script tags
		if ($GLOBALS['kml_request_type'] != "feed") {
			$r	= kml_flashembed_build_fo_script($atts);
		} else {
			$r	= kml_flashembed_build_object_tag($atts);
		}
	}
 	return $r; 
}


/***********************************************************************
*	Build the Javascript from the tags
************************************************************************/

function kml_flashembed_build_fo_script($atts) {
	
	global $kml_flashembed_root;
	
	if (is_array($atts)) extract($atts);
	
	$out	= array();
	$ret	= "";
	
	$rand	= mt_rand();  // For making sure this instance is unique
	
	// Extract the filename minus the extension...
	$swfname	= (strrpos($movie, "/") === false) ?
							$movie :
							substr($movie, strrpos($movie, "/") + 1, strlen($movie));
	$swfname	= (strrpos($swfname, ".") === false) ?
							$swfname :
							substr($swfname, 0, strrpos($swfname, "."));
	
	// ... to use as a default ID if an ID is not defined.
	$fid			= (isset($fid)) ? $fid : "fm_" . $swfname . "_" . $rand;
	// ... as well as an empty target if that isn't defined.
	if (empty($target)) {              
		$targname	= "so_targ_" . $swfname . "_" . $rand;
		$classname	= (empty($targetclass)) ? "flashmovie" : $targetclass;
		// Create a target div
		$out[]		= '<div id="' . $targname . '" class="' . $classname . '">'.$alttext.'</div>';
		$target	= $targname;
	}
  	
	// Set variables for rendering JS
	$movie 				= '"'.$movie.'",'; 
	$fid 				= '"'.$fid.'",'; 
	$width 				= '"'.$width.'",'; 
	$height				= '"'.$height.'",';
	$fversion			= '"'.$fversion.'",';
	$bgcolor			= (isset($bgcolor)) ? '"'.$bgcolor.'",' : '"",';
	$useexpressinstall	= (isset($useexpressinstall) && $useexpressinstall == 'true') ? true : false;
	$quality			= (isset($quality)) ? '"'.$quality.'",' : '"",';
	$xiredirecturl		= (isset($xiredirecturl)) ? '"'.$xiredirecturl.'",' : '"",';
	$redirecturl		= (isset($redirecturl)) ? '"'.$redirecturl.'",' : '"",';
	$detectKey			= (isset($detectKey)) ? '"'.$detectKey.'"' : '""';
	$fvars				= (isset($fvars)) ? $fvars : array();				
	
									$out[] = '';
						  	  		$out[] = '<script type="text/javascript">';
						  	  		$out[] = '	// <![CDATA[';
									$out[] = '';
						  	  		$out[] = '	var so_' . $rand . ' = new SWFObject('.$movie . $fid . $width . $height . $fversion . $bgcolor . $quality . $xiredirecturl . $redirecturl . $detectKey . ');';
	if (isset($play))				$out[] = '	so_' . $rand . '.addParam("play", "' . $play . '");';
	if (isset($loop))				$out[] = '	so_' . $rand . '.addParam("loop", "' . $loop . '");';
	if (isset($menu)) 				$out[] = '	so_' . $rand . '.addParam("menu", "' . $menu . '");';
	if (isset($scale)) 				$out[] = '	so_' . $rand . '.addParam("scale", "' . $scale . '");';
	if (isset($wmode)) 				$out[] = '	so_' . $rand . '.addParam("wmode", "' . $wmode . '");';
	if (isset($align)) 				$out[] = '	so_' . $rand . '.addParam("align", "' . $align . '");';
	if (isset($salign)) 			$out[] = '	so_' . $rand . '.addParam("salign", "' . $salign . '");';    
	if (isset($base)) 	   		 	$out[] = '	so_' . $rand . '.addParam("base", "' . $base . '");';
	if (isset($allowscriptaccess))	$out[] = '	so_' . $rand . '.addParam("allowScriptAccess", "' . $allowscriptaccess . '");';
	if (isset($allowfullscreen))	$out[] = '	so_' . $rand . '.addParam("allowFullScreen", "' . $allowfullscreen . '");';
	if ($useexpressinstall) {
		$xiswf = $kml_flashembed_root.'/lib/expressinstall.swf';
									$out[] = '	so_' . $rand . '.useExpressInstall("' . $xiswf . '");';
	}		
	// Loop through and add any name/value pairs in the $fvars attribute
	for ($i = 0; $i < count($fvars); $i++) {
		$thispair	= trim($fvars[$i]);
		$nvpair		= explode("=",$thispair);
		$name		= trim($nvpair[0]);
		$value		= "";
		for ($j = 1; $j < count($nvpair); $j++) {			// In case someone passes in a fvars with additional "="       
			$value		.= trim($nvpair[$j]);
			$value		= preg_replace('/&#038;/', '&', $value);
			if ((count($nvpair) - 1)  != $j) {
				$value	.= "=";
			}
		}
		// Prune out JS or PHP values
		if (preg_match("/^\\$\\{.*\\}/i", $value)) { 		// JS
			$endtrim 	= strlen($value) - 3;
			$value		= substr($value, 2, $endtrim);
			$value		= str_replace(';', '', $value);
		} else if (preg_match("/^\\?\\{.*\\}/i", $value)) {	// PHP
			$endtrim 	= strlen($value) - 3;
			$value 		= substr($value, 2, $endtrim);
			$value 		= '"'.eval("return " . $value).'"';
		} else {
			$value = '"'.$value.'"';
		}
									$out[] = '	so_' . $rand . '.addVariable("' . $name . '",' . $value . ');';
	}
	
									$out[] = '	so_' . $rand . '.write("' . $target . '");';
									$out[] = '';
									$out[] = '	// ]]>';
									$out[] = '</script>';
	// Add NoScript content
	if (!empty($noscript)) {
									$out[] = '<noscript>';
									$out[] = '	' . $noscript;
									$out[] = '</noscript>';
	}
									$out[] = '';
											
	$ret .= join("\n", $out);
	return $ret;
}
           
/***********************************************************************
*	Build a Satay Object for RSS feeds
************************************************************************/

function kml_flashembed_build_object_tag($atts) {
	
	$out	= array();	
	if (is_array($atts)) extract($atts);
	
	// Build a query string based on the $fvars attribute
	$querystring = (count($fvars) > 0) ? "?" : "";
	for ($i = 0; $i < count($fvars); $i++) {
		$thispair	= trim($fvars[$i]);
		$nvpair		= explode("=",$thispair);
		$name		= trim($nvpair[0]);
		$value		= "";
		for ($j = 1; $j < count($nvpair); $j++) {			// In case someone passes in a fvars with additional "="
			$value		.= trim($nvpair[$j]);
			$value		= preg_replace('/&#038;/', '&', $value);
			if ((count($nvpair) - 1)  != $j) {
				$value	.= "=";
			}
		}
		// Prune out JS or PHP values
		if (preg_match("/^\\$\\{.*\\}/i", $value)) { 		// JS
			$endtrim 	= strlen($value) - 3;
			$value		= substr($value, 2, $endtrim);
			$value		= str_replace(';', '', $value);
		} else if (preg_match("/^\\?\\{.*\\}/i", $value)) {	// PHP
			$endtrim 	= strlen($value) - 3;
			$value 		= substr($value, 2, $endtrim);
			$value 		= eval("return " . $value);
		}
		// else {
		//	$value = '"'.$value.'"';
		//}
		$querystring .= $name . '=' . $value;
		if ($i < count($fvars) - 1) {
			$querystring .= "&";
		}
	}
	
									$out[] = '';    
						  	  		$out[] = '<object	type="application/x-shockwave-flash"';
									$out[] = '			data="'.$movie.$querystring.'"'; 
	if (isset($base)) 	   		 	$out[] = '			base="'.$base.'"';
									$out[] = '			width="'.$width.'"';
									$out[] = '			height="'.$height.'">';
									$out[] = '	<param name="movie" value="' . $movie.$querystring . '" />';
	if (isset($play))				$out[] = '	<param name="play" value="' . $play . '" />';
	if (isset($loop))				$out[] = '	<param name="loop" value="' . $loop . '" />';
	if (isset($menu)) 				$out[] = '	<param name="menu" value="' . $menu . '" />';
	if (isset($scale)) 				$out[] = '	<param name="scale" value="' . $scale . '" />';
	if (isset($wmode)) 				$out[] = '	<param name="wmode" value="' . $wmode . '" />';
	if (isset($align)) 				$out[] = '	<param name="align" value="' . $align . '" />';
	if (isset($salign)) 			$out[] = '	<param name="salign" value="' . $salign . '" />';    
	if (isset($base)) 	   		 	$out[] = '	<param name="base" value="' . $base . '" />';
	if (isset($allowscriptaccess))	$out[] = '	<param name="allowScriptAccess" value="' . $allowscriptaccess . '" />';
	if (isset($allowfullscreen))	$out[] = '	<param name="allowFullScreen" value="' . $allowfullscreen . '" />';
	 								$out[] = '</object>';     

	$ret .= join("\n", $out);
	return $ret;
	
}

/***********************************************************************
*	Add the call to flashobject.js
************************************************************************/

function kml_flashembed_add_flashobject_js() {
	global $kml_flashembed_ver, $kml_flashembed_root;
	echo '
	<!-- Courtesy of Kimili Flash Embed - Version '. $kml_flashembed_ver . ' -->
	<script src="' . $kml_flashembed_root . '/js/swfobject.js" type="text/javascript"></script>
';
}


/***********************************************************************
*	Toolbar Button Functions                                             
*	Props to Alex Rabe for fuguring out the WP 2.1 buttonsnap workaround
* 	http://alexrabe.boelinger.com/?page_id=46
************************************************************************/

function kml_flashembed_addbuttons() {  
  
	global $wp_db_version, $kml_flashembed_root;  
 	
	// Check for WordPress 2.5+ and activated RTE
	if (  $wp_db_version >= 6846 && 'true' == get_user_option('rich_editing')  ) {  
		
		add_filter( 'mce_external_plugins', 'kml_flashembed_plugin', 0 );
		add_filter( 'mce_buttons', 'kml_flashembed_button',0 );
		
	// Check for WordPress 2.1+ and activated RTE
	} elseif ( 3664 <= $wp_db_version && 'true' == get_user_option('rich_editing') ) {  
		// add the button for wp21 in a new way  
		add_filter("mce_plugins", "kml_flashembed_plugin", 0);  
		add_filter('mce_buttons', 'kml_flashembed_button', 0);  
		add_action('tinymce_before_init','kml_flashembed_load');
		
	}
	
	if (class_exists('buttonsnap')) {
		$button_image_url = $kml_flashembed_root.'/kfe/images/flash.gif';  
		buttonsnap_separator();  
		buttonsnap_ajaxbutton($button_image_url, __('Kimili Flash Embed', 'kfe'), 'kml_flashembed_insert_hook');
		add_filter('kml_flashembed_insert_hook', 'kml_flashembed_insert_tag');
		
	}  
}

// used to insert button in wordpress 2.1x and 2.5 editor  
function kml_flashembed_button($buttons) {  
	array_push($buttons, "separator", "kfe");  
	return $buttons;  
}  
  
// Tell TinyMCE that there is a plugin 
function kml_flashembed_plugin($plugins) {  
	
	global $kml_flashembed_root, $wp_db_version;
	
	// WP 2.5
	if ( $wp_db_version >= 6846 )
		$plugins['kfe'] = $kml_flashembed_root.'/kfe/editor_plugin_tmce3.js';
	// WP 2.1 - 2.3
	else
		array_push($plugins, "-kfe");  
	return $plugins;  
}

// Load the TinyMCE plugin : editor_plugin.js (wp2.1)  
function kml_flashembed_load() {
	
	global $kml_flashembed_root;
	
	$pluginURL = $kml_flashembed_root.'/kfe/';
	
	echo 'tinyMCE.loadPlugin("kfe", "'.$pluginURL.'");'."\n"; 
	return;  
}

function kml_flashembed_insert_tag($selectedtext) {
	return '[kml_flashembed movie="'. $selectedtext . '" height="" width="" /]';
}

/***********************************************************************
*	Apply the filter 
************************************************************************/

if (preg_match("/(\/\?feed=|\/feed)/i",$_SERVER['REQUEST_URI'])) {
	// RSS Feeds
	$kml_request_type	= "feed";
} else {
	// Everything else
	$kml_request_type	= "nonfeed";
	add_action('wp_head', 'kml_flashembed_add_flashobject_js');
	add_action('init', 'kml_flashembed_addbuttons');
}

// Apply all over except the admin section
if (strpos($_SERVER['REQUEST_URI'], 'wp-admin') === false ) {
	add_action('template_redirect','kmlDoObStart');
}



/***********************************************************************
*	Trigger Function
************************************************************************/

function kmlDoObStart()
{
	ob_start('kml_flashembed');
}

?>
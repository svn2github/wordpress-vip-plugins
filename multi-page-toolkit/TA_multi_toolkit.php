<?php

/*
Plugin Name: Multi-page Toolkit
Plugin URI:  http://www.tarkan.info/tag/multi-page
Description: Multipage posts with page titling and single page view. Fully featured quick jump and navigation options. Easy to use with Visual editor integration.
Version: 2.6-WPCOM
Author: Tarkan Akdam
Author URI: http://www.tarkan.info


	Copyright (c) 2007, 2008 Tarkan Akdam (http://www.tarkan.info)
	Please consider making a donation if you found this plugin useful
	
	Multi Page Toolkit is released under the GNU General Public
	License: http://www.gnu.org/licenses/gpl.txt

	This is a WordPress plugin (http://wordpress.org). WordPress is
	free software; you can redistribute it and/or modify it under the
	terms of the GNU General Public License as published by the Free
	Software Foundation; either version 2 of the License, or (at your
	option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
	General Public License for more details.

	For a copy of the GNU General Public License, write to:

	Free Software Foundation, Inc.
	59 Temple Place, Suite 330
	Boston, MA  02111-1307
	USA

	You can also view a copy of the HTML version of the GNU General
	Public License at http://www.gnu.org/copyleft/gpl.html

CHANGELOG
v2.6	Added auto-insert function for Pages
		Checked compatible with Wordpress 2.6.1
		Fixed options reseting to default when no page seperator selected
		Fixed text entry boxes now accept html code without breaking
v2.5	SVN broke upload - uploading again
v2.4	Changed CSS style naming for the all page links
v2.3 (internal release)
		Added option to have custom page seperators (including javascript for adverts) on single (ALL) page display
		Added function call to use options page settings from inside the theme files
		Fixed badly coded string matching (Thanks to Andrei for the fix)
		Fixed spelling error in button registration process (Thanks to Jonathan for spotting this)
		Fixed maintain trailing slash consistency across site
v2.2 (internal release)
		Added ALL link Display Text Option
		Added new CSS class for ALL link styling (contentjumpall , contentlistall)
		Fixed bug where pagetitle followed by nextpage caused errors
		Fixed tinymce js cache issues (hopefully..)
		Fixed navigation for preview draft posts
v2.1 -	Corrected Folder Naming Error
v2.0 -	Added admin options page to auto insert navigation menu
		Added View ALL pages link option
		Visual Editor Integration (TinyMCE) buttons for nextpage and pagetitle (wordpress 2.5 and above only)
v1.2 -	Added a check for trailing slashs and permalink structure for paging
		Cleaned up readme.txt
		Checked compatibility with WP 2.5rc1
v1.1 -	NEW quick jump method - page list / content menu
v1.0 -	Initial release

*/


function TA_display_pages($firsttext = ' Page ' , $lasttext = ' ' , $midtext = ' of ' , $display_type = 'all' ) {
	global $numpages, $multipage, $page;
	
	if ( $multipage ) {
		if ( $display_type == 'all' ) {
			$output = $firsttext .$page . $midtext .$numpages . $lasttext;
		}
		
		if ( $display_type == 'current' ) {
			$output = $firsttext . $page . $lasttext;
		}
		
		if ( $display_type == 'total' ) {
			$output = $firsttext . $numpages . $lasttext;
		}	
		
		echo $output;			
		return $output;
	}	
}
function TA_content_jump($before = '<p>', $after = '</p>', $title_number = 2, $quick_type = 1, $nav_type = 2, $nav_number = TRUE, $previouspagelink = '&laquo;', $nextpagelink = '&raquo;', $firstpagetext = 'On First Page', $lastpagetext = 'On Last Page', $display_all = TRUE, $display_all_text = 'View All') {
	if ($before == '1') $args = 1;
	elseif ($before == '2') $args = 2;
	else $args = compact('before', 'after', 'title_number', 'quick_type', 'nav_type', 'nav_number', 'previouspagelink', 'nextpagelink', 'firstpagetext', 'lastpagetext', 'display_all', 'display_all_text');
	
	echo TA_navigation($args);
}

function TA_navigation($args= '') {

	$defaults = array ('before' => '<p>', 'after' => '</p>', 'title_number' => 2, 'quick_type' => 1, 'nav_type' => 2, 'nav_number' => TRUE, 'previouspagelink' => '&laquo;', 'nextpagelink' => '&raquo;', 'firstpagetext' => 'On First Page', 'lastpagetext' => 'On Last Page', 'display_all' => TRUE, 'display_all_text' => 'View All', 'echo' => TRUE);

	// Check if using option page settings or custom setting in function call
	If ($args == 1) {
			$ta_multipage = get_option("ta_multipage"); 
			$before = 			$ta_multipage['mp1_before'];
			$after = 			$ta_multipage['mp1_after'];
			$title_number = 	$ta_multipage['mp1_title_number'];
			$quick_type = 		$ta_multipage['mp1_quick_type'];
			$nav_type = 		$ta_multipage['mp1_nav_type'];
			$nav_number = 		$ta_multipage['mp1_nav_number'];
			$previouspagelink = $ta_multipage['mp1_previouspagelink'];
			$nextpagelink = 	$ta_multipage['mp1_nextpagelink'];
			$firstpagetext = 	$ta_multipage['mp1_firstpagetext'];
			$lastpagetext = 	$ta_multipage['mp1_lastpagetext'];
			$display_all = 		$ta_multipage['mp1_display_all'];
			$display_all_text = $ta_multipage['mp1_display_all_text'];
	}
	ElseIf ($args == 2) {
			$ta_multipage = get_option("ta_multipage"); 
			$before = 			$ta_multipage['mp2_before'];
			$after = 			$ta_multipage['mp2_after'];
			$title_number = 	$ta_multipage['mp2_title_number'];
			$quick_type = 		$ta_multipage['mp2_quick_type'];
			$nav_type = 		$ta_multipage['mp2_nav_type'];
			$nav_number = 		$ta_multipage['mp2_nav_number'];
			$previouspagelink = $ta_multipage['mp2_previouspagelink'];
			$nextpagelink = 	$ta_multipage['mp2_nextpagelink'];
			$firstpagetext = 	$ta_multipage['mp2_firstpagetext'];
			$lastpagetext = 	$ta_multipage['mp2_lastpagetext'];
			$display_all = 		$ta_multipage['mp2_display_all'];
			$display_all_text = $ta_multipage['mp2_display_all_text'];
	}
	Else {
			$r = wp_parse_args( $args, $defaults );
			extract( $r, EXTR_SKIP );
	}

	global $numpages, $multipage, $page, $posts, $post;

	if ( $multipage ) {
	
			$pagetitlestring = '/<!--pagetitle:(.*?)-->/';
			preg_match_all($pagetitlestring, $posts[0]->post_content, $titlesarray, PREG_PATTERN_ORDER);
			$pagetitles = $titlesarray[1];
			$ta_pagetitles = $titlesarray[1];
			
			$previouslink = $page - 1;
			$nextlink = $page + 1;
				
			$previoustitle = $pagetitles[$previouslink - 1];
			$nexttitle = $pagetitles[$nextlink - 1];
			
			$slash_yes = '';  // assume no trailing slash in permalink unless detected otherwise later
			
			if (get_query_var('all') == '1') $allpage_link = TRUE;
			
			if ( '' == get_option('permalink_structure') || 'draft' == $post->post_status ) {
				$page_link_type = '&amp;page=';
				$page_link_all = '&amp;all=1';
			} else {
				$page_link_type = '/';
				$page_link_all = '/all/1';
				$url = get_permalink();
				if ( '/' == $url[strlen($url)-1]) $slash_yes = '/'; 
			}
			
			if ( (empty($previoustitle)) && (empty($nexttitle)) && ($quick_type == 1) ) $nav_type = 2;
			
			if ($nav_number) {
				$previoustitle = $previouslink .'. '. $previoustitle;
				$nexttitle = $nextlink .'. '. $nexttitle;
			}
			
			$output = $before;
			if ($quick_type ==1) $output .= '<form name="content_jump">';
			
			if ($previouslink == 1) $previouslink_checked = '">';
			else $previouslink_checked = $page_link_type . $previouslink . $slash_yes .'">';

			if ($page > 1) {
				if ($nav_type == 2) $output .= '<a class="contentjumplink" href="' . untrailingslashit(get_permalink()) . $previouslink_checked . $previouspagelink.'</a>';
				if ($nav_type == 1) $output .= '<a class="contentjumptitle" href="' . untrailingslashit(get_permalink()) . $previouslink_checked . $previoustitle.'</a>';
				}
			else {
				if ($nav_type == 2) $output .= '<span class="contentjumplink" >'. $previouspagelink.'</span>';
				if ($nav_type == 1) $output .= '<span class="contentjumptitle" >'.$firstpagetext.'</span>';
			}	
			
			if (($quick_type == 0) && ($nav_type == 1)) {
				if (empty($pagetitles[$page - 1])) $output .= '<span class="contentjumptitle" >Page '.$page.'</span>';
				else {
					if ($nav_number) $output .= '<span class="contentjumptitle" >'. $page . '. ' . $pagetitles[$page - 1] . '</span>';
					else $output .= '<span class="contentjumptitle" >'. $pagetitles[$page - 1].'</span>';
				}
			}
			
			if ($quick_type == 1) {
				$output .= '<select class="contentjumpddl" onchange="location = this.options[this.selectedIndex].value;">' ;
			
				for ( $i = 1; $i < ($numpages+1); $i = $i + 1 ) {
					$pagename = $pagetitles[$i-1];				
					
					if ( 1 == $i ) $output .='<option value="'. get_permalink().'"' ;
					else $output .='<option value="'. untrailingslashit(get_permalink()) . $page_link_type . $i. $slash_yes . '"' ;
					
					if ($page == $i) $output .= 'selected="selected"' ;
				
					if (empty($pagename)) $output .= '>Page ' . $i;
					else {
						$output .= '>';
						if ($title_number == 0) $output.= $pagename ;
						if ($title_number == 1) $output.= $pagename .' (' .$i.'/'.$numpages.')';
						if ($title_number == 2) $output.= $i .'. ' . $pagename ;
					}
					$output .='</option>';	
				}
				if ($display_all) {
					$output .='<option value="'. untrailingslashit(get_permalink()) . $page_link_all . '"' ;
					if ($allpage_link) $output .= 'selected="selected"' ;
						$output .= '>';
						if ($title_number == 2) $output.= $numpages + 1 . '. '. $display_all_text ;
						else $output.= $display_all_text;			
						$output .='</option>';
				}
				$output .= '</select>';
			}
			
			if ($quick_type == 2) {
			
				for ( $i = 1; $i < ($numpages+1); $i = $i + 1 ) {
					$output .= ' ';
					if ( ($i != $page || $allpage_link) && (!$more) ) {
						if ( 1 == $i ) {
							$output .= '<a class="contentjumpnumber" href="' . get_permalink() . '">';
						} else {
							$output .= '<a class="contentjumpnumber" href="' . untrailingslashit(get_permalink()) . $page_link_type . $i . $slash_yes . '">';
						}	
						$output .= $i . '</a>';
					}
					if ($page == $i && !$allpage_link) $output .= '<span class="contentjumpnumber">'.$i.'</span>';	
				}
				if ($display_all) {
					if ($allpage_link) $output .= '<span class="contentjumpall">' . $display_all_text . '</span>';
					else $output .= '<a class="contentjumpall" href="'. untrailingslashit(get_permalink()) . $page_link_all . '">'. $display_all_text .'</a>' ;
				}
			}

			if ($quick_type == 3) {
				$output .= '<ol class="contentlist">' ;
				$title_number = 0 ;
				
				for ( $i = 1; $i < ($numpages+1); $i = $i + 1 ) {
					$pagename = $pagetitles[$i-1];				
					
					if ($page == $i && !$allpage_link) {
						$output .= '<li><span class="contentlist" >';
						if ($title_number == 0) $output.= $pagename ;
						if ($title_number == 1) $output.= $pagename .' (' .$i.'/'.$numpages.')';
						if ($title_number == 2) $output.= $i .'. ' . $pagename ;					
						$output .= '</span></li>';
					}
					else {
						if ( 1 == $i ) $output .='<li><a class="contentlist" href="' . get_permalink().'"' ;
						else $output .='<li><a class="contentlist" href="' . untrailingslashit(get_permalink()) . $page_link_type . $i. $slash_yes . '"' ;
					
						if (empty($pagename)) $output .= '>Page '.$i;
						else {
							$output .= '>';
							if ($title_number == 0) $output.= $pagename ;
							if ($title_number == 1) $output.= $pagename .' (' .$i.'/'.$numpages.')';
							if ($title_number == 2) $output.= $i .'. ' . $pagename ;
						}
						$output .='</a></li>';
					}	
				}
				if ($display_all) {
						if ($allpage_link) $output .= '<li class="contentlistall">' . $display_all_text .'</li>';
						else $output .= '<li class="contentlistall"><a href="'. untrailingslashit(get_permalink()) . $page_link_all . '">' . $display_all_text . '</a></li>' ;
					}	
					
				$output .= '</ol>';
			}		
			
			if ($page < $numpages && !$allpage_link) {
				if ($nav_type == 2) $output .= '<a class="contentjumplink" href="' . untrailingslashit(get_permalink()) . $page_link_type. $nextlink . $slash_yes . '" >'.$nextpagelink.'</a>';
				if ($nav_type == 1) $output .= '<a class="contentjumptitle" href="' . untrailingslashit(get_permalink()) . $page_link_type . $nextlink . $slash_yes . '" >'.$nexttitle.'</a>';
				}	
			else {
				if ($nav_type == 2) $output .= '<span class="contentjumplink" >'.$nextpagelink.'</span>';
				if ($nav_type == 1) $output .= '<span class="contentjumptitle" >'.$lastpagetext.'</span>';
			}	
			
	if ($quick_type == 1) $output .= '</form>' ;	
	
	$output .= $after;
	
	return $output ;
	}
}

// Init Plugin

add_filter('the_content', 'allpage_show', 0);
function allpage_show($content) {
	global $multipage, $page, $posts, $numpages; 
	
	$all_page = get_query_var('all');
		
	if ($multipage && $all_page == '1' ) {
		
		$ta_multipage = get_option("ta_multipage");
		if ($ta_multipage['seperator'] == '0') $content = $posts[0]->post_content;
		if ($ta_multipage['seperator'] == '1') {
			$pagetitlestring = '/<!--pagetitle:(.*?)-->/';
			preg_match_all($pagetitlestring, $posts[0]->post_content, $titlesarray, PREG_PATTERN_ORDER);
			$pagetitles = $titlesarray[1];
			$content = '<p><h2 style="text-align:center">' . $pagetitles[0] . '</h2></p>' . $posts[0]->post_content;

			for ( $i = 1; $i < ($numpages+1); $i = $i + 1 ) {
				$content = preg_replace('/<!--nextpage-->/', '<p><h2 style="text-align:center">' . $pagetitles[$i] . '</h2></p>', $content, 1);
				}
		}
		if ($ta_multipage['seperator'] == '2') {
			$code = stripslashes($ta_multipage['seperator_code']);
			$content = preg_replace('/<!--nextpage-->/', '<div align=center>' . $code . '</div>', $posts[0]->post_content);
		}
	}
	return $content;
}

add_action('init', 'allpage_permalink', -1);
function allpage_permalink() {
	global $wp_rewrite;
	$wp_rewrite->add_endpoint("all", EP_ALL);
	$wp_rewrite->flush_rules();
	
	// set intitial insert priority
	if (! get_option("ta_multipage_priority")) update_option("ta_multipage_priority", 99);
}

add_filter('query_vars', 'AllPageEndpointQueryVarsFilter');
function AllPageEndpointQueryVarsFilter($vars){
	$vars[] = 'all';
	return $vars; 
}

// TinyMCE stuff

// init process for button control

function my_refresh_mce($ver) {
   $ver += 3; // or $ver .= 3; or ++$ver; etc.
   return $ver;
}
add_filter('tiny_mce_version', 'my_refresh_mce');

function multipage_addbuttons() {
 	// Check if WordPress 2.5+ (TinyMCE 3.x)
	global $wp_db_version;
	if ( $wp_db_version > 6124 ) {
   	// Add only in Rich Editor mode
    add_filter("mce_external_plugins", "add_multipagebuttons_plugin");
   	add_filter('mce_buttons', 'register_multipagebuttons'); }
}
 
function register_multipagebuttons($buttons) {
   	array_push($buttons, "separator", "nextpage", "pagetitle");
   	return $buttons;
}
 
// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function add_multipagebuttons_plugin($plugin_array) {
	$plugin_array['multipagebuttons'] = get_option( 'siteurl' ) . '/wp-content/plugins/multi-page-toolkit/buttons/editor_plugin.js';
	return $plugin_array;
}

add_action('init', 'multipage_addbuttons');

// Options Page

add_filter('the_content', 'TA_multi_toolkit_auto', get_option("ta_multipage_priority"));	
function TA_multi_toolkit_auto($content){
	$ta_multipage = get_option("ta_multipage");
	if (! is_single() && ! is_page()) return $content;

	$output1 = TA_navigation(1) ;
	$output2 = TA_navigation(2) ;	
	
	$output1 = '<p style="text-align:' . $ta_multipage['mp1_div_align'] . '">' . $output1 . '</p>';
	$output2 = '<p style="text-align:' . $ta_multipage['mp2_div_align'] . '">' . $output2 . '</p>';

	if ( is_page() ) {
		if ( $ta_multipage['mp1_insert_pages'] == False) $output1 = '' ;
		if ( $ta_multipage['mp2_insert_pages'] == False) $output2 = '' ;
	}
	
	if ( $ta_multipage["mp2_insert_top"] == 'True' ) $content = $output2 . $content ;
	if ( $ta_multipage["mp1_insert_top"] == 'True' ) $content = $output1 . $content ;
	if ( $ta_multipage["mp1_insert_bottom"] == 'True' ) $content = $content . $output1;
	if ( $ta_multipage["mp2_insert_bottom"] == 'True' ) $content = $content . $output2;	
	return $content;
}

add_action('admin_menu', 'ta_multipage_add_options_page');
function ta_multipage_add_options_page() {
	if (function_exists('add_options_page')) {
		add_options_page( __('Multipage Toolkit','multipage_toolkit'), __('Multipage Toolkit','multipage_toolkit'), 8, basename(__FILE__), 'ta_multipage_add_options_subpanel');
	}
}

function ta_multipage_add_options_subpanel() {
	if($_POST["ta_multipage_Submit"]){
		$message = "Multipage Toolkit Settings Updated";
	
		$ta_multipage_saved = get_option("ta_multipage");
		
		update_option("ta_multipage_priority",$_POST['priority']);
		
		$ta_multipage = array (
			'mp1_before' 			=> $_POST['mp1_before'],
			'mp1_after'				=> $_POST['mp1_after'],
			'mp1_title_number'		=> $_POST['mp1_title_number'],
			'mp1_quick_type'		=> $_POST['mp1_quick_type'],
			'mp1_nav_type'			=> $_POST['mp1_nav_type'],
			'mp1_nav_number'		=> $_POST['mp1_nav_number'],
			'mp1_previouspagelink'	=> $_POST['mp1_previouspagelink'],
			'mp1_nextpagelink'		=> $_POST['mp1_nextpagelink'],
			'mp1_firstpagetext'		=> $_POST['mp1_firstpagetext'],
			'mp1_lastpagetext'		=> $_POST['mp1_lastpagetext'],
			'mp1_display_all'		=> $_POST['mp1_display_all'],
			'mp1_display_all_text'	=> $_POST['mp1_display_all_text'],			
			'mp1_div_align'			=> $_POST['mp1_div_align'],
			'mp1_insert_top'		=> $_POST['mp1_insert_top'],
			'mp1_insert_bottom'		=> $_POST['mp1_insert_bottom'],
			'mp1_insert_pages'		=> $_POST['mp1_insert_pages'],
			'mp2_before' 			=> $_POST['mp2_before'],
			'mp2_after'				=> $_POST['mp2_after'],
			'mp2_title_number'		=> $_POST['mp2_title_number'],
			'mp2_quick_type'		=> $_POST['mp2_quick_type'],
			'mp2_nav_type'			=> $_POST['mp2_nav_type'],
			'mp2_nav_number'		=> $_POST['mp2_nav_number'],
			'mp2_previouspagelink'	=> $_POST['mp2_previouspagelink'],
			'mp2_nextpagelink'		=> $_POST['mp2_nextpagelink'],
			'mp2_firstpagetext'		=> $_POST['mp2_firstpagetext'],
			'mp2_lastpagetext'		=> $_POST['mp2_lastpagetext'],
			'mp2_display_all'		=> $_POST['mp2_display_all'],
			'mp2_display_all_text'	=> $_POST['mp2_display_all_text'],	
			'mp2_div_align'			=> $_POST['mp2_div_align'],
			'mp2_insert_top'		=> $_POST['mp2_insert_top'],
			'mp2_insert_bottom'		=> $_POST['mp2_insert_bottom'],
			'mp2_insert_pages'		=> $_POST['mp2_insert_pages'],
			'seperator'				=> $_POST['seperator'],
			'seperator_code'		=> $_POST['seperator_code']
		);

		if ($ta_multipage_saved != $ta_multipage)
			if(!update_option("ta_multipage",$ta_multipage))
				$message = "Update Failed";
		
		echo '<div id="message" class="updated fade"><p>'.$message.'.</p></div>';
	}
	
	$ta_multipage = get_option("ta_multipage");
	
	// Set defaults
	if ($ta_multipage == '' OR $_POST["ta_multipage_Reset"]) {
		$ta_multipage = array (
			'mp1_before' 			=> '',
			'mp1_after'				=> '',
			'mp1_title_number'		=> 2,
			'mp1_quick_type'		=> 1,
			'mp1_nav_type'			=> 2,
			'mp1_nav_number'		=> 'True',
			'mp1_previouspagelink'	=> '&laquo;',
			'mp1_nextpagelink'		=> '&raquo;',
			'mp1_firstpagetext'		=> 'On First Page',
			'mp1_lastpagetext'		=> 'On Last Page',
			'mp1_display_all'		=> 'True',
			'mp1_display_all_text'	=> 'View All',			
			'mp1_div_align'			=> 'center',
			'mp1_insert_top'		=> 'False',
			'mp1_insert_bottom'		=> 'False',
			'mp1_insert_pages'		=> 'False',
			'mp2_before' 			=> 'Page :',
			'mp2_after'				=> '',
			'mp2_title_number'		=> 2,
			'mp2_quick_type'		=> 2,
			'mp2_nav_type'			=> 0,
			'mp2_nav_number'		=> 'True',
			'mp2_previouspagelink'	=> '&laquo;',
			'mp2_nextpagelink'		=> '&raquo;',
			'mp2_firstpagetext'		=> 'On First Page',
			'mp2_lastpagetext'		=> 'On Last Page',
			'mp2_display_all'		=> 'True',
			'mp2_display_all_text'	=> 'ALL',
			'mp2_div_align'			=> 'center',
			'mp2_insert_top'		=> 'False',
			'mp2_insert_bottom'		=> 'False',
			'mp2_insert_pages'		=> 'False',
			'seperator'				=> '2',
			'seperator_code'		=> "--~~~~~~~~~~~~--"
		);
		update_option("ta_multipage", $ta_multipage);
		update_option("ta_multipage_priority", 99);
	}

	// removes slahes to display code correctly in textarea
	
?>
<div class="wrap">
	<h2 id="write-post"><?php _e("Multipage Toolkit Auto Insert Options",'TA_multi_toolkit');?></h2>
	<table><tr>
    	<td><?php _e("Tarkan Akdam's Multi-page toolkit creates pagetitles for multipage posts & pages with highly configurable navigation and quickjump pagination features, ultimate replacement for the builtin wp_link_pages() function without the code. ",'TA_multi_toolkit');?>
    More information can be found at <a href="http://www.tarkan.info/tag/multi-page" target="_blank">http://www.tarkan.info</a> - Please consider making a donation if you find this plugin useful. It helps pay for my hosting.
    <br/>
    <strong>It is recommended to reset settings after updating the Multi-page Toolkit.</strong></td>
    	<td><form action="https://www.paypal.com/cgi-bin/webscr" accept-charset="UNKNOWN" enctype="application/x-www-form-urlencoded" method="post">
<input maxlength="2147483647" name="cmd" size="20" type="hidden" value="_s-xclick" />
<input alt="PayPal - The safer, easier way to pay online." maxlength="2147483647" name="submit" size="20" src="https://www.paypal.com/en_GB/i/btn/btn_donateCC_LG.gif" type="image" /> <img src="https://www.paypal.com/en_GB/i/scr/pixel.gif" border="0" alt="" width="1" height="1" /><br />
<input maxlength="2147483647" name="encrypted" size="20" type="hidden" value="-----BEGIN PKCS7-----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-----END PKCS7-----" /></form>
		</td>
    </tr></table>
<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo basename(__FILE__); ?>">
<div style="width: 49%; float:left">
<table class="form-table">
	<tr>
		<th colspan="2"><?php _e("First Instance",'TA_multi_toolkit');?></th> 
    </tr>
	<tr>
    	<th><?php _e("Auto Insert:",'TA_multi_toolkit');?></th>
    	<td>
        <label>
        <?php	if ( $ta_multipage["mp1_insert_top"] == 'True' ) {
				echo '<input name="mp1_insert_top" type="checkbox" value="True" checked>';
				} else {
				echo '<input name="mp1_insert_top" type="checkbox" value="True">'; }
		?>
		<?php _e(" Top",'TA_multi_toolkit');?>
		&nbsp;
        <?php	if ( $ta_multipage["mp1_insert_bottom"] == 'True' ) {
				echo '<input name="mp1_insert_bottom" type="checkbox" value="True" checked>';
				} else {
				echo '<input name="mp1_insert_bottom" type="checkbox" value="True">'; }
		?>
		<?php _e(" Bottom",'TA_multi_toolkit');?>
        &nbsp;
        <?php	if ( $ta_multipage["mp1_insert_pages"] == 'True' ) {
				echo '<input name="mp1_insert_pages" type="checkbox" value="True" checked>';
				} else {
				echo '<input name="mp1_insert_pages" type="checkbox" value="True">'; }
		?>
		<?php _e(" Pages",'TA_multi_toolkit');?>
		</label>
        </td>
	</tr>
    <tr>
    	<th><?php _e("Manual Insertion:",'TA_multi_toolkit');?></th>
        <td>Use the following code within your theme files
        <br/>&lt;?php TA_content_jump(1); ?&gt;
        </td>
	</tr>
    <tr>
    	<th><?php _e("Display ALL Link:",'TA_multi_toolkit');?></th>
    	<td>
        <?php	if ( $ta_multipage["mp1_display_all"] == 'True' ) {
				echo '<input name="mp1_display_all" type="checkbox" value="True" checked>';
				} else {
				echo '<input name="mp1_display_all" type="checkbox" value="True">'; }
		?>
        <?php _e("Display multipage post on a single page",'TA_multi_toolkit');?>
        <br />
        <input type="text" name="mp1_display_all_text" value="<?php echo(stripslashes($ta_multipage["mp1_display_all_text"])); ?>" />
        <?php _e(" Link title",'TA_multi_toolkit');?>
        </td>
    </tr>
	<tr>
    	<th><?php _e("Alignment:",'TA_multi_toolkit'); ?></th>
        <td>
		<?php $div_align = $ta_multipage["mp1_div_align"]; ?>
		<select name="mp1_div_align" >
		<option value="left" <?php if($div_align == 'left') echo 'selected' ?> ><?php _e("Left",'TA_multi_toolkit'); ?></option>
		<option value="center" <?php if($div_align == 'center') echo 'selected' ?>><?php _e("Center",'TA_multi_toolkit'); ?></option>
		<option value="right" <?php if($div_align == 'right') echo 'selected' ?>><?php _e("Right",'TA_multi_toolkit'); ?></option>
        </select>
		</td>
    </tr>
	<tr>
    	<th><?php _e("Before/After Text:",'TA_multi_toolkit'); ?></th>
        <td>
        <label><input type="text" name="mp1_before" value="<?php echo(stripslashes($ta_multipage["mp1_before"])); ?>" />
        <?php _e(" Before",'TA_multi_toolkit');?></label>
        <br />
        <label><input type="text" name="mp1_after" value="<?php echo(stripslashes($ta_multipage["mp1_after"])); ?>" />
        <?php _e(" After",'TA_multi_toolkit');?></label>
        </td>
	</tr>
	<tr>
    	<th><?php _e("Next/Previous:",'TA_multi_toolkit'); ?></th>
        <td>
        <label><input type="text" name="mp1_previouspagelink" value="<?php echo(stripslashes($ta_multipage["mp1_previouspagelink"])); ?>" />
        <?php _e(" Previous",'TA_multi_toolkit');?></label>
        <br />
        <label><input type="text" name="mp1_nextpagelink" value="<?php echo(stripslashes($ta_multipage["mp1_nextpagelink"])); ?>" />
        <?php _e(" Next",'TA_multi_toolkit');?></label>
        </td>
	</tr>
	<tr>
    	<th><?php _e("First/Last Page Text:",'TA_multi_toolkit'); ?></th>
        <td>
        <label><input type="text" name="mp1_firstpagetext" value="<?php echo(stripslashes($ta_multipage["mp1_firstpagetext"])); ?>" />
        <?php _e(" First Page",'TA_multi_toolkit');?></label>
        <br />
        <label><input type="text" name="mp1_lastpagetext" value="<?php echo(stripslashes($ta_multipage["mp1_lastpagetext"])); ?>" />
        <?php _e(" Last Page",'TA_multi_toolkit');?></label>
        </td>
	</tr>
	<tr>
    	<th><?php _e("Quick Jump Type:",'TA_multi_toolkit'); ?></th>
        <td>
		<?php $div_align = $ta_multipage["mp1_quick_type"]; ?>
		<select name="mp1_quick_type" >
		<option value="0" <?php if($div_align == '0') echo 'selected' ?> ><?php _e("Disabled",'TA_multi_toolkit'); ?></option>
		<option value="1" <?php if($div_align == '1') echo 'selected' ?>><?php _e("Drop Down List",'TA_multi_toolkit'); ?></option>
		<option value="2" <?php if($div_align == '2') echo 'selected' ?>><?php _e("Page Numbers",'TA_multi_toolkit'); ?></option>
		<option value="3" <?php if($div_align == '3') echo 'selected' ?>><?php _e("List Menu",'TA_multi_toolkit'); ?></option>
        </select>
		</td>
    </tr>
	<tr>
    	<th><?php _e("Navigation Type:",'TA_multi_toolkit'); ?></th>
        <td>
		<?php $div_align = $ta_multipage["mp1_nav_type"]; ?>
		<select name="mp1_nav_type" >
		<option value="0" <?php if($div_align == '0') echo 'selected' ?> ><?php _e("Disabled",'TA_multi_toolkit'); ?></option>
		<option value="1" <?php if($div_align == '1') echo 'selected' ?>><?php _e("Page Titles",'TA_multi_toolkit'); ?></option>
		<option value="2" <?php if($div_align == '2') echo 'selected' ?>><?php _e("Next/Previous Links",'TA_multi_toolkit'); ?></option>
        </select>
		</td>
    </tr>
    <tr>
    	<th><?php _e("Navigation Number:",'TA_multi_toolkit');?></th>
    	<td>
        <?php	if ( $ta_multipage["mp1_nav_number"] == 'True' ) {
		echo '<input name="mp1_nav_number" type="checkbox" value="True" checked>';
		} else {
		echo '<input name="mp1_nav_number" type="checkbox" value="True">'; }
		?>
		<?php _e("Valid when Navigation Type set to Page Titles",'TA_multi_toolkit'); ?>
        </td>
    </tr>
	<tr>
    	<th><?php _e("PageTitle Number:",'TA_multi_toolkit'); ?></th>
        <td>
		<?php $div_align = $ta_multipage["mp1_title_number"]; ?>
		<select name="mp1_title_number" >
		<option value="0" <?php if($div_align == '0') echo 'selected' ?> ><?php _e("No Number",'TA_multi_toolkit'); ?></option>
		<option value="1" <?php if($div_align == '1') echo 'selected' ?>><?php _e("PageTitle (1/3)",'TA_multi_toolkit'); ?></option>
		<option value="2" <?php if($div_align == '2') echo 'selected' ?>><?php _e("1. PageTitle",'TA_multi_toolkit'); ?></option>
        </select>
		</td>
    </tr>
</table>
</div>
<div style="width: 49%; float:right">
<table class="form-table">
	<tr>
		<th colspan="2"><?php _e("Second Instance",'TA_multi_toolkit');?></th> 
    </tr>
	<tr>
    	<th><?php _e("Auto Insert:",'TA_multi_toolkit');?></th>
    	<td>
        <label>
        <?php	if ( $ta_multipage["mp2_insert_top"] == 'True' ) {
				echo '<input name="mp2_insert_top" type="checkbox" value="True" checked>';
				} else {
				echo '<input name="mp2_insert_top" type="checkbox" value="True">'; }
		?>
		<?php _e("Top",'TA_multi_toolkit');?>
		&nbsp;
        <?php	if ( $ta_multipage["mp2_insert_bottom"] == 'True' ) {
				echo '<input name="mp2_insert_bottom" type="checkbox" value="True" checked>';
				} else {
				echo '<input name="mp2_insert_bottom" type="checkbox" value="True">'; }
		?>
		<?php _e("Bottom",'TA_multi_toolkit');?>
        &nbsp;
        <?php	if ( $ta_multipage["mp2_insert_pages"] == 'True' ) {
				echo '<input name="mp2_insert_pages" type="checkbox" value="True" checked>';
				} else {
				echo '<input name="mp2_insert_pages" type="checkbox" value="True">'; }
		?>
		<?php _e(" Pages",'TA_multi_toolkit');?>
		</label>
        </td>
	</tr>
    <tr>
    	<th><?php _e("Manual Insertion:",'TA_multi_toolkit');?></th>
        <td>Use the following code within your theme files
        <br/>&lt;?php TA_content_jump(2); ?&gt;
        </td>
	</tr>
    <tr>
    	<th><?php _e("Display ALL Link:",'TA_multi_toolkit');?></th>
    	<td>
        <?php	if ( $ta_multipage["mp2_display_all"] == 'True' ) {
				echo '<input name="mp2_display_all" type="checkbox" value="True" checked>';
				} else {
				echo '<input name="mp2_display_all" type="checkbox" value="True">'; }
		?>
        <?php _e("Display multipage post on a single page",'TA_multi_toolkit');?>
        <br />
        <input type="text" name="mp2_display_all_text" value="<?php echo(stripslashes($ta_multipage["mp2_display_all_text"])); ?>" />
        <?php _e(" Link title",'TA_multi_toolkit');?>
        </td>
    </tr>
	<tr>
    	<th><?php _e("Alignment:",'TA_multi_toolkit'); ?></th>
        <td>
		<?php $div_align = $ta_multipage["mp2_div_align"]; ?>
		<select name="mp2_div_align" >
		<option value="left" <?php if($div_align == 'left') echo 'selected' ?> ><?php _e("Left",'TA_multi_toolkit'); ?></option>
		<option value="center" <?php if($div_align == 'center') echo 'selected' ?>><?php _e("Center",'TA_multi_toolkit'); ?></option>
		<option value="right" <?php if($div_align == 'right') echo 'selected' ?>><?php _e("Right",'TA_multi_toolkit'); ?></option>
        </select>
		</td>
    </tr>
	<tr>
    	<th><?php _e("Before/After Text",'TA_multi_toolkit'); ?></th>
        <td>
        <label><input type="text" name="mp2_before" value="<?php echo(stripslashes($ta_multipage["mp2_before"])); ?>" />
        <?php _e(" Before",'TA_multi_toolkit');?></label>
        <br />
        <label><input type="text" name="mp2_after" value="<?php echo(stripslashes($ta_multipage["mp2_after"])); ?>" />
        <?php _e(" After",'TA_multi_toolkit');?></label>
        </td>
	</tr>
	<tr>
    	<th><?php _e("Next/Previous:",'TA_multi_toolkit'); ?></th>
        <td>
        <label><input type="text" name="mp2_previouspagelink" value="<?php echo(stripslashes($ta_multipage["mp2_previouspagelink"])); ?>" />
        <?php _e(" Previous",'TA_multi_toolkit');?></label>
        <br />
        <label><input type="text" name="mp2_nextpagelink" value="<?php echo(stripslashes($ta_multipage["mp2_nextpagelink"])); ?>" />
        <?php _e(" Next",'TA_multi_toolkit');?></label>
        </td>
	</tr>
	<tr>
    	<th><?php _e("First/Last Page Text:",'TA_multi_toolkit'); ?></th>
        <td>
        <label><input type="text" name="mp2_firstpagetext" value="<?php echo(stripslashes($ta_multipage["mp2_firstpagetext"])); ?>" />
        <?php _e(" First Page",'TA_multi_toolkit');?></label>
        <br />
        <label><input type="text" name="mp2_lastpagetext" value="<?php echo(stripslashes($ta_multipage["mp2_lastpagetext"])); ?>" />
        <?php _e(" Last Page",'TA_multi_toolkit');?></label>
        </td>
	</tr>
	<tr>
    	<th><?php _e("Quick Jump Type:",'TA_multi_toolkit'); ?></th>
        <td>
		<?php $div_align = $ta_multipage["mp2_quick_type"]; ?>
		<select name="mp2_quick_type" >
		<option value="0" <?php if($div_align == '0') echo 'selected' ?> ><?php _e("Disabled",'TA_multi_toolkit'); ?></option>
		<option value="1" <?php if($div_align == '1') echo 'selected' ?>><?php _e("Drop Down List",'TA_multi_toolkit'); ?></option>
		<option value="2" <?php if($div_align == '2') echo 'selected' ?>><?php _e("Page Numbers",'TA_multi_toolkit'); ?></option>
		<option value="3" <?php if($div_align == '3') echo 'selected' ?>><?php _e("List Menu",'TA_multi_toolkit'); ?></option>
        </select>
		</td>
    </tr>
	<tr>
    	<th><?php _e("Navigation Type:",'TA_multi_toolkit'); ?></th>
        <td>
		<?php $div_align = $ta_multipage["mp2_nav_type"]; ?>
		<select name="mp2_nav_type" >
		<option value="0" <?php if($div_align == '0') echo 'selected' ?> ><?php _e("Disabled",'TA_multi_toolkit'); ?></option>
		<option value="1" <?php if($div_align == '1') echo 'selected' ?>><?php _e("Page Titles",'TA_multi_toolkit'); ?></option>
		<option value="2" <?php if($div_align == '2') echo 'selected' ?>><?php _e("Next/Previous Links",'TA_multi_toolkit'); ?></option>
        </select>
		</td>
    </tr>
    <tr>
    	<th><?php _e("Navigation Number:",'TA_multi_toolkit');?></th>
    	<td>
        <?php	if ( $ta_multipage["mp2_nav_number"] == 'True' ) {
		echo '<input name="mp2_nav_number" type="checkbox" value="True" checked>';
		} else {
		echo '<input name="mp2_nav_number" type="checkbox" value="True">'; }
		?>
        <?php _e("Valid when Navigation Type set to Page Titles",'TA_multi_toolkit'); ?>
        </td>
    </tr>
	<tr>
    	<th><?php _e("PageTitle Number:",'TA_multi_toolkit'); ?></th>
        <td>
		<?php $div_align = $ta_multipage["mp2_title_number"]; ?>
		<select name="mp2_title_number" >
		<option value="0" <?php if($div_align == '0') echo 'selected' ?> ><?php _e("No Number",'TA_multi_toolkit'); ?></option>
		<option value="1" <?php if($div_align == '1') echo 'selected' ?>><?php _e("PageTitle (1/3)",'TA_multi_toolkit'); ?></option>
		<option value="2" <?php if($div_align == '2') echo 'selected' ?>><?php _e("1. PageTitle",'TA_multi_toolkit'); ?></option>
        </select>
		</td>
    </tr>
</table>
</div>
<div style="clear:both;">
<table class="form-table">
	<tr>
    	<th><?php _e("Page Seperator on ALL PAGE view:",'TA_multi_toolkit'); ?></th>
        <td valign="top">
		<?php $seperator = $ta_multipage["seperator"]; ?>
		<select name="seperator" >
		<option value="0" <?php if($seperator == '0') echo 'selected' ?>><?php _e("No Seperator",'TA_multi_toolkit'); ?></option>
		<option value="1" <?php if($seperator == '1') echo 'selected' ?>><?php _e("Use Page Titles",'TA_multi_toolkit'); ?></option>
		<option value="2" <?php if($seperator == '2') echo 'selected' ?>><?php _e("Custom Code >>>",'TA_multi_toolkit'); ?></option>
        </select>
		</td>
        <th><?php _e("HTML/CODE (e.g. javascript) to use as seperator (such as code for advertising) :",'TA_multi_toolkit');?></th>
        <td>
        <textarea name="seperator_code" cols="60" rows="8"><?php echo(stripslashes($ta_multipage['seperator_code'])); ?></textarea>
        </td>
    </tr>
</table>
</div>
<div style="clear:both;">
<table class="form-table">
	<tr class="submit">
    	<td><input type="submit" value="<?php _e("Update Options &raquo;",'TA_multi_toolkit');?>" name="ta_multipage_Submit" /></td>
        <td><input type="submit" value="<?php _e("Reset To Default &raquo;",'TA_multi_toolkit');?>" name="ta_multipage_Reset" /></td>
    	<th><?php _e("Adjust Insert Priority:",'TA_multi_toolkit'); ?></th>
        <td><input type="text" name="priority" value="<?php echo get_option('ta_multipage_priority'); ?>" />
        <?php _e("Adjust where Multipage Toolkit appears in relation to other plugins by changing the priority (1 to 99)",'TA_multi_toolkit');?>
        </td>
	</tr>
</table>
</div>            
</form>
</div>

<?php }?>

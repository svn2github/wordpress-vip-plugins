=== Plugin Name ===
Contributors: Tarkan Akdam
Donate link: http://www.tarkan.info/20080106/tutorials/wordpress-plugin-multipage-tool-kit/
Tags: posts, link_pages, multi-page, quicktag, navigation, 2.6.1, paginate, pagination, titles, heading, content menu, content table, page titles, seo
Requires at least: 2.3
Tested up to: 2.6.1
Stable tag: 2.6

Multi-page toolkit create titles for pages and configurable navigation features. Single (all) page view option with custom seperator (great for adverts). Easy to use. Integrated with the Visual Editor. No Code necessary - unless you want to!

== Description ==

Multi-page toolkit

This plugin is the ultimate companion for people who use the multi-page capabilities of wordpress. Easy to use fully integrated in to the wordpress visual editor (tinyMCE) (Wordpress 2.5 and above) and simple to use options page to create great navigation links. No coding required !!!!

Using the nextpage button in the editor or the `<!--nextpage-->` quicktag you can create multi-page posts, and this plugin gives you three functions that extend this functionality even more!!

* Page Title button or Quicktag `<!--pagetitle:-->`

Create a title for each page in your multi-page post.

* Navigation Options

This function give you several pagination options choose from simply previous and next links, page title links and numbered links.
Quick jump options include dropdown menu or a list menu using page numbers or page titles. 

New option to display all pages on a single page (ALL page link).
New option to have a custom seperator between pages on the single page view, great for adding advertising code or just to make it look pretty.
New option to display navigation on multipage pages as well as posts.

* TA_display_pages

With this function you can quickly and easily display how many pages a particular post has on your index page. Choose from 3 formats ( 1 of 2 , Page 1, 3 pages)

== Installation ==

1. Unzip the file archive and put the directory into your "plugins" folder (/wp-content/plugins/)
2. Activate the plugin
3. Adjust the auto insert options in the settings page (admin / settings / Multipage Toolkit)
4. If you have upgraded from a previous version I suggest reseting to default in the options page

** PLEASE NOTE **
The function call names and parameter settings have been changed since version 2.1. Please check the code you use in your theme if you have added navigation jumps directly.

That is all there is to it - for useage instructions please read the FAQ.

For hardcore coders and people who prefer to call functions from their themes, below I have listed all the functions that the multipage toolkit offers with all the available options.

** To use TA_display_pages **

Place this in your template file (e.g. index.php)

	`<?php if(function_exists('TA_display_pages')) { TA_display_pages(); } ?>`

Parameters (defaults shown)

	$firsttext = ' Page '
	$lasttext = ' '
	$midtext = ' of ' (only used when display_type is all)
	$display_type = 'all' (total , current, all)

Examples
	
	Default
				Page 1 of 3
				
	TA_display_pages('(',' pages)','','total');
				
				(3 pages)	
	
	TA_display_pages('(you are on page ',' now)','','current');
	
				(you are on page 1 now)
	
				
** To use TA_content_jump **
	
Place this in your template file used to display posts (normally single.php) in the location where you want it to appear. You can call the parameters set from the options page.

To display the options from the 1st instance settings use :-	
	`<?php TA_content_jump(1); ?>`
	
To display the options from the 2nd instance settings use :-	
	`<?php TA_content_jump(2); ?>`
	

The above method is the prefered method of manual insertion, but you can still set the parameters directly in the function call if you prefer, they are set out below. Be careful the function call name is different.

TA_content_jump($before = '<p>', $after = '</p>', $title_number = 2, $quick_type = 1, $nav_type = 2, $nav_number = TRUE, $previouspagelink = '&laquo;', $nextpagelink = '&raquo;', $firstpagetext = 'On First Page', $lastpagetext = 'On Last Page', $display_all = TRUE, $display_all_text = 'View All')

Example
	`<?php TA_content_jump('Page :','', 2, 2, 0, False, '&laquo;', '&raquo;'); ?>`

Parameters (defaults shown)
	
	$before = '<p>'
	$after = '</p>'
	$title_number = 2 	(used when quick_type set to 2, adds page number to page title
						0 = no number, 1 = Page Title (1/3), 2 =  1. Page Title )	
						
	$quick_type = 1		(quick jump navigation type 0 = disable ,1 = Drop Down List ,2 = page number links ,3 = list menu) 
	
	$nav_type = 2		(navigation type 0 = disable, 1 = use page titles as next or previous, 
						2 = use $previous/$nextpagelink as next or previous links)
						
	$nav_number = TRUE	(only used when nav_type = 1, if TRUE page titles preceeded by page number)
	
	$previouspagelink = '&laquo;'
	$nextpagelink = '&raquo;'
	
	$firstpagetext = 'On First Page' (text to display when on first or last page when using nav_type 1)
	$lastpagetext = 'On Last Page'
	
	$display_all = TRUE	(Display ALL page link on navigation)
	$display_all_text = 'View All' (ALL page link title / text)
	
	**NOTE** nav_type is switched to 2 when post has no page titles !!!
	

**CSS Styling**
	
The plugin display will follow your existing CSS styling
	
You can do more targeted styling by adding the following ID's in to your templates style.css file
	
	span.contentjumplink {	font-size: 2em; 
							color: #aaa; 
							vertical-align:middle; 
							font-weight: bold; 
							padding: 0 3px 0px 3px}
							
	a.contentjumplink {		font-size: 2em; 
							color: #25A; 
							vertical-align:middle; 
							font-weight: bold; 
							padding: 0 3px 0px 3px}
	
	a.contentjumpall {  }
	
	span.contentjumpall {   }	

	span.contentjumptitle { vertical-align: middle ; 
							color: #aaa; 
							font-weight: bold;
							border:1px #ddd solid ;
							border-top-color: #a7a7a7;
							padding: 3px 3px 3px 3px }
							
	a.contentjumptitle { 	vertical-align: middle;
							border:1px #ddd solid ; 
							border-top-color: #a7a7a7; 
							padding: 3px 3px 3px 3px}

	select.contentjumpddl { vertical-align: middle; 
							margin: 0px 0px 0px 0px ; 
							color: #25A;
							font-weight:bold; 
							font-family:Verdana, Arial, Helvetica, sans-serif;
							width: 160px }

	ol.contentlist { background-color:#f5f5f5; width: 20%; text-align:left; line-height: 3px; padding: 0px; }
	
	ol.contentlist li { padding: 0px; }
	
	span.contentlist { color: #aaa; font-weight: bold; }
	
	a.contentlist { padding: 0px; }
	
	li.contentlistall { }

	span.contentjumpnumber { 	vertical-align: middle ;
								color: #ccc; 
								font-weight: bold;
								border:1px #ddd solid ; 
								border-top-color: #a7a7a7; 
								background-color: #25a; 
								padding: 3px 3px 3px 3px }
								
	a.contentjumpnumber { 	vertical-align: middle; 
							border:1px #ddd solid ; 
							border-top-color: #a7a7a7; 
							padding: 3px 3px 3px 3px}
							
	a.contentjumpnumber:hover { border-top-color: #25a; }
				
				
== Frequently Asked Questions ==

= To create pagetitles for your posts =

If you are using the visual editor, you will see two new buttons in the button bar. 

Click on Next Page to insert page breaks (nextpage).

Click on the page title button and in the popup - type in the required page title and click insert.
Icon will appear in the main editor window to show that the page title has been inserted. If you hover your mouse over the icon the page title will appear.
To edit the page title, select the page title icon in the main editor window and then click on page title button.

If you are not using the visual editor or are switched to CODE view
	
type in the following tag to create page breaks

	`<!--nextpage-->`

within each page add (including the starting page) to create page title

	`<!--pagetitle:TYPE IN PAGE TITLE HERE-->`
	
= Visual Editor does not show the new buttons =

The buttons will only work in Wordpress 2.5 and above only.

Please upgrade Wordpress.

= What is first and second instance on the options page mean? =

The different instances gives you the flexibilty to have two different navigation methods in your posts. This gives you the freedom to have a quick jump using page numbers and another drop down menu navigation method.

Each instance of multipage toolkit can be inserted in to the top , bottom or top and bottom of your post, or you can choose to have the first instance displayed at the top of your post and the second instance at the bottom. You are totally free to choose!!!

= Display ALL link, what is that? =

Some people on the web do not like multipage posts and they prefer to read posts as one long page. By selecting this option Multipage toolkit inserts a ALL pages link in to your quick jump or navigation links. On your quick jump you will see something like (Page: 1 2 3 4 ALL).

= Adjust Insert Priority, what is that? =

The problem with auto inserting things in to your posts is that you cannot control where they appear in relation to other items that are inserted. By changing the priority you can change where the navigation links appear in relation to other plugins.

e.g. If you use a plugin to generate related posts, ideally you want your navigation links to appear before the related posts listing - so by changing the priority value you can have the multipage toolkit appear before your related posts.

= Is there anywhere I can see multipage toolkit in action? =

Yes of course, my website is one place (http://www.tarkan.info).

I have also setup an example page to showcase the different options :-
http://www.tarkan.info/archives/multipage/

== Screenshots ==

1. Example navigation methods with code required to create them.

== Change Log ==
* Version 2.6
	* Added auto-insert function for Pages
	* Checked compatible with Wordpress 2.6.1
	* Fixed options reseting to default when no page seperator selected
	* Fixed text entry boxes now accept html code without breaking
* Version 2.5
	* SVN broke the upload - so uploading again
* Version 2.4
	* Public release
	* Changed CSS style naming for the all page links

* Version 2.3 (internal release)
	* Added option to have custom page seperators (including javascript for adverts) on single (ALL) page display
	* Added function call to use options page settings from inside the theme files
	* Fixed badly coded string matching (Thanks to Andrei for the fix)
	* Fixed spelling error in button registration process (Thanks to Jonathan for spotting this)
	* Fixed maintain trailing slash consistency across site
		
* Version 2.2 (internal release)
	* Added ALL link Display Text Option
	* Added new CSS class for ALL link styling (contentjumpall , contentlistall)
	* Fixed bug where pagetitle followed by nextpage caused errors
	* Fixed tinymce js cache issues (hopefully..)
	* Fixed navigation for preview draft posts

* Version 2.1
	* Corrected Folder Naming Error

* Version 2.0 (28th May 2008)
	* Major update
	* Fully integrated with the Visual Editor (nextpage and pagetitle buttons added)(WP 2.5 and above only)
	* New auto insert options page (no more coding necessary)
	* Created view ALL pages link option
* Version 1.2 (20th March 2008)
	* Added a check for trailing slashs and permalink structure for paging
	* Cleaned up readme.txt for better formatting
	* Checked compatibility with WP 2.5rc1
* Version 1.1
	* Add new quickjump method - list menu / content table
* Version 1.0
	* Initial version
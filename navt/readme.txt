=== WordPress Navigation List Plugin NAVT ===
Contributors: gbellucci, et_ux
Donate link: http://atalatastudio.com
Tags: navigation, menu, breadcrumb, lists, pages, categories, links, navbar, widget, dropdown, avatars, gravatars, graphic links
Requires at least: 2.3
Tested up to: 2.5
Stable tag: 1.0.12

Create, organize and manage your WordPress menus and navigation lists by logically grouping your pages, categories, users via a drag'n drop interface.

== Description ==

The __WordPress Navigation Tool (NAVT)__ plugin is a powerful tool designed to provide you with complete control over the creation, styling and contents of your web site's navigation. The plugin gives you the ability to create unique site navigation from your pages, categories and users using a Drag 'n Drop Interface; arrange the items within a group in any arbitrary order. Navigation groups may be composed of any combination of pages, categories, Authors, (Editors, Contributors, Subscribers), internal/external links and list dividers.

= Plugin Features =

* Navigation items can be duplicated and may appear in more than one group. Each item (even if duplicated) can be independently configured.
* List item names (called a menu alias) can be set to a name that differs from the name used as the page title or the category name.
* Create navigation items to be displayed in one of the following format:
    1. Text only
    1. Text over graphics
    1. Text with side graphic
    1. Graphic only
* Group items can be constructed to appear as a hierarchy - parent/child relationships can be formed between all types of items.
* Navigation menus may be styled using NAVT provided CSS classes, standard Word Press classes, or NAVT will apply _user specified CSS classes_.
* Supports BreadCrumb navigation.
* Supports navigation trees. Clicking a parent navigation item on a page reveals all child navigation on the subsequently displayed page.
* Navigation menus can be displayed (or not displayed) on any combination of user selected posts, pages, home page, archives, 404, search pages.
* Theme integration options allow you to insert a navigation group anywhere in your theme (_without editing your theme_).
* Create navigation using HTML Selects. Create multiple selects in a single group by using dividers.
* Embed navigation lists inside your posts and/or pages.
* Privacy settings for all navigation items and entire groups allows you to hide navigation items in a menu if the user is not logged into your site.
* Supports Gravatars for user navigation items.
* Transparently supports Word Press widgets.
* Transparently supports K2 sidebar modules when used with the K2 theme.
* Built in help.
* Backup/Restore functionality.
* Compatible with the Word Press 2.3+ and WordPress MU
* Compatibility tested with IE6/IE7, Firefox, Opera (_all pc browsers only_, I don't have accesss to a MAC for testing).
* __NOT__ compatible with Safari


= Localization =

* English (en_US)
* German (de_DE)  by [Rico Neitzel](http://www.rizi-online.de "Rico Neitzel")
* Russian (ru_RU) by [Dmitriy Kostromin](http://www.blog.kostromin.ru "Dmitriy Kostromin")


  __If you'd like to contribute by offering your translating skills please contact me at greg @ gbellucci . us__

  For more information, help, etc. Visit the [NAVT Home Page](http://atalayastudio.com "navt home page")

= Change Log Information =

Plugin change information is located on the Installation page.


== Installation ==

_Classic WordPress and WordPress MU Plugin directory_

1. Download the plugin.
1. Unzip the file in the WordPress directory: `/wp-content/plugins/` - __The plugin must reside in its own directory.__
1. Activate the NAVT plugin from the Word Press plugin page.
1. After activating the plugin, go to the `Manage` menu and select the menu tab: __NAVT Lists__ to use the plugin.
1. NAVT requires the use of JavaScript (_it must be turned on in your browser_).

NAVT version 1.0.x+ will convert navigation groups created with previous versions to the new data format used by NAVT 1.0.x+. However, you should backup any current navigation groups using the NAVT plugin you have installed BEFORE installing and activating the new version of the plugin. NAVT backups are forward compatible with NAVT 1.0.x+

Click the ? (help) buttons provided on the NAVT List page to get help. More help is available in the [NAVT Home Page](http://atalayastudio.com "navt home page")

The `doc` directory contains a single page manual explaining the PHP interface function call syntax. The `modules` directory contains a document explaining how to install the sidebar module.


= Release Notes =

*__1.0.12 Release Candidate__ _(April 12, 2008)_

1. Update to Russian translation - Dmitriy Kostromin

*__1.0.11 Release Candidate__ _(April 10, 2008)_

1. Changed readme.txt and plugin header (still trying to force update notifications to work!)

*__1.0.9 Release Candidate__ _(April 8, 2008)_

1. Changed header in readme.txt to match plugin name to fix plugin update notification.
1. Update to German localization. - Rico Neitzel


*__1.0.8 Release Candidate__ _(April 6, 2008)_

1. Added Russian localization (__thanks to Dmitriy Kostromin for his help__)
1. Correction for escaping single quotations in link/alt strings (_thanks to Troy Thompson for the heads up_)


*__1.0.7 Release Candidate__ _(April 3, 2008)_

1. Added hierarchical representations to pages and categories in the asset select lists. Child page and child category names are displayed (in the Assets Panel) with the number of dash characters that corresponds to the child's relative postion - similar to the way they are displayed by Word Press. The relative position information is only for informational purposes and is not translated to represent the item's position in a navigation group.

1. Added sorting capability to pages and category assets. Radio buttons have been added to the category and page select lists that enable you to change the sort order. Pages and categories can be sorted by 'title/name' or by 'menu order'.

1. Added localized language code (I.E, en-US, de-DE, (contents of WPLANG value) ) browser name and browser version as additional NAVT menu classes. Browser names (firefox, msie, etc) are followed by the browser version as group classes. This is useful for creating CSS classes that are targetted for use with specific languages, browsers and browser versions. The German language for example, can sometimes contain more letters in an expression or sentence than English - applying a class that targets a specific language enables you to adjust container widths to prevent word wrapping.

1. Browser class names:
* firefox
* opera
* msie
* webtv
* netpositive
* mspie (MS Pocket Internet Explorer)
* galeon
* konqueror
* icab
* omniweb
* phoenix
* firebird
* mozilla (Mozilla Alpha/Beta Versions)
* amaya
* safari
* netscape

1. Versions information
* Versions numbers always begin with a 'v' followed by the verion number without the 'DOT' characters. Check by looking at the source code produced by the browser.


*__1.0.6a Release Candidate__ _(March 30, 2008)_

1. Corrections to the readme.txt : added screenshots.

*__1.0.6 Release Candidate__ _(March 30, 2008)_

1. Fix to text with side graphic output HTML.
1. Corrected radio button selection for user navigation item (user or default avatar, gravatar)
1. Fix to 'show on...' routines to correctly handle hide on selected pages and show on selected pages
1. Changes to navt widget and navt sbm - no html output is written if navt_getlist() returns an empty list.


*__1.0.5 Release Candidate__ _(March 29, 2008)_

1. Fix to correctly allow dashes (-) in a navigation group name.


*__1.0.4 Release Candidate__ _(March 27, 2008)_

1. Fix for missing end of anchor tag for user defined URIs


*__1.0.3 Release Candidate__ _(March 25, 2008)_

1. '#wpcontent select' height was changed in WP 2.5 RC1.1 - this caused NAVT selects to display incorrectly.
Set the NAVT css stylesheets to change the select height to auto.


*__1.0.2 Release Candidate__ _(March 23, 2008)_

1. Fixed the version information
1. Added backup your data reminder to the installation page


*__1.0.1 Release Candidate__ _(March 23, 2008)_

1. Major rewrite - Brand new interface, new options


== Frequently Asked Questions ==

= Does NAVT provide a widget or a K2 sidebar module? =

__Yes.__ 5 NAVT widgets are transparently added when you activate the NAVT Plugin. The NAVT sidebar module is also transparently added to K2SBM if you are using the K2 theme.

= How do I create horizontal menus at the top of my theme? =

NAVT enables you to use your own classes by entering the CSS class information into the group options dialogbox. The group options dialogbox is indicated by the _gear icon_ on the left side of any navigation group container. Write or obtain a CSS stylesheet that contains the classes for creating a horizontal menu (there are several sources available on the Internet). Integrate the CSS stylesheet with your theme style sheet. Note the style class names for the UL and LI tags that are used in the stylesheet and enter the names into the places provided under the group CSS options tab. __Be sure to select: Do not apply CSS classes and check the Apply the CSS information below to this navigation group__ . This will force NAVT to apply the classes you've entered to the navigation group. Add the navigation group to the theme by using a widget, use the theme integration tab or by adding the navt function call directory to your theme.

= How do I use NAVT Theme Integration? =

NAVT theme integration allows you to add a navigation group into your theme without editing the theme. It does this by using an XPATH expression and applying one of the available actions: _insert before_, _insert after_, _insert above_, etc. An XPATH statment is used to identify a specific location within your theme where you'd like to put the navigation group. XPATH expressions use a combination of CSS selector ids and CSS classes to target a specific location within your theme. For example, if you wanted to place a navigation group at a specific location in your sidebar, you would formulate the necessary XPATH expression. If your sidebar had the selector id #sidebar and you wanted a navigation group to appear at the bottom of the sidebar the XPATH expression would simply be: #sidebar and you would use the _insert bottom_ action.

CSS selector ids must begin with a __#__ symbol and classes must begin with a single __dot__. You can also target locations using expressions like ___#header div.main ul.menu__ This XPATH expression describes an unordered list with the class __menu__ that is contained within a div that has the class named __main__ that is contained with a container named __header__.

Here is a working example: to __replace the top menu in a K2 theme with a NAVT navigation group__:

Select the Theme Integration tab for the NAVT navigation group you want to use. Enter the following XPATH __#page #header ul.menu__ and use the action __Replace With__. The navigation group will replace the standard K2 horizontal menu across the top of the theme.

== Screenshots ==


1. Basic components of the NAVT administration page.
2. Items are created in the Asset Panel by clicking a item in the select box and then dragging the item to a navigation group.
3. Multiple copies of the same item can be created in the Asset Panel
4. Copies can be placed into multiple groups.

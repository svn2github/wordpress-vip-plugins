<?php 
/* Paged Comments config file */ 

// Enable paged comments on all POSTS?
// -----------------------------------
// Note: previous versions of this plugin made no distinction between posts and pages.
// As of version 2 (2006-04-07), this option has been split to all_posts and all_pages.
// Setting this to false will disable paged comments on posts by default.
// Individual posts can still enable paged comments by adding a custom
// field: 'paged_comments' with the value 'on'.
// Note: to disable paged comments completely, deactivate this plugin
// through the admin interface.
$paged_comments->all_posts = true;

// Enable paged comments on all PAGES?
// -----------------------------------
// Note: this option was introduced in version 2 (2006-04-07), see comments above.
// Setting this to false will disable paged comments on pages by default.
// Individual pages can still enable paged comments by adding a custom
// field: 'paged_comments' with the value 'on'.
// Note: to disable paged comments completely, deactivate this plugin
// through the admin interface.
$paged_comments->all_pages = true;

// Comments per page
// -----------------
// Page numbers will only be displayed when comments exceed this value
// Individual posts can override this value by adding a custom field with
// 'comments_per_page' as key.
$paged_comments->per_page = 50;

// Comment ordering
// ----------------
// 'ASC': earliest comments will be displayed first and page numbers increase from 1: 1,2,3,...x
// 'DESC': latest comments will be displayed first and page numbers will decrease from x: x,....3,2,1
// Note: ordering is implemented this way so new comments don't 
// displace older comments on a page.
// Individual posts can override this value by adding a custom field with
// 'comment_ordering' as key.
$paged_comments->ordering = 'DESC';

// Fill last comment page
// ---------------------------------------------------------------
// When enabled, the last comment-page will contain the maximum number of comments 
// allowed on a page (the max being the value specified for the per_page option).
// Note: with this enabled new comments will cause older comments to shift 
// down to the preceding page until page 1 is full. So if you value your comment
// permalinks, set this value to false.
$paged_comments->fill_last_page = false;

// Page range
// ----------
// Number of page numbers to show at one time.
// e.g. if there are 10 pages, current page is page 6 and page range is 5
// page numbers displayed will be: << 4 5 (6) 7 8 >>
$paged_comments->page_range = 11;

// Fancy URL
// ---------
// If you currently have a custom URI structure for permalinks -- see: 
// <http://codex.wordpress.org/Introduction_to_Blogging#Pretty_Permalinks> --
// enabling this will append /comment-page-x/ (where x is a page number) 
// to the end of the URLs for comment pages.
$paged_comments->fancy_url = false;

// Show-all comments option
// ------------------------
// If enabled, visitors will have the option of choosing to see all
// comments on one page (ie. not paged). A 'show all' link will be 
// displayed if this is enabled.
$paged_comments->show_all_option = true;

// Show-all comment ordering
// -------------------------
// Determines how comments are ordered when loaded on a single page.
// The default ascending ('ASC') order means comments will be ordered with
// the earliest comment displayed first. 'DESC' reverses this order. 
// The ordering specified here is used in the following circumstances:
// 1. When a user chooses the 'Show All' option to have all comments displayed at once.
// 2. When the total number of comments has not exceeded the per_page value (ie. when 
//    they're few enough to fit on a single comment page).
// In all other circumstances, the value specified in ordering is used.
$paged_comments->show_all_ordering = 'ASC';

// Default page
// ------------
// The default page is either 'first' (page 1), 'last', or 'auto' (determined
// by the comment ordering value). When comment ordering is set to ascending (ASC)
// the default comment page loaded is page 1 (showing the earliest comments).
// When comment ordering is set to descending (DESC) the default comment page
// is the last page (showing the latest comments). To override this behaviour
// set the value below to either 'first' or 'last'.
$paged_comments->default_page = 'auto';

?>
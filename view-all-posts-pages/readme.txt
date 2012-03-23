=== View All Post's Pages ===
Contributors: ethitter, thinkoomph
Donate link: http://www.thinkoomph.com/plugins-modules/view-all-posts-pages/
Tags: view all, pages, paged, paged post, multipage, single view, single page, wp_link_pages, nextpage, next page, quicktag
Requires at least: 3.2.1
Tested up to: 3.3
Stable tag: 0.4.1

Provides a "view all" (single page) option for content paged using WordPress' &lt;!--nextpage--&gt; Quicktag (multipage posts).
== Description ==

Provides a "view all" (single page) option for posts, pages, and custom post types paged using WordPress' <a href="http://codex.wordpress.org/Write_Post_SubPanel#Quicktags" target="_blank"><code>&lt;!--nextpage--&gt;</code> Quicktag</a> (multipage posts).

Your theme must use `wp_link_pages()` to generate post page links, or you must use either the automatic or manual link capabilities provided in the plugin.

**IMPORTANT**: There are certain plugins that may interfere with this plugin's functionality. See the **FAQ** for more information.

== Installation ==

1. Upload view-all-posts-pages.php to /wp-content/plugins/.
2. Activate plugin through the WordPress Plugins menu.
3. Navigate to Options > Permalinks and click *Save Changes* to update navigation.

== Frequently Asked Questions ==

= Links don't work =
First, navigate to Options > Permalinks in WP Admin, click *Save Changes*, and try again.

If clicking on a link takes you back to the post or page where the link appeared, see the **Known Plugin Conflicts** item below.

If, after reviewing the remaining FAQ, you are still experiencing problems, visit [http://www.thinkoomph.com/plugins-modules/view-all-posts-pages/](http://www.thinkoomph.com/plugins-modules/view-all-posts-pages/) and leave a comment detailing the problem.

= How do I add a link to my template? =
The function `vapp_the_link` will add a link to the full-content version of whatever page it appears on. This function accepts the following arguments:

* **$link_text**: Set to text that should appear for the link. Defaults to *View All*.
* **$class**: Specifies the CSS class for the link. Defaults to *vapp*.

= Known Plugin Conflicts =
This plugin is known to conflict with certain plugins, many pertaining to SEO and permalinks. Conflicting plugins include, but are not limited to, the following:

* **WordPress SEO by Yoast:** This plugin's `Permalink` options, particularly *Redirect attachment URL's to parent post URL* and *Redirect ugly URL's to clean permalinks. (Not recommended in many cases!)*, interfere with View All Post's Pages' ability to display full content. Both must be disabled, and the site's rewrite rules regenerated (by visiting Options > Permalinks and clicking *Save Changes*), for View All Post's Pages to function.

== Changelog ==

= 0.4.1 =
* Eliminate use of plugins_loaded action since plugin has no dependencies on other plugins. All code previously located in the associated method has been moved to the class's constructor. props danielbachhuber.

= 0.4 =
* Add filter to disable rewrite rules notice.
* Apply esc_html() to link text during output.
* Update code to conform to WordPress Coding Standards.

= 0.3 =
* Simplyify rewrite rule creation, resolving 404 errors in most cases.

= 0.2 =
* Change how wp_link_pages arguments are filtered to better support as-needed filtering.

= 0.1 =
* Intial release

== Upgrade Notice ==

= 0.4.1 =
Eliminates use of plugins_loaded action since plugin has no dependencies on other plugins. All code previously located in the associated method has been moved to the class's constructor. props danielbachhuber.

= 0.4 =
Adds filter to disable rewrite rules notice. Also applies esc_html() to link text when output since HTML isn't permitted in the link text.

= 0.3 =
Simplifies rewrite rules, resolving 404 errors in most cases.

= 0.2 =
Introduces helper function for wp_link_pages arguments and split filtering from plugin options.
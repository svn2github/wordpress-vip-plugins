=== Livefyre Realtime Comments ===
Contributors: Livefyre
Donate link: http://livefyre.com/
Tags: comments, widget, plugin, community, social, profile,
moderation, engagement, twitter, facebook, conversation
Requires at least: 2.8
Tested up to: 3.2.1
Stable tag: 3.12

Livefyre replaces your outdated comment section with a real-time
social conversation. Post comments live, tag friends from Facebook and
Twitter, and sync the conversation happening across the web to your
content.

== Description ==

Livefyre is a real-time comment solution that centralizes
conversations from around the social web back to your content, while
encouraging live engagement between users on your site.

The Livefyre best-of-breed model is the future for content publisher
communities, giving you the flexibility and constant, focused
innovation you need from your technology providers.

Livefyre offers key features like:
- Social Sync; pulling Facebook & Twitter comments into your content
in real-time.
- Tagging Friends from Facebook & Twitter; anyone leaving a comment
brings their entire social network with them to the conversation.
- Intelligent & Community Moderation; ability to moderate in real-time
thereby increasing the quality of the conversations without effecting
the quantity.
- SEO credit; Livefyre is Google crawl-able so you receive SEO credit
for all comments, including those originating from Facebook & Twitter.
- Real-time technology; built on XMPP chat technology for the fastest,
lightest weight conversations possible.

For more info check out [Livefyre's full feature
list](http://livefyre.com/features).

== Installation ==

Sign-in to your WordPress admin panel.
Click on Plugins, then click on Add New
Search for "Livefyre" and click "Install Now." Note: If you have an
existing comment plugin, deactivate it before activating Livefyre to
ensure proper functionality.
At the next screen, click Activate plugin
Click Confirm your blog configuration with Livefyre.com at the top of
the page. (This will redirect you away from your WP-Admin.)
Sign in or create a Livefyre account. You will be directed back to
WordPress where your import will complete.

All done!

Check out the FAQ for install here to [view a
video](http://support.livefyre.com/customer/portal/articles/22145-how-do-i-install-livefyre-on-wordpress-)

== Frequently Asked Questions ==

Visit the [Livefyre FAQ](http://support.livefyre.com) or e-mail us at
support@livefyre.com.

== Changelog ==
= 3.12 =
* Added a validator which tests the UTF cleaner before applying it.  If it fails to return the string that was passed in, we turn off the UTF cleaner.  This will fix the import process for a number of blogs where iconv is broken or other encoding issues exist in the target environment.
= 3.11 =
* Fixing an issue with postback parent comments not being associated - which caused replies to appear at the top level in the WordPress comments section when livefyre is turned off.
= 3.10 =
* Skip invalid dates - the importer will choose a date (that of the article) if WordPress can't supply a valid date on export.
= 3.09 =
* Suppress comment changes to author fields when an approval activity is posted back to the plugin.
= 3.08 =
* Fix very old bug with livefyre_get_wp_comment_id (does not return!) this fixes postback in some cases.
* Add signature to sync request url.
* Uses the new www.livefyre.com domain to avoid costly redirects.
= 3.07 =
* Fixed misconfigured Livefyre domain for bootstrap html fetching.
= 3.06 =
* Added a deactivate action to reset the status of the import process.  This prevents upgrading an old plugin (in an inconsistent state) and erroniously getting the admin notification "we're still importing your comments..."
* Added testing for iconv() support for before attempting to sanitize comments that are being exported (to Livefyre)
= 3.05 =
* Added better messaging during import process: * notifying users of the fact that a job is queued, when it is * better display when an error is encountered * allow users to come back and see continuously updated import status via the Livefyre link under Comments (WordPress Admin) * better messaging in admin dashboard during import
* Added a unicode-cleansing character filter as per the spec http://www.w3.org/TR/xml/#charsets.  This resolves a rare issue where very old (upgraded) WordPress blogs sent Livefyre invalid characters during data export.
= 3.04 =
* Handling quotes better for postback, as using the correct "init" hook causes WordPress to unilaterally escape all quotes in $_POST.  This fixes broken postback in a number of cases.
= 3.03 =
* Moving postback hook into the more appropriate "init" wp hook for better performance
= 3.02 =
* Fixing syntax that was incompatible with php 4.x.
= 3.01 =
* New platform release with updated postback synchronization.
= 2.41 =
* Fixed bug where livefyre css loads on every page (which made pingdom claim that every image in the css, whether loaded or not, was being fetched on page load.  LIES!)
= 2.40 =
* Fixed bug related to load order changes in the Livefyre streaming library.
= 2.39 =
* Fixed bug on upgrade to 3.0.3 that caused a permission error on activation (or re-activation) of Livefyre.
= 2.38 =
* Corrected use of 'siteurl' to 'home' instead when obtaining the site's base url for web service endpoints in the plugin.
= 2.37 =
* Added 'copy my comments' button for those who decide to import or who need to sync comments.  Unfortunately until our 'full sync' solution is complete, the button is kind of dumb and is there all the time.  It can't hurt tho - we'll never duplicate a comment thats already in the livefyre system.
* Added automated test for wp_head() template hook, to proactively notify users of compatibility issues.
= 2.36 =
* Improved the automated importer - it now limits the maximum number of queries to run for one chunk of xml (20) in addition to limiting the number of characters that are allowed in one chunk.
* Improved the automated importer - using local dates where GMT is unavailable on very old articles, presumably from versions of WP that didn't track gmt.
* Added a 'copy my comments' button to the options page for users who opted out of automatic import on the initial registration step.
= 2.33 =
* Added automatic comment sync for users who deactivate, then collect more comments in the wp db, then re-activate Livefyre.
= 2.31 =
* Securing the export process with a signature.
= 2.27 =
* Changes to ignore bogus/zero import dates.
= 2.26 =
* Adding phone-home on activation/deactivation of plugin, we now store the status on a Livefyre server for debugging purposes.
* Adding large blog import support - we now use chunk files with a central index delivered to Livefyre.com instead of one giant XML file of arbitrary length (regularly growing to the point of exhausting RAM).
* Making the livefyre interface behave correctly on pages (eg 'About') as well as posts.
* Not showing the livefyre interface on preview mode - this was breaking the title grabber.
* Only showing approved pingbacks/trackbacks.
= 2.25 =
* Excluding pingback and trackback data from comment data import. Removed unnecessary extra call to Livefyre server on successful authentication on the plugin options page.
= 2.24 =
* Added cache reset calls to reset wp-cache and WP Super Cache plugins
= 2.22 =
* Shows trackbacks
= 2.20 =
* Copies comments to WordPress database.
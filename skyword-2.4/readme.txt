=== Skyword API Plugin ===
Contributors: skyword
Tags: skyword, api
Requires at least: 3.3
Tested up to: 4.7.3
Stable tag: 2.4.5

Allows integration with the skyword publishing platform.

== Description ==

[Skyword](http://www.skyword.com/)  is the leading comprehensive content production platform. The Skyword Platform enables brands, retailers, media companies, and agencies to acquire and engage customers by efficiently producing quality content optimized for search and the social web. Skyword for Agencies is an offshoot of the Skyword Platform designed to meet the specific needs of marketing and advertising agencies that manage content programs for multiple clients. Quality content is essential for reaching and engaging consumers today, but the creation process is messy, inconsistent and immeasurable. The Skyword Platforms make it easy to produce, optimize, and promote content at any scale to create meaningful, lasting relationships with customers. 

== Installation ==


== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==

= 2.4.5 =
* Add sanity check for trusted URLs for iframe shortcode

= 2.4.4 =
* Additional code updates to meet WordPress coding standards
* Added 'skyword_post_publish' action to the end of the post creation process, hook onto this action to add your own post-processing
* Additional integration with the Co-Author Plus plugin to transfer Skyword profile pictures for new guest authors
* Users with the role of Editor will now also be considered Authors


= 2.4.3 =
* Added in-plugin option to disable the automatic generation of WordPress user accounts
* Added support for Skyword iFrame max-height and max-width attributes
* Updated code structure to meet WordPress coding standards

= 2.4.1 =
* Update shortcode generator and properly check for values

= 2.4.0 =
* Add custom short codes for handling iframes with social media content from Youtube, Instagram, Facebook, etc. published from the Skyword application

= 2.3.4 =
* undo a mistaken int cast

= 2.3.3 =
* Use explode function to convert comma delimited string to an array

= 2.3.2 =
* Cast value to int on wp_set_object_terms call

= 2.3.1 =
* Improved featured image functionality

= 2.3.0 =
* Fixed a typo

= 2.2.9 =
* Fix a quote issue

= 2.2.8 =
* Add skywordId as meta tag to images. Use that to facilitate attaching images to posts

= 2.2.7 =
* Add more sanitization and validation of data

= 2.2.6 =
* Fix security issue

= 2.2.5 =
* Fix bug with syncing taxonomies when having a different table prefix than the default of wp_

= 2.2.4 =
* Get all attachments when locating featured image

= 2.2.3 =
* Better uri matching for sitemaps

= 2.2.2 =
* Modified to grab tags with no usages in posts

= 2.2.1 =
* Increase number of posts to return for sitemaps

= 2.2 =
* Add new method of syncing taxonomies by getting terms in chunks instead of all at once lowering client's memory usage

= 2.1.14 =
* Fix hyphens in custom fields
* Use full image for og:image meta tag

= 2.1.13 =
* Use urldecode() instead of str_replace()

= 2.1.12 =
* Skip any taxonomies with over 50k terms during sync. Workaround till platform changes are completed.

= 2.1.11 =
* Fix version error for platform

= 2.1.10 =
* Fix sitemap errors

= 2.1.9 =
* Add check for thumbnail size for opengraph image tag

= 2.1.8 =
* Add method to get phpinfo() for diagnosis. Can only be called by authorized user using HTTPS

= 2.1.7 =
* Fix use of Coauthors Plus to create user

= 2.1.6 =
* Bug fix

= 2.1.5 =
* Can now send taxonomies by their id

= 2.1.4 =
* Fixed version number

= 2.1.3 =
* Added shortcode for tracking - [skyword_tracking id='12345'] or [skyword_tracking] (auto content_id retrieval)
* Fixed line returns in open graph tags.
* Fixed published date changing after a revision.

= 2.1.2 =
* Added configurable slugs

= 2.1.1 =
* Trim user-id

= 2.1 =
* Added Geo My WP Integration

= 2.0.5.7 =
* Updated hyphens in custom fields

= 2.0.5.6 =
* Fix for backslashes

= 2.0.5.5 =
* Updated tested up to

= 2.0.5.4 =
* Updated admin panel

= 2.0.5.3 =
* Admin panel

= 2.0.5.2.1 =
* Category escaping issue

= 2.0.5.2 =
* Only write sitemaps if ABSPATH directory is valid

= 2.0.5.1 =
* Fixed warning log message

= 2.0.5 =
* Additions to evergreen sitemap generation

= 2.0.4 =
* Updated sitemap generation

= 2.0.3 =
* Added Google news keyword meta tag

= 2.0.2 =

* Sitemap access fix

= 2.0.1 =
* Sitemap action filter fix

= 2.0 =
* Added metatag info

= 1.0.7.7 =
* Disable notice logging

= 1.0.7.6 =
* Renamed default un

= 1.0.7.5 =
* Updated naming of custom fields

= 1.0.7.4 =
* Blog Language updated

= 1.0.7.3 =
* Evergreen sitemap fix

= 1.0.7.2 =
* Added tracking tag shortcodes

= 1.0.7.1 =
* Updated sitemaps for multisite use

= 1.0.7 =
* Added news sitemaps

= 1.0.6.1 =
* Fixed no attachment error

= 1.0.6 =
* Fixed featured image updating

= 1.0.5 =
* Fixed author syncing bug

= 1.0.4 =
* Added update functionality

= 1.0.3 =
* Initial release through wordpress SVN

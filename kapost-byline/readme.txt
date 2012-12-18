=== Kapost Social Publishing Byline ===
Contributors: iamnader, icebreaker, worldnamer
Tags: social, publishing
Requires at least: 2.9
Tested up to: 3.4.2
Stable tag: 1.7.4

This Kapost plug-in is a companion with the newsroom that you can create at Kapost.com.

== Description ==
This plugin allows posts published from Kapost.com into your WordPress blog to keep the same username for the author. If the author on the published post doesn't exist in WordPress (check done by looking at email address), this plugin will return an error message.

In addition, this plugin allows Kapost newsrooms to publish to a Custom Type. 

For this to work, you should name a Custom Field (on Kapost.com) as "kapost_custom_type". 

The display name can be anything but the field name has to be what's listed above. 

Also, on the Kapost.com side, in the dropdown list, then put the name exactly of the Custom Type. 

The way you can tell what it is, go to post type, and the page in WP will reveal what the exact name is. 

The name in the Custom Field has to be exact name.

For more information, visit the [Kapost website](http://www.kapost.com).

== Installation ==

1. Upload all the files to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==
= 1.7.4 =
* improved kapost.getPermalink

= 1.7.3 =
* double escaping fixes

= 1.7.2 =
* analytics improvement

= 1.7.0 =
* added new analytics

= 1.6.0 =
* added kapost.editPost

= 1.5.0 =
* added support to match custom fields to custom taxonomies

= 1.4.0 =
* added kapost.getPermalink

= 1.3.0 =
* added image metadata support

= 1.2.0 =
* reworked asynchronous calls to analytics to reduce plugin interference and remove need for tags in the body

= 1.0.7 =
* added support to store and modify protected custom fields

= 1.0.6 =
* set publish date for drafts

= 1.0.5 =
* avoid double escaping

= 1.0.4 =
* added built-in Kapost Analytics support

= 1.0.3 =
* clear publish date when publishing as draft

= 1.0.2 =
* added featured image support

= 1.0.1 =
* fixed "publish as draft"

= 1.0.0 =
* First version

== Upgrade Notice ==
= 1.7.4 =
* improved kapost.getPermalink

= 1.7.3 =
* double escaping fixes

= 1.7.2 =
* analytics improvement

= 1.7.0 =
* added new analytics

= 1.6.0 =
* added kapost.editPost

= 1.5.0 =
* added support to match custom fields to custom taxonomies

= 1.4.0 =
* added kapost.getPermalink

= 1.3.0 =
* added image metadata support

= 1.2.0 =
* reworked asynchronous calls to analytics to reduce plugin interference and remove need for tags in the body

= 1.0.7 =
* added support to store and modify protected custom fields

= 1.0.6 =
* set publish date for drafts

= 1.0.5 =
* avoid double escaping

= 1.0.4 =
* added built-in Kapost Analytics support

= 1.0.3 =
* clear publish date when publishing as draft

= 1.0.2 =
* added featured image support

= 1.0.1 =
* fixed "publish as draft"

= 1.0.0 =
* First version

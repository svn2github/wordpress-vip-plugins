=== Email Post Changes ===
Contributors: mdawaffe, automattic
Tags: email, diff, post, page, change
Requires at least: 2.9
Tested up to: 2.9.1
Stable tag: 0.5

Emails you Whenever a change to a post or page is made.

== Description ==

Each time a change is made to a post or page, those changes are emailed to the email addresses you specify.

Optionally, you can set what types of changes to email (post changes, page changes, attachment changes, or changes
to any other 'post type' defined by any other plugin).

The changes are emailed as a unified diff.  If the email client supports HTML emails, the diff
will be colorized.


== Installation ==

After uploading and activating the plugin, go to Settings -> Email Post Changes
to set up the plugin.

You can change what email addresses to use and for what post types you
want change notifications.

This plugin requires PHP 5.2 or greater.


== Changelog ==

= 0.5 =
* Fix htmlencoding in email subjects.

= 0.4 =
* Fix some PHP Warnings.
* Fix bug when emails array is already an array.
* Remove code that requires PHP 5.3.
* Fix dates in HTML email.
* Fix name of who edited the post.

= 0.3 =
* Fix a Fatal PHP Error.
* Configuration settings: email addresses, post types.


== Upgrade Notice ==

= 0.5 =
Bug fixes.

= 0.4 =
Reduces PHP dependency to PHP version 5.2.  Bug fixes.

= 0.3 =
Bug fixes.  Configuration settings.

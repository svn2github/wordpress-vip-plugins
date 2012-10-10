=== PMC Post Savior ===
Contributors: pmcdotcom, mintindeed
Tags: login
Requires at least: 3.4
Tested up to: 3.5
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Never get unexpectedly logged out when saving a post again.

== Description ==

PMC Post Savior checks every 15 seconds to see if you're still logged in.  If you are not, it presents a pop-up window so that you can log back in without losing your work.

== Installation ==

1. Upload the `pmc-post-savior` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Why don't you just read the cookies via javascript? =

Since the auth cookies are not visible to javascript, this uses AJAX to communicate with WordPress to check the login cookies.

= Why 15 seconds? =

Because 1 minute is too long.

== Screenshots ==

1. PMC Post Savior notices that your login has expired.
2. PMC Post Savior presents a pop-up that lets you login again.

== Changelog ==

= 1.0 =
* Fix issue where login prompt didn't show in full-screen edit mode.
* No longer instantiate into a global.  Unnecessary since the class is already a singleton.  Props JJJ.

= 0.9 =
* Initial beta release.

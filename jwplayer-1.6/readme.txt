=== JW Player for WordPress ===
Contributors: LongTail Video
Tags: jwplayer, jw, player, jwplatform, video, media, html5
Requires at least: 4.3
Tested up to: 4.8.2
Stable tag: 1.6.1
License: GPLv3

Upload and embed videos with your JW Player account to seamlessly integrate video into your WordPress website.

== Description ==

**If you do run into an issue we encourage you to [report this issue in the GitHub mirror](https://github.com/jwplayer/wordpress-plugin/issues) of this plugin. Thank you for your help.**

This plugin will give you the power to use videos, playlists, and players from your JW Player account within WordPress. You will also be able to track the performance of your content with JW Player’s dashboard analytics.


= Key Features =

* What it does:
    - Allows you to easily insert media (including playlists) into your website from your JW Player account.
    - Allows you to place players from your JW Player account into your website.


* What is does NOT do:
    - Replace the JW Player dashboard
    - Allow you to create or manage players or media objects from within the plugin (these actions happen within the JW Player dashboard)

* Additional features:
    - You may also sync WordPress-hosted media to your JW Player account (as externally-hosted media)

[Sign up for a free JW Player account!](http://www.jwplayer.com/pricing/)


= Documentation =

Full documentation on installation, setup and getting started can be found on
our [Support Site](http://support.jwplayer.com/).

If you have any questions, comments, problems or suggestions please post on our
[User Forum](http://support.jwplayer.com/).

= Issues & Contributions =

This plugin is open source and we strongly encourage users to contribute to the plugin's development. If you find a bug or another issue, please [report it on the plugin's GitHub mirror](https://github.com/jwplayer/wordpress-plugin/issues) and if you would like to suggest improvements feel free to open a pull request.

* Known Issues:
    - In some cases the posts list in your admin may appear empty after activating this plugin. [Please help us fix this issue](https://github.com/jwplayer/wordpress-plugin/issues/17).


== Installation ==

1. Unpack the zip-file and put the resulting folder in the wp-content/plugins
   directory of your WordPress install.
2. Login as WordPress admin.
3. Go the the plugins page, the JW Player plugin should be visible.
   Click "activate" to enable the plugin.
4. Click the "authorize plugin" link in the notification to authorize your
   plugin.
5. Change the settings to your liking. Don't forget to enable secure content in
   your JW Platform account if you want to make use of the signed links.
   It is also possible to enable the widget as a box inside the authoring
   environment, in addition to the "Add media" window.


== Screenshots ==

1. Insert media from your JW Player Account via the media library overlay or ...
2. ... use the sidebar widget.
2. You can enable the sidebar widget and edit other settings on the plugin's settings page.


== Frequently Asked Questions ==

= Does this plugin replace the old JW Player Plugin for WordPress? =

Yes, it does. You cannot run both plugins at the same time. However, you can import your referenced media, your players and your playlists from the old plugin into your JW Player account.

= Does this plugin work with caching solutions like WP-Supercache? =

Yes, it does. However, you should disable the signing functionality of the
plugin, since the caching might interfere with the signing timeout logic. Simply
go to Settings > JW Player and set the signing timeout to 0.

= Can I search through only my playlists? =

Yes, you can. In order to do this, simply write "pl:" (without the quotes) in front of your search query in the widget.

= I've found an issue with the plugin, what should I do? =

We're sorry that you've found an issue. Could you [report the issue in the plugin's the GitHub mirror](https://github.com/jwplayer/wordpress-plugin/issues).

= I have a suggestion to make the plugin better. =

That's great. Tell us about it and open a pull request on [our GitHub mirror of the plugin](https://github.com/jwplayer/wordpress-plugin/).

== Changelog ==

= 1.6.1 =

* Fix: Several small fixes. Thanks to [@david-binda](https://github.com/david-binda), [@dzienisz](https://github.com/dzienisz) and [@srtfisher](srtfisher).

= 1.6.0 =

* Feature: Added option to disable syncing local content to your JW Player account.
* Feature: Added a filter hook for generated JS embed code. Thanks to [@nirarazi](https://github.com/nirarazi)
* Update: Show Widget by default
* Update: Tested for compatibility with Wordpress 4.7

= 1.5.8 =

* Enhancement: Check for minimum PHP version to prevent common issues.

= 1.5.7 =

* Fix: Replacing legacy constant fixes https issue. Thanks to [@andrewhayter](https://github.com/andrewhayter)

= 1.5.6 =

* Update: Tested for compatibility with Wordpress 4.6

= 1.5.5 =

* Update: Use JSON feeds instead of XML

= 1.5.4 =

* Fix: Uploading files with Unicode names
* Fix: Added back the ph parameter

= 1.5.3 =

* Fix: Https upload issues in admin.
* Fix: Renamed main JS object to prevent conflicts with player in the admin.

= 1.5.2 =

* Issue: Small fix to make the plugin work with broken mime types.
* Change: Use https for API by default even for server to server.

= 1.5.1 =

* Issue: Widget text parser was undefined
* Issue: PHP warning for undefined variable removed
* Issue: Signing fix for when secure embeds is enabled
* Issue: Minor VIP fixes.

= 1.5.0 =

* Change: Strange version number increase to work with previous VIP plugin
* Update: Wordpress VIP changes.
* Update: API kit param is includes plugin version.

= 0.10.2 beta =

* Update: Force https upload even if API returns http protocol.
* Issue: Force https for thumbs if content mask is the def

= 0.10.1 beta =

* Issue: Fixed overflow of long video titles.
* Update: Added new screenshots

= 0.10.0 beta =

* Feature: Improved and redesigned media selection widgets.

= 0.9.2 beta =

* Issue: Fixed bug with an undefined constant

= 0.9.1 beta =

* Issue: Fixed bug with content signing

= 0.9 beta =

* Initial beta release.

== Upgrade Notice ==

Please remember that this plugin replaces our old JW Platform and our old JW Player for WordPress plugins and it should not be activated at the same time.


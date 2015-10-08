=== inform-plugin ===
Contributors: Inform, Inc.
Donate link: http://www.newsinc.com/
Tags: video, videos, embed
Requires at least: 4.2.2
Tested up to: 4.2.3
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Video player plugin for Inform videos.

== Description ==

Enhance and monetize your content by tapping into Inform's robust library of premium videos from over 400 highly reputable affiliates including AP, E! Online, and Fox Sports -- at absolutely no cost.
This plug-in offers a host of benefits:

* Premium video content from top publishers
* HD video platform & CMS
* An easily searchable database
* Video content pre-loaded with brand safe advertising for increased monetization
* The ability to insert a video into posts with one click
* The ability to preview a video before selecting and publishing
* Shared revenue model, at no cost to you

More about Inform:

* #1 in comScore’s April 2015 ranking of Online News & Information Video Properties
* Inform partners with the top online publishers, content providers, and advertisers to drive increased revenue while bringing premium, brand-safe videos to millions of viewers.
* With a robust video technology platform with advanced analytics, full service digital media management and CMS, 24/7 editorial support, and more, Inform is even better than free.

To use the plug-in or to find out more about partnering with Inform, you can request a [demo](http://www.inform.com/contact-us), or email us [here](mailto:wordpress@inform.com). One of our account managers will be happy to onboard you in the process.

== Installation ==

* Download and extract plugin files to a wp-content/plugin directory.
* Activate the plugin through the WordPress admin interface with your Inform Control Room credentials.
* Customize the settings on the options page.

If you have any questions or problems, please email [wordpress@inform.com](mailto:wordpress@inform.com)

== Frequently Asked Questions ==

= What do the general setting fields mean? =

- Default Tracking Group **(required)**
The tracking group is a critical config setting for distribution partners.  The value of this setting is responsible for tracking all of a distribution partner's earnings and analytics in the Inform network. This can be obtained from your Inform account manager.

- Default DIV Class **(optional)**
To style the `div` that contains the video, supply your class name selector and modify your CSS stylesheet with that class name selector.

- Default Site Section **(optional)**
This is the site-section value used for analytics. If this config setting is not provided, the site-section will default to `inform_wordpress_plugin`. You can override this value with a unique identifier, such as your publication name.

- Responsive
See the question below, "What does responsive mean?"

= Why is there an image instead of my video in the visual editor? =

In this release, our embed code is not integrated with the visual editor. As a placeholder, we placed the video's thumbnail into the visual editor to show where the video will go.  Once the post is published, the image will change to the proper video on the page.

= How can I edit video configuration settings after the video has been embedded? =

You can change your editor to text mode and edit the attributes within the "img" tag, i.e. within "<img" and "/>"

* ndn-config-video-id: Inform Video ID
* ndn-config-widget-id: Start behavior settings. "1" is Autoplay. "2" is Click to play.
* ndn-site-section-id: The site section ID, set in the Inform settings page.
* ndn-video-width & ndn-video-height: Fixed video width and height. Only works if ndn-responsive="false". *Important*: needs to have "px" units after the number.
* ndn-responsive: Handles video width and height for you. Responsive and mobile friendly.

= What is the minimum width for a fixed width video player? =
The smallest width is 300px, which calculates to a height of 170px.

= Can I use a shortcode instead of the main plugin? =

As of version 0.1.1, yes. The shortcode has an `ndn` prefix.

Example:

[ndn video_id="29316028" tracking_group="10557"]

Required Fields:

* "video_id" -> Inform video id number
* "tracking_group" -> Inform tracking group number

Optional Fields:

* "start_behavior" -> Will only accept "autoplay" or "click_to_play"
* "site_section_id" -> site section id as a text field
* "responsive" -> True or false. If true, will not accept "width" and "height" attributes
* "width" -> Fixed width
* "height" -> Fixed height

= How can I change the alignment of the video? =

Currently, the plugin is only left-aligned and does not support right- or center- alignment. However, you can manually change the alignment by altering the CSS rule using your default div class.

This guide should help you with centering: https://css-tricks.com/centering-css-complete-guide/ , whereas this guide should help you with left/right justified divs: https://css-tricks.com/almanac/properties/f/float/

= What does responsive mean? =

Responsive turns off fixed widths and sizes your video accordingly. The CSS div class you select in default settings can be set at a max-width or max-height.

If responsive is not chosen as an option, you will need to specify video width. The default width is 425px.  Height is calculated automatically. Width can range from a minimum of 300px to a maximum of 640px.

= Why doesn't the plugin work with IE 11? =

The short answer is, it does; the plugin supports IE 11. There is a bug in the latest release of Wordpress, version 4.2.2, that blocks the visual editor.

To circumvent this problem, change the editor to "text" before using the plugin. Videos will insert accordingly.

If you have any concerns about this, let Wordpress know on their [support page](https://wordpress.org/support/).

== Screenshots ==

1. Inform button in your add/edit posts editor
2. Configure video settings and insert videos from Inform

== Changelog ==

= 1.3.2 =
* Featured image bug for not inserting video fixed

= 1.3.0 =
* Allows user to set any post with a video inserted to use that video's thumbnail as the featured image

= 1.2.0 =
* Allow user to set video thumbnail as featured image on post

= 1.1.0 =
* Rebranding Changes
* Bug fix for no image loaded on the visual editor

= 0.1.12 =
* Updated i18n domains

= 0.1.11 =
* Major security vulnerabilities fixed
* Properly escaped html, urls, and attrs
* Converted cURL to WP HTML API

= 0.1.8 =
* Changes to the readme

= 0.1.7 =
* Different GA account

= 0.1.5 =
* Fixed Width bug fixed - wasn't showing before

= 0.1.3 =
* Added Google Analytics
* Style changes

= 0.1.0 =
* Initial Release

== Upgrade Notice ==

= 1.3.2 =
* Featured image bug for not inserting video fixed

= 1.3.0 =
* Allows user to set any post with a video inserted to use that video's thumbnail as the featured image

= 1.2.0 =
* Allow user to set video thumbnail as featured image on post

= 1.1.0 =
* Rebranding Changes
* Bug fix for no image loaded on the visual editor

= 0.1.12 =
* Updated i18n domains

= 0.1.11 =
* Major security vulnerabilities fixed
* Properly escaped html, urls, and attrs
* Converted cURL to WP HTML API

= 0.1.8 =
* Changes to the readme

= 0.1.7 =
* Different GA account

= 0.1.5 =
* Fixed Width bug fixed - wasn't showing before

= 0.1.3 =
* Added Google Analytics
* Style Changes

= 0.1.0 =
Initial Release

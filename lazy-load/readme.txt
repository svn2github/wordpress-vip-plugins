=== Lazy Load ===
Contributors: batmoo, automattic, jakemgold, get10up
Tags: lazy load, images, front-end optimization
Requires at least: 3.2
Tested up to: 3.3
Stable tag: 0.3

Lazy load images to improve page load times and server bandwidth. Images are loaded only when visible to the user.

== Description ==

Lazy load images to improve page load times. Uses jQuery.sonar to only load an image when it's visible in the viewport.

This plugin is an amalgamation of code written by the WordPress.com VIP team at Automattic, the TechCrunch 2011 Redesign team, and Jake Goldman (10up LLC).

Uses <a href="http://www.artzstudio.com/files/jquery-boston-2010/jquery.sonar/ ">jQuery.sonar</a> by Dave Artz (AOL).

== Installation ==

1. Upload the plugin to your plugins directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Enjoy!

== Screenshots ==

No applicable screenshots

== Changelog ==

= 0.3 =

* Make LazyLoad a static class so that it's easier to change its hooks
* Hook in at a higher priority for content filters

= 0.2 =

* Adds noscript tags to allow the image to show up in no-js contexts (including crawlers), props smub
* Lazy Load post thumbnails, props ivancamilov

= 0.1 =

* Initial working version

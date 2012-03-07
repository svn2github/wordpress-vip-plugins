=== Chartbeat ===
Contributors: chartbeat
Tags: chartbeat, analytics
Requires at least: 2.8
Tested up to: 2.9.2
Stable tag: 1.2

Automatically adds pinging code for chartbeat real-time analytics service and provides top pages widget.

== Description ==

[Chartbeat](http://chartbeat.com) is a real-time analytics and
monitoring service. Site owners add Javascript to their pages so that
they can see how many people are using their site at any given
time. This plugin automatically adds the needed Javascript to a
WordPress blog.

Additionally, the plugin contains a Top Pages sidebar widget. This
allows site owners to show their users what the most popular pages are
on their site using data from chartbeat.

== Installation ==

1. Upload `chartbeat.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Under Settings > Chartbeat, add your User ID
4. You may also add your API key and configure the widget (API key must have "all" persmissions).
5. Select Newsbeat if you're using it
6. If you want to use the widget, drag it into your sidebar under Appearance > Widgets

*Note that you must have your timezone set correctly for events to work properly in
the historical chart.

== Frequently Asked Questions ==

= What is Chartbeat? =

Chartbeat is a real-time analytics and uptime monitoring service used
by everyone from mom-and-pop bloggers to some of the biggest sites on
the web. Try the [demo](http://chartbeat.com/demo/) and then sign up
for [a free trial](https://chartbeat.com/signup/)!

= What does this plugin do? =

This plugin makes it easy for chartbeat users to install the code they
need to add to their site that enables chartbeat to track what's
happening. Adding this plugin to your site if you do not have a
chartbeat account will not provide you with any analytics.

It also

= Will this plugin slow down my site? =

No, chartbeat's code is completely asynchronous. That means that it
doesn't begin to run until everything else has already loaded.

= How is this different from Google Analytics? =

Chartbeat provides statistics in real-time and sends you alerts if
your site goes down or your traffic spikes. This allows you to quickly
respond by fixing your server, improving your content, responding to
other bloggers, or whatever is appropriate for your site. Because
chartbeat sends pings throughout a user's session, you also get a much
richer sense of how people are using your site.

== Screenshots ==

== Changelog ==

= 1.0 =
* First verison. Please provide feedback.

= 1.2 =
* stable version

= 1.3 =
* 'trackadmin' option added by Jesse S. McDougall, jesse@catalystwebworks.com

= 2.0 =
* Added Dashboard Widget, Active Visits in Post Board and Embedded Console

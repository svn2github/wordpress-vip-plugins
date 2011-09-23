=== Zone Manager (Zoninator) ===
Contributors: batmoo
Tags: zones, post order, post list, posts, order, zonination, content curation, curation, content management
Requires at least: 3.0
Tested up to: 3.3
Stable tag: 0.2

Curation made easy! Create "zones" then add and order your content!

== Description ==

This plugin is designed to help you curate your content. 

Assign and order stories within zones that you create, edit, and delete. Then use the handy API functions to retrieve and display your content in your theme. Or for those who are a bit code-averse, try the handy widget.

Key features included in the plugin:

* Add/edit/delete zones
* Add/remove posts (or any custom post type) to/from zones
* Order posts in any given zone
* Limit capabilities on who can add/edit/delete zones vs add content to zones
* Locking mechanism, so only one user can edit a zone at a time (to avoid conflicts)
* Idle control, so people can't keep the zoninator locked

This plugin was built by [Mohammad Jangda](http://digitalize.ca) in conjunction with [William Davis](http://wpdavis.com/) and the [Bangor Daily News](http://www.bangordailynews.com/).

== Installation ==

1. Unzip contents and upload to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Dashboard > Zones to create and manage zones.
1. Use the plugin's handy API functions to add zones to your theme.
1. Enjoy!

== Frequently Asked Questions ==

= How do I disable the locking feature? =

You can use a filter:

`add_filter( 'zoninator_zone_max_lock_period', 'z_disable_zoninator_locks' );`

= How do I change the the locking feature settings? =

Filter the following and change according to your needs:

<pre>zoninator_zone_lock_period</pre> - number of seconds a lock is valid for, default `30`

<pre>zoninator_zone_max_lock_period</pre> - max idle time in seconds

== Screenshots ==

1. Create and manage your zones and content through a fairly intuitive and familiar interface

== Changelog ==

= 0.3 =

* Disable editing and prefixing of slugs. They're just problems waiting to happen...

= 0.2 = 

* Move Zones to a top-level menu so that it's easier to access. And doesn't make much sense hidden under Dashboard.
* Change the way error and success messages are handled.
* jQuery 1.6.1 compatibility.
* Bug fix: Custom Post Types not being included in search. Thanks Shawn!
* Bug fix: Custom Post Types not being included in post list. Thanks Daniel!
* Bug fix: Error thrown when removing last post in a zone. Thanks Daniel!
* Other cleanup.

= 0.1 =

* Initial Release!

== Upgrade Notice ==

= 0.3 =

* Slugs can no longer be edited. This is possibly a breaking change if you're using slugs to get zones or zone posts.

= 0.2 =

* Bunch of bug fixes and code improvements

== Usage Notes ==

= Function Reference = 

<code>
@return array List of all zones
z_get_zones()
</code>

<code>
@param $zone int|string ID or Slug of the zone
@return array Zone object
z_get_zone( $zone )
</code>

<code>
@param $zone int|string ID or Slug of the zone
@return array List of orders post objects
z_get_posts_in_zone( $zone )
</code>

More functions listed in functions.php

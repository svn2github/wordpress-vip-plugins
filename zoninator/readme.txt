=== Zone Manager (Zoninator) ===
Contributors: batmoo
Tags: zones, post order, post list, posts, order, zonination, content curation, curation, content management
Requires at least: 3.0
Tested up to: 3.1
Stable tag: 0.1

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

= 0.2 = 

* Bug fix: Custom Post Types not being included in search. Thanks Shawn!

= 0.1 =

* Initial Release!

== Upgrade Notice ==

Nothing to see here...

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

=== External Permalinks Redux ===
Contributors: ethitter, thinkoomph
Donate link: http://www.thinkoomph.com/plugins-modules/external-permalinks-redux/
Tags: link, redirect, external link, permalink
Requires at least: 3.0
Tested up to: 3.4
Stable tag: 1.0.1

Allows you to point WordPress objects (posts, pages, custom post types) to a URL of your choosing.

== Description ==

Allows users to point WordPress objects (posts, pages, custom post types) to a URL of their choosing, which is particularly useful for injecting non-WordPress content into loops. The object appears normally in any loop output, but visitors to the object will be redirected to the specified URL. The plugin also allows you to choose the type of redirect, either temporary (302), or permanent (301).

Through a filter, the External Permalinks Redux meta box can easily be added to custom post types. There is also a function available for use with WordPress' `add_meta_box` function.

This plugin was originally written for use on WordPress.com VIP. It is inspired by and backwards-compatible with Mark Jaquith's Page Links To plugin, meaning users can switch between plugins without risk of losing any existing external links.

This plugin is translation-ready.

== Installation ==

1. Upload external-permalinks-redux.php to /wp-content/plugins/.
2. Activate plugin through the WordPress Plugins menu.

== Frequently Asked Questions ==

= How can I add support for my custom post type? =
Using the `epr_post_types` filter, one can modify the default array of object types (`post` and `page`) to include additional custom post types or remove the plugin from one of the default post types.

= What other filters does this plugin include? =
* `epr_meta_key_target` - modify the meta key associated with the external URL
* `epr_meta_key_type` - modify the meta key associated with the redirect type

== Changelog ==

= 1.0.1 =
* Add shortcut function for registering meta box on custom post types. This is included as an alternative to the `epr_post_types` filter discussed in the FAQ.

= 1.0 =
* Initial release in WordPress.org repository.
* Rewrote original WordPress.com VIP plugin into a class and added support for custom post types.
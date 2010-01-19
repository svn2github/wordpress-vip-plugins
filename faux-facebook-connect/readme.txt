=== Faux Facebook Connect ===
Contributors: tott, automattic
Tags: facebook, fbconnect, facebook connect, comments, sso, single sign on
Requires at least: 2.7
Tested up to: 2.9
Stable tag: trunk

Faux Facebook Connect is a basic integration to allow Facebook users to comment on a WordPress blog.

== Description ==

Faux Facebook Connect is a basic integration to allow Facebook users to comment on a WordPress blog. It provides single sign on, and avatars. It is tuned for WordPress MU usage as it does not perform any database alterations or adds any users and is mainly a javascript integration. It requires a <a href="http://www.facebook.com/developers/">Facebook API Key</a> for use. Thanks go to Beau Lebens for writing a <a href="http://dentedreality.com.au/2008/12/implementing-facebook-connect-on-wordpress-in-reality/">good introduction</a> and Adam Hupp for his inspireing <a href="http://wordpress.org/extend/plugins/wp-facebookconnect/">WP-Facebookconnect plugin</a>

== Installation ==

1. Upload the `faux-facebook-connect` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Visit `Settings`=>`Faux Facebook` in your wp-admin area and follow the instructions on that page.

== Screenshots ==

1. The setup parameters. You need to complete all those steps in order to make the plugin work
2. Comment form with Facebook connect button
3. Facebook connect dialog
4. Comment form and comment after successful connecting to Facebook.

== Changelog ==

= 0.1 =
* initial version of this plugin.

== WordPress MU usage ==

This plugin can be easily implemented in a WordPress MU environment and run out of the theme context. 
In order to activate it and customize various options there are various filter hooks within the script.

= Activation in theme context =

To make sure the script loads out of a theme context or other urls use the fauxfb_plugin_url filter as shown below

`add_filter( 'fauxfb_plugin_url', 'fauxfb_plugin_url' );
function fauxfb_plugin_url() {
        return get_bloginfo('template_url') . '/plugins/faux-facebook-connect';
}`

Placing this code in the `functions.php` of your theme would cause the script to search in the `plugins` folder of the template for the `faux-facebook-connect` folder.

It could be activated by putting the following line in your `functions.php` file just after this call.

`require_once( 'plugins/faux-facebook-connect/faux-facebook-connect.php' );`

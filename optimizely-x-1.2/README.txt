=== Optimizely X ===
Contributors: arthuracs, jonslaught, bradtaylorsf, lucasoptimizely
Tags: optimizely, ab testing, split testing, website optimization
Requires at least: 3.0
Tested up to: 4.9.1
Donate link: N/A
Stable tag: 1.2.3
License: BSD 3-Clause
License URI: https://opensource.org/licenses/BSD-3-Clause

This plugin helps you configure your WordPress website to use Optimizely X, a dramatically easier way to improve your website through A/B testing.

== Description ==

This plugin helps you configure your WordPress website to use Optimizely X. If you are new to Optimizely or have started using Optimizely Classic (not X), then please use the [Optimizely Classic plugin](https://wordpress.org/plugins/optimizely/).

Optimizely is a dramatically easier way for you to improve your website through A/B testing. Create an experiment in minutes with our easy-to-use visual interface with absolutely no coding or engineering required. Convert your website visitors into customers and earn more revenue today!

This plugin helps you configure your WordPress website to use Optimizely. After setting up an account at Optimizely.com, you simply enter your Optimizely project code in the plugin's configuration page and you're ready to start improving your website using Optimizely. Built for testing headlines, this plugin allows you to, create new experiments, see your experiment results, launch winners and much more all without leaving Wordpress.

You'll need an [Optimizely.com](http://www.optimizely.com) account to use it.

== Installation ==
Sign up at [Optimizely.com](http://www.optimizely.com).

1. If you have the Optimizely Classic plugin installed, uninstall the Classic plugin
2. Upload the Optimizely WordPress plugin to your blog
3. Activate the plugin through the Optimizely menu in WordPress
4. Enter your Optimizely API token in the plugin's settings page, choose a project to use, then save.

You're ready to start using Optimizely!

== Changelog ==

= 1.2.3 =
* Allows experiments to be created while posts are still drafts.
* Adds error handling for errors that happen during ajax on the Results page.
* Resolves issue with slow meta query.
* UI improvement: Remove button to start experiment for archived experiments.
* Bug prevention: Rename template variables so that they don't override WP Globals.
* Fix encoding issue for headline test.

= 1.2.1 =
* Fix an issue with multiple experiment IDs in post meta.
* Fix bug where launching an experiment didn't work for custom post types.
* Escape headline variation text for output in javascript and use innerText instead of innerHTML to prevent javascript injection.
* Only display the metabox to users with the Optimizely capability.
* Improve performance of WordPress meta queries.
* Log API requests and responses to post meta to allow for determining cause of API failures and other issues.
* Increase all requests to 60 second timeout to handle long server response times.
* Improve the error messages that are displayed to the user.

= 1.2.0 =
* Added new Optimizely Results dashboard.
* Ability to Pause, Start, and Archive experiments from the dashboard.
* Ability to Launch experiments from the dashboard.
* Migrated all `optimizely_` option keys to `optimizely_x_` to avoid settings collisions.
* Additional fix for a required field bug on the post edit page.

= 1.1.1 =
* Fixed a bug where some required fields were hidden.

= 1.1.0 =
* Increased the timeout of all requests to the Optimizely API to 60 seconds

= 1.0.0 =
* Introducing the Optimizely X WordPress plugin. Now it's even easier to start improving your website.

== Upgrade Notice ==
There are no upgrade notices

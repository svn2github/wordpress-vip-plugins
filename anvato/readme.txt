== Anvato Video Plugin ==
Contributors: Anvato
Tags: anvato, media, video player, media explorer, live stream player
Stable tag: 1.1

== Description ==
A WordPress Plugin for integrating the Anvato video player. This plugin lets you add a shortcode for Anvato video into your content. You can easily find your Anvato video by searching with this plugin. 

== Setup ==
In order to get this working in your WordPress installation, you have to follow
the next steps:

* Get the below configuration parameters form Anvato
	* `mcp_id`
	* `station_id`
	* `profile`
	* `player_url`
* Set default video player size & autoplay state
	* `width`
	* `height`
	* `autoplay`
* Set tracking parameters, give empty if you use default 
	* `plugin_dfp_adtagurl`
	* `plugin_fw_parameters`
	* `tracker_id`
	* `adobe_profile`
	* `adobe_account`
	* `adobe_trackingserver`

== Usage ==
# Shortcode

This plugin has a shortcode supports to prepare Anvato video embed code automatically.

## Basic shortcode usage

`[anvplayer video="282411"]`

### Available shortcode attributes
* `video`
* `width`
* `height`
* `autoplay`
* `adobe_analytics` (accepts only `false`, which removes all Adobe settings from the output)



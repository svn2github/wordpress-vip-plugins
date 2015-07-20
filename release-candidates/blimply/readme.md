# Blimply
[![Build Status](https://travis-ci.org/rinatkhaziev/blimply.png?branch=master)](https://travis-ci.org/rinatkhaziev/blimply)

## Description

Blimply is unofficial WordPress Plugin for [Urban Airship](http://urbanairship.com/). It's based on official [UA PHP library](https://github.com/urbanairship/php-library2) and supports Urban Airship v3 APIs.

## Requirements
+ [WordPress 3.9](https://wordpress.org/download/)
+ PHP 5.3+
+ [Composer](https://getcomposer.org/)

## Features
+ Send push notifications from Dashboard widget
+ Send pushes on post publish
+ Manage Urban Airship tags/segments
+ Set custom sounds for push notifications (iOS only)
+ Set quiet time for pushes (no sound)

##  Initial installation
1. `git clone https://github.com/rinatkhaziev/blimply.git` in your WP plugins directory
1. Do `composer install --no-dev`
1. Activate the plugin
1. Set the settings
1. Enjoy

## Upgrade instructions
1. Pull as usual
1. Do `composer install --no-dev`
1. ...
1. Profit

## Tests
Plugin includes basic test suite, run a phpunit from the plugin folder

## Developers
Miss a feature? Pull requests are welcome.

## Future improvements
* Rich Push
* Scheduled pushes
* Geolocated pushes
WordPress Codebird
======================

* _Contributors: @automattic, @batmoo, @danielbachuber, @nickdaugherty_
* _Tested up to: 3.5.1_
* _Stable tag: 1.1.0_
* _License: GPLv2 or later_
* _License URI: http://www.gnu.org/licenses/gpl-2.0.html_

Description
-----------------------

An extension of the [Codebird](https://github.com/mynetx/codebird-php) class to use WordPress' HTTP API instead of cURL.

Provides a drop in replacement for Codebird with improved WordPress integration by replacing all cURL calls with WordPress HTTP API calls.

Usage
--------------------

Include both the Codebird library and `class-wp-codebird.php`, then get a new instance of `WP_Codebird`:

```php
$wp_codebird = WP_Codebird::getInstance();
```

The rest of the api is identical to Codebird - it is a drop in replacement that does not require any modification to existing code.

Changes
--------------------
**1.1.0 (04/09/2013)**

* Updated to support Codebird 2.3.2 and Bearer authentication 
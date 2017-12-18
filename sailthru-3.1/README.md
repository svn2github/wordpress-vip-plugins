Sailthru WordPress Plugin
=========================

For full documentation on this plugin please visit http://docs.sailthru.com/integrations/wordpress

For an list of bugs fixed and features released in this update please review the [Changelog](changelog.md). 

## Quick Start 
To install the plugin add to your WordPress plugins folder and enable the plugin via the WordPress Admin. This plugin will require a Sailthru API key and Secret which you can obtain from your Sailthru account. 

This version adds the ability to use the new Personalization Engine JavaScript from Sailthru. This new version will provide similar functionality to the Horizon JavaScript but uses a different setup. 

Initial setup will ask if you wish to use Horizon Javascript or Personalization Engine JavaScript. Select the one you wish to use and follwo instructions below. 

### Sailthru Script Tag
This version of the Sailthru JavaScript removes the need for the horizon domain name setting and adds a customer id. This can be found in your Sailthru account at Settings->Setup.

use of Sailthru Script Tag will remove the options for Scout and Concierge and replace the Sailthru Recommends widget with Sailthru Personalization Engine. This is powered by Sailthru's new Site Personalization Manager. Please speak to your Customer Success Manager for more information on this powerful new feature. 

As this new plugin has the Content API built in your Sailthru tags will be passed to us whenever a post is saved. To use the on site meta tags you instead of these you can check the setting "Ignore Stored Tags"

### Horizon JS
This setting will use the Sailthru Horizon JavaScript. Please note that this version of the Javascript will be deprecated at the end of 2016. 


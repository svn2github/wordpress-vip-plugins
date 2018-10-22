=== LaterPay ===

Contributors: laterpay, dominik-rodler, mihail-turalenka, avahura, ahryb
Donate link: https://laterpay.net
Tags: laterpay, accept micropayments, accept payments, access control, billing, buy now pay later, content monetization, creditcard, debitcard, free to read, laterpay for wordpress, laterpay payment, laterpay plugin, micropayments, monetize, paid content, pay button, pay per use, payments, paywall, PPU, sell digital content, sell digital goods, single sale, wordpress laterpay
Requires at least: 4.6
Tested up to: 4.9.8
Stable tag: 2.1.0
Author URI: https://laterpay.net
Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
License: MIT
License URI: http://opensource.org/licenses/MIT

Monetize your blog and content with LaterPay. We offer a frictionless transaction experience that helps you increase your revenue streams, conversion rates and online customer interactions.

== Description ==

= Overview =
Monetize your blog and content with LaterPay. We offer a frictionless transaction experience that helps you increase your revenue streams, conversion rates and online customer interactions.

[What LaterPay can do for you in one minute.](https://vimeo.com/273858404 "What LaterPay can do for you in one minute.")

Suggested users:

* bloggers
* small to medium-sized online magazines

NOTE: the plugin provides:

* Support for all standard post types and custom post types.
* The plugin is fully localized for English and German.

Contact us: [support@laterpay.net](mailto:support@laterpay.net)

= Pricing =
The LaterPay plugin allows you to set different price types for your blog posts:

* **Global default price**: This price is by default applied to all new and existing posts of the blog.
* **Category default price**: This price is applied to all new and existing posts in a given category:
    * If a category default price is set, it overwrites the global default price.
    * For example: setting a category default price of $0.00, while having set a global default price of $0.49 makes all posts in that category free.
* **Individual price**: This price can be set for each post on the ‘Add / Edit Post’ page:
    * It overwrites both the category default price and the global default price for the respective article.
    * For example: setting an individual price of $0.19 with a category default price of $0.10 and a global default price of $0.00 results in a price for that post of $0.19.
* You can also apply a **dynamic pricing** scheme to posts. That means for every single post, you can set a price curve that changes the price of a blog post over time, starting from the publication date:
    * For example: you can offer a breaking news post for $0.49 for the first two days and then automatically reduce the price to $0.05 until the fifth day to increase your sales.
* With **Time-Pass**:
    * You can sell time-limited access to all the LaterPay content:
        * on your entire website
        * in a specific category
        * on your entire website except for a specific category.
    * The user will have access to all the covered content during the validity period and afterwards, this access will expire automatically.
    * If you delete a time pass from the WordPress plugin, users, who have purchased this time pass before, will still be able to access their purchase.
    * Time passes are by default displayed below your posts or can be positioned manually using a shortcode or a WordPress action.
    * For each time pass, you can create any number of voucher codes that enable your users to purchase a time pass for a reduced price.
    * A user can enter a voucher code below the respective time pass and its price will then be updated.
        * Voucher codes are not user specific and can be used for any number of times until you delete them. Deleting a voucher code will not affect the access to time passes which have already been purchased with this code.

= Presentation / Visual elements =
* LaterPay button: Each post with a price > 0.00 automatically contains a LaterPay button at the beginning of the post content. You can choose to not show this button and instead render it from within your theme by calling `do_action( ‘laterpay_purchase_button’ )` within your theme.
* Teaser content: Every post you sell with LaterPay has to contain a teaser:
    * The teaser is shown to the user before purchasing a post.
    * The plugin automatically generates teaser content by taking the first 120 words of every existing post.
    * You can refine the teaser content on the ‘Add / Edit Post’ page.
    * You have the choice between two presentation modes for your teaser content:
        * Teaser only: This mode shows only the teaser with an unobtrusive purchase link below.
        * Teaser + overlay: This mode shows the teaser, an excerpt of the full content under a semi-transparent overlay that briefly explains LaterPay’s benefits. The plugin never loads the full content before a user has bought it.

= Security =
* File protection: The plugin secures files in paid posts against downloading them via a shared direct link.
* So even if a user purchases a post and shares the direct link to a contained file, other users won’t be able to access that file, unless they’ve already bought the post.

= Crawler friendliness =
* Social media: The plugin supports Facebook, Twitter, and Google+ crawlers, so it won’t hurt your social media reach.
* Google and Google News: The plugin also supports Google and Google News crawlers.
* Crawlers will never have access to the full content but only to your teaser content.
    * So depending on the presentation mode you’ve chosen, Google will access only the teaser content or the teaser content plus an excerpt of the full content.

= Caching compatibility =
The plugin automatically detects if one of the available WordPress caching plugins (WP Super Cache, W3 Total Cache, Quick Cache, WP Fastest Cache, Cachify, WP-Cache.com) are active and sets the config-key caching.compatible_mode accordingly. If the site is in caching compatibility mode, the post page is rendered without the actual post content,
which the plugin then requests using Ajax. If the user has not purchased the post already, only the teaser content and the purchase button are displayed.

= Test and Live mode =
* **Test mode:** The test mode lets you test your plugin configuration. While providing the full plugin functionality, no real transactions are processed. We highly recommend to configure and test the integration of the LaterPay WordPress plugin into your site on a test system, not on your production system. You can choose, whether LaterPay  should be visible for your visitors in test mode or not.
* **Live mode:** After integrating and testing the plugin, you might want to start selling content and process real transactions. To sign up for a live merchant account, [click here if you are based in the US](https://web.uselaterpay.com/dialog/entry/?redirect_to=/merchant/add#/signup) or [click here if you are based in the EU](https://web.laterpay.net/dialog/entry/?redirect_to=/merchant/add#/signup) . After the registration and the check of your contract information, we will send you an email with your LaterPay live API credentials for switching your plugin to live mode.

= Statistics =
You can access sales statistics in your LaterPay merchant account on [web.uselaterpay.com/merchant](web.uselaterpay.com/merchant) (for U.S. merchants) or [web.laterpay.com/merchant](web.laterpay.com/merchant) (for European merchants) or If have any questions about your LaterPay merchant account, please contact us via [support@laterpay.net](mailto:support@laterpay.net).

= Roles and capabilities =
Some plugin features won’t be available for certain user roles, based on the WordPress model of roles and capabilities:

* Subscribers (and regular, non-registered visitors): **cannot change any** plugin settings
* Contributors: can edit the teaser content of their **own** posts
* Authors: can **additionally** edit the individual prices of their **own** posts
* Editors: can edit the teaser content and individual prices of **all** posts
* Super Admins and Admins: Can **additionally** access the plugin backend, edit the plugin settings and (un-)install
and (de-)activate the LaterPay WordPress plugin.

= Custom hooks =
To integrate with existing access management systems, we provide several filters which you can hook into. These filters allow you to give a user access to content without having to purchase it. You can use `add_filter( ‘laterpay_post_access’, your_callback_returning_boolean )` to provide access to a post or page without necessitating a purchase. This still checks for access with the LaterPay API. To disable it for any post or page use `add_filter( ‘laterpay_access_check_enabled’, your_callback_returning_boolean )`.

= Note to our users =
The LaterPay WordPress plugin is one possible implementation of the LaterPay API that is targeted at the typical needs of bloggers and small to medium-sized online magazines.
You can — and are highly welcome — to modify the LaterPay plugin to fit your requirements.
If you are an expert WordPress user who is comfortable with web technologies and want to explore every last possibility of the LaterPay API, you may be better off by modifying the plugin or writing your own integration from scratch.
As a rule of thumb, if you employ a whole team of developers, it is very likely that you may want to make a few modifications to the LaterPay WordPress plugin.
If you have made a modification that would benefit other users of the LaterPay WordPress plugin, we will happily have a look at your work and integrate it into the official plugin.
If you want to suggest a feature or report a bug, please send your message to [wordpress@laterpay.net](mailto:wordpress@laterpay.net)

== Installation ==

* Install the plugin from wordpress.org/plugins/laterpay and activate it.
* The plugin is now in Invisible Test Mode, i.e. the plugin is not visible to visitors, but only to logged-in admins, and you can start the configuration.
* First, go to the Plugin Settings (There is a new item "LaterPay" in the side navigation) and go to the "Account" section.
  Please choose your region and currency here: If your business is located in the U.S. and you want your prices to be displayed in Dollar (USD), choose "United States (USD)". If your business is located in the European Union, and you want the prices to be displayed in Euro (EUR), choose "Europe (EUR)".
* By default, the plugin has set a Global Default Price of 0.29 after the activation – that means that each of your existing posts costs 0.29 by default. If you want to change this: In the "Pricing" section, you can define the Global Default Price, and also set Category Default Prices.
* If you want to set prices only for specific posts, and keep the rest of your content free, we recommend to set a Global Default Price of 0.00, and set an individual price for the respective posts. You can set an individual price for a post by opening this post for editing. In the upper right corner, you will see the LaterPay pricing widget.
* In the plugin, you will see two important terms: Pay Now and Pay Later – these are so called "revenue models" and define, if a post has to be paid immediately or can be paid later.
* If you choose "Pay Later" for a price, that means: The user agrees to pay later, and gets immediate access to the content – without upfront payment or registration. LaterPay keeps track of the user's device invoice and asks the user to pay, once the invoice has (across various websites) reached a total of 5.00 €/$. That's the LaterPay signature model and provides the unique and most convenient LaterPay purchase experience. You can use the PPU model for prices between 0.05 and 5.00.
* If you choose "Pay Now" for a price, that means: The user has to register/login, and pay immediately before getting access to the purchase. This model might make sense for higher-priced items. The SIS model can be used for prices between 1.49 € / $1.99 and 149.99.
* If you want to start earning money, you have to first register a LaterPay merchant account and request your
  Live API credentials (See section "Test and Live Mode" above).

The plugin's settings page (Settings > LaterPay) allows you to adjust some parameters, which are too advanced to list them in the regular LaterPay plugin backend:

= Colors =
You can define a primary and a secondary color to adjust the appearance of LaterPay elements on your website to your website's identity.

= Caching Compatibility Mode =
The plugin detects on activation or update, if you are using one of the common caching plugins and automatically
switches to caching compatibility mode.
You can manually turn this mode on (or off), if you have installed a caching plugin after installing the LaterPay
plugin or if you are using a caching plugin that is not detected.

= LaterPay-enabled Post Types =
You can enable or disable LaterPay for any standard or custom post type. By default, LaterPay is enabled for all
post types.

= Display of Time Passes for Free Posts =
You can choose whether you want to display time passes below free posts or only below paid posts.

= Automatically Generated Teaser Content =
The plugin will automatically generate teaser content, if you leave the teaser empty.
This functionality was introduced to handle the case that the LaterPay plugin is installed to monetize a large number
of existing posts and it would be too much effort to create individual teaser content or that work simply has not yet
been done. With the setting in this section, you can control, how many words of the full content the plugin should use
as teaser content. (E.g. 500 will use the first 500 words of the full content as teaser content,
if there is no teaser content.)

= Excerpt under Teaser Overlay =
If you choose the preview mode "Teaser + excerpt of the full text under overlay" in the appearance tab,
you can define the length of the excerpt under the overlay with the three settings in this section.

= Unlimited Access to Paid Content =
This setting gives all logged in users with a specific role full access to all paid content on your website.
To use this feature, you have to create at least one custom user role (e.g. with the free plugin 'User Role Editor')
and add the respective users to this group.

= LaterPay API Settings =
You can define a fallback behavior for the case, that the LaterPay API is not responding: Choose if you either want to "do nothing" and continue blocking access to your premium content, "grant access" and make your premium content available for free or "hide premium content" and simply not display premium content anymore as long as the LaterPay API is unavailable.

Additionally, you can choose whether you want to enable LaterPay on your front page or not.

== Frequently Asked Questions ==

= Contextual Help =
The LaterPay WordPress Plugin supports contextual help, so you will have all the information at hand right where and
when you need it. Contextual help for the current page is available via the ‘Help’ tab on the top of the respective page.

= Knowledge Base =
You can find further information about LaterPay and the LaterPay WordPress plugin in the LaterPay Knowledge Base on
support.laterpay.net

= How do I get my LaterPay Live API credentials? =
Please see the "Test and Live Mode" section.

== Screenshots ==

1. The LaterPay WordPress plugin lets you easily define a teaser text for your paid content and set an individual price...
2. ...or a dynamic price for your posts, pages and other content types.
3. In the *Pricing tab*, you can set default prices for the entire website or specific categories. You can create time passes to offer time-limited access to all your content or a category, or even offer subscriptions.
4. In the *Appearance tab*, you can choose between three preview modes and customize the appearance of all LaterPay elements on your website.
5. In the *Account tab*, you can manage your API credentials, switch between test and live mode, choose if LaterPay should be visible for your users in test mode, and set the region (U.S. or Europe) and currency ($ or €) for your LaterPay account.
6. The plugin provides a variety of advanced settings to customize the LaterPay plugin and adjust it to your needs.

== Changelog ==
= 2.1.0 ( October 11, 2018 ) =
* Add Voucher to Subscriptions.
* Add Voucher information to identify purchase made using Voucher Code.
* Add New cases to Global Default Pricing to support backwards compatibility.
* Fix issue with Voucher price exceeding time pass price.
* Remove redeem count display.
* Update Post Preview Widget permission only to be displayed to Admin User.
* Minor Bug Fixes

= 2.0.0 ( September 12, 2018 ) =
* Add Intro section in LaterPay Account tab to improve on-boarding.
* Add notice to inform if account setup is incomplete and/or plugin is invisible to users.
* Improve Category Pricing UX.
* Add section to inform about Revenue Model in Global Default Price section.
* Minor Bug Fixes

= 0.12.2 (August 24, 2018): Bugfix Release (v1.0 RC33) =
* Add FAQ section in account tab, to inform users about known issues.
* Fix issue with edit screen pointers.
* Fix issue with global default price revenue model.

= 0.12.0 (August 13, 2018): Bugfix Release (v1.0 RC32) =
* Fix issue with post purchase display.
* Fix translation issues.
* Add notice on WPEngine environment to bypass page cache.
* Update token cookie name to avoid conflicts.
* Remove Subscription Notice for pre June 2017 merchants.

= 0.11.0 (July 26, 2018): Bugfix Release (v1.0 RC31) =
* Fix teaser content display issue.
* Fix VIP-GO cache issue
* Fix timepass-subscription display issue.

= 0.10.0 (June 21, 2018): Bugfix Release (v1.0 RC30) =
* Set Minimum PHP version to 5.6
* Set Minimum WordPress version to 4.6
* Remove deprecated features from plugin backend
* Remove logger/debug bar code
* Remove custom table and use WP schema
* Add Migration for existing users
* Fix issue with Post purchase display

= 0.9.27.5 (April 3, 2018): Bugfix Release (v1.0 RC29) =
* Improved onboarding experience for U.S. users.

= 0.9.27.4 (March 13, 2018): Bugfix Release (v1.0 RC28) =
* Fixed issue with overlay.

= 0.9.27.3 (February 2, 2018): Bugfix Release (v1.0 RC27) =
* Added support for the new and more convenient live registration process.
* Fixed issue with deleting category price.

= 0.9.27.2 (November 24, 2017): Bugfix Release (v1.0 RC26) =
* Fixed issue with handling of free posts.
* Fixed issue with displaying subscriptions for specific categories.

= 0.9.27.1 (November 23, 2017): Bugfix Release (v1.0 RC25) =
* Fixed issue with plugin update

= 0.9.27 (November 23, 2017): Bugfix Release (v1.0 RC24) =
* Added a new, highly customizable appearance option which shows all purchase options in the same overlay.
* Optimized and reduced necessary API requests.
* Fixed deprecation issues in LaterPay PHP client.

= 0.9.26.2 (September 21, 2017): Bugfix Release (v1.0 RC23) =
* Fixed issue with plugin update

= 0.9.26.1 (September 19, 2017): Bugfix Release (v1.0 RC22) =
* Fixed overlay copy
* Improved process for requesting live credentials

= 0.9.26 (August 22, 2017): Bugfix Release (v1.0 RC21) =
* Added support for LaterPay Pro.
* Changing/removing subscriptions and time passes now auto-purges supported caches.

= 0.9.25.2 (August 8, 2017): Bugfix Release (v1.0 RC20) =
* Fixed translation issues

= 0.9.25.1 (July 27, 2017): Bugfix Release (v1.0 RC19) =
* Fixed issue with the activation of subscriptions after plugin update.

= 0.9.25 ( July 20, 2017 ): Bugfix Release (v1.0 RC18) =
* Added support for LaterPay subscriptions.

= 0.9.24 ( May 18, 2017 ): Bugfix Release (v1.0 RC17) =
* Removed PPUL payment model, added advanced setting for "Login required" instead.
* PPU and SIS abbreviations were replaced with "Pay Later" and "Pay Now" accordingly.
* Fixed translation issue on purchase overlay.
* Fixed issues with "drag and drop" and price input on dynamic pricing widget.
* Reduced "Pay Now" threshold for U.S. region.
* Fixed incorrect "Pay Later" validation for dynamic pricing.

= 0.9.23 ( March 02, 2017 ): Bugfix Release (v1.0 RC16) =
* Added support for the LaterPay U.S. system
* Added U.S. Dollar (USD) support in addition to Euro (EUR)
* Fixed issue with the display of "times redeemed" for voucher codes

= 0.9.22 ( December 02, 2016 ): Bugfix Release (v1.0 RC15) =
* Updated identification flow.
* Updated login / logout / signup links.
* Improved plugin update process.
* Removed Browscap library

= 0.9.21 ( November 25, 2016 ): Bugfix Release (v1.0 RC14) =
* Updated sandbox creds.

= 0.9.20 ( November 8, 2016 ): Bugfix Release (v1.0 RC13) =
* Added filters for access check customization.

= 0.9.19 ( October 18, 2016 ): Bugfix Release (v1.0 RC12) =
* Improved update compatibility.

= 0.9.18 ( September 26, 2016 ): Bugfix Release (v1.0 RC11) =
* Increased update compatibility for very old plugin versions.

= 0.9.17 ( September 15, 2016 ): Bugfix Release (v1.0 RC10) =
* Fixed individual article pricing issues relating to formal German locale.
* Fixed post-install color settings bug.

= 0.9.16 ( August 24, 2016 ): Bugfix Release (v1.0 RC9) =
* Fixed issue with attachment images on frontend and in media library.
* Fixed issue with revenue model settings.
* Fixed issue with premium shortcode download.
* Fixed time pass rendering issue after saving.
* Fixed issue with hidden category price settings after changing the category.
* Fixed problem with redeeming voucher codes with German language settings.
* Added color customization options to advanced plugin settings to customize the color of clickable LaterPay elements.
* Replaced LaterPay logo by new version and adjusted default button colors.
* Refined time pass styling.
* Updated browscap library.

= 0.9.15 ( July 12, 2016 ): Bugfix Release (v1.0 RC8) =
* Fixed "Dynamic Pricing" price range for SIS purchases was limited to 5.00 €
* Dropped iframed dialogs in favour of redirection, for broader user support
* Removed include of Yui js library on pages without "LaterPay" elements (except pages with invoice and account links).

= 0.9.14 ( April 7, 2016 ): Bugfix Release (v1.0 RC7) =
* Fixed category default price can't be saved as PPUL
* Fixed category default price is not set automatically
* Fixed issue with download request after purchasing attachment not fired.
* Fixed issue when voucher codes can't be fully deleted.
* Fixed error during category deletion.
* Fixed incorrect attachments purchase url.
* Fixed undefined offset error after update.
* Removed advanced setting for collecting statics data
* Removed statistics functionalities

= 0.9.13 (February 2, 2016 ): Bugfix Release (v1.0 RC6) =
* Fixed videos not displayed in teaser
* Remove deprecated features from plugin backend
* Fixed "More" tag on homepage is ignored for paid posts
* Added "I have a time pass" link below purchase button
* Removed statistics notice in post statistics
* Removed "dashboard" entry from navigation
* Fixed check health functionality
* Fixed price type incorrectly displayed for posts with global price 0 cost.
* Removed frontend ajax nonces
* Fixed admin can purchase paid post in "preview as visitor" mode when plugin in "test visible" mode.
* Adjusted teaser overlay layout and functionality
* Implemented Exceptions handling functionality
* Added revenue model Pay Later (PPU)
* Created Plugin Extension Boilerplate
* Added "comment" field for voucher codes
* Extended list of user agents in browscap cache file
* Added extensive customization of plugin via hooks

= 0.9.12 (July 8, 2015): Bugfix Release (v1.0 RC5) =
* Added feature to allow setting prices in time pass only mode
* Added advanced setting to not contact LaterPay on the homepage
* Added avanced setting to disable check_token on homepage
* Disabled sales statistics
* Fixed fatal error after plugin activation
* Fixed issue with special characters in time pass URLs
* Fixed time Passes being displayed for users, but not in the pricing tab
* Fixed bug that prevented to create voucher code while creating time pass
* Fixed warning: "Cannot modify header information - headers already sent"
* Limited validity of time passes to 1 year
* Fixed images not being displayed in print preview / not printed in Internet Explorer
* Fixed state of "Time Passes Only"-toggle not saving
* Fixed duplicate entries in database
* Adjusted calculation of New Customers metric

= 0.9.11.4 (May 8, 2015): Bugfix Release (v1.0 RC4) =
* Completely revised plugin backend user interface with clearer layout and smoother user interaction
* Added functionality to automatically remove logged page view data after three months
* Added advanced option to manually update the Browscap database from the advanced settings page
* Added advanced option to define the plugin behavior in case the LaterPay API is not responding
* Improved behavior of deleting time passes (only mark as deleted instead of actually removing from database)
* Changed mechanism for including vendor libraries from git submodules to Composer
* Fixed several internals regarding the calculation of sales statistics
* Adjusted copy in teaser content overlay for Time Passes and Single Sale purchases
* Fixed various visual bugs
* Lots of internal structural improvements

= 0.9.11.3 (April 7, 2015): Bugfix Release (v1.0 RC3) =
* Added parameter 'id' to the shortcode [laterpay_time_passes] to display only one specific time pass
* Fixed display of voucher code statistics in pricing tab
* Visual fixes for LaterPay purchase button
* Fixed attachment download via the shortcode [laterpay_premium_download] in caching mode
* Fixed redeeming voucher codes via the shortcode [laterpay_redeem_voucher]
* Fixed undefined index in time_pass partial
* Fixed a few visual bugs in post price form
* More ongoing refactoring of markup and SCSS files

= 0.9.11.2 (March 5, 2015): Bugfix Release (v1.0 RC2) =
* Fixed undefined variable on dashboard
* Removed sourcemaps from production assets

= 0.9.11.1 (March 5, 2015): Bugfix Release (v1.0 RC1) =
* Added capability to also allow users with role 'editor' to see the dashboards in the plugin backend
* Fixed bug that caused link checker plugins to report broken links
* Fixed bug that prevented time passes widget to render, if a specific time pass id was not provided
* Visual fixes for redeem voucher code form in some themes
* Fixed bug that caused custom columns in posts page to not be rendered
* Improved dashboard behavior: running Ajax requests are aborted now, when changing the dashboard configuration
* Improved performance: do not check LaterPay token on free posts
* Removed default values for VAT, which were made obsolete by VATMOSS
* Removed filters from plugin config, because of recent introduction of advanced settings page
* Removed commented out function to switch the default currency
* Lots of internal refactoring and clean-up

= 0.9.11 (February 25, 2015): Time Pass Additions Release =
* Added option to allow only time pass purchases or time pass and individual post purchases
* Added dashboard page for time pass customer lifecycle that shows how many time passes are sold and active, and when
  the currently active time passes will expire
* Added shortcode for rendering time passes
* Added option to have the plugin visible or invisible for visitors in test mode
* Added advanced setting for defining unrestricted access for a user role on a per category basis
* Added proper handling of subcategories for time pass access checks
* Added proper handling of subcategories for category prices
* Added separation of analytics data between data collected in test mode and in live mode
* Fixed bug where category-specific time pass would give access to entire site
* Fixed bug where number of page views was not rendered correctly in post statistics pane
* Fixed a lot of usability and rendering bugs of the dynamic pricing widget
* Fixed bug where custom position of purchase was not respected in admin preview
* Fixed bug where custom position of time passes was not respected in admin preview
* Fixed bug with day names in dashboard
* Added missing documentation and fixed inconsistencies in coding style
* The post statistics pane is now rendered again in debug mode after WordPress update 4.1.1 was released

= 0.9.10 (January 21, 2015): Gift Cards Release =
* Added gift cards for time passes to allow giving away time passes as a present
* Added two shortcodes: [laterpay_gift_card] to render gift cards and [laterpay_redeem_voucher] to render a form for
  redeeming gift card codes.
* Changed time pass behavior to render below the content by default
* Added shortcode [laterpay_time_passes] as alternative for the action 'laterpay_time_passes'.
* Added shortcode [laterpay_account_links] and action 'laterpay_account_links' to render stylable links to log in to /
  out of LaterPay
* Implemented filters for dashboard
* Fixed various bugs related to the dashboard
* Changed config mechanism to use a WordPress settings page for advanced settings
* Added support for caching plugin WP Rocket
* Restored option to give unlimited access to a specific user group
* Fixed bug that shortcode [laterpay_premium_download] always uses global default price
* Fixed bug where teaser would not save with price type "global default" and "category default"
* Fixed bug where its price could not be updated after a post was published
* Fixed bug where post statistics pane was not visible
* Fixed bug where Youtube videos in paid content are not loaded
* Fixed bug where '?' was appended to the URL
* Fixed bug where the category default price was not automatically applied, if the category affiliation of a post changed
* Various bug fixes on dynamic pricing widget
* Various smaller bug fixes
KNOWN BUGS:
* The post statistics pane is not rendered in debug mode because of a WordPress bug that will be resolved with WP 4.1.1

= 0.9.9 (December 2, 2014): Time Passes Release =
* Added time passes and vouchers for selling access to the entire site or parts of it for a limited amount of time
* Added sales dashboard (pre-release) for monitoring sales performance
* Added quality rating functionality to let users who bought an article rate it on a five-star scale
* Purchases from shortcode now directly trigger a download, if it is an attachment
* Improved functionality of dynamic pricing widget (added option to enter exact price values, added option to restart
  dynamic pricing, automatically adjust scaling of y-axis, depending on revenue model, etc.)
* Fixed bug that broke the installation ("Unrecognized Address in line 78")
* Fixed loading of youtube videos in paid content
* Around 8784126852 other small bugfixes and improvements
KNOWN BUGS:
* Shortcode always uses global default price https://github.com/laterpay/laterpay-wordpress-plugin/issues/503

= 0.9.8.3 (October 28, 2014): Bugfix Release =
* Added bulk price editor to make editing large numbers of posts easier
* Fixed saving of global default and category default prices with German number format
* Fixed bug where user was not immediately forwarded to purchases content but had to click purchase button a second time
* Fixed IPv6 bug in logger / debugger functionality
* Fixed plugin mode toggle
* Fixed loading of youtube videos in paid posts
* Fixed displaying of custom teaser images in laterpay_premium_download shortcode
* Ensured shortcode plain text is hidden to visitors in test mode
* Improved server-side validation of forms

= 0.9.8.2 (October 9, 2014): Integration Support Release =
* Added debugger pane to help with integration of plugin (pane is displayed in debug mode: define('WP_DEBUG', true);)
* Documented UI options and shortcode usage in appearance tab
* Made post statistics logging compatible with page caching
* Ensured that LaterPay can be enabled on attachment pages
* Extended file protection in paid posts to all files on current host
* Disabled option to select currency as currently only Euro is supported

= 0.9.8.1 (September 30, 2014): Bugfix Release =
* Made sure the LaterPay client is included in the release

= 0.9.8 (September 30, 2014): Single Sales Release =
* Added option to sell content as single sale (SIS), allowing prices up to 149.99 Euro
* Added configuration option for enabled post types in appearance tab
* Added the action 'laterpay_invoice_indicator' to render the invoice indicator from within a theme
* Huge improvements on RAM consumption and CPU usage
* Ensured compatibility with WordPress 4.0
* Added plugin icon for WordPress 4.0 plugins page
* Rewrote all CSS using Stylus CSS preprocessor
* Rewrote all Javascript to encapsulate all variables and functions
* Added hint text for premium posts to feeds
* Fixed bug caused by checking for edit_plugins capability, which might be disabled
* Restricted querying for categories to taxonomy 'category'
* Improved uninstall action
* Extracted LaterPay PHP client into separate repository and included it as vendor library
* Fixed paths to LaterPay libraries depending on plugin mode
* Extensive refactoring plus various smaller bugfixes and improvements

= 0.9.7.2: Migration to wordpress.org =

= 0.9.7.1 (August 13, 2014): Bugfix Release =
* Removed GitHub plugin updater to switch plugin over to wordpress.org plugin release channel
* Fixed bugs in multi-layer pricing logic (global default, category default, individual price)
* Fixed minor bug on post add / edit page that would trigger a Javascript confirm message when saving
* Revised user interface to work on tablet resolutions
* Fixed preview mode for paid posts
* Disabled autoupdates for Browscap and removed requirements check for writable cache folder
* Disabled rendering of post statistics, if a page includes multiple single post pages
* LaterPay CSS and JS assets are now only loaded, if a post can be purchased
* Various smaller bugfixes and improvements

= 0.9.7 (August 8, 2014): Production-readiness release IV =
* Added support for all standard as well as custom post types
* Instead of modifying the_title, we now prepend a purchase button to the post content to prevent various compatibility issues
* Added the action 'laterpay_purchase_button' to render the purchase button from within a theme
* Added shortcode to align premium content shortcodes
* Changed advanced settings mechanism from file-based to WordPress filters
* Increased robustness of installation and activation procedure
* Replaced custom code with native WordPress functions wherever possible
* Improved performance / reduced memory footprint of plugin
* Improved security of plugin (validation, sanitizing, access to files)
* Prefixed all class names, variables etc. to avoid collisions with other plugins
* Changed internal coding style to adhere to WordPress standards
* Lots of smaller bugfixes and improvements

= 0.9.6 (July 21, 2014): Production-readiness release III =
* Included public Sandbox API credentials supplied by default
* Fully implemented planned roles and capabilities model
* Revised pricing form in add / edit post page
* Removed superfluous handle from dynamic pricing widget
* Added shortcode to render nicely styled links to premium content related to a post
* Added contextual help to all backend pages
* Fixed problem where re-activating the plugin forwarded to the getStarted tab
* Added submenu links to the admin menu
* Added two columns to posts table that indicate price and price type of each post
* Tested and established compatibility with PHP 5.2.4
* Revised README to comply with WordPress standards
* Added option to switch off auto-updating of browscap
* Secured plugin folders against external access
* Extended list of protected filetypes by popular audio, video, and ebook filetypes
* Prefixed all classes and functions with 'LaterPay'
* Improved requirements check during installation
* Several smaller bugfixes

= 0.9.5.1 (July 10, 2014): Bugfix release =
* Fixed purchase button
* Fixed rendering of paid posts overlay on smartphones
* Added option to choose between automatic and manual updating of browser detection library browscap
* Secured plugin folders against external access by adding an empty index.php file to each folder
* Added versioning to LaterPay icon font to ensure cache invalidation on updates

= 0.9.5 (July 9, 2014): Production-readiness release II =
* Made plugin compatible with page caching solutions like WP Super Cache
* Redesigned overlay for previewing paid content
* Added more fine grained over amount of text previewed behind overlay
* Bugfix for auto-updating of browser detection library
* Improved internal use of standard WP APIs (transport, wp_localize_script)
* Added price of posts to posts table in admin backend
* Added more flash messages for system feedback
* Ensured the Buyers bar chart properly scales to 1 (100%)
* Added possibility to hide / show the statistics pane on the view post page
* Switched to loading minified version of YUI
* Renamed views and several variables to be more self-explanatory
* Added an already cached copy of browscap library to the plugin
* Added uninstall.php file that takes care of wiping the database from all data added by the plugin,
  when the plugin gets deactivated and then uninstalled
* Fixed notices that broke the activation process in debug mode
* Fixed bug in getStarted tab that showed an error message that Merchant ID or API Key is not valid, if it was not entered yet

= 0.9.4.2 (June 29, 2014): Bugfix release =
* Removed superfluous function argument for saving the teaser content that caused a warning

= 0.9.4.1 (June 28, 2014): Bugfix release =
* Fixed visibility of plugin to visitors in test mode

= 0.9.4 (June 27, 2014): Production-readiness release =
* Modified behaviour of plugin to be not visible to visitors in test mode
* Added switch to post page, to allow admin users to preview their settings like a visitor
* Added mechanism to ensure that configurations are properly migrated on plugin updates
* Updated price validation to comply with the LaterPay terms and conditions for Pay Now (0.05 - 5.00 Euro)
* Removed questions callout from account tab
* Applied a few visual fixes

= 0.9.3.3 (June 26, 2014): Post-migration release =
* Updated LATERPAY_ASSETS_PATH constant to include '/static'

= 0.9.3.2 (June 26, 2014): Pre-migration release =
* Updated configuration for auto-update functionality to allow migration to new public repo

= 0.9.3.1 (June 25, 2014): Bugfix release =
* Fixed loading of YUI library
* Several smaller visual fixes

= 0.9.3 (June 25, 2014): Code quality release =
* Dramatically reduced memory consumption of browser detection and added auto-updating for browser detection library
* Fixed bug that caused free images to be encrypted
* Fixed bug related to loading API key
* Restricted API calls and other plugin activity to paid posts
* Improved documentation
* Added LaterPay contracts for requesting LaterPay Live API credentials to Account tab
* Made logging function compatible with IPv6
* Refactored plugin to properly register and enqueue Javascript and CSS files
* Added handling for invalid prices
* Added option to define file types protected against direct download in config.php
* Refactored laterpay.php and several controllers
* Removed Javascript and CSS files that are not used anymore

= 0.9.2 (June 13, 2014): Bugfix release =
* Fixed visual glitches of switch

= 0.9.1 (June 13, 2014): Code quality release =
* Removed vendor libraries for HTTP requests and switched to using native WP functionality

= 0.9 (June 11, 2014): Improved maintenance release =
* Added mechanism for automatic plugin updates from official LaterPay repository on github
* Added mechanism for migrating the database on plugin updates
* Added mechanism for clearing application caches on plugin updates
* Added mechanism to prevent config.php from being deleted on plugin updates
* Added requirements check on plugin installation
* Improved layout of account tab in plugin backend
* Improved German translations

= 0.8.2 (June 5, 2014): Bugfix release =
* Extended truncate function to remove HTML comments when auto-generating teaser content
* Made sure flash message warning about missing teaser content is visible
* Removed useless wrapper div#post-wrapper in singlePost
* Added functionality to generate config.php with unique salt and resource encryption key from config.sample.php on setup
* Fixed database error in statistics logging that occurs if one user visits a post multiple times on the same day

= 0.8.1 (June 4, 2014): Bugfix release =
* Made plugin backwards compatible with PHP >= 5.2
* Added rendering of invoice indicator HTML snippet to appearance tab
* Changed auto-generation of teaser content from batch creation on initialization of plugin to on-demand creation on first view or edit of post
* Added pointers to hint at key functions
* Fixed bug related to printing
* Exchanged full version of browscap.ini by its much smaller standard version

= 0.8 (May 27, 2014): First release for beta customers =
* Updated LaterPay PHP client to API v2
* Added separate inputs for Sandbox Merchant ID and Live Merchant ID to Account tab
* Changed Merchant ID input in Get Started tab to Sandbox Merchant ID input
* Added a simple passthrough script that checks authorization for file downloads
* Added a constant to config.php that lets you define a user role that has unrestricted access to all paid content
* Added script that doesn't load jQuery if it's already present
* Changed treatment of search engine bots to avoid cloaking penalties; removed toggle for search engine visibility from appearance tab

== Upgrade notice ==

= 2.1.0 ( September 12, 2018 ) =
Voucher Improvements, Backward Compatibility and Fixed Bugs with LaterPay functionality.

== Arbitrary section ==

The LaterPay WordPress plugin is one possible implementation of the LaterPay API that is targeted at the typical
needs of bloggers and small to medium-sized online magazines.
You can — and are highly welcome — to modify the LaterPay plugin to fit your requirements.

If you are an expert WordPress user who is comfortable with web technologies and want to explore every last possibility
of the LaterPay API, you may be better off by modifying the plugin or writing your own integration from scratch.
As a rule of thumb, if you employ a whole team of developers, it is very likely that you may want to make a few
modifications to the LaterPay WordPress plugin.

If you have made a modification that would benefit other users of the LaterPay WordPress plugin, we will happily have a
look at your work and integrate it into the official plugin.
If you want to suggest a feature or report a bug, we are also looking forward to your message to wordpress@laterpay.net

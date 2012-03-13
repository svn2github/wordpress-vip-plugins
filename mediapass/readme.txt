=== MediaPass ===
Contributors: joehoward, matthewsacks
Donate link: http://mediapass.com
Tags: billing, content monetization, earn money, media pass, mediapass, member, membership, monetize, overlay, payments, paywall, premium content, registration, subscribe, subscriptions,
Requires at least: 3.0
Tested up to: 3.3.1
Stable tag: 1.0

Enable subscriptions and billing with ease using the MediaPass plugin. Earn money by charging for your content such as articles, blog posts and video.

== Description ==

MediaPass gives WordPress users an easy way to turn on subscriptions.  With the MediaPass plugin, publishers can earn money by charging for their premium content, without turning on a site-wide registration system or directing end-users to a third party. Sign up for a MediaPass account and use the plugin to manage subscription pages, or even for specific articles or posts, with the click of a button. WYSIWYG buttons are added to your content window to provide an easy way to insert subscriptions to any chosen content on your website.  Merchant accounts are not required for websites using MediaPass.  Integrate your MediaPass.com account with your WordPress website and watch your revenue grow.

== Installation ==

If you do not have a MediaPass account, you must register one before installing the plugin. Go to (http://www.mediapass.com) to register an account. Visit the FAQ for more information. Once you have registered an account, follow these directions:

1. Download the plugin from the WordPress plugin directory or from (http://www.mediapass.com/wordpress)
2. Upload the mediapass folder to the /wp-content/plugins/ directory
3. Activate the plugin through the 'Plugins' menu in WordPress

You can also install the plugin by the following: 

1. In your WordPress dashboard, go to the 'Plugins' menu then click 'Add New'
2. Search for 'mediapass'
3. Install and activate

== Frequently Asked Questions ==

= How do I get started? =

If you do not have a MediaPass account, you must register one before installing the plugin. Go to (http://www.mediapass.com) to register an account. Note: You only need to submit the four required fields on the initial signup page. There is no need to complete any subsequent steps to start using your MediaPass subscription plugin.Then, follow the simple steps on the 'Installation' tab. We also recommend watching this short video (http://youtu.be/jBuDuVGsG_k).

= How do I use the page overlay option? =

Highlight the text within your content window that you would like to use as a teaser. Click the MediaPass "Overlay" button of your wysiwyg content window.

= How do I use the in-page option? =

Highlight the text within your content window that you would like to hide for those not signed up for access to your content. Click the MediaPass "In Page" button of your wysiwyg content window.

= How do I use the video overlay option? =

Click the MediaPass "Video" button of your wysiwyg content window. Paste in your video where it says "Paste Your Video Code Here". You can also set the delay and title within the WordPress shortcode you see.

= Where can I learn more about using MediaPass and the plugin? =

For more information, please visit http://www.mediapass.com/wordpress

== Screenshots ==

1. Highlight the content you wish to protect, then click on the subscription option you wish to use. The content will become wrapped by shortcodes.
2. Screenshot 2
These are the three MediaPass subscription options. In order from left to right, the buttons are for the following options: page overlay, in-page, video overlay.

== Changelog ==

= 0.9.5 =
* Upon successful association of MediaPass account with plugin, activate the site and set the default mode to "exclude" to accomodate new defaults
* Remove unused code from menu_default
* Remove old comments regarding unused code removal
* Migrate to production API

= 0.9.4 =
* Enable account deauthorization process.

= 0.9.3 =
* Ajax now validates nonces.  Different nonce seed for each page initiating the action.
* Ajax handlers now validates capabilities.

= 0.9.2 =
* Converted all NONCE generation to MediaPass_Plugin::nonce_for($action_specific_nonce)
* Migrated is_good_post() to is_valid_http_post_action($action_specific_nonce)
* Removed unused code from menu_placement() - page has become purely instructional
* Added icon to main menu
* Updated version to 0.9.2





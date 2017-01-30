# Civil Comments #
**Contributors:** civilco, jjeaton, reaktivstudios  
**Tags:** comments, community, civil, civil comments, spam, comment spam, spam comments, anti-spam, moderation, comment moderation, moderate comments, trolls  
**Requires at least:** 4.2  
**Tested up to:** 4.7  
**Stable tag:** 0.2.1  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Civil Comments is a subscription service that lets you host civil discussions and debates on your site without the usual spam, harassment, and abuse.

## Description ##

Civil Comments is a subscription service that lets you host civil discussions and debates on your site without the usual spam, harassment, and abuse. Built to integrate seamlessly with WordPress, Civil Comments gives site owners and developers control over how their community looks and runs.

*A subscription to Civil Comments is required to use this plugin.*

[vimeo https://vimeo.com/167691566]

### Get rid of moderation headaches ###
Civil Comments uses a patent-pending peer review system to keep comments non-toxic and fun—even at massive scale.

### Define your style ###
Make your comments match the look and feel of your page with fully customizable CSS.

### Speed up your page loads ###
Civil Comments was built in modern, progressive React.js, with blazing-fast server-side rendering and the smallest file size of any full-featured drop-in platform.

## Installation ##

1. Upload `civil-comments` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Check the box to "Enable Civil Comments" and add your publication slug to begin using Civil Comments on your site.

## Changelog ##

### 0.2.1 ###
* Dev - Don't deactivate on WordPress.com VIP.

### 0.2.0 ###
* Feature - Add hide comments until clicked.
* Fix - Login and Logout URLs didn't work with unauthenticated users.
* Fix - Refactor comments.php to only require a single template tag `show_civil_comments()`.
* Dev - Add login/logout url filters and custom js hook to support analytics integration.
* Dev - Add inline hook documentation.
* Dev - Allow loading a custom template from the theme. Create `templates/civil-comments.php` in your active theme to use a custom template.
* Dev - Tested compatibility with 4.7.

### 0.1.0 ###
* Initial release.

## Screenshots ##

### 1. The Civil Comments comment form. ###
![The Civil Comments comment form.](http://ps.w.org/civil-comments/assets/screenshot-1.png)

### 2. Moderating submitted comments before a comment is posted. ###
![Moderating submitted comments before a comment is posted.](http://ps.w.org/civil-comments/assets/screenshot-2.png)

### 3. The Civil Comments settings page. ###
![The Civil Comments settings page.](http://ps.w.org/civil-comments/assets/screenshot-3.png)


## Frequently Asked Questions ##

### Which hooks are available? ###

* ** civil_login_url ** - Override the login page url, defaults to the standard `wp_login_url()` which can also be filtered.
* ** civil_logout_url ** - Override the logout page url, defaults to the standard `wp_logout_url()` which can also be filtered.
* ** civil_custom_js ** - Add custom JS to the Civil initialization, for analytics integration or other custom code.

### How do I integrate analytics? ###

Use the `civil_custom_js` action. Here is an example to be used in your theme's functions.php:

    function prefix_civil_integrate_analytics() {
    ?>
        function myLogEventFunction (eventName, eventData) {
            console.log("Civil event called");
            console.log("Event Name:", eventName);
            console.log("Event Data:", eventData);

            // ...add event to your analytics platform
        }
        Civil({ logEvent: myLogEventFunction });
    <?php
    }
    add_action( 'civil_custom_js', 'prefix_civil_integrate_analytics', 10 );

### Can I use a custom comments template? ###

Yes, in your theme, just create a new template at `templates/civil-comments.php`. The template can be completely custom, just place the `show_civil_comments()` template tag in that file where you want the comments to be displayed. The default template is located inside this plugin at `templates/civil-comments.php`.

### Can I hide the comments until clicked? ###

Yes. In the Civil Comments setting page, check the box next to `Hide Comments Until Clicked`. Comments will be hidden until the user clicks the comments button.

## Upgrade Notice ##

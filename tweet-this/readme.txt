=== Tweet This ===
Contributors: richardxthripp
Donate link: http://richardxthripp.thripp.com/donate
Tags: twitter, tweets, url shortening, urls, links, social, connections, networking, sharing, bookmarking
Requires at least: 1.5
Tested up to: 2.7
Stable tag: 1.2.3

Adds a "Tweet This Post" link to every post and page, so your readers can share your blog entries on their Twitter accounts with ease. Shortens URLs in advance.

== Description ==

Adds a "Tweet This Post" link to every post and page. Shortens URLs in advance through <a href="http://th8.us/">Th8.us</a>, eating up only 19 of 140 characters. Also included: Plurk This Post, Digg This Post, and Ping This Post. Includes the post's title after the link (can be customized). If your titles are really long, they get cut off at 136 characters with "...". Customize under Settings > Tweet This. Includes your choice of six Tweet This / Plurk This / Digg This / Ping This buttons.

Normally, posting a link to Twitter takes up a lot of space. Though they shorten URLs with TinyURL, it doesn't happen till after you post your tweet, so the length of the original URL takes away from your 140 characters.

While your readers might go to TinyURL.com, copy and paste the blog post's permalink, shorten the URL, copy that new URL, go to Twitter, and paste it into the box, this plugin merges all that into one step, using the Twitter API and Th8.us, an external URL trimming service I created. I didn't like how long TinyURL's URLs are, so I made Th8.us. Its URLs are 19 characters instead of TinyURL's 25, giving you more space to write about the blog post.

This plugin fetches a shortened URL from Th8.us for each of your blog posts' permalinks server-side, then displays a link to Twitter for each post, with a small Twitter logo. This is done automatically for each post as needed, but the shortened URLs are cached (as a custom field in the postmeta table) to keep load times fast. The cached records are updated or deleted as needed when you edit a post's permalink, delete a post, change your site's permalink structure, or change URL services.

You can pick Th8.us, is.gd, urlb.at, Zi.ma, bit.ly, Metamark, Snurl, Tweetburner, or TinyURL as your short URL service. Or, you can use local URLs, like http://yourblog.com/?p=123.

Tweet This is extensible: you can edit the link text, tweet text, and popup title text under Settings > Tweet This, as well as restricting the links to single posts or pages. 

`tweet_this_text_link()` : Simple implementation. Echoes a text link to Twitter.

`tweet_this()` : Same as above, but with 16x16 Twitter "t" icon. This is the default, and is only useful if you disable auto-insertion.

`tt_plurk_this()` : Plurk This link.

`tt_digg_this()` : Digg This link.

`tt_ping_this()` : Ping This link.

`tweet_this_url()` : Echoes the Tweet This URL, which is like http://twitter.com/home/?status=http://37258.th8.us+Tweet+This by default.

`tweet_this_short_url()` : Just echoes the short URL for the post (Th8.us, TinyURL, etc.), cached if possible.

`tweet_this_trim_title()` : URL-encodes `get_the_title()`, truncates it at the nearest word if it's overly long, and echoes.

You can prefix the last three functions above with `get_` to return the data without echoing, for further manipulation by PHP.

If you have a post or page for which you do not want a Tweet This link displayed, add a custom field titled `"tweet_this_hide"` with the value of "true".

Copyright 2008-2009  Richard X. Thripp  ( email : richardxthripp@thripp.com )
Freely released under Version 2 of the GNU General Public License as published
by the Free Software Foundation, or, at your option, any later version.

== Installation ==

1. Upload the `tweet-this` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. If you're using WordPress MU and want this plugin active for all blogs, move `tweet-this.php` to `/wp-content/mu-plugins/` at this point.
4. Tweet This icons should automatically appear on every post and page! Go to Settings > Tweet This to change stuff.
5. Optionally, delete readme.txt and the screenshots from the tweet-this folder to save space on your server.

== Version History ==

1.2.3: 2009-02-02: Digg and Ping.fm support added: Digg and Ping This Post links and buttons can be enabled in the settings.

1.2.2: 2009-02-01: Plurk support added: Plurk This Post links and buttons can be enabled in the settings.

1.2.1: 2009-01-31: 6 Tweet This buttons by Sascha added, Zi.ma added to URL services, and local fallback code added in case an external URL service goes down.

1.2: 2009-01-30: Full customization options added, local URLs now allowed, Metamark and Tweetburner added, Twitterific bird replace with Twitter logo (by Corey Marion's request) which is moved to bottom-right, small link is now default, "Hide Tweet This on pages" option added, "www." prefixing option added, replacement logic for characters like &#8221; (") added, cleaned up code.

1.1.1: 2008-09-11: I fixed an oversight from 1.1, so `tweet_this_small()` now works properly. If you stick with 1.1, you have to use echo `tweet_this_small()` instead to get the link plus icon to show up in your theme.

1.1: 2008-09-11: Added a ton of stuff. Short URLs are cached now, you can disable automatic insertion, tweet text includes post title, and there are configuration options. Also, the icon is 24-bit color. This bumps the file size up (10KB from 4KB), but it looks much cleaner.

1.0: 2008-09-01: First release, simple plugin with no config options.

== Frequently Asked Questions ==

= Why not start Tweet This URLs with "www." instead of "http://"? =

Though it saves space and is the behavior of previous versions of Tweet This, Twitter clients such as TweetDeck will not convert such URLs into links.

= Why is all your HTML and CSS squished together? =

To be insanely efficient! If eliminating non-required tabs and spaces saves 100 bytes per page load, and you get 10,000 per day, you've just saved 102 minutes of your visitors' time per month, assuming they're all on dialup and you're not gzipping.

= What's with all the `get_` functions? =

Like in WordPress, the default Tweet This functions echo the result. Most can be prefixed with `get_`, which returns without echoing, so you can manipulate the data before displaying it.

= How does the cache work? =

Cached short URLs are saved to the postmeta table when a visitor views posts. For any future pageloads, those URLs are loaded, instead of pinging the Th8.us server (or is.gd, TinyURL, etc.). This works, because as long as the post's permalink doesn't change, the short URL from the third-party service doesn't change.

The cache is invalidated by setting the existing short URLs in the postmeta table to "getnew" as needed. By reusing the old fields instead of replacing them, I don't bump up the `meta_id` counter needlessly. When the next person visits that post, the `get_tweet_this_short_url` function in Tweet This sees this and gets a new short URL.

What triggers a cached URL as invalid? When you save a post (including editing and publishing), the cache is invalidated in case you changed the permalink. Secondly, when you change URL services under Settings > Tweet This or change permalink structures under Options > Permalinks, all the cached URLs are set to "getnew". If you move your blog to a different directory or domain name, just change URL services and then change back to trigger a refresh on the cache.

When you deactivate the plugin, all the cached URLs are deleted.

For more details, look at these functions in tweet-this.php:

`flush_tt_cache`
`delete_tt_cache`
`global_flush_tt_cache`
`global_delete_tt_cache`
`get_tweet_this_short_url`

= Why doesn't Tweet This show when I preview a draft post? =

Because I'd have to fetch a short URL if it did, and the permalink for the post isn't set yet. It would be something like /?p=1, which would just waste an entry in TinyURL or another service's database.

= Which short URL service do you recommend? =

Th8.us of course. I created it, and it's going to be around for hundreds of years. is.gd is good and short, if you don't mind your short URLs being case-sensitive. TinyURL has a slow API. I tested it, and it takes six seconds to load a page with ten Tweet This links on it if the TinyURLs aren't cached.

= Can I just use the Tweet This functions without it adding icons to my blog? =

Sure! Activate the plugin, go to Settings > Tweet This, uncheck "Automatically insert Tweet This links," and click "Save Options."

= If I change URL services, will the old URLs continue to work? =

Yes. The short URLs are on third-party servers (Th8.us, TinyURL, is.gd, etc.), and they should never delete them.

= Why can't Tweetburner URLs have the "www." prefix? =

Because Tweetburner does not work with the www subdomain; use it and your URLs just redirect to the Tweetburner home page.

== Screenshots ==

1. Tweet This options page.
2. URL services list.
3. A "Tweet This Post" link.
4. One of the 6 Tweet This buttons in action.
5. Arriving at Twitter, your reader can start typing about your blog post right away as the cursor is already flashing to the right of the trimmed URL.
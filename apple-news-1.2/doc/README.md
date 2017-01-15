#Publish to Apple News

##Installation

Installing the Publish to Apple News plugin is similar to the process of
installing other WordPress plugins. Simply perform these steps:

1. Download the Publish to Apple News plugin.
2. Upload the entire “apple-news” folder to the “./wp-content/plugins” directory
   on your web server.
3. Activate the Publish to Apple News plugin using the “Plugins” menu in
   WordPress.

Once activated, the "Apple News" menu should appear in your WordPress Admin
panel.

###Troubleshooting: Resolving Potential Permissions Issues

In most cases, the Publish to Apple News plugin should function immediately once
installed. However, in some cases the default permissions WordPress has set for
the plugin directory may be too restrictive to allow plugins to function
properly. If you encounter errors, you may need to modify the permissions of
both the “./apple-export/workspace” and the  “./apple-export/workspace/tmp”
directories. In most cases, setting permissions for these two directories to
“775” will enable plugins to function properly. For additional information and
detailed instructions for setting WordPress directory permissions, please see
[this WordPress technical post](http://codex.wordpress.org/Changing_File_Permissions).

##Configuration

Once the Publish to Apple News plugin has been installed and activated, the next
step is configuration. To begin, choose *Settings > Apple News* from the
WordPress Admin panel.

###Publishing your WordPress Site to Apple News

This plugin is not responsible for setting up or managing your News Publisher
channel with Apple, nor does using this plugin guarantee that Apple News will
accept your content. Some information and links to documentation are provided
here to point you in the right direction for this process. Please see the Apple
Developer and Apple News Publisher documentation and terms on Apple’s website
for complete information.

To enable content from your WordPress blog to be published to your Apple News
channel, you must obtain and enter your Apple News API credentials from Apple to
enable your WordPress content to be published to Apple News.

To obtain these credentials, click the “Apple News documentation” link at the
top of the “API Settings” subsection of *Settings > Apple News*. This link will
direct you to instructions for obtaining your credentials. Once you receive them
from Apple, enter your private API Key, Secret, and Channel settings into the
“Apple News API” fields. Once entered, you can begin publishing WordPress site
content to Apple News.

###General (Site-wide) Settings

Once your WordPress site has been linked to your Apple News channel, your next
step is to configure site-wide general settings that determine how your content
will be published to Apple News. You may want to review these site-wide settings
on occasion.

####Choosing How Content is Published to Apple News

The most important setting is the “Automatically Publish to Apple News” setting,
located at the bottom of the “Apple News API” subsection of *Settings > Apple
News*.

By default, this option is set to **Yes**, so that whenever you publish or
update an article on your own WordPress site, the article will also be
automatically pushed to your Apple News channel, without any additional action
or customization on your part. In most cases, this setting is recommended.

If you would instead prefer more granular control over which content is posted
to your Apple News channel, set “Automatically Publish…” to **No**. With this
setting, new content posted to your site will *not* be automatically pushed to
your Apple News channel. In this case, you will need to manually push any posts
you create, or later modify, to Apple News (this process is described in
“Per-Post Settings” below).

####Set Default Formatting

The “Formatting” subsection of *Settings > Apple News* provides options that
determine the general appearance for how posts in Apple News should appear.
These settings are relatively detailed, giving you a higher level of control
over how your content should appear. Note that if you do not apply specific
preferences, the default settings have been designed so that your content will
have a consistent, professional appearance.

####Advanced Settings

Advanced settings allow you to set custom content height settings. In most
cases, the default settings will provide the best results. However, you can also
enter custom values if desired (values are typographical point sizes). To return
to the default values, simply clear any values entered into these fields.

##Usage

Once the above steps are completed, you’re ready to publish content to Apple
News. If you’ve chosen to automatically publish your blog content to Apple News,
all posts created and updated from this time forward will automatically be
pushed to your Apple News channel. If you’ve disabled Automatic Publishing to
Apple News, the following section describes the tools you’ll use to manually
publish selected posts from your WordPress site to Apple News.

In addition, no matter which Automatic Publishing option you’ve selected, this
section describes how you can override general settings for any specific post as
desired.

###The “Apple News” Admin Panel Menu

The “Apple News” menu (not to be confused with the site-wide *Settings > Apple
News* described in the previous section) provides important tools and settings
for specific posts.

####Controlling Individual Posts

The top section of the “Apple News” pane provides a dashboard-like view that
displays each post you’ve published on your WordPress blog, as well as whether
each post has been published to your Apple News channel, and if so, the date of
the latest update. In addition, individual and bulk controls give you granular
control over each post.

As described above, if you’ve chosen to automatically publish content to Apple
News, each new post that you publish (or later update) on your own WordPress
site will also be automatically pushed to your Apple News channel. In this case,
your primary use of these settings will likely be to override any posts you
choose not to publish to Apple News.

On the other hand, if you’ve chosen *not* to automatically publish your content
to Apple News, you will likely use these settings much more frequently. These
settings will be the tool you’ll use each time you wish to initially publish —
or later update — WordPress posts to Apple News.

####Individual Controls

To publish a single post to your Apple News channel (or perform other actions),
locate the desired post in the list of your locally published content, and hover
your mouse immediately below the post’s title. This will display a contextual
menu, presenting the following choices: “Options”, “Download”, and “Publish”. In
addition, for posts already published to Apple News, two more choices will be
displayed: “Delete from Apple News” and “Copy News URL“.

Choosing “Publish” or “Delete from Apple News” will either push the current
version of the selected post to Apple News, or permanently remove the post from
Apple News. You can override either choice at any future time (e.g., a post
deleted from Apple News can later be published once again if desired). Note that
once a post has been deleted from Apple News, no further updates will
automatically be published to Apple News, regardless of whether you’ve enabled
“Automatically Publish to Apple News” in your general settings.

The “Download” option will generate a JSON
document describing the selected post to your
browser’s default Downloads location.

Finally, the “Options” menu allows you to create a Pull Quote for the selected
post. In addition to entering a pull quote, you can choose to place it at the
top, middle, or bottom of the selected post.

####Bulk Publishing Controls

Bulk actions can be particularly helpful for pushing older content to your Apple
News channel. This is because Automatic Publishing only takes effect on content
created or modified after the Publish to Apple News plugin has been installed.
Therefore, to push a number of pre-existing posts to Apple News, Bulk Publishing
can save significant time and redundant steps.

*Warning: Using Bulk Publishing for a large number of pre-existing posts at one
time may require significant processing time and server resources. It is
recommended that you test this feature with smaller batches of pre-existing
posts first.*

You can choose to publish several posts to your Apple News channel at once,
using the *Bulk Actions* control located at the top left of this section. Simply
check any number of desired posts, then select “publish” from the Bulk Actions
menu. This will open a page displaying the posts you’ve selected, and the status
of each post as it is pushed to Apple News. Note that while bulk publishing
actions are in progress, you should not close your browser window or navigate
away from this page. Doing so will halt the bulk action in progress.


###Tips

In general, little effort should be required to prepare your content itself for
publishing to Apple News once you’ve configured this plugin to your desired
settings. Even so, these simple tips will help your content look its best:

####Controlling Image Placement

By default, images inserted in your content will be placed inline, and will
follow WordPress placement rules. You may find your images will be presented
better within Apple News if you take the time to set the appearance of each
image. To do this, in the WordPress text editor, click on an image, which will
display a contextual alignment menu. This will provide you with a series of
simple granular controls, allowing you to determine more precisely where each
image will be displayed when your post appears in Apple News.

Note that images smaller than the body width (~1024px) will always be aligned.
If you want to display an image without alignment, make sure it’s big enough.

####Banners

You may also select an image to appear as a banner at the top of your post. To
do this, choose a [featured image](https://en.support.wordpress.com/featured-images/)
by clicking on the “Set Featured Image” link when creating or updating a post.
The image will displayed full width at the top of the selected post.

####Image Galleries

You can add image galleries the same way you do in WordPress, by using the “Add
Media” button in the WordPress editor. Once there, navigate to “Create Gallery”
and choose any images you want. Once done, choose to use “Full Size” images
instead of thumbnails. By default, galleries will be shown as a horizontal
slide; if desired, you can change this to “Mosaic” style in the plugin settings.

####Advertisement

While this plugin is not responsible for managing advertising options for your
Apple News channel, it does provide simple settings to help you prepare your
content for this. Please see the Apple News Publisher and iAds documentation and
terms on Apple’s website for more information about advertising options on Apple
News.

By default, your exported posts should be ready to include advertising content
somewhere in the middle of your content. If you don’t want your WordPress
content to be prepared in this way, simply set the “Advertisement” option in the
plugin settings to **No**.

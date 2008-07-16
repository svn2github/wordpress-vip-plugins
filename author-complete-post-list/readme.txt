=== Author Complete Post List ===
Contributors: zelopes
Tags: author,post,multiple,co-author
Requires at least: 
Tested up to: 2.2
Stable tag: 2.0

This plugin displays the complete post list of one Author, with the total number, including the co-author participations.

== Description ==

Provides an easy way to display the complete list of the an author's posts, including the posts where he's also co-author.
It solves two problems with the author page:

1. It displays all the posts from the author despite any pre-definition of the maximum number of posts to be displayed.
2. It counts the posts where the user is co-author, even if he's not the original poster.

The plugin was created in reference to the <a href="http://codex.wordpress.org/Author_Templates#Sample_Template_File">author.php</a>
example on the Wordpress.

= Usage =

You have two options to call this plugin on your template (for instance the author.php):

1. For the total number of posts of the author include: &lt;?php total_posts($author, $curauth->user_login) ; ?&gt;
2. For the complete list of the author's posts include: &lt;?php full_post_list($author, $curauth->user_login) ; ?&gt;

Note that the arguments are to be writen like that.

= Version 2.0 Note =

This new version includes Internacionalization.
If you have the previous version 0.1 installed you just need to install the new files. You <b>don't need</b> to
change the functions calls since they are compatible.

= Requirements =

You should be aware of the following points:

1. If you are using the <a href="http://wordpress.org/extend/plugins/multiple-authors/">multiple-authors</a> plugin:

   1. The co-author is added automatically once he edits the post.
   2. You can alos add the co-author manually (see the next point).

2. If you are not using the <a href="http://wordpress.org/extend/plugins/multiple-authors/">multiple-authors</a> plugin
or if you want to do it manually:

   1. Add a costum key on the post. Use 'other_author' as key name, and the author login name as value.


== Installation ==

1. Upload author-complete-post-list.php to the /wp-content/plugins/ directory.
2. Activate the plugin through the 'Plugins' menu of WordPress.
3. Place in your templates:

   1. For the total of posts: &lt;?php total_posts($author, $curauth->user_login); ?&gt;
   2. For the list of posts: &lt;?php full_post_list($author, $curauth->user_login); ?&gt;

== Internationalization ==

You may find the POT file available with this distribution to create your own language version.

Besides English, this distribution provides the language versions:

- Portuguese (Portugal)
- French (France)

Contact me on the <a href="http://www.bombolom.com/weblog/wordpress/PluginAuthorCompletePostList2-2007-11-06-00-35.html">Plugin Homepage</a>
if you want to add a new language version.

== Credits ==

Copyright 2007 Jose Lopes

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA

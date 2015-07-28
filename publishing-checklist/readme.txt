=== Publishing Checklist ===
Contributors: fusionengineering, danielbachhuber, davisshaver  
Tags: editorial, checklist, publishing, preflight
Requires at least: 4.2  
Tested up to: 4.2.2
Stable tag: 0.1.0
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html 

Pre-flight your posts.

== Description==

Publishing Checklist is a developer tool for adding pre-flight editorial checklists to WordPress posts. Each time a user saves a post, Publishing Checklist validates that post type's list of tasks to make sure the content is ready for release. Tasks are validated with callbacks you supply when registering tasks.

== Installation  ==

It's a plugin! Install it like any other. 

Once you've done so, you'll need to register the checklist items and callbacks for your site. Here's a simple example that checks for a featured image.

```php
add_action( 'publishing_checklist_init', function() {
	$args = array(
		'label'           => esc_html__( 'Featured Image', 'demo_publishing_checklist' ),
		'callback'        => function ( $post_id ) {
			return has_post_thumbnail( $post_id );
		},
		'explanation'     => esc_html__( 'A featured image is required.', 'demo_publishing_checklist' ),
		'post_type'       => array( 'post' ),
	);
	Publishing_Checklist()->register_task( 'demo-featured-image', $args );
});
```

== Frequently Asked Questions ==

= Where will the checklist appear? =

On Manage and Edit post screens.

= Does the plugin come with any default checklists? =

Not yet.

== Screenshots ==

1. Checklist summaries will be displayed within a column on the Manage post screen.

2. Checklists will also be displayed within the Publish metabox on the Edit screen.

== Changelog ==

= 0.1.0 (June 26, 2015) =

* Initial release.
* [Full release notes](http://fusion.net/story/154952/introducing-publishing-checklist-v0-1-0)

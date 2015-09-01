# Speed Bumps #
**Contributors:** fusioneng, goldenapples, noppanit  
**Tags:** content, advertising, recirculation  
**Requires at least:** 3.0.1  
**Stable tag:** 0.1.0  
**Tested up to:** 4.3  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Intelligently insert speed bumps into site content.

## Description ##

Speed Bumps inserts speed bumps into site content based on business needs. This plugin requires code-level configuration.

Need a 300x250 unit inserted 3 paragraphs down on every story greater than 9 paragraphs long? Speed Bumps makes seemingly complex business requests like this simple to implement within your WordPress environment.

Any number of speed bumps can be registered, from graphical elements to advertising units to recirculation modules. Each speed bump inherits a default set of overridable rules, and the speed bump can also dictate its own logic regarding acceptable placement.

To report bugs or feature requests, [please use Github issues](https://github.com/fusioneng/speed-bumps).

## Installation ##

It's a plugin! Install it like any other.

Onec you've installed the plugin, you'll have to register one or more speed bumps in order for it to have any effect. You'll also have to specifically call Speed Bumps to filter your content - the plugin doesn't attach any filters to `the_content` or other hooks by itself.

The simplest way to have Speed Bumps process all of your content and insert speed bumps into content everywhere is simply adding the filter following registration:

```
register_speed_bump( 'speed_bump_sample', array(
	'string_to_inject' => function() { return '<div id="speed-bump-sample"></div>'; },
));
add_filter( 'the_content', 'insert_speed_bumps', 1 );
```

This registration results in the `string_to_inject` value being injected at the first opportunity based on the default rules (e.g. on posts longer than 1200 characters, following the third paragraph OR following the paragraph which contains the 75th word, whichever comes later).

Let's say you wanted the speed bump higher in the content. You could modify the `from_start` parameter to declare that the speed bump can be inserted after the first paragraph (yes, like good engineers, we prefer zero-based indexing).
```
register_speed_bump( 'speed_bump_sample', array(
	'string_to_inject' => function() { return '<div id="speed-bump-sample"></div>'; },
	'from_start' => 0,
));
```

You can also selectively insert speed bumps into a string of content by calling Speed Bumps directly:

```
echo insert_speed_bumps( $content_to_be_inserted_into );
```

## Frequently Asked Questions ##

### What are the default rules? ###

The default options for speed bumps are currently:

- Never insert in a post fewer than 8 paragraphs long, or fewer than 1200 characters.
- Never insert before the the third paragraph, or before 75 words into the post.
- Never insert fewer than 3 paragraphs or 75 words from the end of the article.
- At least one paragraph from an iframe or embed.
- At least two paragraphs from an image.
- At least one paragraph from any other speed bump in the post.

### How to add more specific rules? ###

Adding a custom rule for a speed bump is a matter of defining a function and hooking it to the `speed_bumps_{id}_constraints` filter. The function hooked to that filter will receive several arguments to determine the state of the content, surrounding paragraphs, and other context, and can return `false` to block insertion.

**Simple, stupid example:** You have a speed bump called "rickroll" which inserts a beautiful musical video throughout your content. You _really_ need this viral bump (publisher's words, not yours) so you disable minimum content length and the rules regarding acceptable speed bump distance from start/end of the post. Greedy!  

```
register_speed_bump( 'rickroll', array(
	'string_to_inject' => function() { return '<iframe width="420" height="315" src="https://www.youtube.com/embed/dQw4w9WgXcQ" frameborder="0" allowfullscreen></iframe>'; },
	'minimum_content_length' => false,
	'from_start' => false,
	'from_end' => false,
));
add_filter( 'the_content', 'insert_speed_bumps', 1 );
```

But, maybe that's a little too extreme. You want to show it in certain situations, say, only when the previous paragraph contains the phrase 'give {something} up'. Here's how you would achieve that:

```
add_filter( 'speed_bumps_rickroll_constraints', 'give_you_up', 10, 4 );

function give_you_up( $can_insert, $context, $args, $already_inserted ) {
	if ( ! preg_match( '/give [^ ]+ up/i', $context['prev_paragraph'] ) ) {
		$can_insert = false;
	}
	return $can_insert;
}
```

You could also disable it altogether with this filter (although why you would disable so soon after addition, only Rick Astley himself could answer):

```
add_filter( 'speed_bumps_rickroll_constraints', '__return_false' );
```

### How to remove default rules? ###

Each rule is hooked to that speed bump's "constraints" filter. To remove a rule, simply remove the filter which defines that rule, like these lines which remove the default rules for your speed bump:

```
remove_filter( 'speed_bumps_{id}_constraints', '\Speed_Bumps\Constraints\Text\Minimum_Text::content_is_long_enough_to_insert' );
remove_filter( 'speed_bumps_{id}_constraints', '\Speed_Bumps\Constraints\Text\Minimum_Text::meets_minimum_distance_from_start' );
remove_filter( 'speed_bumps_{id}_constraints', '\Speed_Bumps\Constraints\Text\Minimum_Text::meets_minimum_distance_from_end' );
remove_filter( 'speed_bumps_{id}_constraints', '\Speed_Bumps\Constraints\Content\Injection::less_than_maximum_number_of_inserts' );
remove_filter( 'speed_bumps_{id}_constraints', '\Speed_Bumps\Constraints\Content\Injection::meets_minimum_distance_from_other_inserts' );
remove_filter( 'speed_bumps_{id}_constraints', '\Speed_Bumps\Constraints\Elements\Element_Constraints::meets_minimum_distance_from_elements' );
```

## Changelog ##

### 0.1.0 (July 22, 2015) ###

* Initial release.
* [Full release notes](http://fus.in/1MidK1N)

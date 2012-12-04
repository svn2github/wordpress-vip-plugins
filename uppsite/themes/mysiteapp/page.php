<?php
/**
 * "Page" page
 */
$should_hide_posts = mysiteapp_should_hide_posts();
if (!$should_hide_posts) {
	$uppsite_options = get_option('uppsite_options');
	$paged = (get_query_var('paged') ? get_query_var('paged') : ( isset($page) ? $page : 1 ) );
	if (is_front_page() && (!isset($uppsite_options['option_homepagelist']) || $uppsite_options['option_homepagelist']=='Yes')) {
		// Front page, according to blog settings, and wish to show the posts list like it is a homepage.
		$args = array(
			'showposts' => 10,
			'paged' => $paged,
            'order' => 'desc'
		);
		// Disable "Sticky" in apps?
		if (isset($uppsite_options['option_sticky']) && $uppsite_options['option_sticky'] == "Yes") {
			$sticky = get_option('sticky_posts');
			if (get_bloginfo('version') >= 3.1) {
				// 'caller_get_posts' deprecated
				$args['ignore_sticky_posts'] = 1;
			} else {
				$args['caller_get_posts'] = 1;
			}
		}
		query_posts($args);
	}
}

get_template_part('header');
?><title><![CDATA[]]></title>
<posts>
<?php
if (!$should_hide_posts && have_posts()) {
	$iterator = 0;
	// Avoid 'loop_end' output, if any
	while (mysiteapp_clean_output('have_posts')) {
		// Avoid 'loop_start' output, if any (some plugins make it)
		mysiteapp_clean_output('the_post');
		
		mysiteapp_print_post($iterator);
		
		$iterator++;
	}
	// Comments 
	comments_template();
}
?>
</posts>
<?php
get_template_part('sidebar');
get_template_part('footer', 'nav');
<?php
/**
 * Index page
 */
$show_homepage_display = mysiteapp_should_show_homepage();
$wrap_with_homepage_tags = $show_homepage_display && !mysiteapp_homepage_is_only_show_posts();
$should_hide_posts = mysiteapp_should_hide_posts() || $show_homepage_display;
if ($show_homepage_display) {
    query_posts(array(
        'paged' => 1,
        'posts_per_page' => mysiteapp_homepage_carousel_posts_num(),
        'order' => 'desc'
    ));
}

if (!$should_hide_posts) {
	$uppsite_options = get_option('uppsite_options');
	$paged = (get_query_var('paged') ? get_query_var('paged') : ( isset($page) ? $page : 1 ) );
	
	// Disable "Sticky" in apps?
	if (isset($uppsite_options['option_sticky']) && $uppsite_options['option_sticky'] == "Yes") {
		$sticky = get_option('sticky_posts');
		
		$args = array(
			'posts_per_page' => 10,
			'paged' => $paged,
            'order' => 'desc'
		);
		
		if (get_bloginfo('version') >= 3.1) {
			// 'caller_get_posts' deprecated
			$args['ignore_sticky_posts'] = 1;
		} else {
			$args['caller_get_posts'] = 1;
		}
		// Query without sticky
		query_posts($args);
	}
}
get_template_part('header');
?><title><![CDATA[]]></title>
<posts>
    <?php if ($wrap_with_homepage_tags): ?><homepage><?php endif; ?>
    <?php if (!$should_hide_posts || $show_homepage_display) { get_template_part( 'the_loop' ); } ?>
    <?php if ($wrap_with_homepage_tags): ?></homepage><?php endif; ?>
</posts>
<?php
get_template_part($show_homepage_display ? 'homepage' : 'sidebar');
get_template_part('footer', 'nav');

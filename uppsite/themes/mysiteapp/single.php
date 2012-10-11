<?php
/**
 * Single post Page
 */
get_template_part('header');
?><title><![CDATA[]]></title><?php
if (have_posts()) {
	while (mysiteapp_clean_output('have_posts')) {
		mysiteapp_clean_output('the_post');
		mysiteapp_print_post();
		
		$options = get_option('uppsite_options');
		/*if (isset($options['disqus'])) {
			remove_filter('comments_template', 'dsq_comments_template'); 
		}*/
		comments_template();
	}
}
get_template_part('footer', 'nav');
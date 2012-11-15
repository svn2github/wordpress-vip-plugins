<?php
/**
 * There's some ugly code in mu-plugins/gprofiles.php that redirects anything on profile.php that's not 
 * a whitelisted gprofile page. Let's put it under tools instead.
 */
add_filter( 'coauthors_guest_author_parent_page', function() { return 'tools.php'; } );

/**
 * Auto-apply Co-Authors Plus template tags on themes that are properly using the_author()
 * and the_author_posts_link()
 */
$wpcom_coauthors_plus_auto_apply_themes = array(
		'premium/portfolio',
	);
if ( in_array( get_option( 'template' ), $wpcom_coauthors_plus_auto_apply_themes ) )
	add_filter( 'coauthors_auto_apply_template_tags', '__return_true' );
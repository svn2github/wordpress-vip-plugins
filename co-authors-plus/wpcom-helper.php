<?php
/**
 * Because the guest authors functionality is work in progress,
 * only enable it for specific VIP themes
 */
$coauthors_plus_guest_authors_whitelist = array(
		'vip/cartercenterorg',
		'vip/dawn-urdu',
		'vip/dawn-urdu-mobile',
		'vip/newyorkobserver',
		'vip/sc-beautyhigh',
		'vip/sc-stylecaster',
		'vip/sc-vivant',
		'vip/thoughtcatalog',
		'vip/lifestyle',
		'vip/healthcommon',
		'vip/pmc-movieline',
		'vip/pgoa-ap',
		'vip/pgoa-relish',
		'vip/pgoa-spry',
		'vip/vanguardadvisors',
	);
if ( !in_array( get_option( 'stylesheet' ), $coauthors_plus_guest_authors_whitelist ) )
	add_filter( 'coauthors_guest_authors_enabled', '__return_false' );

/**
 * There's some ugly code in mu-plugins/gprofiles.php that redirects anything on profile.php that's not 
 * a whitelisted gprofile page. Let's put it under tools instead.
 */
add_filter( 'coauthors_guest_author_parent_page', function() { return 'tools.php'; } );

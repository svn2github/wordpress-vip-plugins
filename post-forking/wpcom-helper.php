<?php
// Add capabilities to admin role for the Post Forking plugin
add_action( 'init', function() {
	wpcom_vip_add_role_caps( 'administrator', array(
		'edit_forks',
		'edit_others_forks',
		'edit_private_forks',
		'edit_published_forks',
		'read_forks',
		'read_private_forks',
		'delete_forks',
		'delete_others_forks',
		'delete_private_forks',
		'delete_published_forks',
		'publish_forks',
	) );
} );
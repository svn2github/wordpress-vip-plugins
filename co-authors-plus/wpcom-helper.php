<?php
/**
 * There's some ugly code in mu-plugins/gprofiles.php that redirects anything on profile.php that's not 
 * a whitelisted gprofile page. Let's put it under tools instead.
 */
add_filter( 'coauthors_guest_author_parent_page', function() { return 'tools.php'; } );

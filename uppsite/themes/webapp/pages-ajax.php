<?php
$pages = wp_list_pages(array('echo' => false));
$pagesList = uppsite_format_html_to_array($pages);

print json_encode($pagesList);
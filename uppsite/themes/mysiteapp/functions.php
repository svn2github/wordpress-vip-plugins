<?php
/**
 * Remove all the filters and actions that can ruin the XML format
 */
// Filters 
remove_all_filters('get_sidebar');
remove_all_filters('get_header');
remove_all_filters('get_footer');
remove_all_filters('the_post');
remove_all_filters('comments_number');
// Actions
remove_all_actions('loop_start');
remove_all_actions('loop_end');
remove_all_actions('the_excerpt');
remove_all_actions('wp_footer');
remove_all_actions('wp_print_footer_scripts');
remove_all_actions('comments_array');
<?php
/*
Plugin Name: Cached Related Posts by Category
Plugin URI: Plugin URI: http://www.sandboxdev.com/blog-and-cms-development/wordpress/plugins
Description: Show Related Posts with Caching. 
Author: Jennifer Zelazny
Version: 1.0
Author URI: http://www.sandboxdev.com

Based on the un-cached version:
http://playground.ebiene.de/400/related-posts-by-category-the-wordpress-plugin-for-similar-posts/ 
By Sergej M&uuml;ller
*/

function related_posts_by_category($params, $post_id = 0) {
	global $blog_id;
	$entries = array();
	$output = '';
	$related_posts_expire = 60 * 60 * 24; // Cache data for one day (86400 seconds)
	if (!$post_id) {
		$post_id = $GLOBALS['post']->ID;
	}
	$entries = wp_cache_get($post_id, "$blog_id:related_posts_cached");
	if (false === $entries) {
 		$entries = $GLOBALS['wpdb']->get_results(
		sprintf(
			"SELECT DISTINCT object_id, post_title FROM {$GLOBALS['wpdb']->term_relationships} r, {$GLOBALS['wpdb']->term_taxonomy} t, {$GLOBALS['wpdb']->posts} p WHERE t.term_id IN (SELECT t.term_id FROM {$GLOBALS['wpdb']->term_relationships} r, {$GLOBALS['wpdb']->term_taxonomy} t WHERE r.term_taxonomy_id = t.term_taxonomy_id AND t.taxonomy = 'category' AND r.object_id = $post_id) AND r.term_taxonomy_id = t.term_taxonomy_id AND p.post_status = 'publish' AND p.ID = r.object_id AND object_id <> $post_id %s %s %s",
			(isset($params['type']) === true && empty($params['type']) === false) ? ("AND p.post_type = '" .$params['type']. "'") : '',
			(isset($params['orderby']) === true && empty($params['orderby']) === false) ? ('ORDER BY ' .(strtoupper($params['orderby']) == 'RAND' ? 'RAND()' : $params['orderby']. ' ' .(isset($params['order']) ? $params['order'] : ''))) : '',
			(isset($params['limit']) === true && empty($params['limit']) === false) ? ('LIMIT ' .$params['limit']) : ''
		),
		OBJECT
		);
    	wp_cache_set($post_id, $entries, "$blog_id:related_posts_cached", $related_posts_expire);
    } 	
	if ($entries) { 
		foreach ($entries as $entry) {
			$output .= sprintf(
				'%s<a href="%s" %s title="%s">%s%s%s</a>%s',
				isset($params['before']) ? $params['before'] : '',
				get_permalink($entry->object_id),
				(isset($params['rel']) ? ('rel="' .$params['rel']. '"') : ''),
				$entry->post_title,
				isset($params['inside']) ? $params['inside'] : '',
				$entry->post_title,
				isset($params['outside']) ? $params['outside'] : '',
				isset($params['after']) ? $params['after'] : ''
			);
		}
	} else {
		$output = $params['message'];
	}
	if (isset($params['echo']) === true && $params['echo']) {
		echo $output;
	} else {
		return $output;
	}
}
?>

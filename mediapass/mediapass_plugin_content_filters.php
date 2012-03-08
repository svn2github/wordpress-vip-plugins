<?php

class MediaPass_Plugin_ContentFilters {
	
	function __construct() {
		add_filter('the_content', array(&$this,'mp_content_placement_category_filter'));
		add_filter('the_content', array(&$this,'mp_content_placement_tag_filter'));
		add_filter('the_content', array(&$this,'mp_content_placement_author_filter'));
		add_filter('the_content', array(&$this,'mp_content_placement_exemptions'));
	}
		
	function mp_content_placement_category_filter($content) {
		global $post;
		
		if( ! is_single() ) {
			return $content;	
		}
		
		$selected = get_option(MediaPass_Plugin::OPT_PLACEMENT_CATEGORIES);
		$selected = empty($selected) ? array() : $selected;
		
		if( empty($selected) ){
			return $content;
		}

		$post_categories = get_the_category( $post->ID );
		$post_category_ids = ! empty( $post_categories ) ? wp_list_pluck( $post_categories, 'term_id' ) : array();

		$category_overlap = array_intersect($selected, $post_category_ids);
	
		if( ! empty( $category_overlap  ) ) {
			$content = MediaPass_ContentHelper::enable_overlay($content);
		}		
		
		return $content;
	}

	function mp_content_placement_author_filter($content) {
		global $post;
		
		if( ! is_single() ) {
			return $content;	
		}
		
		$selected = get_option(MediaPass_Plugin::OPT_PLACEMENT_AUTHORS);
		$selected = empty($selected) ? array() : $selected;
		
		if( in_array( $post->post_author, $selected ) ) {
			$content = MediaPass_ContentHelper::enable_overlay($content);
		}		
		
		return $content;
	}
	
	function mp_content_placement_tag_filter($content) {
		global $post;
		
		if( ! is_single() ) {
			return $content;	
		}
		
		$selected = get_option(MediaPass_Plugin::OPT_PLACEMENT_TAGS);
		$selected = empty($selected) ? array() : $selected;

		$tags = get_the_tags( $post->ID );
		$tag_ids = ! empty( $tags ) ? wp_list_pluck( $tags, 'term_id' ) : array();

		$tag_overlap = array_intersect($selected, $tag_ids);
		
		if( ! empty( $tag_overlap ) ) {
			$content = MediaPass_ContentHelper::enable_overlay($content);
		}		
		
		return $content;
	}
	
	function mp_content_placement_exemptions($content) {
		global $post;
		
		if( current_user_can('edit_posts') ) {
			$content = MediaPass_ContentHelper::strip_all_shortcodes($content);
		}		
		
		return $content;
	}
}
?>
<?php
/**
 * Infinite Scroll Theme Assets
 *
 * Register support for @Twenty Eleven and enqueue relevant styles.
 */

add_action( 'template_redirect',      'twenty_eleven_infinite_scroll_enqueue_styles', 25 );
add_action( 'infinite_scroll_render', 'twenty_eleven_infinite_scroll_render' );
add_action( 'init',                   'twenty_eleven_infinite_scroll_init' );

/**
 * Add theme support for infinity scroll
 */
function twenty_eleven_infinite_scroll_init() {
	// Theme support takes one argument: the ID of the element to append new results to.
	add_theme_support( 'infinite-scroll', 'content' );
}

/**
 * Set the code to be rendered on for calling posts,
 * hooked to template parts when possible.
 *
 * Note: must define a loop.
 */
function twenty_eleven_infinite_scroll_render() {
	while ( have_posts() ) : the_post();
		get_template_part( 'content', get_post_format() );
	endwhile;
}

/**
 * Enqueue CSS stylesheet with theme styles for infinity.
 */
function twenty_eleven_infinite_scroll_enqueue_styles() {
	// Add theme specific styles.
	wp_enqueue_style( 'infinity-twentyeleven', plugins_url( 'twentyeleven.css', __FILE__ ), array() );
}
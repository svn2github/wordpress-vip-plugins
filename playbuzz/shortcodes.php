<?php
/*
 * Security check
 * Exit if file accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$options = (array) get_option( 'playbuzz' );
$site_jshead = ( ( ( array_key_exists( 'jshead', $options ) ) && ( '1' == $options['jshead'] ) ) ? true : false );

/**
 * @param $posts
 * @return mixed
 */
function load_feed_js_head( $posts ) {

	if ( empty( $posts ) || is_admin() ) {
		return $posts;
	}

	$found = false;

	foreach ( $posts as $post ) {
		if ( has_shortcode( $post->post_content, 'playbuzz-item' ) ||
			has_shortcode( $post->post_content, 'playbuzz-game' ) ||
			has_shortcode( $post->post_content, 'playbuzz-post' ) ) {
			$found = true;
			break;
		}
	}

	if ( $found ) {
		wp_enqueue_script( 'external-playbuzz-js', '//cdn.playbuzz.com/widget/feed.js' , '', '', false );
	}

	return $posts;
}

if ( $site_jshead && ! is_admin() ) {

	add_action( 'the_posts', 'load_feed_js_head' );
}


/*
 * Item Shortcode
 * Display a specific item in a desired location on your content.
 *
 * usage: [playbuzz-item url="https://www.playbuzz.com/jonathang/players-and-playmates-playoffs"]
 *
 * @since 0.1.0
 */
add_shortcode( 'playbuzz-item', 'playbuzz_item_shortcode' );
add_shortcode( 'playbuzz-game', 'playbuzz_item_shortcode' );
add_shortcode( 'playbuzz-post', 'playbuzz_item_shortcode' );



/*
 * Section Shortcode
 * Display a list of items according specific tags in a desired location on your content.
 *
 * usage: [playbuzz-section tags="Celebrities"]
 *
 * @since 0.1.0
 */
add_shortcode( 'playbuzz-section', 'playbuzz_section_shortcode' );
add_shortcode( 'playbuzz-hub',     'playbuzz_section_shortcode' );
add_shortcode( 'playbuzz-archive', 'playbuzz_section_shortcode' );



/*
 * Shortcode functions
 *
 * @since 0.1.1
 */
function playbuzz_item_shortcode( $atts ) {

	// Load WordPress globals
	global $wp_version;

	// Load global site settings from DB
	$options = (array) get_option( 'playbuzz' );

	// Prepare site settings
	$site_key       = ( ( ( array_key_exists( 'key',               $options ) ) ) ? $options['key'] : str_replace( 'www.', '', wp_parse_url( home_url(), PHP_URL_HOST ) ) );
	$site_jshead    = ( ( ( array_key_exists( 'jshead',              $options ) ) && ( '1' == $options['jshead']      ) ) ? 'true' : 'false' );
	$site_info      = ( ( ( array_key_exists( 'info',              $options ) ) && ( '1' == $options['info']      ) ) ? 'true' : 'false' );
	$site_shares    = ( ( ( array_key_exists( 'shares',            $options ) ) && ( '1' == $options['shares']    ) ) ? 'true' : 'false' );
	$site_comments  = ( ( ( array_key_exists( 'comments',          $options ) ) && ( '1' == $options['comments']  ) ) ? 'true' : 'false' );
	$site_recommend = ( ( ( array_key_exists( 'recommend',         $options ) ) && ( '1' == $options['recommend'] ) ) ? 'true' : 'false' );
	$site_tags      = '';
	$site_tags     .= ( ( ( array_key_exists( 'tags-mix',          $options ) ) && ( '1' == $options['tags-mix']          ) ) ? 'All,'                  : '' );
	$site_tags     .= ( ( ( array_key_exists( 'tags-fun',          $options ) ) && ( '1' == $options['tags-fun']          ) ) ? 'Fun,'                  : '' );
	$site_tags     .= ( ( ( array_key_exists( 'tags-pop',          $options ) ) && ( '1' == $options['tags-pop']          ) ) ? 'Pop,'                  : '' );
	$site_tags     .= ( ( ( array_key_exists( 'tags-geek',         $options ) ) && ( '1' == $options['tags-geek']         ) ) ? 'Geek,'                 : '' );
	$site_tags     .= ( ( ( array_key_exists( 'tags-sports',       $options ) ) && ( '1' == $options['tags-sports']       ) ) ? 'Sports,'               : '' );
	$site_tags     .= ( ( ( array_key_exists( 'tags-editors-pick', $options ) ) && ( '1' == $options['tags-editors-pick'] ) ) ? 'EditorsPick_Featured,' : '' );
	$site_tags     .= ( ( ( array_key_exists( 'more-tags',         $options ) ) ) ? $options['more-tags']  : '' );
	$site_tags      = rtrim( $site_tags, ',' );
	$site_margintop = ( ( ( array_key_exists( 'margin-top',        $options ) ) ) ? $options['margin-top'] : '' );
	$embeddedon     = ( ( ( array_key_exists( 'embeddedon',        $options ) ) ) ? $options['embeddedon'] : 'content' );

	// Set default attribute values if the user did not defined any
	$atts = shortcode_atts(
		array(
			'key'        => $site_key,       // api key allowing configuration and analytics
			'game'       => '',              // defines the item that will be loaded by the IFrame (deprecated in 0.3 ; use "url" attribute)
			'url'        => '',              // defines the item that will be loaded by the IFrame (deprecated in 1.00 ; use id attribute)
			'item'       => '',              // defines the item that will be loaded by the IFrame (added in 1.00 )
			'format'     => '',              // defines the item format (added in 1.00 )
			'info'       => $site_info,      // show item info (thumbnail, name, description, editor, etc)
			'shares'     => $site_shares,    // show sharing buttons
			'comments'   => $site_comments,  // show comments control from the item page
			'recommend'  => $site_recommend, // show recommendations for more items
			'tags'       => $site_tags,      // filter by tags
			'links'      => '',              // destination url in your site where new items will be displayed
			'width'      => 'auto',          // define custom width (added in 0.3)
			'height'     => 'auto',          // define custom height (added in 0.3)
			'margin-top' => $site_margintop, // margin top for score bar in case there is a floating bar
	), $atts );

	// Playbuzz Embed Code
	$code = '';
	if ( 'false' == $site_jshead ) {
		$code = '<script type="text/javascript" src="//cdn.playbuzz.com/widget/feed.js"></script>';
	}

	$code .= '	
		<div class="pb_feed" data-provider="WordPress ' . esc_attr( $wp_version ) . '"
		 data-key="' . esc_attr( $atts['key'] ) . '"		
		 data-tags="' . esc_attr( $atts['tags'] ) . '"
		 data-game="' . esc_url( $atts['url'] . $atts['game'] ) . '"
		 data-item="' . esc_attr( $atts['item'] ) . '"
		 data-game-info="' . esc_attr( $atts['info'] ) . '"
		 data-comments="' . esc_attr( $atts['comments'] ) . '"
		 data-shares="' . esc_attr( $atts['shares'] ) . '"
		 data-links="' . esc_attr( $atts['links'] ) . '"
		 data-width="' . esc_attr( $atts['width'] ) . '"
		 data-height="' . esc_attr( $atts['height'] ) . '"
		 data-margin-top="' . esc_attr( $atts['margin-top'] ) . '"
	';

	if ( 'story' == $atts['format'] ) {
	    $code .= 'data-version=2';
	}

	$code .= '></div>';

	// Theme Visibility
	if ( 'content' == $embeddedon ) {
		// Show only in singular pages
		if ( is_singular() || is_admin() ) {
			return $code;
		}
	} elseif ( 'all' == $embeddedon ) {
		// Show in all pages
		return $code;
	}

}

/*
 * Shortcode functions
 *
 * @since 0.1.4
 */
function playbuzz_section_shortcode( $atts ) {

	// Load WordPress globals
	global $wp_version;

	// Load global site settings from DB
	$options = (array) get_option( 'playbuzz' );

	// Prepare site settings
	$site_key       = ( ( ( array_key_exists( 'key',               $options ) ) ) ? $options['key'] : str_replace( 'www.', '', wp_parse_url( home_url(), PHP_URL_HOST ) ) );
	$site_info      = ( ( ( array_key_exists( 'info',              $options ) ) && ( '1' == $options['info']      ) ) ? 'true' : 'false' );
	$site_shares    = ( ( ( array_key_exists( 'shares',            $options ) ) && ( '1' == $options['shares']    ) ) ? 'true' : 'false' );
	$site_comments  = ( ( ( array_key_exists( 'comments',          $options ) ) && ( '1' == $options['comments']  ) ) ? 'true' : 'false' );
	$site_recommend = ( ( ( array_key_exists( 'recommend',         $options ) ) && ( '1' == $options['recommend'] ) ) ? 'true' : 'false' );
	$site_tags      = '';
	$site_tags     .= ( ( ( array_key_exists( 'tags-mix',          $options ) ) && ( '1' == $options['tags-mix']          ) ) ? 'All,'                  : '' );
	$site_tags     .= ( ( ( array_key_exists( 'tags-fun',          $options ) ) && ( '1' == $options['tags-fun']          ) ) ? 'Fun,'                  : '' );
	$site_tags     .= ( ( ( array_key_exists( 'tags-pop',          $options ) ) && ( '1' == $options['tags-pop']          ) ) ? 'Pop,'                  : '' );
	$site_tags     .= ( ( ( array_key_exists( 'tags-geek',         $options ) ) && ( '1' == $options['tags-geek']         ) ) ? 'Geek,'                 : '' );
	$site_tags     .= ( ( ( array_key_exists( 'tags-sports',       $options ) ) && ( '1' == $options['tags-sports']       ) ) ? 'Sports,'               : '' );
	$site_tags     .= ( ( ( array_key_exists( 'tags-editors-pick', $options ) ) && ( '1' == $options['tags-editors-pick'] ) ) ? 'EditorsPick_Featured,' : '' );
	$site_tags     .= ( ( ( array_key_exists( 'more-tags',         $options ) ) ) ? $options['more-tags']  : '' );
	$site_tags      = rtrim( $site_tags, ',' );
	$site_margintop = ( ( ( array_key_exists( 'margin-top',        $options ) ) ) ? $options['margin-top'] : '' );
	$embeddedon     = ( ( ( array_key_exists( 'embeddedon',        $options ) ) ) ? $options['embeddedon'] : 'content' );

	// Set default attribute values if the user did not defined any
	$atts = shortcode_atts(
		array(
			'key'        => $site_key,       // api key allowing configuration and analytics
			'tags'       => $site_tags,      // filter by tags
			'game'       => '',              // defines the item that will be loaded by the IFrame (deprecated in 0.3 ; use "url" attribute)
			'url'        => '',              // defines the item that will be loaded by the IFrame (added in 0.3)
			'info'       => $site_info,      // show item info (thumbnail, name, description, editor, etc)
			'shares'     => $site_shares,    // show sharing buttons
			'comments'   => $site_comments,  // show comments control from the item page
			'recommend'  => $site_recommend, // show recommendations for more items
			'links'      => '',              // destination url in your site where new items will be displayed
			'width'      => 'auto',          // define custom width (added in 0.3)
			'height'     => 'auto',          // define custom height (added in 0.3)
			'margin-top' => $site_margintop, // margin top for score bar in case there is a floating bar
	), $atts );

	// Playbuzz Embed Code
	$code = '
		<script type="text/javascript" src="//cdn.playbuzz.com/widget/feed.js"></script>
		<div class="pb_feed" data-provider="WordPress ' . esc_attr( $wp_version ) . '" data-key="' . esc_attr( $atts['key'] ) . '" data-tags="' . esc_attr( $atts['tags'] ) . '" data-game="' . esc_url( $atts['url'] . $atts['game'] ) . '" data-game-info="' . esc_attr( $atts['info'] ) . '" data-comments="' . esc_attr( $atts['comments'] ) . '" data-shares="true" data-recommend="' . esc_attr( $atts['recommend'] ) . '" data-links="' . esc_attr( $atts['links'] ) . '" data-width="' . esc_attr( $atts['width'] ) . '" data-height="' . esc_attr( $atts['height'] ) . '" data-margin-top="' . esc_attr( $atts['margin-top'] ) . '"></div>
	';

	// Theme Visibility
	if ( 'content' == $embeddedon ) {
		// Show only in singular pages
		if ( is_singular() || is_admin() ) {
			return $code;
		}
	} elseif ( 'all' == $embeddedon ) {
		// Show in all pages
		return $code;
	}

}


<?php
/*
Plugin Name: The Neverending Home Page.
Plugin URI: http://automattic.com/
Description: Adds infinite scrolling support to the front-end blog post view for themes, pulling the next set of posts automatically into view when the reader approaches the bottom of the page.
Version: 1.1
Author: Automattic
Author URI: http://automattic.com/
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/**
 * Class: The_Neverending_Home_Page relies on add_theme_support, expects specific
 * styling from each theme; including fixed footer.
 */
class The_Neverending_Home_Page {
	function __construct() {
		add_filter( 'pre_get_posts',                  array( $this, 'posts_per_page_query' ) );

		add_action( 'admin_init',                     array( $this, 'settings_api_init' ) );
		add_action( 'template_redirect',              array( $this, 'action_template_redirect' ) );
		add_action( 'template_redirect',              array( $this, 'ajax_response' ) );
		add_action( 'custom_ajax_infinite_scroll',    array( $this, 'query' ) );
		add_action( 'the_post',                       array( $this, 'preserve_more_tag' ) );
		add_action( 'get_footer',                     array( $this, 'footer' ) );

		// Parse IS settings from theme
		self::get_settings();
	}

	/**
	 * Initialize our static variables
	 */
	static $the_time = null;
	static $settings = null; // Don't access directly, instead use self::get_settings().

	/**
	 * Parse IS settings provided by theme
	 *
	 * @uses get_theme_support, infinite_scroll_has_footer_widgets, sanitize_title, add_action, get_option, wp_parse_args, is_active_sidebar
	 * @return object
	 */
	static function get_settings() {
		if ( is_null( self::$settings ) ) {
			$css_pattern = '#[^A-Z\d\-_]#i';

			$settings = $defaults = array(
				'type'           => 'scroll', // scroll | click
				'requested_type' => 'scroll', // store the original type for use when logic overrides it
				'footer_widgets' => false, // true | false | sidebar_id | array of sidebar_ids -- last two are checked with is_active_sidebar
				'container'      => 'content', // container id
				'wrapper'        => true, // true | false | css class
				'render'         => false, // optional function, otherwise the `content` template part will be used
				'posts_per_page' => false // int | false to set based on IS type
			);

			// Validate settings passed through add_theme_support()
			$_settings = get_theme_support( 'infinite-scroll' );

			if ( is_array( $_settings ) ) {
				// Preferred implementation, where theme provides an array of options
				if ( isset( $_settings[0] ) && is_array( $_settings[0] ) ) {
					foreach ( $_settings[0] as $key => $value ) {
						switch ( $key ) {
							case 'type' :
								if ( in_array( $value, array( 'scroll', 'click' ) ) )
									$settings[ $key ] = $settings['requested_type'] = $value;

								break;

							case 'footer_widgets' :
								if ( is_string( $value ) )
									$settings[ $key ] = sanitize_title( $value );
								elseif ( is_array( $value ) )
									$settings[ $key ] = array_map( 'sanitize_title', $value );
								elseif ( is_bool( $value ) )
									$settings[ $key ] = $value;

								break;

							case 'container' :
							case 'wrapper' :
								if ( 'wrapper' == $key && is_bool( $value ) ) {
									$settings[ $key ] = $value;
								}
								else {
									$value = preg_replace( $css_pattern, '', $value );

									if ( ! empty( $value ) )
										$settings[ $key ] = $value;
								}

								break;

							case 'render' :
								if ( false !== $value && is_callable( $value ) ) {
									$settings[ $key ] = $value;

									add_action( 'infinite_scroll_render', $value );
								}

								break;

							case 'posts_per_page' :
								if ( is_numeric( $value ) )
									$settings[ $key ] = (int) $value;

								break;

							default:
								continue;

								break;
						}
					}
				}
				// Checks below are for backwards compatibility
				elseif ( is_string( $_settings[0] ) ) {
					// Container to append new posts to
					$settings['container'] = preg_replace( $css_pattern, '', $_settings[0] );

					// Wrap IS elements?
					if ( isset( $_settings[1] ) )
						$settings['wrapper'] = (bool) $_settings[1];
				}
			}

			// Always ensure all values are present in the final array
			$settings = wp_parse_args( $settings, $defaults );

			// Check if a legacy `infinite_scroll_has_footer_widgets()` function is defined and override the footer_widgets parameter's value.
			// Otherwise, if a widget area ID or array of IDs was provided in the footer_widgets parameter, check if any contains any widgets.
			// It is safe to use `is_active_sidebar()` before the sidebar is registered as this function doesn't check for a sidebar's existence when determining if it contains any widgets.
			if ( function_exists( 'infinite_scroll_has_footer_widgets' ) ) {
				$settings['footer_widgets'] = (bool) infinite_scroll_has_footer_widgets();
			}
			elseif ( is_array( $settings['footer_widgets'] ) ) {
				$sidebar_ids = $settings['footer_widgets'];
				$settings['footer_widgets'] = false;

				foreach ( $sidebar_ids as $sidebar_id ) {
					if ( is_active_sidebar( $sidebar_id ) ) {
						$settings['footer_widgets'] = true;
						break;
					}
				}

				unset( $sidebar_ids );
				unset( $sidebar_id );
			}
			elseif ( is_string( $settings['footer_widgets'] ) ) {
				$settings['footer_widgets'] = (bool) is_active_sidebar( $settings['footer_widgets'] );
			}

			// For complex logic, let themes filter the `footer_widgets` parameter.
			$settings['footer_widgets'] = apply_filters( 'infinite_scroll_has_footer_widgets', $settings['footer_widgets'] );

			// Finally, after all of the sidebar checks and filtering, ensure that a boolean value is present, otherwise set to default of `false`.
			if ( ! is_bool( $settings['footer_widgets'] ) )
				$settings['footer_widgets'] = false;

			// Ensure that IS is enabled and no footer widgets exist if the IS type isn't already "click".
			if ( 'click' != $settings['type'] ) {
				// Check the setting status
				$disabled = '' === get_option( 'infinite_scroll' ) ? true : false;

				// Footer content or Reading option check
				if ( $settings['footer_widgets'] || $disabled )
					$settings['type'] = 'click';
			}

			// Backwards compatibility for posts_per_page setting
			if ( false === $settings['posts_per_page'] )
				$settings['posts_per_page'] = 'click' == $settings['type'] ? (int) get_option( 'posts_per_page' ) : 7;

			// Store final settings in a class static to avoid reparsing
			self::$settings = $settings;
		}

		return (object) self::$settings;
	}

	/**
	 * Has infinite scroll been triggered?
	 */
	static function got_infinity() {
		return isset( $_GET[ 'infinity' ] );
	}

	/**
	 * The more tag will be ignored by default if the blog page isn't our homepage.
	 * Let's force the $more global to false.
	 */
	function preserve_more_tag( $array ) {
		global $more;

		if ( self::got_infinity() )
			$more = 0; //0 = show content up to the more tag. Add more link.

		return $array;
	}

	/**
	 * Add a checkbox field to Settings > Reading
	 * for enabling infinite scroll.
	 *
	 * Only show if the current theme supports infinity,
	 * and the blog has at least one footer widget.
	 */
	function settings_api_init() {
		if ( ! current_theme_supports( 'infinite-scroll' ) )
			return;

		// Add the setting field [infinite_scroll] and place it in Settings > Reading
		add_settings_field( 'infinite_scroll', '<span id="infinite-scroll-options">' . __( 'To infinity and beyond' ) . '</span>', array( $this, 'infinite_setting_html' ), 'reading' );
		register_setting( 'reading', 'infinite_scroll', 'esc_attr' );
	}

	/**
	 * HTML code to display a checkbox true/false option
	 * for the infinite_scroll setting.
	 */
	function infinite_setting_html() {
		$notice = '<em>' . __( "We've disabled this option for you since you have footer widgets in Appearance &rarr; Widgets, or because your theme does not support infinite scroll." ) . '</em>';

		// If the blog has footer widgets, show a notice instead of the checkbox
		if ( self::get_settings()->footer_widgets || 'click' == self::get_settings()->requested_type ) {
			echo '<label>' . $notice . '</label>';
		} else {
			echo '<label><input name="infinite_scroll" type="checkbox" value="1" ' . checked( 1, '' !== get_option( 'infinite_scroll' ), false ) . ' /> ' . __( 'Scroll Infinitely' ) . '</br><small>' . sprintf( __( '(Shows %s posts on each load)' ), number_format_i18n( self::get_settings()->posts_per_page ) ) . '</small>' . '</label>';
		}
	}

	/**
	 * Does the legwork to determine whether the feature is enabled.
	 */
	function action_template_redirect() {
		// Check that we support infinite scroll, and are on the home page.
		if ( ! current_theme_supports( 'infinite-scroll' ) || ! is_home() )
			return;

		$id = self::get_settings()->container;

		// Check that we have an id.
		if ( empty( $id ) )
			return;

		// Bail if there are not enough posts for infinity.
		if ( ! self::set_last_post_time() )
			return;

		// Add a class to the body.
		add_filter( 'body_class', array( $this, 'body_class' ) );

		// Add our scripts.
		wp_enqueue_script( 'the-neverending-homepage', plugins_url( 'infinity.js', __FILE__ ), array( 'jquery' ), '20121004' );

		// Add our default styles.
		wp_enqueue_style( 'the-neverending-homepage', plugins_url( 'infinity.css', __FILE__ ), array(), '20120612' );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_spinner_scripts' ) );

		add_action( 'wp_head', array( $this, 'action_wp_head' ), 2 );
	}

	/**
	 * Enqueue spinner scripts.
	 */
	function enqueue_spinner_scripts() {
		wp_enqueue_script( 'jquery.spin' );
	}

	/**
	 * Adds an 'infinite-scroll' class to the body.
	 */
	function body_class( $classes ) {
		$classes[] = 'infinite-scroll';

		if ( 'scroll' == self::get_settings()->type )
			$classes[] = 'neverending';

		return $classes;
	}

	/**
	 * Grab the timestamp for the last post.
	 * @return string 'Y-m-d H:i:s' or null
	 */
	function set_last_post_time( $date = false ) {
		global $posts;
		$count = count( $posts );

		if ( ! empty( $date ) && preg_match( '|\d\d\d\d\-\d\d\-\d\d|', $_GET['date'] ) ) {
			self::$the_time = "$date 00:00:00";
			return self::$the_time;
		}

		// If we don't have enough posts for infinity, return early
		if ( ! $count || $count < self::get_settings()->posts_per_page )
			return self::$the_time;

		$last_post = end( $posts );

		// If the function is called again but we already have a value, return it
		if ( null != self::$the_time ) {
			return self::$the_time;
		}
		else if ( isset( $last_post->post_date_gmt ) ) {
			// Grab the latest post time in Y-m-d H:i:s gmt format
			self::$the_time = $last_post->post_date_gmt;
		}

		return self::$the_time;
	}

	/**
	 * Create a where clause that will make sure post queries
	 * will always return results prior to (descending sort)
	 * or before (ascending sort) the last post date.
	 *
	 * @param string $where
	 * @param object $query
	 * @filter posts_where
	 * @return string
	 */
	function query_time_filter( $where, $query ) {
		global $wpdb;

		$operator = 'ASC' == $query->get( 'order' ) ? '>' : '<';

		// Construct the date query using our timestamp
		$where .= $wpdb->prepare( " AND post_date_gmt {$operator} %s", self::set_last_post_time() );

		return $where;
	}

	/**
	 * Let's overwrite the default post_per_page setting
	 * to always display a fixed amount.
	 */
	function posts_per_page_query( $query ) {
		if ( is_home() && $query->is_main_query() )
			$query->query_vars['posts_per_page'] = self::get_settings()->posts_per_page;

		return $query;
	}

	/**
	 * Check if the IS output should be wrapped in a div.
	 * Setting value can be a boolean or a string specifying the class applied to the div.
	 *
	 * @uses self::get_settings
	 * @return bool
	 */
	function has_wrapper() {
		return (bool) self::get_settings()->wrapper;
	}

	/**
	 * Returns the Ajax url
	 */
	function ajax_url() {
		global $current_blog, $wp;

		$base_url = home_url( trailingslashit( $wp->request ), is_ssl() ? 'https' : 'http' );

		$ajaxurl = add_query_arg( array( 'infinity' => 'scrolling' ), $base_url );

		// If present, take domain mapping into account
		// But make sure the url is not a WP.com one
		if ( isset( $current_blog->primary_redirect ) ) {
			$wpcom_url = preg_match('/\bwordpress.com/i', $current_blog->primary_redirect );

			if ( ! $wpcom_url )
				$ajaxurl = preg_replace( '|https?://' . preg_quote( $current_blog->domain ) . '|', 'http://' . $current_blog->primary_redirect, $ajaxurl );
		}

		return $ajaxurl;
	}

	/**
	 * Our own Ajax response, avoiding calling admin-ajax
	 */
	function ajax_response() {
		// Only proceed if the url query has a key of "Infinity"
		if ( ! self::got_infinity() )
			return false;

		define( 'DOING_AJAX', true );

		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		send_nosniff_header();

		do_action( 'custom_ajax_infinite_scroll' );
		die( '0' );
	}

	/**
	 * Prints the relevant infinite scroll settings in JS.
	 *
	 * @uses self::get_settings, esc_js, esc_url_raw, self::has_wrapper, __, apply_filters, do_action
	 * @action wp_head
	 * @return string
	 */
	function action_wp_head() {
		// Base JS settings
		$js_settings = array(
			'id'            => self::get_settings()->container,
			'ajaxurl'       => esc_js( esc_url_raw( self::ajax_url() ) ),
			'type'          => self::get_settings()->type,
			'wrapper'       => self::has_wrapper(),
			'wrapper_class' => is_string( self::get_settings()->wrapper ) ? self::get_settings()->wrapper : 'infinite-wrap',
			'text'          => esc_js( __( 'Load more posts' ) ),
			'totop'         => esc_js( __( 'Scroll back to top' ) ),
			'order'         => 'DESC'
		);

		// Optional order param
		if ( isset( $_GET['order'] ) ) {
			$order = strtoupper( $_GET['order'] );

			if ( in_array( $order, array( 'ASC', 'DESC' ) ) )
				$js_settings['order'] = $order;
		}

		$js_settings = apply_filters( 'infinite_scroll_js_settings', $js_settings );

		do_action( 'infinite_scroll_wp_head' );

		?>
		<script type="text/javascript">
		//<![CDATA[
		var infiniteScroll = <?php echo json_encode( array( 'settings' => $js_settings ) ); ?>;
		//]]>
		</script>
		<?php
	}

	/**
	 * Runs the query and returns the results via JSON.
	 * Triggered by an AJAX request.
	 *
	 * @global $wp_query
	 * @uses current_user_can, get_option, self::set_last_post_time, current_user_can, apply_filters, self::get_settings, add_filter, WP_Query, remove_filter, have_posts, do_action, add_action, this::render, this::has_wrapper, esc_attr
	 * @return string or null
	 */
	function query() {
		global $wp_query;

		if ( ! isset( $_GET['page'] ) || ! current_theme_supports( 'infinite-scroll' ) )
			die;

		$page = (int) $_GET['page'];
		$sticky = get_option( 'sticky_posts' );

		if ( ! empty( $_GET['date'] ) )
			self::set_last_post_time( $_GET['date'] );

		$post_status = array( 'publish' );
		if ( current_user_can( 'read_private_posts' ) )
			array_push( $post_status, 'private' );

		$order = in_array( $_GET['order'], array( 'ASC', 'DESC' ) ) ? $_GET['order'] : 'DESC';

		$query_args = apply_filters( 'infinite_scroll_query_args', array(
			'paged'          => $page,
			'post_status'    => $post_status,
			'posts_per_page' => self::get_settings()->posts_per_page,
			'post__not_in'   => ( array ) $sticky,
			'order'          => $order
		) );

		// Add query filter that checks for posts below the date
		add_filter( 'posts_where', array( $this, 'query_time_filter' ), 10, 2 );

		$wp_query = new WP_Query( $query_args );

		remove_filter( 'posts_where', array( $this, 'query_time_filter' ), 10, 2 );

		$results = array();

		if ( have_posts() ) {
			$results['type'] = 'success';

			// First, try theme's specified rendering handler, either specified via `add_theme_support` or by hooking to this action directly.
			ob_start();
			do_action( 'infinite_scroll_render' );
			$results['html'] = ob_get_clean();

			// Fall back if a theme doesn't specify a rendering function. Because themes may hook additional functions to the `infinite_scroll_render` action, `has_action()` is ineffective here.
			if ( empty( $results['html'] ) ) {
				add_action( 'infinite_scroll_render', array( $this, 'render' ) );
				rewind_posts();

				ob_start();
				do_action( 'infinite_scroll_render' );
				$results['html'] = ob_get_clean();
			}

			// If primary and fallback rendering methods fail, prevent further IS rendering attempts. Otherwise, wrap the output if requested.
			if ( empty( $results['html'] ) ) {
				unset( $results['html'] );
				do_action( 'infinite_scroll_empty' );
				$results['type'] = 'empty';
			}
			elseif ( $this->has_wrapper() ) {
				$wrapper_classes = is_string( self::get_settings()->wrapper ) ? self::get_settings()->wrapper : 'infinite-wrap';
				$wrapper_classes .= ' infinite-view-' . $page;
				$wrapper_classes = trim( $wrapper_classes );

				$results['html'] = '<div class="' . esc_attr( $wrapper_classes ) . '">' . $results['html'] . '</div>';
			}
		} else {
			do_action( 'infinite_scroll_empty' );
			$results['type'] = 'empty';
		}

		echo json_encode( apply_filters( 'infinite_scroll_results', $results ) );
		die;
	}

	/**
	 * Rendering fallback used when themes don't specify their own handler.
	 *
	 * @uses have_posts, the_post, get_template_part, get_post_format
	 * @action infinite_scroll_render
	 * @return string
	 */
	function render() {
		while( have_posts() ) {
			the_post();

			get_template_part( 'content', get_post_format() );
		}
	}

	/**
	 * The Infinite Blog Footer
	 */
	function footer() {
		// Bail if there are not enough posts for infinity.
		if ( ! self::set_last_post_time() )
			return;

		// We only need the new footer for the 'scroll' type
		if ( 'scroll' != self::get_settings()->type || ! is_home() )
			return;

		$theme_name = wp_get_theme()->name;

		?>
		<div id="infinite-footer">
			<div class="container">
				<div class="blog-info">
					<a id="infinity-blog-title" href="<?php echo home_url( '/' ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home">
						<?php bloginfo( 'name' ); ?>
					</a>
				</div>
				<div class="blog-credits">
					<a href="http://wordpress.org/" rel="generator">Proudly powered by WordPress</a>
					<?php printf( __( 'Theme: %1$s.' ), $theme_name ); ?>
				</div>
			</div>
		</div><!-- #infinite-footer -->
		<?php
	}
};

/**
 * Initialize The_Neverending_Home_Page
 */
function the_neverending_home_page_init() {
	if ( ! current_theme_supports( 'infinite-scroll' ) )
		return;

	new The_Neverending_Home_Page;
}
add_action( 'init', 'the_neverending_home_page_init', 20 );

/**
 * Check whether the current theme is infinite-scroll aware.
 * If so, include the files which add theme support.
 */
function the_neverending_home_page_theme_support() {
	$theme_name = get_stylesheet();

	$customization_file = apply_filters( 'infinite_scroll_customization_file', dirname( __FILE__ ) . "/themes/{$theme_name}.php", $theme_name );

	if ( is_readable( $customization_file ) )
		require_once( $customization_file );
}
add_action( 'after_setup_theme', 'the_neverending_home_page_theme_support', 5 );
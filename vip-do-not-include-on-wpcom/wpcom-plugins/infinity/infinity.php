<?php
/**
 * Plugin Name: The Neverending Home Page
 * Description: Infinite Scroll meets themes and turns the blog home page into a neverending page,
 * loading seven additional posts whenever the viewer approaches the end.
 * Author: Automattic
 *
 * License: GPL
 *
 * Class: The_Neverending_Home_Page relying on add_theme_support, expects specific
 * styling from each theme; including fixed footer.
 *
 */

class The_Neverending_Home_Page {
	function __construct() {
		add_filter( 'pre_get_posts',                  array( $this, 'posts_per_page' ) );

		add_action( 'template_redirect',              array( $this, 'action_template_redirect' ) );
		add_action( 'template_redirect',              array( $this, 'ajax_response' ) );
		add_action( 'custom_ajax_infinite_scroll',    array( $this, 'query' ) );
	}

	/**
	 * Initialize our variables
	 * $posts_per_page and $the_time
	 */
	static $posts_per_page = 7;
	static $the_time       = null;

	/**
	 * Does the legwork to determine whether the feature is enabled.
	 */
	function action_template_redirect() {
		// Check that we support infinite scroll and are on the home page.
		if ( ! current_theme_supports( 'infinite-scroll' ) || ! is_home() )
			return;

		$id = $this->get_id();

		// Check that we have an id.
		if ( empty( $id ) )
			return;

		// Add a class to the body.
		add_filter( 'body_class', array( $this, 'body_class' ) );

		// Add our scripts.
		wp_enqueue_script( 'the-neverending-homepage', plugins_url( 'infinity.js', __FILE__ ), array( 'jquery' ) );

		// Add our default styles.
		wp_enqueue_style( 'the-neverending-homepage', plugins_url( 'infinity.css', __FILE__ ), array() );

		add_action( 'wp_head', array( $this, 'action_wp_head' ), 2 );
	}

	/**
	 * Adds an 'infinite-scroll' class to the body.
	 */
	function body_class( $classes ) {
		$classes[] = 'infinite-scroll';
		return $classes;
	}

	/**
	 * Grab the timestamp for the last post.
	 */
	function set_last_post_time() {
		global $wpdb;
		$offset = self::$posts_per_page - 1;

		// If the function is called again but we already have a value, return it
		if ( self::$the_time != null ) {
			return self::$the_time;
		}
		else {
			// Grab the seventh post time in Y-m-d H:i:s gmt format
			self::$the_time = $wpdb->get_var(
				$wpdb->prepare( "SELECT post_date_gmt FROM $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date DESC LIMIT %d, %d", $offset, self::$posts_per_page ) );
		}

		return self::$the_time;
	}

	/**
	 * Create a where clause that will make sure post queries
	 * will always return results prior to the last post date.
	 */
	function query_time_filter( $where ) {
		global $wpdb;

		// Construct the date query using our timestamp
		$where .= $wpdb->prepare( ' AND post_date_gmt < %s', self::set_last_post_time() );

		return $where;
	}

	/**
	 * Let's overwrite the default post_per_page setting
	 * to always display a fixed amount.
	 */
	function posts_per_page( $query ) {
		if ( is_home() && $query->is_main_query() )
			$query->query_vars['posts_per_page'] = self::$posts_per_page;

		return $query;
	}

	/**
	 * Fetches the ID provided by add_theme_support
	 */
	function get_id() {
		$settings = get_theme_support( 'infinite-scroll' );

		return is_array( $settings ) ? $settings[0] : false;
	}

	/**
	 * Returns the Ajax url
	 */
	function ajax_url() {
		global $current_blog;

		$ajaxurl = home_url( '?infinity=scrolling', is_ssl() ? 'https' : 'http' );

		// If present, take domain mapping into account
		if ( isset( $current_blog->primary_redirect ) )
			$ajaxurl = preg_replace( '|https?://' . preg_quote( $current_blog->domain ) . '|', 'http://' . $current_blog->primary_redirect, $ajaxurl );

		return $ajaxurl;
	}

	/**
	 * Our own Ajax response, avoiding calling admin-ajax
	 */
	function ajax_response() {

		// Only proceed if the url query has a key of "Infinity"
		if ( ! isset( $_GET[ 'infinity' ] ) ) {
			return false;
		}

		define( 'DOING_AJAX', true );

		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		send_nosniff_header();

		do_action( 'custom_ajax_infinite_scroll' );
		die( '0' );
	}

	/**
	 * Prints the relevant infinite scroll settings in JS.
	 */
	function action_wp_head() {
		$id = $this->get_id();

		$js_settings = array(
			'id'      => $id,
			'ajaxurl' => esc_js( esc_url_raw( self::ajax_url() ) ),
		);

		self::load_other_plugins_scripts();

		?>
		<script type="text/javascript">
		//<![CDATA[
		var infiniteScroll = <?php echo json_encode( array( 'settings' => $js_settings ) ); ?>;
		//]]>
		</script>
		<?php
	}

	/**
	 * For several WP.com plugins, we currently need to always load their scripts on first load.
	 *
	 * See http://dotcom.wordpress.com/2011/12/02/infinite-scroll-for-themes-has-been-enabled-for/
	 * And http://dotcom.wordpress.com/2011/12/21/infinite-scroll-round-two-thanks-for-all-the/
	 */
	function load_other_plugins_scripts() {

		// Load VideoPress here so we don't get a broken blog.
		if ( function_exists( 'video_embed_load_scripts' ) )
			video_embed_load_scripts();

		// Fire the post_gallery action early so Carousel scripts are present.
		do_action( 'post_gallery' );

		// Load Twitter Blackbird Pie scripts
		if ( class_exists( 'BlackbirdPie' ) )
			BlackbirdPie::load_scripts();
	}

	/**
	 * Runs the query and returns the results via JSON.
	 * Triggered by an AJAX request.
	 */
	function query() {
		global $wp_query;

		if ( ! isset( $_POST['page'] ) || ! current_theme_supports( 'infinite-scroll' ) )
			die;

		$page = (int) $_POST['page'];

		$query_args = apply_filters( 'infinite_scroll_query_args', array(
			'paged' => $page,
			'post_status' => 'publish',
			'posts_per_page' => self::$posts_per_page,
			'post__not_in' => get_option( 'sticky_posts' ),
		) );

		// Add query filter that checks for posts below the date
		add_filter( 'posts_where', array( $this, 'query_time_filter' ) );

		$wp_query = new WP_Query( $query_args );

		remove_filter( 'posts_where', array( $this, 'query_time_filter' ) );

		$results = array();

		if ( have_posts() ) {
			$results['type'] = 'success';

			ob_start();
			do_action( 'infinite_scroll_render' );
			$results['html'] = ob_get_clean();
		} else {
			do_action( 'infinite_scroll_empty' );
			$results['type'] = 'empty';
		}

		echo json_encode( apply_filters( 'infinite_scroll_results', $results ) );
		die;
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
$theme_name = get_option( 'stylesheet' );

$infinity_theme_file = dirname( __FILE__ ) . "/themes/$theme_name.php";

if ( is_readable( $infinity_theme_file ) ) {
	require_once( $infinity_theme_file );
}

unset( $theme_name, $infinity_theme_file );

// End
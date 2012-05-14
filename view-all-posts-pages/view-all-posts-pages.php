<?php
/**
Plugin Name: View All Post's Pages
Plugin URI: http://www.thinkoomph.com/plugins-modules/view-all-posts-pages/
Description: Provides a "view all" (single page) option for posts, pages, and custom post types paged using WordPress' <a href="http://codex.wordpress.org/Write_Post_SubPanel#Quicktags" target="_blank"><code>&lt;!--nextpage--&gt;</code> Quicktag</a> (multipage posts).
Author: Erick Hitter (Oomph, Inc.)
Version: 0.5
Author URI: http://www.thinkoomph.com/
*/

class view_all_posts_pages {
	private $query_var = 'view-all';

	private $ns = 'view_all_posts_pages';

	private $settings_key = 'vapp';
	private $settings_defaults = array(
		'wlp' => true,
		'wlp_text' => 'View All',
		'wlp_class' => 'vapp',
		'wlp_post_types' => array(
			'post'
		),
		'link' => false,
		'link_position' => 'below',
		'link_text' => 'View All',
		'link_class' => 'vapp',
		'link_post_types' => array(
			'post'
		),
		'link_priority' => 10
	);

	private $notice_key = 'vapp_admin_notice_dismissed';

	/**
	 * Register actions and filters.
	 * @uses register_deactivation_hook, add_action, add_filter, this::get_options, apply_filters, get_option
	 * @return null
	 */
	public function __construct() {
		register_deactivation_hook( __FILE__, array( $this, 'deactivation_hook' ) );

		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );

		add_action( 'init', array( $this, 'action_init' ), 20 );

		add_action( 'the_post', array( $this, 'action_the_post' ), 5 );

		$options = $this->get_options();

		if ( array_key_exists( 'wlp', $options ) && true === $options[ 'wlp' ] )
			add_filter( 'wp_link_pages_args', array( $this, 'filter_wp_link_pages_args_early' ), 0 );

		if ( $options[ 'link' ] )
			add_filter( 'the_content', array( $this, 'filter_the_content_auto' ), $options[ 'link_priority' ] );

		if ( apply_filters( 'vapp_display_rewrite_rules_notice', true ) && ! get_option( $this->notice_key ) )
			add_action( 'admin_notices', array( $this, 'action_admin_notices_activation' ) );
	}

	/**
	 * Clean up after plugin deactivation.
	 * @uses flush_rewrite_rules, delete_option
	 * @action register_deactivation_hook
	 * @return null
	 */
	public function deactivation_hook() {
		flush_rewrite_rules();

		delete_option( $this->settings_key );
		delete_option( $this->notice_key );
	}

	/**
	 * Register plugin option and disable rewrite rule flush warning.
	 * @uses register_setting, apply_filters, update_option
	 * @action admin_init
	 * @return null
	 */
	public function action_admin_init() {
		register_setting( $this->settings_key, $this->settings_key, array( $this, 'admin_options_validate' ) );

		if ( isset( $_GET[ $this->notice_key ] ) && apply_filters( 'vapp_display_rewrite_rules_notice', true ) )
			update_option( $this->notice_key, 1 );
	}

	/**
	 * Determine if full post view is being requested.
	 * @uses $wp_query
	 * @return bool
	 */
	public function is_view_all() {
		global $wp_query;
		return is_array( $wp_query->query ) && array_key_exists( $this->query_var, $wp_query->query );
	}

	/**
	 * Add rewrite endpoint, which sets query var and rewrite rules
	 * @uses add_rewrite_endpoint
	 * @action init
	 * @return null
	 */
	public function action_init() {
		add_rewrite_endpoint( $this->query_var, EP_ALL );
	}

	/**
	 * Modify post variables to display entire post on one page
	 *
	 * @global $pages, $more
	 * @param object $post
	 * @uses this::is_view_all
	 * @action the_post
	 * @return null
	 */
	public function action_the_post( $post ) {
		if ( $this->is_view_all() ) {
			global $pages, $more;

			$post->post_content = str_replace( "\n<!--nextpage-->\n", "\n\n", $post->post_content );
			$post->post_content = str_replace( "\n<!--nextpage-->", "\n", $post->post_content );
			$post->post_content = str_replace( "<!--nextpage-->\n", "\n", $post->post_content );
			$post->post_content = str_replace( "<!--nextpage-->", ' ', $post->post_content );

			$pages = array( $post->post_content );

			$more = 1;
		}
	}

	/**
	 * Add wp_link_pages arguments filter if automatic inclusion is chosen for a given post type.
	 * Automatic inclusion can be disabled by passing false through the vapp_display_link filter.
	 *
	 * @param array $args
	 * @uses $post, this::get_options, apply_filters, add_filter
	 * @filter wp_link_pages
	 * @return array
	 */
	public function filter_wp_link_pages_args_early( $args ) {
		global $post;

		$options = $this->get_options();

		if ( in_array( $post->post_type, $options[ 'wlp_post_types' ] ) && apply_filters( 'vapp_display_link', true, (int) $post->ID, $options, $post ) )
			add_filter( 'wp_link_pages_args', array( $this, 'filter_wp_link_pages_args' ), 999 );

		return $args;
	}

	/**
	 * Filter wp_link_pages arguments to append "View all" link to output.
	 * @param array $args
	 * @uses this::get_options, $more, this::is_view_all, esc_attr, esc_url
	 * @return array
	 */
	public function filter_wp_link_pages_args( $args ) {
		$options = $this->get_options();

		if ( is_array( $options ) ) {
			extract( $options );

			//Set global $more to false so that wp_link_pages outputs links for all pages when viewing full post page
			if ( $this->is_view_all() )
				$GLOBALS[ 'more' ] = false;

			//Process link text, respecting pagelink parameter.
			$link_text = str_replace( '%', $wlp_text, $args[ 'pagelink' ] );

			//View all
			$link = ' ' . $args[ 'link_before' ];

			if ( $this->is_view_all() )
				$link .= '<span class="' . esc_attr( $wlp_class ) . '">' . $link_text . '</span><!-- .' . esc_attr( $wlp_class ) . ' -->';
			else
				$link .= '<a class="' . esc_attr( $wlp_class ) . '" href="' . esc_url( $this->url() ) . '">' . $link_text . '</a><!-- .' . esc_attr( $wlp_class ) . ' -->';

			$link .= $args[ 'link_after' ] . ' ';

			$args[ 'after' ] = $link . $args[ 'after' ];
		}

		return $args;
	}

	/**
	 * Filter the content if automatic link inclusion is selected
	 * @param string $content
	 * @uses this::get_options, $post, this::is_view_all, esc_attr, esc_url, this::url
	 * @filter the_content
	 * @return string
	 */
	public function filter_the_content_auto( $content ) {
		$options = $this->get_options();

		global $post;

		if ( ! $this->is_view_all() && is_array( $options ) && array_key_exists( 'link', $options ) && true === $options[ 'link' ] && in_array( $post->post_type, $options[ 'link_post_types' ] ) ) {
			extract( $options );

			$link = '<p class="vapp_wrapper"><a class="' . esc_attr( $link_class ) . '" href="' . esc_url( $this->url() ) . '">' . esc_html( $link_text ) . '</a></p><!-- .vapp_wrapper -->';

			if ( 'above' == $link_position )
				$content = $link . $content;
			elseif ( 'below' == $link_position )
				$content = $content . $link;
			elseif ( 'both' == $link_position )
				$content = $link . $content . $link;
		}

		return $content;
	}

	/**
	 * Generate URL
	 * @param int $post_id
	 * @uses is_singular, in_the_loop, $post, get_permalink, is_home, is_front_page, home_url, is_category, get_category_link, get_query_var, is_tag, get_tag_link, is_tax, get_queried_object, get_term_link, $wp_rewrite, path_join, trailingslashit, add_query_arg
	 * @return string or bool
	 */
	public function url( $post_id = false ) {
		$link = false;

		//Get link base specific to page type being viewed
		if ( is_singular() || in_the_loop() ) {
			$post_id = intval( $post_id );

			if ( ! $post_id ) {
				global $post;
				$post_id = $post->ID;
			}

			if ( ! $post_id )
				return false;

			$link = get_permalink( $post_id );
		}
		elseif ( is_home() || is_front_page() )
			$link = home_url( '/' );
		elseif ( is_category() )
			$link = get_category_link( get_query_var( 'cat' ) );
		elseif ( is_tag() )
			$link = get_tag_link( get_query_var( 'tag_id' ) );
		/** DISABLED FOR NOW AS PRINTING OF DATE-BASED ARCHIVES DOESN'T WORK YET
		elseif ( is_date() ) {
			$year = get_query_var( 'year' );
			$monthnum = get_query_var( 'monthnum' );
			$day = get_query_var( 'day' );

			if ( $day )
				$link = get_day_link( $year, $monthnum, $day );
			elseif ( $monthnum )
				$link = get_month_link( $year, $monthnum );
			else
				$link = get_year_link( $year );
		}*/
		elseif ( is_tax() ) {
			$queried_object = get_queried_object();

			if ( is_object( $queried_object ) && property_exists( $queried_object, 'taxonomy' ) && property_exists( $queried_object, 'term_id' ) )
				$link = get_term_link( (int)$queried_object->term_id, $queried_object->taxonomy );
		}

		//If link base is set, build link
		if ( false !== $link ) {
			global $wp_rewrite;

			if ( $wp_rewrite->using_permalinks() ) {
				$link = path_join( $link, $this->query_var );

				if ( $wp_rewrite->use_trailing_slashes )
					$link = trailingslashit( $link );
			}
			else {
				$link = add_query_arg( $this->query_var, 1, $link );
			}
		}

		return $link;
	}

	/**
	 * Add menu item for options page
	 * @uses __, add_options_page
	 * @action admin_menu
	 * @return null
	 */
	public function action_admin_menu() {
		add_options_page( __( 'View All Post\'s Pages Options', $this->ns ), 'View All Post\'s Pages', 'manage_options', $this->ns, array( $this, 'admin_options' ) );
	}

	/**
	 * Render options page
	 * @uses settings_fields, this::get_options, this::post_types_array, _e, checked, esc_attr, submit_button
	 * @return string
	 */
	public function admin_options() {
	?>
		<div class="wrap">
			<h2>View All Post's Pages</h2>

			<form action="options.php" method="post">
				<?php
					settings_fields( $this->settings_key );
					$options = $this->get_options();

					$post_types = $this->post_types_array();
				?>

				<h3><em>wp_link_pages</em> Options</h3>

				<p class="description">A "view all" link can be appended to WordPress' standard page navigation using the options below.</p>

				<table class="form-table">
					<tr>
						<th scope="row"><?php _e( 'Automatically append link to post\'s page navigation?', $this->ns ); ?></th>
						<td>
							<input type="radio" name="<?php echo $this->settings_key; ?>[wlp]" id="wlp-true" value="1"<?php checked( $options[ 'wlp' ], true, true ); ?> /> <label for="wlp-true"><?php _e( 'Yes', $this->ns ); ?></label><br />
							<input type="radio" name="<?php echo $this->settings_key; ?>[wlp]" id="wlp-false" value="0"<?php checked( $options[ 'wlp' ], false, true ); ?> /> <label for="wlp-false"><?php _e( 'No', $this->ns ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wlp_text"><?php _e( 'Link text:', $this->ns ); ?></label></th>
						<td>
							<input type="text" name="<?php echo $this->settings_key; ?>[wlp_text]" id="wlp_text" value="<?php echo esc_attr( $options[ 'wlp_text' ] ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wlp_class"><?php _e( 'Link\'s CSS class(es):', $this->ns ); ?></label></th>
						<td>
							<input type="text" name="<?php echo $this->settings_key;?>[wlp_class]" id="wlp_class" value="<?php echo esc_attr( $options[ 'wlp_class' ] ); ?>" class="regular-text" />

							<p class="description"><?php _e( 'Be aware that Internet Explorer will only interpret the first two CSS classes.', $this->ns ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Display automatically on:', $this->ns ); ?></th>
						<td>
							<?php foreach ( $post_types as $post_type ) : ?>
								<input type="checkbox" name="<?php echo $this->settings_key; ?>[wlp_post_types][]" id="wlp-pt-<?php echo $post_type->name; ?>" value="<?php echo $post_type->name; ?>"<?php if ( in_array( $post_type->name, $options[ 'wlp_post_types' ] ) ) echo ' checked="checked"'; ?> /> <label for="wlp-pt-<?php echo $post_type->name; ?>"><?php echo $post_type->labels->name; ?></label><br />
							<?php endforeach; ?>
						</td>
					</tr>
				</table>

				<h3>Standalone Link Options</h3>

				<p class="description">In addition to appending the "view all" link to WordPress' standard navigation, link(s) can be added above and below post content.</p>

				<table class="form-table">
					<tr>
						<th scope="row"><?php _e( 'Automatically add links based on settings below?', $this->ns ); ?></th>
						<td>
							<input type="radio" name="<?php echo $this->settings_key; ?>[link]" id="link-true" value="1"<?php checked( $options[ 'link' ], true, true ); ?> /> <label for="link-true"><?php _e( 'Yes', $this->ns ); ?></label><br />
							<input type="radio" name="<?php echo $this->settings_key; ?>[link]" id="link-false" value="0"<?php checked( $options[ 'link' ], false, true ); ?> /> <label for="link-false"><?php _e( 'No', $this->ns ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Automatically place link:', $this->ns ); ?></th>
						<td>
							<input type="radio" name="<?php echo $this->settings_key; ?>[link_position]" id="link_position-above" value="above"<?php checked( $options[ 'link_position' ], 'above', true ); ?> /> <label for="link_position-above"><?php _e( 'Above content', $this->ns ); ?></label><br />
							<input type="radio" name="<?php echo $this->settings_key; ?>[link_position]" id="link_position-below" value="below"<?php checked( $options[ 'link_position' ], 'below', true ); ?> /> <label for="link_position-below"><?php _e( 'Below content', $this->ns ); ?></label><br />
							<input type="radio" name="<?php echo $this->settings_key; ?>[link_position]" id="link_position-both" value="both"<?php checked( $options[ 'link_position' ], 'both', true ); ?> /> <label for="link_position-both"><?php _e( 'Above and below content', $this->ns ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e( 'Display automatically on:', $this->ns ); ?></th>
						<td>
							<?php foreach ( $post_types as $post_type ) : ?>
								<input type="checkbox" name="<?php echo $this->settings_key; ?>[link_post_types][]" id="link-pt-<?php echo $post_type->name; ?>" value="<?php echo $post_type->name; ?>"<?php if ( in_array( $post_type->name, $options[ 'link_post_types' ] ) ) echo ' checked="checked"'; ?> /> <label for="link-pt-<?php echo $post_type->name; ?>"><?php echo $post_type->labels->name; ?></label><br />
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="link_text"><?php _e( 'Link text:', $this->ns ); ?></label></th>
						<td>
							<input type="text" name="<?php echo $this->settings_key; ?>[link_text]" id="link_text" value="<?php echo esc_attr( $options[ 'link_text' ] ); ?>" class="regular-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="link_class"><?php _e( 'Link\'s CSS class(es):', $this->ns ); ?></label></th>
						<td>
							<input type="text" name="<?php echo $this->settings_key; ?>[link_class]" id="link_class" value="<?php echo esc_attr( $options[ 'link_class' ] ); ?>" class="regular-text" />

							<p class="description"><?php _e( 'Be aware that Internet Explorer will only interpret the first two CSS classes.', $this->ns ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="link_priority"><?php _e( 'Link\'s priority:', $this->ns ); ?></label></th>
						<td>
							<input type="text" name="<?php echo $this->settings_key; ?>[link_priority]" id="link_priority" class="small-text code" value="<?php echo esc_attr( $options[ 'link_priority' ] ); ?>" />

							<p class="description"><?php _e( 'Priority determines when the link is added to a post\'s content. You can use the above setting to modulate the link\'s placement.', $this->ns ); ?></p>
							<p class="description"><?php _e( 'The default value is <strong>10</strong>. Lower values mean the link will be added earlier, while higher values will add the link later.', $this->ns ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

		</div><!-- .wrap -->
	<?php
	}

	/**
	 * Validate options
	 * @param array $options
	 * @uses this::get_options, this::post_types_array, sanitize_text_field, absint
	 * @return array
	 */
	public function admin_options_validate( $options ) {
		$new_options = array();

		if ( is_array( $options ) ) {
			foreach ( $options as $key => $value ) {
				switch( $key ) {
					case 'wlp':
					case 'link':
						$new_options[ $key ] = (bool)$value;
					break;

					case 'link_position':
						$placements = array(
							'above',
							'below',
							'both'
						);

						$new_options[ $key ] = in_array( $value, $placements ) ? $value : 'below';
					break;

					case 'wlp_post_types':
					case 'link_post_types':
						$post_types = $this->post_types_array();

						$new_options[ $key ] = array();

						if ( is_array( $value ) && is_array( $post_types ) ) {
							foreach ( $post_types as $post_type ) {
								if ( in_array( $post_type->name, $value ) )
									$new_options[ $key ][] = $post_type->name;
							}
						}
					break;

					case 'wlp_text':
					case 'wlp_class':
					case 'link_text':
					case 'link_class':
						$value = sanitize_text_field( $value );

						if ( ( 'wlp_text' == $key || 'link_text' == $key ) && empty( $value ) )
							$value = 'View all';

						$new_options[ $key ] = $value;
					break;

					case 'link_priority':
						$value = absint( $value );

						$new_options[ $key ] = $value;
					break;

					default:
						continue;
					break;
				}
			}
		}

		return $new_options;
	}

	/**
	 * Return plugin options array parsed with default options
	 * @uses get_option, wp_parse_args
	 * @return array
	 */
	private function get_options() {
		$options = get_option( $this->settings_key, $this->settings_defaults );

		if ( ! is_array( $options ) )
			$options = array();

		if ( ! array_key_exists( 'wlp_post_types', $options ) )
			$options[ 'wlp_post_types' ] = array();

		if ( ! array_key_exists( 'link_post_types', $options ) )
			$options[ 'link_post_types' ] = array();

		return wp_parse_args( $options, $this->settings_defaults );
	}

	/**
	 * Build array of available post types, excluding all built-in ones except 'post' and 'page'
	 * @uses get_post_types
	 * @return array
	 */
	private function post_types_array() {
		$post_types = array();
		foreach ( get_post_types( array(), 'objects' ) as $post_type ) {
			if ( false == $post_type->_builtin || 'post' == $post_type->name || 'page' == $post_type->name )
				$post_types[] = $post_type;
		}

		return $post_types;
	}

	/**
	 * Display admin notice regarding rewrite rules flush.
	 * @uses get_option, apply_filters, _e, __, admin_url, add_query_arg
	 * @action admin_notices
	 * @return html or null
	 */
	public function action_admin_notices_activation() {
		if ( ! get_option( $this->notice_key ) && apply_filters( 'vapp_display_rewrite_rules_notice', true ) ) :
		?>

		<div id="wpf-rewrite-flush-warning" class="error fade">
			<p><strong><?php _e( 'View All Post\'s Pages', $this->ns ); ?></strong></p>

			<p><?php printf( __( 'You must refresh your site\'s permalinks before <em>View All Post\'s Pages</em> is fully activated. To do so, go to <a href="%s">Permalinks</a> and click the <strong><em>Save Changes</em></strong> button at the bottom of the screen.', $this->ns ), admin_url( 'options-permalink.php' ) ); ?></p>

			<p><?php printf( __( 'When finished, click <a href="%s">here</a> to hide this message.', $this->ns ), admin_url( add_query_arg( $this->notice_key, 1, 'index.php' ) ) ); ?></p>
		</div>

		<?php
		endif;
	}
}
global $vapp;
if ( ! is_a( $vapp, 'view_all_posts_pages' ) )
	$vapp = new view_all_posts_pages;

/**
 * Shortcut to public function for generating full post view URL
 * @param int $post_id
 * @uses $vapp
 * @return string or bool
 */
function vapp_get_url( $post_id = false ) {
	global $vapp;
	if ( ! is_a( $vapp, 'view_all_posts_pages' ) )
		$vapp = new view_all_posts_pages;

	return $vapp->url( intval( $post_id ) );
}

/**
 * Output link to full post view.
 * @param string $link_text
 * @param string $class
 * @uses $post, vapp_get_url, esc_attr, esc_url, esc_html
 * @return string or null
 */
function vapp_the_link( $link_text = 'View All', $class = 'vapp' ) {
	global $post;
	$url = vapp_get_url( $post->ID );

	if ( $url ) {
		$link = '<a ' . ( $class ? 'class="' . esc_attr( $class ) . '"' : '' ) . ' href="' . esc_url( $url ) . '">' . esc_html( $link_text ) . '</a>';

		echo $link;
	}
}

/**
 * Filter wp_link_pages args.
 * Function is a shortcut to class' filter.
 * @param array $args
 * @uses $vapp
 * @return array
 */
function vapp_filter_wp_link_pages_args( $args ) {
	global $vapp;
	if ( ! is_a( $vapp, 'view_all_posts_pages' ) )
		$vapp = new view_all_posts_pages;

	return $vapp->filter_wp_link_pages_args( $args );
}

if ( ! function_exists( 'is_view_all' ) ) {
	/**
	 * Conditional tag indicating if full post view was requested.
	 * @uses $vapp
	 * @return bool
	 */
	function is_view_all() {
		global $vapp;
		if ( ! is_a( $vapp, 'view_all_posts_pages' ) )
			$vapp = new view_all_posts_pages;

		return $vapp->is_view_all();
	}
}
?>
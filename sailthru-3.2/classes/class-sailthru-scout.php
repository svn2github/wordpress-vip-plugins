<?php
/*
 *	Scout depends entirely on Horizon.
 */
class Sailthru_Scout_Widget extends WP_Widget {

	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/
	function __construct() {

		// Register Scout Javascripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scout_scripts' ) );

		// Attempt to create the page needed for Scout.
		$post_id = $this->create_scout_page();

		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_widget_text_domain' ) );

		parent::__construct(
			'sailthru-recommends-id',
			__( 'Sailthru Personalization Engine', 'sailthru-for-wordpress' ),
			array(
				'classname'   => 'Sailthru_Scout',
				'description' => __( 'Sailthru Personalization Engine Widget', 'sailthru-for-wordpress' ),
			)
		);

	} // end constructor.

	/*--------------------------------------------------*/
	/* Public Functions
	/*--------------------------------------------------*/

	// Loads the Widget's text domain for localization and translation.
	public function load_widget_text_domain() {

		load_plugin_textdomain( 'sailthru-for-wordpress', false, plugin_dir_path( SAILTHRU_PLUGIN_PATH ) . '/lang/' );

	} // End load_widget_text_domain.



	// Add scout. But only if scout is turned on.
	public function register_scout_scripts() {

		$params  = get_option( 'sailthru_scout_options' );
		$options = get_option( 'sailthru_setup_options' );
		// Is scout turned on?

		// Don't load scout if using Personalize J
		if ( isset( $options['sailthru_js_type'] ) && 'horizon_js' !== $options['sailthru_js_type'] ) {
			return;
		}

		wp_enqueue_style( 'sailthru-scout-widget-styles', SAILTHRU_PLUGIN_URL . 'css/widget.scout.css' );

		/**
		 * Filter the Sailthru Scout status.
		 *
		 * @param bool    $status True if Scout is turned on.
		 */
		if ( isset( $params['sailthru_scout_is_on'] ) && $params['sailthru_scout_is_on'] === "1") {

			// Check first, otherwise js could throw errors.
			if ( "1" ===  get_option( 'sailthru_setup_complete' ) ) {

				// If conceirge is on, we want noPageView to be set to true
				$conceirge = get_option( 'sailthru_concierge_options' );
				/** This filter is documented in class-sailthru-horizon.php */
				if ( isset( $conceirge['sailthru_concierge_is_on'] ) && $conceirge['sailthru_concierge_is_on'] && apply_filters( 'sailthru_concierge_on', true ) ) {
					$params['sailthru_scout_noPageview'] = 'true';
				}
				wp_enqueue_script( 'sailthru_scout_js', '//ak.sail-horizon.com/scout/v1.js' );
				add_action( 'wp_footer', array( $this, 'scout_js' ), 10 );

			} // End if sailthru setup is done.
		} // End if scout is on.

	} // End register_conceirge_scripts.

	/*-------------------------------------------
	 * Utility Functions
	 *------------------------------------------*/

	/**
	 * A function used to render the Scout JS.
	 *
	 * @return string
	 */
	function scout_js() {

		$options        = get_option( 'sailthru_setup_options' );
		$horizon_domain = $options['sailthru_horizon_domain'];
		$scout          = get_option( 'sailthru_scout_options' );
		$concierge      = get_option( 'sailthru_concierge_options' );
		$scout_params   = array();

		// if we're using Personalize JS then don't use Scout
		if ( isset( $options['sailthru_js_type'] ) && 'horizon_js' !== $options['sailthru_js_type'] )  {
			return;
		}

		echo 'ccccccc';

		// inlcudeConsumed?
		if ( isset( $scout['sailthru_scout_includeConsumed'] ) && strlen( $scout['sailthru_scout_includeConsumed'] ) > 0 ) {
			$scout_params[] = 'includeConsumed: ' . (bool) $scout['sailthru_scout_includeConsumed'];
		}

		// renderItem?
		if ( isset( $scout['sailthru_scout_renderItem'] ) && strlen( $scout['sailthru_scout_renderItem'] ) > 0 ) {
			$scout_params[] = 'renderItem: ' . (bool) $scout['sailthru_scout_renderItem'];
		}

		if ( isset( $scout['sailthru_scout_numVisible'] ) ) {
			$scout_params[] = strlen( $scout['sailthru_scout_numVisible'] ) > 0 ? 'numVisible: ' . (int) $scout['sailthru_scout_numVisible'] . ' ' : '';
		}
		/**
		 * Filter the Sailthru Scout number of visible articles.
		 *
		 * @param string|int $num_visible Number of visible articles.
		 */
		$scout_params[] = 'numVisible: ' . (int) apply_filters( 'sailthru_scout_num_visible', $num_visible );

		// Tags.
		$tags   = array();
		$filter = '';
		if ( ! empty( $scout['sailthru_scout_filter'] ) ) {
			$tags = explode( ',', $scout['sailthru_scout_filter'] );
		}
		/**
		 * Filter the Sailthru Scout content filter tags.
		 *
		 * @param array $tags Array of tags.
		 */
		$tags = apply_filters( 'sailthru_scout_filter', $tags );
		$tags = array_map( array( $this, 'escape_filter_tags' ), $tags );
		if ( ! empty( $tags ) ) {
			if ( 1 === count( $tags ) ) {
				$filter = "        filter: {tags:'" . implode( "','", $tags ) . "'},\n";
			} else {
				$filter = "        filter: {tags: ['" . implode( "','", $tags ) . "']},\n";
			}
		}

		echo "<script type=\"text/javascript\">\n";
		echo "    SailthruScout.setup( {\n";
		echo "        domain: '" . esc_js( $options['sailthru_horizon_domain'] ) . "',\n";
		if ( is_array( $scout_params ) ) {
			foreach ( $scout_params as $key => $val ) {
				if ( strlen( $val ) > 0 ) {
					echo '        ' . esc_js( $val ) . ",\n";
				}
			}
		}
		if ( ! empty( $filter ) ) {
			echo wp_kses_post( $filter );
		}
		echo "    } );\n";
		echo "</script>\n";

	}

	/**
	 * Escape and trim whitespace from tags.
	 *
	 * @param string $tag Tag to be escaped and trimmed.
	 * @return string Escaped and trimmed tag.
	 */
	private function escape_filter_tags( $tag ) {
		return esc_js( trim( $tag ) );
	}

	/**
	 * A function used to programmatically create a page needed for Scout.
	 * The slug, author ID, and title are defined within the context of the function.
	 *
	 * @return -1 if the post was never created, -2 if a post with the same title exists,
	 * or the ID of the post if successful.
	 */
	private function create_scout_page() {

		// Never run this on public facing pages.
		if ( ! is_admin() ) {
			return;
		}

		// -1 = No action has been taken.
		$post_id = -1;

		// Our specific settings.
		$slug         = 'scout-from-sailthru';
		$title        = 'Recommended for You';
		$post_type    = 'page';
		$post_content = '<div id="sailthru-scout"><div class="loading">Loading, please wait...</div></div>';

		// If the page doesn't already exist, then create it.
		$create_page = function_exists( 'wpcom_vip_get_page_by_title' ) ? null === wpcom_vip_get_page_by_title( $title ) : null === get_page_by_title( $title );
		if ( $create_page ) {
			// Set the post ID so that we know the post was created successfully.
			$post_id = wp_insert_post(
				array(
					'comment_status' => 'closed',
					'ping_status'    => 'closed',
					'post_name'      => $slug,
					'post_title'     => $title,
					'post_status'    => 'publish',
					'post_type'      => $post_type,
					'post_content'   => $post_content,
				)
			);
		} else {
			$post_id = -2;
		} // End if.

		return $post_id;

	}


	/*--------------------------------------------------*/
	/* Widget API Functions
	/*--------------------------------------------------*/

	/**
	 * Outputs the content of the widget.
	 *
	 * @param array args     The array of form elements.
	 * @param array instance The current instance of the widget.
	 */
	function widget($args, $instance) {
		extract( $args, EXTR_SKIP );
		echo wp_kses_post( $before_widget );
		include( SAILTHRU_PLUGIN_PATH . 'views/widget.scout.display.php' );
		echo wp_kses_post( $after_widget );
	}
	/**
	 * Processes the widget's options to be saved.
	 *
	 * @param array new_instance The previous instance of values before the update.
	 * @param array old_instance The new instance of values to be generated via the update.
	 */
	public function update( $new_instance, $old_instance ) {

		$instance                         = array();
		$instance['title']                = filter_var( $new_instance['title'], FILTER_SANITIZE_STRING );
		$instance['sailthru_spm_section'] = filter_var( $new_instance['sailthru_spm_section'], FILTER_SANITIZE_STRING );

		return $instance;

	} // End widget.

	/**
	 * Generates the administration form for the widget.
	 *
	 * @param array instance The array of keys and values for the widget.
	 */
	public function form( $instance ) {

		// Default values for a widget instance
		$instance   = wp_parse_args(
			(array) $instance, array(
				'title'                => '',
				'sailthru_spm_section' => '',
			)
		);
		$title      = esc_attr( $instance['title'] );
		$active_section_id = esc_attr( $instance['sailthru_spm_section'] );

		// Display the admin form.
		include SAILTHRU_PLUGIN_PATH . 'views/widget.scout.admin.php';

	} // End form.
} // End class.

/**
 * Register Sailthru Scout Widget
 */
function sailthru_register_scout_widget() {
	register_widget( 'Sailthru_Scout_Widget' );
}
add_action( 'widgets_init', 'sailthru_register_scout_widget' );

/**
 * Template tag for manual placement of Scout
 *
 * @return string Scout element
 */
function sailthru_show_scout() {
	$scout = get_option( 'sailthru_scout_options' );
	/** This filter is documented in class-sailthru-scout.php */
	if ( isset( $scout['sailthru_scout_is_on'] ) && $scout['sailthru_scout_is_on'] && apply_filters( 'sailthru_scout_on', true ) ) {
		echo '<div id="sailthru-scout"><div class="loading">' . esc_html( __( 'Loading, please wait...', 'sailthru-for-wordpress' ) ) . '</div></div>';
	}
}

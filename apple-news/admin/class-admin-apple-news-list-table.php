<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Use WordPress List_Table class to create a custom table displaying posts
 * information and actions.
 *
 * @since 0.4.0
 */
class Admin_Apple_News_List_Table extends WP_List_Table {

	/**
	 * How many entries per page will be displayed.
	 *
	 * @var int
	 * @since 0.4.0
	 */
	public $per_page = 20;

	/**
	 * Current settings.
	 *
	 * @var Settings
	 * @since 0.9.0
	 */
	public $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings
	 */
	function __construct( $settings ) {
		$this->settings = $settings;

		parent::__construct( array(
			'singular' => __( 'article', 'apple-news' ),
			'plural'   => __( 'articles', 'apple-news' ),
			'ajax'     => false,
		) );
	}

	/**
	 * Set column defaults.
	 *
	 * @param mixed $item
	 * @param string $column_name
	 * @return string
	 * @access public
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'title':
				$default = $item[ $column_name ];
				break;
			case 'updated_at':
				$default = $this->get_updated_at( $item );
				break;
			case 'sync':
				$default = $this->get_synced_status_for( $item );
				break;
		}

		return apply_filters( 'apple_news_column_default', $default, $column_name, $item );
	}

	/**
	 * Get the updated at time.
	 *
	 * @param WP_Post $post
	 * @return string
	 * @access private
	 */
	private function get_updated_at( $post ) {
		$updated_at = get_post_meta( $post->ID, 'apple_news_api_modified_at', true );

		if ( $updated_at ) {
			return date( 'F j, h:i a', strtotime( $updated_at ) );
		}

		return __( 'Never', 'apple-news' );
	}

	/**
	 * Get the synced status.
	 *
	 * @param WP_Post $post
	 * @return string
	 * @access private
	 */
	private function get_synced_status_for( $post ) {
		$remote_id = get_post_meta( $post->ID, 'apple_news_api_id', true );

		if ( ! $remote_id ) {
			// There is no remote id, check for a delete mark
			$deleted = get_post_meta( $post->ID, 'apple_news_api_deleted', true );
			if ( $deleted ) {
				return __( 'Deleted', 'apple-news' );
			}

			// No delete mark, this has not been published yet.
			return __( 'Not published', 'apple-news' );
		}

		$updated = get_post_meta( $post->ID, 'apple_news_api_modified_at', true );
		$updated = strtotime( $updated );
		$local   = strtotime( $post->post_modified );

		if ( $local > $updated ) {
			return __( 'Needs to be updated'. 'apple-news' );
		}

		return __( 'Published', 'apple-news' );
	}

	/**
	 * This method is responsible for what is rendered in any column with a
	 * name/slug of 'title'.
	 *
	 * Every time the class needs to render a column, it first looks for a method
	 * named column_{$column_title}, if it exists, that method is run, otherwise,
	 * column_default() is called.
	 *
	 * Actions can be generated here.
	 *
	 * @param WP_Post $item
	 * @return string
	 * @access public
	 */
	public function column_title( $item ) {
		$current_screen = get_current_screen();
		if ( empty( $current_screen->parent_base ) ) {
			return;
		}

		// Build the base URL
		$base_url = add_query_arg(
			array(
				'page' => $current_screen->parent_base,
				'post_id' => $item->ID,
			),
			get_admin_url( null, 'admin.php' )
		);

		// Add common actions
		$actions = array(
			'settings' => sprintf(
				"<a href='%s'>%s</a>",
				esc_url( add_query_arg( 'action', 'settings', $base_url ) ),
				esc_html__( 'Options', 'apple-news' )
			),
			'export' => sprintf(
				"<a href='%s'>%s</a>",
				esc_url( add_query_arg( 'action', 'export', $base_url ) ),
				esc_html__( 'Download', 'apple-news' )
			),
			'push' => sprintf(
				"<a href='%s'>%s</a>",
				esc_url( add_query_arg( 'action', 'push', $base_url ) ),
				esc_html__( 'Publish', 'apple-news' )
			),
		);

		// Add the delete action, if required
		if ( get_post_meta( $item->ID, 'apple_news_api_id', true ) ) {
			$actions['delete'] = sprintf(
				"<a title='%s' href='%s'>%s</a>",
				esc_html__( 'Delete from Apple News', 'apple-news' ),
				esc_url( add_query_arg( 'action', 'delete', $base_url ) ),
				esc_html__( 'Delete', 'apple-news' )
			);
		}

		// Create the share URL
		$share_url = get_post_meta( $item->ID, 'apple_news_api_share_url', true );
		if ( $share_url ) {
			$actions['share'] = sprintf(
				"<a class='share-url-button' title='%s' href='#'>%s</a><br/><input type='text' name='share-url-%s' class='apple-share-url' value='%s' />",
				esc_html__( 'Preview in News app', 'apple-news' ),
				esc_html__( 'Copy News URL', 'apple-news' ),
				absint( $item->ID ),
				esc_url( $share_url )
			);
		}

		// Return the row action HTML
		return apply_filters( 'apple_news_column_title', sprintf( '%1$s <span>(id:%2$s)</span> %3$s',
			esc_html( $item->post_title ),
			absint( $item->ID ),
			$this->row_actions( $actions ) // can't be escaped but all elements are fully escaped above
		), $item, $actions );
	}

	/**
	 * Dictates the table columns and titles. The 'cb' column is special and, if
	 * existant, there needs to be a `column_cb` method defined.
	 *
	 * @return array An array where the key is the column slug and the value is
	 * the title text.
	 *
	 * @return array
	 * @access public
	 */
	public function get_columns() {
		return apply_filters( 'apple_news_export_list_columns', array(
			'cb'         => '<input type="checkbox">',
			'title'      => __( 'Title', 'apple-news' ),
			'updated_at' => __( 'Last updated at', 'apple-news' ),
			'sync'       => __( 'Apple News Status', 'apple-news' ),
		) );
	}

	/**
	 * Required IF using checkboxes or bulk actions. The 'cb' column gets special
	 * treatment when columns are processed. It ALWAYS needs to have it's own
	 * method.
	 *
	 * @param WP_Post $item
	 * @return string
	 * @access public
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s">',
			esc_attr( $this->_args['singular'] ),
			absint( $item->ID )
		);
	}

	/**
	 * Get bulk actions.
	 *
	 * @return array
	 * @access public
	 */
	public function get_bulk_actions() {
		return apply_filters( 'apple_news_bulk_actions', array(
			'push' => __( 'Publish', 'apple-news' ),
		) );
	}

	/**
	 * Prepare items for the table.
	 *
	 * @access public
	 */
	public function prepare_items() {
		// Set column headers. It expects an array of columns, and as second
		// argument an array of hidden columns, which in this case is empty.
		$columns = $this->get_columns();
		$this->_column_headers = array( $columns, array(), array() );

		// Data fetch
		$current_page = $this->get_pagenum();
		$query = new WP_Query( apply_filters( 'apple_news_export_table_get_posts_args', array(
			'post_type'     => $this->settings->get( 'post_types' ),
			'posts_per_page' => $this->per_page,
			'offset'         => ( $current_page - 1 ) * $this->per_page,
			'orderby'        => 'ID',
			'order'          => 'DESC',
		) ) );

		// Set data
		$this->items = $query->posts;;
		$total_items = $query->found_posts;
		$this->set_pagination_args( apply_filters( 'apple_news_export_table_pagination_args', array(
			'total_items' => $total_items,
			'per_page'    => $this->per_page,
			'total_pages' => ceil( $total_items / $this->per_page ),
		) ) );
	}

}

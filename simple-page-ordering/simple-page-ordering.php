<?php
/**
 Plugin Name: Simple Page Ordering 2 - VIP
 Plugin URI: http://10up.com/plugins/simple-page-ordering-wordpress/
 Description: Order your pages and hierarchical post types using drag and drop on the built in page list. For further instructions, open the "Help" tab on the Pages screen.
 Version: 2.0-VIP
 Author: Jake Goldman (10up LLC)
 Author URI: http://10up.com

    Plugin: Copyright 2011 Jake Goldman (email : jake@10up.com)

	 This plug-in is a derivative of Simple Page Ordering, created by Jake Goldman
	 with copyright attributions to Oomph Inc. from inception to January 2011, with
	 modifications and updates attributed to 10up LLC and Jake Goldman from February
	 2011 to March 2012.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Simple_Page_Ordering_2 {

	public function __construct() {
		add_action( 'load-edit.php', array( $this, 'load_edit_screen' ) );
		add_action( 'wp_ajax_simple_page_ordering', array( $this, 'ajax_simple_page_ordering' ) );
	}

	public function load_edit_screen() {
		$screen = get_current_screen();

		if ( !current_user_can('edit_others_pages') || ( !post_type_supports( $screen->post_type, 'page-attributes' ) && !is_post_type_hierarchical( $screen->post_type ) ) )		// check permission
			return;

		add_filter( 'views_' . $screen->id, array( $this, 'sort_by_order_link' )  );		// add view by menu order to views
		add_action( 'wp', array( $this, 'wp' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
	}

	public function wp() {
		if ( get_query_var('orderby') == 'menu_order title' ) {	// we can only sort if we're organized by menu order
			$script_name = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? 'simple-page-ordering.dev.js' : 'simple-page-ordering.js';
			wp_enqueue_script( 'simple-page-ordering', plugins_url( 'simple-page-ordering/' . $script_name, dirname( __FILE__ ) ), array('jquery-ui-sortable'), '2.0', true );
		}
	}

	public function admin_head() {
		$screen = get_current_screen();
		$screen->add_help_tab(array(
			'id' => 'simple_page_ordering_help_tab',
			'title' => __( 'Simple Page Ordering', 'simple_page_ordering' ),
			'content' => '
				<p><a href="http://www.get10up.com/plugins/simple-page-ordering-wordpress/" target="_blank">' . __( 'Simple Page Ordering', 'simple_page_ordering' ) . '</a> ' . __( 'is a plug-in by', 'simple-page-ordering' ) . ' <a href="http://www.get10up.com" target="_blank">Jake Goldman (10up)</a> ' . __( 'that  allows you to order pages and other hierarchical post types with drag and drop.', 'simple-page-ordering' ) . '</p>
				<p>' . __( 'To reposition an item, simply drag and drop the row by "clicking and holding" it anywhere (outside of the links and form controls) and moving it to its new position.', 'simple-page-ordering' ) . ' ' . __( 'If you have a large number of pages, it may be helpful to adjust the items shown per page by opening the Screen Options tab.', 'simple-page-ordering' ) . '</p>
			',
		));
	}

	public function ajax_simple_page_ordering() {
		// check permissions again and make sure we have what we need
		if ( !current_user_can( 'edit_others_pages' ) || empty( $_POST['id'] ) || ( !isset( $_POST['previd'] ) && !isset( $_POST['nextid'] ) ) )
			die(-1);

		// real post?
		if ( ! $post = get_post( $_POST['id'] ) )
			die(-1);

		$previd = empty( $_POST['previd'] ) ? false : (int) $_POST['previd'];
		$nextid = empty( $_POST['nextid'] ) ? false : (int) $_POST['nextid'];
		$start = empty( $_POST['start'] ) ? 0 : (int) $_POST['start'];
		$new_pos = array(); // store new positions for ajax

		// attempt to get the intended parent... if either sibling has a matching parent ID, use that
		$parent_id = $post->post_parent;
  		$next_post_parent = $nextid ? wp_get_post_parent_id( $nextid ) : false;
  		if ( $previd == $next_post_parent )
  			$parent_id = $next_post_parent;
		elseif ( $next_post_parent !== $parent_id ) {
  			$prev_post_parent = $previd ? wp_get_post_parent_id( $previd ) : false;
			if ( $prev_post_parent !== $parent_id )
				$parent_id = ( $prev_post_parent !== false ) ? $prev_post_parent : $next_post_parent;
  		}

		$siblings = get_posts(array(
			'depth' => 1,
			'numberposts' => -1,
			'post_type' => $post->post_type,
			'post_status' => array( 'publish' , 'pending', 'draft', 'future', 'private' ),
			'post_parent' => $parent_id,
			'orderby' => 'menu_order title',
			'order' => 'ASC',
		)); // fetch all the siblings (relative ordering)

		$max_sortable_posts = (int) apply_filters( 'simple_page_ordering_limit', 30 );	// should reliably be able to do about 30 at a time
		if ( $max_sortable_posts <= 0 )
			$max_sortable_posts = 30;

		$menu_order = 0;

		// don't waste overhead of revisions on a menu order change (especially since they can't *all* be rolled back at once)
		remove_action( 'pre_post_update', 'wp_save_post_revision' );

		foreach( $siblings as $sibling ) :

			// because their can be muliple requests for ordering to prevent time outs
			if ( $start > $menu_order ) {
				$new_pos[$sibling->ID] = $menu_order;
				$menu_order++;
				$max_sortable_posts++;
				continue;
			}

			// don't handle the actual post
			if ( $sibling->ID == $post->ID )
				continue;

			// if this is the post that comes after our repositioned post, set our repositioned post position and increment menu order
			if ( $nextid == $sibling->ID ) {
				wp_update_post(array( 'ID' => $post->ID, 'menu_order' => $menu_order, 'post_parent' => $parent_id ));
				$ancestors = get_post_ancestors( $post->ID );
				$new_pos[$post->ID] = array( 'menu_order' => $menu_order, 'post_parent' => $parent_id, 'depth' => count($ancestors) );
				$menu_order++;
			}

			// if repositioned post has been set, and new items are already in the right order, we can stop
			if ( isset( $new_pos[$post->ID] ) && $sibling->menu_order >= $menu_order )
				break;

			// set the menu order of the current sibling and increment the menu order
			wp_update_post(array( 'ID' => $sibling->ID, 'menu_order' => $menu_order ));
			$new_pos[$sibling->ID] = $menu_order;
			$menu_order++;

			if ( !$nextid && $previd == $sibling->ID ) {
				wp_update_post(array( 'ID' => $post->ID, 'menu_order' => $menu_order, 'post_parent' => $parent_id ));
				$ancestors = get_post_ancestors( $post->ID );
				$new_pos[$post->ID] = array( 'menu_order' => $menu_order, 'post_parent' => $parent_id, 'depth' => count($ancestors) );
				$menu_order++;
			}

			// max per request
			if ( $max_sortable_posts <= $menu_order ) {
				$next_data = array( 'id' => $post->ID, 'previd' => $previd, 'nextid' => $nextid, 'start' => end($new_pos) );
				die( json_encode($next_data) );
			}

		endforeach;

		// if the moved post has children, we need to refresh the page
		$children = get_posts(array(
			'numberposts' => 1,
			'post_type' => $post->post_type,
			'post_status' => array('publish','pending','draft','future','private'),
			'post_parent' => $post->ID
		));

		if ( ! empty( $children ) )
			die('children');

		die( json_encode($new_pos) );
	}

	public function sort_by_order_link( $views ) {
		$class = ( get_query_var('orderby') == 'menu_order title' ) ? 'current' : '';
		$query_string = remove_query_arg(array( 'orderby', 'order' ));
		$query_string = add_query_arg( 'orderby', urlencode('menu_order title'), $query_string );
		$views['byorder'] = '<a href="'. $query_string . '" class="' . $class . '">Sort by Order</a>';
		return $views;
	}
}

$simple_page_ordering_2 = new Simple_Page_Ordering_2;
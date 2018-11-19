<?php
/**
 * Sticky Custom Post Types
 *
 * @package  StickyCustomPostTypes
 * @license  GPL2
 *
 * @wordpress-plugin
 * Plugin Name:       Sticky Custom Post Types
 * Plugin URI:        http://superann.com/sticky-custom-post-types/
 * Description:       Enables support for sticky custom post types. Set options in Settings &rarr; Reading.
 * Version:           1.3 WPCOM
 * Author:            Ann Oyama
 * Author URI:        http://superann.com
 * Text Domain:       sticky-custom-post-types
 * Requires WP:       3.0
 * Tested up to:      4.9
 * License:           GPL2
 */

/*
Copyright 2011 Ann Oyama  (email : wordpress [at] superann.com)

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

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Sticky_Custom_Post_Types' ) ) {

	/**
	 * Sticky Custom Post Types Class.
	 */
	class Sticky_Custom_Post_Types {

		/**
		 * Class Constructor.
		 *
		 * @return void
		 */
		public function __construct() {
			add_filter( 'post_class', [ $this, 'sticky_class' ], 10, 3 );
			add_action( 'admin_init', [ $this, 'add_sticky_settings' ], 20 );
			add_action( 'admin_init', [ $this, 'sticky_meta_box' ] );
			add_action( 'pre_get_posts', [ $this, 'add_sticky_post_types_to_home' ] );
		}

		/**
		 * Add sticky class in article to style sticky posts differently.
		 *
		 * @param array $classes An array of post classes.
		 * @param array $class   An array of additional classes added to the post.
		 * @param int   $post_id Post ID.
		 *
		 * @return array
		 */
		public function sticky_class( $classes, $class, $post_id ) {
			if ( is_sticky( $post_id ) ) {
				$classes[] = 'sticky';
			}

			return $classes;
		}

		/**
		 * Add sticky settings.
		 *
		 * @return void
		 */
		public function add_sticky_settings() {
			register_setting( 'reading', 'sticky_custom_post_types' );
			register_setting( 'reading', 'sticky_custom_post_types_filters' );

			add_settings_section(
				'super_sticky_options',
				__( 'Sticky Custom Post Types', 'sticky-custom-post-types' ),
				[ $this, 'sticky_description' ],
				'reading'
			);

			add_settings_field(
				'sticky_custom_post_types',
				__( 'Show "Stick this..." checkbox on', 'sticky-custom-post-types' ),
				[ $this, 'set_sticky_post_types' ],
				'reading',
				'super_sticky_options'
			);

			add_settings_field(
				'sticky_custom_post_types_filters',
				__( 'Display selected post type(s) on', 'sticky-custom-post-types' ),
				[ $this, 'set_sticky_filters' ],
				'reading',
				'super_sticky_options'
			);
		}

		/**
		 * Sticky Description.
		 *
		 * @return void
		 */
		public function sticky_description() {
			echo '<p>' . esc_html__( 'Enable support for sticky custom post types.', 'sticky-custom-post-types' ) . '</p>'; // WPCS: XSS ok.
		}

		/**
		 * Set sticky options.
		 *
		 * @return void
		 */
		public function set_sticky_post_types() {
			$post_types = get_post_types( [
				'_builtin' => false,
				'public'   => true,
			], 'names' );

			if ( empty( $post_types ) ) {
				echo '<p>' . esc_html__( 'No public custom post types found.', 'sticky-custom-post-types' ) . '</p>'; // WPCS: XSS ok.
			}

			$checked_post_types = $this->get_sticky_post_types();
			foreach ( $post_types as $post_type ) { ?>
				<div>
					<input
						type="checkbox"
						id="<?php echo esc_attr( 'post_type_' . $post_type ); ?>"
						name="sticky_custom_post_types[]"
						value="<?php echo esc_attr( $post_type ); ?>"
						<?php checked( in_array( $post_type, $checked_post_types, true ) ); ?>
					/>

					<label for="<?php echo esc_attr( 'post_type_' . $post_type ); ?>">
						<?php echo esc_html( $post_type ); ?>
					</label>
				</div>
				<?php
			}
		}

		/**
		 * Settings sticky filters.
		 *
		 * @return void
		 */
		public function set_sticky_filters() {
			?>
			<span>
				<input
					type="checkbox"
					id="sticky_custom_post_types_filters_home"
					name="sticky_custom_post_types_filters[]"
					value="home"
					<?php checked( $this->super_sticky_filter( 'home' ) ); ?>
				/>

				<label for="sticky_custom_post_types_filters_home">
					<?php esc_html_e( 'home', 'sticky-custom-post-types' ); ?>
				</label>
			</span>
			<?php
		}

		/**
		 * Sticky Meta Box.
		 *
		 * @return void
		 */
		public function sticky_meta_box() {
			$post_types = $this->get_sticky_post_types();

			// Bail if no post types to add to.
			if ( empty( $post_types ) ) {
				return;
			}

			// Bail if user cannot edit posts.
			if ( ! current_user_can( 'edit_others_posts' ) ) {
				return;
			}

			// Add the meta box.
			add_meta_box(
				'super_sticky_meta',
				__( 'Sticky', 'sticky-custom-post-types' ),
				[ $this, 'sticky_meta' ],
				$post_types,
				'side',
				'high'
			);
		}

		/**
		 * Sticky Input.
		 *
		 * @return void
		 */
		public function sticky_meta() {
			?>
			<input
				id="super-sticky"
				name="sticky"
				type="checkbox"
				value="sticky"
				<?php checked( is_sticky( get_the_ID() ) ); ?>
			/>
			<label for="super-sticky" class="selectit">
				<?php esc_html_e( 'Stick this to the front page', 'sticky-custom-post-types' ); ?>
			</label>
			<?php
		}

		/**
		 * Filter posts with sticky option.
		 *
		 * @param  object $query The main query.
		 *
		 * @return void
		 */
		public function add_sticky_post_types_to_home( $query ) {

			if ( ! $query->is_main_query() || is_admin() ) {
				return;
			}

			if ( $query->is_home() && ! $query->get( 'suppress_filters' ) && $this->super_sticky_filter( 'home' ) ) {

				$get_sticky_post_types = $this->get_sticky_post_types();

				if ( ! empty( $get_sticky_post_types ) ) {
					$post_types      = [];
					$query_post_type = $query->get( 'post_type' );

					if ( empty( $query_post_type ) ) {
						$post_types[] = 'post';
					} elseif ( is_string( $query_post_type ) ) {
						$post_types[] = $query_post_type;
					} elseif ( is_array( $query_post_type ) ) {
						$post_types = $query_post_type;
					} else {
						return; // Unexpected value.
					}

					$post_types = array_merge( $post_types, $get_sticky_post_types );

					if ( ! empty( $post_types ) ) {
						$query->set( 'post_type', $post_types );
						$query->set( 'ignore_sticky_posts', false );
					}
				}
			}
		}

		/**
		 * Get sticky post types.
		 *
		 * @return array
		 */
		protected function get_sticky_post_types() {
			return (array) get_option( 'sticky_custom_post_types', [] );
		}

		/**
		 * Confirm supported query filter.
		 *
		 * @param  string $query_type Query type.
		 *
		 * @return boolean
		 */
		protected function super_sticky_filter( $query_type ) {
			$filters = get_option( 'sticky_custom_post_types_filters' );

			if ( empty( $filters ) || ! is_array( $filters ) ) {
				$filters = [];
			}

			return in_array( $query_type, $filters, true );
		}
	}

	$sticky_post_types = new Sticky_Custom_Post_Types();
}

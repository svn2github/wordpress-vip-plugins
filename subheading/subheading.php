<?php
/*
Plugin Name: SubHeading
Plugin URI: http://wordpress.org/extend/plugins/subheading/
Description: Adds the ability to show a subheading for posts, pages and custom post types. To display subheadings place <code>&lt;?php the_subheading(); ?&gt;</code> in your template file. 
Version: 1.6.9
Author: StvWhtly
Author URI: http://stv.whtly.com
*/
if ( ! class_exists( 'SubHeading' ) ) {
	class SubHeading
	{
		var $name = 'SubHeading';
		var $tag = 'subheading';
		var $meta_key = null;
		var $options = null;
		function SubHeading()
		{
			if ( $options = get_option( $this->tag ) ) {
				$this->options = $options;
			}	
			$this->meta_key = '_' . $this->tag;
			if ( is_admin() ) {
				add_action( 'admin_menu', array( &$this, 'meta' ) );
				add_action( 'save_post', array( &$this, 'save' ) );
				add_action( 'admin_init', array( &$this, 'settings_init' ) );
				if ( isset( $this->options['lists'] ) ) {
					add_action( 'admin_enqueue_scripts', array( &$this, 'admin' ), 10, 1 );
				}
				add_filter( 'plugin_row_meta', array( &$this, 'settings_meta' ), 10, 2 );
				register_activation_hook( __FILE__, array( &$this, 'activate' ) );
			} else {
				add_filter( 'the_title_rss', array( &$this, 'rss' ) );
				add_filter( 'the_subheading', array( &$this, 'build' ), 1 );
				add_filter( 'the_content', array( &$this, 'append' ) );
				if ( isset( $this->options['search'] ) ) {
					add_action( 'posts_where_request', array( &$this, 'search' ) );
				}
			}
		}
		function activate()
		{
			if ( ! $this->options ) {
				update_option( $this->tag, array(
					'search' => 1,
					'post_types' => array('page'),
					'rss' => 1,
					'lists' => 1,
					'append' => 1,
					'before' => '<h3>',
					'after' => '</h3>'
				) );
			} else {
				$this->options['post_types'] = array( 'page' );
				if (isset($this->options['posts'])) {
					$this->options['post_types'][] = 'post';
				}
				unset($this->options['posts']);
				update_option( $this->tag, $this->options );
			}
		}
		function build( $args )
		{
			extract( $args );
			if ( $value = $this->value( $id ) ) {
				if ( isset( $this->options['tags'] ) ) {
					$value = do_shortcode( $value );
				}
				$subheading = $before . $value . $after;
				if ( $display == true ) {
					echo $subheading;
				} else {
					return $subheading;
				}
			}
			return null;
		}
		function meta()
		{
			if ( isset( $this->options['post_types'] ) && is_array( $this->options['post_types'] ) ) {
				foreach ( $this->options['post_types'] AS $type ) {
					$this->meta_box( $type );
				}
			}
		}
		function meta_box( $type='page' )
		{
			add_meta_box(
				$this->tag . '_postbox',
				$this->name, array( &$this, 'panel' ),
				$type,
				'normal',
				'high'
			);
		}
		function panel()
		{
			include_once( 'panel.php' );
		}
		function save( $post_id )
		{
			global $post_type;
			if ( ! isset( $_POST[$this->tag.'nonce'] ) || ! wp_verify_nonce( $_POST[$this->tag . 'nonce'], 'wp_' . $this->tag ) ) {
				return $post_id;
			}
			$post_type_object = get_post_type_object( $post_type );
			if ( ! current_user_can( $post_type_object->cap->edit_post, $post_id ) ) {
				return $post_id;
			}
			$subheading = wp_kses( $_POST[$this->tag . '_value'], $this->allowed_tags() );
			if ( empty( $subheading ) ) {
				delete_post_meta( $post_id, $this->meta_key, $subheading );
			} else if ( ! update_post_meta( $post_id, $this->meta_key, $subheading ) ){
				add_post_meta( $post_id, $this->meta_key, $subheading, true );
			}
		}
		function allowed_tags()
		{
			global $allowedtags;
			$tags = $allowedtags;
			return apply_filters( 'subheading_tags', $tags );
		}
		function value( $id=false )
		{
			global $post;
			return get_post_meta( ( $id !== false ? $id : $post->ID ), $this->meta_key, true );
		}
		function rss( $title )
		{
			if ( isset( $this->options['rss'] ) && $subheading = $this->value()) {
				return $title . ' - ' . esc_html( strip_tags( $subheading ) );
			}
			return $title;
		}
		function append( $content )
		{
			if ( $this->is_main_query() && isset( $this->options['append'] ) && $subheading = $this->value() ) {
				if ( isset($this->options['before'] ) && isset( $this->options['after'] ) ) {
					return $this->options['before'] . $subheading . $this->options['after'] . $content;
				}
				return wpautop( $subheading ) . $content;
			}
			return $content;
		}
		function column_heading( $columns )
		{
			$columns[$this->tag] = $this->name;
			return $columns;
		}
		function column_value( $column, $post_id )
		{
			if ( $column == $this->tag) {
				$this->build(array(
					'id' => $post_id,
					'before' => '',
					'after' => '',
					'display' => true
				));
			}
		}
		function admin( $hook )
		{
			if ( in_array( $hook, array( 'edit.php', 'edit-pages.php', 'options-reading.php' ) ) ) {
				wp_enqueue_script( $this->name, plugins_url( $this->tag.'/admin.js' , dirname( __FILE__ ) ) );
				if ( isset( $this->options['post_types'] ) && is_array( $this->options['post_types'] ) ) {
					foreach ( $this->options['post_types'] AS $post_type ) {
						if ( in_array( $post_type, array( 'post', 'page' )) ) {
							$post_type .= 's';
						}
						add_filter( 'manage_'.$post_type.'_columns', array( &$this, 'column_heading' ) );
						add_filter( 'manage_'.$post_type.'_custom_column', array( &$this, 'column_value' ), 10, 2 );
					}
				}
			}
		}
		function settings_init()
		{
			$description = 'Configuration options for the <a href="http://wordpress.org/extend/plugins/' . $this->tag . '/" target="_blank">' . $this->name . '</a> plugin.';
		 	add_settings_field(
		 		$this->tag . '_settings',
				$this->name . ' <div class="description">' . $description . '</div>',
				array(&$this, 'settings_fields'),
				'reading',
				'default'
		 	);
		 	register_setting(
		 		'reading',
		 		$this->tag,
		 		array(&$this, 'settings_validate')
		 	);
		}
		function settings_validate($inputs)
		{
			if ( is_array( $inputs ) ) {
				foreach ( $inputs AS $key => $input ) {
					if ( in_array( $key, array( 'before', 'after', 'post_types' ) ) ) {
						if ( 'post_types' == $key ) {
							$post_types = array();
							$settings_post_types = $this->settings_post_types( 'names' );
							foreach ( $inputs[$key] AS $post_type ) {
								if ( in_array( $post_type, $settings_post_types ) ) {
									$post_types[] = $post_type;
								}
							}
							$inputs[$key] = $post_types;
						} else {
							$inputs[$key] = wp_kses( $inputs[$key], $this->settings_allowed_tags() );
						}
						if ( empty( $inputs[$key] ) ) {
							unset( $inputs[$key] );
						}
					} else {
						$inputs[$key] = ( $inputs[$key] == 1 ? 1 : 0 );
					}
				}
				return $inputs;
			}
		}
		function settings_allowed_tags()
		{
			global $allowedtags;
			$settings_tags = $allowedtags;
			$additional_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p' );
			foreach ( $additional_tags AS $tag ) {
				$settings_tags[$tag] = array(
					'class' => array(),
					'id' => array()
				);
			}
			return apply_filters( 'subheading_settings_tags', $settings_tags );
		}
		function settings_fields()
		{
			$fields = array(
				'search' => 'Allow search to find matches based on subheading values.',
				'rss' => 'Append to RSS feeds.',
				'reposition' => 'Prevent reposition of input under the title when editing.',
				'lists' => 'Display on admin edit lists.',
				'tags' => 'Apply shortcode filters.',
				'lists' => 'Display on admin edit lists.',
				'append' => array(
					'description' => 'Automatically display subheadings before post content.',
					'break' => false
				),
				'before' => array(
					'description' => 'Before:',
					'value' => ( isset( $this->options['before'] ) ? esc_attr( $this->options['before'] ) : '' ),
					'type' => 'text',
					'break' => false,
					'prepend' => true
				),
				'after' => array(
					'description' => 'After:',
					'value' => ( isset( $this->options['after'] ) ? esc_attr( $this->options['after'] ) : '' ),
					'type' => 'text',
					'prepend' => true
				),
			);
			$post_types = $this->settings_post_types();
			unset( $post_types['attachment'] );
			foreach ( $post_types AS $id => $post_type ) {
				$fields['post_type_'.$id] = array(
					'description' => 'Enable on ' . $post_type->labels->name . '.',
					'name' => $this->tag . '[post_types][]',
					'value' => $id,
					'options' => ( isset( $this->options['post_types'] ) ? $this->options['post_types'] : array() )
				);
			}
			foreach ( $fields AS $id => $field ) {
				if ( ! is_array( $field ) ) {
					$field = array( 'description' => $field );
				}
				if ( ! isset( $field['options'] ) ) {
					$field['options'] = $this->options;
				}
				?>
				<label>
					<?php if ( isset( $field['prepend'] ) && $field['prepend'] === true ) : ?>
					<?php _e( $field['description'] ); ?>
					<?php endif; ?>
					<input name="<?php _e( isset( $field['name'] ) ? $field['name'] : $this->tag . '[' . $id . ']' ); ?>"
						type="<?php _e( isset( $field['type'] ) ? $field['type'] : 'checkbox' ); ?>"
						id="<?php _e( $this->tag . '_' . $id ); ?>"
						value="<?php _e( isset( $field['value'] ) ? $field['value'] : 1 ); ?>"
						<?php if ( isset( $field['options'][$id] ) || ( isset( $field['value'] ) && is_array( $field['options'] ) && in_array( $field['value'], $field['options'] ) ) )  { echo 'checked="checked"'; } ?> />
					<?php if ( ! isset( $field['prepend'] ) || $field['prepend'] == false ) : ?>
					<?php _e( $field['description'] ); ?>
					<?php endif; ?>
				</label>
				<?php if ( ! isset( $field['break'] ) || $field['break'] === true ) : ?><br /><?php endif; ?>
				<?php
			}
		}
		function settings_meta( $links, $file )
		{
			$plugin = plugin_basename( __FILE__ );
			if ( $file == $plugin ) {
				return array_merge(
					$links,
					array( '<a href="'.admin_url('options-reading.php').'">Settings</a>' )
				);
			}
			return $links;
		}
		function settings_post_types($output = 'objects')
		{
			return get_post_types( array( 'public' => true ), $output );
		}
		function search( $where )
		{
			if ( is_search() ) {
				global $wpdb, $wp;
				$where = preg_replace(
					"/\({$wpdb->posts}.post_title (LIKE '%{$wp->query_vars['s']}%')\)/i",
					"$0 OR ($wpdb->postmeta.meta_value $1)",
					$where
				);
				add_filter( 'posts_join_request', array( &$this, 'search_join' ) );
			}
			return $where;
		}
		function search_join( $join )
		{
			global $wpdb;
			return $join .= " LEFT JOIN $wpdb->postmeta ON ($wpdb->postmeta.meta_key = '_{$this->tag}' AND $wpdb->posts.ID = $wpdb->postmeta.post_id) ";
		}
		function is_main_query() {
			if ( function_exists( 'is_main_query' ) && is_main_query() ) {
				return true;
			} else {
				global $query, $wp_the_query;
				if ( $query === $wp_the_query ) {
					return true;
				}
			}
			return false;
		}
	}
	$subHeading = new SubHeading();
	function the_subheading($b='',$a='',$d=true,$i=false)
	{
		return apply_filters(
			'the_subheading',
			array('before'=>$b,'after'=>$a,'display'=>$d,'id'=>$i)
		);
	}
	function get_the_subheading($i=false,$b='',$a='')
	{
		return apply_filters(
			'the_subheading',
			array('before'=>$b,'after'=>$a,'display'=>false,'id'=>$i)
		);
	}	
}
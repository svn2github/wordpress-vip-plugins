<?php
/*
Plugin Name: Publishing Checklist
Version: 0.2.0-alpha
Description: Pre-flight your posts.
Author: Fusion Engineering
Author URI: http://fusion.net/
Plugin URI: https://github.com/fusioneng/publishing-checklist
Text Domain: publishing-checklist
Domain Path: /languages
*/

define( 'PUBLISHING_CHECKLIST_VERSION', '0.2.0-alpha' );

class Publishing_Checklist {

	private static $instance;
	private $tasks = array();

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Publishing_Checklist;
			self::$instance->setup_actions();
			do_action( 'publishing_checklist_init' );

			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				require_once dirname( __FILE__ ) . '/inc/class-cli-command.php';
			}
		}

		return self::$instance;
	}

	/**
	 * Set up actions for the plugin
	 */
	private function setup_actions() {
		add_action( 'publishing_checklist_enqueue_scripts', array( $this, 'action_publishing_checklist_enqueue_scripts' ) );
		add_action( 'post_submitbox_misc_actions', array( $this, 'action_post_submitbox_misc_actions_render_checklist' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'action_manage_posts_custom_column' ), 10, 2 );
		add_filter( 'manage_posts_columns', array( $this, 'filter_manage_posts_columns' ), 99 );
	}

	/**
	 * Register a validation task for our publishing checklist
	 *
	 * @param string $id Unique identifier for the task (can be arbitrary, as long as it doesn't conflict with others)
	 * @param string $label Human-friendly label for the task
	 * @param mixed $callback Callable function or method to indicate whether or not the task has been complete
	 * @param string $explanation A longer description as to what needs to be accomplished for the task
	 */
	public function register_task( $id, $args = array() ) {

		$defaults = array(
			'label'          => $id,
			'callback'       => '__return_false',
			'explanation'    => '',
			'post_type'      => array(),
			);
		$args = array_merge( $defaults, $args );

		$this->tasks[ $id ] = $args;
	}

	/**
	 * Render the checklist in the publish submit box
	 */
	public function action_post_submitbox_misc_actions_render_checklist() {
		$post_id = get_the_ID();
		$tasks_completed = $this->evaluate_checklist( $post_id );
		if ( $tasks_completed ) {
			do_action( 'publishing_checklist_enqueue_scripts' );
			echo $this->get_template_part( 'post-submitbox-misc-actions',
				array(
					'tasks' => $tasks_completed['tasks'],
					'completed_tasks' => $tasks_completed['completed'],
				)
			);
		}
	}

	/**
	* Evaluate tasks for a post
	*
	* @param string $post_id WordPress post ID
	*
	*/
	public function evaluate_checklist( $post_id ) {

		if ( empty( $post_id ) ) {
			return false;
		}

		if ( empty( $this->tasks ) ) {
			return false;
		}

		$post_type = get_post_type( $post_id );

		$completed_tasks = array();
		foreach ( $this->tasks as $task_id => $task ) {
			if ( ! is_callable( $task['callback'] ) ) {
				unset( $this->tasks[ $task_id ] );
			}

			if ( ! empty( $task['post_type'] ) && ! in_array( $post_type, $task['post_type'] ) ) {
				unset( $this->tasks[ $task_id ] );
			}

			if ( call_user_func_array( $task['callback'], array( $post_id, $task_id ) ) ) {
				$completed_tasks[] = $task_id;
			}
		}

		if ( empty( $this->tasks ) ) {
			return false;
		}

		$checklist_data = array(
			'tasks' => $this->tasks,
			'completed' => $completed_tasks,
		);

		return $checklist_data;

	}

	/**
	 * Load our scripts and styles
	 */
	public function action_publishing_checklist_enqueue_scripts() {
		wp_enqueue_style( 'publishing-checklist', plugins_url( 'assets/css/publishing-checklist.css', __FILE__ ), false, PUBLISHING_CHECKLIST_VERSION );
		wp_enqueue_script( 'publishing-checklist', plugins_url( 'assets/js/src/publishing-checklist.js', __FILE__ ), array( 'jquery' ), PUBLISHING_CHECKLIST_VERSION );
	}

	/**
	 * Get a rendered template part
	 *
	 * @param string $template
	 * @param array $vars
	 * @return string
	 */
	private function get_template_part( $template, $vars = array() ) {
		$full_path = dirname( __FILE__ ) . '/templates/' . sanitize_file_name( $template ) . '.php';

		if ( ! file_exists( $full_path ) ) {
			return '';
		}

		ob_start();
		// @codingStandardsIgnoreStart
		if ( ! empty( $vars ) ) {
			extract( $vars );
		}
		// @codingStandardsIgnoreEnd
		include $full_path;
		return ob_get_clean();
	}

	/**
	 * Customize columns on the "Manage Posts" views
	 */
	public function filter_manage_posts_columns( $columns ) {

		foreach ( $this->tasks as $task_id => $task ) {
			if ( ! is_callable( $task['callback'] ) ) {
				unset( $this->tasks[ $task_id ] );
			}

			if ( ! empty( $task['post_type'] ) && ! in_array( get_post_type(), $task['post_type'] ) ) {
				unset( $this->tasks[ $task_id ] );
			}
		}

		if ( empty( $this->tasks ) ) {
			return $columns;
		}

		$columns['publishing_checklist'] = esc_html__( 'Publishing Checklist', 'publishing-checklist' );
		do_action( 'publishing_checklist_enqueue_scripts' );
		return $columns;
	}

	/**
	 * Handle the output for a custom column
	 */
	public function action_manage_posts_custom_column( $column_name, $post_id ) {
		if ( 'publishing_checklist' === $column_name ) {
			$tasks_completed = $this->evaluate_checklist( $post_id );
			echo $this->get_template_part( 'column-checklist', array(
				'tasks' => $tasks_completed['tasks'],
				'completed_tasks' => $tasks_completed['completed'],
			) );
		}
	}
}

/**
 * Load the plugin
 */
// @codingStandardsIgnoreStart
function Publishing_Checklist() {
// @codingStandardsIgnoreEnd
	return Publishing_Checklist::get_instance();
}
add_action( 'init', 'Publishing_Checklist' );

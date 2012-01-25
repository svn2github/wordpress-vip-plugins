<?php
/*
 * Plugin Name: Engage
 * Plugin URI: http://engagewidget.com
 * Description: Get recommendations of articles that might interest you
 * Author: Dean Attali
 * Version: 1.0
 * Author URI: http://www.contextlogic.com
 */

class EngageCLWidget extends WP_Widget {
	function __construct() {
		$widget_ops = array( 'classname' => 'EngageCLWidget', 'description' => 'Get recommendations of articles that might interest you' );
		parent::__constuct( 'EngageCLWidget', 'Engage', $widget_ops );
	}

	function form( $instance ) {
		$defaults = array( 'title' => '' , 'bg_color' => '#F5F5F5' );
		$instance = wp_parse_args( (array) $instance, $defaults );
		$title = $instance['title'];
		$bg_color = $instance['bg_color'];
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Title:
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
					name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
					value="<?php echo esc_attr( $title ); ?>" />
			</label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'bg_color' ); ?>">Background Color:
				<input class="widefat" id="<?php echo $this->get_field_id( 'bg_color' ); ?>"
					name="<?php echo $this->get_field_name( 'bg_color' ); ?>" type="text"
					value="<?php echo esc_attr( $bg_color ); ?>" />
			</label>
		</p>
		<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$new_color = $new_instance['bg_color'];
		if ( preg_match( '/^#([0-9a-f]{1,2}){3}$/i', $new_color ) ) {
			$instance['bg_color'] = $new_color;
		}
		return $instance;
	}

	function init_engage_cl_widget( $instance ) {
		$engageJsUrl = 'http://www.engagewidget.com/static/js/show_widget.js';
		echo '<script type=\'text/javascript\'>'
			.'var cl_ad=1;var cl_refer=\'wordpress\';var cl_bg_color=\'' . esc_js( $instance['bg_color'] ) . '\';'
			.'var cl_link=window.location.href;'
			.'document.write(unescape("%3Cscript src=\'' . $engageJsUrl . '\' '
			.'type=\'text/javascript\'%3E%3C/script%3E"));'
			.'</script>';
	}

	function widget( $args, $instance ){
		extract( $args, EXTR_SKIP );

		$title = empty( $instance['title'] ) ? ' ' : apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . esc_html( $title ) . $after_title;
		$this->init_engage_cl_widget( $instance );
		echo $after_widget;
	}
}
add_action( 'widgets_init', create_function( '', 'register_widget( "EngageCLWidget" );' ) );



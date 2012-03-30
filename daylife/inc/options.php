<?php

class Daylife_Options {
	public static $instance;

	public function __construct() {
		self::$instance = $this;
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );
	}

	public function add_menu_page() {
		$options_page = add_options_page( __( 'Daylife Options', 'daylife' ), __( 'Daylife', 'daylife' ), 'manage_options', 'daylife-options', array( $this, 'render_options_page' ) );
		add_action( "load-$options_page", array( $this, 'help' ) );
	}

	public function help() {
		$screen = get_current_screen();
		$screen->add_help_tab( array(
			'id'      => 'daylife-about',
			'title'   => __( 'About', 'daylife' ),
			'content' => __( '<p>The Daylife Images Plugin helps you find images related to your posts, and add them as a single image or to a gallery to your post. The plugin will recommend images based on the text of your post. You can also type in keywords in the Search box to find images relevant to your post.</p>', 'dayflife' )
		) );
		$screen->add_help_tab( array(
			'id'      => 'daylife-licenses',
			'title'   => __( 'Licenses & Pricing', 'daylife' ),
			'content' => __( '<p>This plugin helps you find licensed images from sources like Getty, AP, Reuters and more. See the complete list of all the content partners <a href="http://www.daylife.com/about-us/our-partnerships/">here</a>. To learn more about these Image Licenses and their pricing, drop an email to <a href="mailto:getdaylife@daylife.com">getdaylife@daylife.com</a>.</p>', 'dayflife' )
		) );
		$screen->add_help_tab( array(
			'id'      => 'daylife-getting-started',
			'title'   => __( 'Getting Started', 'daylife' ),
			'content' => __( '<p>Please drop an email to <a href="mailto:getdaylife@daylife.com">getdaylife@daylife.com</a>, and they will set your Plugin Settings - your Accesskey, SharedSecret and a Source Filter with access to your licensed sources.</p>', 'dayflife' )
		) );
		$screen->set_help_sidebar( __( '<p><strong>For more information:</strong></p><p><a href="http://www.daylife.com/">Daylife</a></p>', 'daylife' ) );
	}

	public function settings_init() {
		register_setting( 'daylife_options', 'daylife', array( $this, 'sanitize_settings' ) );
		add_settings_section( 'daylife-general', '', '__return_false', 'daylife-options' );
		add_settings_field( 'daylife-access-key', __( 'Access Key', 'daylife' ), array( $this, 'text_box' ), 'daylife-options', 'daylife-general', array( 'id' => 'daylife-access-key', 'name' => 'access_key' ) );
		add_settings_field( 'daylife-shared-secret', __( 'Shared Secret', 'daylife' ), array( $this, 'text_box' ), 'daylife-options', 'daylife-general', array( 'id' => 'daylife-shared-secret', 'name' => 'shared_secret' ) );
		add_settings_field( 'daylife-source-filter-id', __( 'Source Filter ID', 'daylife' ), array( $this, 'text_box' ), 'daylife-options', 'daylife-general', array( 'id' => 'daylife-source-filter-id', 'name' => 'source_filter_id' ) );
		add_settings_field( 'daylife-api-endpoint', __( 'Daylife API Endpoint', 'daylife' ), array( $this, 'text_box' ), 'daylife-options', 'daylife-general', array( 'id' => 'daylife-api-endpoint', 'name' => 'api_endpoint' ) );
	}

	public function text_box( $args ) {
		$options = get_option( 'daylife', array() );
		if ( ! isset( $options[ $args['name'] ] ) )
			$options[ $args['name'] ] = '';
		?><input type="text" id="<?php echo esc_attr( $args['id'] ); ?>" name="daylife[<?php echo esc_attr( $args['name'] ); ?>]" value="<?php echo esc_attr( $options[ $args['name'] ] ); ?>" class="regular-text" /><?php
	}

	public function sanitize_settings( $options ) {
		foreach ( $options as $option_key => &$option_value ) {
			switch ( $option_key ) {
				default:
					$option_value = esc_attr( $option_value );
					break;
			}
		}
		return $options;
	}

	public function render_options_page() {
		?>
		<style type="text/css" media="screen">
			#icon-daylife {
				background: transparent url(<?php echo plugins_url( 'images/daylife32.png', dirname( __FILE__ ) ); ?>) no-repeat;
			}
		</style>

		<div class="wrap">
			<?php screen_icon( 'daylife' ); ?>
			<h2><?php _e( 'Daylife Settings', 'daylife' ); ?></h2>
			 <p><?php _e( 'This plugin helps you find licensed images from sources like Getty, AP, Reuters and more. See the complete list of all the content partners <a href="http://www.daylife.com/about-us/our-partnerships/">here</a>. To learn more about these Image Licenses and their pricing, drop an email to <a href="mailto:getdaylife@daylife.com">getdaylife@daylife.com</a>.', 'dayflife' ); ?></p>
			<form method="post" action="options.php">
				<?php
					settings_fields( 'daylife_options' );
					do_settings_sections( 'daylife-options' );
					submit_button();
				?>
			</form>
		</div><?php
	}
}

new Daylife_Options;
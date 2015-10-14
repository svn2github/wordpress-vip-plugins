<?php
/*
Plugin Name: VIP Staging
Description: Enables quick staging on your VIP website. No not enable it.
Version: 0.0.1
Author: Team 1
*/

class VIP_Staging {

	const SUFIX = '-live';

    /**
     * VIP_Staging constructor
     *
     * Loading the required hooks and filters for the staging functionality
     * to be added to a blog.
     *
     * The action hooks and filters are only loaded for a specific user a stagin
     * enabled site.
     */
	public function __construct() {

		// TODO: remove this, just for testing
		if( isset($_GET['toggle_staging']) ) {

			$this->toggle_staging();

		}

		// Only load the staging theme if user has the option
		if( $this->is_current_user_staging() ) {

			add_filter( 'template_directory', array( $this, 'change_template_directory' ), 10, 3 );
			add_filter( 'template', array( $this, 'change_template' ) );

			add_filter( 'stylesheet_directory', array( $this, 'change_template_directory' ), 10, 3 );
			add_filter( 'stylesheet', array( $this, 'change_template' ) );

			add_action( 'wp_footer' , array( $this, 'load_staging_button' ) );

		}

	}

    /**
     * Override the template directory to load the staging repository instead
     * of the live site repository.
     *
     * @param $template_dir
     * @param $template
     * @param $theme_root
     *
     * @return string $template_dir
     */
	public function change_template_directory( $template_dir, $template, $theme_root ) {

		$staging_name = str_replace( self::SUFIX, '', $template );

		$staging_theme = wp_get_theme( $staging_name );
		// Check if there is a staging parent theme.
		if ( $staging_theme->exists() ) {

			return $theme_root . '/' . $staging_name;

		}

		return $template_dir;

	}

    /**
     * Override the template name when in a staging environment.
     *
     * @param $template
     * @return mixed
     */
	public function change_template( $template ) {

		$staging_name = str_replace( self::SUFIX, '', $template );
		$theme = wp_get_theme( $staging_name );

		if( $theme->exists() ) {

			return $staging_name;

		}

		return $template;

	} // end change_template

    /**
     * Check if the current user has enabled the staging environment
     * on the current blog.
     *
     * @return boolean
     */
	public function is_current_user_staging() {

		return get_user_option( 'show_staging_env', get_current_user_id() );

	}// end is_current_user_staging

    /**
     * Switch the current users between the staging and the live
     * environment.
     */
	public function toggle_staging() {

		$user_id = get_current_user_id();
		$stage_option = get_user_option( 'show_staging_env', $user_id );

		// Update the option
		update_user_option( $user_id, 'show_staging_env', ! $stage_option );

	} // end is_current_user_staging

    /**
     * Display the staging user interface.
     *
     * Outputs the HTML , CSS and Javascript
     * needed for the staging button to function.
     */
	public function load_staging_button(){

		?>

		<div id="wpcom-staging">
			<div id="popup" class="hidden" ></div>
			<a class="button" href="#" >Staging</a>
		</div>

		<style type="text/css">

			#wpcom-staging .button {
				text-transform: uppercase;
				background: #DCA816;
				color: #fff;
				letter-spacing: 0.2em;
				text-shadow: none;
				font-size: 9px;
				font-weight: bold;
				padding: 8px 10px;
				float: left;
				cursor: pointer;
				margin-left: 10em;
				position: fixed;
				bottom: 14px;
			}



		</style>

		<?php
	} // end load_staging_button
}

new VIP_Staging();

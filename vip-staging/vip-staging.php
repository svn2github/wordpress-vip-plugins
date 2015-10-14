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

		// Load the AJAX endpoints
		add_action( "wp_ajax_vip_staging_deploy", array( $this, 'ajax_deploy_endpoint' ) );
		add_action( "wp_ajax_vip_staging_deploy_status", array( $this, 'ajax_deploy_status_endpoint' ) );
		add_action( "wp_ajax_vip_staging_deploy_info", array( $this, 'ajax_deploy_info_endpoint' ) );

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

	private function get_repositories_info() {
		$stylesheet = str_replace( 'vip/' , '', get_stylesheet() );
		$template = str_replace( 'vip/' , '', get_template() );

		// There is a child theme activated when the stylesheet name is different than the template name.
		$has_child = ( $stylesheet != $template );

		// Get the parent revisions
		$deployed_revision = wpcom_get_vip_deployed_revision( $template );
		$committed_revision = wpcom_get_vip_committed_revision( $template );

		$data = array(
			'template'      => $template,
			'stylesheet'    => $stylesheet,
			'has_child'     => $has_child,
			'parent_theme'  => false, // default to false. This can happen when it's a public theme.
		);

		// It should validate if the parent theme is a valid VIP theme. If so, overwrite with the correct revisions info
		if( false != $committed_revision ) {

			$data['parent_theme'] = array(
				'deployed_rev'  => $deployed_revision,
				'committed_rev' => $committed_revision,
			);

		}

		if( $has_child ) {

			// Fetch the correct data from the child theme
			$deployed_revision = wpcom_get_vip_deployed_revision( $stylesheet );
			$committed_revision = wpcom_get_vip_committed_revision( $stylesheet );

			$data['child_theme'] = array(
				'deployed_rev'  => $deployed_revision,
				'committed_rev' => $committed_revision,
			);
		}

		return $data;
	}

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

	public function user_can_stage() {
		return true; //todo
	}

	public function user_can_deploy() {
		return true; //todo
	}

	public function ajax_deploy_endpoint() {
		// todo: creating the job to start the deploy
		wp_send_json(  array( "success" => true ) );
		wp_die();
	}

	public function ajax_deploy_status_endpoint() {

		if( ! $this->user_can_deploy() ) {

			$this->ajax_die_no_permissions();

		}

		// Grab all the repositories data
		$data = $this->get_repositories_info();

		// todo: check the jobs system for the job status
		$data['status'] = ( $data['deployed_revision'] <= $data['committed_revision'] ? 'deployed' : 'undeployed' );

		wp_send_json_success( $data );

	}

	public function ajax_deploy_info_endpoint() {

		if( ! $this->user_can_deploy() ) {

			$this->ajax_die_no_permissions();

		}

		wp_send_json_success( $this->get_repositories_info() );

	}

	private function ajax_die_no_permissions() {
		wp_send_json_error( array(
			'message' => 'No permissions'
		) );
	}
}

new VIP_Staging();

<?php
/*
Plugin Name: VIP Staging
Description: Enables quick staging on your VIP website. No not enable it.
Version: 0.0.1
Author: Team 1
*/

class VIP_Staging {

	const SUFIX = '-live';

	public function __construct() {

		if( isset($_GET['staging']) ) {
			add_filter( 'template_directory', array( $this, 'change_template_directory' ), 10, 3 );
			add_filter( 'template', array( $this, 'change_template' ) );

			add_filter( 'stylesheet_directory', array( $this, 'change_template_directory' ), 10, 3 );
			add_filter( 'stylesheet', array( $this, 'change_template' ) );
		}

	}

	public function change_template_directory( $template_dir, $template, $theme_root ) {
		$staging_name = str_replace( self::SUFIX, '', $template );

		$staging_theme = wp_get_theme( $staging_name );
		// Check if there is a staging parent theme.
		if ( $staging_theme->exists() ) {
			return $theme_root . '/' . $staging_name;
		}

		return $template_dir;
	}

	public function change_template( $template ) {

		$staging_name = str_replace( self::SUFIX, '', $template );
		$theme = wp_get_theme( $staging_name );

		if( $theme->exists() ) {
			return $staging_name;
		}

		return $template;
	}

}

new VIP_Staging();

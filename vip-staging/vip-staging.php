<?php
/*
Plugin Name: VIP Staging
Description: Enables quick staging on your VIP website. No not enable it.
Version: 0.0.1
Author: Team 1
*/

function vip_staging_change_template( $template_dir, $template, $theme_root ) {
	$staging_theme = wp_get_theme( $template . "-staging" );
	// Check if there is a staging parent theme.
	if ( $staging_theme->exists() ) {
		return $theme_root . '/' . $template . "-staging";
	}

	return $template_dir;
}

add_filter( 'template_directory', 'vip_staging_change_template' , 10, 3);
add_filter( 'stylesheet_directory', 'vip_staging_change_template' , 10, 3);

function vip_staging_change_theme_name ( $theme_name ) {
	$theme = wp_get_theme( $theme_name . '-staging' );

	if( $theme->exists() ) {
		return $theme_name . "-staging";
	}

	return $theme_name;
}
add_filter( 'template', 'vip_staging_change_theme_name' );
add_filter( 'stylesheet', 'vip_staging_change_theme_name' );
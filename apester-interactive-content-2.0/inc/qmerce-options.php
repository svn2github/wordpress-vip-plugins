<?php
/*
 * Security check:
 * Exit if file accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QmerceOptions {

	public $name = 'apester';

	/**
	 * Constructor. Called when the plugin is initialized.
	 */
	function __construct() {
		// using 'admin_init' hook (instead of 'init') to only run this logic on the admin side of WordPress
		add_action( 'admin_init', array( &$this, 'init' ) );
	}

	function init() {
		// Check if the current user has post editing privilege
		if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) )
			return;

		$apester_options = get_option( 'qmerce-settings-admin' );

		$apester_options = $this->setPlaylistPosition($apester_options);
		$apester_options = $this->initBooleanProperty($apester_options, 'context', true);
		$apester_options = $this->initBooleanProperty($apester_options, 'fallback', true);

		update_option( 'qmerce-settings-admin', $apester_options );
	}

	function setPlaylistPosition($apester_options){
		$playlistPosition = $apester_options['playlist_position'];

		if ( ! isset($playlistPosition) ) {
			$playlistPosition = 'bottom';
			$apester_options['playlist_position'] = $playlistPosition;
		}

		return $apester_options;
	}

	/**
	 * Adds a boolean property to the plugins options if it is missing
	 * @param $apester_options - the value of apester plugin options
	 * @param $optionName - option name (to be set on the root option object)
	 * @param $initialValue - the initial value of the option
	 * @return mixed - $apester_options updated with the new subProperty
	 */
	function initBooleanProperty($apester_options, $optionName, $initialValue){
		$optionToCheck = $apester_options[$optionName];

		if ( ! isset($optionToCheck) ) {
			$optionToCheck = $initialValue;
			$apester_options[$optionName] = $optionToCheck;
		}

		return $apester_options;
	}

}

$apesterOptions = new QmerceOptions();

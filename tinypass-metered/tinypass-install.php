<?php

/**
 * Activate Tinypass plugin.  Will perform upgrades and check compatibility
 */
function tinypass_activate() {

	$error = '';

	if (version_compare(PHP_VERSION, '5.2.0') < 0) {
		$error .= "&nbsp;&nbsp;&nbsp;Requires PHP 5.2+";
	}

	if ($error)
		die('Tinypass could not be enabled<br>' . $error);

	tinypass_upgrades();

	$data = get_plugin_data(plugin_dir_path(__FILE__) . "/tinypass.php");
	$version = $data['Version'];
	update_option('tinypass_version', $version);
}

function tinypass_upgrades() { }

function tinypass_deactivate() { 
}

function tinypass_uninstall() {
	tinypass_include();
	$storage = new TPStorage();
	$storage->deleteAll();
}

?>
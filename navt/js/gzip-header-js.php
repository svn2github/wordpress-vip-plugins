<?php
	// check to see if the user has enabled gzip compression in the WordPress admin panel
	if ( extension_loaded('zlib') &&
	!ini_get('zlib.output_compression') &&
	ini_get('output_handler') != 'ob_gzhandler' &&
	((version_compare(phpversion(), '5.0', '>=') &&
	ob_get_length() == false) or ob_get_length() === false) ) {
		ob_start('ob_gzhandler');
	}

	// The headers below tell the browser to cache the file and also tell the browser it is JavaScript.
	header("Cache-Control: public");
	header("Pragma: cache");

	$offset = 5184000; // 60 * 60 * 24 * 60
	$ExpStr = "Expires: ".gmdate("D, d M Y H:i:s", time() + $offset)." GMT";
	$LmStr = "Last-Modified: ".gmdate("D, d M Y H:i:s", filemtime($_SERVER['SCRIPT_FILENAME']))." GMT";

	header($ExpStr);
	header($LmStr);
	header('Content-Type: text/javascript; charset: UTF-8');
?>
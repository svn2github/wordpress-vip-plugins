<?php

// WPCOM: Don't load the facebook lib within the context of WP_CLI to prevent fatals
// from incompatibilities with the autoloader WP_CLI uses.
if( defined('WP_CLI') && WP_CLI )
	return;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/FacebookWPHttpClient.php';

Facebook\FacebookRequest::setHttpClientHandler( new FacebookWPHttpClient() );

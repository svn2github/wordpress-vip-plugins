<?php

// Prevent fatals if the the OAuth lib is loaded already.
if ( ! class_exists( 'OAuthException' ) )
	require_once( __DIR__ . '/OAuth.php' );

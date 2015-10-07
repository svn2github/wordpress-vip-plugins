<?php
//Helper file for loading Babble's globals.php

require_once( dirname( dirname(__FILE__) ) . '/babble/globals.php' );

if ( did_action( 'plugins_loaded' ) ) {
	babble_globals();
}

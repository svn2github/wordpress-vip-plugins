<?php

/**
 * Define BOTR_CONTENT_MASK constant if it's not defined yet.
 * Addressed bug fixed in later version (v1.5.3) of the plugin.
 * See https://github.com/jwplayer/wordpress-plugin/pull/37 for more details.
 */
if ( false === defined( 'BOTR_CONTENT_MASK' ) ) {
	define( 'BOTR_CONTENT_MASK', 'content.jwplatform.com' );	
}

<?php
/**
 * LaterPay PHP client autoloader.
 *
 * @param string $class The name of the class to load
 */
function LaterPayClientAutoload( $class ) {
    // can't use __DIR__ as it's only available in PHP 5.3+
    $class = str_replace( '..', '', $class );
    if ( strpos( $class, '_' ) !== false ) {
        $class = str_replace( '_', DIRECTORY_SEPARATOR, $class );
    } else {
        $class = str_replace( '\\', DIRECTORY_SEPARATOR, $class );
    }
    $path = dirname( __FILE__ );
    $file = $path . DIRECTORY_SEPARATOR . $class . '.php';

    if ( file_exists( $file ) ) {
        require_once( $file );
    }
}

try {
    spl_autoload_register( 'LaterPayClientAutoload', true, true );
} catch ( Exception $e ) {
    unset( $e );
}

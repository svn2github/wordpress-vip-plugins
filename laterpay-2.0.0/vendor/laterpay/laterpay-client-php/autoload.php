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

if ( version_compare( PHP_VERSION, '5.1.2', '>=' ) ) {
    // SPL autoloading was introduced in PHP 5.1.2
    if ( version_compare( PHP_VERSION, '5.3.0', '>=' ) ) {
        spl_autoload_register( 'LaterPayClientAutoload', true, true );
    } else {
        spl_autoload_register( 'LaterPayClientAutoload' );
    }
} else {
    /**
     * Fall back to traditional autoload for old PHP versions.
     *
     * @param string $classname name of the class to load
     */
    function __autoload( $classname ) {
        LaterPayClientAutoload( $classname );
    }
}

<?php
/**
 * LaterPay plugin autoloader.
 */

class LaterPay_AutoLoader
{

    /**
     * Storage array for class search directories.
     * @var array
     */
    static private $paths = array();

    /**
     * Formatted array comprising namespaces and directories for them.
     * @var array
     */
    static private $namespaces = array();

    /**
     * Namespace registrator.
     *
     * @param string $dirName Name of directory where classes placed.
     * @param string $namespace namespace used for classes.
     *
     * @return void
     */
    public static function register_namespace( $dirName, $namespace ) {
        $namespace = self::get_class_relative_path( $namespace );
        LaterPay_AutoLoader::$namespaces[] = array(
                                                   'path' => $dirName,
                                                   'name' => $namespace,
                                                );
    }

    /**
     * Class directory getter. Get correct directory from class name.
     *
     * @param string $class class name.
     *
     * @return string prepared relative class directory.
     */
    protected static function get_class_relative_path( $class ) {
        $class = str_replace( '..', '', $class );
        if ( strpos( $class, '_' ) !== false ) {
            $class = str_replace( '_', DIRECTORY_SEPARATOR, $class );
        } else {
            $class = str_replace( '\\', DIRECTORY_SEPARATOR, $class );
        }

        return $class;
    }

    /**
     * Namespace class loader.
     *
     * @param string $class class name
     *
     * @return void
     */
    public static function load_class_from_namespace( $class ) {
        $class = self::get_class_relative_path( $class );

        foreach ( LaterPay_AutoLoader::$namespaces as $namespace ) {
            if ( strpos( $class, $namespace['name'] ) !== false ) {
                $relative_path = str_replace( $namespace['name'], '', $class );
                $relative_path = trim( $relative_path, DIRECTORY_SEPARATOR );
                $file = $namespace['path'] . DIRECTORY_SEPARATOR . $relative_path . '.php';
                if ( file_exists( $file ) ) {
                    require_once( $file );
                    break;
                }
            }
        }
    }

    /**
     * Store the filename (without extension) and full path of all '.php' files found.
     *
     * @param string $dirName Directory to search of classes
     *
     * @return void
     */
    public static function register_directory( $dirName ) {
        LaterPay_AutoLoader::$paths[] = $dirName;
    }

    /**
     * Class loader. Load class from registered directories if such class exists.
     *
     * @param string $class Class name
     *
     * @return void
     */
    public static function load_class( $class ) {
        $class = self::get_class_relative_path( $class );

        foreach ( LaterPay_AutoLoader::$paths as $path ) {
            $file = $path . DIRECTORY_SEPARATOR . $class . '.php';
            if ( file_exists( $file ) ) {
                require_once( $file );
                break;
            }
        }
    }

}

// registration of LaterPay autoloaders
spl_autoload_register( array( 'LaterPay_AutoLoader', 'load_class' ), false );
spl_autoload_register( array( 'LaterPay_AutoLoader', 'load_class_from_namespace' ), false );

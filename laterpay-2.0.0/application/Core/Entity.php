<?php

/**
 * LaterPay core entity.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Core_Entity
{

    /**
     * Object attributes
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Data changes flag (true after set_data|unset_data call)
     *
     * @var $_has_dataChange boolean
     */
    protected $_has_data_changes = false;

    /**
     * Original data loaded
     *
     * @var array
     */
    protected $_origData;

    /**
     * Name of object id field
     *
     * @var string
     */
    protected $_idFieldName = null;

    /**
     * Setter / getter underscore transformation cache
     *
     * @var array
     */
    protected static $_underscoreCache = array();

    /**
     * Object deleted flag
     *
     * @var boolean
     */
    protected $_is_deleted = false;

    /**
     * Map short fields names to their full names
     *
     * @var array
     */
    protected $_oldFieldsMap = array();

    /**
     * Map of fields to sync to other fields upon changing their data
     */
    protected $_syncFieldsMap = array();

    /**
     * Constructor
     *
     * By default is looking for first argument as array and assigns it as object attributes.
     * This behavior may change in child classes.
     *
     */
    public function __construct() {
        $this->_init_old_fields_map();
        if ( $this->_oldFieldsMap ) {
            $this->_prepare_sync_map_for_fields();
        }

        $args = func_get_args();
        if ( empty( $args[0] ) ) {
            $args[0] = array();
        }
        $this->_data = $args[0];
        $this->_add_full_names();

        $this->_construct();
    }

    /**
     *
     * @return void
     */
    protected function _add_full_names() {
        $existing_short_keys = array_intersect( $this->_syncFieldsMap, array_keys( $this->_data ) );

        if ( ! empty( $existing_short_keys ) ) {
            foreach ( $existing_short_keys as $key ) {
                $fullFieldName = array_search( $key, $this->_syncFieldsMap, true );
                $this->_data[ $fullFieldName ] = $this->_data[ $key ];
            }
        }
    }

    /**
     * Initiate mapping the array of object's previously used fields to new fields.
     * Must be overloaded by descendants to set actual fields map.
     *
     * @return void
     */
    protected function _init_old_fields_map() {
    }

    /**
     * Called after old fields are initiated. Forms synchronization map to sync old fields and new fields.
     *
     * @return LaterPay_Core_Entity
     */
    protected function _prepare_sync_map_for_fields() {
        $old2New = $this->_oldFieldsMap;
        $new2Old = array_flip( $this->_oldFieldsMap );
        $this->_syncFieldsMap = array_merge( $old2New, $new2Old );

        return $this;
    }

    /**
     * Internal constructor not dependent on parameters. Can be used for object initialization.
     *
     * @return  void
     */
    protected function _construct() { }

    /**
     * Set _is_deleted flag value (if $is_deleted parameter is defined) and return current flag value.
     *
     * @param boolean $is_deleted
     *
     * @return boolean
     */
    public function is_deleted( $is_deleted = null ) {
        $result = $this->_is_deleted;
        if ( ! is_null( $is_deleted ) ) {
            $this->_is_deleted = $is_deleted;
        }

        return $result;
    }

    /**
     * Get data change status.
     *
     * @return boolean
     */
    public function has_data_changes() {
        return $this->_has_data_changes;
    }

    /**
     * Set name of object id field.
     *
     * @param string $name
     *
     * @return LaterPay_Core_Entity
     */
    public function set_id_field_name( $name ) {
        $this->_idFieldName = $name;

        return $this;
    }

    /**
     * Get name of object id field.
     *
     * @return string
     */
    public function get_id_field_name() {
        return $this->_idFieldName;
    }

    /**
     * Get object id.
     *
     * @return mixed
     */
    public function get_id() {
        if ( $this->get_id_field_name() ) {
            return $this->_get_data( $this->get_id_field_name() );
        }

        return $this->_get_data( 'id' );
    }

    /**
     * Set object id field value.
     *
     * @param mixed $value
     *
     * @return LaterPay_Core_Entity
     */
    public function set_id( $value ) {
        if ( $this->get_id_field_name() ) {
            $this->set_data( $this->get_id_field_name(), $value );
        } else {
            $this->set_data( 'id', $value );
        }

        return $this;
    }

    /**
     * Add data to the object.
     *
     * Retains previous data in the object.
     *
     * @param array $arr
     *
     * @return LaterPay_Core_Entity
     */
    public function add_data( array $arr ) {
        foreach ( $arr as $index => $value ) {
            $this->set_data( $index, $value );
        }

        return $this;
    }

    /**
     * Overwrite data in the object.
     *
     * $key can be string or array.
     * If $key is string, the attribute value will be overwritten by $value
     *
     * If $key is an array, it will overwrite all the data in the object.
     *
     * @param string|array  $key
     * @param mixed         $value
     *
     * @return LaterPay_Core_Entity
     */
    public function set_data( $key, $value = null ) {
        $this->_has_data_changes = true;

        if ( is_array( $key ) ) {
            $this->_data = $key;
            $this->_add_full_names();
        } else {
            $this->_data[ $key ] = $value;
            if ( isset( $this->_syncFieldsMap[ $key ] ) ) {
                $fullFieldName = $this->_syncFieldsMap[ $key ];
                $this->_data[ $fullFieldName ] = $value;
            }
        }

        return $this;
    }

    /**
     * Unset data from the object.
     *
     * $key can be a string only. Array will be ignored.
     *
     * @param null|string $key
     *
     * @return LaterPay_Core_Entity
     */
    public function unset_data( $key = null ) {
        $this->_has_data_changes = true;

        if ( is_null( $key ) ) {
            $this->_data = array();
        } else {
            unset( $this->_data[ $key ] );
            if ( isset( $this->_syncFieldsMap[ $key ] ) ) {
                $fullFieldName = $this->_syncFieldsMap[ $key ];
                unset( $this->_data[ $fullFieldName ] );
            }
        }

        return $this;
    }

    /**
     * Unset old field data from the object.
     *
     * $key can be a string only. Array will be ignored.
     *
     * @param null|string $key
     *
     * @return LaterPay_Core_Entity
     */
    public function unset_old_data( $key = null ) {
        if ( is_null( $key ) ) {
            $keys = array_keys( $this->_syncFieldsMap );
            foreach ( $keys as $key ) {
                unset( $this->_data[ $key ] );
            }
        } else {
            unset( $this->_data[ $key ] );
        }

        return $this;
    }

    /**
     * Retrieve data from the object.
     *
     * If $key is empty, will return all the data as an array.
     * Otherwise it will return the value of the attribute specified by $key.
     *
     * If $index is specified, it will assume that attribute data is an array
     * and retrieve the corresponding member.
     *
     * @param string $key
     * @param null|string|int $index
     *
     * @return array
     */
    public function get_data( $key = '', $index = null ) {
        if ( empty( $key ) ) {
            return $this->_data;
        }

        $default = null;

        // @phpcs:ignore accept a/b/c as ['a']['b']['c']
        if ( strpos( $key, '/' ) ) {
            $keyArr = explode( '/', $key );
            $data = $this->_data;
            foreach ( $keyArr as $k ) {
                if ( empty( $k ) ) {
                    return $default;
                }
                if ( is_array( $data ) ) {
                    if ( ! isset( $data[ $k ] ) ) {
                        return $default;
                    }
                    $data = $data[ $k ];
                } elseif ( $data instanceof Varien_Object ) {
                    $data = $data->get_data( $k );
                } else {
                    return $default;
                }
            }

            return $data;
        }

        // legacy functionality for $index
        if ( isset( $this->_data[ $key ] ) ) {
            if ( is_null( $index ) ) {
                return $this->_data[ $key ];
            }

            $value = $this->_data[ $key ];
            if ( is_array( $value ) ) {
                // use any existing data, even if it's empty
                if ( isset( $value[ $index ] ) ) {
                    return $value[ $index ];
                }

                return null;
            } elseif ( is_string( $value ) ) {
                $arr = explode( "\n", $value );
                if ( isset( $arr[ $index ] ) && ( ! empty( $arr[ $index ] ) || strlen( $arr[ $index ] ) > 0 ) ) {
                    $aux = $arr[ $index ];
                } else {
                    $aux = null;
                }

                return $aux;
            } elseif ( $value instanceof Varien_Object ) {
                return $value->get_data( $index );
            }

            return $default;
        }

        return $default;
    }

    /**
     * Get value from _data array without parse key.
     *
     * @param   string $key
     *
     * @return  mixed
     */
    protected function _get_data( $key ) {
        if ( isset( $this->_data[ $key ] ) ) {
            $aux = $this->_data[ $key ];
        } else {
            $aux = null;
        }

        return $aux;
    }

    /**
     * Set object data by calling setter method.
     *
     * @param string $key
     * @param mixed  $args
     *
     * @return LaterPay_Core_Entity
     */
    public function set_data_using_method( $key, $args = array() ) {
        $method = 'set' . $this->_camelize( $key );
        $this->$method( $args );

        return $this;
    }

    /**
     * Get object data by key by calling getter method.
     *
     * @param string $key
     * @param mixed  $args
     *
     * @return mixed
     */
    public function get_data_using_method( $key, $args = null ) {
        $method = 'get' . $this->_camelize( $key );

        return $this->$method( $args );
    }

    /**
     * Get data or set default value, if value is not available.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get_data_set_default( $key, $default ) {
        if ( ! isset( $this->_data[ $key ] ) ) {
            $this->_data[ $key ] = $default;
        }

        return $this->_data[ $key ];
    }

    /**
     * Check, if there's any data in the object, if $key is empty.
     * Otherwise check, if the specified attribute is set.
     *
     * @param string $key
     *
     * @return boolean
     */
    public function has_data( $key = '' ) {
        if ( empty( $key ) || ! is_string( $key ) ) {
            return ! empty( $this->_data );
        }

        return array_key_exists( $key, $this->_data );
    }

    /**
     * Convert object attributes to array.
     *
     * @param array $arrAttributes array of required attributes
     *
     * @return array
     */
    public function to_array( array $arrAttributes = array() ) {
        if ( empty( $arrAttributes ) ) {
            return $this->_data;
        }

        $arrRes = array();
        foreach ( $arrAttributes as $attribute ) {
            if ( isset( $this->_data[ $attribute ] ) ) {
                $arrRes[ $attribute ] = $this->_data[ $attribute ];
            } else {
                $arrRes[ $attribute ] = null;
            }
        }

        return $arrRes;
    }

    /**
     * Set required array elements.
     *
     * @param array $arr
     * @param array $elements
     *
     * @return array
     */
    protected function _prepare_array( &$arr, array $elements = array() ) {
        foreach ( $elements as $element ) {
            if ( ! isset( $arr[ $element ] ) ) {
                $arr[ $element ] = null;
            }
        }

        return $arr;
    }

    /**
     * Convert object attributes to XML.
     *
     * @param array  $arrAttributes array of required attributes
     * @param string $rootName      name of the root element
     * @param boolean $addOpenTag
     * @param boolean $addCdata
     *
     * @return string
     */
    public function to_xml( array $arrAttributes = array(), $rootName = 'item', $addOpenTag = false, $addCdata = true ) {
        $xml = '';

        if ( $addOpenTag ) {
            $xml .= '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        }

        if ( ! empty( $rootName ) ) {
            $xml .= '<' . $rootName . '>' . "\n";
        }

        $xmlModel = new Varien_Simplexml_Element( '<node></node>' );

        $arrData = $this->to_array( $arrAttributes );
        foreach ( $arrData as $fieldName => $fieldValue ) {
            if ( $addCdata === true ) {
                $fieldValue = "<! [CDATA[$fieldValue]]>";
            } else {
                $fieldValue = $xmlModel->xmlentities( $fieldValue );
            }

            $xml .= "<$fieldName>$fieldValue</$fieldName>" . "\n";
        }

        if ( ! empty( $rootName ) ) {
            $xml .= '</' . $rootName . '>' . "\n";
        }

        return $xml;
    }

    /**
     * Convert object attributes to JSON.
     *
     * @param array $arrAttributes array of required attributes
     *
     * @return string
     */
    public function to_json( array $arrAttributes = array() ) {
        $arrData    = $this->to_array( $arrAttributes );
        $json       = wp_json_encode( $arrData );

        return $json;
    }

    /**
     * Public wrapper for __to_string.
     *
     * Uses $format as an template and substitute {{key}} for attributes
     *
     * @param string $format
     *
     * @return string
     */
    public function to_string( $format = '' ) {
        if ( empty( $format ) ) {
            $str = implode( ', ', $this->get_data() );
        } else {
            preg_match_all( '/\{\{([a-z0-9_]+)\}\}/is', $format, $matches );
            foreach ( $matches[1] as $var ) {
                $format = str_replace( '{{' . $var . '}}', $this->get_data( $var ), $format );
            }
            $str = $format;
        }

        return $str;
    }

    /**
     * Get / set attribute wrapper.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call( $method, $args ) {
        switch ( substr( $method, 0, 3 ) ) {
            case 'get' :
                $key = $this->_underscore( substr( $method, 3 ) );
                if ( isset( $args[0] ) ) {
                    $aux = $args[0];
                } else {
                    $aux = null;
                }
                $data = $this->get_data( $key, $aux );
                return $data;

            case 'set' :
                $key = $this->_underscore( substr( $method, 3 ) );
                if ( isset( $args[0] ) ) {
                    $aux = $args[0];
                } else {
                    $aux = null;
                }
                $data = $this->set_data( $key, $aux );
                return $data;

            case 'uns' :
                $key = $this->_underscore( substr( $method, 3 ) );
                $result = $this->unset_data( $key );
                return $result;

            case 'has' :
                $key = $this->_underscore( substr( $method, 3 ) );
                return isset( $this->_data[ $key ] );
        }
        $call_argument = '(' . isset($args[0]) ? (string) $args[0] : '' . ')';
        throw new Varien_Exception( 'Invalid method ' . get_class( $this ) . '::' . $method . $call_argument ); // @phpcs:ignore
    }

    /**
     * Attribute getter (deprecated).
     *
     * @param string $var
     *
     * @return mixed
     */
    public function __get( $var ) {
        $var = $this->_underscore( $var );

        return $this->get_data( $var );
    }

    /**
     * Attribute setter (deprecated).
     *
     * @param string $var
     *
     * @param mixed $value
     */
    public function __set( $var, $value ) {
        $var = $this->_underscore( $var );

        $this->set_data( $var, $value );
    }

    /**
     * Check, if the object is empty.
     *
     * @return boolean
     */
    public function is_empty() {
        if ( empty( $this->_data ) ) {
            return true;
        }

        return false;
    }

    /**
     * Convert field names for setters and getters.
     *
     * $this->setMyField($value) === $this->set_data('my_field', $value)
     * Uses cache to eliminate unnecessary preg_replace
     *
     * @param string $name
     *
     * @return string
     */
    protected function _underscore( $name ) {
        if ( isset( self::$_underscoreCache[ $name ] ) ) {
            return self::$_underscoreCache[ $name ];
        }

        $result = strtolower( preg_replace( '/(.)([A-Z])/', '$1_$2', $name ) );

        self::$_underscoreCache[ $name ] = $result;

        return $result;
    }

    /**
     * Convert a string to camelCase.
     *
     * @param string $name a string to convert to camelCase
     *
     * @return string the string in camelCase
     */
    protected function _camelize( $name ) {
        return ucwords( $name, '' );
    }

    /**
     * Serialize object attributes.
     *
     * @param array  $attributes
     * @param string $valueSeparator
     * @param string $fieldSeparator
     * @param string $quote
     *
     * @return string $serialized_object_attributes
     */
    public function serialize( $attributes = array(), $valueSeparator = '=', $fieldSeparator = ' ', $quote = '"' ) {
        $serialized_object_attributes   = '';
        $data                           = array();

        if ( empty( $attributes ) ) {
            $attributes = array_keys( $this->_data );
        }

        foreach ( $this->_data as $key => $value ) {
            if ( in_array( $key, $attributes, true ) ) {
                $data[] = $key . $valueSeparator . $quote . $value . $quote;
            }
        }

        // convert array to string
        $serialized_object_attributes = implode( $fieldSeparator, $data );

        return $serialized_object_attributes;
    }

    /**
     * Get object's loaded data (original data).
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get_original_data( $key = null ) {
        if ( is_null( $key ) ) {
            return $this->_origData;
        }

        if ( isset( $this->_origData[ $key ] ) ) {
            $aux = $this->_origData[ $key ];
        } else {
            $aux = null;
        }

        return $aux;
    }

    /**
     * Initialize object's original data.
     *
     * @param string $key
     * @param mixed  $data
     *
     * @return LaterPay_Core_Entity
     */
    public function set_original_data( $key = null, $data = null ) {
        if ( is_null( $key ) ) {
            $this->_origData = $this->_data;
        } else {
            $this->_origData[ $key ] = $data;
        }

        return $this;
    }

    /**
     * Compare object data with original data.
     *
     * @param string $field
     *
     * @return boolean
     */
    public function data_has_changed_for( $field ) {
        $newData = $this->get_data( $field );
        $origData = $this->get_original_data( $field );

        return ( $newData !== $origData );
    }

    /**
     * Clear data changes status.
     *
     * @param boolean $value
     *
     * @return LaterPay_Core_Entity
     */
    public function set_data_changes( $value ) {
        $this->_has_data_changes = (bool) $value;

        return $this;
    }

    /**
     * Implementation of ArrayAccess::offsetSet().
     *
     * @link http://www.php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param string  $offset
     * @param mixed   $value
     */
    public function offset_set( $offset, $value ) {
        $this->_data[ $offset ] = $value;
    }

    /**
     * Implementation of ArrayAccess::offsetExists().
     *
     * @link http://www.php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param string $offset
     *
     * @return boolean
     */
    public function offset_exists( $offset ) {
        return isset( $this->_data[ $offset ] );
    }

    /**
     * Implementation of ArrayAccess::offsetUnset().
     *
     * @link http://www.php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param string $offset
     */
    public function offset_unset( $offset ) {
        unset( $this->_data[ $offset ] );
    }

    /**
     * Implementation of ArrayAccess::offsetGet().
     *
     * @link http://www.php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param string $offset
     *
     * @return mixed
     */
    public function offset_get( $offset ) {
        if ( isset( $this->_data[ $offset ] ) ) {
            $aux = $this->_data[ $offset ];
        } else {
            $aux = null;
        }

        return $aux;
    }

    /**
     * @param string $field
     *
     * @return boolean
     */
    public function is_dirty( $field = null ) {
        if ( empty( $this->_dirty ) ) {
            return false;
        }
        if ( is_null( $field ) ) {
            return true;
        }

        return isset( $this->_dirty[ $field ] );
    }

    /**
     * Flag a field as dirty.
     *
     * @param string    $field
     * @param boolean   $flag
     *
     * @return LaterPay_Core_Entity
     */
    public function flag_dirty( $field, $flag = true ) {
        if ( is_null( $field ) ) {
            $fields = array_keys( $this->get_data() );
            foreach ( $fields as $field ) {
                $this->flag_dirty( $field, $flag );
            }
        } else {
            if ( $flag ) {
                $this->_dirty[ $field ] = true;
            } else {
                unset( $this->_dirty[ $field ] );
            }
        }

        return $this;
    }
}

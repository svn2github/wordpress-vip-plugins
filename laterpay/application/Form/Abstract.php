<?php

/**
 * LaterPay abstract form class.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
abstract class LaterPay_Form_Abstract
{

    /**
     * Form fields
     *
     * @var array
     */
    protected $fields;

    /**
     * Validation errors
     *
     * @var array
     */
    protected $errors = array();

    /**
     * Array of no strict names
     *
     * @var array
     */
    protected $nostrict;

    /**
     * Default filters set
     *
     * @var array
     */
    public static $filters = array(
        // sanitize string value
        'text'       => 'sanitize_text_field',
        // sanitize email
        'email'      => 'sanitize_email',
        // sanitize xml
        'xml'        => 'ent2ncr',
        // sanitize url
        'url'        => 'esc_url',
        // sanitize js
        'js'         => 'esc_js',
        // sanitize sql
        'sql'        => 'esc_sql',
        // convert to int, abs
        'to_int'     => 'absint',
        // convert to string
        'to_string'  => 'strval',
        // delocalize
        'delocalize' => array( 'LaterPay_Helper_View', 'normalize' ),
        // convert to float
        'to_float'   => 'floatval',
        // replace part of value with other
        // params:
        // type    - replace type (str_replace, preg_replace)
        // search  - searched value or pattern
        // replace - replacement
        'replace'    => array( 'LaterPay_Form_Abstract', 'replace' ),
        // format number with given decimal places
        'format_num' => 'number_format',
        // strip slashes
        'unslash'    => 'wp_unslash',
    );

    /**
     * Constructor.
     *
     * @param array $data
     *
     * @return void
     */
    public final function __construct( $data = array() ) {
        // Call init method from child class
        $this->init();

        // set data to form, if specified
        if ( ! empty( $data ) ) {
            $this->set_data( $data );
        }
    }

    /**
     * Init form
     *
     * @return void
     */
    abstract protected function init();

    /**
     * Set new field, options for its validation, and filter options (sanitizer).
     *
     * @param       $name
     * @param array $options
     *
     * @return bool field was created or already exists
     */
    public function set_field( $name, $options = array() ) {
        $fields = $this->get_fields();

        // check, if field already exists
        if ( isset( $fields[ $name ] ) ) {
            return false;
        } else {
            // field name
            $data                = array();
            // validators
            $data['validators']  = isset( $options['validators'] )      ? $options['validators']    : array();
            // filters ( sanitize )
            $data['filters']     = isset( $options['filters'] )         ? $options['filters']       : array();
            // default value
            $data['value']       = isset( $options['default_value'] )   ? $options['default_value'] : null;
            // do not apply filters to null value
            $data['can_be_null'] = isset( $options['can_be_null'] )     ? $options['can_be_null']   : false;

            // name not strict, value searched in data by part of the name (for dynamic params)
            if ( isset( $options['not_strict_name'] ) && $options['not_strict_name'] ) {
                $this->set_nostrict( $name );
            }

            $this->save_field_data( $name, $data );
        }

        return true;
    }

    /**
     * Save data in field.
     *
     * @param $name
     * @param $data
     *
     * @return void
     */
    protected function save_field_data( $name, $data ) {
        $this->fields[ $name ] = $data;
    }

    /**
     * Get all fields.
     *
     * @return array
     */
    public function get_fields() {
        return $this->fields;
    }

    /**
     * Get all filters.
     *
     * @return array
     */
    protected function get_filters() {
        return self::$filters;
    }

    /**
     * Get field value.
     *
     * @param $field_name
     *
     * @return mixed
     */
    public function get_field_value( $field_name ) {
        $fields = $this->get_fields();

        if ( isset( $fields[ $field_name ] ) ) {
            return $fields[ $field_name ]['value'];
        }

        return null;
    }

    /**
     * Set field value.
     *
     * @param $field_name
     * @param $value
     *
     * @return void
     */
    protected function set_field_value( $field_name, $value ) {
        $this->fields[ $field_name ]['value'] = $value;
    }

    /**
     * Add field name to nostrict array.
     *
     * @param $name
     *
     * @return void
     */
    protected function set_nostrict( $name ) {
        if ( ! isset( $this->nostrict ) ) {
            $this->nostrict = array();
        }

        array_push( $this->nostrict, $name );
    }

    /**
     * Check if field value is null and can be null
     *
     * @param $field
     * @return bool
     */
    protected function check_if_field_can_be_null( $field ) {
        $fields = $this->get_fields();

        if ( $fields[ $field ]['can_be_null'] ) {
            return true;
        }

        return false;
    }

    /**
     * Add condition to the field validation
     *
     * @param $field
     * @param array $condition
     * @return void
     */
    public function add_validation( $field, $condition = array() ) {
        $fields = $this->get_fields();

        if ( isset( $fields[ $field ] ) ) {
            if ( is_array( $condition ) && ! empty( $condition ) ) {
                // condition should be correct
                array_push( $fields[ $field ]['validators'], $condition );
            }
        }
    }

    /**
     * Validate data in fields
     *
     * @param $data
     *
     * @return bool is data valid
     */
    public function is_valid( $data = array() ) {
        $this->errors = array();
        // If data passed set data to the form
        if ( ! empty( $data ) ) {
            $this->set_data( $data );
        }

        $fields = $this->get_fields();

        // validation logic
        if ( is_array( $fields ) ) {
            foreach ( $fields as $name => $field ) {
                $validators = $field['validators'];
                foreach ( $validators as $validator_key => $validator_value ) {
                    $validator_option = is_int( $validator_key ) ? $validator_value : $validator_key;
                    $validator_params = is_int( $validator_key ) ? null : $validator_value;

                    // continue loop if field can be null and has null value
                    if ( $this->check_if_field_can_be_null( $name ) && $this->get_field_value( $name ) === null ) {
                        continue;
                    }

                    $is_valid = $this->validate_value( $field['value'], $validator_option, $validator_params );
                    if ( ! $is_valid ) {
                        // data not valid
                        $this->errors[] = array('name' => $name, 'value' => $field['value'], 'validator' => $validator_option, 'options' => $validator_params);
                    }
                }
            }
        }

        return empty( $this->errors );
    }

    public function get_errors() {
        $aux = $this->errors;
        $this->errors = array();
        return $aux;
    }

    /**
     * Apply filters to form data.
     *
     * @return void
     */
    protected function sanitize() {
        $fields = $this->get_fields();

        // get all form filters
        if ( is_array( $fields ) ) {
            foreach ( $fields as $name => $field ) {
                $filters = $field['filters'];
                foreach ( $filters as $filter_key => $filter_value ) {
                    $filter_option = is_int( $filter_key ) ? $filter_value : $filter_key;
                    $filter_params = is_int( $filter_key ) ? null : $filter_value;

                    // continue loop if field can be null and has null value
                    if ( $this->check_if_field_can_be_null( $name ) && $this->get_field_value( $name ) === null ) {
                        continue;
                    }

                    $this->set_field_value( $name, $this->sanitize_value( $this->get_field_value( $name ), $filter_option, $filter_params ) );
                }
            }
        }
    }

    /**
     * Apply filter to the value.
     *
     * @param $value
     * @param $filter
     * @param null $filter_params
     *
     * @return mixed
     */
    public function sanitize_value( $value, $filter, $filter_params = null ) {
        // get filters
        $filters = $this->get_filters();

        // sanitize value according to selected filter
        $sanitizer = isset( $filters[ $filter ] ) ? $filters[ $filter ] : '';

        if ( $sanitizer && is_callable( $sanitizer ) ) {
            if ( $filter_params ) {
                if ( is_array( $filter_params ) ) {
                    array_unshift( $filter_params, $value );
                    $value = call_user_func_array( $sanitizer, $filter_params );
                } else {
                    $value = call_user_func( $sanitizer, $value, $filter_params );
                }
            } else {
                $value = call_user_func( $sanitizer, $value );
            }
        }

        return $value;
    }

    /**
     * Call str_replace with array of options.
     *
     * @param $value
     * @param $options
     *
     * @return mixed
     */
    public static function replace( $value, $options ) {
        if ( is_array( $options ) && isset( $options['type'] ) && is_callable( $options['type'] ) ) {
            $value = $options['type']( $options['search'], $options['replace'], $value );
        }

        return $value;
    }

    /**
     * Validate value by selected validator and its value optionally.
     *
     * @param $value
     * @param $validator
     * @param null $validator_param
     *
     * @return bool
     */
    public function validate_value( $value, $validator, $validator_params = null ) {
        $is_valid = false;

        switch ( $validator ) {
            // compare value with set
            case 'cmp':
                if ( $validator_params && is_array( $validator_params ) ) {
                    // OR realization, all validators inside validators set used like AND
                    // if at least one set correct then validation passed
                    foreach ( $validator_params as $validators_set ) {
                        foreach ( $validators_set as $operator => $param ) {
                            $is_valid = $this->compare_values( $operator, $value, $param );
                            // if comparison not valid break the loop and go to the next validation set
                            if ( ! $is_valid ) {
                                break;
                            }
                        }

                        // if comparison valid after full validation set check then do not need to check others
                        if ( $is_valid ) {
                            break;
                        }
                    }
                }
                break;

            // check, if value is an int
            case 'is_int':
                $is_valid = is_int( $value );
                break;

            // check, if value is a string
            case 'is_string':
                $is_valid = is_string( $value );
                break;

            // check, if value is a float
            case 'is_float':
                $is_valid = is_float( $value );
                break;

            // check string length
            case 'strlen':
                if ( $validator_params && is_array( $validator_params ) ) {
                    foreach ( $validator_params as $extra_validator => $validator_data ) {
                        // recursively call extra validator
                        $is_valid = $this->validate_value( strlen( $value ), $extra_validator, $validator_data );
                        // break loop if something not valid
                        if ( ! $is_valid ) {
                            break;
                        }
                    }
                }
                break;

            // check array values
            case 'array_check':
                if ( $validator_params && is_array( $validator_params ) ) {
                    foreach ( $validator_params as $extra_validator => $validator_data ) {
                        if ( is_array( $value ) ) {
                            foreach ( $value as $v ) {
                                // recursively call extra validator
                                $is_valid = $this->validate_value( $v, $extra_validator, $validator_data );
                                if ( ! $is_valid ) {
                                    break;
                                }
                            }
                        } else {
                            $is_valid = false;
                        }

                        if ( ! $is_valid ) {
                            break;
                        }
                    }
                }
                break;

            // check, if value is in array
            case 'in_array':
                if ( $validator_params && is_array( $validator_params ) ) {
                    $is_valid = in_array( $value, $validator_params, true );
                }
                break;

            // check if value is an array
            case 'is_array':
                $is_valid = is_array( $value );
                break;

            case 'match':
                if ( $validator_params && ! is_array( $validator_params ) ) {
                    $is_valid = preg_match( $validator_params, $value );
                }
                break;

            case 'match_url':
                $is_valid = preg_match_all( '/[-a-zA-Z0-9@:%_\+.~#?&\/\/=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~#?&\/\/=]*)?/i', $value );
                break;

            case 'depends':
                if ( $validator_params && is_array( $validator_params ) ) {
                    //get all dependency
                    foreach ( $validator_params as $dependency ) {
                        // if dependency match
                        if ( ! isset( $dependency['value'] ) || $value === $dependency['value'] || ( is_array( $dependency['value'] ) && in_array( $value, $dependency['value'], true ) ) ) {
                            // loop for dependencies conditions and check if all of them is valid
                            foreach ( $dependency['conditions'] as $vkey => $vparams ) {
                                $extra_validator = is_int( $vkey ) ? $vparams : $vkey;
                                $validator_data = is_int( $vkey ) ? null : $vparams;
                                // recursively call extra validator
                                $is_valid = $this->validate_value( $this->get_field_value( $dependency['field'] ), $extra_validator, $validator_data );
                                // break loop if something not valid
                                if ( ! $is_valid ) {
                                    break;
                                }
                            }

                            // dependency matched, break process
                            break;
                        } else {
                            $is_valid = true;
                        }
                    }
                }
                break;
            case 'verify_nonce':
                if ( $validator_params ) {
                    if ( is_array( $validator_params ) ) {
                        if ( isset( $validator_params['action'] ) ) {
                            wp_verify_nonce( $value, $validator_params['action'] );
                        }
                    } else {
                        wp_verify_nonce( $value );
                    }
                }
                break;
            case 'post_exist':
                $post = get_post( $value );
                $is_valid = $post !== null;
                break;
            default:
                // incorrect validator specified, do nothing
                break;
        }

        return $is_valid;
    }

    /**
     * Compare two values
     *
     * @param $comparison_operator
     * @param $first_value
     * @param $second_value
     *
     * @return bool
     */
    protected function compare_values( $comparison_operator, $first_value, $second_value ) {
        $result = false;

        switch ( $comparison_operator ) {
            // equal ===
            case 'eq':
                $result = ( $first_value === $second_value );
                break;

            // not equal !==
            case 'ne':
                $result = ( $first_value !== $second_value );
                break;

            // greater than >
            case 'gt':
                $result = ( $first_value > $second_value );
                break;

            // greater than or equal >=
            case 'gte':
                $result = ( $first_value >= $second_value );
                break;

            // less than <
            case 'lt':
                $result = ( $first_value < $second_value );
                break;

            // less than or equal <=
            case 'lte':
                $result = ( $first_value <= $second_value );
                break;

            // search if string present in value
            case 'like':
                $result = ( strpos( $first_value, $second_value ) !== false );
                break;

            default:
                // incorrect comparison operator, do nothing
                break;
        }

        return $result;
    }

    /**
     * Set data into fields and sanitize it.
     *
     * @param $data
     *
     * @return $this
     */
    public function set_data( $data ) {
        $fields = $this->get_fields();

        // set data and sanitize it
        if ( is_array( $data ) ) {
            foreach ( $data as $name => $value ) {
                // set only, if name field was created
                if ( isset( $fields[ $name ] ) ) {
                    $this->set_field_value( $name, $value );
                    continue;
                } elseif ( isset( $this->nostrict ) && is_array( $this->nostrict ) ) {
                    // if field name is not strict
                    foreach ( $this->nostrict as $field_name ) {
                        if ( strpos( $name, $field_name ) !== false ) {
                            $this->set_field_value( $field_name, $value );
                            break;
                        }
                    }
                }
            }

            // sanitize data, if filters were specified
            $this->sanitize();
        }

        return $this;
    }

    /**
     * Get form values.
     *
     * @param bool   $not_null get only not null values
     * @param string $prefix   get values with selected prefix
     * @param array  $exclude  array of names for exclude
     *
     * @return array
     */
    public function get_form_values( $not_null = false, $prefix = null, $exclude = array() ) {
        $fields = $this->get_fields();
        $data   = array();

        foreach ( $fields as $name => $field_data ) {
            if ( $not_null && ( $field_data['value'] === null ) ) {
                continue;
            }
            if ( $prefix && ( strpos( $name, $prefix ) === false ) ) {
                continue;
            }
            if ( is_array( $exclude ) && in_array( $name, $exclude, true ) ) {
                continue;
            }
            $data[ $name ] = $field_data['value'];
        }

        return $data;
    }
}


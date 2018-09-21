<?php

class LaterPay_Client_Signing
{

	/**
	 * Contains the hash algorithm.
	 * @var string
	 */
	protected static $hashAlgo = 'sha224';

	/**
     * Todo: add description
     *
	 * @param string   $known_str
	 * @param string   $given_str
     *
	 * @throws InvalidArgumentException
     *
	 * @return boolean
	 */
    protected static function time_independent_hmac_compare( $known_str, $given_str ) {
        if ( strlen( $known_str ) === 0 ) {
            throw new InvalidArgumentException( 'This function cannot safely compare against an empty given string.' );
        }

        $res = strlen( $given_str ) ^ strlen( $known_str );
        $given_len = strlen( $given_str );
        $known_len = strlen( $known_str );

        for ( $i = 0; $i < $given_len; ++$i ) {
            $res |= ord( $known_str[$i % $known_len] ) ^ ord( $given_str[$i] );
        }

        return $res === 0;
    }

	/**
     * TODO: add description
     *
	 * @param string   $secret
	 * @param string   $parts
     *
	 * @return string
	 */
    protected static function create_hmac( $secret, $parts ) {
        if ( is_array( $parts ) ) {
            $data = join( '', $parts );
        } else {
            $data = (string) $parts;
        }

        // limit at length 32 for sha224 as it was the same in previously used library.
        $raw_hash = substr( hash_hmac( self::$hashAlgo, $data, $secret, true ), 0, 32 );
        // hexadecimal representation of the given string.
        $hash = bin2hex( $raw_hash );

        return $hash;
    }

	/**
	 * TODO: add description
     *
	 * @param string   $signature
	 * @param string   $secret
	 * @param array    $params
	 * @param string   $url
	 * @param string   $method
     *
	 * @return boolean
	 */
	public static function verify( $signature, $secret, $params, $url, $method ) {
        if ( is_array( $signature ) ) {
            $signature = $signature[0];
        }

        $mac = self::sign( $secret, $params, $url, $method );

        return self::time_independent_hmac_compare( $signature, $mac );
    }

    /**
     * Request parameter dictionaries are handled in different ways in different libraries,
     * this function is required to ensure we always have something of the format
     * { key: [ value1, value2, ... ] }.
     *
     * @param array $params
     *
     * @return array
     */
    protected static function normalise_param_structure( $params ) {
        $out = array();

        // this is tricky - either we have (a, b), (a, c) or we have (a, (b, c))
        foreach ( $params as $param_name => $param_value ) {
            if ( is_array( $param_value ) ) {
                // this is (a, (b, c)). WPCS: comment ok
                $out[$param_name] = $param_value;
            } else {
                // this is (a, b), (a, c). WPCS: comment ok
                if ( ! in_array( $param_name, $out, true ) ) {
                    $out[$param_name] = array();
                }
                $out[$param_name][] = $param_value;
            }
        }

        return $out;
    }

    /**
     * Create base message.
     *
     * @param array     $params mapping of all parameters that should be signed
     * @param string    $url    full URL of the target endpoint, no URL parameters
     * @param string    $method
     *
     * @return string
     */
    protected static function create_base_message( $params, $url, $method = LaterPay_Core_Request::POST ) {
        $msg = '{method}&{url}&{params}';
        $method = strtoupper( $method );

        $data   = array();
        $url    = rawurlencode( utf8_encode( $url ) );
        $params = self::normalise_param_structure( $params );

        $keys = array_keys( $params );
        sort( $keys, SORT_STRING );
        foreach ( $keys as $key ) {
            $value  = $params[$key];
            $key    = rawurlencode( utf8_encode( $key ) );

            if ( ! is_array( $value ) ) {
                $value = array( $value );
            }

            $encoded_value = '';
            sort( $value, SORT_STRING );
            foreach ( $value as $v ) {
                if ( mb_detect_encoding( $v, 'UTF-8' ) !== 'UTF-8' ) {
                    $encoded_value = rawurlencode( utf8_encode( $v ) );
                } else {
                    $encoded_value = rawurlencode( $v );
                }
                $data[] = $key . '=' . $encoded_value;
            }
        }

        $param_str = rawurlencode( join( '&', $data ) );
        $result = str_replace( array( '{method}', '{url}', '{params}' ), array( $method, $url, $param_str ), $msg );

        return $result;
    }

    /**
     * Create signature for given 'params', 'url', and HTTP method.
     *
     * How params are canonicalized:
     * - 'urllib.quote' every key and value that will be signed
     * - sort the params list
     * - '&'-join the params
     *
     * @param string    $secret secret used to create signature
     * @param array     $params mapping of all parameters that should be signed
     * @param string    $url    full URL of the target endpoint, no URL parameters
     * @param string    $method
     *
     * @return string
     */
    protected static function sign( $secret, $params, $url, $method = LaterPay_Core_Request::POST ) {
        $secret = utf8_encode( $secret );

        if ( isset( $params['hmac'] ) ) {
            unset( $params['hmac'] );
        }

        if ( isset( $params['gettoken'] ) ) {
            unset( $params['gettoken'] );
        }

        $aux = explode( '?', $url );
        $url = $aux[0];
        $msg = self::create_base_message( $params, $url, $method );
        $mac = self::create_hmac( $secret, $msg );

        return $mac;
    }

    /**
     * Sign and encode a URL 'url' with a 'secret' key called via a HTTP 'method'.
     * It adds the signature to the URL as the URL parameter "hmac" and also adds the required timestamp parameter 'ts'
     * if it's not already in the 'params' dictionary. 'unicode()' instances in params are handled correctly.
     *
     * @param string    $secret
     * @param array     $params
     * @param string    $url
     * @param string    $method HTTP method
     *
     * @return string query params
     */
    public static function sign_and_encode( $secret, $params, $url, $method = LaterPay_Core_Request::GET ) {
        if ( ! isset( $params['ts'] ) ) {
            $params['ts'] = (string) time();
        }

        if ( isset( $params['hmac'] ) ) {
            unset( $params['hmac'] );
        }

        // get the keys in alphabetical order
        $keys = array_keys( $params );
        sort( $keys, SORT_STRING );
        $query_pairs = array();
        foreach ( $keys as $key ) {
            $aux = $params[$key];
            $key = utf8_encode( $key );

            if ( ! is_array( $aux ) ) {
                $aux = array( $aux );
            }
            sort( $aux, SORT_STRING );
            foreach ( $aux as $value ) {
                if ( mb_detect_encoding( $value, 'UTF-8' ) !== 'UTF-8' ) {
                    $value = rawurlencode( utf8_encode( $value ) );
                }
                $query_pairs[] = rawurlencode( $key ) . '=' . rawurlencode( $value );
            }
        }

        // build the querystring
        $encoded = join( '&', $query_pairs );

        // hash the querystring data
        $hmac = self::sign( $secret, $params, $url, $method );

        return $encoded . '&hmac=' . $hmac;
    }

    /**
     * Sign JWT token
     *
     * @param $payload
     * @param $key
     *
     * @return string
     */
    public static function sign_jwt( $payload, $key ) {

        $header  = array(
            'typ' => 'JWT',
            'alg' => 'HS256',
        );

        $data   = array();
        $data[] = self::encode_jwt_data( $header );
        $data[] = self::encode_jwt_data( $payload );
        $data[] = self::base64url_encode( hash_hmac( 'sha256', join( '.', $data ), $key, true ) );

        return join( '.', $data );
    }

    /**
     * Encode JWT data
     *
     * @param $data
     *
     * @return string
     */
    protected static function encode_jwt_data( $data ) {
        return self::base64url_encode( LaterPay_Wrapper::laterpay_json_encode( $data ) );
    }

    /**
     * Urlsafe base64encode
     *
     * @param $data
     *
     * @return string
     */
    protected static function base64url_encode( $data ) {
        return str_replace( '=', '', strtr( base64_encode( $data ), '+/', '-_' ) );
    }

}

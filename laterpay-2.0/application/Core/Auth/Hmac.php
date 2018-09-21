<?php

/**
 * Auth_Hmac provides tokenizer using OpenSSL extension.
 *
 * Plugin Name: LaterPay
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-plugin
 * Author URI: https://laterpay.net/
 */
class LaterPay_Core_Auth_Hmac
{

    /**
     * @const int time of live for token in seconds
     */
    const VALID_PERIOD = 86400; // 24 hrs

    /**
     * @var LaterPay_Core_Auth_Hmac instance of class
     */
    protected static $_instance = null;

    /**
     * @var string secret key for token generation
     */

    protected static $privateKey = null;
    /**
     * @var string used algorithm for creation of hash
     */

    protected static $hashAlgo = 'sha224';
    /**
     * @var bool is base64 encoding for token needed or not
     */
    protected static $useBase64 = false;

    /**
     * constructor for class Token
     *
     * @throws Exception
     *
     * @param string $privateKey File name of private pem key
     * @param boolean $isPacked key must be packed
     */
    public function __construct( $privateKey = null, $isPacked = false ) {
        if ( ( $privateKey === null ) && ( self::$privateKey === null ) ) {

            if ( defined( 'LATERPAY_HMAC_KEY_SECRET' ) ) {
                self::$privateKey = LATERPAY_HMAC_KEY_SECRET;
            } else {
                if ( defined( 'LATERPAY_HMAC_KEY_BYTECODE' ) ) {
                    self::$privateKey = pack( 'H*', LATERPAY_HMAC_KEY_BYTECODE );
                } else {
                    throw new Exception( 'no secret key for token generator' );
                }
            }
        } else {
            if ( $isPacked ) {
                self::$privateKey = pack( 'H*', $privateKey );
            } else {
                self::$privateKey = $privateKey;
            }
        }
    }

    /**
     * sign data
     *
     * @param string|array $data Date for generate sign, all data will glue with '|' string
     *
     * @return string|boolean
     */
    public function sign( $data ) {
        if ( is_array( $data ) ) {
            $data = implode( '', $data );
        }

        // limit at length 32 for sha224 as it was the same in previously used library.
        $raw_hash = substr( hash_hmac( self::$hashAlgo, $data, self::$privateKey, true ), 0, 32 );
        // hexadecimal representation of the given string.
        $hash = bin2hex( $raw_hash );

        return $hash;
    }

    /**
     * Verify data and sign.
     *
     * @param string|array  $data data to be verified
     * @param string        $sign Sign string
     *
     * @return number|boolean
     */
    public function verify( $data, $sign ) {
        $signV = $this->sign( $data );

        return ( ! empty( $sign ) ) && ( $sign === $signV );
    }

    /**
     * generate token based on phone and time
     *
     * @param string  $data
     * @param string  $ts   Unix timestamp of token
     *
     * @return string
     */
    public function get_token( $data, $ts ) {
        $fresult = $this->sign( array( $data, $ts ) );
        if ( self::$useBase64 ) {
            $fresult = base64_encode( $fresult );
            $fresult = strtr( $fresult, '+/', '-_' );
            $fresult = rawurlencode( $fresult );
        }

        return $fresult;
    }

    /**
     * Validate token
     *
     * @param string  $data  data
     * @param string  $ts    Time in seconds
     * @param string  $token Token string
     *
     * @return boolean
     */
    public function validate_token( $data, $ts, $token ) {
        $now = time();

        $fresult = false;
        if ( ( $now - $ts ) < self::VALID_PERIOD ) {
            if ( self::$useBase64 ) {
                $temp = urldecode( $token );
                $temp = strtr( $temp, '-_', '+/' );
                $temp = base64_decode( $temp );
            } else {
                $temp = $token;
            }
            $fresult = ( $this->verify( array( $data ), $temp ) );
        }

        return $fresult;
    }

    /**
     * Retrieve instance
     *
     * @throws Exception
     *
     * @return LaterPay_Core_Auth_Hmac
     */
    public static function get_instance() {
        if ( null === self::$_instance ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
}

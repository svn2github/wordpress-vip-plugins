<?php

class LaterPay_Crypt
{

    /**
     * mcrypt resource for encryption
     *
     * The mcrypt resource can be recreated every time something needs to be created or it can be created just once.
     * Since mcrypt operates in continuous mode, by default, it'll need to be recreated when in non-continuous mode.
     *
     * @var String
     * @access private
     */
    var $enmcrypt;

    /**
     * mcrypt resource for decryption
     *
     * The mcrypt resource can be recreated every time something needs to be created or it can be created just once.
     * Since mcrypt operates in continuous mode, by default, it'll need to be recreated when in non-continuous mode.
     *
     * @var String
     * @access private
     */
    var $demcrypt;

    /**
     * mcrypt resource for CFB mode
     *
     * @var String
     * @access private
     */
    var $ecb;

    /**
     * Is the mode one that is paddable?
     *
     * @var Boolean
     * @access private
     */
    var $paddable = false;

    /**
     * The Encryption Mode
     *
     * @var Integer
     * @access private
     */
    var $mode;

    /**
     * Does the key schedule need to be (re)calculated?
     *
     * @var Boolean
     * @access private
     */
    var $changed = true;

    /**
     * Has the key length explicitly been set or should it be derived from the key, itself?
     *
     * @var Boolean
     * @access private
     */
    var $explicit_key_length = false;

    /**
     * The Key Length divided by 32
     *
     * @var Integer
     * @access private
     * @internal The max value is 256 / 32 = 8, the min value is 128 / 32 = 4
     */
    var $Nk = 4;

    /**
     * The Key Length
     *
     * @var Integer
     * @access private
     * @internal The max value is 256 / 8 = 32, the min value is 128 / 8 = 16.  Exists in conjunction with $key_size
     *    because the encryption / decryption / key schedule creation requires this number and not $key_size.  We could
     *    derive this from $key_size or vice versa, but that'd mean we'd have to do multiple shift operations, so in lieu
     *    of that, we'll just precompute it once.
     */
    var $key_size = 16;

    /**
     * The Initialization Vector
     *
     * @var String
     * @access private
     */
    var $iv = '';

    /**
     * The Key
     *
     * @var String
     * @access private
     */
    var $key = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";

    /**
     * Padding status
     *
     * @var Boolean
     * @access private
     */
    var $padding = true;

    /**
     * The Block Length
     *
     * @var Integer
     * @access private
     * @internal The max value is 32, the min value is 16.  All valid values are multiples of 4.  Exists in conjunction with
     *     $Nb because we need this value and not $Nb to pad strings appropriately.
     */
    var $block_size = 16;

    /**
     * Continuous Buffer status
     *
     * @var Boolean
     * @access private
     */
    var $continuousBuffer = false;

    /**
     * current encryption decryption method.
     *
     * @var string $secure_method contains method by which,
     * string has to be encrypted/decrypted.
     * @access protected
     */
    protected $secure_method;

    public function __construct()
    {
        // Sets an encryption/decryption method for use
        if ( function_exists( 'openssl_encrypt' ) ) {
           $this->secure_method = 'openssl';
        } else {
            $this->secure_method = 'mcrypt';
            $this->paddable      = true;
            $this->mode          = MCRYPT_MODE_CBC;
        }
    }

    public function encrypt( $uri = '', $key = '' ) {

        if ( 'openssl' === $this->secure_method ) {
            $ivlen = openssl_cipher_iv_length( $cipher = "AES-128-CBC" );
            $iv    = openssl_random_pseudo_bytes( $ivlen );

            $ciphertext_raw = openssl_encrypt( $uri, $cipher, $key, OPENSSL_RAW_DATA, $iv );
            $hmac           = hash_hmac( 'sha256', $ciphertext_raw, $key, true );
            $uri            = base64_encode( $iv . $hmac . $ciphertext_raw );
        } else {

            $this->key = $key;
            $this->_mcryptSetup();
            $this->changed = true;

            if ($this->paddable) {
                $uri = $this->_pad( $uri );
            }

            $ciphertext = mcrypt_generic( $this->enmcrypt, $uri );

            if (! $this->continuousBuffer) {
                mcrypt_generic_init( $this->enmcrypt, $this->key, $this->iv );
            }
            $file       = base64_encode( $ciphertext );
            $uri       = strtr( $file, '+/', '-_' );
        }
        return $uri;
    }

    function decrypt( $uri = '', $key = '' ) {

        if ( 'openssl' === $this->secure_method ) {
            $c     = base64_decode( $uri );
            $ivlen = openssl_cipher_iv_length( $cipher = "AES-128-CBC" );
            $iv    = substr( $c, 0, $ivlen );
            $hmac  = substr( $c, $ivlen, $sha2len = 32 );

            $ciphertext_raw     = substr( $c, $ivlen + $sha2len );
            $original_plaintext = openssl_decrypt( $ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv) ;
            $calcmac            = hash_hmac( 'sha256', $ciphertext_raw, $key, true );

            if ( hash_equals( $hmac, $calcmac ) ) { //PHP 5.6+ timing attack safe comparison
                $uri = $original_plaintext;
            }
        } else {

            $uri    = strtr( $uri, '-_', '+/' );
            $uri    = base64_decode( $uri );

            $this->key = $key;
            $this->_mcryptSetup();
            if ( $this->paddable ) {
                // we pad with chr(0) since that's what mcrypt_generic does.  to quote from http://php.net/function.mcrypt-generic :
                // The data is padded with \0 to make sure the length of the data is n * blocksize.
                $uri = str_pad( $uri, ( strlen( $uri ) + 15 ) & 0xFFFFFFF0, chr( 0 ) );
            }

            $plaintext = mdecrypt_generic( $this->demcrypt, $uri );

            if ( ! $this->continuousBuffer ) {
                mcrypt_generic_init( $this->demcrypt, $this->key, $this->iv );
            }

            if ( $this->paddable ) {
                $aux = $this->_unpad( $plaintext );
            } else {
                $aux = $plaintext;
            }

            $uri = $aux;
        }
        return $uri;
    }

    /**
     * Setup mcrypt
     *
     * Validates all the variables.
     *
     * @access private
     */
    function _mcryptSetup()
    {
        if ( ! $this->changed ) {
            return;
        }

        if ( ! $this->explicit_key_length ) {
            $length = strlen( $this->key ) >> 2;
            if ( $length > 8 ) {
                $length = 8;
            } else if ( $length < 4 ) {
                $length = 4;
            }
            $this->Nk = $length;
            $this->key_size = $length << 2;
        }

        switch ( $this->Nk ) {
            case 4: // 128
                $this->key_size = 16;
                break;
            case 5: // 160
            case 6: // 192
                $this->key_size = 24;
                break;
            case 7: // 224
            case 8: // 256
                $this->key_size = 32;
        }

        $this->key = str_pad( substr( $this->key, 0, $this->key_size ), $this->key_size, chr( 0 ) );
        $this->encryptIV = $this->decryptIV = $this->iv = str_pad( substr( $this->iv, 0, 16 ), 16, chr( 0 ) );

        if ( ! isset( $this->enmcrypt ) ) {
            $mode = $this->mode;

            $this->demcrypt = mcrypt_module_open( MCRYPT_RIJNDAEL_128, '', $mode, '' );
            $this->enmcrypt = mcrypt_module_open( MCRYPT_RIJNDAEL_128, '', $mode, '' );
        }

        mcrypt_generic_init($this->demcrypt, $this->key, $this->iv);
        mcrypt_generic_init($this->enmcrypt, $this->key, $this->iv);

        $this->changed = false;
    }

    /**
     * Pads a string
     *
     * Pads a string using the RSA PKCS padding standards so that its length is a multiple of the blocksize.
     * $block_size - (strlen($text) % $block_size) bytes are added, each of which is equal to
     * chr($block_size - (strlen($text) % $block_size)
     *
     * If padding is disabled and $text is not a multiple of the blocksize, the string will be padded regardless
     * and padding will, hence forth, be enabled.
     *
     * @access private
     */
    function _pad($text)
    {
        $length = strlen($text);

        if ( ! $this->padding ) {
            if ( $length % $this->block_size === 0 ) {
                return $text;
            } else {
                user_error( "The plaintext's length ($length) is not a multiple of the block size ({$this->block_size})" );
                $this->padding = true;
            }
        }

        $pad = $this->block_size - ( $length % $this->block_size );

        return str_pad( $text, $length + $pad, chr( $pad ) );
    }

    /**
     * Unpads a string.
     *
     * If padding is enabled and the reported padding length is invalid the encryption key will be assumed to be wrong
     * and false will be returned.
     *
     * @access private
     */
    function _unpad( $text )
    {
        if ( ! $this->padding ) {
            return $text;
        }

        $length = ord( $text[strlen( $text ) - 1] );

        if ( ! $length || $length > $this->block_size ) {
            return false;
        }

        return substr( $text, 0, -$length );
    }
}

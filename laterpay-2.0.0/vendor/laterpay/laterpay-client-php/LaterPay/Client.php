<?php

class LaterPay_Client
{
    /**
     * API key
     *
     * @var string
     */
    protected $api_key;

    /**
     * Backend API root
     *
     * @var string
     */
    protected $api_root;

    /**
     * Dialog API root
     *
     * @var string
     */
    protected $web_root;

    /**
     * Merchant Id
     *
     * @var string
     */
    protected $cp_key;

    /**
     * Lptoken value
     *
     * @var null|string
     */
    protected $lptoken = null;

    /**
     * Lptoken toke name
     *
     * @var string
     */
    protected $token_name = 'laterpay_token';

    /**
     * LaterPay_Client constructor.
     *
     * @param $cp_key
     * @param $api_key
     * @param $api_root
     * @param $web_root
     * @param null $token_name
     */
    public function __construct( $cp_key, $api_key, $api_root, $web_root, $token_name = null )
    {
        $this->cp_key   = $cp_key;
        $this->api_key  = $api_key;
        $this->api_root = $api_root;
        $this->web_root = $web_root;

        if ( ! empty( $token_name ) ) {
            $this->token_name = $token_name;
        }
        if ( isset( $_COOKIE[$this->token_name] ) ) {
            $this->lptoken = $_COOKIE[$this->token_name];
        }
    }

    /**
     * Get lptoken
     *
     * @return null|string
     */
    public function get_laterpay_token()
    {
        return $this->lptoken;
    }

    /**
     * Get API key.
     *
     * @return string|null
     */
    public function get_api_key()
    {
        return $this->api_key;
    }

    /**
     * Get access URL.
     *
     * @return string
     */
    private function _get_access_url()
    {
        return $this->api_root . '/access';
    }

    /**
     * Get token URL.
     *
     * @return string
     */
    private function _get_token_url()
    {
        return $this->api_root . '/gettoken';
    }

    /**
     * Get health URL.
     *
     * @return string
     */
    private function _get_health_url()
    {
        return $this->api_root . '/validatesignature';
    }

    /**
     * Get token redirect URL.
     *
     * @param string $return_to URL
     *
     * @return string $url
     */
    private function _get_token_redirect_url( $return_to )
    {
        $url    = $this->_get_token_url();
        $params = $this->sign_and_encode(
                        array(
                            'redir' => $return_to,
                            'cp'    => $this->cp_key,
                        ),
                        $url,
                        LaterPay_Http_Client::GET
                    );
        $url   .= '?' . $params;

        return $url;
    }

    /**
     * Get identify URL.
     *
     * @param string $back_url
     *
     * @return string
     */
    public function get_identify_url( $back_url, $content_ids )
    {
        $url = $this->web_root . '/ident';

        $payload = array(
            'back' => $back_url,
            'ids'  => $content_ids
        );

        $jwt_token = LaterPay_Client_Signing::sign_jwt( $payload, $this->api_key );

        return $url . '/' . $this->cp_key . '/' . $jwt_token;
    }

    /**
     * Get controls balance URL.
     *
     * @param string|null $forcelang
     *
     * @return string $url
     */
    public function get_controls_balance_url( $forcelang = null )
    {
        $data = array( 'cp' => $this->cp_key );

        if ( ! empty( $forcelang ) ) {
            $data['forcelang'] = $forcelang;
        }

        $data['xdmprefix'] = substr( uniqid( '', true ), 0, 10 );
        $base_url   = $this->web_root . '/controls/balance';
        $params     = $this->sign_and_encode( $data, $base_url );
        $url        = $base_url . '?' . $params;

        return $url;
    }

    /**
     * Get account links URL.
     *
     * @param string|null $show          Possible options: ('g', 'gg', 'l', 's', 'ss') or combination of them
     * @param string|null $css_url
     * @param string|null $next_url
     * @param string|null $forcelang
     * @param bool        $use_jsevents
     *
     * @return string URL
     */
    public function get_account_links( $show = null, $css_url = null, $next_url = null, $forcelang = null, $use_jsevents = false )
    {
        $data = array(
            'next' => $next_url,
            'cp'   => $this->cp_key
        );

        if ( ! empty( $forcelang ) ) {
            $data['forcelang'] = $forcelang;
        }

        if ( ! empty( $css_url ) ) {
            $data['css'] = $css_url;
        }

        if ( ! empty( $show ) ) {
            $data['show'] = $show;
        }

        if ( $use_jsevents ) {
            $data['jsevents'] = '1';
        }

        $data['xdmprefix'] = substr( uniqid( '', true ), 0, 10 );

        $url    = $this->web_root . '/controls/links';
        $params = $this->sign_and_encode( $data, $url );

        return join( '?', array( $url, $params ) );
    }

    /**
     * Get dialog API url
     *
     * @param string $url
     *
     * @return string
     */
    protected function get_dialog_api_url( $url )
    {
        return $this->web_root . '/dialog-api?url=' . rawurlencode( $url );
    }

    /**
     * Get URL for the LaterPay login form.
     *
     * @param string   $next_url
     * @param boolean  $use_jsevents
     *
     * @return string $url
     */
    public function get_login_dialog_url( $next_url, $use_jsevents = false )
    {
        if ( $use_jsevents ) {
            $aux = '&jsevents=1';
        } else {
            $aux = '';
        }
        $url = $this->web_root . '/account/dialog/login?next=' . rawurlencode( $next_url ) . $aux . '&cp=' . $this->cp_key;

        return $this->get_dialog_api_url( $url );
    }

    /**
     * Get URL for the LaterPay signup form.
     *
     * @param string   $next_url
     * @param boolean  $use_jsevents
     *
     * @return string $url
     */
    public function get_signup_dialog_url( $next_url, $use_jsevents = false )
    {
        if ( $use_jsevents ) {
            $aux = '&jsevents=1';
        } else {
            $aux = '';
        }
        $url = $this->web_root . '/account/dialog/signup?next=' . rawurlencode( $next_url ) . $aux . '&cp=' . $this->cp_key;

        return $this->get_dialog_api_url( $url );
    }

    /**
     * Get URL for logging out a user from LaterPay.
     *
     * @param string   $next_url
     * @param boolean  $use_jsevents
     *
     * @return string $url
     */
    public function get_logout_dialog_url( $next_url, $use_jsevents = false )
    {
        if ( $use_jsevents ) {
            $aux = '&jsevents=1';
        } else {
            $aux = '';
        }
        $url = $this->web_root . '/account/dialog/logout?next=' . rawurlencode( $next_url ) . $aux . '&cp=' . $this->cp_key;

        return $this->get_dialog_api_url( $url );
    }

    /**
     * Build puchase url
     *
     * @param array  $data
     * @param string $endpoint
     * @param array  $options
     *
     * @return string $url
     */
    protected function get_web_url( array $data, $endpoint, $options = array() )
    {
        $default_options = array(
            'dialog'   => true,
            'jsevents' => false,
        );

        // merge with defaults
        $options = array_merge( $default_options, $options );

        // add merchant id if not specified
        if ( ! isset( $data['cp'] ) ) {
            $data['cp'] = $this->cp_key;
        }

        // force to return lptoken
        $data['return_lptoken'] = 1;

        // jsevent for dialog if specified
        if ( $options['jsevents'] ) {
            $data['jsevents'] = 1;
        }

        // is dialog url
        if ( $options['dialog'] ) {
            $prefix = $this->web_root . '/dialog';
        } else {
            $prefix = $this->web_root;
        }

        // build puchase url
        $base_url = join( '/', array( $prefix, $endpoint ) );
        $params   = $this->sign_and_encode( $data, $base_url, LaterPay_Http_Client::GET );
        $url      = $base_url . '?' . $params;

        return $url;
    }

    /**
     * Get purchase url for Pay Now revenue
     *
     * @param array $data
     * @param array $options
     *
     * @return string $url
     */
    public function get_buy_url( $data, $options = array() )
    {
        return $this->get_web_url( $data, 'buy', $options );
    }

    /**
     * Get purchase url for Pay Later revenue
     *
     * @param array $data
     * @param array $options
     *
     * @return string $url
     */
    public function get_add_url( $data, $options = array() )
    {
        return $this->get_web_url( $data, 'add', $options );
    }

    /**
     * Get purchase url for subscriptions
     *
     * @param $data
     * @param array $options
     *
     * @return string
     */
    public function get_subscription_url( $data, $options = array() )
    {
        return $this->get_web_url( $data, 'subscribe', $options );
    }

    /**
     * Sign and encode all request parameters.
     *
     * @param array     $params array params
     * @param string    $url
     * @param string    $method HTTP method
     *
     * @return string query params
     */
    public function sign_and_encode( $params, $url, $method = LaterPay_Http_Client::GET )
    {
        return LaterPay_Client_Signing::sign_and_encode( $this->api_key, $params, $url, $method );
    }

    /**
     * Check if user has access to a given item / given array of items.
     *
     * @param array         $article_ids    array with posts ids
     * @param null|string   $product_key    array with posts ids
     *
     * @return array response
     */
    public function get_access( $article_ids, $product_key = null )
    {
        if ( ! is_array( $article_ids ) ) {
            $article_ids = array( $article_ids );
        }

        if ( ! $this->lptoken || empty( $article_ids ) ) {
            return array();
        }

        $params = array(
            'lptoken'    => $this->lptoken,
            'cp'         => $this->cp_key,
            'article_id' => $article_ids,
        );

        if ( ! empty( $product_key ) ) {
            $params['product'] = $product_key;
        }

        return $this->make_request( $this->_get_access_url(), $params );
    }

    /**
     * Update token.
     *
     * @return void
     */
    public function acquire_token()
    {
        header( "Location: " . $this->_get_token_redirect_url( self::get_current_url() ), true );
        exit;
    }

    /**
     * Set cookie with token.
     *
     * @param string $token token key
     *
     * @return void
     */
    public function set_token( $token, $redirect = false )
    {
        $this->lptoken = $token;
        try {
            setcookie( $this->token_name, $token, strtotime( '+1 day' ), '/' );
	        if ( $this->is_vip_go() ) {
		        setcookie( 'vip-go-cb', '1', strtotime( '+1 day' ), '/' );
	        }
        } catch ( Exception $e ) {
            unset( $e );
        }
        if ( $redirect ) {
            header( 'Location: ' . self::get_current_url(), true );
            exit();
        }
    }

    /**
     * Delete the token from cookies.
     *
     * @return void
     */
    public function delete_token()
    {
        try {
            setcookie( $this->token_name, '', time() - 100000, '/' );
	        if ( $this->is_vip_go() ) {
	            setcookie( 'vip-go-cb', '0', time() - 100000, '/' );
            }
        } catch ( Exception $e ) {
            unset( $e );
        }
        unset( $_COOKIE[$this->token_name] );
        $this->token = null;
    }

    /**
     * Send request to $url.
     *
     * @param string    $url    URL to send request to
     * @param array     $params
     * @param string    $method
     *
     * @return array $response
     */
    protected function make_request( $url, $params = array(), $method = LaterPay_Http_Client::GET )
    {
        // build the request
        $params = $this->sign_and_encode( $params, $url, $method );
        $headers = array(
            'X-LP-APIVersion' => 2,
            'User-Agent'      => LaterPay_Wrapper::get_user_agent(),
        );
        try {
            $raw_response_body = LaterPay_Http_Client::request($url, $headers, $params, $method);
            $response = (array) json_decode( $raw_response_body, true );
            if ( empty( $response ) ) {
                throw new Exception('connection_error');
            }
            if ( isset($response['status']) && 'invalid_token' === $response['status'] ) {
                $this->delete_token();
            }
            if ( array_key_exists( 'new_token', $response ) ) {
                $this->set_token( $response['new_token'] );
            }
        } catch ( Exception $e ) {
            unset( $e );
            $response = array( 'status' => 'connection_error' );
        }

        return $response;
    }

    /**
     * Check if the current request is an Ajax request.
     *
     * @return bool
     */
    public static function is_ajax()
    {
        return ! empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && 'xmlhttprequest' === strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ); // WPCS: sanitization ok, input var ok.
    }

    /**
     * Get current URL.
     *
     * @return string $url
     */
    public static function get_current_url()
    {
        $ssl = isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS']; // WPCS: input var ok.
        // Check for Cloudflare Universal SSL / flexible SSL
        if ( isset( $_SERVER['HTTP_CF_VISITOR'] ) && strpos( $_SERVER['HTTP_CF_VISITOR'], 'https' ) !== false ) { // WPCS: sanitization ok, input var ok.
            $ssl = true;
        }

        if ( ! empty( $_SERVER['REQUEST_URI'] ) ) { // WPCS: input var ok.
            $uri = $_SERVER['REQUEST_URI']; // WPCS: sanitization ok, input var ok.
        }

        // process Ajax requests
        if ( self::is_ajax() ) {
            if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) { // WPCS: input var ok.
                $url = $_SERVER['HTTP_REFERER']; // WPCS: sanitization ok, input var ok.
            }
            $parts  = LaterPay_Wrapper::laterpay_parse_url( $url );

            if ( ! empty( $parts ) ) {
                $uri = $parts['path'];
                if ( ! empty( $parts['query'] ) ) {
                    $uri .= '?' . $parts['query'];
                }
            }
        }

        $uri = preg_replace( '/lptoken=.*?($|&)/', '', $uri );

        $uri = preg_replace( '/ts=.*?($|&)/', '', $uri );
        $uri = preg_replace( '/hmac=.*?($|&)/', '', $uri );

        $uri = preg_replace( '/&$/', '', $uri );
        $uri = preg_replace( '/\?$/', '', $uri );

        if ( $ssl ) {
            $pageURL = 'https://';
        } else {
            $pageURL = 'http://';
        }

        if ( ! empty( $_SERVER['SERVER_PORT'] ) ) { //WPCS: input var ok.
            $serverPort = $_SERVER['SERVER_PORT']; // WPCS: sanitization ok, input var ok.
        }

        if ( ! empty( $_SERVER['SERVER_NAME'] ) ) { // WPCS: input var ok.
            $serverName = $_SERVER['SERVER_NAME']; // WPCS: sanitization ok, input var ok.
        }

        if ( $serverName === 'localhost' and function_exists('site_url')) {
            $serverName = (str_replace(array('http://', 'https://'), '', site_url())) ; // WP function 
            // overwrite port on Heroku 
            if ( isset( $_SERVER['HTTP_CF_VISITOR'] ) && strpos( $_SERVER['HTTP_CF_VISITOR'], 'https' ) !== false ) { // WPCS: sanitization ok, input var ok.
                $serverPort = 443;
            } else {
                $serverPort = 80;
            }
        }
        if ( ! $ssl && 80 !== intval( $serverPort ) ) {
            $pageURL .= $serverName . ':' . $serverPort . $uri;
        } else if ( $ssl && 443 !== intval( $serverPort ) ) {
            $pageURL .= $serverName . ':' . $serverPort . $uri;
        } else {
            $pageURL .= $serverName . $uri;
        }

        return $pageURL;
    }

    /**
     * Method to check url API availability.
     *
     * @return boolean
     */
    public function check_health()
    {
        $headers = array(
            'X-LP-APIVersion' => 2,
            'User-Agent'      => LaterPay_Wrapper::get_user_agent(),
        );
        $method = LaterPay_Http_Client::GET;
        $url    = $this->_get_health_url();
        $params = $this->sign_and_encode(
            array(
                'salt'  => md5( microtime(true) ),
                'cp'    => $this->cp_key,
            ),
            $url,
            $method
        );
        $url .= '?' . $params;
        try {
            LaterPay_Http_Client::request( $url, $headers, array(), $method );
        } catch ( Exception $e ) {
            unset( $e );
            return false;
        }

        return true;
    }

	/**
	 * Checks if site is on WordPress VIP go.
	 */
	public function is_vip_go() {
		if ( function_exists( 'laterpay_is_vip_go' ) ) {
			return laterpay_is_vip_go();
		}
		return false;
    }
}

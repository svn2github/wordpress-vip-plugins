<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Roost_API {

    public function __construct() {
        //blank
    }

    public static function roost_remote_request( $remote_data ) {
        $auth_creds = '';
        if ( ! empty( $remote_data['appkey'] ) ) {
            $auth_creds = 'Basic ' . base64_encode( $remote_data['appkey'] .':'.$remote_data['appsecret'] );
        }
        $remote_url = 'https://go.goroost.com/api/' . $remote_data['remoteAction'];

        $headers = array(
            'Authorization'  => $auth_creds,
            'Accept'       => 'application/json',
            'Content-Type'   => 'application/json',
            'Content-Length' => strlen( $remote_data['remoteContent'] ),
        );

        $remote_payload = array(
            'method'    => $remote_data['method'],
            'headers'   => $headers,
            'body'      => $remote_data['remoteContent'],
        );
        $response = wp_remote_request( esc_url_raw( $remote_url ), $remote_payload );
        return $response;
    }

    public static function decode_data( $remote_data ) {
        $xfer = self::roost_remote_request( $remote_data );
        $nxfer = wp_remote_retrieve_body( $xfer );
        $lxfer = json_decode( $nxfer, true );
        return $lxfer;
    }

    public static function api_check() {
        $remote_data = array(
            'method' => 'GET',
            'remoteAction' => 'app',
            'appkey' => '',
            'appsecret' => '',
            'remoteContent' => '',
        );
        $response = self::roost_remote_request( $remote_data );
        return $response;
    }

    public static function login( $roost_user, $roost_pass, $roost_token ){
        $remote_content = array(
            'username' => $roost_user,
            'password' => $roost_pass,
            'roost_token' => $roost_token,
        );
        $remote_data = array(
            'method' => 'POST',
            'remoteAction' => 'accounts/details',
            'appkey' => $roost_user,
            'appsecret' => $roost_pass,
            'roost_token' => $roost_token,
            'remoteContent' => json_encode( $remote_content ),
        );
        $response = self::decode_data( $remote_data );
        return $response;
    }

    public static function get_server_settings( $appKey, $appSecret ) {
        $remote_data = array(
            'method' => 'POST',
            'remoteAction' => 'app',
            'appkey' => $appKey,
            'appsecret' => $appSecret,
            'remoteContent' => '',
        );
        $response = self::decode_data( $remote_data );
        return $response;
    }

    public static function get_graph_data( $app_key, $app_secret, $type, $range, $value, $offset ) {
        $remote_data = array (
            'method' => 'POST',
            'remoteAction' => 'stats/graph?type=' . $type . '&range=' . $range . '&value=' . $value . '&tzOffset=' . $offset,
            'appkey' => $app_key,
            'appsecret' => $app_secret,
            'remoteContent' => '',
        );
        $response = self::decode_data( $remote_data );
        return $response;
    }

    public static function get_stats( $app_key, $app_secret ) {
        $remote_data = array (
            'method' => 'POST',
            'remoteAction' => 'stats/app',
            'appkey' => $app_key,
            'appsecret' => $app_secret,
            'remoteContent' => '',
        );
        $response = self::decode_data( $remote_data );
        return $response;
    }

    public static function save_remote_settings( $app_key, $app_secret, $roost_server_settings, $POST, $chrome_vars ) {
        if ( ! empty( $chrome_vars ) ) {
            $remote_content['serviceWorkerHostPath'] = $chrome_vars['html_url'];
            $remote_content['websiteURL'] = $chrome_vars['website_url'];
        }

        if ( ! empty( $remote_content ) ) {
            $remote_data = array(
                'method' => 'PUT',
                'remoteAction' => 'app',
                'appkey' => $app_key,
                'appsecret' => $app_secret,
                'remoteContent' => json_encode( $remote_content ),
            );
            self::roost_remote_request( $remote_data );
        }
    }

    public static function send_notification( $alert, $url, $image_url, $app_key, $app_secret, $device_tokens, $segments ) {
        $alert = Roost::filter_string( $alert );
        $remote_content = array(
            'alert' => $alert,
        );
        if ( null === $remote_content['alert'] ) {
            $remote_content['alert'] = '';
        }
        if ( $url ){
            $remote_content['url'] = $url;
        }
        if ( $image_url ) {
            $remote_content['imageURL'] = $image_url;
        }
        if ( $device_tokens ) {
            $remote_content['device_tokens'] = $device_tokens;
        }
        if ( $segments ) {
            $remote_content['segments'] = $segments;
        }
        $remote_data = array(
            'method' => 'POST',
            'remoteAction' => 'push',
            'appkey' => $app_key,
            'appsecret' => $app_secret,
            'remoteContent' => json_encode($remote_content),
        );
        $response = self::decode_data( $remote_data );
        return $response;
    }

}

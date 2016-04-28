<?php
if(file_exists(LFAPPS__PLUGIN_PATH . '/../vip-init.php' )) 
    require_once( LFAPPS__PLUGIN_PATH . '/../vip-init.php' );

/*
 * Extension to the WP http helper class
 */
class LFAPPS_Http_Extension {
    
    /* 
     * Map the Livefyre request signature to what WordPress expects.
     * This just means changing the name of the payload argument.
     *
     */
    public function request( $url, $args = array() ) {
        if(file_exists(LFAPPS__PLUGIN_PATH . '/../vip-init.php' )) {
            if ( isset( $args[ 'data' ] ) ) {
                $args[ 'body' ] = $args[ 'data' ];
                unset( $args[ 'data' ] );
            }
            return wpcom_vip_file_get_contents( $url, $args );
        } else {
            $http = new WP_Http;
            if ( isset( $args[ 'data' ] ) ) {
                $args[ 'body' ] = $args[ 'data' ];
                unset( $args[ 'data' ] );
            }
            $result = $http->request( $url, $args );
            // VIP: Fixing fatal error "Cannot use object of type WP_Error as array"
            if ( ! is_wp_error( $result ) ){
                return array('response'=>array('code'=>'500'));
            } else {
                return $result;
            }
        }
    }
}

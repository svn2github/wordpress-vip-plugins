<?php

/*
Plugin Name: Usatsi Gallery Wp Plugin
Description: Allows embedding of USA Today Sports Images Galleries.
Version: 1.0
Author: Thomas J. Rivera - USA Today Sports Images
Author URI:  http://www.usatodaysportsimages.com
License:     GPL2
*/

class Usatsi_Gallery_Plugin {

    /**
     * Loads SI Gallery Embed Scripts.
     *
     * @return void
     */
    static function init_scripts() {
        wp_enqueue_script( 'usatsi-gallery-scripts', plugins_url( 'js/usatsi-gallery-scripts.js', __FILE__ ), 'jquery', '1.0', true );
    }

    /**
     * SI Gallery Shortcode.
     *
     * @param array $atts shortcode attributes
     *
     * @return string
     */
    static function handle_shortcode( $atts ) {

        self::init_scripts();

        $gallery_atts = shortcode_atts( array(
            'id'    => '', // required!
            'title' => '', // optional!
        ), $atts );

        if ( ! empty( $gallery_atts[ 'id' ] ) ) {
            return '<div title="' . esc_attr( $gallery_atts[ 'title' ] ) . '" class="sigallery" data-gallery-id="' . esc_attr( $gallery_atts[ 'id' ] ) . '"></div>';
        } else {
            return '';
        }
    }

    /**
     * SI Gallery init & register shortcode.
     *
     * @return void
     */
    static function init() {
        add_shortcode( 'usatsi-gallery', array( __CLASS__, 'handle_shortcode' ) );
    }

}

Usatsi_Gallery_Plugin::init();
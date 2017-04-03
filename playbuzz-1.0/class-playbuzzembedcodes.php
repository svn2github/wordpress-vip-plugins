<?php

/**
 * Created by PhpStorm.
 * User: Lior
 * Date: 06/12/2016
 * Time: 15:45
 */

/**
 * Security check
 * Exit if file accessed directly.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class EmbedCodes
 *
 * Generate Playbuzz embed codes
 * TODO -- use this for shortcodes.
 * TODO -- add GetOptions method.
 */

class PlaybuzzEmbedCodes {

	public  $item_script_url = '//cdn.playbuzz.com/widget/feed.js';

	public  $item_script_handle = 'pb-feed-js';


	public function __construct() {

	}


	public function item( $item_id, $options ) {

		global $wp_version;

		$site_key       = ( ( ( array_key_exists( 'key',               $options ) ) ) ? $options['key'] : str_replace( 'www.', '', wp_parse_url( home_url(), PHP_URL_HOST ) ) );
		$site_info      = ( ( ( array_key_exists( 'info',              $options ) ) && ( '1' == $options['info']      ) ) ? 'true' : 'false' );
		$site_shares    = ( ( ( array_key_exists( 'shares',            $options ) ) && ( '1' == $options['shares']    ) ) ? 'true' : 'false' );
		$site_comments  = ( ( ( array_key_exists( 'comments',          $options ) ) && ( '1' == $options['comments']  ) ) ? 'true' : 'false' );
		$site_margintop = ( ( ( array_key_exists( 'margin-top',        $options ) ) ) ? $options['margin-top'] : '' );
		$width = 'auto';
		$height = 'auto';

		$code = '<script type="text/javascript" src="' . esc_url( $this -> item_script_url ) . '"></script>
                <div class="pb_feed" data-provider="WordPress ' . esc_attr( $wp_version ) . '"
                data-version=2
                data-key="' . esc_attr( $site_key ) . '"
                data-item="' . esc_attr( $item_id ) . '"
                data-game-info="' . esc_attr( $site_info ) . '"
                data-comments="' . esc_attr( $site_comments ) . '"
                data-shares="' . esc_attr( $site_shares ) . '"
                data-recommend="false"
                data-links=""
                data-width="' . esc_attr( $width ) . '"
                data-height="' . esc_attr( $height ) . '"
                data-margin-top="' . esc_attr( $site_margintop ) . '"></div>
	    ';

		return $code;

	}
}


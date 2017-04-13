<?php
/**
 * Created by PhpStorm.
 * User: Lior
 * Date: 29/11/2016
 * Time: 16:42
 */
/*
 * Exit if file accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Class PbConstants
 */
class PbConstants {
    public static $pb_url_param_key = 'pb-story';
    public static $pb_url_param_value = 'true';
    public static $pb_nonce_key = 'nonce';
    public static $pb_nonce_value = 'pb';
}
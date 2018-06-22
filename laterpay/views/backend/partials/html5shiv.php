<?php
if ( ! defined( 'ABSPATH' ) ) {
    // prevent direct access to this file
    exit;
}
echo "<!--[if lt IE 9]>\n";

foreach ( $laterpay['scripts'] as $script ) :
    // use ignore: enqueue script doesn't allow conditions.
    echo "<script src='" . esc_url( $script ) . "'></script>\n"; // phpcs:ignore

endforeach;

echo "\n<![endif]-->\n";

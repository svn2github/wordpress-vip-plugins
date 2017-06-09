<?php
/**
 * Optimizely X admin field partials: optimizely_url_targeting field
 *
 * @package Optimizely_X
 * @since 1.0.0
 */

// Negotiate URL targeting value.
$url_targeting = get_option( 'optimizely_url_targeting' );
if ( empty( $url_targeting ) ) {
	$url_targeting = get_site_url();
}

?>

<div>
	<input class="optimizely-requires-authentication"
		id="optimizely-url-targeting"
		name="optimizely_url_targeting"
		type="text"
		value="<?php echo esc_attr( $url_targeting );  ?>"
	/>
</div>
<p class="description">
	<?php printf(
		/* translators: 1: opening <a> tag, 2: </a> */
		esc_html__( 'This is the default location on your site you would like to run experiments on. By default we use your domain and a substring match so that the experiment will run anywhere on your site. Used with conditional activation this will assure you change the headline no matter where it is. For more info on URL targeting, please read this %1$sknowledge base article%2$s.', 'optimizely-x' ),
		'<a href="https://help.optimizely.com/hc/en-us/articles/200040835" target="_blank">',
		'</a>'
	); ?>
</p>

<?php
/**
 * Plugin Name: WP.com Geo Uniques
 * Description: Batcache-friendly way to handle geo-targetting of users at a country level. Relies on WP.com-specific functions but will work with WP_DEBUG.
 * Author: Automattic, WordPress.com VIP
 * Version: 0.1
 * License: GPLv2
 * Usage:
 *
 * 		wpcom_vip_load_plugin( 'wpcom-geo-uniques' );
 * 		wpcom_geo_add_country( 'us' ); // add list of supported countries
 *
 * 		if ( 'us' == wpcom_geo_get_user_country() ) {
 * 			echo "USA: A-Okay!";
 *		} else {
 *			echo "You're not American! No soup for you!";
 *		}
 *
 *	Note that this should only be used with a very small list of countries for performance reasons.
 */
class WPCOM_Geo_Uniques {

	const COOKIE_NAME = 'wpcom_geo';
	const ACTION_PARAM = 'wpcom-geolocate';

	private static $expiry_time = 3600;
	private static $default_country = 'default';
	private static $supported_countries = array();

	static function init() {
		if ( is_admin() )
			return;

		// Add default to list of supported countries
		static::add_country( static::$default_country );

		if ( ! self::user_has_country() ) {
			if ( isset( $_GET[ self::ACTION_PARAM ] ) )
				self::geolocate_user();
			else
				add_action( 'wp_head', array( __CLASS__, 'geoloate_js' ), 0 ); // geo-locate as early as possible
		}
	}

	function geolocate_user() {
		$country = static::ip2country();
		$expiry_date = date( 'D, d M Y H:i:s T', strtotime( "+" . static::$expiry_time . " seconds", current_time( 'timestamp', 1 ) ) );
		// output js and redirect
		header( 'Content-type: text/javascript' );
		?>
		document.cookie = '<?php printf( '%s=%s; expires=%s; max-age=%s; path=/', esc_js( self::COOKIE_NAME ), esc_js( $country ), esc_js( $expiry_date ), esc_js( self::$expiry_time ) ); ?>';
		window.location.reload();
		<?php
		exit;
	}

	function geoloate_js() {
		?>
		<script src="<?php echo esc_url( add_query_arg( self::ACTION_PARAM, '', home_url() ) ); ?>"></script>
		<?php
	}

	static function set_default_country( $country ) {
		if ( in_array( $country, static::$supported_countries ) )
			static::$default_country = $country;
	}

	static function add_country( $country ) {
		static::$supported_countries = array_merge( static::$supported_countries, (array) $country );
	}

	static function get_user_country() {
		static $user_country;

		if ( isset( $user_country ) )
			return $user_country;

		$checks = array();
		foreach( static::$supported_countries as $country ) {
			$checks[] = sprintf(
				'( "%1$s" == $_COOKIE["%2$s"] ) return "%1$s";',
				$country,
				static::COOKIE_NAME
			);
		}

		$test = sprintf( 'if %s', implode( 'elseif', $checks ) );
		$test .= sprintf( ' else return "%s";', static::$default_country );

		$user_country = static::run_vary_cache_on_function( $test );
		return $user_country;
	}

	static function user_has_country() {
		return static::run_vary_cache_on_function( 'return isset( $_COOKIE["'. self::COOKIE_NAME .'"] );' );
	}

	private static function ip2country() {
		$country = '';
		if ( function_exists( 'ip2location' ) ) {
			$location = ip2location( $_SERVER['REMOTE_ADDR'] );
			if ( $location )
				$country = $location->country_short;
		} elseif ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
			// the plugin is probably in a dev environment
			$random_country = array_rand( static::$supported_countries );
			$country = static::$supported_countries[ $random_country ];
		}

		if ( empty( $country ) )
			$country = static::$default_country;

		return strtolower( $country );
	}

	// Make it play nice with Batcache. Real nice.
	private static function run_vary_cache_on_function( $test ) {
		if ( function_exists( 'vary_cache_on_function' ) ) {
			vary_cache_on_function( $test );
		}

		$test_func = create_function( '', $test );
		return $test_func();
	}
}

add_action( 'init', array( 'WPCOM_Geo_Uniques', 'init' ) );

// helper functions
function wpcom_geo_add_country( $country ) {
	WPCOM_Geo_Uniques::add_country( $country );
}

function wpcom_geo_get_user_country( $country ) {
	WPCOM_Geo_Uniques::get_user_country();
}
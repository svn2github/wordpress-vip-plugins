<?php
/**
 * Plugin Name: WP.com Geo Uniques
 * Description: Batcache-friendly way to handle geo-targetting of users at a specific set of locations. Relies on WP.com-specific functions but will work with WP_DEBUG.
 * Author: Automattic, WordPress.com VIP
 * Version: 0.2
 * License: GPLv2
 * Usage:
 *
 * 		wpcom_vip_load_plugin( 'wpcom-geo-uniques' );
 * 		wpcom_geo_add_location( 'us' ); // add list of supported countries
 *
 * 		if ( 'us' == wpcom_geo_get_user_location() ) {
 * 			echo "USA: A-Okay!";
 *		} else {
 *			echo "You're not American! No soup for you!";
 *		}
 *
 *	Note that this should only be used with a very small list of locations for performance reasons.
 *
 *	By default, geo-location happens at a country-level but this can be extended to cities and states.
 *
 *	== Changelog ==
 *
 *	= 0.2 =
 *	- Props Zack Tollman and 10up
 *	- Support for more granular location sets (e.g. city-level)
 *	- New filters for tweaks (wpcom_geo_uniques_return_data, wpcom_geo_gelocate_js_query_args, etc.)
 *	- Fallback data for local testing
 */
class WPCOM_Geo_Uniques {

	const COOKIE_NAME = 'wpcom_geo';
	const ACTION_PARAM = 'wpcom-geolocate';

	private static $expiry_time = 3600;
	private static $default_location = 'default';
	private static $supported_locations = array();

	static function after_setup_theme() {
		if ( is_admin() )
			return;

		// Add default to list of supported countries
		static::add_location( static::$default_location );
		
		// Determine which piece of geolocation data to salt the cache key with
		$location_type = apply_filters( 'wpcom_geo_uniques_return_data', 'country_short' );

		if ( ! self::user_has_location() ) {
			if ( isset( $_GET[ self::ACTION_PARAM ] ) )
				self::geolocate_user( $location_type );
			else
				WPCOM_Geo_Uniques::geolocate_js(); // Do it nowww (aka as soon as possible)!
				//add_action( 'init', array( __CLASS__, 'geolocate_js' ), 0 ); // geo-locate as early as possible, I think init is as high as we can get...
		}
	}

	function geolocate_user( $location_type = 'country_short' ) {
		$location = static::ip2location( $location_type );
		$expiry_date = date( 'D, d M Y H:i:s T', strtotime( "+" . static::$expiry_time . " seconds", current_time( 'timestamp', 1 ) ) );
		// output js and redirect
		header( 'Content-type: text/javascript' );
		do_action( 'wpcom_geo_uniques_locate_user', $location, $expiry_date );
	?>
		document.cookie = '<?php printf( '%s=%s; expires=%s; max-age=%s; path=/', esc_js( self::COOKIE_NAME ), esc_js( $location ), esc_js( $expiry_date ), esc_js( self::$expiry_time ) ); ?>';
		window.location.reload();
	<?php
		exit;
	}

	function geolocate_js() {
		do_action( 'wpcom_geo_uniques_gelocate_js');
		$query_args = array( self::ACTION_PARAM => '' );
		$query_args = apply_filters( 'wpcom_geo_gelocate_js_query_args', $query_args );
	?>
		<script src="<?php echo esc_url( add_query_arg( $query_args, home_url() ) ); ?>"></script>
	<?php
	}

	static function set_default_location( $location ) {
		if ( in_array( $location, static::$supported_locations ) )
			static::$default_location = $location;
	}

	static function add_location( $location ) {
		static::$supported_locations = array_merge( static::$supported_locations, (array) $location );
	}

	static function get_user_location() {
		static $user_location;

		if ( isset( $user_location ) )
			return $user_location;

		$checks = array();
		foreach ( static::$supported_locations as $location ) {
			$checks[] = sprintf(
				'( "%1$s" == $_COOKIE["%2$s"] ) return "%1$s";',
				$location,
				static::COOKIE_NAME
			);
		}

		$test = sprintf( 'if %s', implode( 'elseif', $checks ) );
		$test .= sprintf( ' else return "%s";', static::$default_location );

		$user_location = static::run_vary_cache_on_function( $test );
		return $user_location;
	}

	static function user_has_location() {
		return static::run_vary_cache_on_function( 'return isset( $_COOKIE["' . self::COOKIE_NAME . '"] );' );
	}

	private static function ip2location( $location_type = 'country_short' ) {
		$location_full = self::get_geolocation_data();

		if ( $location_full && property_exists( $location_full, $location_type ) )
			$location = $location_full->$location_type;

		if ( empty( $location ) )
			$location = static::$default_location;

		$location = apply_filters( 'wpcom_geo_location', $location, $location_full, $location_type );

		return strtolower( $location );
	}

	private static function get_geolocation_data() {
		$location_full = null;

		if ( function_exists( 'ip2location' ) ) {
			$ip_address    = apply_filters( 'wpcom_geo_ip_address', $_SERVER['REMOTE_ADDR'] );
			$location_full = ip2location( $ip_address );
			$location_full = apply_filters( 'wpcom_geo_location_full', $location_full );
		} elseif ( defined( 'WP_DEBUG' ) && true === WP_DEBUG ) {
			// Add some fake data for dev
			$location_full                = new stdClass;
			$location_full->latitude      = '43.6525';
			$location_full->longitude     = '-79.381667';
			$location_full->country_short = 'CA';
			$location_full->country_long  = 'CANADA';
			$location_full->region        = 'ONTARIO';
			$location_full->city          = 'TORONTO';

			// Allows for debugging with specific test data
			$location_full = apply_filters( 'wpcom_geo_location_full_debug', $location_full );
		}

		return $location_full;
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

add_action( 'after_setup_theme', array( 'WPCOM_Geo_Uniques', 'after_setup_theme' ), 1 );

// helper functions
function wpcom_geo_add_location( $location ) {
	WPCOM_Geo_Uniques::add_location( $location );
}

function wpcom_geo_get_user_location() {
	WPCOM_Geo_Uniques::get_user_location();
}

function wpcom_geo_set_default_location( $location ) {
	WPCOM_Geo_Uniques::set_default_location( $location );
}

// @deprecated use wpcom_geo_add_location
function wpcom_geo_add_country( $country ) {
	WPCOM_Geo_Uniques::add_location( $country );
}

// @deprecated use wpcom_geo_get_user_location
function wpcom_geo_get_user_country( $country ) {
	WPCOM_Geo_Uniques::get_user_location();
}

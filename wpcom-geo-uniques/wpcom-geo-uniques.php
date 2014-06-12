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

	const COOKIE_NAME = '_wpcom_geo'; // must be prefixed with "_" since batcache ignores cookies starting with "wp"
	const ACTION_PARAM = 'wpcom-geolocate';

	private static $expiry_time = 604800; // 1 week
	private static $default_location = 'default';
	private static $supported_locations = array();
	private static $simple_mode = true;

	static function after_setup_theme() {
		if ( is_admin() )
			return;

		if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
			return;

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
			return;

		// Add default to list of supported countries
		self::add_location( self::get_default_location() );

		// If the theme hasn't registered any locations, bail. 
		$locations = self::get_registered_locations();
		if ( count( $locations ) <= 1 )
			return;

		static::$simple_mode = (bool) apply_filters( 'wpcom_geo_simple_mode', static::$simple_mode );

		// Note: For simple mode, we don't need to init anything simple location handling is on-demand
		if ( false === static::$simple_mode ) {
			self::init_advanced_geolocation();
		}
	}

	static function init_advanced_geolocation() {
		// Handle location detection on parse_request so we know the context of the request
		add_action( 'parse_request', array( 'WPCOM_Geo_Uniques', 'action_parse_request' ) );
	}

	static function action_parse_request( $request ) {
		// Don't do this on feed requests or robots.txt
		if ( ! empty( $request->query_vars['feed'] ) || ! empty( $request->query_vars['robots'] ) )
			return;

		if ( false === apply_filters( 'wpcom_geo_process_request', true, $request ) )
			return;

		if ( isset( $_GET[ self::ACTION_PARAM ] ) ) {
			// Determine which piece of geolocation data to salt the cache key with
			$location_type = apply_filters( 'wpcom_geo_uniques_return_data', 'country_short' );

			self::geolocate_user_and_die( $location_type );
		} 

		if ( ! self::user_has_location_cookie() ) {
			add_action( 'wp_head', array( __CLASS__, 'geolocate_js' ), -1 ); // We want this to run super early
		}

	}

	static function geolocate_user_and_die( $location_type = 'country_short' ) {
		if ( self::user_has_location_cookie() ) {
			?>
			// no location cookie needed
			<?php
			exit;
		}

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

	static function geolocate_js() {
		do_action( 'wpcom_geo_uniques_gelocate_js');
		$query_args = array( self::ACTION_PARAM => '' );
		$query_args = apply_filters( 'wpcom_geo_gelocate_js_query_args', $query_args );
	?>
		<script>
		( function() {
			// Avoid infinite loops and geolocation requests for clients that support javascript but not cookies
			var cookies_enabled = ( 'undefined' !== navigator.cookieEnabled && navigator.cookieEnabled ) ? true : null;

			if ( ! cookies_enabled ) {
				document.cookie = '__testcookie=1';
				if ( -1 !== document.cookie.indexOf( '__wpcomgeo_testcookie=1' ) ) {
					cookies_enabled = true;
				}
				var expired_date = new Date( 2003, 5, 27 );
				document.cookie = '__wpcomgeo_testcookie=1;expires=' + expired_date.toUTCString();
			}

			if ( cookies_enabled ) {
				var s = document.createElement( 'script' );
				s.src = '<?php echo esc_js( add_query_arg( $query_args, home_url() ) ); ?>';
				document.getElementsByTagName('head')[0].appendChild( s );
			}
		} )();
		</script>
	<?php
	}

	static function is_valid_location( $location ) {
		return in_array( $location, static::$supported_locations );
	}

	static function get_default_location() {
		return static::$default_location;
	}

	static function set_default_location( $location ) {
		if ( self::is_valid_location( $location ) ) {
			static::$default_location = $location;
		}
	}

	static function add_location( $location ) {
		static::$supported_locations = array_merge( static::$supported_locations, (array) $location );
	}

	static function get_registered_locations() {
		return static::$supported_locations;
	}

	static function get_user_location() {
		static $user_location;

		if ( isset( $user_location ) )
			return $user_location;

		if ( static::$simple_mode && ! self::user_has_location_cookie() ) {
			$user_location = self::get_user_location_from_global( '$_SERVER[ "GEOIP_COUNTRY_CODE" ]' );
		} else {
			$user_location = self::get_user_location_from_global( sprintf( '$_COOKIE[ "%s" ]', static::COOKIE_NAME ) );
		}

		return $user_location;
	}

	static function get_user_location_from_global( $global_var ) {
		$checks = array();
		foreach ( self::get_registered_locations() as $location ) {
			$checks[] = sprintf(
				'( "%1$s" == strtolower( %2$s ) ) { return "%1$s"; }',
				$location,
				$global_var
			);
		}

		$test  = sprintf( 'if ( empty( %s ) ) { return "%s"; }', $global_var, esc_js( self::get_default_location() ) );
		$test .= sprintf( ' elseif %s', implode( ' elseif ', $checks ) );
		$test .= sprintf( ' else { return "%s"; }', esc_js( self::get_default_location() ) );

		$user_location = static::run_vary_cache_on_function( $test );
		return $user_location;
	}

	static function user_has_location_cookie() {
		// TODO: should currently only be used in advanced mode
		return static::run_vary_cache_on_function( 'return isset( $_COOKIE[ "' . self::COOKIE_NAME . '" ] );' );
	}

	private static function ip2location( $location_type = 'country_short' ) {
		$location_full = self::get_geolocation_data_from_ip();

		if ( $location_full && property_exists( $location_full, $location_type ) )
			$location = $location_full->$location_type;

		if ( empty( $location ) )
			$location = static::$default_location;

		$location = apply_filters( 'wpcom_geo_location', $location, $location_full, $location_type );

		return strtolower( $location );
	}

	private static function get_geolocation_data_from_ip() {

		$location_full = apply_filters( 'wpcom_geo_pre_get_geolocation_data_from_ip', false, $_SERVER['REMOTE_ADDR'] );
		if ( false !== $location_full )
			return $location_full;

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
	return WPCOM_Geo_Uniques::get_user_location();
}

function wpcom_geo_set_default_location( $location ) {
	WPCOM_Geo_Uniques::set_default_location( $location );
}

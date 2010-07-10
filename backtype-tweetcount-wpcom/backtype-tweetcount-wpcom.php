<?php
/*
Plugin Name: BackType Tweetcount: WordPress.com Edition
Plugin URI:  http://viphostingtech.wordpress.com/plugins/backtype-tweetcount-wpcom/
Description: Shows the number of tweets your posts get and allows users to retweet. Recoded from scratch to make better usage of the WordPress API.
Version:     Based on 2.0
Author:      <a href="http://automattic.com/">Automattic</a>, <a href="http://www.backtype.com/plugins/tweetcount">BackType</a>
*/

class WPCom_BackType_Tweetcount {
	public $settings = array();

	public $admin_stub           = 'backtype-tweetcount-wpcom';
	public $option_group         = 'backtype_tweetcount_wpcom';
	public $option_name          = 'backtype_tweetcount_wpcom';
	public $shorturl_meta_prefix = '_backtype_shorturl_';

	// Plugin initialization
	function __construct() {
		add_action( 'admin_menu', array(&$this, 'admin_menu') );

		// Maybe automatically add the button to the post content
		$this->add_the_content_filter();

		// Don't show the button in the_excerpt()
		add_filter( 'get_the_excerpt', array(&$this, 'remove_the_content_filter'), 9 );
		add_filter( 'get_the_excerpt', array(&$this, 'add_the_content_filter'), 10 );

		// Default settings
		$defaultsettings = array(
			'src'        => '',
			'via'        => 0,
			'links'      => 0,
			'size'       => 'large',
			'location'   => 'top',
			'style'      => 'float:left;margin-right:10px;',
			'shortener'  => 'wpme',
			'api_key'    => '',
			'login'      => '',
			'background' => '',
			'border'     => '',
			'text'       => '',
		);

		// Merge user settings and defaults
		$usersettings = get_option( $this->option_name );
		if ( ! is_array( $usersettings ) ) {
			$usersettings = array();
			update_option( $this->option_name, $usersettings );
		}
		$this->settings = wp_parse_args( $usersettings, $defaultsettings );
	}


	// Register the settings page
	public function admin_menu() {

		// Register the settings page
		add_options_page( __( 'BackType Tweetcount' ), __( 'BackType Tweetcount' ), 'manage_options', $this->admin_stub, array(&$this, 'admin_page') );

		// Register the option handler and validator
		register_setting( $this->option_group, $this->option_name, array( &$this, 'validate_options' ) );

		// Register the settings sections and fields
		$section_tweets    = $this->admin_stub . '-tweets';
		$section_button    = $this->admin_stub . '-button';
		$section_shortener = $this->admin_stub . '-shortener';

		// New Tweet Settings
		add_settings_section( $section_tweets, __( 'New Tweet Settings' ), '__return_true', $this->admin_stub );
		add_settings_field( $section_tweets . '-leadingtext', __( 'Leading Text' ), array( &$this, 'settings_field_input' ), $this->admin_stub, $section_tweets, array( 'name' => 'src', 'text' => __( 'e.g. RT @BackType' ) ) );
		add_settings_field( $section_tweets . '-attribution', __( 'Attribution' ), array( &$this, 'settings_field_checkbox' ), $this->admin_stub, $section_tweets, array( 'name' => 'via', 'text' => __( 'Add BackType attribution' ) ) );

		// Button Settings
		add_settings_section( $section_button, __( 'Button Settings' ), '__return_true', $this->admin_stub );
		add_settings_field( $section_button . '-links', __( 'Links' ), array( &$this, 'settings_field_checkbox' ), $this->admin_stub, $section_button, array( 'name' => 'links', 'text' => __( 'Open links in new windows' ) ) );
		add_settings_field( $section_button . '-size', __( 'Size' ), array( &$this, 'settings_field_select' ), $this->admin_stub, $section_button, array( 'name' => 'size', 'values' => array( 'large' => __( 'Large' ), 'small' => __( 'Small' ) ) ) );
		add_settings_field( $section_button . '-location', __( 'Location' ), array( &$this, 'settings_field_select' ), $this->admin_stub, $section_button, array( 'name' => 'location', 'values' => array( 'top' => __( 'Top' ), 'bottom' => __( 'Bottom' ), 'topbottom' => __( 'Top & Bottom' ), 'manual' => __( 'Manual' ) ), 'text' => __( 'For manual positioning, <code>echo backtype_tweetcount();</code> where you would like the button to appear' ) ) );
		add_settings_field( $section_button . '-style', __( 'Wrapper Style' ), array( &$this, 'settings_field_input' ), $this->admin_stub, $section_button, array( 'name' => 'style', 'text' => __( 'CSS for positioning, margins, etc.' ) ) );
		add_settings_field( $section_button . '-background', __( 'Button Background Color' ), array( &$this, 'settings_field_input' ), $this->admin_stub, $section_button, array( 'name' => 'background', 'text' => __( 'e.g. FFFFFF' ) ) );
		add_settings_field( $section_button . '-border', __( 'Button Border Color' ), array( &$this, 'settings_field_input' ), $this->admin_stub, $section_button, array( 'name' => 'border', 'text' => __( 'e.g. 3399CC' ) ) );
		add_settings_field( $section_button . '-text', __( 'Button Text Color' ), array( &$this, 'settings_field_input' ), $this->admin_stub, $section_button, array( 'name' => 'text', 'text' => __( 'e.g. 000000' ) ) );

		// URL Shortener Settings
		add_settings_section( $section_shortener, __( 'URL Shortener Settings' ), '__return_true', $this->admin_stub );
		add_settings_field( $section_shortener . '-shortener', __( 'Shortening Service' ), array( &$this, 'settings_field_select' ), $this->admin_stub, $section_shortener, array( 'name' => 'shortener', 'values' => array( 'wpme' => __( 'WP.me (default)' ), 'custom' => __( 'Custom' ), 'btio' => 'bt.io', 'awesm' => 'awe.sm', 'bitly' => 'bit.ly', 'tinyurl' => 'tinyurl.com', 'digg' => 'digg.com', 'supr' => 'su.pr' ), 'text' => __( "WP.me is WordPress.com's shortening service, Custom uses <code>short_url</code> post meta value" ) ) );
		add_settings_field( $section_shortener . '-apikey', __( 'API Key' ), array( &$this, 'settings_field_input' ), $this->admin_stub, $section_shortener, array( 'name' => 'api_key', 'text' => __( 'Required: bit.ly, awe.sm, Optional: su.pr' ) ) );
		add_settings_field( $section_shortener . '-login', __( 'Login' ), array( &$this, 'settings_field_input' ), $this->admin_stub, $section_shortener, array( 'name' => 'login', 'text' => __( 'Required: bit.ly, Optional: su.pr' ) ) );
	}


	// Adds the button the_content filter
	public function add_the_content_filter( $content = '' ) {
		add_filter( 'the_content', array(&$this, 'maybe_add_button') );
		return $content;
	}


	// Removes the button the_content filter (for the excerpt)
	public function remove_the_content_filter( $content ) {
		remove_filter( 'the_content', array(&$this, 'maybe_add_button') );
		return $content;
	}


	// Conditionally adds the button to the post content
	public function maybe_add_button( $content ) {
		$options = $this->options; // For simplicity

		if ( is_feed() || 'manual' == $options['location'] )
			return $content;

		$button = $this->button();

		switch ( $options['location'] ) {
			case 'bottom':
				return $content . $button;
			case 'topbottom':
				return $button . $content . $button;
			case 'top':
			default:
				return $button . $content;
		}
	}


	// Create the button's HTML
	public function button( $src = null, $via = null, $links = null, $size = null, $style = null, $background = null, $border = null, $text = null, $shortener = null, $api_key = null, $login = null, $shorturl = null ) {
		global $post;

		if ( empty( $post->ID ) )
			return '';

		$options = $this->options; // For simplicity

		// Let users override these vars when calling manually
		$src        = ( null === $src )        ? $options['src']        : $src;
		$via        = ( null === $via )        ? $options['via']        : (int) $via;
		$links      = ( null === $links )      ? $options['links']      : (int) $links;
		$size       = ( null === $size )       ? $options['size']       : $size;
		$style      = ( null === $style )      ? $options['style']      : $style;
		$background = ( null === $background ) ? $options['background'] : $background;
		$border     = ( null === $border )     ? $options['border']     : $border;
		$text       = ( null === $text )       ? $options['text']       : $text;
		$shortener  = ( null === $shortener )  ? $options['shortener']  : $shortener;
		$api_key    = ( null === $api_key )    ? $options['api_key']    : $api_key;
		$login      = ( null === $login )      ? $options['login']      : $login;

		$url = get_permalink( $post->ID );

		// Fetch the tweet count
		if ( false === $tweetcount = get_transient( 'backtype_tweetcount_' . $post->ID ) ) {
			$response = wp_remote_get( 'http://backtweets.com/search.php?identifier=bttc&since_id=0&refresh=1&q=' . urlencode( $url ) );

			if ( is_wp_error( $response ) ) {
				$tweetcount = 0;
			} else {
				$data = unserialize( wp_remote_retrieve_body( $response ) );
				$tweetcount = ( !empty( $data['results_count'] ) ) ? (int) $data['results_count'] : 0;
			}

			set_transient( 'backtype_tweetcount_' . $post->ID, $tweetcount, mt_rand( 800, 1000 ) ); // Randomized so all don't expire at once
		}

		// Create the short URL if it wasn't passed as an arg
		if ( ! $shorturl ) {
			switch ( $shortener ) {

				// Pull a custom short URL from the post meta
				case 'custom':
					if ( $shorturl = get_post_meta( $post->ID, 'short_url', true ) )
						break;

				// BackType creates these URLs on their own
				case 'btio':
				case 'awesm':
					$shorturl = false;
					break;

				// Get (and generate if need be) a short URL from a service
				case 'bitly':
				case 'tinyurl':
				case 'digg':
				case 'supr':
					if ( $shorturl = $this->get_shortlink( $post->ID, $url, $shortener, $api_key, $login ) )
						break;

				// Default to using WordPress' internal shortlink API
				default;
					$shorturl = wp_get_shortlink( $post->ID, $post->post_type, false );
					break;
			}
		}

		// Generate the HTML
		$button = '';

		if ( $style )
			$button .= "<div style='$style'>\n";

		$button = '	<script type="text/javascript">
	<!--
		tweetcount_url = "' . esc_js( $url ) . '";
		tweetcount_title = "' . esc_js( strip_tags( $post->post_title ) ) . '";
		tweetcount_cnt = "' . (int) $tweetcount . '";
';

		if ( false !== $shorturl )
			$button .= '		tweetcount_short_url = "' . esc_js( $shorturl ) . '";' . "\n";

		if ( $src )
			$button .= '		tweetcount_src = "' . esc_js( $src ) . '";' . "\n";

		if ( 0 == $via )
			$button .= "		tweetcount_via = false;\n";

		if ( $links )
			$button .= "		tweetcount_links = true;\n";

		if ( $size )
			$button .= '		tweetcount_size = "' . esc_js( $size ) . '";' . "\n";

		if ( $background )
			$button .= '		tweetcount_background = "' . esc_js( $background ) . '";' . "\n";

		if ( $border )
			$button .= '		tweetcount_border = "' . esc_js( $border ) . '";' . "\n";

		if ( $text )
			$button .= '		tweetcount_text = "' . esc_js( $text ) . '";' . "\n";

		if ( $api_key )
			$button .= '		tweetcount_api_key = "' . esc_js( $api_key ) . '";' . "\n";

		$button .= "\t-->\n\t</script>\n\t<script type='text/javascript' src='http://widgets.backtype.com/tweetcount.js'></script>\n";

		if ( $style )
			$button .= "</div>\n";

		return $button;
	}


	// Fetches and/or generates a short URL from a third party service
	public function get_shortlink( $post_ID, $url, $service, $api_key = null, $login = null ) {

		// Already cached?
		if ( $shorturl = get_post_meta( $post_ID, $this->shorturl_meta_prefix . $service, true ) )
			return $shorturl;

		// Did we recently have a failure to get a short URL for this service? If so, abort for now.
		// This is to avoid lots of timeouts for a down service.
		if ( get_transient( 'backtype_shorurl_fail_' . $service ) )
			return false;

		// Get a new short URL
		switch ( $service ) {
			case 'bitly':
				if ( empty( $api_key ) || empty( $login ) )
					return false;

				$response = wp_remote_get( 'http://api.bit.ly/shorten?version=2.0.1&longUrl=' . urlencode( $url ) . '&login=' . $login . '&apiKey=' . $api_key );

				// Failed reponse, try again in a bit
				if ( is_wp_error( $response ) || ! $data = json_decode( wp_remote_retrieve_body( $response ) ) || empty( $data['results'] ) ) {
					set_transient( 'backtype_shorurl_fail_' . $service, 1, mt_rand( 800, 1000 ) );
					return false;
				}

				$keys = array_keys( $data['results'] );
				if ( !empty( $data['results'][$keys[0]]['shortCNAMEUrl'] ) )
					return $data['results'][$keys[0]]['shortCNAMEUrl'];
				elseif ( !empty($data['results'][$keys[0]]['shortUrl'] ) )
					return $data['results'][$keys[0]]['shortUrl'];

				set_transient( 'backtype_shorurl_fail_' . $service, 1, mt_rand( 800, 1000 ) );
				return false;

			case 'tinyurl':
				$response = wp_remote_get( 'http://tinyurl.com/api-create.php?url=' . urlencode( $url ) );

				if ( is_wp_error( $response ) ) {
					set_transient( 'backtype_shorurl_fail_' . $service, 1, mt_rand( 800, 1000 ) );
					return false;
				}

				return wp_remote_retrieve_body( $response );

			case 'digg':
				// send user agent, etc.
		}
	}


	// Administration settings
	public function admin_page() { ?>

<div class="wrap">
	<h2><?php _e( 'BackType Tweetcount' ); ?></h2>

	<form action="options.php" method="post">
		<?php settings_fields( $this->option_group ); ?>
		<?php do_settings_sections( $this->admin_stub ); ?>
		<p class="submit">
			<input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" />
		</p>
	</form>
</div>
<?php
	}


	// Output a text input settings field
	public function settings_field_input( $args ) {
		echo '<input type="text" name="backtype_tweetcount_wpcom[' . $args['name'] . ']" value="' . esc_attr( $this->settings[$args['name']] ) . '" class="regular-text" /> <span class="description">' . $args['text'] . '</span>';
	}


	// Output a checkbox settings field
	public function settings_field_checkbox( $args ) {
		echo '<label><input type="checkbox" name="backtype_tweetcount_wpcom[' . $args['name'] . ']" value="1"';
		checked( $this->settings[$args['name']], 1 );
		echo " /> {$args['text']}</label>";
	}


	// Output a select settings field
	public function settings_field_select( $args ) {
		echo '<select name="backtype_tweetcount_wpcom[' . $args['name'] . ']">';

		foreach ( (array) $args['values'] as $value => $title ) {
			echo '<option value="' . $value . '"';
			selected( $this->settings[$args['name']], $value );
			echo '>' . esc_html( $title ) . '</option>';
		}

		echo '</select>';

		if ( !empty( $args['text'] ) )
			echo " {$args['text']}";
	}


	// Validate form results
	public function validate_options( $options ) {
		$options = array_map( 'strip_tags', $options );

		$options['via']   = ( !empty( $options['via'] ) )   ? 1 : 0;
		$options['links'] = ( !empty( $options['links'] ) ) ? 1 : 0;

		return $options;
	}
}


// Start this plugin once all other plugins are fully loaded
add_action( 'init', 'WPCom_BackType_Tweetcount', 5 );
function WPCom_BackType_Tweetcount() {
	global $WPCom_BackType_Tweetcount;
	$WPCom_BackType_Tweetcount = new WPCom_BackType_Tweetcount();
}


// Template function for manual placement
if ( ! function_exists( 'backtype_tweetcount' ) ) {
	function backtype_tweetcount( $src = null, $via = null, $links = null, $size = null, $style = null, $background = null, $border = null, $text = null, $shortener = null, $api_key = null, $login = null, $shorturl = null ) {
		global $WPCom_BackType_Tweetcount;
		return $WPCom_BackType_Tweetcount->button( $src, $via, $links, $size, $style, $background, $border, $text, $shortener, $api_key, $login, $shorturl );
	}
}

?>
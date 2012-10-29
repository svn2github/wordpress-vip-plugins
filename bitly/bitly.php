<?php
/**
 * Plugin Name: Bit.ly
 * Version: 1.0
 * Author: Micah Ernst
 * Description: Uses bit.ly API to get shortened url for a post on publish and saves url as meta data. Based on TIME.com's Bit.ly plugin.
 */

class Bitly {
	
	// storing a copy of the api credentials
	var $options;
	
	/**
	 * Set our options and some hooks
	 */
	function __construct() {
		
		$this->options = $this->get_options();

		add_action( 'admin_menu', array( $this, 'admin_menu') );
		
		// default supported post types
		add_post_type_support( 'post', 'bitly' );
		add_post_type_support( 'page', 'bitly' );
		
		// only hook into the publish_post hook if api credentials have been specified
		if( isset( $this->options['api_login'] ) && isset( $this->options['api_key'] ) ) {
		
			add_action( 'publish_post', array( $this, 'publish_post' ), 50, 2 );
			add_action( 'publish_future_post', array( $this, 'publish_post' ), 50, 2 );
		}
	}
	
	/**
	 * Checks the post's status and creates a bitly url if it's publishing for the first time
	 *
	 * @param int $post_id
	 * @param object $post
	 */
	function publish_post( $post_id, $post ) {
		
		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;
		
		// only save short urls for the following post types		
		if( !post_type_supports( $post->post_type, 'bitly' ) )	
			return;
		
		// only get short url when post is published	
		if( $post->post_status != 'publish' )
			return;
		
		// all good, lets make a url
		$this->generate_bitly_url( $post_id );
	}
	
	/**
	 * Create a bitly url if one doesnt already exist for the passed post id
	 *
	 * @param int $post_id
	 *
	 * @return mixed 
	 */
	function generate_bitly_url( $post_id ) {
		
		extract( $this->options );
		
		$bitly_url = bitly_get_url( $post_id );
		
		if( empty( $bitly_url ) ) {
	
			// need to test this if the post is a time_slide	
			$params = http_build_query(
				array(
					'login' => $api_login,
					'apiKey' => $api_key,
					'longUrl' => get_permalink( $post_id ),
					'format' => 'json',
				)
			);
			
			$rest_url = 'https://api-ssl.bitly.com/v3/shorten?' . $params;
			
			$response = wp_remote_get( $rest_url );
			
			// if we get a valid response, save the url as meta data for this post
			if( !is_wp_error( $response ) ) {
	
				$json = json_decode( wp_remote_retrieve_body( $response ) );
	
				if( isset( $json->data->url ) ) {
					update_post_meta( $post_id, 'bitly_url', $json->data->url );
					return $json->data->url;
				}
			}
		}
		
		return false;
		
	}
	
	/**
	 * Wrapper function to get our bitly options
	 */
	function get_options() {
		return wp_parse_args( get_option('bitly_settings'), array(
			'bitly_api_login' => '',
			'bitly_api_key' => ''
		));
	}
	
	/**
	 * Register a submenu page and the settings fields we'll use on that page
	 */
	function admin_menu() {
		
		// reg our section
		add_settings_section( 'api', 'API Credentials', '__return_false', 'bitly-options' );
		
		// create an api login and key field
		add_settings_field( 'bitly_api_login', 'API Login', array( $this, 'textfield' ), 'bitly-options', 'api', array(
			'name' => 'bitly_settings[api_login]',
			'value' => $this->options['api_login'],
		));
		
		add_settings_field( 'show_lede_dates', 'API Key', array( $this, 'textfield' ), 'bitly-options', 'api', array(
			'name' => 'bitly_settings[api_key]',
			'value' => $this->options['api_key'],
		));
		
		// set our validation callback
		register_setting( 'bitly_settings', 'bitly_settings', array( $this, 'validate_settings' ) );
		
		// create a sub menu page within settings menu page
		add_submenu_page( 'options-general.php', 'Bit.ly Settings', 'Bit.ly', 'edit_theme_options', 'bitly-settings', array( $this, 'settings_page' ) );
	}
	
	/**
	 * Builds a simple text field
	 */
	function textfield( $args ) {
		extract( wp_parse_args( $args, array(
			'name' => null,
			'value' => null,
		)));
		?>
		<input type="text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text"/>
		<?php
	}
	
	/**
	 * Sanitize the values the user entered on our settings page
	 */
	function validate_settings( $input ) {
		
		$output = array();
		
		$output['api_login'] = sanitize_text_field( $input['api_login'] );
		$output['api_key'] = sanitize_text_field( $input['api_key'] );
		
		return $output;
	}
	
	/**
	 * Build the html for our settings screen
	 */
	function settings_page() {
		?>
		<div class="wrap">
			<div id="icon-options-general" class="icon32"><br></div>
			<h2>Bit.ly Settings</h2>
			<form action="options.php" method="post">
				<?php
				wp_nonce_field( 'bitly_settings', 'bitly_settings_nonce', false );
				settings_fields( 'bitly_settings' );
				do_settings_sections( 'bitly-options' );
				?>
				<p class="submit">
					<input type="submit" name="submit" class="button-primary" value="Save Changes"/>
				</p>
			</form>
		</div>
		<?php
	}
}
$bitly = new Bitly();

/**
 * Helper function to get the short url for a post
 *
 * @param int $post_id
 * @return string $url
 */
function bitly_get_url( $post_id = null ) {

	$post_id = empty( $post_id ) ? get_the_ID() : $post_id;
	
	return get_post_meta( $post_id, 'bitly_url', true );
}

/**
 * Generate short_url for use in bitly_process_posts()
 */
function bitly_generate_short_url( $post_id ) {
	global $bitly;
	if ( is_object( $bitly ) && is_callable( $bitly, 'generate_bitly_url' ) )
		return call_user_func( $bitly, 'generate_bitly_url', $post_id );
	return false;
}

/**
 * Filter to replace the default shortlink
 */
function bitly_shortlink( $shortlink, $id, $context ) {
	
	if( $context == 'post' ) {
		$bitly = bitly_get_url( $id );
		if( $bitly ) $shortlink = esc_url( $bitly );
	}
	
	return $shortlink;
}
add_filter( 'pre_get_shortlink', 'bitly_shortlink', 10, 3 );

/**
 * Cron to process all of the posts that don't have bitly urls
 */
function bitly_process_posts() {
	
	global $wpdb;
	
	// get 100 published posts that don't have a bitly url
	$query = "
		SELECT $wpdb->posts.ID
		FROM $wpdb->posts
		WHERE NOT EXISTS (
			SELECT ID
			FROM $wpdb->postmeta
			WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id
			AND $wpdb->postmeta.meta_key = 'bitly_url'
		)
		AND ( $wpdb->posts.post_type = 'post' OR $wpdb->posts.post_type = 'page' )
		AND ( $wpdb->posts.post_status = 'publish' )
		GROUP BY $wpdb->posts.ID
		ORDER BY $wpdb->posts.post_date DESC
		LIMIT 0, 100
	";
	
	$posts = $wpdb->get_results( $query );
	
	if( $posts ) {

		// process these posts
		foreach( $posts as $p ) {
			bitly_generate_short_url( $p->ID );
		}
	} else {

		// kill our scheduled event
		add_option( 'bitly_processed', 1 );
		wp_clear_scheduled_hook( 'bitly_hourly_hook' );
	}
}

// Enable backfill for posts that don't have a bitly url
add_action( 'init', 'bitly_init_post_backfill' );

function bitly_init_post_backfill() {
	add_action( 'bitly_hourly_hook', 'bitly_process_posts' );

	$bitly_processed = get_option( 'bitly_processed' );

	if ( ! $bitly_processed && ! wp_next_scheduled( 'bitly_hourly_hook' ) )
		wp_schedule_event( time() + 30, 'hourly', 'bitly_hourly_hook' );
}

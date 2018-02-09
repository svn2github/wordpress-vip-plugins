<?php
/*
Plugin name: Getty Images
Plugin URI: http://www.oomphinc.com/work/getty-images-wordpress-plugin/
Description: Integrate your site with Getty Images
Author: gettyImages
Author URI: http://gettyimages.com/
Version: 3.0.6
*/

/*  Copyright 2014  Getty Images

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/***
 ** Getty Images: The WordPress plugin!
 ***/
class Getty_Images {
	// Define and register singleton
	private static $instance = false;
	public static function instance() {
		if( !self::$instance )
			self::$instance = new Getty_Images;

		return self::$instance;
	}

	private function __clone() { }

	const capability = 'edit_posts';
	const getty_imageid_meta_key = 'getty_images_image_id';
	const getty_details_meta_key = 'getty_images_image_details';

	/**
	* Returns current plugin version.
	*
	* @return string Plugin version
	*/
	private function __plugin_get_version() {
		$plugin_data = get_plugin_data( __FILE__ );
		$plugin_version = $plugin_data['Version'];
		return $plugin_version;
	}

	/**
	 * Register actions and filters
	 *
	 * @uses add_action, add_filter
	 * @return null
	 */
	private function __construct() {
		// Enqueue essential assets
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );

		// Add the Getty Images media button
		add_action( 'media_buttons', array( $this, 'media_buttons' ), 20 );

		// Prevent publishing posts with comp images
		add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ), 20, 2 );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );

		// Create view templates used by the Getty Images media manager
		add_action( 'print_media_templates', array( $this, 'print_media_templates' ) );

		// Handle the various AJAX actions from the UI
		add_action( 'wp_ajax_getty_images_download', array( $this, 'ajax_download' ) );
		add_action( 'wp_ajax_getty_image_details', array( $this, 'ajax_image_details' ) );
		add_action( 'wp_ajax_getty_get_facets', array( $this, 'ajax_get_facets' ) );

		// Allow for validation of comp images
		add_filter( 'contains_getty_comp', array( $this, 'filter_contains_getty_comp' ), 0, 2 );

		// Register shorcodes
		add_action( 'init', array( $this, 'action_init' ) );

		// Handle shortcode alignment
		add_filter( 'embed_oembed_html', array( $this, 'align_embed' ), 10, 4 );

		// Add caller to oembed call
		add_filter( 'oembed_fetch_url', array( $this, 'add_caller_oembed' ), 10, 3 );

		// Add styles for alignment
		add_action( 'wp_head', array( $this, 'frontend_style' ) );

		// Add Google Tag Manager
		add_action( 'admin_footer', array( $this, 'admin_footer' ));
	}

	/**
	 * Register shortcodes
	 */
	function action_init() {
		wp_oembed_add_provider( 'http://gty.im/*', 'http://embed.gettyimages.com/oembed' );
	}
	
	/**
	 * Filter embed fetch url
	 * @param  string $provider    oembed provider url
	 * @param  string $url     original oembed URL
	 * @param  string $args     additional arguments
	 **/
	function add_caller_oembed( $provider, $url, $args ) {
		$domain = home_url();
		$caller = rawurlencode($domain . '#wp-plugin');

        // check that this is a getty embed
		if ( strpos( $url, 'http://gty.im/' ) === 0 ) {
			$provider = add_query_arg('caller', $caller, $provider);
		}
		return $provider;
	}
	
	/**
	 * Filter embed shortcode html
	 * @param  string $html    html generated from embed shortcode
	 * @param  string $url     original oembed URL
	 * @param  array $attr     shortcode attributes
	 * @param  integer $post_ID post id
	 * @return string          modified or original html
	 */
	function align_embed( $html, $url, $attr, $post_ID ) {
		// check that this is a getty embed
		if ( strpos( $url, 'http://gty.im/' ) === 0 ) {
			if ( isset( $attr['align'] ) && in_array( $attr['align'], array( 'left', 'center', 'right' ), true ) ) {
				return '<div class="getty ' . esc_attr( 'align' . $attr['align'] ) . '">' . $html . '</div>';
			}
		}
		return $html;
	}

	/**
	 * Print some frontend styles for getty embeds that are aligned
	 */
	function frontend_style() {
		?>
		<style>
		.getty.aligncenter {
			text-align: center;
		}
		.getty.alignleft {
			float: none;
			margin-right: 0;
		}
		.getty.alignleft > div {
			float: left;
			margin-right: 5px;
		}
		.getty.alignright {
			float: none;
			margin-left: 0;
		}
		.getty.alignright > div {
			float: right;
			margin-left: 5px;
		}
		</style>
		<?php
	}

	// Convenience methods for adding 'message' data to standard
	// WP JSON responses
	function ajax_error( $message = null, $data = array() ) {
		if( !is_null( $message ) ) {
			$data['message'] = $message;
		}

		wp_send_json_error( $data );
	}

	function ajax_success( $message = null, $data = array() ) {
		if( !is_null( $message ) ) {
			$data['message'] = $message;
		}

		wp_send_json_success( $data );
	}

	/**
	 * Check against a nonce to limit exposure, all AJAX handlers must use this
	 */
	function ajax_check() {
		if( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'getty-images' ) ) {
			$this->ajax_error( __( "Invalid nonce", 'getty-images' ) );
		}
	}

	/**
	 * Include all of the templates used by Backbone views
	 */
	function print_media_templates() {
		include( __DIR__ . '/getty-templates.php' );
	}

	/**
	 * Enqueue all assets used for admin view. Localize scripts.
	 */
	function admin_enqueue() {
		global $pagenow;

		// Only operate on edit post pages
		if( 'post.php' !== $pagenow  && 'post-new.php' !== $pagenow )
			return;

		// Ensure all the files required by the media manager are present
		wp_enqueue_media();

		wp_register_script( 'jquery-cookie', plugins_url( '/js/vendor/jquery.cookie.js', __FILE__ ), array( 'jquery' ), '2006', true );

		$isWPcom = self::isWPcom();
		$current_timestamp = time();

		// Determine if the Ga and GTM Javascript should be loaded
		$load_tracking = true;
		$settings = isset( $_COOKIE['wpGIc'] ) ? json_decode( stripslashes( $_COOKIE['wpGIc'] ) ) : false;
		if ( isset( $settings->{'omniture-opt-in'} ) && ! $settings->{'omniture-opt-in'} ) {
			$load_tracking = false;
		}
		if ( $isWPcom ) {
			$googleAnalyticsId = 'UA-85194766-9';
			$googleTagManagerId = 'GTM-TBS9LM9';
		} else {
			$googleAnalyticsId = 'UA-85194766-10';
			$googleTagManagerId = 'GTM-WCPDGK9';
		}

		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-dialog');

		wp_enqueue_style('wp-jquery-ui-dialog');

		wp_enqueue_script( 'moment-js', plugins_url( '/js/vendor/moment.min.js', __FILE__ ), array(), 1, true );

		wp_enqueue_script( 'spin-js', plugins_url( '/js/vendor/spin.js', __FILE__ ), array(), 1, true );
		wp_enqueue_script( 'firebase-app-js', plugins_url( '/js/vendor/firebase-app.js', __FILE__ ), array(), $current_timestamp, true );
		wp_enqueue_script( 'firebase-auth-js', plugins_url( '/js/vendor/firebase-auth.js', __FILE__ ), array( 'firebase-app-js' ), $current_timestamp, true );
		wp_enqueue_script( 'firebase-database-js', plugins_url( '/js/vendor/firebase-database.js', __FILE__ ), array( 'firebase-app-js' ), $current_timestamp, true );
		wp_enqueue_script( 'firebase-messaging-js', plugins_url( '/js/vendor/firebase-messaging.js', __FILE__ ), array( 'firebase-app-js' ), $current_timestamp, true );
		wp_enqueue_script( 'getty-mosaic', plugins_url( '/js/getty-mosaic.js', __FILE__ ), array(), 1, true );
		wp_enqueue_script( 'getty-images-filters', plugins_url( '/js/getty-filters.js', __FILE__ ), array(), $current_timestamp, true );
		wp_enqueue_script( 'getty-images-views', plugins_url( '/js/getty-views.js', __FILE__ ), array( 'getty-images-filters', 'spin-js' ), $current_timestamp, true );
		wp_enqueue_script( 'getty-images-firebase', plugins_url( '/js/getty-firebase.js', __FILE__ ), array( 'firebase-app-js' ), $current_timestamp, true );

		// Register specific Omniture version of s_code for VIP or .org
		if($isWPcom) {
			wp_register_script( 'getty-omniture-scode', apply_filters( 'getty_images_s_code_js_url', plugins_url( '/js/vendor/s_code_vip.js', __FILE__ ) ), array(), $current_timestamp, true );
		} else {
			wp_register_script( 'getty-omniture-scode', apply_filters( 'getty_images_s_code_js_url', plugins_url( '/js/vendor/s_code_org.js', __FILE__ ) ), array(), $current_timestamp, true );
		}
		

		wp_enqueue_script( 'getty-images-models', plugins_url( '/js/getty-models.js', __FILE__ ), array( 'jquery-cookie', 'getty-omniture-scode' ), $current_timestamp, true );
		wp_enqueue_script( 'getty-images', plugins_url( '/js/getty-images.js', __FILE__ ), array( 'getty-images-views', 'getty-images-models' ), $current_timestamp, true );

		wp_enqueue_style( 'getty-base-styles', plugins_url( '/css/getty-base-styles.css', __FILE__ ) );
		wp_enqueue_style( 'getty-about-text', plugins_url( '/css/getty-about-text.css', __FILE__ ) );
		wp_enqueue_style( 'getty-browser', plugins_url( '/css/getty-browser.css', __FILE__ ) );
		wp_enqueue_style( 'getty-choose-mode', plugins_url( '/css/getty-choose-mode.css', __FILE__ ) );
		wp_enqueue_style( 'getty-images-login', plugins_url( '/css/getty-images-login.css', __FILE__ ) );
		wp_enqueue_style( 'getty-refinement-panel', plugins_url( '/css/getty-refinement-panel.css', __FILE__ ) );
		wp_enqueue_style( 'getty-sidebar-container', plugins_url( '/css/getty-sidebar-container.css', __FILE__ ) );
		wp_enqueue_style( 'getty-title-bar', plugins_url( '/css/getty-title-bar.css', __FILE__ ) );
		wp_enqueue_style( 'getty-toolbar', plugins_url( '/css/getty-toolbar.css', __FILE__ ) );
		wp_enqueue_style( 'getty-welcome', plugins_url( '/css/getty-welcome.css', __FILE__ ) );
		wp_enqueue_style( 'getty-landing', plugins_url( '/css/getty-landing.css', __FILE__ ) );

		if( $load_tracking ) {
			// Register Google Analytics
			wp_enqueue_script( 'google-analytics', plugins_url( '/js/vendor/google-analytics.js', __FILE__ ), array(), $this->__plugin_get_version(), true );
			wp_localize_script( 'google-analytics', 'google_analytics_data',
					array( 
						'ID' => $googleAnalyticsId
					)
				);

			//Register Google Tag Manager
			wp_enqueue_script( 'google-tag-manager-head', plugins_url( '/js/vendor/google-tag-manager-head.js', __FILE__ ), array(), $this->__plugin_get_version(), true );
			wp_localize_script( 'google-tag-manager-head', 'google_tag_manager_data',
					array( 
						'ID' => $googleTagManagerId
					)
				);
		}

		// Nonce 'n' localize!
		wp_localize_script( 'getty-images-filters', 'gettyImages',
			array(
				'nonce' => wp_create_nonce( 'getty-images' ),
				'sizes' => $this->get_possible_image_sizes(),
				'embedSizes' => array(
					'scale50' => array( 'scale' => 0.50, 'label' => __( 'Scaled 50%', 'getty-images' ) ),
					'scale75' => array( 'scale' => 0.75, 'label' => __( 'Scaled 75%', 'getty-images' ) ),
				),
				'isWPcom' => $isWPcom,
				'text' => array(
					// Getty Images search field placeholder
					'searchPlaceholder' => __( "Enter keywords...", 'getty-images' ),
					// Search button text
					'search' => __( "Search", 'getty-images' ),
					// 'Refine' collapsible link
					'refine' => __( "Refine", 'getty-images' ),
					// Search refinement field placeholder
					'refinePlaceholder' => __( "Search within...", 'getty-images' ),
					// Search only within these categories
					'refineCategories' => __( "Refine categories", 'getty-images' ),
					// This will be used as the default button text
					'title'  => __( "Getty Images", 'getty-images' ),
					// This will be used as the default button text
					'button' => __( "Insert Image", 'getty-images' ),

					// Downloading...
					'authorizing' => __( "Authorizing...", 'getty-images' ),
					'downloading' => __( "Downloading...", 'getty-images' ),
					'remaining' => __( "remaining", 'getty-images' ),
					'free' => __( "free", 'getty-images' ),
					'inOverage' => __( "in overage", 'getty-images' ),

					// Results
					'oneResult' => __( "%d result", 'getty-images' ),
					'results' => __( "%d results", 'getty-images' ),
					'noResults' => __( "Sorry, we found zero results matching your search.", 'getty-images' ),

					// Full Sized images
					'fullSize' => __( 'Full Size', 'getty-images' ),
					'recentlyViewed' => __( "Recently Viewed", 'getty-images' ),

					// Image download
					'downloadImage' => __( "Download Image", 'getty-images' ),
					'reDownloadImage' => __( "Download Again", 'getty-images' ),

					//// Frame toolbar buttons
					'insertComp' => __( "Insert Comp into Post", 'getty-images' ),
					'embedImage' => __( "Embed Image into Post", 'getty-images' ),
					'insertImage' => __( "Insert Image into Post", 'getty-images' ),
					'selectImage' => __( "Select Image", 'getty-images' ),

					//// Filters
					'imageType' => __( "Image Type", 'getty-images' ),
					'assetType' => __( "Asset Type", 'getty-images' ),
					'editorial' => __( "Editorial", 'getty-images' ),
					'creative' => __( "Creative", 'getty-images' ),

					'imageType' => __( "Image Type", 'getty-images' ),
					'photography' => __( "Photography", 'getty-images' ),
					'illustration' => __( "Illustration", 'getty-images' ),

					'orientation' => __( "Orientation", 'getty-images' ),
					'horizontal' => __( "Horizontal", 'getty-images' ),
					'vertical' => __( "Vertical", 'getty-images' ),

					'excludeNudity' => __( "Exclude Nudity?", 'getty-images' ),

					'sortOrder' => __( "Sort Order", 'getty-images' ),
					'bestMatch' => __( "Best Match", 'getty-images' ),
					'newest' => __( "Newest", 'getty-images' ),
					'oldest' => __( "Oldest", 'getty-images' ),
					'mostPopular' => __( "Most Popular", 'getty-images' ),
					'mostRecent' => __( "Most Recent", 'getty-images' ),

					'bestMatch' => __( "Best Match", 'getty-images' ),
					'newest' => __( "Newest", 'getty-images' ),

					'alignments' => array(
						'none' => __( 'None', 'getty-images' ),
						'left' => __( 'Left', 'getty-images' ),
						'center' => __( 'Center', 'getty-images' ),
						'right' => __( 'Right', 'getty-images' ),
					),

					'productTypes' => __( "Product types", 'getty-images' ),
					'premiumAccess' => __( "Premium Access", 'getty-images' ),
					'easyAccess' => __( "Easy-access", 'getty-images' ),
					'editorialSubscription' => __( "Editorial Subscription", 'getty-images' ),
					'royaltyfreeSubscription' => __( "Royalty Free Subscription", 'getty-images' ),
					'imagePack' => __( "Ultra Pack", 'getty-images' ),

					'numberOfPeople' => __( "Number of people", 'getty-images' ),
					'noPeople' => __( "No people", 'getty-images' ),
					'onePerson' => __( "One person", 'getty-images' ),
					'twoPerson' => __( "Two person", 'getty-images' ),
					'groupOfPeople' => __( "Group of people", 'getty-images' ),

					'age' => __( "Age", 'getty-images' ),
					'newborn' => __( "Newborn", 'getty-images' ),
					'baby' => __( "Baby", 'getty-images' ),
					'child' => __( "Child", 'getty-images' ),
					'teenager' => __( "Teenager", 'getty-images' ),
					'youngAdult' => __( "Young adult", 'getty-images' ),
					'adult' => __( "Adult", 'getty-images' ),
					'adultsOnly' => __( "Adults Only", 'getty-images' ),
					'matureAdult' => __( "Mature adult", 'getty-images' ),
					'seniorAdult' => __( "Senior adult", 'getty-images' ),
					'_0_1months' => __( "0-1 months", 'getty-images' ),
					'_2_5months' => __( "2-5 months", 'getty-images' ),
					'_6_11months' => __( "6-11 months", 'getty-images' ),
					'_12_17months' => __( "12-17 months", 'getty-images' ),
					'_18_23months' => __( "18-23 months", 'getty-images' ),
					'_2_3years' => __( "2-3 years", 'getty-images' ),
					'_4_5years' => __( "4-5 years", 'getty-images' ),
					'_6_7years' => __( "6-7 years", 'getty-images' ),
					'_8_9years' => __( "8-9 years", 'getty-images' ),
					'_10_11years' => __( "10-11 years", 'getty-images' ),
					'_12_13years' => __( "12-13 years", 'getty-images' ),
					'_14_15years' => __( "14-15 years", 'getty-images' ),
					'_16_17years' => __( "16-17 years", 'getty-images' ),
					'_18_19years' => __( "18-19 years", 'getty-images' ),
					'_20_24years' => __( "20-24 years", 'getty-images' ),
					'_20_29years' => __( "20-29 years", 'getty-images' ),
					'_25_29years' => __( "25-29 years", 'getty-images' ),
					'_30_34years' => __( "30-34 years", 'getty-images' ),
					'_30_39years' => __( "30-39 years", 'getty-images' ),
					'_35_39years' => __( "35-39 years", 'getty-images' ),
					'_40_44years' => __( "40-44 years", 'getty-images' ),
					'_40_49years' => __( "40-49 years", 'getty-images' ),
					'_45_49years' => __( "45-49 years", 'getty-images' ),
					'_50_54years' => __( "50-54 years", 'getty-images' ),
					'_50_59years' => __( "50-59 years", 'getty-images' ),
					'_55_59years' => __( "55-59 years", 'getty-images' ),
					'_60_64years' => __( "60-64 years", 'getty-images' ),
					'_60_69years' => __( "60-69 years", 'getty-images' ),
					'_65_69years' => __( "65-69 years", 'getty-images' ),
					'_70_79years' => __( "70-79 years", 'getty-images' ),
					'_80_89years' => __( "80-89 years", 'getty-images' ),
					'_90_plusYears' => __( "90 plus years", 'getty-images' ),
					'over100' => __( "Over 100", 'getty-images' ),

					'peopleComposition' => __( "People composition", 'getty-images' ),
					'headShot' => __( "Head shot", 'getty-images' ),
					'waistUp' => __( "Waist up", 'getty-images' ),
					'threeQuarterLength' => __( "Three quarter length", 'getty-images' ),
					'fullLength' => __( "Full length", 'getty-images' ),
					'lookingAtCamera' => __( "Looking at camera", 'getty-images' ),
					'candid' => __( "Candid", 'getty-images' ),

					'imageStyle' => __( "Image style", 'getty-images' ),
					'fullFrame' => __( "Full frame", 'getty-images' ),
					'closeUp' => __( "Close up", 'getty-images' ),
					'portrait' => __( "Portrait", 'getty-images' ),
					'sparse' => __( "Sparse", 'getty-images' ),
					'abstract' => __( "Abstract", 'getty-images' ),
					'macro' => __( "Macro", 'getty-images' ),
					'stillLife' => __( "Still life", 'getty-images' ),
					'cutOut' => __( "Cut out", 'getty-images' ),
					'copySpace' => __( "Copy space", 'getty-images' ),

					'ethnicity' => __( "Ethnicity", 'getty-images' ),
					'eastAsian' => __( "Easn Asian", 'getty-images' ),
					'southeastAsian' => __( "Southeast Asian", 'getty-images' ),
					'southAsian' => __( "South Asian", 'getty-images' ),
					'black' => __( "Black", 'getty-images' ),
					'hispanicLatino' => __( "Hispanic/Latino", 'getty-images' ),
					'caucasian' => __( "Caucasian", 'getty-images' ),
					'middleEastern' => __( "Middle Eastern", 'getty-images' ),
					'nativeAmericanFirstNations' => __( "Native American/First Nations", 'getty-images' ),
					'pacificIslander' => __( "Pacific Islander", 'getty-images' ),
					'mixedRacePerson' => __( "Mixed Race Person", 'getty-images' ),
					'multiEthnicGroup' => __( "Multi-Ethnic Group", 'getty-images' ),
				),
				'tracking' => array(
					'user' => array('username' => ''),
					'search' => array('results_count' => 0, 'search_term' => ''),
					'asset' => array('id' => '', )
				)
			)
		);
	}

	static function isWPcom() {
		return function_exists( 'wpcom_is_vip' ) && wpcom_is_vip();
	}

	/**
	 * Add "Getty Images..." button to edit screen
	 *
	 * @action media_buttons
	 */
	function media_buttons( $editor_id = 'content' ) { ?>
		<a href="#" id="insert-getty-button" class="button getty-images-activate add_media"
			data-editor="<?php echo esc_attr( $editor_id ); ?>"
			title="<?php esc_attr_e( "Getty Images...", 'getty-images' ); ?>"><span class="getty-media-buttons-icon"></span><?php esc_html_e( "Getty Images...", 'getty-images' ); ?></a>
	<?php
	}

	/**
	 * Check if a string contains a comp via filter
	 *
	 * @filter contains_getty_images_comp
	 * @return bool
	 */
	function filter_contains_getty_images_comp( $contains_comp, $content ) {
		return $this->contains_comp( $content );
	}

	/**
	 * Does this string contain a Getty Images comp image?
	 * @param $post_content string
	 * @return bool
	 */
	function contains_comp( $post_content ) {
		return preg_match( '|https?://cache\d+\.asset-cache\.net|', $post_content );
	}

	/**
	 * Don't allow users to publish posts with comp images in their content
	 *
	 * @filter wp_insert_post_data
	 * @param array $postdata
	 * @param array $postarr
	 * @return array $postdata
	 */
	function wp_insert_post_data( $data, $postarr ) {
		if( $this->contains_comp( $data['post_content'] ) && 'publish' === $data['post_status'] ) {
			$data['post_status'] = 'draft';
		}

		return $data;
	}

	/**
	 * Notify the user if they tried to save an image with a comp
	 * @action admin_notices
	 */
	function admin_notices() {
		global $pagenow;

		if( 'post.php' !== $pagenow || !isset( $_GET['post'] ) ) {
			return;
		}

		$post = get_post();

		if( !$post ) {
			return;
		}

		if( $this->contains_comp( $post->post_content ) ) {
			// can't use esc_html__ since it would break the HTML tags in the string to be translated.
			echo '<div class="error getty-images-message"><p>' . wp_kses_post(
				__( "<strong>WARNING</strong>: You may not publish posts with Getty Images comps. Download the image first in order to include it into your post.", 'getty-images' ) )
			. '</p></div>';
		}
	}

    function getty_download_url( $url, $timeout = 300 ) {
        //WARNING: The file is not automatically deleted, The script must unlink() the file.
        if ( ! $url )
            return new WP_Error('http_no_url', __('Invalid URL Provided.'));

        $url_filename = basename( parse_url( $url, PHP_URL_PATH ) );

        $tmpfname = wp_tempnam( $url_filename );
        if ( ! $tmpfname )
            return new WP_Error('http_no_file', __('Could not create Temporary file.'));

        $response = wp_safe_remote_get( $url, array( 'timeout' => $timeout, 'stream' => true, 'filename' => $tmpfname ) );

        preg_match("/filename=([^*]+)/", $response['headers']['content-disposition'], $filename);

        if ( is_wp_error( $response ) ) {
            unlink( $tmpfname );
            return $response;
        }

        if ( 200 != wp_remote_retrieve_response_code( $response ) ){
            unlink( $tmpfname );
            return new WP_Error( 'http_404', trim( wp_remote_retrieve_response_message( $response ) ) );
        }

        $content_md5 = wp_remote_retrieve_header( $response, 'content-md5' );
        if ( $content_md5 ) {
            $md5_check = verify_file_md5( $tmpfname, $content_md5 );
            if ( is_wp_error( $md5_check ) ) {
                unlink( $tmpfname );
                return $md5_check;
            }
        }

        return array('tmpfname' => $tmpfname, 'fname' =>  $filename[1]);
    }

	/**
	 * Download an image from a URL, attach Getty MetaData which will also act
	 * as a flag that the image came from GettyImages
	 *
	 * @action wp_ajax_getty_images_download
	 */
	function ajax_download() {
		$this->ajax_check();

		if( !current_user_can( self::capability ) ) {
			$this->ajax_error( __( "User can not download images", 'getty-images' ) );
		}

		// Sanity check inputs
		if( !isset( $_POST['url'] ) ) {
			$this->ajax_error( __( "Missing image URL", 'getty-images' ) );
		}

		$url = sanitize_url( $_POST['url'] );

		if( empty( $url ) ) {
			$this->ajax_error( __( "Invalid image URL", 'getty-images' ) );
		}

		if( !isset( $_POST['meta'] ) ) {
			$this->ajax_error( __( "Missing image meta", 'getty-images' ) );
		}

		if( !is_array( $_POST['meta'] ) || !isset( $_POST['meta']['id'] ) ) {
			$this->ajax_error( __( "Invalid image meta", 'getty-images' ) );
		}

        // Getty Images delivery URLs have the pattern:
        //
        // http://delivery.gettyimages.com/../<filename>.<ext>?TONSOFAUTHORIZATIONDATA
        //
        // Check that the URL component is correct:
        if( strpos( $url, 'https://delivery.gettyimages.com/' ) !== 0 ) {
            $this->ajax_error( "Invalid URL" );
        }

		// Download the image, but don't necessarily attach it to this post.
		$tmp = $this->getty_download_url( $url );

		// Wah wah
		if( is_wp_error( $tmp ) ) {
			$this->ajax_error( __( "Failed to download image", 'getty-images' ) );
		}

		$file_array = array();

		$file_array['name'] = basename( $tmp['fname'] );
		$file_array['tmp_name'] = $tmp['tmpfname'];

		$attachment_id = media_handle_sideload( $file_array, 0 );

		if( is_wp_error( $attachment_id ) ) {
			$this->ajax_error( __( "Failed to sideload image", 'getty-images' ) );
		}

		// Set the post_content to post_excerpt for this new attachment, since
		// the field put in post_content is meant to be used as a caption for Getty
		// Images.
		//
		// We would normally use a filter like wp_insert_post_data to do this,
		// preventing an extra query, but unfortunately media_handle_sideload()
		// uses wp_insert_attachment() to insert the attachment data, and there is
		// no way to filter the data going in via that function.
		$attachment = get_post( $attachment_id );

		if( !$attachment ) {
			$this->ajax_error( __( "Attachment not found", 'getty-images' ) );
		}

    $post_parent = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;

		wp_update_post( array(
			'ID' => $attachment->ID,
			'post_content' => '',
      		'post_excerpt' => $attachment->post_content,
      		'post_parent' => $post_parent
		) );

		// Trash any existing attachment for this Getty Images image. Don't force
		// delete since posts may be using the image. Let the user force file delete explicitly.
		$getty_id = sanitize_text_field( $_POST['meta']['id'] );

		$existing_image_ids = get_posts( array(
			'post_type' => 'attachment',
			'post_status' => 'any',
			'meta_key' => self::getty_details_meta_key,
			'meta_value' => $getty_id,
			'fields' => 'ids',
			'suppress_filters' => false
		) );

		foreach( $existing_image_ids as $existing_image_id ) {
			wp_delete_post( $existing_image_id );
		}

		// Save the getty image details in post meta, but only sanitized top-level
		// string values
		update_post_meta( $attachment->ID, self::getty_details_meta_key, array_map( 'sanitize_text_field', array_filter( $_POST['meta'], 'is_string' ) ) );

		// Save the image ID in a separate meta key for serchability
		update_post_meta( $attachment->ID, self::getty_imageid_meta_key, sanitize_text_field( $_POST['meta']['ImageId'] ) );

		// Success! Forward new attachment_id back
		$this->ajax_success( __( "Image downloaded", 'getty-images' ), wp_prepare_attachment_for_js( $attachment_id ) );
	}

	/**
	 * Figure out potential sizes that can be used for displaying a comp
	 * in the post. Because the comp lives remotely and is never downloaded into WordPress,
	 * there's no ability to crop, so just inform JavaScript of which sizes can be used.
	 *
	 * @return array
	 */
	function get_possible_image_sizes() {
		$possible_sizes = apply_filters( 'image_size_names_choose', array(
			'thumbnail' => __('Thumbnail', 'getty-images'),
			'medium'    => __('Medium', 'getty-images'),
			'large'     => __('Large', 'getty-images'),
			'full'      => __('Full Size', 'getty-images'),
		) );

		unset( $possible_sizes['full'] );

		foreach( $possible_sizes as $size => $label ) {
			$possible_sizes[$size] = array(
				'width' => get_option( $size . '_size_w' ),
				'height' => get_option( $size . '_size_h' ),
				'label' => $label
			);
		}

		return $possible_sizes;
	}

	/**
	 * Fetch facets for current query to get the specific people filter values.
	 *
	 * @action wp_ajax_getty_get_facets
	 */
	function ajax_get_facets() {
		$this->ajax_check();

		$url = $_POST["url"];

		$request = function_exists('vip_safe_wp_remote_get') ? vip_safe_wp_remote_get( $url ) : wp_remote_get( $url );

		if( is_wp_error( $request ) ) {
			$this->ajax_error( __( "Error getting facets", 'getty-images' ), $request );
		}

		$body = wp_remote_retrieve_body( $request );

		$data = json_decode( $body );

		$response = array();
		$response['facets'] = $data;

		$this->ajax_success( __( "Succesfully loaded facets", 'getty-images' ), $response ); 
	}

	/**
	 * Fetch details about a particular image by Getty image ID
	 *
	 * @action wp_ajax_getty_image_details
	 */
	function ajax_image_details() {
		$this->ajax_check();

		// User should only be able to read the posts DB to see these details
		if( !current_user_can( self::capability ) ) {
			$this->ajax_error( __( "No access", 'getty-images' ) );
		}

		// Sanity check inputs
		if( !isset( $_POST['ImageId'] ) ) {
			$this->ajax_error( __( "Missing image ID", 'getty-images' ) );
		}

		$id = sanitize_text_field( $_POST['ImageId'] );

		if( empty( $id ) ) {
			$this->ajax_error( __( "Invalid image ID", 'getty-images' ) );
		}

		$posts = get_posts( array(
			'post_type' => 'attachment',
			'meta_key' => self::getty_imageid_meta_key,
			'meta_value' => $id,
			'posts_per_page' => 1,
			'suppress_filters' => false
		) );

		if( empty( $posts ) ) {
			$this->ajax_error( __( "No attachments found", 'getty-images' ) );
		}

		$this->ajax_success( __( "Got image details", 'getty-images' ), wp_prepare_attachment_for_js( $posts[0] ) );
	}

	function admin_footer() {
		$isWPcom = self::isWPcom();

		if($isWPcom) {
			$googleTagManagerID = 'GTM-TBS9LM9';
		} else {
			$googleTagManagerID = 'GTM-WCPDGK9';
		}

		echo '
			<!-- Google Tag Manager (noscript) -->
			<noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.esc_html($googleTagManagerID).'" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
			<!-- End Google Tag Manager (noscript) -->
		';
	}

}

Getty_Images::instance();

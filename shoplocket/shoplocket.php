<?php
/**
 * Plugin Name: ShopLocket
 * Plugin URI: http://shoplocket.com
 * Description: Sell your products straight from your site.
 * Author: Randy Hoyt, Mohammad Jangda, ShopLocket
 * Version: 0.1
 * Author URI: http://shoplocket.com
 * Text Domain: shoplocket
 * Domain Path: /languages
 */

if(!defined("SHOPLOCKET_CURRENT_PAGE"))
	define("SHOPLOCKET_CURRENT_PAGE", basename($_SERVER['PHP_SELF']));
 
class ShopLocket {
	const BASE_URL = 'https://www.shoplocket.com';
	const OAUTH_NEW_INSTALL = '/integrations/wordpress_installs/new_install';
	const OAUTH_AUTHORIZE = '/oauth/authorize';
	const OAUTH_REDIRECT_FOLDER = 'shoplocket/oauth/authorize/';
	const EMBED_URL_SPRINTF_PATTERN = 'https://www.shoplocket.com/products/%s/embed';
	const PRODUCT_URL_SPRINTF_PATTERN = 'https://www.shoplocket.com/products/%s';
	const URL_REGEX_PATTERN = '#https://www\.shoplocket\.com/products/([\w]+)(/embed[a-zA-Z0-9-]*)?#';
	const IFRAME_REGEX_PATTERN = '!<iframe((?:\s+\w+=[\'"][^\'"]*[\'"]\s*)*)src=[\'"]https://www\.shoplocket\.com/products/(\w+)/embed[a-zA-Z0-9-]*[\'"]((?:\s+\w+=[\'"][^\'"]*[\'"]\s*)*)></iframe>!i';
	const DEFAULT_WIDTH = 510;
	const DEFAULT_HEIGHT = '400';
	const HELP_URL = 'http://help.shoplocket.com/customer/portal/articles/675322-why-isn-t-shoplocket-working-on-my-wordpress-blog-post-';

	static function load() {
		require_once( dirname( __FILE__ ) . '/class-shoplocket-widget.php' );

		add_shortcode( 'shoplocket', array( __CLASS__, 'render_shortcode' ) );
		wp_embed_register_handler( 'shoplocket', self::URL_REGEX_PATTERN, array( __CLASS__, 'embed_handler' ) );

		add_action( 'init', array('ShopLocket','shoplocket_tinymce_addbuttons'));
		add_action( 'init', array( __CLASS__, 'init' ) );

	}

	static function init() {
		if ( current_user_can( 'unfiltered_html' ) )
			add_filter( 'content_save_pre', array( __CLASS__, 'content_save_pre_embed_to_shortcode' ) );
		else
			add_filter( 'pre_kses', array( __CLASS__, 'pre_kses_embed_to_shortcode' ) );

		if(in_array(SHOPLOCKET_CURRENT_PAGE, array('post.php', 'post-new.php')))
		{
			add_action('media_buttons_context', array('ShopLocket', 'add_product_button')); 
			add_action('admin_footer',  array('ShopLocket', 'add_product_popup'));
		}
		if ( SHOPLOCKET_CURRENT_PAGE == 'post.php' ||
			 SHOPLOCKET_CURRENT_PAGE ==  'post-new.php' ||
			(SHOPLOCKET_CURRENT_PAGE == 'options-general.php' && isset($_GET["page"]) && $_GET["page"]==="shoplocket_settings")
		   )
		{
			wp_enqueue_style( "shoplocket", plugins_url( '/css/shoplocket-admin.css?2', __FILE__ ));        
		}
		add_filter( 'mce_css', array( 'ShopLocket', 'shoplocket_mce_css' ) );       
		add_action( 'admin_menu', array( 'ShopLocket', 'settings_add_shoplocket_page') );              
		add_action( 'wp_ajax_shoplocket_get_products', array('ShopLocket','shoplocket_get_products'));      
		add_action( 'wp_ajax_shoplocket_dismiss_config_message', array('ShopLocket','shoplocket_dismiss_config_message'));
		add_action( 'admin_footer', array( 'ShopLocket', 'display_config_message' ) );
		add_action( 'admin_post_shoplocket_admin_css_products', array('ShopLocket', 'shoplocket_admin_css_products'));

	}

	static function install() {
		delete_option("shoplocket_dismiss_config_message");
	}
	
	static function display_config_message() {
		if (isset($_GET["page"]) && $_GET["page"] == "shoplocket_settings") return;

		$dismissed = get_option("shoplocket_dismiss_config_message");
		if ($dismissed != "") return; 

		$shoplocket = get_option("shoplocket_settings");
		if (isset($shoplocket["access_token"]) && $shoplocket["access_token"] != "") return; 
		
		echo '<div id="message" class="error shoplocket_config_message"><p><strong>Connect to ShopLocket:</strong> You must connect your WordPress site to your ShopLocket account. <strong><a href="' . admin_url( 'options-general.php?page=shoplocket_settings' ) . '">Connect Now &rarr;</a></strong><a id="shoplocket_dismiss_config_message_button" class="button alignright" style="position: relative; top: -4px;" href="javascript:dismissShopLocketConfigMessage(&quot;' . wp_create_nonce('shoplocket_settings') . '&quot;)">Dismiss</a></p></div>';
		wp_enqueue_script(
				'shoplocket_dismiss_config_message',
				plugins_url('/js/dismiss-config-message.js', __FILE__),
				null,
				null,
				true
			);
	}

	public static function shoplocket_admin_css_products() {

		header("Content-type: text/css; charset: UTF-8");
		$json = get_option("shoplocket_products_json");
		if ($json) {
			$products = json_decode(get_option("shoplocket_products_json"));
			if ($products) {
				foreach($products->products as $product) {
					echo '#shoplocket' . $product->token . '
						{background: 50% 20px no-repeat url("' . $product->images[0]->sizes->thumb . '"),
									 url("' . plugins_url('/img/bluegrid.png', __FILE__ ) . '") 50% -1px;}
					' . "\n";   
				}
			}
		}

	}

	public static function shoplocket_mce_css( $mce_css ) {
		if ( ! empty( $mce_css ) )
			$mce_css .= ',';

		// static css for editor
		$mce_css .= plugins_url( '/css/editor.css?7', __FILE__ );
		
		// separator
		$mce_css .= ',';

		// dynamic css for individual products
		$version = get_transient("use_shoplocket_products_json"); // use the time the data was pulled for cache-busting
		if (!$version) {
			$version = time();
		}
		$mce_css .= admin_url('admin-post.php?action=shoplocket_admin_css_products&version=' . $version);

		return $mce_css;
	}

	public static function shoplocket_dismiss_config_message() {    
		
		$nonce = "";
		if (isset($_POST["nonce"])) {
			$nonce = $_POST["nonce"];           
		}
		if (! wp_verify_nonce($nonce, 'shoplocket_settings') ) wp_die('You do not have permission to save this page.');
		if (! current_user_can("manage_options") ) wp_die('You do not have permission to save this page.');
		$shoplocket = update_option('shoplocket_dismiss_config_message', 'true');
		$to_wordpress = array('code' => 200);
		echo json_encode($to_wordpress);
		die();
	}

	function shoplocket_tinymce_addbuttons() {
	   // Don't bother doing this stuff if the current user lacks permissions
	   if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') )
		 return;
	 
	   // Add only in Rich Editor mode
	   if ( get_user_option('rich_editing') == 'true') {
		 add_filter("mce_external_plugins", array('ShopLocket',"shoplocket_tinymce_add_plugin"));
	   }
	}

	// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
	function shoplocket_tinymce_add_plugin($plugin_array) {
	   $plugin_array['shoplocketproduct'] = plugins_url('/js/editor_plugin.js', __FILE__);
	   return $plugin_array;
	}           

	/**
	 * Allow using a URL on its own line:
	 *    https://www.shoplocket.com/products/02b0c58fd5f/embed
	 *    https://www.shoplocket.com/products/02b0c58fd5f
	 */
	function embed_handler( $matches, $attr, $url, $rawattr ) {
		return self::render_shortcode( array( 'url' => $url ) );
	}

	function content_save_pre_embed_to_shortcode( $content ) {
		// This data is slashed, so we need to do the strip and add dance
		return addslashes( self::embed_to_shortcode( stripslashes( $content ), self::IFRAME_REGEX_PATTERN ) );
	}

	function pre_kses_embed_to_shortcode( $content ) {
		return self::embed_to_shortcode( $content, self::IFRAME_REGEX_PATTERN );
	}

	/**
	 * Convert an standard iframe embed into a shortcode.
	 * Borrowed from Jetpack's Vimeo Shortcode
	 *
	 * Adding this to post content
	 *    <iframe class='shoplocket-embed' src='https://www.shoplocket.com/products/02b0c58fd5f/embed' width='510' height='400' frameborder='0' style='max-width:100%;'scrolling='no'></iframe>
	 * Turns into
	 *    [shoplocket id="02b0c58fd5f" width="510" height="400"]
	 */
	function embed_to_shortcode( $content, $regexp ) {
		if ( false === stripos( $content, self::BASE_URL ) ) 
			return $content;

		$regexp_ent = str_replace( '&amp;#0*58;', '&amp;#0*58;|&#0*58;', htmlspecialchars( $regexp, ENT_NOQUOTES ) ); 

		foreach ( array( 'regexp', 'regexp_ent' ) as $reg ) {
			if ( ! preg_match_all( $$reg, $content, $matches, PREG_SET_ORDER ) )
				continue;

			foreach ( $matches as $match ) {
				$id = $match[2];

				$params = $match[1] . $match[3];

				if ( 'regexp_ent' == $reg ) 
					$params = html_entity_decode( $params );

				$params = wp_kses_hair( $params, array( 'http' ) );

				$width = isset( $params['width'] ) ? (int) $params['width']['value'] : 0;
				$height = isset( $params['height'] ) ? (int) $params['height']['value'] : 0;

				$wh = '';
				if ( $width && $height ) 
					$wh = ' w=' . $width . ' h=' . $height; 

				$shortcode = '[shoplocket id=' . $id . $wh . ']';
				$content = str_replace( $match[0], $shortcode, $content );
			}
		}

		return $content;
	}

	/**
	 * [shoplocket url="https://www.shoplocket.com/products/02b0c58fd5f/embed" width=500 height=400]
	 * or
	 * [shoplocket id="02b0c58fd5f" width=500 height=400]
	 */
	function render_shortcode( $atts ) {

		wp_enqueue_script('shoplocket_external_js','https://www.shoplocket.com/assets/widgets/embed.js?body=1',null,null,true);

		$atts = self::normalize_args( $atts );

		$error_message = sprintf(
			__( 'You need to specify <a href="%s" target="_blank">a valid "url" or "id" </a> for your ShopLocket product.', 'shoplocket' ),
			self::HELP_URL
		);

		if ( empty( $atts['url'] ) && empty( $atts['id'] ) ) {
			if ( current_user_can( 'edit_posts' ) )
				return '<p>' . $error_message . '</p>';
			return;
		}

		if ( $atts['url'] ) {
			$atts['id'] = self::get_id_from_url( $atts['url'] );
		}

		if ( $atts['id'] && ctype_alnum( $atts['id'] ) ) {
			$url = self::get_embed_url_from_id( $atts['id'] );
		} else {
			if ( current_user_can( 'edit_posts' ) )
				return '<p>' . $error_message . '</p>';
			return;
		}

		// TODO: validate width and height

		return sprintf( '<iframe class="shoplocket-embed" src="%1$s" width="%2$s" height="%3$s" frameborder="0" style="max-width:100%4$s;" scrolling="no"></iframe>',
			esc_url( $url ),
			esc_attr( $atts['w'] ),
			esc_attr( $atts['h'] ),
			"%"
		);
	}

	/**
	 * Inserts an "Add ShopLocket Product" button to the media buttons.
	 */  
	public static function add_product_button($context){
		$button = '<a onclick="javascript:launchShopLocketProduct();" class="button add_shoplocket_product" data-editor="content" title="' . __("Add ShopLocket Product", 'shoplocket') . '"><span class="wp-media-buttons-icon"></span> ' . __('Add Product') . '</a>';
		if (get_bloginfo( 'version' ) < 3.5) {
			$button = '<a href="#TB_inline?width=640&height=100%&inlineId=select_shoplocket_product" class="thickbox" id="add_shoplocket_product" title="' . __("Add ShopLocket Product", 'shoplocket') . '"><img src="' . plugin_dir_url( __FILE__ ) . '/img/icon-shoplocket.png"></a>';
		}
		return $context . $button;
	}
	
	/**
	 * Inserts the hidden div that gets launched by the new
	 * "Add ShopLocket Product" button.
	 */ 
	public static function add_product_popup(){
		?>
		<script>
		
			function launchShopLocketProduct() {
				getShopLocketProducts();
				tb_show("<?php echo __("Add ShopLocket Product", 'shoplocket'); ?>", "#TB_inline?width=640&inlineId=select_shoplocket_product");
				jQuery("#TB_ajaxContent").height(jQuery("#TB_window").height()-48)
			}
			function addShopLocketProduct(){
								
				var shoplocket_shortcode;
				if( ! tinyMCE.activeEditor || tinyMCE.activeEditor.isHidden()) {
					shoplocket_shortcode = document.getElementById("shoplocket_shortcode").value;
				} else {
					shoplocket_shortcode = document.getElementById("shoplocket_shortcode_visual").value;
				}

				if(shoplocket_shortcode == ""){
					alert("<?php echo __("Please select a product.", "shoplocket"); ?>");
					return;
				}
				var width = validatePreviewSize(document.getElementById('shoplocket_width').value);
				var height = validatePreviewSize(document.getElementById('shoplocket_height').value);

				shoplocket_shortcode = shoplocket_shortcode.replace("{W}",width);
				shoplocket_shortcode = shoplocket_shortcode.replace("{H}",height);
				shoplocket_shortcode = shoplocket_shortcode.replace("{W}",width);
				shoplocket_shortcode = shoplocket_shortcode.replace("{H}",height);              
				
				unclickShopLocketProduct();             
				window.send_to_editor(shoplocket_shortcode);
			}
			
			function clickShopLocketProduct(clickedListItem) {
			
				// add selected class
				unclickShopLocketProduct(clickedListItem.parentNode);               
				clickedListItem.className = "selected";
				
				// put the shortcode in the hidden field
				clickedListItem.getElementsByTagName('input');
				inputs = clickedListItem.getElementsByTagName('input');
				document.getElementById("shoplocket_shortcode").value = inputs[0].value;
				document.getElementById("shoplocket_shortcode_visual").value = inputs[1].value;
				document.getElementById("shoplocket_width").value = clickedListItem.getAttribute("data-width");
				document.getElementById("shoplocket_height").value = clickedListItem.getAttribute("data-height");
				if (clickedListItem.getAttribute("data-responsive")==1) {
					document.getElementById("shoplocket_dimensions").className = "";                    
				} else {
					document.getElementById("shoplocket_dimensions").className = "invisible";
				}

				// put the preview iframe in the proper div
				jQuery("#shoplocket_preview").empty().append(inputs[2].value).addClass('has_iframe');
				updateShopLocketPreviewSize();

			}

			function updateShopLocketPreviewSize() {
				width = validatePreviewSize(document.getElementById("shoplocket_width").value);
				height = validatePreviewSize(document.getElementById("shoplocket_height").value);
				setTimeout(function() {
					jQuery("#shoplocket_preview iframe").attr('height',height).attr('width',width);
				}, 300);
			}

			function validatePreviewSize(dim) {
				return dim.replace(/[^0-9]/g, '');
			}   
			
			function unclickShopLocketProduct(unorderedList) {

				if (!unorderedList) {
					unorderedList = document.getElementById("shoplocket_product_list");
				}

				// remove "selected" class from all li          
				listItems = unorderedList.getElementsByTagName("li");
				for (i=0;i<listItems.length;i++)
				{
					listItems[i].className = "";
				}    
				
				// clear selected item
				inputHidden = document.getElementById("shoplocket_shortcode");
				inputHidden.value = "";
				jQuery("#shoplocket_preview").empty().removeClass('has_iframe');                                           

			}       
			
			function closeShopLocketProduct() {
				unclickShopLocketProduct();
				tb_remove();
			}
			
			function getShopLocketProducts() {

				ajax_loading = document.getElementById("shoplocket-ajax-loading");
				ajax_refresh = document.getElementById("shoplocket-ajax-refresh");
				ajax_error   = document.getElementById("shoplocket-ajax-error");
				ajax_loading.className = "";
				ajax_refresh.className = "hidden";
				ajax_error.className = "hidden";

				var data = {
					action: 'shoplocket_get_products',
					nonce: '<?php echo wp_create_nonce('shoplocket_settings') ?>'
				};
				jQuery.post(ajaxurl, data, function(response) {
					if (response.code == 200) {
						jQuery('ul#shoplocket_product_list').empty().append(response.html)
						ajax_loading.className = "hidden";
						ajax_refresh.className = "";
						ajax_error.className = "hidden";
					} else {
						ajax_loading.className = "hidden";
						ajax_refresh.className = "hidden";
						ajax_error.className = "";
					}
				}, "json");             
			}
			<?php
			// we have a copy of products in wp_options, shoplocket_products_json
			// if that copy is over 24 hours old [i.e., if the transient
			// use_shoplocket_products_json has expired], then we want to add
			// this line of JavaScript to kick off the AJAX request that 
			// pulls products from ShopLocket automatically
			if (!get_transient("use_shoplocket_products_json")) { 
			?>
			jQuery(document).ready(function() {
				getShopLocketProducts();
			});
			<?php
			}
			?>
		</script>

		<div id="select_shoplocket_product" style="display:none;">
			<div class="wrapper">               
				<p class="howto">
					<?php echo __('Select a product', 'shoplocket'); ?>
					<a style="float: right;" href="#" onclick="getShopLocketProducts(); return false;">
						<img src="<?php echo plugin_dir_url( __FILE__ ); ?>/img/ajax-refresh.png" class="ajax-refresh" id="shoplocket-ajax-refresh">
						<img class="hidden" src="<?php echo plugin_dir_url( __FILE__ ); ?>/img/ajax-error.png" class="ajax-error-loading" id="shoplocket-ajax-error">
						<img class="hidden" src="images/wpspin_light.gif" class="ajax-loading" id="shoplocket-ajax-loading">
					</a>
					<span class="howto-support">Questions? <a href="mailto:help@shoplocket.com">ShopLocket is here to help 24/7</a></span>
				</p>
				<ul id="shoplocket_product_list">
					<?php echo self::shoplocket_get_html_for_product_list(get_option("shoplocket_products_json")); ?>
				</ul>
				
				<p>
					<input type="hidden" value="" id="shoplocket_shortcode" style="width: 100%;">
					<input type="hidden" value="" id="shoplocket_shortcode_visual" style="width: 100%;">
				</p>
				
				<div class="submitbox">
					<span style="float: left;">
						<a href="#" onclick="javascript:closeShopLocketProduct(); return false;"><?php echo __("Cancel", "shoplocket"); ?></a>
					</span>
					<span class="invisible" id="shoplocket_dimensions">
						<label>Width: <input id="shoplocket_width" type="text" size="5" onkeyup="javascript:updateShopLocketPreviewSize();"></label>
						<label>Height: <input id="shoplocket_height" type="text" size="5" onkeyup="javascript:updateShopLocketPreviewSize();"></label>
					</span>
					<span  style="float: right;">
						<input type="submit" class="button-primary" value="Insert Product" onclick="addShopLocketProduct();"/>
					</span>
				</div>
				<div id="shoplocket_preview">
				</div>
			</div>
		</div>

		<?php
	}
	
	public static function shoplocket_get_html_for_product_list($json) {
 
		$html = "";

		if ($json) {
			$products = json_decode(get_option("shoplocket_products_json"));
			if ($products) {
				foreach($products->products as $product) {
					$img = "";
					$html .= '<li data-responsive="' . $product->default_widget_style->responsive . '" data-height="'.  $product->default_widget_style->height . '" data-width="'.  $product->default_widget_style->width . '" onclick="javascript:clickShopLocketProduct(this)";>';
					$shortcode = '[shoplocket id=' .  $product->token . ' w={W} h={H}]';
					//$shortcode = '<div class="shoplocketProductDiv" title="' . $product->name . '"><span style="width: ' . $product->default_widget_style->width . 'px; height: ' . $product->default_widget_style->height . 'px;" class="shoplocketProduct mceItem" title="' . str_replace(array("[","]"),"",$shortcode) . '">&nbsp;</span></div>';
					//$shortcode = '<img src="' . '/wp-content/plugins/slproductitem/img/t.gif" class="slProductItem mceItem" title="' . str_replace(array("[","]"),"",$shortcode) . '" />';
					$shortcode_visual = '<img id="shoplocket' . $product->token . '" style="width: {W}px; height: {H}px;" src="'.plugins_url('/img/spacer.gif', __FILE__).'" class="shoplocketProductDiv mceItem" title="' . str_replace(array("[","]"),"",$shortcode) . '" />';
					$html .= '<input type="hidden" class="item-shortcode" value="' . esc_attr($shortcode) . '">';
					$html .= '<input type="hidden" class="item-shortcode-visual" value="' . esc_attr($shortcode_visual) . '">';
					$html .= '<input type="hidden" class="item-preview" value="' . esc_attr(do_shortcode('[shoplocket id=' .  $product->token . ' w=' .  $product->default_widget_style->width . ' h=' . $product->default_widget_style->height . ']') ) . '">';
					$html .= '<span class="item-image">';
					if (count($product->images) > 0) {
							$img = $product->images[0]->sizes->thumb;
							$html .= '<img height="53" src="' . $img . '">';
					}
					$html .= '</span>';
					$html .= '<span class="item-title"><h2>' . esc_html($product->name) . '</h2>';
					$html .= 'Published: ' . date('F d, Y',strtotime($product->published_at));
					$html .= '</span>';
					$html .= '</li>';
				}
			}
		}
		if ($html == "") {
			$html = '<li>No products found.</li>';                                  
		}
		
//      $html = "<pre>" . print_r($products->products[1]->images[0]->sizes->thumb,true) . "</pre>";
		
		return $html;
				
	}
	
	public static function shoplocket_get_options_for_product_list($json,$selected = "") {
 
		$html = "";

		if ($json) {
			$products = json_decode(get_option("shoplocket_products_json"));
			if ($products) {
				foreach($products->products as $product) {
					$html .= '<option ' . selected($selected,$product->token) . ' value="' . esc_attr($product->token) . '">';
					$html .= $product->name;
					$html .= '</option>';
				}
			}
		}
		return $html;
				
	}   

	public static function shoplocket_get_products() {  

		$nonce = "";
		if (isset($_POST["nonce"])) {
			$nonce = $_POST["nonce"];           
		}
		if (! wp_verify_nonce($nonce, 'shoplocket_settings') ) wp_die('You do not have permission to retrieve this information.');
		if (! current_user_can("edit_posts") && ! current_user_can("edit_pages") ) wp_die('You do not have permission to retrieve this information.');

		$shoplocket = get_option('shoplocket_settings');
		if (! isset($shoplocket["access_token"])) {$shoplocket["access_token"] = "";}       

		$from_shoplocket = wp_remote_request("https://www.shoplocket.com/api/v1/products.json?state=published&access_token=" . $shoplocket["access_token"]);
		if (isset($from_shoplocket["response"]["code"])) {
			$to_wordpress = array('code' => $from_shoplocket["response"]["code"]);
			if ($from_shoplocket["response"]["code"] == 200) {
				// $to_wordpress["body"] = $from_shoplocket["body"];
				$products = $from_shoplocket["body"];
				set_transient("use_shoplocket_products_json",time(), 60*60*24); // transient contains the time the data was pulled for reference
				update_option("shoplocket_products_json",$products);
				$to_wordpress["html"] = self::shoplocket_get_html_for_product_list($products);
			}
		} else {
			$to_wordpress = array('code' => 500);
		}
		echo json_encode($to_wordpress);
		die(); // this is required to return a proper result
	}   
	
	public static function settings_init() {
		register_setting( 'shoplocket_settings', 'shoplocket_settings', array("ShopLocket",'settings_validate_submission'));
	}              
	
	public static function settings_add_shoplocket_page() {
		add_options_page( 'Configure ShopLocket Integration', 'ShopLocket', 'manage_options', 'shoplocket_settings', array( 'ShopLocket', 'settings_render_shoplocket_page') );
	}

	public static function settings_render_shoplocket_page() {
		
		$shoplocket = get_option('shoplocket_settings');
		if (!isset($shoplocket["access_token"])) {$shoplocket["access_token"] = "";}
		if (!isset($shoplocket["app_id"])) {$shoplocket["app_id"] = "";}
		if (!isset($shoplocket["app_secret"])) {$shoplocket["app_secret"] = "";}
		
		$action = "";
		if (isset($_REQUEST['action'])) {
		  $action = sanitize_text_field($_REQUEST['action']);
		}
		
		echo '<div class="wrap">';
		echo '<div class="icon32" id="icon-options-general"><br /></div>';
		echo '<h2>' . __('Configure ShopLocket Integration','shoplocket') . '</h2>';
		
		if (isset($_REQUEST['message'])) {
			switch ( sanitize_text_field($_REQUEST['message']) ) {
				case 1:
					echo '<div id="setting-error-settings_updated" class="updated settings-error"><p><strong>Settings saved.</strong></p></div>';
					break;
				case 2:
					echo '<div id="setting-error-settings_updated" class="error settings-error"><p><strong>There was a problem connecting to ShopLocket. Please try again.</strong></p></div>';
					break;                  
			}      
		}

		// 1. if we're missing either an app id or an app secret,
		// then we display a Connect to ShopLocket button that
		// creates an app
		// 2. if we have an app id and an app secret, but no
		// access token, then we display a Finish Connection button
		// 3. if we have all three pieces, then we display a 
		// success message with a Start Over button

		$site_name = urlencode(get_bloginfo('name'));
		$redirect_uri = urlencode(site_url() . '/');

		if ($shoplocket['app_id'] == "" || $shoplocket['app_secret'] == "" || $shoplocket['access_token'] == "") {
		
			echo '<div class="shoplocket-connect"><div class="shoplocket-connect-inner">';

			echo '<p class="lead">' . __('Connect to your ShopLocket account to make it easy to display your ShopLocket products in your posts, sidebar, and other WordPress content.','shoplocket') . '</p>';

			echo '<p class="submit">';
			if ($shoplocket['app_id'] == "" || $shoplocket['app_secret'] == "") {
				// create app
				$create_app_class = 'button-primary';
				$create_app_label = __('Connect to ShopLocket','shoplocket');               
				$state = urlencode('shoplocket/connect/' . wp_create_nonce('shoplocket_settings'));
				echo '<a href="' . self::BASE_URL . self::OAUTH_NEW_INSTALL . '?state=' . $state . '&redirect_uri=' . $redirect_uri . '&site_name=' . $site_name . '" class="' . $create_app_class . '" >' . $create_app_label . '</a> ';
			} else {
				// get token
				$get_token_label = __('Connect to ShopLocket','shoplocket');
				$redirect_uri = urlencode(site_url() . '/');
				$state = urlencode('shoplocket/authorize/' . wp_create_nonce('shoplocket_settings'));
				echo '<a href="' . self::BASE_URL . self::OAUTH_AUTHORIZE . '?state=' . $state . '&client_id=' . $shoplocket["app_id"] . '&response_type=code&redirect_uri=' . $redirect_uri . '&site_name=' . $site_name . '" class="button-primary" >' . $get_token_label . '</a> ';
			}
			echo '</p>';

			echo '<p>';
			echo __('Click on the &ldquo;Connect to ShopLocket&rdquo; button. It will take you to ShopLocket. You may be required to login first. After that, you will be giving this WordPress site access to your ShopLocket information.','shoplocket');
			echo '</p>';

			echo '</div></div>';
			echo '<div class="shoplocket-signup">';
			echo '<img src="' . plugins_url('/img/shoplocket-signup-background.png', __FILE__) . '">';
			echo '<h3>' . __('Don&rsquo;t have a ShopLocket account?') . '</h3>';
			echo '<p>' . __('ShopLocket is the easiest way to sell any product online. <a href="https://www.shoplocket.com/signup" target="_blank">Create an account</a> and start selling your product now.') . '</p>';
			echo '</div>';
			echo '<div class="instructions-support"><p">' . __('Questions? Contact the ShopLocket team any time. We&rsquo;re here to help.') . '</p><p class="support-methods"> ' . __('1 (855) 885-7675 | <a href="mailto:help@shoplocket.com">help@shoplocket.com</a> | <a href="http://www.youtube.com/user/ShopLocket/videos" target=_blank">Video Tutorials</a>') . '</p></div>';

		} else {

			echo '<div class="shoplocket-success">';
			echo '<h3>' . __('You are now connected to your ShopLocket account!','shoplocket') . '</h3>';
			echo '<div class="shoplocket-success-instructions">';
			echo '<div class="instructions-editor"><p>' . __('A new Add Product button has been added to the posts and pages screen. Click that button to create a new product or to see a list of your exisitng ShopLocket products.') . '</p></div>';
			echo '<div class="instructions-widget"><p>' . __('A new kind of widget now exists that you can add to your sidebar. Add the widget and then select the product you&rsquo;d like to display.') . '</p></div>';
			echo '<div class="instructions-support"><p">' . __('Questions? Contact the ShopLocket team any time. We&rsquo;re here to help.') . '</p><p class="support-methods"> ' . __('1 (855) 885-7675 | <a href="mailto:help@shoplocket.com">help@shoplocket.com</a> | <a href="http://www.youtube.com/user/ShopLocket/videos" target=_blank">Video Tutorials</a>') . '</p></div>';
			echo '</div>';
		}

		if ($shoplocket['app_id'] != "" && $shoplocket['app_secret'] != "") {
			echo '<div class="shoplocket-start-over">';
			$create_app_class = 'button';
			$create_app_label = __('Start Over','shoplocket');              
			$state = urlencode('shoplocket/connect/' . wp_create_nonce('shoplocket_settings'));
			echo '<div class="shoplocket-start-over-button">';
			echo '<a href="' . self::BASE_URL . self::OAUTH_NEW_INSTALL . '?state=' . $state . '&redirect_uri=' . $redirect_uri . '&site_name=' . $site_name . '" class="' . $create_app_class . '" >' . $create_app_label . '</a> ';
			echo '</div>';
			echo '<div class="shoplocket-start-over-table">';
			echo '<table class="form-table">';
			echo '<tr valign="top"><th scope="row">App ID </th><td>' . esc_html($shoplocket['app_id']) . '</td></tr>';
			echo '<tr valign="top"><th scope="row">App Secret</th><td>' . esc_html($shoplocket['app_secret']) . '</td></tr>';    
			echo '<tr valign="top"><th scope="row">Token</th><td>' . esc_html($shoplocket['access_token']) . '</td></tr>';
			echo '</table>';
			echo '</div>';
			echo '</div>';
		}

		echo '</div>';

		?>
			<script>
				var data = {
					action: 'shoplocket_get_products',
					nonce: '<?php echo wp_create_nonce('shoplocket_settings') ?>'
				};
				jQuery.post(ajaxurl, data, function(response) {
					// no action required
				}, "json");
			</script>
		<?php

	}
	
	public static function settings_validate_submission( $input ) {
		
		$action = "";
		if (isset($_REQUEST['action'])) {
			$action = sanitize_text_field($_REQUEST['action']);
		}
		
		if ($action == "connect") {
			$newinput['app_id'] = sanitize_text_field($input['app_id']);
			$newinput['app_secret'] = sanitize_text_field($input['app_secret']);        
		} else {
			$newinput['token'] = sanitize_text_field($input['token']);
		}

		return $newinput;
	}
	
	static function shoplocket_oauth_endpoints() {

		if (!is_admin()) {
			
			if (!isset($_GET["state"])) { return; }
			$state = explode("/", sanitize_text_field($_GET["state"]));
			if ($state[0] != "shoplocket") { return; }
				
			// validate the nonce and the user permissions
			$nonce = $state[2];
			if (! wp_verify_nonce($nonce, 'shoplocket_settings') ) wp_die('You do not have permission to save this page.');

			// retrieve the existing shoplocket values from the database
			$shoplocket = get_option('shoplocket_settings');
			if (!isset($shoplocket["token"])) {$shoplocket["token"] = "";}
			if (!isset($shoplocket["app_id"])) {$shoplocket["app_id"] = "";}
			if (!isset($shoplocket["app_secret"])) {$shoplocket["app_secret"] = "";}            
		
			// if these POST variables are set, then we're at Step 1: Connect 
			if ($state[1] == "connect" && isset($_POST["app_id"]) && isset($_POST["app_secret"]) && !empty($_POST["app_id"]) && !empty($_POST["app_secret"])) {
			
				// retrieve the values from the POST submission
				$app_id = sanitize_text_field($_POST["app_id"]);
				$app_secret = sanitize_text_field($_POST["app_secret"]);
				
				// save the client settings
				$shoplocket["app_id"] = $app_id;
				$shoplocket["app_secret"] = $app_secret;
				$shoplocket["access_token"] = "";
				update_option('shoplocket_settings',$shoplocket);
				
				// redirect to get code
				$redirect_uri = urlencode(site_url() . '/');
				$state = urlencode('shoplocket/authorize/' . wp_create_nonce('shoplocket_settings'));
				
				$wp_redirect = self::BASE_URL . '/oauth/authorize?state=' . $state . '&client_id=' . $shoplocket["app_id"] . '&response_type=code&redirect_uri=' . $redirect_uri;
				if (!headers_sent()) {
					wp_redirect($wp_redirect);
					exit;
				} else {
					echo '<p>ShopLocket and WordPress are now connected. <a href="' . $wp_redirect . '">Continue to ShopLocket Authorization &rarr;</a></p>';
					exit;
				}           
			}
							
			// if these GET variables are set, then we're at Step 2: Authorize 
			if ($state[1] == "authorize" && isset($_GET["code"]) && !empty($_GET["code"])) {
				$args["body"]["code"] = sanitize_text_field($_GET["code"]);
				$args["body"]["client_id"] = $shoplocket["app_id"];
				$args["body"]["client_secret"] = $shoplocket["app_secret"];
				$args["body"]["grant_type"] = "authorization_code";
				$args["body"]["redirect_uri"] = site_url() . '/';
				$response = wp_remote_post('https://www.shoplocket.com/oauth/token',$args);
				if (isset($response["response"]["code"]) && $response["response"]["code"] == 200) { 
					$json = json_decode($response["body"]);
					$shoplocket["access_token"] = $json->access_token;
					update_option('shoplocket_settings',$shoplocket);
					
					$wp_redirect = admin_url('options-general.php?page=shoplocket_settings&message=1');
					if (!headers_sent()) {
						wp_redirect($wp_redirect);
						exit;
					} else {
						echo '<p><a href="' . $wp_redirect . '">Continue to WordPress Dashboard &rarr;</a></p>';
						exit;
					}
				}
				
			}

			// if these GET variables are set, then the user denied access
			if ($state[1] == "authorize" && isset($_REQUEST["error"])) {
				$wp_redirect = admin_url('options-general.php?page=shoplocket_settings&message=2');
				if (!headers_sent()) {
					wp_redirect($wp_redirect);
					exit;
				} else {
					echo '<p><a href="' . $wp_redirect . '">Continue to WordPress Dashboard &rarr;</a></p>';
					exit;
				}
			}
		}
	}

	static function is_shoplocket_url( $url ) {
		return preg_match( ShopLocket::URL_REGEX_PATTERN, $url );
	}

	static function get_product_url_from_id( $id ) {        
		return sprintf( self::PRODUCT_URL_SPRINTF_PATTERN, $id );
	}

	static function get_embed_url_from_id( $id ) {
		return sprintf( self::EMBED_URL_SPRINTF_PATTERN, $id );
	}

	static function get_id_from_url( $url ) {
		if ( preg_match( ShopLocket::URL_REGEX_PATTERN, $url, $matches ) )
			return sanitize_text_field( $matches[1] );
		return false;
	}

	static function normalize_args( $args ) {
		return shortcode_atts( array(
			'code' => '',
			'url' => '',
			'id' => '',
			'w' => self::DEFAULT_WIDTH,
			'h' => self::DEFAULT_HEIGHT,
			'title' => '',
		), $args );
	}
}
	

ShopLocket::load();
add_action('wp',array( 'ShopLocket', 'shoplocket_oauth_endpoints'));
register_activation_hook( __FILE__, array('ShopLocket', 'install') );
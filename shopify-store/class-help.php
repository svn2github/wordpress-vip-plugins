<?php

class Shopify_Help
{
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'help_page' ) );
	}

	public function help_page() {
		#$parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function
		add_submenu_page( 'options.php', "Shopify for WordPress Help", "Shopify for WordPress Help", "administrator", "shopify_help", array( $this, 'help_page_output' ) );
	}

	public function help_page_output() {
		$shopify = get_option( "shopify", array() );
		$app_url = "https://wordpress-shortcode-generator.shopifyapps.com/login?shop=" . $shopify["myshopify_domain"] . "&wordpress_admin_url=" . rawurlencode( admin_url() );
		include 'views/help.php';
	}

}

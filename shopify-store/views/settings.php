<div id="shopify_settings" class="wrap">
	<div class="header">
		<h2>Shopify for WordPress</h2>
		<div id="shopify_error" class="error"></div>
		<div id="flash" class="updated"></div>
		<a class="shopify_help_link" href="options.php?page=shopify_help">Need help?</a>
	</div>

	<div id="setup" style="<?php if( $setup === "true" ) echo "display: none;" ?>">
		<div id="signin" data-wordpressdomain="<?php echo esc_html( rawurlencode( admin_url() ) ) ?>">
			<img src="<?php echo esc_url( plugins_url( '../images/shopify-logo.png', __FILE__ ) ); ?>" alt="shopify logo">
			<h4>Enter your Shopify store address</h4>
			<p>
				<input class="large" type="text" name="shopify_url" id="shopify_url" placeholder="your-store-name" size="17" autofocus="autofocus" />
				<span class="large">.myshopify.com</span>
			</p>
			<p>
				<a href="#" onclick="Shopify.getShopifySettings()" class="button button-primary">Connect this account</a>
			</p>
		</div>
		<div class="signup">
			<p>
				Don't have a Shopify account?
				<?php global $current_user; get_currentuserinfo(); ?>
				<a href="<?php echo esc_url( "http://www.shopify.com/sell/wordpress?email=" . rawurlencode( $current_user->user_email ) . "&store_name=" . rawurlencode( get_bloginfo( 'name' ) ) ); ?>" target="blank">Create your store now</a>
			</p>
		</div>
	</div>

	<div id="finished_setup" style="<?php if( $setup === "false" ) echo "display:none" ?>">
		<div id="shopify_getting_started" class="updated" style="display: none;">
			<h3>Let's get started!</h3>
			<p>Copy the url for one of the products from <strong><a id="store_link" href="#" target="_blank">your storefront</a></strong>, like <code class="smaller_code">http://wordpress-demo.myshopify.com/products/demo-product/</code> and paste it into a <strong><a target="_blank" href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>">new blog post.</a></strong></p>
			<p><a target="_blank" href="https://www.youtube.com/watch?v=IE_j5mszpLw">Show me how</a></p>
			<p><a href="#" onclick="Shopify.hideTip()" class="button" >Dismiss</a></p>
		</div>

		<form id="shopify_settings_form" method="post" action="options.php">
			<?php settings_fields( $this->option_group ); ?>
			<?php do_settings_sections( $this->menu_slug );?>
			<?php submit_button( 'Save widget settings', 'primary', 'save_shopify_settings' ) ?>
		</form>

		<div id="widget_preview">
			<h3>Preview of widget</h3>
			<?php
				$sample_settings = array_merge( $settings, array(
					'product' => 'http://wordpress-demo.myshopify.com/products/test',
					'myshopify_domain' => 'wordpress-demo.myshopify.com',
				) );
				echo Shopify_Widget::generate( $sample_settings );
			?>
		</div>
	</div>
</div>

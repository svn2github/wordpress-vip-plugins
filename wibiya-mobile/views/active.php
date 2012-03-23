<?php
include_once 'functions_js.php';
?>
<form  method="post"  action="" name="ap_wibiya_mobile_activate">
<input type="hidden" id="ap_wibiya_mobile_deactivate" name="ap_wibiya_mobile_deactivate" value="1" />
<?php
if (function_exists('wp_nonce_field')) {
	wp_nonce_field('ap_wibiya_mobile_admin_form');
}
?>
<div class="wibiyaWrapper">
	<div class="leftPane">
		<h1>Wibiya’s Mobile Site Plugin</h1>
		<h2>Mobilize your site in a single click</h2>
	
		<div class="customize">
			<h3>Customize your new mobile site</h3>
			<p>Personalize it even more by choosing custom colors, images, additional pages, social features, and much more!</p>
			<p><a href="javascript:void(0);" class="regularButton" onclick="jQuery('.registerPanel').toggle('slide');jQuery(this).next().toggle();jQuery(this).toggleClass('disabled');">Customize</a> <span class="note"></span></p>
			<div class="registerPanel">
			<iframe name="register_iframe"   id="register_iframe" src="<?php echo __WIBIYA_REGISTER_PAGE__ . '&email=' . $GLOBALS['admin_email'] . '&domain=' . base64_encode(home_url()).'&callback='.base64_encode(admin_url()).'&rss_url=';?><?php echo bloginfo('rss2_url'); ?>" frameborder="0" scrolling="no" width="100%" height="400"></iframe>
			</div>
		</div>
		<div class="support">
			<h3>Questions? Comments?</h3>
			<p>We’d love to hear from you! Email us at <a href="mailto:vipsupport@wibiya.com">vipsupport@wibiya.com</a></p>
		</div>
		<div class="wibiyaInfo">
			Modular Patterns Ltd. &copy; 2012 All Rights Reserved<br/>
            <a href="http://wibiya.conduit.com/about" class="footerLinks" title="About Us" target="_blank">About Us</a> | <a href="http://wibiya.conduit.com/contact" class="footerLinks" target="_blank" title="Contact Us">Contact Us</a> | <a href="http://wibiya.conduit.com/tos" class="footerLinks" target="_blank">Terms Of Service</a> | <a href="http://wibiya.conduit.com/Privacy" class="footerLinks" target="_blank">Privacy Policy</a>
		</div>
	</div><!-- /left pane -->
	
	
	
	<div class="rightPane">
		<div class="previewSwitch">
			<a href="javascript:void(0);" class="apple" onclick="changePreview(this);" ></a>
		</div>
		<div class="sitePreview iphone">
			<div class="mobileContent"><iframe src="<?php echo __LIVEPREVIEW_URI__;?>" title="Mobile Website Live Preview" width="100%" height="100%" name="livepreview" scrolling="no" FRAMEBORDER="0" style="width:100%; border:0; height:100%; overflow:auto;"></iframe></div>
		</div>
		<p><a href="javascript:void(0);" class="regularButton" onclick="document.ap_wibiya_mobile_activate.submit();">Disable My Mobile Site</a></p>
	</div><!-- /right pane -->
</div><!-- /wrapper -->
<form>
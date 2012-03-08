<?php
include_once 'functions_js.php';
?>
<div class="wibiyaWrapper">
        <div class="pendingNotice">
                <h1>Wibiya’s Mobile Site Plugin</h1>
                <h2>Mobilize your site in a single click</h2>
                <h3>We’re hard at work!</h3>
                <p><img src="<?php echo __WIBIYA_MOBILE_URL__; ?>views/images/pending_animation.gif" alt="pending" /></p>
                <p>
                        <strong style="font-weight: bold;">
                                Thank you for mobilizing your site with Wibiya.<br />
                                Our team of experts is currently preparing your mobile site and will email you as soon as <br />it’s ready to go live.
                                Please sit tight – we’ll have it ready in about two business days.
                        </strong>
                        
                        <div class="notice">
                        	<strong style="font-weight: bold;">Notice</strong><br />Emails are currently being sent to <?php echo get_option('admin_email');
							if(!strstr(get_option('ap_wibiya_mobile_secondary_email'),get_option('admin_email'))){
								echo ' & '.get_option('ap_wibiya_mobile_secondary_email');
							}							
							?>.<br />If you are not getting anything you can add yours below.
                        	<p><input id="secondary_email" name="secondary_email" type="text"  class="text" placeholder="Email address" /><br /><input type="button" value="Add email" onclick="changeSecondaryEmail()"/></p>
                        </div>
                </p>
                <div class="support">
                        <h3>Questions? Comments?</h3>
                        <p>We’d love to hear from you! Email us at <a href="mailto:vipsupport@wibiya.com">vipsupport@wibiya.com</a></p>
                </div>
                <div class="wibiyaInfo">
                        Modular Patterns Ltd. &copy; 2012 All Rights Reserved<br/>
            <a href="http://wibiya.conduit.com/about" class="footerLinks" title="About Us" target="_blank">About Us</a> | <a href="http://wibiya.conduit.com/contact" class="footerLinks" target="_blank" title="Contact Us">Contact Us</a> | <a href="http://wibiya.conduit.com/tos" class="footerLinks" target="_blank">Terms Of Service</a> | <a href="http://wibiya.conduit.com/Privacy" class="footerLinks" target="_blank">Privacy Policy</a>
                </div>
        </div>
</div>
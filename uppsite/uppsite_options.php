<?php
/**
 * UppSite admin panel
 */

/**
 * admin_menu action
 * Hook for the menu
 */
function mysiteapp_admin_menu() {
	add_options_page('UppSite - Apping Your Wordpress', 'UppSite - Apping WP', 'manage_options', 'uppsite-settings', 'mysiteapp_option_page');
}

/**
 * UppSite API Key
 */
function mysiteapp_settings_uppsite_key() {
	$options = get_option('uppsite_options');
	$uppsite_key = ( ! empty( $options['uppsite_key'] ) ) ? $options['uppsite_key'] : '';
	echo "<input id='uppsite_options_key' name='uppsite_options[uppsite_key]' size='40' type='text' value='" . esc_attr( $uppsite_key ) . "' />";
}

/**
 * UppSite API Secret
 */
function mysiteapp_settings_uppsite_secret() {
	$options = get_option('uppsite_options');
	$uppsite_key = ( ! empty( $options['uppsite_secret'] ) ) ? $options['uppsite_secret'] : '';
	echo "<input id='uppsite_options_secret' name='uppsite_options[uppsite_secret]' size='40' type='text' value='" . esc_attr( $uppsite_key ) . "' />";
}

/**
 * Display sticky posts?
 */
function mysiteapp_settings_uppsite_sticky() {
	$options = get_option('uppsite_options');
	$items = array("No", "Yes");
	
	if (!isset($options['option_sticky'])) {
		$options['option_sticky'] = "No";
	}
	
	foreach ($items as $item) {
		echo "<label><input " . checked( $item, $options['option_sticky'], false ) ." value='" . esc_attr( $item ) . "' name='uppsite_options[option_sticky]' type='radio' /> $item</label><br />";
	}
}

/**
 * Homepage settings
 */
function mysiteapp_settings_uppsite_homepagelist() {
	$options = get_option('uppsite_options');
	$items = array(
		"No" => "No, show homepage according to my blog's settings.",
		"Yes" => "Yes, I want my apps to show the posts list on homepage.",
	);
	
	if (!isset($options['option_homepagelist'])) {
		$options['option_homepagelist'] = "Yes";
	}
	
	foreach ($items as $_key => $item) {
		echo "<label><input " . checked( $_key, $options['option_homepagelist'], false ) ." value='" . esc_attr( $_key ) . "' name='uppsite_options[option_homepagelist]' type='radio' /> $item</label><br />";
	}
}

/**
 * External comments plugins
 */
function mysiteapp_settings_uppsite_external_comments(){
	$options = get_option('uppsite_options');
	$items = array(
		'fbcomment' =>'Enable Facebook comments support (you will need to enter the Facebook-API information in <a href="http://www.uppsite.com/dashboard/" target="_blank">UppSite Dashboard</a> to enable writing permissions)',
		//'disqus'=>'Enable Disqus comments support',
	);
	
	foreach($items as $_key => $item) {
		$checked = isset($options[$_key]) ? ' checked="checked" ' : '' ;
		?><fieldset name="<?php echo $_key ?>">
		<label><input "<?php echo $checked ?>" value="1" name="uppsite_options[<?php echo esc_attr( $_key ); ?>]" type="checkbox" /> <?php echo $item ?></label>
		</fieldset><?php
	}
}

/**
 * Popup display (if mobile apps available)
 */
function mysiteapp_setting_display_alert() {
	$options = get_option('uppsite_options');
	$items = array("No", "Yes");
	
	if(!isset($options['option_popup'])){
		$options['option_popup'] = "Yes";
	}
	
	foreach($items as $item) {
		echo "<label><input " . checked( $item, $options['option_popup'], false ) . " value='" . esc_attr( $item ) . "' name='uppsite_options[option_popup]' type='radio' /> $item</label><br />";
	}
}

/**
 * Popup display timing
 */
function mysiteapp_setting_display_timing() {
	$options = get_option('uppsite_options');
	$items = array(
		"Everytime",
		"Every Hour",
		"Every Day",
		"Every Week",
		"Every Month",
	);
	
	if(!isset($options['option_popup_time'])){
		$options['option_popup_time'] = "Everytime";
	}
	
	foreach($items as $item) {
		echo "<label><input " . checked( $item, $options['option_popup_time'], false ) . " value='" . esc_attr( $item ) . "' name='uppsite_options[option_popup_time]' type='radio' /> $item</label><br />";
	}
}

/**
 * Hidden fields:
 * - Plugin version
 */
function mysiteapp_settings_uppsite_hidden() {
	echo "<input type='hidden' name='uppsite_options[uppsite_plugin_version]' value='" . esc_attr( MYSITEAPP_PLUGIN_VERSION ) . "' />";
}

/**
 * Callback after adding UppSite to the menu
 */
function mysiteapp_option_page() {
	global $sent;
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}

	if (isset($_POST['report']) && isset($_POST['get_options'])) {
		mysiteapp_get_report();
	}

	// Header
?>
<div>
<?php if (!mysiteapp_is_keys_set()): ?>
	<h2>Almost done...</h2>
	<p>All you need to do now is register FREE to <a href="http://www.uppsite.com" target='_blank'>UppSite.com</a> with this website's URL and get your own API key and secret, which will allow your users to get Push notifications.</p>
	<p>Register now FREE:
	<a href="http://www.uppsite.com" target='_blank'>http://www.uppsite.com</a></p>
	
	<h2>I have registered...</h2>
	<p>Good! Now enter the API key and secret from your account's Dashboard here:</p>
<?php else: ?>
	<h2>UppSite Plugin</h2>
	<p>Have questions? Need help? Use <a href="http://www.uppsite.com/support-home/" target="_blank">UppSite Support</a>.</p>
<?php endif;?>
<form action="options.php" method="post">
	<?php settings_fields('uppsite_options'); ?>
	<?php do_settings_sections(__FILE__); ?>
	<p class="submit"><input type="submit" name="Submit" value="Save Changes" class="button-primary" /></p>
</form>
<p>&nbsp;</p><hr />
<table style="width:100%; border:0;" cellspacing="5">
<tr>
	<td style="vertical-align:top;"><form method="post"><input type="submit" name="Submit" value="Download report" class="button-secondary"/>
		<input type="hidden" name="report" value="yes"><input type="hidden" name="withoptions" value="yes"><input type="hidden" name="get_options" value="get_options">
		</form></td>
	<td style="vertical-align:top;"> Clicking on this button will automatically download a "txt" file <br>which you can attach to a message which describes your problem to our <a href="mailto:support@uppsite.com">Support team</a>
	</td>
</tr>
</table>
<p></p>

</div>
<?php 
}  // mysiteapp_option_page

/**
 * Validates the input
 * @param mixed $input Input to validate
 * @return mixed	Validated input
 */
function mysiteapp_options_validate($input){
	return wp_kses_post( $input );
}

/**
 * Text for "UppSite API Key & Secret"
 */
function mysiteapp_options_section_api_text(){
	echo "<p>The API Key &amp; Secret are used by your applications for Push Notifications, which usually increases the user engagement with your website.</p>
		<p>You can get the API Key &amp; Secret from <a href='http://www.uppsite.com/dashboard/' target='_blank'>UppSite Dashboard</a> (be sure you input the API key+secret assigned to this website).</p>";
}
/**
 * Text for "Popup notify"
 */
function mysiteapp_options_section_notify_text(){
	echo "<p>Visitors using mobile browsers (such as Safari for iPhone, Chrome for Android, etc), can be notified and redirected to download your native apps once they are in the stores (handled by UppSite). Choose if and when to inform them of your native apps.</p>";
}

/**
 * No text
 */
function mysiteapp_options_section_no_text(){
}

/**
 * admin_init action
 * Register actions and settings for the admin panel
 */
function mysiteapp_admin_init_options(){

	register_setting('uppsite_options', 'uppsite_options', 'mysiteapp_options_validate' );
	// API Settings
	add_settings_section('main_section', 'UppSite APIs', 'mysiteapp_options_section_api_text', __FILE__);
	add_settings_field('uppsite_options_key', 'API Key', 'mysiteapp_settings_uppsite_key', __FILE__, 'main_section');
	add_settings_field('uppsite_options_secret', 'API Secret', 'mysiteapp_settings_uppsite_secret', __FILE__, 'main_section');
	
	// End-users surfing to the website
	add_settings_section('plugin_alert', 'Notify Users', 'mysiteapp_options_section_notify_text', __FILE__);
	add_settings_field('uppsite_options_popup', 'Display', 'mysiteapp_setting_display_alert', __FILE__, 'plugin_alert');
	
	// Other options
	add_settings_section('other_section', 'Other Options', 'mysiteapp_options_section_no_text', __FILE__);
	add_settings_field('uppsite_options_sticky', 'Disable sticky in apps', 'mysiteapp_settings_uppsite_sticky', __FILE__, 'other_section');
	add_settings_field('uppsite_options_homepagelist', 'Ignore blog homepage settings', 'mysiteapp_settings_uppsite_homepagelist', __FILE__, 'other_section');

	// Plugin version
	add_settings_field('uppsite_options_hidden', NULL, 'mysiteapp_settings_uppsite_hidden', __FILE__, 'other_section');
	// Comments
	add_settings_field('uppsite_options_comments', 'Comments', 'mysiteapp_settings_uppsite_external_comments', __FILE__, 'other_section');
	
	
	$options = get_option('uppsite_options');

	if ($options['option_popup'] == "Yes"){
		add_settings_field('uppsite_options_pop_time', 'Display Alert Every...', 'mysiteapp_setting_display_timing', __FILE__, 'plugin_alert');
	}
	
}

/**
 * Tells whether the UppSite API Key & Secret were set.
 * @return boolean 	Set or not
 */
function mysiteapp_is_keys_set() {
	$options = get_option('uppsite_options');
	
	if ( empty( $options['uppsite_key'] )  || empty( $options['uppsite_secret'] ) ) {
		return false;
	}
	return true;
}

/**
 * Notification for admins who didn't enter UppSite's API key & secret
 */
function mysiteapp_activation_notice(){
	if (function_exists('admin_url')){
		echo '<div class="error fade"><p><strong>NOTICE</strong>: You need to configure the UppSite plugin first, in order to use it. Please go to the <a href="' . admin_url( 'options-general.php?page=uppsite-settings' ) . '">settings page</a></p></div>';
	}
}

/**
 * Gets a report format to send via email to UppSite
 * in case there is some plugin which messes the normal activity
 * of our plugin.
 * 
 * Opens a secret iframe which downloads the report.
 */
function mysiteapp_get_report(){
	if ( ! current_user_can( 'manage_options' ) )
		return;

	$options = get_alloptions();
	$response = wp_remote_post(
		MYSITEAPP_APP_DOWNLOAD_SETTINGS,
		array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(),
			'body' => array( 'options' => $options),
			'cookies' => array(),
	    )
   );
    
    if (is_wp_error($response)) {
    	wp_die("Unable to complete the request.");
    }
    $url = $response['body'];
	echo "<iframe src=\"".$url."\" id=\"frame1\" style=\"display:none\"></iframe>";
}


if (!mysiteapp_is_keys_set()) {
	// Notification to set API key & secret
	add_action( 'admin_notices', 'mysiteapp_activation_notice');
}

/** Hooking the admin init **/
add_action('admin_init', 'mysiteapp_admin_init_options');
/** Hooking the menu **/
add_action('admin_menu', 'mysiteapp_admin_menu');




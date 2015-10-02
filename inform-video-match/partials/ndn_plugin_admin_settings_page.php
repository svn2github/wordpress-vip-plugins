<?php

/**
 * Provide a admin area view for the plugin
 */

?>

<div class="wrap">

	<h2>
    <span style="float:left;"><?php esc_html_e( 'Inform Video Match Settings', 'ndn_admin' ); ?></span>
    <div style="float:right;"><img src="<?php echo esc_url( NDN_PLUGIN_DIR . '/assets/informLogo_116x50.png' ) ?>" /></div>
    <div style="clear:both"></div>
  </h2>

	<div id="poststuff">

		<div id="post-body" class="metabox-holder" style="min-width:450px;max-width: 60%;">

			<!-- main content -->
			<div id="post-body-content">

				<div class="meta-box-sortables ui-sortable">

						<div class="inside">
							<?php if (!self::$has_token): ?>
							<div class="postbox">

								<h3><span><?php esc_html_e( 'Login with your Inform Control Room credentials. If you don\'t have an Inform Control Room login, contact your account manager ', 'ndn_admin' ); ?><a href="mailto:wordpress@inform.com" class="ndn-email-help" analytics-category="WPSettings" analytics-label="SettingsHelp"><?php esc_html_e('or click here.', 'ndn_admin') ?></a></span></h3>

								<fieldset style="margin:10px 0;padding:0 12px;">
									<select class="ndn-login-form-type" name="login-type">
										<option value="1" selected="selected">First Time Login</option>
										<option value="2">Returning User</option>
									</select><br />
								</fieldset>

								<form id="ndn-plugin-first-time-login" name="ndn-plugin-login-form" action="" analytics-category="WPSettings" analytics-label="SettingsLogin" method="post" novalidate>

									<fieldset style="margin:10px 0;padding:0 12px;">
										<label for="username">Username</label><br />
										<input style="min-width:400px;" id="<?php echo esc_attr( self::$login_form_options['ndn_username'] ) ?>" type="text" value="" class="regular-text" name="username" placeholder="required" required /><br />
									</fieldset>

									<fieldset style="margin:10px 0;padding:0 12px;">
										<label for="password">Password</label><br />
										<input style="min-width:400px;" id="<?php echo esc_attr( self::$login_form_options['ndn_password'] ) ?>" type="password" value="" class="regular-text" name="password" placeholder="required" required /><br />
									</fieldset>

									<fieldset style="margin:10px 0;padding:0 12px;">
										<label for="company_name">Company Name</label><br />
										<input style="min-width:400px;" id="<?php echo esc_attr( self::$login_form_options['ndn_company_name'] ) ?>" type="text" value="" class="regular-text" name="company_name" placeholder="required" required /><br />
									</fieldset>

									<fieldset style="margin:10px 0;padding:0 12px;">
										<label for="contact_name">Contact Name</label><br />
										<input style="min-width:400px;" id="<?php echo esc_attr( self::$login_form_options['ndn_contact_name'] ) ?>" type="text" value="" class="regular-text" name="contact_name" placeholder="required" required /><br />
									</fieldset>

									<fieldset style="margin:10px 0;padding:0 12px;">
										<label for="contact_email">Contact Email</label><br />
										<input style="min-width:400px;" id="<?php echo esc_attr( self::$login_form_options['ndn_contact_email'] ) ?>" type="text" value="" class="regular-text" name="contact_email" placeholder="required" required /><br />
									</fieldset>

									<input type="hidden" name="login-submission" value="1" />
									<input class="button-primary" type="submit" name="submit" style="margin: 10px 10px 10px 12px;" value="<?php echo esc_attr( 'Login' ); ?>" />
								</form>

								<form id="ndn-plugin-returning-login" style="display:none;" name="ndn-plugin-login-form" action="" analytics-category="WPSettings" analytics-label="SettingsLogin" method="post" novalidate>

									<fieldset style="margin:10px 0;padding:0 12px;">
										<label for="username">Username</label><br />
										<input style="min-width:400px;" id="<?php echo esc_attr( self::$login_form_options['ndn_username'] ) ?>" type="text" value="" class="regular-text" name="username" /><br />
									</fieldset>

									<fieldset style="margin:10px 0;padding:0 12px;">
										<label for="password">Password</label><br />
										<input style="min-width:400px;" id="<?php echo esc_attr( self::$login_form_options['ndn_password'] ) ?>" type="password" value="" class="regular-text" name="password" /><br />
									</fieldset>

									<input type="hidden" name="returning-login-submission" value="1" />
									<input class="button-primary" type="submit" name="submit" style="margin: 10px 10px 10px 12px;" value="<?php echo esc_attr( 'Login' ); ?>" />
								</form>
							</div>
							<?php else :

								// Grab Default Settings
								$ndn_settings = array (
									'ndn_default_tracking_group' => get_option('ndn_default_tracking_group'),
									'ndn_default_div_class' => get_option('ndn_default_div_class'),
									'ndn_default_site_section' => get_option('ndn_default_site_section') ? get_option('ndn_default_site_section') : 'inform_wordpress_plugin',
									'ndn_default_responsive' => get_option('ndn_default_responsive') == '1',
									'ndn_default_width' => get_option('ndn_default_width') ? get_option('ndn_default_width') : '425',
									'ndn_default_video_position' => get_option('ndn_default_video_position'),
									'ndn_default_start_behavior' => get_option('ndn_default_start_behavior'),
									'ndn_default_featured_image' => get_option('ndn_default_featured_image') == '1'
								);
							?>

							<form name="ndn-plugin-default-settings-form" method="post" action="" analytics-category="WPSettings" analytics-label="SettingsPageSave" novalidate>

								<div class="postbox">

									<h3><span><?php esc_html_e( 'Video', 'ndn_admin' ); ?></span></h3>

									<fieldset style="margin:10px 0;padding:0 12px;">
										<label for="<?php echo esc_attr( self::$settings_form_options['ndn_default_tracking_group'] ) ?>">Default Tracking Group</label><br />
										<input name="<?php echo esc_attr( self::$settings_form_options['ndn_default_tracking_group'] ) ?>" type="text" value="<?php echo esc_attr($ndn_settings['ndn_default_tracking_group']) ?>" class="regular-text" placeholder="required" /><br />
										<div class="invalid-input" style="display:none;color:red;">Invalid Character. Digits only.</div>
									</fieldset>

									<fieldset style="margin:10px 0;padding:0 12px;">
										<label for="<?php echo esc_attr( self::$settings_form_options['ndn_default_div_class'] ) ?>">Default DIV Class</label><br />
										<input name="<?php echo esc_attr( self::$settings_form_options['ndn_default_div_class'] ) ?>" type="text" value="<?php echo esc_attr($ndn_settings['ndn_default_div_class']) ?>" class="regular-text" /><br />
									</fieldset>

									<fieldset style="margin:10px 0;padding:0 12px;">
										<label for="<?php echo esc_attr( self::$settings_form_options['ndn_default_site_section'] ) ?>">Default Site Section</label><br />
										<input name="<?php echo esc_attr( self::$settings_form_options['ndn_default_site_section'] ) ?>" type="text" value="<?php echo esc_attr($ndn_settings['ndn_default_site_section']) ?>" class="regular-text" placeholder="required" /><br />
									</fieldset>
								</div>
								<div class="postbox">

								<h3><span><?php esc_html_e( 'Display', 'ndn_admin' ); ?></span></h3>
									<fieldset style="margin:10px 0;padding:0 12px;">
										<legend class="screen-reader-text"><span>Responsive</span></legend>
										<label for="<?php echo esc_attr( self::$settings_form_options['ndn_default_responsive'] ) ?>">
											<input class="ndn-responsive-checkbox" type="checkbox" name="<?php echo esc_attr( self::$settings_form_options['ndn_default_responsive'] ) ?>" value="1" <?php echo ( $ndn_settings['ndn_default_responsive'] ? esc_attr('checked') : '' ) ?> />
											<input class="ndn-responsive-checkbox-disabled" type='hidden' name='<?php echo esc_attr( self::$settings_form_options['ndn_default_responsive'] ) ?>' value="not_checked" />
											<span><?php esc_html_e( 'Responsive', 'ndn_admin' ); ?></span>
										</label>
									</fieldset>

									<fieldset style="margin:10px 0;padding:0 12px;">
										<label for="<?php echo esc_attr( self::$settings_form_options['ndn_default_width'] ) ?>">Default Width</label><br />
										<input name="<?php echo esc_attr( self::$settings_form_options['ndn_default_width'] ) ?>" type="text" value="<?php echo esc_attr( $ndn_settings['ndn_default_width'] ) ?>" class="regular-text" /><br />
									  <input class="ndn-default-width-disabled" name="<?php echo esc_attr( self::$settings_form_options['ndn_default_width'] ) ?>" type="hidden" value="0" />
										<div class="invalid-input" style="display:none;color:red;">Invalid Character. Digits only.</div>
									</fieldset>

									<fieldset style="margin:10px 0;padding:0 12px;display:none;">
										<label for="<?php echo esc_attr( self::$settings_form_options['ndn_default_video_position'] ) ?>">Default Video Position</label><br />
										<select name="<?php echo esc_attr( self::$settings_form_options['ndn_default_video_position'] ) ?>">
											<?php foreach(self::$video_position_options as $selection) { ?>
												<option value="<?php echo esc_attr($selection['value']) ?>" <?php echo ( $ndn_settings['ndn_default_video_position'] == $selection['value'] ? esc_attr('selected="selected"') : '' ) ?>><?php echo esc_html( $selection['name'] ) ?></option>
											<?php } ?> <!-- End FOR -->
										</select><br />
									</fieldset>

									<fieldset style="margin:10px 0;padding:0 12px;">
										<label for="<?php echo esc_attr( self::$settings_form_options['ndn_default_start_behavior'] ) ?>">Start Behavior</label><br />
										<select name="<?php echo esc_attr( self::$settings_form_options['ndn_default_start_behavior'] ) ?>">
											<?php foreach( self::$start_behavior_options as $selection ) { ?>
												<option value="<?php echo esc_attr($selection['value']) ?>" <?php echo ( $ndn_settings['ndn_default_start_behavior'] == $selection['value'] ? esc_attr('selected="selected"') : '' ) ?>><?php echo esc_html( $selection['name'] ) ?></option>
											<?php } ?> <!-- End FOR -->
										</select><br />
									</fieldset>

									<fieldset style="margin:10px 0;padding:0 12px;">
										<legend class="screen-reader-text"><span>Set Featured Image</span></legend>
										<label for="<?php echo esc_attr( self::$settings_form_options['ndn_default_featured_image'] ) ?>">
											<input class="ndn-featured-image-checkbox" type="checkbox" name="<?php echo esc_attr( self::$settings_form_options['ndn_default_featured_image'] ) ?>" value="1" <?php echo ( $ndn_settings['ndn_default_featured_image'] ? esc_attr('checked') : '' ) ?> />
											<input class="ndn-featured-image-checkbox-disabled" type='hidden' name='<?php echo esc_attr( self::$settings_form_options['ndn_default_featured_image'] ) ?>' value="not_checked" />
											<span><?php esc_html_e( 'Set Featured Image', 'ndn_admin' ); ?></span>
										</label>
									</fieldset>

									<input type="hidden" name="ndn-save-settings" value="1" />

								</div>

								<input class="submit-settings-form button-primary" type="submit" name="submit" style="margin: 10px 0 0 0;" value="<?php echo esc_attr( 'Save Settings' ) ?>" />
							</form>
							<?php endif; ?>
						</div>
						<!-- .inside -->

				</div>
				<!-- .meta-box-sortables .ui-sortable -->

			</div>
			<!-- post-body-content -->

		</div>
		<!-- #post-body .metabox-holder .columns-2 -->

		<br class="clear">
	</div>
	<!-- #poststuff -->

</div> <!-- .wrap -->

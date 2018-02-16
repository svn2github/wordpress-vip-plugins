<?php
// Check to see if everything is set up correctly.
$verify_setup = sailthru_verify_setup();
?>

<?php if ( isset( $verify_setup['error'] ) && ! empty( $verify_setup['error'] ) ) : ?>
	<?php if ( 'template not configured' === $verify_setup['errormessage'] ) : ?>
		<div class="error settings-error">
		<p>The template you have selected is not configured correctly. Please check the <a href="http://docs.sailthru.com/developers/client-libraries/wordpress-plugin">documentation<a/> for instructions.</p>
		</div>
	<?php elseif ( 'select a template' === $verify_setup['errormessage'] ) : ?>
		<div class="error settings-error">
		<p><a href="?page=settings_configuration_page#sailthru_setup_email_template">Select a Sailthru template</a> to use for all WordPress emails.</p>
		</div>
	<?php else : ?>
		<div class="error settings-error">
		<p>Sailthru is not correctly configured, please check your API key and template settings.</p>
		</div>
	<?php endif; ?>
<?php endif; ?>
	<div id="sailthru-template-choices">
			<div class="meta-box-sortables">
				<div id="sailthru-choose-template" class="postbox">
					<div class="inside">
					<?php
						settings_fields( 'sailthru_setup_options' );
						do_settings_sections( 'sailthru_setup_options' );
					?>
					</div>

				</div>
			</div>
		</div>
	<div class="clear"></div>

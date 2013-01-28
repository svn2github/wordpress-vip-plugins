<?php

/**
 * This file perform the saving and dispalying of TinyPass settings on the 
 * TinyPass->Settings menu
 */
function tinypass_site_settings() {

	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	$storage = new TPStorage();

	if (isset($_POST['_Submit'])) {

		if (!wp_verify_nonce($_REQUEST['_wpnonce'])) {
			wp_die(__('This action is not allowed'));
			check_admin_referer();
		}

		$ss = $storage->getSiteSettings();
		$ss->mergeValues($_POST['tinypass']);
		$storage->saveSiteSettings($ss);
	}

	$ss = $storage->getSiteSettings();
	?>
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<?php if (!empty($_POST['_Submit'])) : ?>
			<div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
		<?php endif; ?>

		<div class="">
			<form action="" method="post" id="tinypass-conf">

				<?php wp_nonce_field(); ?>
				<?php __tinypass_section_head_alt(__("General settings")) ?>
				<br>

				<div class="tp-section">
					<div class="info">
						<div class="heading">Environment</div>
						<div class="desc"></div>
					</div>
					<div class="body">
						<div class="postbox">
							<h3><?php _e('Environment') ?> </h3>
							<div class="inside">
								<input type="radio" name="tinypass[env]" value="0" <?php echo checked($ss->isSand(), true) ?>><label><?php _e('Sandbox - for testing only'); ?></label><br>
								<input type="radio" name="tinypass[env]" value="1" <?php echo checked($ss->isProd(), true) ?>><label><?php _e('Live - for live payments'); ?></label>
							</div>
						</div>
					</div>
				</div>


				<div class="clear"></div>

				<p>
					<input type="submit" name="_Submit" id="publish" value="Save Changes" tabindex="4" class="button-primary" />
				</p>

			</form>
		</div>
	</div>
<?php } ?>
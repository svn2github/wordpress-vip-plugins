<?php

/**
 * This file is responsbile for confiuring, displaying, and saving
 * individual paywall settings.
 */
function tinypass_mode_settings() {

	$storage = new TPStorage();
	$errors = array();

	$ps = null;

	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}

	if (isset($_POST['_Submit'])) {

		if (!wp_verify_nonce($_REQUEST['_wpnonce'])) {
			wp_die(__('This action is not allowed'));
			check_admin_referer();
		}

		//Save the paywall
		$ss = $storage->getSiteSettings();
		$ps = $ss->validatePaySettings($_POST['tinypass'], $errors);
		if (count($errors) == 0) {
			$storage->savePaywallSettings($ss, $ps);
			$_REQUEST['msg'] = 'Your settings have been saved!';
		}
	}

	$edit = false;
	$ps = $storage->getPaywall('pw_config', true);
	?>

	<div id="poststuff">
		<div class="tp-settings">

			<?php if (!count($errors)): ?>
				<?php if (!empty($_REQUEST['msg'])) : ?>
					<div class="updated fade"><p><strong><?php echo esc_html($_REQUEST['msg']); ?></strong></p></div>
				<?php endif; ?>
			<?php else: ?>
				<div id="tp-error" class="error fade"></div>
			<?php endif; ?>


			<div class="tp-section">

				<div id="tp_modes" style="display:none">
					<div id="tp_mode4" class="choice" mode="<?php echo TPPaySettings::MODE_METERED_LIGHT ?>" <?php checked(true, true) ?>><?php echo TPPaySettings::MODE_METERED_LIGHT_NAME ?></div>
					<div class="clear"></div>
				</div>

				<div class="clear"></div>
			</div>


			<div id="tp_mode4_panel" class="tp_mode_panel">
				<form action="" method="post" autocomplete="off">
					<?php wp_nonce_field(); ?>
					<input class="tp_mode" name="tinypass[mode]" type="hidden">
					<div style="float:right">
						<input type="hidden" readonly="true" name="tinypass[resource_id]" value="<?php echo esc_attr($ps->getResourceId()); ?>">
						<input type="hidden" readonly="true" name="tinypass[resource_name]" value="na">
						<input type="hidden" readonly="true" name="tinypass[en]" value="<?php echo esc_attr($ps->getEnabled()); ?>">
					</div>
					<?php $num = 0; ?>
					<?php __tinypass_section_head($ps, ++$num, __("Setup your paywall")) ?>
					<?php __tinypass_mlite_display($ps) ?>
					<?php __tinypass_section_head($ps, ++$num, __("Select your premium content")) ?>
					<?php __tinypass_tag_display($ps) ?>
					<?php __tinypass_section_head($ps, ++$num, __("Miscellaneous")) ?>
					<?php __tinypass_misc_display($ps) ?>
					<?php __tinypass_save_buttons($ps, $edit) ?>
				</form>
			</div>


		</div>
	</div>

	<script>
		var scope = '';
		jQuery(function(){
			var $ = jQuery;

			$('#tp_mode_details .choice').click(function(){
				var index = $(this).index();
				var elem = $('#tp_modes .choice').get(index);
				$(elem).trigger('click');
			})

			$('#tp_modes .choice').click(function(){
				$('#tp_mode_details .choice').removeClass("choice-selected");
				$('#tp_modes .choice').removeClass("choice-selected");
				$('#tp_modes .choice').removeAttr("checked");

				$(this).addClass("choice-selected");
				$(this).attr("checked", "checked");
								  	                                                                                        													
				var elem = $(".choice[checked=checked]");
				var id = elem.attr("id");

				scope = '#' + id + '_panel';

				$(".tp_mode").val(elem.attr('mode'));

				$('#tp_mode_details #' + id + "_details").addClass("choice-selected");

				tinypass.fullHide('.tp_mode_panel');
				tinypass.fullShow(scope);

			});
			$("#tp_modes .choice[checked=checked]").trigger('click');

			$(".tag-holder").click(function(event){
				if($(event.target).hasClass("remove"))
					$(event.target).parent().remove();
			})

			function addTag(){
				var tag = $(".premium_tags", scope).val();
				if(tag == "")
					return;

				$(".tag-holder", scope).append("<div class='tag'><div class='text'>" + tag + "</div><div class='remove'></div>" 
					+ "<input type='hidden' name='tinypass[tags][]' value='" + tag  + "'>" 
					+ "</div>"
			);
				$(".premium_tags", scope).val("");
				$(".premium_tags", scope).focus();
			}
			$(".tag-entry .add_tag").click(function(){
				addTag();
			});

			$(".tag-entry .premium_tags").keypress(function(event){
				if(event.which == 13){
					addTag();
					event.stopPropagation();
					return false;
				}
			});
								  	                                                                                        									
			$('.premium_tags').suggest("admin-ajax.php?action=ajax-tag-search&tax=post_tag",{minchars:2,multiple:false,multipleSep:""})

	<?php
	if (isset($_REQUEST['rid'])) {
		echo "$('#tp-hide-paywalls').trigger('click');";
	} else {
		echo "$('#tp-show-paywalls').trigger('click');";
	}
	?>
		});
	</script>

	<?php if (count($errors)): ?>
		<?php foreach ($errors as $key => $value): ?>
			<script>tinypass.doError("<?php echo esc_js($key); ?>", "<?php echo esc_js($value); ?>");</script>
		<?php endforeach; ?>
	<?php endif; ?>


<?php } ?>
<?php $checked = 'checked="checked"'; ?>
<div class="wrap">
<img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'images/logo-dark.png' ); ?>" alt="Logo" />
	<h2>GrabPress: Edit the player template for video posts</h2>
	<p>Video that fits your site design</p>
	<form action="" method="POST" class="template-form">
		<input type="hidden" name="action" value="<?php echo esc_attr( $form['action'] ); ?>" />
		<input type="hidden" id="player_width_orig" name="width_orig" value="<?php echo esc_attr( $form['width'] ); ?>" />
		<fieldset>
			<legend>Player</legend>
			<table class="form-table grabpress-table template-table">
				<tbody>
					<tr valign="bottom">
						<th scope="row">Ratio <span class="asterisk">*</span></th>
						<td>
							<input type="radio" id ="ratiowide" name="ratio" value="widescreen" <?php if ( !isset( $form['widescreen_selected'] ) ) echo $checked; echo $form['widescreen_selected'] ? $checked : ''; ?> /> Widescreen 16:9
							<input type="radio" id ="ratiostand" name="ratio" value="standard" <?php echo $form['standard_selected'] ? $checked : ''; ?> /> Standard 4:3
						</td>
					</tr>
					<tr valign="bottom">
						<th scope="row">Width</th>
						<td>
							<input type="text" id="player_width" name="width" value="<?php echo esc_attr( $form['width'] ); ?>" />
						</td>
					</tr>
					<tr valign="bottom">
						<th scope="row">Height</th>
						<td>
							<span class="height"><?php echo esc_html( isset( $form['height'] ) ? $form['height'] : '270' ); ?></span>
						</td>
					</tr>
					<tr valign="bottom">
						<td class="button-tip" colspan="2">
							<input type="submit" class="button-primary" value="Save" id="btn-create-feed" />
						</td>
					</tr>
				</tbody>
			</table>
		</fieldset>
	</form>
	<div class="template-preview" style="width:<?php echo esc_attr( $form['width'] ); ?>px;height:<?php echo esc_attr( $form['height'] ); ?>px;">
		<div class="widescreen" <?php if ( ! $form['widescreen_selected'] ) { ?>style="display:none;" <?php } ?> ></div>
		<div class="standard" <?php if ( ! $form['standard_selected'] ) { ?>style="display:none;" <?php } ?> ></div>
	</div>
</div>
<div id="dialog_300" title="Warning !"><p></p></div>
<div id="dialog_640" title="Warning !"><p></p></div>
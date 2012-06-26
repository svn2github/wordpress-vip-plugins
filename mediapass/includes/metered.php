<div class="wrap">
	<h2 class="header"><img src="<?php echo plugins_url('/images/logo-icon.png', dirname(__FILE__)) ?>" class="mp-icon" /><span>Metered Settings</span></h2>
	
	<p class="subtitle" style=" padding-left:0;width: 800px;">
		MediaPass offers a Metered feature for Subscriptions. Metered models are used by some online publications to allow a reader to access a set number of pages before they are required to sign-up for a Premium Subscription.		
	<br/><br/>
		Enter the number of page views a user can access before they are prompted to sign up for your site's Premium Subscription.  This subscription meter is only applied to the content with MediaPass Subscriptions enabled.
	</p>

	<form action="" method="post" accept-charset="utf-8">
		<?php MediaPass_Plugin::nonce_for(MediaPass_Plugin::NONCE_METERED) ?>
		<table border="0" class="form-table">
			<tr>
				<th><label for="Status">Status</label></th>
				<td>
					<select name="Status" id="Status">
						<option value="On"<?php echo ($data['Msg']['Status'] == 'On') ? ' selected="selected"' : null ?>>On</option>
						<option value="Off"<?php echo ($data['Msg']['Status'] == 'Off') ? ' selected="selected"' : null ?>>Off</option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="Count">Count</label></th>
				<td>
					<input type="text" name="Count" value="<?php echo esc_attr( $data['Msg']['Count'] ); ?>" id="Count">
				</td>
			</tr>
			<tr>
				<td colspan="2"><input type="submit" class="button-primary" value="Update"></td>
			</tr>
		</table>
	</form>
</div>
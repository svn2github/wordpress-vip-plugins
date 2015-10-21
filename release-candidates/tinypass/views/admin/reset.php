<?php
/**
 * Template for plugin settings resetting page
 * Rendered by @see WPTinypassAdmin
 */
?>
<form method="POST" action="">
	<div class="postbox">
		<div class="inside">
			<h3><?php esc_html_e( 'Are you sure you want to reset your tinypass plugin settings?', 'tinypass' ) ?></h3>

			<p><?php esc_html_e( 'This will only reset the settings for the plugin. It will NOT affect any configuration made in Tinypass dashboard.', 'tinypass' ) ?></p>
			<table>
				<tr>
					<td>
						<?php submit_button( __( 'Reset settings', 'tinypass' ), 'primary', 'reset_confirm' ) ?>
					</td>
					<td>
						<?php submit_button( __( 'Cancel', 'tinypass' ), 'primary', 'reset_cancel' ) ?>
					</td>
				</tr>
			</table>
		</div>
	</div>
</form>
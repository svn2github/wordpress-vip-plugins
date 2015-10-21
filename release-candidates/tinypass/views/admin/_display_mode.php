<?php
/**
 * Template with "first click" settings
 * Rendered by @see WPTinypassAdmin
 * @var WPTinypassAdmin $this
 */
?>
<h3><?php esc_html_e( 'Display mode settings', 'tinypass' ) ?></h3>
<table>
	<tr>
		<td>
			<select name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_DISPLAY_MODE ) ) ?>" >

				<option <?php selected( $this::$display_mode == TinypassConfig::DISPLAY_MODE_INLINE ) ?>
					value="<?php echo esc_attr( TinypassConfig::DISPLAY_MODE_INLINE ) ?>"><?php esc_html_e( 'Display offer inline, alongside the content', 'tinypass' ) ?></option>
				<option <?php selected( $this::$display_mode == TinypassConfig::DISPLAY_MODE_MODAL ) ?>
					value="<?php echo esc_attr( TinypassConfig::DISPLAY_MODE_MODAL ) ?>"><?php esc_html_e( 'Display offer in modal window', 'tinypass' ) ?></option>
			</select>
		</td>
	</tr>
</table>
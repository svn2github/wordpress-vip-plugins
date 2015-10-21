<?php
/**
 * Template with "first click" settings
 * Rendered by @see WPTinypassAdmin
 * @var WPTinypassAdmin $this
 */
?>
<h3><?php esc_html_e( 'First click settings' ) ?></h3>
<table>
	<tr>
		<td>
			<select name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_FIRST_CLICK_MODE ) ) ?>"
			        class="tinypass-dynamic-display"
			        tinypass-dynamic-display="<?php echo esc_attr( TinypassConfig::FIRST_CLICK_OPTION_INCLUDE ) ?>|<?php echo esc_attr( TinypassConfig::FIRST_CLICK_OPTION_EXCLUDE ) ?>"
			        rel="#<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_FIRST_CLICK_REFERRERS ) ) ?>">
				<option <?php selected( $this::$first_click_mode == TinypassConfig::FIRST_CLICK_OPTION_NONE ) ?>
					value="<?php echo esc_attr( TinypassConfig::FIRST_CLICK_OPTION_NONE ) ?>"><?php esc_html_e( 'No external referrers are first click free', 'tinypass' ) ?></option>
				<option <?php selected( $this::$first_click_mode == TinypassConfig::FIRST_CLICK_OPTION_ALL ) ?>
					value="<?php echo esc_attr( TinypassConfig::FIRST_CLICK_OPTION_ALL ) ?>"><?php esc_html_e( 'All external referrers are first click free', 'tinypass' ) ?></option>
				<option <?php selected( $this::$first_click_mode == TinypassConfig::FIRST_CLICK_OPTION_INCLUDE ) ?>
					value="<?php echo esc_attr( TinypassConfig::FIRST_CLICK_OPTION_INCLUDE ) ?>"><?php esc_html_e( 'Only the following domains are enabled for first click free', 'tinypass' ) ?></option>
				<option <?php selected( $this::$first_click_mode == TinypassConfig::FIRST_CLICK_OPTION_EXCLUDE ) ?>
					value="<?php echo esc_attr( TinypassConfig::FIRST_CLICK_OPTION_EXCLUDE ) ?>"><?php esc_html_e( 'All domains except for the following are enabled for first click free', 'tinypass' ) ?></option>
			</select>
		</td>
	</tr>
	<!-- This element will only be visible if first click settings are set to be "Only the following domains are enabled for first click free" or "All domains except for the following are enabled for first click free" -->
	<tr id="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_FIRST_CLICK_REFERRERS ) ) ?>"
	    style="display: none">
		<td>
			<textarea style="width: 100%" rows="5"
			          name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_FIRST_CLICK_REFERRERS ) ) ?>"><?php echo esc_textarea( $this::$first_click_ref ) ?></textarea>
		</td>
	</tr>
</table>
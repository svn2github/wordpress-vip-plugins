<?php
/**
 * Template for configuration of debug option
 * Rendered by @see WPTinypassAdmin
 * @var WPTinypassAdmin $this
 */
?>
<p>
	<label><input type="checkbox" name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_DEBUG ) ) ?>"
	              value="1" <?php checked( $this::$debug ) ?>><?php esc_html_e( 'Enable debugger', 'tinypass' ) ?></label>
</p>
<?php
/**
 * Template for configuration of debug option
 * Rendered by @see WPTinypassAdmin
 * @var WPTinypassAdmin $this
 */
?>
<p>
	<label><input type="checkbox" name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_ENABLE_PREMIUM_TAG ) ) ?>"
	              value="1" <?php checked( $this::$enable_premium_tag ) ?>><?php esc_html_e( 'Enable premium tag in post titles', 'tinypass' ) ?></label>
</p>
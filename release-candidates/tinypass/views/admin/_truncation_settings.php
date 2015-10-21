<?php
/**
 * Template for the content truncation settings
 * Rendered by @see WPTinypassAdmin
 * @var WPTinypassAdmin $this
 */
?>

<h3><?php esc_html_e( 'Content truncation settings', 'tinypass' ) ?></h3>
<div class="postbox">
	<div class="inside">
		<table>
			<tr>
				<td>
					<label><input type="radio"
					              name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_TRUNCATION_MODE ) ) ?>"
					              class="tinypass-dynamic-display"
					              tinypass-dynamic-display="<?php echo esc_attr( $this::TRUNCATION_MODE_FILTER ) ?>"
					              rel="#<?php echo esc_attr( $this::TRUNCATION_MODE_FILTER ) ?>"
					              value="<?php echo esc_attr( $this::TRUNCATION_MODE_ALL ) ?>"
							<?php checked( ( $this::$truncation_mode == $this::TRUNCATION_MODE_ALL ) || ! $this::$truncation_mode ) ?>/> <?php esc_html_e( 'Hide all content', 'tinypass' ) ?>
					</label>
				</td>
			</tr>
			<tr>
				<td>
					<label><input type="radio"
					              name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_TRUNCATION_MODE ) ) ?>"
					              class="tinypass-dynamic-display"
					              tinypass-dynamic-display="<?php echo esc_attr( $this::TRUNCATION_MODE_FILTER ) ?>"
					              rel="#<?php echo esc_attr( $this::TRUNCATION_MODE_FILTER ) ?>"
					              value="<?php echo esc_attr( $this::TRUNCATION_MODE_PARAGRAPHED ) ?>"
							<?php checked( ( $this::$truncation_mode == $this::TRUNCATION_MODE_PARAGRAPHED ) ) ?>/> <?php echo sprintf( esc_html__( 'Hide everything after %s paragraphs', 'tinypass' ),
							'<input type="text" name="' . esc_attr( $this::getOptionName( $this::OPTION_NAME_PARAGRAPHS_COUNT ) ) . '" value="' . esc_attr( intval( $this::$paragraphs_count ) ) . '" size="2"/>' ) ?>
					</label>
				</td>
			</tr>
			<tr>
				<td>
					<label><input type="radio"
					              name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_TRUNCATION_MODE ) ) ?>"
					              class="tinypass-dynamic-display"
					              tinypass-dynamic-display="<?php echo esc_attr( $this::TRUNCATION_MODE_FILTER ) ?>"
					              rel="#<?php echo esc_attr( $this::TRUNCATION_MODE_FILTER ) ?>"
					              value="<?php echo esc_attr( $this::TRUNCATION_MODE_TPMORE ) ?>"
							<?php checked( ( $this::$truncation_mode == $this::TRUNCATION_MODE_TPMORE ) ) ?>/> <?php esc_html_e( 'Let me specify myself using "tinypass more" tag', 'tinypass' ) ?>
					</label>
				</td>
			</tr>
			<tr>
				<td>
					<label><input type="radio"
					              name="<?php echo esc_attr( $this::getOptionName( $this::OPTION_NAME_TRUNCATION_MODE ) ) ?>"
					              class="tinypass-dynamic-display"
					              tinypass-dynamic-display="<?php echo esc_attr( $this::TRUNCATION_MODE_FILTER ) ?>"
					              rel="#<?php echo esc_attr( $this::TRUNCATION_MODE_FILTER ) ?>"
					              value="<?php echo esc_attr( $this::TRUNCATION_MODE_FILTER ) ?>"
							<?php checked( ( $this::$truncation_mode == $this::TRUNCATION_MODE_FILTER ) ) ?>/> <?php echo sprintf( esc_html__( 'Let me specify myself using %s filter', 'tinypass' ), '<code>' . $this::WP_FILTER_NO_ACCESS . '</code>' ) ?>
					</label>
					<?php // This block will only be visible if truncation setting is set to be "Let me specify myself using tinypass_no_access filter" ?>
					<div class="inside" id="<?php echo esc_attr( $this::TRUNCATION_MODE_FILTER ) ?>">
                        <pre>
function my_tinypass_truncation ( $content ) {
    // Perform truncation of the $content here
    return $content;
}
add_filter( '<?php echo $this::WP_FILTER_NO_ACCESS ?>', 'my_tinypass_truncation' );</pre>
					</div>
				</td>
			</tr>
		</table>
	</div>
</div>

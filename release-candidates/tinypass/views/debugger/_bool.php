<?php
/**
 * @var $name string
 * @var $title string
 * @var $value string
 */
?>
<tr>
	<td><?php echo esc_html( $name ) ?></td>
	<td><span
			class="tp-debugger-<?php if ( $value ): ?>yes<?php else: ?>no<?php endif ?>"
			id="tp-debugger-<?php echo esc_attr( $title ) ?>"><?php if ( ! $value ): ?><?php esc_html_e( 'No', 'tinypass' ) ?><?php else: ?><?php esc_html_e( 'Yes', 'tinypass' ) ?><?php endif ?></span>
	</td>
</tr>
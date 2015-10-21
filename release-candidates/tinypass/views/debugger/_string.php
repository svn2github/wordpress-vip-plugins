<?php
/**
 * @var $name string
 * @var $title string
 * @var $value string
 */
?>
<tr>
	<td><?php echo esc_html( $title ) ?></td>
	<td><span class="info"
	          id="tp-debugger-<?php echo esc_attr( $name ) ?>"><?php if ( null === $value ): ?>...<?php else: ?><?php echo wp_kses_post( $value ) ?><?php endif ?></span>
	</td>
</tr>
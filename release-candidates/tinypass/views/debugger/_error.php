<?php
/**
 * @var $name string
 * @var $title string
 * @var $value string
 */
?>
<tr>
	<td><strong class="fatal"><?php echo esc_html( $title ) ?></strong></td>
	<td><span class="info fatal"
	          id="tp-debugger-<?php echo esc_attr( $name ) ?>"><?php if ( null === $value ): ?>...<?php else: ?><?php echo wp_kses_post( $value ) ?><?php endif ?></span>
	</td>
</tr>
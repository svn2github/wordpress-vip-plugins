<?php
/**
 * @var $name string
 * @var $title string
 * @var $value array
 */
?>
<tr>
	<td><?php echo esc_html( $title ) ?></td>
	<td><span class="info tp-debugger-array"
	          id="tp-debugger-<?php echo esc_attr( $name ) ?>"><?php if ( ! $value ): ?>N/A<?php else: ?>
				<label>...</label>
				<ul>
					<?php foreach ( $value as $item ): ?>
						<li><?php echo wp_kses_post( $item ) ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif ?>
        </span>
	</td>
</tr>
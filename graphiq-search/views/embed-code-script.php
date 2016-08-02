<div class="ftb-widget" data-width="<?php echo esc_attr( $vars['width'] ); ?>" data-height="<?php echo esc_attr( $vars['height'] ); ?>" data-widget-id="<?php echo esc_attr( $vars['id'] ); ?>" data-frozen="<?php echo esc_attr( $vars['frozen'] ); ?>" data-href="<?php echo esc_url( $vars['link'] ); ?>">
	<?php if ( ! empty( $vars['link'] ) && ! empty( $vars['link_text'] ) ) : ?>
		<a href="<?php echo esc_url( $vars['link'] ); ?>" target="_blank"  style="font:13px/16px arial;color:#6d6d6d;"><?php echo esc_html( $vars['link_text'] ); ?></a>
	<?php endif; ?>
</div>
